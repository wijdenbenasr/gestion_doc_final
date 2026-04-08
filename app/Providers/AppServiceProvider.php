<?php

namespace App\Providers;

use App\Events\DocumentApproved;
use App\Events\DocumentRejected;
use App\Events\DocumentSigned;
use App\Events\DocumentSubmitted;
use App\Events\DocumentValidated;
use App\Listeners\CreateAuditLogListener;
use App\Repositories\Eloquent\EloquentDocumentRepository;
use App\Repositories\Interfaces\DocumentRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            DocumentRepositoryInterface::class,
            EloquentDocumentRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            DocumentSubmitted::class,
            DocumentValidated::class,
            DocumentRejected::class,
            DocumentApproved::class,
            DocumentSigned::class,
        ] as $eventClass) {
            Event::listen(
                $eventClass,
                CreateAuditLogListener::class
            );
        }
    }
}
