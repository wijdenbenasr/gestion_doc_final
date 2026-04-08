<?php

namespace App\Listeners;

use App\Events\DocumentApproved;
use App\Events\DocumentEvent;
use App\Events\DocumentRejected;
use App\Events\DocumentSigned;
use App\Events\DocumentSubmitted;
use App\Events\DocumentValidated;
use App\Services\AuditService;

class CreateAuditLogListener
{
    public function __construct(protected AuditService $auditService) {}

    public function handle(DocumentEvent $event): void
    {
        $action = match (get_class($event)) {
            DocumentSubmitted::class => 'document_submitted',
            DocumentValidated::class => 'document_validated',
            DocumentRejected::class => 'document_rejected',
            DocumentApproved::class => 'document_approved',
            DocumentSigned::class => 'document_signed',
            default => 'document_action',
        };

        $this->auditService->log(
            $event->actor?->id ?? auth()->id(),
            $action,
            $event->document,
            $event->data
        );
    }
}
