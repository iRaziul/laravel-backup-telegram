<?php

declare(strict_types=1);

namespace Larament\BackupTelegram\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup-telegram:install
                            {--token= : The Telegram bot token}
                            {--chat-id= : The Telegram chat or channel ID}
                            {--test : Send a test notification after configuration}
                            {--no-test : Skip sending a test notification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactively configure Laravel Backup Telegram credentials';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        intro('Laravel Backup Telegram Setup');

        $token = $this->resolveToken();

        if (empty($token)) {
            error('A Telegram bot token is required.');

            return self::FAILURE;
        }

        $botData = spin(
            fn () => $this->validateToken($token),
            'Verifying bot token with Telegram...'
        );

        if (! $botData) {
            error('Invalid Telegram bot token or unable to connect to Telegram API.');

            return self::FAILURE;
        }

        $botUsername = $botData['username'] ?? 'Bot';
        $botName = $botData['first_name'] ?? 'Telegram Bot';
        info("Connected to @{$botUsername} ({$botName})");

        $chatId = $this->resolveChatId($token, $botUsername);

        if (empty($chatId)) {
            error('A Telegram chat ID is required.');

            return self::FAILURE;
        }

        $saved = $this->writeEnvironmentVariables([
            'BACKUP_TELEGRAM_BOT_TOKEN' => $token,
            'BACKUP_TELEGRAM_CHAT_ID' => $chatId,
        ]);

        if ($saved) {
            info('Saved Telegram credentials to .env');
        } else {
            warning('Could not automatically write to .env file. Please add the following manually:');
            note("BACKUP_TELEGRAM_BOT_TOKEN={$token}\nBACKUP_TELEGRAM_CHAT_ID={$chatId}");
        }

        $this->handleTestMessage($token, $chatId);

        table(
            ['Configuration', 'Value'],
            [
                ['Bot Username', "@{$botUsername}"],
                ['Chat / Channel ID', (string) $chatId],
                ['Config Status', '.env updated'],
            ]
        );

        note('Backups will be sent automatically to Telegram whenever "php artisan backup:run" executes.', 'info');

        $this->askToStar();

        outro('Laravel Backup Telegram configured successfully!');

        return self::SUCCESS;
    }

    /**
     * Ask the user to star the repository on GitHub.
     */
    protected function askToStar(): void
    {
        if ($this->option('no-interaction')) {
            return;
        }

        if (confirm(label: 'Would you like to star our repo on GitHub? ⭐️', default: true)) {
            $url = 'https://github.com/iraziul/laravel-backup-telegram';

            match (PHP_OS_FAMILY) {
                'Darwin' => exec("open {$url}"),
                'Windows' => exec("start {$url}"),
                'Linux' => exec("xdg-open {$url} 2>/dev/null >/dev/null &"),
                default => null,
            };

            info('Thank you for supporting the project! ⭐️');
        }
    }

    /**
     * Resolve the bot token from option or prompt.
     */
    protected function resolveToken(): ?string
    {
        $token = $this->option('token');

        if (! empty($token)) {
            return (string) $token;
        }

        $existingToken = config('backup-telegram.token');

        return text(
            label: 'Enter your Telegram Bot Token (from @BotFather):',
            placeholder: '123456789:ABCdefGHIjklMNO...',
            default: is_string($existingToken) ? $existingToken : '',
            required: true,
            hint: 'Talk to @BotFather on Telegram to create a bot and get this token.',
        );
    }

    /**
     * Validate bot token with Telegram API.
     */
    protected function validateToken(string $token): ?array
    {
        $response = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getMe");

        if ($response->successful() && ($response->json('ok') ?? false)) {
            return $response->json('result');
        }

        return null;
    }

    /**
     * Resolve destination chat ID by auto-discovery or prompt.
     */
    protected function resolveChatId(string $token, string $botUsername): ?string
    {
        $chatId = $this->option('chat-id');

        if (! empty($chatId)) {
            return (string) $chatId;
        }

        $updates = spin(
            fn () => $this->fetchUpdates($token),
            'Checking for recent Telegram chats...'
        );
        $chats = $this->extractChatsFromUpdates($updates);

        if (count($chats) === 1) {
            $detectedId = (string) array_key_first($chats);
            $detectedLabel = reset($chats);

            $useDetected = confirm(
                label: "Detected chat: {$detectedLabel}. Use this chat for backups?",
                default: true
            );

            if ($useDetected) {
                return $detectedId;
            }
        } elseif (count($chats) > 1) {
            $options = $chats;
            $options['manual'] = 'Enter Chat ID manually...';

            $selection = select(
                label: 'Select the Telegram chat to receive backups:',
                options: $options,
            );

            if ($selection !== 'manual') {
                return (string) $selection;
            }
        }

        if (empty($chats)) {
            info("No recent chats detected for @{$botUsername}.");
            note("Tip: Start a chat and send /start to @{$botUsername}, or add it as an admin to your channel/group.", 'info');

            $retry = confirm(
                label: 'Have you sent a message or added the bot? Check again?',
                default: true
            );

            if ($retry) {
                $updates = spin(
                    fn () => $this->fetchUpdates($token),
                    'Checking for recent Telegram chats...'
                );
                $chats = $this->extractChatsFromUpdates($updates);

                if (! empty($chats)) {
                    $options = $chats;
                    $options['manual'] = 'Enter Chat ID manually...';

                    $selection = select(
                        label: 'Select the Telegram chat to receive backups:',
                        options: $options,
                    );

                    if ($selection !== 'manual') {
                        return (string) $selection;
                    }
                }
            }
        }

        $existingChatId = config('backup-telegram.chat_id');

        return text(
            label: 'Enter your Telegram Chat ID:',
            placeholder: 'e.g. 123456789 or -100123456789',
            default: is_string($existingChatId) ? $existingChatId : '',
            required: true,
            hint: 'Use @userinfobot or invite the bot to your channel to get the ID.',
        );
    }

    /**
     * Fetch recent updates from Telegram.
     */
    protected function fetchUpdates(string $token): array
    {
        $response = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getUpdates");

        if ($response->successful() && ($response->json('ok') ?? false)) {
            return (array) $response->json('result');
        }

        return [];
    }

    /**
     * Extract unique chats and user-friendly labels from Telegram updates.
     *
     * @return array<string, string>
     */
    protected function extractChatsFromUpdates(array $updates): array
    {
        $chats = [];

        foreach ($updates as $update) {
            $chat = $update['message']['chat']
                ?? $update['channel_post']['chat']
                ?? $update['edited_message']['chat']
                ?? $update['my_chat_member']['chat']
                ?? null;

            if (! $chat || ! isset($chat['id'])) {
                continue;
            }

            $chatId = (string) $chat['id'];
            $type = ucfirst($chat['type'] ?? 'chat');

            $name = match ($chat['type'] ?? '') {
                'private' => trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? '')),
                default => $chat['title'] ?? "Chat {$chatId}",
            };

            if (empty($name)) {
                $name = $chat['username'] ?? "Chat {$chatId}";
            }

            $username = ! empty($chat['username']) ? " (@{$chat['username']})" : '';

            $chats[$chatId] = "{$name}{$username} [{$type}] (ID: {$chatId})";
        }

        return $chats;
    }

    /**
     * Optionally send a test message to verify the connection.
     */
    protected function handleTestMessage(string $token, string $chatId): void
    {
        $shouldTest = $this->option('test')
            ? true
            : ($this->option('no-test') ? false : confirm(
                label: 'Would you like to send a test message to Telegram?',
                default: true
            ));

        if (! $shouldTest) {
            return;
        }

        $response = spin(
            fn () => Http::timeout(15)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => "🎉 *Laravel Backup Telegram is configured!*\n\nBackups will automatically be sent to this chat upon successful completion.",
                'parse_mode' => 'Markdown',
            ]),
            'Sending test message to Telegram...'
        );

        if ($response->successful() && ($response->json('ok') ?? false)) {
            info('Test message sent successfully to Telegram!');
        } else {
            $error = $response->json('description') ?? 'Unknown error';
            error("Failed to send test message: {$error}");
        }
    }

    /**
     * Write environment variables to .env file safely.
     */
    protected function writeEnvironmentVariables(array $variables): bool
    {
        $envPath = method_exists($this->laravel, 'environmentFilePath')
            ? $this->laravel->environmentFilePath()
            : base_path('.env');

        if (! file_exists($envPath)) {
            return false;
        }

        $envContent = file_get_contents($envPath);

        if ($envContent === false) {
            return false;
        }

        foreach ($variables as $key => $value) {
            $formattedValue = preg_match('/\s/', (string) $value) ? "\"{$value}\"" : (string) $value;
            $pattern = "/^{$key}=.*/m";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$formattedValue}", $envContent);
            } else {
                $envContent .= (str_ends_with($envContent, "\n") ? '' : "\n") . "{$key}={$formattedValue}\n";
            }
        }

        return file_put_contents($envPath, $envContent) !== false;
    }
}
