<?php

namespace OpenSoutheners\LaravelApiable;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use OpenSoutheners\LaravelApiable\Support\Apiable;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (! empty(Apiable::config('resource_type_map'))) {
            Apiable::modelResourceTypeMap(Apiable::config('resource_type_map'));
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('apiable', function () {
            return new Apiable();
        });

        $this->registerMacros();
    }

    /**
     * Register package macros for framework built-ins.
     *
     * @return void
     */
    public function registerMacros()
    {
        \Illuminate\Testing\TestResponse::mixin(new \OpenSoutheners\LaravelApiable\Testing\TestResponseMacros());
        \Illuminate\Http\Request::mixin(new \OpenSoutheners\LaravelApiable\Http\Request());
        \Illuminate\Database\Eloquent\Builder::mixin(new \OpenSoutheners\LaravelApiable\Builder());
        \Illuminate\Support\Collection::mixin(new \OpenSoutheners\LaravelApiable\Collection());
    }
}
