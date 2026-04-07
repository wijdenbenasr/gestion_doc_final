<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Interfaces\DocumentRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentDocumentRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            \App\Events\DocumentSubmitted::class,
            \App\Events\DocumentValidated::class,
            \App\Events\DocumentRejected::class,
            \App\Events\DocumentApproved::class,
            \App\Events\DocumentSigned::class,
        ] as $eventClass) {
            \Illuminate\Support\Facades\Event::listen(
                $eventClass,
                \App\Listeners\CreateAuditLogListener::class
            );
        }
    }
}
