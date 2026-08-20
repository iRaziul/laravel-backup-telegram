# Laravel Backup Telegram

[![Latest Version on Packagist](https://img.shields.io/packagist/v/larament/laravel-backup-telegram.svg?style=flat-square)](https://packagist.org/packages/larament/laravel-backup-telegram)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/iRaziul/laravel-backup-telegram/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/iRaziul/laravel-backup-telegram/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/iRaziul/laravel-backup-telegram/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/iRaziul/laravel-backup-telegram/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/larament/laravel-backup-telegram.svg?style=flat-square)](https://packagist.org/packages/larament/laravel-backup-telegram)

Easily send your Laravel application's backup files directly to a Telegram chat or channel after each successful backup. This package integrates with [spatie/laravel-backup](https://github.com/spatie/laravel-backup) to automate backup delivery and supports large file splitting for Telegram's file size limits.

---

## Features

-   Automatically sends backup files to Telegram after each successful backup.
-   Supports sending to both private chats and channels.
-   Handles large backup files by splitting them into Telegram-compatible chunks.
-   Simple configuration and seamless integration with Laravel's backup events.

---

## Requirements

-   PHP 8.3+
-   Laravel 12+ / 13+
-   [spatie/laravel-backup](https://github.com/spatie/laravel-backup) v10.0+
-   A Telegram bot and a chat/channel to receive backups

### Compatibility

| Package Version | PHP Version | Laravel Version | spatie/laravel-backup | Status |
|---|---|---|---|---|
| `3.x` | `^8.3` | `12.x`, `13.x` | `^10.0` | Active support |
| `2.x` | `^8.2 \|\| ^8.3` | `10.x`, `11.x`, `12.x` | `^9.3` | Maintenance |

---

## Installation & Quick Setup

1. Install via Composer:

```bash
composer require larament/laravel-backup-telegram
```

2. Run the interactive setup command:

```bash
php artisan backup-telegram:install
```

The CLI wizard will:
- Ask for your **Bot Token** (from [@BotFather](https://t.me/BotFather)) and verify it with Telegram.
- Auto-detect your **Chat / Channel ID** directly from recent Telegram updates (or prompt for manual input).
- Automatically save the credentials to your `.env` file.
- Send an optional test message to verify the connection.

---

## Manual Configuration (Optional)

If you prefer to configure manually or publish the config file:

```bash
php artisan vendor:publish --tag="backup-telegram-config"
```

Update your `.env` file:

```env
BACKUP_TELEGRAM_BOT_TOKEN=your_bot_token
BACKUP_TELEGRAM_CHAT_ID=your_chat_id
BACKUP_TELEGRAM_CHUNK_SIZE=40 # (optional, in MB, default: 40, max: 49)
```

Or in `config/backup-telegram.php`:

```php
return [
    'token' => env('BACKUP_TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('BACKUP_TELEGRAM_CHAT_ID'),
    'chunk_size' => env('BACKUP_TELEGRAM_CHUNK_SIZE', 40), // in megabytes (max 49 MB)
];
```

---

## How it Works

-   On every successful backup (`spatie/laravel-backup` event), the package will automatically send the backup file to your configured Telegram chat/channel.
-   If the file is larger than the Telegram limit (default 40MB), it will be split and sent in parts.

---

## Usage

No manual usage is required! Once installed and configured, the package listens for backup events and sends the files automatically.

If you want to trigger a backup manually:

```bash
php artisan backup:run
```

---

## Advanced: Handling Large Files

-   By default, files larger than 40MB are split into chunks and sent as multiple messages.
-   You can adjust the chunk size in your config, but it cannot exceed 49MB due to Telegram's limitations.

---

## Testing

Run the test suite:

```bash
composer test
```

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

## Contributing

Contributions are welcome! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

---

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

---

## Credits

-   [Raziul Islam](https://raziul.dev)
-   [All Contributors](../../contributors)

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
