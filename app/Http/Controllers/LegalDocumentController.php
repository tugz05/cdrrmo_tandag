<?php

namespace App\Http\Controllers;

use App\Http\Requests\LegalDocumentStoreRequest;
use App\Interfaces\LegalDocumentInterface;

class LegalDocumentController extends Controller
{
    public function __construct(private LegalDocumentInterface $legalDocument)
    {}

    public function index(string $type)
    {
        return inertia("", $this->legalDocument->index($type));
    }

    public function store(LegalDocumentStoreRequest $request)
    {
        $this->legalDocument->store($request->validated());
    }
}
