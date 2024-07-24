<?php

namespace Tests\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Tests\BaseTestCase;

class PublishCommandTest extends BaseTestCase
{
    protected Filesystem $filesystem;

    public function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
    }

    public function test_adds_sample_file_into_settings(): void
    {
        $this->artisan('settings:publish')
            ->expectsOutput("Manifest published. Check it at: {$this->app->basePath('app/UserPreferences/users.php')}")
            ->assertExitCode(0);

        static::assertFileExists($this->app->basePath('app/UserPreferences/users.php'));
    }

    public function test_confirms_manifest_replace(): void
    {
        $this->filesystem->ensureDirectoryExists($this->app->basePath('app/UserPreferences'));
        $this->filesystem->put($this->app->basePath('app/UserPreferences/users.php'), '');

        $this->artisan('settings:publish')
            ->expectsConfirmation('A manifest file already exists. Overwrite?')
            ->assertExitCode(0);

        static::assertFileExists($this->app->basePath('app/UserPreferences/users.php'));
        static::assertStringEqualsFile(
            $this->app->basePath('app/UserPreferences/users.php'),
            ''
        );
    }

    public function test_replaces_manifest_once_confirmed(): void
    {
        $this->filesystem->ensureDirectoryExists($this->app->basePath('app/UserPreferences'));
        $this->filesystem->put($this->app->basePath('app/UserPreferences/users.php'), '');

        $this->artisan('settings:publish')
            ->expectsConfirmation('A manifest file already exists. Overwrite?', 'yes')
            ->expectsOutput("Manifest published. Check it at: {$this->app->basePath('app/UserPreferences/users.php')}")
            ->assertExitCode(0);

        static::assertFileExists($this->app->basePath('app/UserPreferences/users.php'));

        sleep(10);

        static::assertFileEquals(
            __DIR__ . '/../../../stubs/users.php',
            $this->app->basePath('app/UserPreferences/users.php')
        );
    }

    public function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->app->basePath('app/UserPreferences'));

        parent::tearDown();
    }
}
