<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->envPath = method_exists($this->app, 'environmentFilePath')
        ? $this->app->environmentFilePath()
        : base_path('.env');

    if (! file_exists(dirname($this->envPath))) {
        mkdir(dirname($this->envPath), 0755, true);
    }

    file_put_contents($this->envPath, "APP_NAME=Laravel\n");
});

afterEach(function () {
    if (file_exists($this->envPath)) {
        unlink($this->envPath);
    }
});

it('fails when token validation fails', function () {
    Http::fake([
        '*/botinvalid_token/getMe*' => Http::response([
            'ok' => false,
            'description' => 'Unauthorized',
        ], 401),
    ]);

    $this->artisan('backup-telegram:install', [
        '--token' => 'invalid_token',
        '--chat-id' => '123456',
        '--no-test' => true,
    ])->assertFailed();
});

it('installs successfully with options and updates .env', function () {
    Http::fake([
        '*/bot123456:valid_token/getMe*' => Http::response([
            'ok' => true,
            'result' => [
                'id' => 123456,
                'is_bot' => true,
                'first_name' => 'Backup Bot',
                'username' => 'test_backup_bot',
            ],
        ]),
    ]);

    $this->artisan('backup-telegram:install', [
        '--token' => '123456:valid_token',
        '--chat-id' => '987654321',
        '--no-test' => true,
    ])
        ->expectsConfirmation('Would you like to star our repo on GitHub? ⭐️', 'no')
        ->assertSuccessful();

    $envContent = file_get_contents($this->envPath);
    expect($envContent)
        ->toContain('BACKUP_TELEGRAM_BOT_TOKEN=123456:valid_token')
        ->toContain('BACKUP_TELEGRAM_CHAT_ID=987654321');
});

it('overwrites existing telegram environment variables in .env', function () {
    file_put_contents($this->envPath, "BACKUP_TELEGRAM_BOT_TOKEN=old_token\nBACKUP_TELEGRAM_CHAT_ID=old_chat\n");

    Http::fake([
        '*/bot123456:valid_token/getMe*' => Http::response([
            'ok' => true,
            'result' => [
                'id' => 123456,
                'is_bot' => true,
                'first_name' => 'Backup Bot',
                'username' => 'test_backup_bot',
            ],
        ]),
    ]);

    $this->artisan('backup-telegram:install', [
        '--token' => '123456:valid_token',
        '--chat-id' => '987654321',
        '--no-test' => true,
    ])
        ->expectsConfirmation('Would you like to star our repo on GitHub? ⭐️', 'no')
        ->assertSuccessful();

    $envContent = file_get_contents($this->envPath);
    expect($envContent)
        ->toContain('BACKUP_TELEGRAM_BOT_TOKEN=123456:valid_token')
        ->toContain('BACKUP_TELEGRAM_CHAT_ID=987654321')
        ->not->toContain('old_token')
        ->not->toContain('old_chat');
});

it('sends a test message when --test option is provided', function () {
    Http::fake([
        '*/bot123456:valid_token/getMe*' => Http::response([
            'ok' => true,
            'result' => [
                'id' => 123456,
                'is_bot' => true,
                'first_name' => 'Backup Bot',
                'username' => 'test_backup_bot',
            ],
        ]),
        '*/bot123456:valid_token/sendMessage*' => Http::response([
            'ok' => true,
            'result' => ['message_id' => 10],
        ]),
    ]);

    $this->artisan('backup-telegram:install', [
        '--token' => '123456:valid_token',
        '--chat-id' => '987654321',
        '--test' => true,
    ])
        ->expectsConfirmation('Would you like to star our repo on GitHub? ⭐️', 'no')
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/bot123456:valid_token/sendMessage')
            && $request['chat_id'] === '987654321';
    });
});

