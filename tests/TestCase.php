<?php

namespace OpenSoutheners\LaravelApiable\Tests;

use Laravel\Scout\ScoutServiceProvider;
use OpenSoutheners\LaravelApiable\ServiceProvider;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Plan;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Tag;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app)
    {
        return [
            ScoutServiceProvider::class,
            ServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup package own config (statuses)
        $app['config']->set('apiable', include __DIR__.'/../config/apiable.php');
        $app['config']->set('apiable.resource_type_map', [
            Plan::class => 'plan',
            Post::class => 'post',
            Tag::class => 'label',
            User::class => 'client',
        ]);
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__.'/database');
    }
}
