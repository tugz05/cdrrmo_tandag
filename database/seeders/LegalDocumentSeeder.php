<?php

namespace Database\Seeders;

use App\Enums\LegalDocumentEnum;
use App\Models\LegalDocument;
use Illuminate\Database\Seeder;

class LegalDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach(LegalDocumentEnum::getValues() as $type) {
            LegalDocument::firstOrCreate([
                'type' => $type
            ], [
                'content' => null,
                'title' => null,
                'type' => $type
            ]);
        }
    }
}
