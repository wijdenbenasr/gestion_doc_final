<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function __invoke(Request $request, Document $document)
    {
        $user = Auth::user();
        $filePath = $document->file_path;
        $filename = $document->file_original_name;

        if ($user->role === 'approver' && $document->current_role === 'approver') {
            if ($document->pdf_signe_validateur && Storage::disk('public')->exists($document->pdf_signe_validateur)) {
                $filePath = $document->pdf_signe_validateur;
                $filename = $document->name . '_signe_validateur.pdf';
            }
        } elseif ($user->role === 'admin') {
            if ($document->pdf_signe_final && Storage::disk('public')->exists($document->pdf_signe_final)) {
                $filePath = $document->pdf_signe_final;
                $filename = $document->name . '_signe_final.pdf';
            } elseif ($document->pdf_signe_approbateur && Storage::disk('public')->exists($document->pdf_signe_approbateur)) {
                $filePath = $document->pdf_signe_approbateur;
                $filename = $document->name . '_signe_approbateur.pdf';
            } elseif ($document->pdf_signe_validateur && Storage::disk('public')->exists($document->pdf_signe_validateur)) {
                $filePath = $document->pdf_signe_validateur;
                $filename = $document->name . '_signe_validateur.pdf';
            } elseif ($document->pdf_signe_createur && Storage::disk('public')->exists($document->pdf_signe_createur)) {
                $filePath = $document->pdf_signe_createur;
                $filename = $document->name . '_signe_createur.pdf';
            }
        } elseif ($user->role === 'validator' && $document->current_role === 'validator') {
            if ($document->pdf_signe_createur && Storage::disk('public')->exists($document->pdf_signe_createur)) {
                $filePath = $document->pdf_signe_createur;
                $filename = $document->name . '_signe_createur.pdf';
            }
        } elseif ($user->id === $document->created_by) {
        } else {
            abort(403, 'Telechargement non autorise.');
        }

        $disk = in_array($user->role, ['admin', 'approver', 'validator']) ? 'public' : 'private';

        if (!$filePath || !Storage::disk($disk)->exists($filePath)) {
            abort(404, 'Fichier introuvable.');
        }

        $finalFilename = $filename ?: ($document->name . '.' . pathinfo($filePath, PATHINFO_EXTENSION));

        return Storage::disk($disk)->download($filePath, $finalFilename);
    }
}
