<?php

namespace App\Services;

use App\Interfaces\LegalDocumentInterface;
use App\Models\LegalDocument;

class LegalDocumentService implements LegalDocumentInterface
{
    public function index(string $type)
    {
        return [
            'document' => LegalDocument::whereType($type)->first(),
        ];
    }

    public function store($validatedData)
    {
        LegalDocument::updateOrCreate(
            ['id' => $validatedData['id']],
            $validatedData
        );
    }
}
