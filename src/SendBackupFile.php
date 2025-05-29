<?php

declare(strict_types=1);

namespace Raziul\LaravelBackupTelegram;

use Illuminate\Support\Facades\Http;
use Spatie\Backup\Events\BackupWasSuccessful;

final class SendBackupFile
{
    public function handle(BackupWasSuccessful $event): void
    {
        $backup = $event->backupDestination->newestBackup();

        if (! $backup->exists()) {
            consoleOutput()->error('Backup file does not exist.');

            return;
        }

        $chunkSize = min(config('backup-telegram.chunk_size', 49), 49);
        $path = $backup->disk()->path($backup->path());

        $response = $backup->sizeInBytes() > $chunkSize * 1024 * 1024
            ? $this->splitAndSendFile($path, $chunkSize)
            : $this->sendFile($path);

        $response['ok'] ?? false
            ? consoleOutput()->comment('Backup sent to telegram.')
            : consoleOutput()->error('Failed to send backup file to Telegram.');
    }

    /**
     * Send file to telegram.
     */
    private function sendFile(string $filePath): ?array
    {
        $token = config('backup-telegram.token');
        $chatId = config('backup-telegram.chat_id');

        if (empty($token) || empty($chatId)) {
            consoleOutput()->error('Telegram token or chat ID is not configured.');

            return null;
        }

        return Http::timeout(300) // timeout of 5 minutes
            ->attach('document', file_get_contents($filePath), basename($filePath))
            ->post("https://api.telegram.org/bot{$token}/sendDocument", [
                'chat_id' => $chatId,
                'caption' => 'Backup of: '.basename($filePath),
            ])
            ->throw()
            ->json();
    }

    /**
     * Split the file into chunks and send each chunk to Telegram.
     */
    private function splitAndSendFile($backupFile, int $chunkSize): ?array
    {
        consoleOutput()->info('Backup file is too large, splitting into chunks of '.$chunkSize.' MB.');

        $chunks = (new SplitLargeFile)
            ->execute($backupFile->path(), $chunkSize);

        foreach ($chunks as $chunk) {
            $response = $this->sendFile($chunk);
        }

        // clean up the chunks after sending
        foreach ($chunks as $chunk) {
            @unlink($chunk);
        }

        return $response;
    }
}