it('auto-detects single chat ID from telegram updates', function () {
    Http::fake([
        '*/bot123456:valid_token/getMe*' => Http::response([
            'ok' => true,
            'result' => [
                'id' => 123456,
                'is_bot' => true,
                'first_name' => 'Backup Bot',
                'username' => 'test_backup_bot',
            ],
        ]),
        '*/bot123456:valid_token/getUpdates*' => Http::response([
            'ok' => true,
            'result' => [
                [
                    'update_id' => 100,
                    'message' => [
                        'message_id' => 1,
                        'from' => ['id' => 555123, 'first_name' => 'Jane', 'username' => 'janedoe'],
                        'chat' => ['id' => 555123, 'first_name' => 'Jane', 'username' => 'janedoe', 'type' => 'private'],
                        'text' => '/start',
                    ],
                ],
            ],
        ]),
    ]);

    $this->artisan('backup-telegram:install', [
        '--token' => '123456:valid_token',
        '--no-test' => true,
    ])
        ->expectsConfirmation('Detected chat: Jane (@janedoe) [Private] (ID: 555123). Use this chat for backups?', 'yes')
        ->expectsConfirmation('Would you like to star our repo on GitHub? ⭐️', 'yes')
        ->assertSuccessful();

    $envContent = file_get_contents($this->envPath);
    expect($envContent)->toContain('BACKUP_TELEGRAM_CHAT_ID=555123');
});

it('allows selecting from multiple detected chats', function () {
    Http::fake([
        '*/bot123456:valid_token/getMe*' => Http::response([
            'ok' => true,
            'result' => [
                'id' => 123456,
                'is_bot' => true,
                'first_name' => 'Backup Bot',
                'username' => 'test_backup_bot',
            ],
        ]),
        '*/bot123456:valid_token/getUpdates*' => Http::response([
            'ok' => true,
            'result' => [
                [
                    'update_id' => 100,
                    'message' => [
                        'message_id' => 1,
                        'chat' => ['id' => 111, 'first_name' => 'Alice', 'type' => 'private'],
                        'text' => 'Hi',
                    ],
                ],
                [
                    'update_id' => 101,
                    'channel_post' => [
                        'message_id' => 2,
                        'chat' => ['id' => -100222, 'title' => 'Dev Backups', 'type' => 'channel'],
                        'text' => 'Test',
                    ],
                ],
            ],
        ]),
    ]);

    $this->artisan('backup-telegram:install', [
        '--token' => '123456:valid_token',
        '--no-test' => true,
    ])
        ->expectsChoice(
            'Select the Telegram chat to receive backups:',
            '-100222',
            [
                '111' => 'Alice [Private] (ID: 111)',
                '-100222' => 'Dev Backups [Channel] (ID: -100222)',
                'manual' => 'Enter Chat ID manually...',
            ]
        )
        ->expectsConfirmation('Would you like to star our repo on GitHub? ⭐️', 'no')
        ->assertSuccessful();

    $envContent = file_get_contents($this->envPath);
    expect($envContent)->toContain('BACKUP_TELEGRAM_CHAT_ID=-100222');
});

it('handles empty updates and prompts for manual chat ID', function () {
    Http::fake([
        '*/bot123456:valid_token/getMe*' => Http::response([
            'ok' => true,
            'result' => [
                'id' => 123456,
                'is_bot' => true,
                'first_name' => 'Backup Bot',
                'username' => 'test_backup_bot',
            ],
        ]),
        '*/bot123456:valid_token/getUpdates*' => Http::response([
            'ok' => true,
            'result' => [],
        ]),
    ]);

    $this->artisan('backup-telegram:install', [
        '--token' => '123456:valid_token',
        '--no-test' => true,
    ])
        ->expectsConfirmation('Have you sent a message or added the bot? Check again?', 'no')
        ->expectsQuestion('Enter your Telegram Chat ID:', '998877')
        ->expectsConfirmation('Would you like to star our repo on GitHub? ⭐️', 'no')
        ->assertSuccessful();

    $envContent = file_get_contents($this->envPath);
    expect($envContent)->toContain('BACKUP_TELEGRAM_CHAT_ID=998877');
});

it('prompts to star the repo with yes as default', function () {
    Http::fake([
        '*/bot123456:valid_token/getMe*' => Http::response([
            'ok' => true,
            'result' => [
                'id' => 123456,
                'is_bot' => true,
                'first_name' => 'Backup Bot',
                'username' => 'test_backup_bot',
            ],
        ]),
    ]);

    $this->artisan('backup-telegram:install', [
        '--token' => '123456:valid_token',
        '--chat-id' => '987654321',
        '--no-test' => true,
    ])
        ->expectsConfirmation('Would you like to star our repo on GitHub? ⭐️', 'yes')
        ->assertSuccessful();
});
