<?php

declare(strict_types=1);

namespace Raziul\LaravelBackupTelegram;

use RuntimeException;
use Symfony\Component\Process\Process;

final class SplitLargeFile
{
    /**
     * Split a large file into smaller chunks.
     */
    public function execute(string $filePath, int $chunkSizeInMegabytes = 49): array
    {
        if (! file_exists($filePath)) {
            throw new RuntimeException("File does not exist: {$filePath}");
        }

        $process = Process::fromShellCommandline(
            "split -b {$chunkSizeInMegabytes}M {$filePath} {$filePath}.part."
        );
        $result = $process->run();

        if ($result !== 0) {
            throw new RuntimeException("Failed to split file: {$filePath}. Error: {$process->getErrorOutput()}");
        }

        return glob("{$filePath}.part.*");
    }
}
