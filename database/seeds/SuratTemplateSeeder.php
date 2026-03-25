<?php

use Illuminate\Database\Seeder;
use App\SuratTemplate;
use App\Support\SuratTemplateCatalog;

class SuratTemplateSeeder extends Seeder
{
    public function run()
    {
        foreach (SuratTemplateCatalog::all() as $item) {
            SuratTemplate::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'description' => $item['description'],
                    'status' => $item['status'] ?? 'active',
                    'field_schema' => $item['field_schema'] ?? [],
                    'template_body' => $item['template_body'] ?? '',
                    'source_type' => $item['source_type'] ?? 'system',
                    'approved_at' => now(),
                ]
            );
        }
    }
}
