<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function __invoke(Request $request, Document $document)
    {
        $user = Auth::user();
        $result = $this->resolveFileForDownload($document, $user);

        if (!$result) {
            return back()->with('error', 'Fichier introuvable.');
        }

        $filePath = $result['file'];
        $filename = $result['name'];
        $disk = $result['disk'];

        if (!$filePath || !Storage::disk($disk)->exists($filePath)) {
            return back()->with('error', 'Fichier introuvable.');
        }

        Log::info('Document downloaded', [
            'document_id' => $document->id,
            'user_id' => $user->id,
            'role' => $user->role,
            'status' => $document->status,
            'file_path' => $filePath,
        ]);

        return Storage::disk($disk)->download($filePath, $filename);
    }

    public function view(Request $request, Document $document)
    {
        $user = Auth::user();
        $result = $this->resolveFileForDownload($document, $user);

        if (!$result) {
            return back()->with('error', 'Fichier introuvable.');
        }

        $filePath = $result['file'];
        $filename = $result['name'];
        $disk = $result['disk'];

        if (!$filePath || !Storage::disk($disk)->exists($filePath)) {
            return back()->with('error', 'Fichier introuvable.');
        }

        $fullPath = Storage::disk($disk)->path($filePath);
        $mimeType = Storage::disk($disk)->mimeType($filePath) ?? 'application/octet-stream';

        // Force correct MIME type for PDFs
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf') {
            $mimeType = 'application/pdf';
        }

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function resolveFileForDownload(Document $document, $user)
    {
        $status = strtolower($document->status);

        $typeMap = [
            'codification'        => 'original',
            'en_codification'     => 'original',
            'in_codification'     => 'original',
            'pending_codification' => 'original',
            'in_validation'       => 'original',
            'en_validation'       => 'original',
            'en_modification'     => 'original',
            'in_modification'     => 'original',
            'draft'               => 'original',
            'brouillon'           => 'original',
            'signing_validator'   => 'pdf_signe_createur',
            'signature_validateur' => 'pdf_signe_createur',
            'in_approbation'      => 'pdf_signe_validateur',
            'en_approbation'      => 'pdf_signe_validateur',
            'signing_approver'    => 'pdf_signe_validateur',
            'signature_approbateur' => 'pdf_signe_validateur',
            'validation_admin'    => 'pdf_signe_approbateur',
            'signing_admin'       => 'pdf_signe_approbateur',
            'signature_admin'     => 'pdf_signe_approbateur',
            'archived'            => 'pdf_signe_final',
            'archive'             => 'pdf_signe_final',
            'finalise'            => 'pdf_signe_final',
            'ready_for_pdf'       => 'pdf_converted',
            'pdf_ready'           => 'pdf_converted',
            'pdf_converted'       => 'pdf_converted',
            'pdf_converti'        => 'pdf_converted',
        ];

        if (isset($typeMap[$status])) {
            $versionType = $typeMap[$status];

            if ($versionType === 'original') {
                $original = $this->resolveOriginalFile($document);
                if ($original) {
                    return $original;
                }
                $modified = $document->versions()
                    ->where('type', 'modified')
                    ->latest()
                    ->first();
                if ($modified && $modified->file_path) {
                    if (Storage::disk('private')->exists($modified->file_path)) {
                        return ['file' => $modified->file_path, 'name' => $modified->original_name ?? basename($modified->file_path), 'disk' => 'private'];
                    }
                    if (Storage::disk('public')->exists($modified->file_path)) {
                        return ['file' => $modified->file_path, 'name' => $modified->original_name ?? basename($modified->file_path), 'disk' => 'public'];
                    }
                }
            } else {
                $version = $document->versions()
                    ->where('type', $versionType)
                    ->latest()
                    ->first();

                if ($version && $version->file_path) {
                    if (Storage::disk('private')->exists($version->file_path)) {
                        $ext = pathinfo($version->file_path, PATHINFO_EXTENSION) ?: 'pdf';
                        $filename = ($document->code ?: 'doc') . '_' . str_replace(' ', '_', $document->name) . '_' . $versionType . '.' . $ext;
                        return ['file' => $version->file_path, 'name' => $filename, 'disk' => 'private'];
                    }
                    if (Storage::disk('public')->exists($version->file_path)) {
                        $ext = pathinfo($version->file_path, PATHINFO_EXTENSION) ?: 'pdf';
                        $filename = ($document->code ?: 'doc') . '_' . str_replace(' ', '_', $document->name) . '_' . $versionType . '.' . $ext;
                        return ['file' => $version->file_path, 'name' => $filename, 'disk' => 'public'];
                    }
                }
            }
        }

        if ($status === 'archived' || $status === 'archive' || $status === 'finalise') {
            $archive = $document->archives()->latest()->first();
            if ($archive && isset($archive->file_path) && $archive->file_path) {
                if (Storage::disk('private')->exists($archive->file_path)) {
                    $ext = pathinfo($archive->file_path, PATHINFO_EXTENSION) ?: 'pdf';
                    $filename = ($document->code ?: 'doc') . '_' . str_replace(' ', '_', $document->name) . '_final.' . $ext;
                    return ['file' => $archive->file_path, 'name' => $filename, 'disk' => 'private'];
                }
                if (Storage::disk('public')->exists($archive->file_path)) {
                    $ext = pathinfo($archive->file_path, PATHINFO_EXTENSION) ?: 'pdf';
                    $filename = ($document->code ?: 'doc') . '_' . str_replace(' ', '_', $document->name) . '_final.' . $ext;
                    return ['file' => $archive->file_path, 'name' => $filename, 'disk' => 'public'];
                }
            }
        }

        return $this->getAnyAvailableFile($document);
    }

    private function getAnyAvailableFile(Document $document)
    {
        $latestVersion = $document->versions()->latest()->first();
        if ($latestVersion && $latestVersion->file_path) {
            if (Storage::disk('private')->exists($latestVersion->file_path)) {
                return [
                    'file' => $latestVersion->file_path,
                    'name' => $latestVersion->original_name ?? basename($latestVersion->file_path),
                    'disk' => 'private'
                ];
            }
            if (Storage::disk('public')->exists($latestVersion->file_path)) {
                return [
                    'file' => $latestVersion->file_path,
                    'name' => $latestVersion->original_name ?? basename($latestVersion->file_path),
                    'disk' => 'public'
                ];
            }
        }

        if ($document->file_path) {
            if (Storage::disk('private')->exists($document->file_path)) {
                return [
                    'file' => $document->file_path,
                    'name' => $document->file_original_name ?? basename($document->file_path),
                    'disk' => 'private'
                ];
            }
            if (Storage::disk('public')->exists($document->file_path)) {
                return [
                    'file' => $document->file_path,
                    'name' => $document->file_original_name ?? basename($document->file_path),
                    'disk' => 'public'
                ];
            }
        }

        return null;
    }

    private function resolveOriginalFile(Document $document)
    {
        if ($document->file_path) {
            if (Storage::disk('private')->exists($document->file_path)) {
                return [
                    'file' => $document->file_path,
                    'name' => $document->file_original_name ?? basename($document->file_path),
                    'disk' => 'private',
                ];
            }
            if (Storage::disk('public')->exists($document->file_path)) {
                return [
                    'file' => $document->file_path,
                    'name' => $document->file_original_name ?? basename($document->file_path),
                    'disk' => 'public',
                ];
            }
        }

        $version = $document->versions()->where('type', 'original')->latest()->first();
        if ($version && $version->file_path) {
            if (Storage::disk('private')->exists($version->file_path)) {
                return [
                    'file' => $version->file_path,
                    'name' => $version->original_name ?? basename($version->file_path),
                    'disk' => 'private',
                ];
            }
            if (Storage::disk('public')->exists($version->file_path)) {
                return [
                    'file' => $version->file_path,
                    'name' => $version->original_name ?? basename($version->file_path),
                    'disk' => 'public',
                ];
            }
        }

        return null;
    }

    private function resolveConvertedPdf(Document $document)
    {
        $version = $document->versions()->where('type', 'pdf_converted')->latest()->first();

        if ($version && $version->file_path) {
            if (Storage::disk('public')->exists($version->file_path)) {
                return [
                    'file' => $version->file_path,
                    'name' => $version->original_name ?? (($document->code ?: 'doc') . '_' . str_replace(' ', '_', $document->name) . '_converti.pdf'),
                    'disk' => 'public'
                ];
            }
            if (Storage::disk('private')->exists($version->file_path)) {
                return [
                    'file' => $version->file_path,
                    'name' => $version->original_name ?? (($document->code ?: 'doc') . '_' . str_replace(' ', '_', $document->name) . '_converti.pdf'),
                    'disk' => 'private'
                ];
            }
        }

        $pdfPath = 'converted_pdfs/' . $document->code . '_' . str_replace(' ', '_', $document->name) . '.pdf';
        if (Storage::disk('public')->exists($pdfPath)) {
            return [
                'file' => $pdfPath,
                'name' => ($document->code ?: 'doc') . '_' . str_replace(' ', '_', $document->name) . '_converti.pdf',
                'disk' => 'public'
            ];
        }
        if (Storage::disk('private')->exists($pdfPath)) {
            return [
                'file' => $pdfPath,
                'name' => ($document->code ?: 'doc') . '_' . str_replace(' ', '_', $document->name) . '_converti.pdf',
                'disk' => 'private'
            ];
        }

        return null;
    }
}
