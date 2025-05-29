<?php

declare(strict_types=1);

// config for Raziul/LaravelBackupTelegram
return [
    'token' => env('BACKUP_TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('BACKUP_TELEGRAM_CHAT_ID'),
    'chunk_size' => env('BACKUP_TELEGRAM_CHUNK_SIZE', 40), // in megabytes (max 49 MB)
];
