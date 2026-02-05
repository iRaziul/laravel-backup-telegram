# Changelog

All notable changes to `laravel-backup-telegram` will be documented in this file.

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
