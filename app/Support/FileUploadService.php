<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Politique unique de stockage/nommage pour tout upload de fichier
 * utilisateur (photo élève, justificatif de dépense, facture
 * fournisseur...) — annoncée dans la doc d'architecture, construite ici
 * à l'occasion de son premier usage réel (photo + certificat médical de
 * la fiche élève).
 */
class FileUploadService
{
    public function store(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, 'public');
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}