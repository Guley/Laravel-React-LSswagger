<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //

        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
       // $this->configureDefaults();
        if ($this->app->environment('local', 'development') && 
            config('l5-swagger.defaults.generate_always', false)) {
            
            $this->app->booted(function () {
                try {
                    //\Artisan::call('l5-swagger:generate');
                    //\Log::info('Swagger documentation auto-generated on server start');
                } catch (\Exception $e) {
                    \Log::warning('Failed to auto-generate Swagger docs: ' . $e->getMessage());
                }
            });
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
