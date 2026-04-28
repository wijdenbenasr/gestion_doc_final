<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Repositories\Interfaces\DocumentRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    public function __construct(
        protected DocumentRepositoryInterface $documentRepository
    ) {}

    public function pdf(Document $document)
    {
        $this->authorize('view', $document);

        abort_if($document->status !== 'archived', 403, 'Le PDF final est disponible apres finalisation du document.');

        $document->load(['signatures.user', 'creator', 'versions.creator']);

        // Charger les audit logs seulement pour les admins
        if (auth()->user()->role === 'admin') {
            $document->load('auditLogs.user');
        } else {
            $document->auditLogs = [];
        }

        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='
            .urlencode(route('documents.download', $document));

        $pdf = Pdf::loadView('documents.export.pdf', [
            'document' => $document,
            'generatedAt' => now(),
            'qrCodeUrl' => $qrCodeUrl,
        ])->setPaper('A4', 'portrait');

        $fileName = 'QMS-'.($document->code ?? 'DOC-'.$document->id).'-v'.$document->revision.'.pdf';

        return $pdf->download($fileName);
    }

    public function csv()
    {
        $documents = Document::with(['creator'])->orderBy('id')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="qms_documents_'.now()->format('Y-m-d').'.csv"',
        ];

        $callback = function () use ($documents) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

            fputcsv($out, [
                'ID', 'Nom', 'Code', 'Type', 'AIO', 'Ligne',
                'Phase', 'Nom Phase / Série',
                'Révision', 'Statut', 'Rôle Actuel',
                'Deadline', 'Signé', 'Créateur', 'Créé le', 'Hash SHA-256',
            ]);

            foreach ($documents as $d) {
                fputcsv($out, [
                    $d->id,
                    $d->name,
                    $d->code ?? '',
                    Document::TYPES[$d->type] ?? $d->type,
                    Document::AIOS[$d->aio] ?? strtoupper($d->aio),
                    $d->ligne,
                    $d->phase === 'projet' ? 'Projet' : 'Série',
                    $d->phase === 'projet' ? ($d->nom_phase ?? '') : ($d->nom_serie ?? ''),
                    $d->revision,
                    Document::STATUSES[$d->status] ?? $d->status,
                    $d->current_role ?? '',
                    $d->deadline ? $d->deadline->format('d/m/Y H:i') : '',
                    $d->is_fully_signed ? 'OUI' : 'NON',
                    ($d->creator->name ?? '').' '.($d->creator->prenom ?? ''),
                    $d->created_at->format('d/m/Y H:i'),
                    $d->hash ?? '',
                ]);
            }

            fclose($out);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function pdfExport()
    {
        $documents = Document::with(['creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('documents.export.follow-up', [
            'documents' => $documents,
            'generatedAt' => now(),
        ])->setPaper('A4', 'landscape');

        return $pdf->download('qms_suivi_documents_'.now()->format('Y-m-d').'.pdf');
    }

    public function wordExport()
    {
        $documents = Document::with(['creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        $html = view('documents.export.follow-up', [
            'documents' => $documents,
            'generatedAt' => now(),
        ])->render();

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"></head><body>'.$html.'</body></html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-word; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="qms_suivi_documents_'.now()->format('Y-m-d').'.doc"',
        ];

        return response($html, 200, $headers);
    }

    public function logsDownloadPdf()
    {
        $auditLogs = \App\Models\AuditLog::with(['user', 'auditable'])
            ->orderBy('created_at', 'desc')
            ->get();

        $transmissions = \App\Models\Transmission::with(['user', 'document'])
            ->orderBy('created_at', 'desc')
            ->get();

        $actionLabels = [
            'login' => 'Connexion',
            'logout' => 'Déconnexion',
            'creator signed' => 'Créateur a signé',
            'approver signed' => 'Approbateur a signé',
            'admin signed final' => 'Admin a signé (final)',
            'approver approved' => 'Approbateur a approuvé',
            'validator validated' => 'Validateur a validé',
            'creator sent to validator' => 'Créateur a envoyé au validateur',
            'creator sent to admin' => 'Créateur a envoyé à l\'admin',
            'admin validated' => 'Admin a validé',
            'admin codified' => 'Admin a codifié',
            'document codified' => 'Document codifié',
            'document converted to pdf' => 'Document converti en PDF',
            'user created' => 'Utilisateur créé',
        ];

        $pdf = Pdf::loadView('documents.export.logs', [
            'auditLogs' => $auditLogs,
            'transmissions' => $transmissions,
            'generatedAt' => now(),
            'actionLabels' => $actionLabels,
        ])->setPaper('A4', 'portrait');

        return $pdf->download('qms_journaux_traceabilite_'.now()->format('Y-m-d').'.pdf');
    }

    public function logsDownloadWord()
    {
        $auditLogs = \App\Models\AuditLog::with(['user', 'auditable'])
            ->orderBy('created_at', 'desc')
            ->get();

        $transmissions = \App\Models\Transmission::with(['user', 'document'])
            ->orderBy('created_at', 'desc')
            ->get();

        $actionLabels = [
            'login' => 'Connexion',
            'logout' => 'Déconnexion',
            'creator signed' => 'Créateur a signé',
            'approver signed' => 'Approbateur a signé',
            'admin signed final' => 'Admin a signé (final)',
            'approver approved' => 'Approbateur a approuvé',
            'validator validated' => 'Validateur a validé',
            'creator sent to validator' => 'Créateur a envoyé au validateur',
            'creator sent to admin' => 'Créateur a envoyé à l\'admin',
            'admin validated' => 'Admin a validé',
            'admin codified' => 'Admin a codifié',
            'document codified' => 'Document codifié',
            'document converted to pdf' => 'Document converti en PDF',
            'user created' => 'Utilisateur créé',
        ];

        $html = view('documents.export.logs', [
            'auditLogs' => $auditLogs,
            'transmissions' => $transmissions,
            'generatedAt' => now(),
            'actionLabels' => $actionLabels,
        ])->render();

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"></head><body>'.$html.'</body></html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-word; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="qms_journaux_traceabilite_'.now()->format('Y-m-d').'.doc"',
        ];

        return response($html, 200, $headers);
    }
}
