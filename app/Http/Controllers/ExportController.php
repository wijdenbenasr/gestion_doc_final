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

        abort_if($document->status !== 'finalized', 403, 'Le PDF final est disponible apres finalisation du document.');

        $document->load(['signatures.user', 'creator', 'auditLogs.user', 'versions.creator']);

        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='
            . urlencode(route('documents.download', $document));

        $pdf = Pdf::loadView('documents.export.pdf', [
            'document'    => $document,
            'generatedAt' => now(),
            'qrCodeUrl'   => $qrCodeUrl,
        ])->setPaper('A4', 'portrait');

        $fileName = 'QMS-' . ($document->code ?? 'DOC-' . $document->id) . '-v' . $document->revision . '.pdf';

        return $pdf->download($fileName);
    }

    public function csv()
    {
        $documents = Document::with(['creator'])->orderBy('id')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="qms_documents_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($documents) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

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
                    Document::AIOS[$d->aio]   ?? strtoupper($d->aio),
                    $d->ligne,
                    $d->phase === 'projet' ? 'Projet' : 'Série',
                    $d->phase === 'projet' ? ($d->nom_phase ?? '') : ($d->nom_serie ?? ''),
                    $d->revision,
                    Document::STATUSES[$d->status] ?? $d->status,
                    $d->current_role ?? '',
                    $d->deadline ? $d->deadline->format('d/m/Y H:i') : '',
                    $d->is_fully_signed ? 'OUI' : 'NON',
                    ($d->creator->name ?? '') . ' ' . ($d->creator->prenom ?? ''),
                    $d->created_at->format('d/m/Y H:i'),
                    $d->hash ?? '',
                ]);
            }

            fclose($out);
        };

        return Response::stream($callback, 200, $headers);
    }
}
