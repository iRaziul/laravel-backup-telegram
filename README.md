# Laravel Backup Telegram

[![Latest Version on Packagist](https://img.shields.io/packagist/v/larament/laravel-backup-telegram.svg?style=flat-square)](https://packagist.org/packages/larament/laravel-backup-telegram)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/larament/laravel-backup-telegram/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/larament/laravel-backup-telegram/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/larament/laravel-backup-telegram/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/larament/laravel-backup-telegram/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
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

-   PHP 8.2+
-   Laravel 10, 11, or 12
-   [spatie/laravel-backup](https://github.com/spatie/laravel-backup) v9.3+
-   A Telegram bot and a chat/channel to receive backups

---

## Installation

Install via Composer:

```bash
composer require larament/laravel-backup-telegram
```

Publish the config file (recommended):

```bash
php artisan vendor:publish --tag="backup-telegram-config"
```

---

## Telegram Setup

1. **Create a Telegram Bot:**
    - Talk to [@BotFather](https://t.me/BotFather) on Telegram and create a new bot. Copy the API token.
2. **Add Bot to Your Chat or Channel:**
    - For a private chat, start a conversation with your bot.
    - For a channel, add your bot as an administrator.
3. **Get the Chat ID:**
    - Use [@userinfobot](https://t.me/userinfobot) or [getIDs bot](https://t.me/getidsbot) to find your chat or channel ID.

---

## Configuration

Update your `.env` file or the published `config/laravel-backup-telegram.php`:

```env
BACKUP_TELEGRAM_BOT_TOKEN=your_bot_token
BACKUP_TELEGRAM_CHAT_ID=your_chat_id
BACKUP_TELEGRAM_CHUNK_SIZE=40 # (optional, in MB, default: 40, max: 49)
```

Or in `config/laravel-backup-telegram.php`:

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
