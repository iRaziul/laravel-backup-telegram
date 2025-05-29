<?php

declare(strict_types=1);

use Raziul\LaravelBackupTelegram\SplitLargeFile;

beforeEach(function () {
    $this->splitter = new SplitLargeFile;
    $this->tempDir = sys_get_temp_dir();
});

it('throws if the file does not exist', function () {
    $missing = $this->tempDir . '/no-such-file-' . uniqid() . '.bin';

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage("File does not exist: {$missing}");

    $this->splitter->execute($missing);
});

it('splits a file into two chunks', function () {
    // create a dummy file
    $file = "{$this->tempDir}/test-split-" . uniqid() . '.dat';
    file_put_contents($file, str_repeat('A', 2 * 1024 * 1024));

    // splits into 1MB chunks
    $parts = $this->splitter->execute($file, 1);

    expect($parts)
        ->toBeArray()
        ->toHaveCount(2);

    // cleanup
    unlink($file);
    foreach ($parts as $part) {
        @unlink($part);
    }
});

it('throws when the split command fails', function () {
    // create a dir so split -b reads a directory and errors
    $dir = "{$this->tempDir}/test-split-dir-" . uniqid();
    mkdir($dir);

    $this->expectException(RuntimeException::class);
    // match the start of the message, error output may vary
    $this->expectExceptionMessageMatches(
        '/^Failed to split file: ' . preg_quote($dir, '/') . '\\. Error: /'
    );

    try {
        $this->splitter->execute($dir, 1);
    } finally {
        rmdir($dir);
    }
});
