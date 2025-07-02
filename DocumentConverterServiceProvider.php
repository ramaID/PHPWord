<?php

namespace App\Providers;

use App\Services\DocumentConverter;
use Illuminate\Support\ServiceProvider;

class DocumentConverterServiceProvider extends ServiceProvider
{
    /**
     * Register the service.
     */
    public function register(): void
    {
        $this->app->singleton(DocumentConverter::class, function ($app) {
            return new DocumentConverter();
        });
    }

    /**
     * Bootstrap the service.
     */
    public function boot(): void
    {
        // Publish configuration file if needed in the future
        // $this->publishes([
        //     __DIR__.'/../../config/document-converter.php' => config_path('document-converter.php'),
        // ], 'document-converter-config');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [DocumentConverter::class];
    }
}