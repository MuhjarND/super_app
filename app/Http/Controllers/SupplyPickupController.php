<?php

namespace App\Http\Controllers;

use App\SupplyPickup;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SupplyPickupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('verify');
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessSupplyModule(), 403);

        if (!Schema::hasTable('supply_pickups')) {
            return response()->view('persediaan.supplies.setup');
        }

        $filters = $this->filters($request);
        $pickups = $this->filteredQuery($filters)->paginate(20)->appends($filters);

        return view('persediaan.supplies.pickups.index', compact('pickups', 'filters'));
    }

    public function reportPdf(Request $request)
    {
        abort_unless(auth()->user()->canAccessSupplyModule(), 403);
        abort_unless(Schema::hasTable('supply_pickups'), 503, 'Modul persediaan belum siap.');

        $filters = $this->filters($request, true);
        $pickups = $this->filteredQuery($filters)->get();

        return PDF::loadView('persediaan.supplies.pickups.pdf', compact('pickups', 'filters'))
            ->setPaper('a4', 'landscape')
            ->download('Laporan-Barang-Keluar-' . $filters['from'] . '-sd-' . $filters['to'] . '.pdf');
    }

    private function filters(Request $request, bool $forReport = false): array
    {
        $dateRules = [$forReport ? 'required' : 'nullable', 'date_format:Y-m-d'];
        $endRules = $dateRules;
        if ($request->filled('from')) {
            $endRules[] = 'after_or_equal:from';
        }

        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'from' => $dateRules,
            'to' => $endRules,
        ], [
            'from.required' => 'Isi tanggal awal untuk mencetak laporan.',
            'to.required' => 'Isi tanggal akhir untuk mencetak laporan.',
            'from.date_format' => 'Tanggal awal tidak valid.',
            'to.date_format' => 'Tanggal akhir tidak valid.',
            'to.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
        ]);
    }

    private function filteredQuery(array $filters)
    {
        $user = auth()->user();
        $query = SupplyPickup::with(['user', 'request'])->latest('pickup_date')->latest('id');

        if (!$user->canManageSupplyModule()) {
            $query->where('user_id', $user->id);
        }
        if (isset($filters['from'])) {
            $query->where('pickup_date', '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->where('pickup_date', '<', Carbon::parse($filters['to'])->addDay()->toDateString());
        }
        if (($filters['search'] ?? '') !== '') {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($query) use ($term) {
                $query->where('item_name_snapshot', 'like', $term)
                    ->orWhereHas('item', function ($item) use ($term) {
                        $item->where('code', 'like', $term);
                    })
                    ->orWhereHas('user', function ($user) use ($term) {
                        $user->where('name', 'like', $term);
                    })
                    ->orWhereHas('request', function ($request) use ($term) {
                        $request->where('request_number', 'like', $term);
                    });
            });
        }

        return $query;
    }

    public function verify(SupplyPickup $pickup)
    {
        $pickup->loadMissing(['user.jabatan', 'item', 'request', 'creator']);

        return view('persediaan.supplies.pickups.verify', compact('pickup'));
    }
}
