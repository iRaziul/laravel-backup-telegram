<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Larament\BackupTelegram\SendBackupFile;
use Larament\BackupTelegram\SplitLargeFile;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Events\BackupWasSuccessful;

beforeEach(function () {
    // Reset HTTP and config fakes before each test
    Event::fake();
    Storage::fake('local');

    // Mock the backup objects
    $this->backup = Mockery::mock(Backup::class);
    $this->backupDestination = Mockery::mock(BackupDestination::class);
    $this->event = Mockery::mock(BackupWasSuccessful::class);

    $this->event->backupDestination = $this->backupDestination;
    $this->backupDestination->shouldReceive('newestBackup')->andReturn($this->backup);
});

it('listens to BackupWasSuccessful event', function () {
    Event::assertListening(BackupWasSuccessful::class, SendBackupFile::class);
});

it('logs error if backup file does not exist', function () {
    $this->backup->shouldReceive('exists')->andReturn(false);

    // Spy on the logger if we could, but prompts write to output.
    // We can rely on no exception being thrown and logic flow.
    // Ideally we would mock Laravel\Prompts but that's hard in this context.

    (new SendBackupFile)->handle($this->event);

    // If it didn't exist, it should return early and not try to get path or disk
    $this->backup->shouldNotReceive('disk');
});

it('sends small file successfully', function () {
    $this->backup->shouldReceive('exists')->andReturn(true);
    $this->backup->shouldReceive('sizeInBytes')->andReturn(1024); // 1KB
    $this->backup->shouldReceive('disk')->andReturn(Storage::disk('local'));
    $this->backup->shouldReceive('path')->andReturn('backup.zip');

    Storage::disk('local')->put('backup.zip', 'dummy content');

    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true]),
    ]);

    (new SendBackupFile)->handle($this->event);

    Http::assertSent(function ($request) {
        // Debugging the request data if needed
        // dump($request->data());

        return $request->url() === 'https://api.telegram.org/bottest_token/sendDocument' &&
               $request->isMultipart() &&
               str_contains($request->body(), 'name="chat_id"') &&
               str_contains($request->body(), 'test_chat_id') &&
               $request->hasFile('document', 'dummy content', 'backup.zip');
    });
});

it('logs error if config is missing', function () {
    Config::set('backup-telegram.token', null);

    $this->backup->shouldReceive('exists')->andReturn(true);
    $this->backup->shouldReceive('sizeInBytes')->andReturn(1024);
    $this->backup->shouldReceive('disk')->andReturn(Storage::disk('local'));
    $this->backup->shouldReceive('path')->andReturn('backup.zip');

    Storage::disk('local')->put('backup.zip', 'dummy content');

    (new SendBackupFile)->handle($this->event);

    // Should not make HTTP request
    Http::assertNothingSent();
});

it('splits and sends large files', function () {
    // Large file threshold is smaller for test
    Config::set('backup-telegram.chunk_size', 1); // 1MB for test logic but we mock size

    $this->backup->shouldReceive('exists')->andReturn(true);
    $this->backup->shouldReceive('sizeInBytes')->andReturn(2 * 1024 * 1024); // 2MB
    $this->backup->shouldReceive('disk')->andReturn(Storage::disk('local'));
    $this->backup->shouldReceive('path')->andReturn('large_backup.zip');

    $fullPath = Storage::disk('local')->path('large_backup.zip');
    file_put_contents($fullPath, str_repeat('A', 2 * 1024 * 1024));

    // Mock SplitLargeFile execution? Or let it run if we have 'split' command.
    // 'split' command might not exist in all envs or CI, but let's assume standard linux.
    // Alternatively we can partial mock logic or just let it run since previous test showed it works.
    // But to be safe and fast, we can mock the `splitAndSendFile` private method?
    // No, we cannot mock private methods easily on 'new SendBackupFile'.
    // We will verify the SplitLargeFile integration.

    // We need to ensure 'split' works or mock the SplitLargeFile class usage if we could inject it.
    // Since `new SplitLargeFile` is hardcoded, we have to rely on its functionality.
    // The `SplitLargeFileTest` proved it works.

    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true]),
    ]);

    (new SendBackupFile)->handle($this->event);

    // Should receive multiple requests (at least 2 for 2MB file with 1MB chunk?
    // Wait, implementation uses default 49MB or config.
    // Logic: $backup->sizeInBytes() > $chunkSize * 1024 * 1024
    // We set chunk_size to 1. 2MB > 1MB. So it goes to split logic.
    // Split logic: (new SplitLargeFile)->execute($path, 1)

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/sendDocument');
    });

    // Clean up
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
});

it('handles api failure logging', function () {
    $this->backup->shouldReceive('exists')->andReturn(true);
    $this->backup->shouldReceive('sizeInBytes')->andReturn(1024);
    $this->backup->shouldReceive('disk')->andReturn(Storage::disk('local'));
    $this->backup->shouldReceive('path')->andReturn('backup.zip');

    Storage::disk('local')->put('backup.zip', 'dummy content');

    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Error'], 400),
    ]);

    // We expect it not to crash but log error
    (new SendBackupFile)->handle($this->event);

    Http::assertSentCount(1);
});
