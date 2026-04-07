<?php

use App\Models\Document;
use App\Notifications\DocumentDeadlineExpired;
use App\Notifications\DocumentDeadlineSoon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('documents:check-deadlines', function () {
    $soon = now()->addDay();

    $documentsSoon = Document::whereNotNull('deadline')
        ->where('status', 'in_validation')
        ->whereBetween('deadline', [now(), $soon])
        ->get();

    foreach ($documentsSoon as $document) {
        $creator = $document->creator;

        if ($creator) {
            Notification::send($creator, new DocumentDeadlineSoon($document));
        }
    }

    $expiredDocuments = Document::whereNotNull('deadline')
        ->where('status', 'in_validation')
        ->where('deadline', '<', now())
        ->get();

    foreach ($expiredDocuments as $document) {
        $creator = $document->creator;

        if ($creator) {
            Notification::send($creator, new DocumentDeadlineExpired($document));
        }
    }
})->purpose('Envoyer les notifications de deadlines documentaires');
