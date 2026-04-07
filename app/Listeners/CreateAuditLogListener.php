<?php

namespace App\Listeners;

use App\Events\DocumentEvent;
use App\Services\AuditService;

class CreateAuditLogListener
{
    public function __construct(protected AuditService $auditService) {}

    public function handle(DocumentEvent $event): void
    {
        $action = match (get_class($event)) {
            \App\Events\DocumentSubmitted::class => 'document_submitted',
            \App\Events\DocumentValidated::class => 'document_validated',
            \App\Events\DocumentRejected::class => 'document_rejected',
            \App\Events\DocumentApproved::class => 'document_approved',
            \App\Events\DocumentSigned::class => 'document_signed',
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
