<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Console\Command;

class VerifyDocumentIntegrity extends Command
{
    protected $signature = 'document:verify-integrity {id?}';

    protected $description = 'Vérifier l\'intégrité des fichiers documents par SHA256';

    public function handle(DocumentService $documentService): int
    {
        $id = $this->argument('id');

        $documents = $id
            ? Document::where('id', $id)->get()
            : Document::all();

        if ($documents->isEmpty()) {
            $this->error('Aucun document trouvé.');

            return 1;
        }

        $this->info('Vérification de '.$documents->count().' document(s)...');

        $errors = 0;
        foreach ($documents as $document) {
            $isValid = $documentService->verifyIntegrity($document);

            if ($isValid) {
                $this->line("<info>[OK]</info> Document #{$document->id}: {$document->name}");
            } else {
                $this->error("[FAIL] Document #{$document->id}: {$document->name} - HASH MISMATCH OU FICHIER MANQUANT");
                $errors++;
            }
        }

        if ($errors > 0) {
            $this->warn("Terminé avec $errors erreur(s) d'intégrité.");

            return 1;
        }

        $this->info('Tous les documents sont intègres.');

        return 0;
    }
}
