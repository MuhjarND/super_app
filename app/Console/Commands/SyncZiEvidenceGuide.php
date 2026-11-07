<?php

namespace App\Console\Commands;

use App\Services\ProgressZi\ZiEvidenceGuideService;
use Illuminate\Console\Command;

class SyncZiEvidenceGuide extends Command
{
    protected $signature = 'progress-zi:sync-evidence-guide';
    protected $description = 'Sinkronkan acuan tetap penyusunan eviden dari panduan Excel yang disertakan aplikasi.';

    public function handle(ZiEvidenceGuideService $service)
    {
        $counts = $service->sync();
        $this->info('Panduan tersinkron: ' . $counts['areas'] . ' area, ' . $counts['points'] . ' poin, ' . $counts['evidence_points'] . ' poin eviden.');
        return 0;
    }
}
