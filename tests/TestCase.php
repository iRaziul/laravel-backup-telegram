<?php

declare(strict_types=1);

namespace Raziul\LaravelBackupTelegram\Tests;

use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Http;
use Mockery;
use Orchestra\Testbench\TestCase as Orchestra;
use Raziul\LaravelBackupTelegram\LaravelBackupTelegramServiceProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Raziul\\LaravelBackupTelegram\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        // Mock HTTP client
        Http::fake();

        // Mock console output
        $this->mockConsoleOutput();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelBackupTelegramServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Set default Telegram config for testing
        config()->set('backup-telegram', [
            'token' => 'test_token',
            'chat_id' => 'test_chat_id',
            'chunk_size' => 49,
        ]);
    }

    /**
     * Mock the console output for testing.
     *
     * @return void
     */
    protected function mockConsoleOutput()
    {
        $output = new BufferedOutput;
        $output->setVerbosity(BufferedOutput::VERBOSITY_VERBOSE);

        $console = new OutputStyle(new ArrayInput([]), $output);

        $this->app->instance('console.output', $console);

        return $output;
    }

    /**
     * Get the console output content.
     */
    protected function getConsoleOutput(): string
    {
        return $this->app->make('console.output')->getOutput()->fetch();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
