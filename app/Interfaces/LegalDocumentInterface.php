<?php

namespace App\Interfaces;

interface LegalDocumentInterface
{
    public function index(string $type);
    public function store($validatedData);
}
