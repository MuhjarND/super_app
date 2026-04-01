<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Schema;

trait InteractsWithProgressZi
{
    protected function progressZiModuleReady()
    {
        return Schema::hasTable('zi_periods') && Schema::hasTable('zi_areas') && Schema::hasTable('zi_activities') && Schema::hasTable('zi_indicators') && Schema::hasTable('zi_activity_realizations') && Schema::hasTable('zi_evidences') && Schema::hasTable('zi_reviews');
    }

    protected function progressZiSetupResponse($title = 'Modul Progress ZI Belum Diaktifkan')
    {
        return response()->view('progress-zi.setup', ['title' => $title, 'message' => 'Schema modul Progress ZI belum dijalankan pada database.']);
    }

    protected function abortUnlessCanAccessProgressZi() { abort_unless(auth()->user()->canAccessProgressZiModule(), 403); }
    protected function abortUnlessCanManageProgressZiMaster() { abort_unless(auth()->user()->canManageProgressZiMasterData(), 403); }
    protected function abortUnlessCanVerifyProgressZi() { abort_unless(auth()->user()->canVerifyProgressZi(), 403); }
}
