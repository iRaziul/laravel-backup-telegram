<?php

declare(strict_types=1);

namespace Larament\BackupTelegram;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Backup\Events\BackupWasSuccessful;

class BackupTelegramServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\InstallCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/backup-telegram.php' => config_path('backup-telegram.php'),
        ], 'backup-telegram-config');

        Event::listen(BackupWasSuccessful::class, SendBackupFile::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/backup-telegram.php',
            'backup-telegram'
        );
    }
}
