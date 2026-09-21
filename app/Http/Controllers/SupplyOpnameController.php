<?php

namespace App\Http\Controllers;

use App\KlasifikasiKode;
use App\Services\SupplyOpnameDocumentService;
use App\SupplyItem;
use App\SupplyOpnameReport;
use App\SuratKeluar;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplyOpnameController extends Controller
{
    const COMMITTEE_ROLES = ['Ketua', 'Sekretaris', 'Anggota', 'Anggota'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->authorizePrint();
        abort_unless(Schema::hasTable('supply_items'), 503, 'Modul persediaan belum siap.');

        $period = $this->period($request->input('period'));
        $items = SupplyItem::active()->orderBy('account_code')->orderBy('name')->get();
        $existing = Schema::hasTable('supply_opname_reports')
            ? SupplyOpnameReport::with('suratKeluar')
                ->whereDate('report_period', $period->toDateString())
                ->first()
            : null;
        $snapshot = $existing ? (array) $existing->snapshot : [];
        $physical = collect($snapshot['items'] ?? [])->keyBy('id');
        $committeeUsers = $this->committeeUsers();
        $committeeDefaults = $this->defaultCommitteeIds($committeeUsers, $snapshot);
        $signatoryDefault = $this->defaultSignatoryId($committeeUsers, $snapshot);

        return view('persediaan.supplies.opname.index', [
            'items' => $items,
            'period' => $period,
            'opnameDate' => $existing && $existing->opname_date ? $existing->opname_date : Carbon::now('Asia/Jayapura'),
            'existing' => $existing,
            'physical' => $physical,
            'committeeUsers' => $committeeUsers,
            'committeeDefaults' => $committeeDefaults,
            'signatoryDefault' => $signatoryDefault,
        ]);
    }

    public function download(Request $request, SupplyOpnameDocumentService $documents)
    {
        $this->authorizePrint();
        abort_unless(
            Schema::hasTable('supply_items')
                && Schema::hasTable('supply_opname_reports')
                && Schema::hasColumn('supply_opname_reports', 'surat_keluar_id'),
            503,
            'Modul opname belum siap. Jalankan migrasi database terlebih dahulu.'
        );

        $validated = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'opname_date' => ['required', 'date'],
            'committee_user_ids' => ['required', 'array', 'size:4'],
            'committee_user_ids.*' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('status_aktif_pegawai', true);
                }),
            ],
            'signatory_user_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('status_aktif_pegawai', true);
                }),
            ],
            'physical' => ['nullable', 'array'],
            'physical.*' => ['nullable', 'integer', 'min:0'],
            'condition' => ['nullable', 'array'],
            'condition.*' => ['nullable', 'string', 'max:50'],
        ]);

        $committeeIds = array_values(array_unique(array_map('intval', $validated['committee_user_ids'])));
        if (count($committeeIds) !== 4) {
            throw ValidationException::withMessages([
                'committee_user_ids' => 'Pilih empat orang berbeda untuk panitia opname fisik.',
            ]);
        }

        $period = Carbon::createFromFormat('Y-m', $validated['period'], 'Asia/Jayapura')->startOfMonth();
        $opnameDate = Carbon::parse($validated['opname_date'], 'Asia/Jayapura')->startOfDay();
        $items = SupplyItem::active()->orderBy('account_code')->orderBy('name')->get();
        $people = $this->resolvePeople($committeeIds, (int) $validated['signatory_user_id']);
        $snapshot = $this->buildSnapshot($items, $validated, $people);

        $created = SuratKeluar::withNomorUrutLock($period->year, function () use (
            $period,
            $opnameDate,
            $snapshot
        ) {
            $report = SupplyOpnameReport::whereDate('report_period', $period->toDateString())
                ->lockForUpdate()
                ->first();
            $suratKeluar = $report && $report->surat_keluar_id
                ? SuratKeluar::whereKey($report->surat_keluar_id)->lockForUpdate()->first()
                : null;

            $classification = KlasifikasiKode::where('tipe', 'klasifikasi')->where('kode', 'PL')->first();
            $function = KlasifikasiKode::where('tipe', 'fungsi')->where('kode', 'PL1')->first();
            $activity = KlasifikasiKode::where('tipe', 'kegiatan')->where('kode', 'PL1.1')->first();
            $transaction = KlasifikasiKode::where('tipe', 'transaksi')->where('kode', 'PL1.1.1')->first();

            if (!$classification || !$function || !$activity || !$transaction) {
                throw new \RuntimeException('Kode klasifikasi PL1.1.1 untuk surat opname belum tersedia.');
            }

            $perihal = 'Berita Acara Opname Fisik Persediaan Bulan '
                . $this->monthName($period)
                . ' ' . $period->year;

            if (!$suratKeluar) {
                $result = SuratKeluar::generateNomorSurat(
                    'sekretaris',
                    $classification->kode,
                    $function->kode,
                    $activity->kode,
                    $transaction->kode,
                    $period->year,
                    $period->month
                );

                $suratKeluar = SuratKeluar::create([
                    'nomor_surat' => $result['nomor'],
                    'nomor_urut' => $result['urut'],
                    'tahun_surat' => $period->year,
                    'klasifikasi_kode_id' => $classification->id,
                    'kode_fungsi_id' => $function->id,
                    'kode_kegiatan_id' => $activity->id,
                    'kode_transaksi_id' => $transaction->id,
                    'nomenklatur_jabatan' => 'sekretaris',
                    'opsi_penerima' => 'internal',
                    'penerima_external' => null,
                    'perihal' => $perihal,
                    'tanggal_surat' => $opnameDate->toDateString(),
                    'has_lampiran' => true,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);
            } else {
                $suratKeluar->update([
                    'klasifikasi_kode_id' => $classification->id,
                    'kode_fungsi_id' => $function->id,
                    'kode_kegiatan_id' => $activity->id,
                    'kode_transaksi_id' => $transaction->id,
                    'nomenklatur_jabatan' => 'sekretaris',
                    'opsi_penerima' => 'internal',
                    'penerima_external' => null,
                    'perihal' => $perihal,
                    'tanggal_surat' => $opnameDate->toDateString(),
                    'has_lampiran' => true,
                    'status' => 'draft',
                ]);
            }

            $report = $report ?: new SupplyOpnameReport([
                'report_period' => $period->toDateString(),
                'created_by' => auth()->id(),
            ]);
            $report->surat_keluar_id = $suratKeluar->id;
            $report->opname_date = $opnameDate->toDateString();
            $report->nomor_surat = $suratKeluar->nomor_surat;
            $report->nomor_urut = $suratKeluar->nomor_urut;
            $report->tahun_surat = $suratKeluar->tahun_surat;
            $report->snapshot = $snapshot;
            $report->updated_by = auth()->id();
            $report->save();

            return [
                'report' => $report->fresh(['suratKeluar']),
                'suratKeluar' => $suratKeluar->fresh(),
            ];
        });

        $temporaryPath = null;
        try {
            $temporaryPath = $documents->make($created['report']);
            $storagePath = 'surat-keluar/opname/'
                . Str::slug($created['suratKeluar']->nomor_surat)
                . '-' . $period->format('Y-m') . '.docx';

            if (!Storage::disk('public')->put($storagePath, file_get_contents($temporaryPath))) {
                throw new \RuntimeException('Dokumen opname tidak dapat disimpan ke storage.');
            }

            $created['suratKeluar']->update([
                'file_path' => $storagePath,
                'status' => 'lengkap',
            ]);
        } finally {
            if ($temporaryPath && is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }

        return response()->download(
            Storage::disk('public')->path($storagePath),
            'BA-Opname-Fisik-Persediaan-' . $period->format('Y-m') . '.docx'
        );
    }

    protected function buildSnapshot($items, array $validated, array $people)
    {
        $rows = [];
        foreach ($items as $item) {
            $saldo = (int) $item->stock;
            $fisikInput = isset($validated['physical'][$item->id]) && $validated['physical'][$item->id] !== ''
                ? (int) $validated['physical'][$item->id]
                : $saldo;
            $rows[] = [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code ?: '-',
                'account_code' => $item->account_code ?: '117111',
                'unit' => $item->unit ?: 'Pcs',
                'saldo' => $saldo,
                'fisik' => $fisikInput,
                'selisih' => $fisikInput - $saldo,
                'kondisi' => trim((string) (($validated['condition'] ?? [])[$item->id] ?? 'Baik')) ?: 'Baik',
                'nilai' => $fisikInput * (float) ($item->unit_price ?: 0),
            ];
        }

        $groups = collect($rows)->groupBy('account_code')->map(function ($group, $accountCode) {
            $labels = [
                '117111' => 'Barang Konsumsi',
                '117113' => 'Barang untuk Pemeliharaan',
            ];

            return [
                'account_code' => $accountCode,
                'label' => $labels[$accountCode] ?? 'Barang Persediaan',
                'items' => array_values($group->all()),
                'total' => $group->sum('nilai'),
            ];
        })->values()->all();

        return [
            'items' => $rows,
            'groups' => $groups,
            'grand_total' => collect($rows)->sum('nilai'),
            'committee' => $people['committee'],
            'signatory' => $people['signatory'],
        ];
    }

    protected function resolvePeople(array $committeeIds, $signatoryId)
    {
        $users = User::active()
            ->with(['jabatan', 'unit', 'roles'])
            ->whereIn('id', array_values(array_unique(array_merge($committeeIds, [(int) $signatoryId]))))
            ->get()
            ->keyBy('id');

        $committee = [];
        foreach ($committeeIds as $index => $id) {
            $user = $users->get($id);
            if (!$user) {
                throw ValidationException::withMessages([
                    'committee_user_ids' => 'Data panitia opname tidak lagi aktif. Muat ulang halaman dan pilih kembali.',
                ]);
            }
            $committee[] = $this->personSnapshot($user, self::COMMITTEE_ROLES[$index]);
        }

        $signatory = $users->get((int) $signatoryId);
        if (!$signatory) {
            throw ValidationException::withMessages([
                'signatory_user_id' => 'Pejabat penandatangan tidak lagi aktif. Muat ulang halaman dan pilih kembali.',
            ]);
        }

        return [
            'committee' => $committee,
            'signatory' => $this->personSnapshot($signatory, $this->signatoryTitle($signatory)),
        ];
    }

    protected function personSnapshot(User $user, $role)
    {
        return [
            'id' => (int) $user->id,
            'name' => trim((string) $user->name) ?: '-',
            'nip' => trim((string) $user->nip) ?: '-',
            'role' => $role,
            'title' => trim((string) ($user->jabatan_keterangan ?: optional($user->jabatan)->nama)) ?: 'Pegawai',
        ];
    }

    protected function committeeUsers()
    {
        return User::active()
            ->with(['jabatan', 'unit', 'roles'])
            ->ordered()
            ->get();
    }

    protected function defaultCommitteeIds($users, array $snapshot)
    {
        $saved = collect($snapshot['committee'] ?? [])
            ->pluck('id')
            ->filter()
            ->map('intval')
            ->unique()
            ->values()
            ->all();
        if (count($saved) === 4 && $users->whereIn('id', $saved)->count() === 4) {
            return $saved;
        }

        $selected = [];
        $pick = function ($predicate) use ($users, &$selected) {
            $user = $users->first(function ($candidate) use ($predicate, $selected) {
                return !in_array((int) $candidate->id, $selected, true) && $predicate($candidate);
            });
            if ($user) {
                $selected[] = (int) $user->id;
            }
        };

        $pick(function ($user) {
            return $this->userPositionCode($user) === 'KASUBAG_TURT';
        });
        $pick(function ($user) {
            return $this->userPositionCode($user) === 'SEK';
        });
        $pick(function ($user) {
            return $user->roles && $user->roles->contains('name', 'operator_persediaan');
        });
        $pick(function ($user) {
            return stripos($this->userTitle($user), 'TURT') !== false;
        });
        foreach ($users as $user) {
            if (count($selected) >= 4) {
                break;
            }
            if (!in_array((int) $user->id, $selected, true)) {
                $selected[] = (int) $user->id;
            }
        }

        return $selected;
    }

    protected function defaultSignatoryId($users, array $snapshot)
    {
        $savedSnapshot = isset($snapshot['signatory']) && is_array($snapshot['signatory'])
            ? $snapshot['signatory']
            : [];
        $saved = (int) ($savedSnapshot['id'] ?? 0);
        if ($saved && $users->contains('id', $saved)) {
            return $saved;
        }

        $secretary = $users->first(function ($user) {
            return $this->userPositionCode($user) === 'SEK';
        });

        return $secretary ? (int) $secretary->id : (int) optional($users->first())->id;
    }

    protected function userPositionCode(User $user)
    {
        return strtoupper((string) optional($user->jabatan)->kode);
    }

    protected function userTitle(User $user)
    {
        return (string) ($user->jabatan_keterangan ?: optional($user->jabatan)->nama);
    }

    protected function signatoryTitle(User $user)
    {
        $code = $this->userPositionCode($user);
        if ($code === 'KPTA') {
            return 'Ketua / Kuasa Pengguna Barang';
        }
        if ($code === 'SEK') {
            return 'Sekretaris / Kuasa Pengguna Barang';
        }

        return $this->userTitle($user) ?: 'Pejabat Penandatangan';
    }

    protected function authorizePrint()
    {
        abort_unless(auth()->user()->canPrintSupplyOpname(), 403);
    }

    protected function period($value)
    {
        try {
            return Carbon::createFromFormat('Y-m', $value ?: now('Asia/Jayapura')->format('Y-m'), 'Asia/Jayapura')->startOfMonth();
        } catch (\Throwable $e) {
            return now('Asia/Jayapura')->startOfMonth();
        }
    }

    protected function monthName($date)
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $months[(int) $date->format('n')];
    }
}
