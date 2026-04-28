<?php

use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentDeadlineExpired;
use App\Notifications\DocumentDeadlineSoon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schedule;

if (!function_exists('getRecipientsForDocument')) {
    function getRecipientsForDocument(Document $document): \Illuminate\Database\Eloquent\Collection
    {
        return match ($document->current_role) {
            'creator' => $document->creator ? collect([$document->creator]) : collect(),
            'validator' => User::where('role', 'validator')->where('is_admin_approved', true)->get(),
            'approver' => User::where('role', 'approver')->where('is_admin_approved', true)->get(),
            'admin' => User::where('role', 'admin')->where('is_admin_approved', true)->get(),
            default => collect(),
        };
    }
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('documents:check-deadlines', function () {
    $documents = Document::whereNotNull('deadline')
        ->whereIn('status', ['in_validation', 'pending_codification'])
        ->get();

    $soon = now()->addDays(2);

    foreach ($documents as $document) {
        $recipients = getRecipientsForDocument($document);

        foreach ($recipients as $recipient) {
            if ($document->deadline->isPast()) {
                Notification::send($recipient, new DocumentDeadlineExpired($document));
            } elseif ($document->deadline->lessThanOrEqualTo($soon)) {
                Notification::send($recipient, new DocumentDeadlineSoon($document));
            }
        }
    }

    $this->info('Deadline check completed for ' . $documents->count() . ' document(s)');
})->purpose('Envoyer les notifications de deadlines documentaires');

Schedule::command('documents:check-deadlines')
    ->dailyAt('00:00')
    ->timezone(config('app.timezone'));

Schedule::command('documents:send-daily-open-alerts')
    ->dailyAt('00:00')
    ->timezone(config('app.timezone'));
