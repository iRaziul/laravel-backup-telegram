<?php

declare(strict_types=1);

namespace Raziul\LaravelBackupTelegram;

use Illuminate\Support\Facades\Event;
use Spatie\Backup\Events\BackupWasSuccessful;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelBackupTelegramServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-backup-telegram')
            ->hasConfigFile();
    }

    public function packageBooted(): void
    {
        Event::listen(BackupWasSuccessful::class, SendBackupFile::class);
    }
}
