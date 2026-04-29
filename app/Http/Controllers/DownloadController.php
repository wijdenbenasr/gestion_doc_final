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
        $role = $user->role;

        $result = $this->resolveFileForDownload($document, $user);

        if (!$result) {
            abort(403, 'Téléchargement non autorisé pour cette phase du workflow.');
        }

        $filePath = $result['file'];
        $filename = $result['name'];
        $disk = $result['disk'];

        if (!$filePath || !Storage::disk($disk)->exists($filePath)) {
            abort(404, 'Fichier introuvable sur le disque.');
        }

        Log::info('Document downloaded', [
            'document_id' => $document->id,
            'user_id' => $user->id,
            'role' => $role,
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
            abort(403, 'Visualisation non autorisée pour cette phase du workflow.');
        }

        $filePath = $result['file'];
        $filename = $result['name'];
        $disk = $result['disk'];

        if (!$filePath || !Storage::disk($disk)->exists($filePath)) {
            abort(404, 'Fichier introuvable sur le disque.');
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
        $role = $user->role;

        // All actors can download any document at any workflow phase
        return $this->resolveOriginalFile($document)
            ?? $this->resolveConvertedPdf($document)
            ?? $this->resolveFinalPdf($document, $role, $user)
            ?? $this->getAnyAvailableFile($document);
    }

    private function getAnyAvailableFile(Document $document)
    {
        // Try latest version file
        $latestVersion = $document->versions()->latest()->first();
        if ($latestVersion && $latestVersion->file_path) {
            $disk = str_contains($latestVersion->file_path, 'private') ? 'private' : 'public';
            $pathInfo = pathinfo($latestVersion->file_path);
            $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : 'pdf';
            $filename = ($document->code ?: 'doc') . '_' . str_replace(' ', '_', $document->name) . '.' . $extension;
            if (Storage::disk($disk)->exists($latestVersion->file_path)) {
                return [
                    'file' => $latestVersion->file_path,
                    'name' => $filename,
                    'disk' => $disk
                ];
            }
        }

        // Last resort: try document file_path
        if ($document->file_path) {
            $disk = str_contains($document->file_path, 'private') ? 'private' : 'public';
            $pathInfo = pathinfo($document->file_path);
            $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : 'pdf';
            $filename = ($document->code ?: 'doc') . '_' . str_replace(' ', '_', $document->name) . '.' . $extension;
            if (Storage::disk($disk)->exists($document->file_path)) {
                return [
                    'file' => $document->file_path,
                    'name' => $filename,
                    'disk' => $disk
                ];
            }
        }

        return null;
    }

    private function resolveOriginalFile(Document $document)
    {
        // First try to get original file from DocumentVersion with type 'original'
        $version = $document->versions()->where('type', 'original')->latest()->first();

        if ($version && $version->file_path && Storage::disk('private')->exists($version->file_path)) {
            $pathInfo = pathinfo($version->file_path);
            $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : 'pdf';
            $code = $document->code ?: 'doc';
            $filename = $code . '_' . str_replace(' ', '_', $document->name) . '.' . $extension;
            return [
                'file' => $version->file_path,
                'name' => $filename,
                'disk' => 'private'
            ];
        }

        // Fallback to document file_path
        if (!$document->file_path || !Storage::disk('private')->exists($document->file_path)) {
            return null;
        }

        $pathInfo = pathinfo($document->file_path);
        $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : 'pdf';
        $code = $document->code ?: 'doc';
        $filename = $code . '_' . str_replace(' ', '_', $document->name) . '.' . $extension;

        return [
            'file' => $document->file_path,
            'name' => $filename,
            'disk' => 'private'
        ];
    }

    private function resolveConvertedPdf(Document $document)
    {
        // Get the converted PDF from document_versions
        $version = $document->versions()->where('type', 'pdf_converted')->latest()->first();

        if ($version && $version->file_path && Storage::disk('public')->exists($version->file_path)) {
            $filename = ($document->code ?: 'doc') . '_' . str_replace(' ', '_', $document->name) . '_converti.pdf';
            return [
                'file' => $version->file_path,
                'name' => $filename,
                'disk' => 'public'
            ];
        }

        // If not found in versions, try to find it in storage
        $pdfPath = 'converted_pdfs/' . $document->code . '_' . str_replace(' ', '_', $document->name) . '.pdf';
        if (Storage::disk('public')->exists($pdfPath)) {
            $filename = ($document->code ?: 'doc') . '_' . str_replace(' ', '_', $document->name) . '_converti.pdf';
            return [
                'file' => $pdfPath,
                'name' => $filename,
                'disk' => 'public'
            ];
        }

        return null;
    }

    private function resolveFileForValidator(Document $document)
    {
        $version = $document->versions()->whereIn('type', ['original', 'sent_to_validator'])->latest()->first();

        if ($version && $version->file_path && Storage::disk('private')->exists($version->file_path)) {
            $pathInfo = pathinfo($version->file_path);
            $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : 'pdf';
            $code = $document->code ?: 'doc';
            $filename = $code . '_' . str_replace(' ', '_', $document->name) . '_pour_validation.' . $extension;
            return [
                'file' => $version->file_path,
                'name' => $filename,
                'disk' => 'private'
            ];
        }

        return $this->resolveOriginalFile($document);
    }

    private function resolveFileForApprover(Document $document)
    {
        $version = $document->versions()->whereIn('type', ['pdf_signe_validateur', 'sent_to_approver'])->latest()->first();

        if ($version && $version->file_path && Storage::disk('public')->exists($version->file_path)) {
            $filename = $document->name . '_pour_approbation.pdf';
            return [
                'file' => $version->file_path,
                'name' => $filename,
                'disk' => 'public'
            ];
        }

        return $this->resolveOriginalFile($document);
    }

    private function resolveFileForAdminValidation(Document $document)
    {
        $version = $document->versions()->whereIn('type', ['pdf_signe_approbateur', 'sent_to_admin'])->latest()->first();

        if ($version && $version->file_path && Storage::disk('public')->exists($version->file_path)) {
            $filename = $document->name . '_pour_validation_admin.pdf';
            return [
                'file' => $version->file_path,
                'name' => $filename,
                'disk' => 'public'
            ];
        }

        return $this->resolveOriginalFile($document);
    }

    private function resolveSignedPdf(Document $document, $field, $signerRole)
    {
        $filePath = $document->$field;

        if (!$filePath || !Storage::disk('public')->exists($filePath)) {
            return null;
        }

        $filename = $document->name . '_signe_' . $signerRole . '.pdf';

        return [
            'file' => $filePath,
            'name' => $filename,
            'disk' => 'public'
        ];
    }

    private function resolveFinalPdf(Document $document, $role, $user)
    {
        if ($document->pdf_signe_final && Storage::disk('public')->exists($document->pdf_signe_final)) {
            $filename = $document->name . '_FINAL_signe.pdf';
            return [
                'file' => $document->pdf_signe_final,
                'name' => $filename,
                'disk' => 'public'
            ];
        }

        foreach (['pdf_signe_approbateur', 'pdf_signe_validateur', 'pdf_signe_createur'] as $field) {
            if ($document->$field && Storage::disk('public')->exists($document->$field)) {
                $filename = $document->name . '_signe.pdf';
                return [
                    'file' => $document->$field,
                    'name' => $filename,
                    'disk' => 'public'
                ];
            }
        }

        return null;
    }
}
