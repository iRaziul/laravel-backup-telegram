<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Raziul\LaravelBackupTelegram\SendBackupFile;
use Spatie\Backup\Events\BackupWasSuccessful;

beforeEach(function () {
    // Reset HTTP and config fakes before each test
    Event::fake();
    Storage::fake('local');
});

it('listens to BackupWasSuccessful event', function () {
    Event::assertListening(BackupWasSuccessful::class, SendBackupFile::class);
});
