<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __invoke(Request $request, Document $document)
    {
        $this->authorize('download', $document);

        if (! $document->file_path || ! Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'Fichier introuvable.');
        }

        $filename = $document->file_original_name
            ?: ($document->name.'.'.pathinfo($document->file_path, PATHINFO_EXTENSION));

        return Storage::disk('private')->download($document->file_path, $filename);
    }
}
