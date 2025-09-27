<?php

namespace Bot\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Bot Service Provider
 * Регистрирует команды, конфигурацию и ресурсы пакета
 */
class BotServiceProvider extends ServiceProvider
{
    /**
     * All console commands.
     */
    protected $commands = [
        // Делаем доступными команды без публикации
        \Bot\Console\Commands\HealthCommand::class,
        \Bot\Console\Commands\SetupCommand::class,
        \Bot\Console\Commands\ConfigCommand::class,
        \Bot\Console\Commands\StatsCommand::class,
        \Bot\Console\Commands\WebhookCommand::class,
        \Bot\Console\Commands\MigrateCommand::class,
        \Bot\Console\Commands\PublishCommand::class,
    ];

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
        // Регистрируем команды только для консоли
        if ($this->app->runningInConsole() && !empty($this->commands)) {
            $this->commands($this->commands);
        }
        
        $this->bootPublishing();
        $this->bootRoutes();
    }

    /**
     * Setup publishing of package resources.
     */
    protected function bootPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Конфигурация
            $this->publishes([
                __DIR__ . '/../../config/bot.php' => config_path('bot.php'),
            ], ['bot-config', 'config']);

            // Файлы приложения (боты, команды)
            $this->publishes([
                __DIR__ . '/../../app' => app_path(),
            ], ['bot-app', 'app']);

            // Миграции
            $this->publishes([
                __DIR__ . '/../../database' => database_path(),
            ], ['bot-database', 'database', 'migrations']);

            // Ресурсы I18n
            $this->publishes([
                __DIR__ . '/../../resources/lang' => base_path('resources/lang'),
            ], ['bot-lang', 'lang']);

            // Все файлы сразу
            $pathsToPublish = [
                __DIR__ . '/../../app' => app_path(),
                __DIR__ . '/../../database' => database_path(),
                __DIR__ . '/../../resources' => base_path('resources'),
            ];

            $this->publishes($pathsToPublish, 'bot');
        }
    }

    /**
     * Setup route loading.
     */
    protected function bootRoutes(): void
    {
        // Загружаем роуты ТОЛЬКО из пакета
        $packageRoute = __DIR__ . '/../../routes/bot.php';
        if (file_exists($packageRoute)) {
            \Illuminate\Support\Facades\Route::withoutMiddleware(['web', 'App\\Http\\Middleware\\VerifyCsrfToken'])->group($packageRoute);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return $this->commands;
    }
} 