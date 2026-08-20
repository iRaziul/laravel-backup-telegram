# Changelog

All notable changes to `laravel-backup-telegram` will be documented in this file.

## v3.1.0 - 2026-08-20

### 🚀 Added

- Added `php artisan backup-telegram:install` interactive setup wizard powered by Laravel Prompts (`intro`, `outro`, `spin`, `note`, `table`, `confirm`, `select`).
- Added automated Telegram bot token validation via `/getMe`.
- Added Telegram chat and channel ID auto-discovery from `/getUpdates`.
- Added automated `.env` updating for `BACKUP_TELEGRAM_BOT_TOKEN` and `BACKUP_TELEGRAM_CHAT_ID`.
- Added optional test notification delivery to verify connections immediately.
- Added interactive GitHub repository star prompt.
- Added comprehensive test suite for the installation command.

### 🐛 Fixed

- Improved Telegram API error handling in `SendBackupFile` to prevent uncaught `RequestException` crashes.

## v3.0.0 - Spatie Laravel Backup v10 Compatibility - 2026-08-19

🚀 Added

- Added support for `spatie/laravel-backup` v10 (resolving backup destinations from primitive event properties `$diskName` and `$backupName`).
- Maintained backwards compatibility with `spatie/laravel-backup` v9.

🔄 Changed

- Updated `composer.json` constraint for `spatie/laravel-backup` to `^10.0`.
- Updated dev dependencies to support Pest `^3.0||^4.0`.
- Updated GitHub Actions CI test matrix.

## v2.0.0 - Package Rebranding & Modernization - 2026-02-05

⚠️ Breaking Changes

- **Package renamed:** raziul/laravel-backup-telegram → larament/laravel-backup-telegram
- **Namespace changed:** Raziul\LaravelBackupTelegram → Larament\BackupTelegram
- **Environment variable renamed:** BACKUP_TELEGRAM_TOKEN → BACKUP_TELEGRAM_BOT_TOKEN
- **Default chunk size:** Changed from 49MB to 40MB

🔄 Changed

- Removed dependency on spatie/laravel-package-tools - now uses standard Laravel
- ServiceProvider
- Simplified service provider with manual config publishing and event registration
- Updated README with correct package name and configuration details

🐛 Fixed

- Fixed bug in SendBackupFile::splitAndSendFile() where ->path() was incorrectly called on a string

✅ Improved

- Added comprehensive test coverage for SendBackupFile (0% → 97%)
- Test scenarios include: missing backup file, small file sending, large file splitting, missing config, and API failure handling

## v1.0 - 2025-05-29

**Full Changelog**: https://github.com/iRaziul/laravel-backup-telegram/commits/v1.0
