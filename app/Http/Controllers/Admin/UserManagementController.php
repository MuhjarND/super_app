<?php

namespace App\Http\Controllers\Admin;

use App\Bidang;
use App\Http\Controllers\Controller;
use App\Jabatan;
use App\Role;
use App\Services\WhatsAppNotificationService;
use App\Unit;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppNotificationService $whatsAppService)
    {
        $this->middleware(['auth', 'role:super_admin']);
        $this->whatsAppService = $whatsAppService;
    }

    public function index(Request $request)
    {
        $query = User::with(['roles', 'jabatan', 'unit', 'bidang', 'atasanLangsung', 'pejabatBerwenang']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->whereHas('roles', function ($builder) use ($request) {
                $builder->where('roles.id', $request->role_id);
            });
        }

        if ($request->filled('jabatan_id')) {
            $query->where('jabatan_id', $request->jabatan_id);
        }

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        if ($request->filled('bidang_id')) {
            $query->where('bidang_id', $request->bidang_id);
        }

        $users = $query->ordered()->paginate(15)->appends($request->query());
        $roles = Role::orderBy('display_name')->get();
        $jabatans = Jabatan::with('unit')->orderBy('nama')->get();
        $units = Unit::orderBy('nama')->get();
        $bidangs = Bidang::orderBy('nama')->get();
        $supervisorOptions = User::with('jabatan')->active()->ordered()->get(['id', 'name', 'nip', 'jabatan_id', 'hirarki']);
        $filters = $request->only(['search', 'role_id', 'jabatan_id', 'unit_id', 'bidang_id']);

        return view('admin.users.index', compact('users', 'roles', 'jabatans', 'units', 'bidangs', 'supervisorOptions', 'filters'));
    }

    public function create()
    {
        return redirect()->route('admin.users.index');
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'jabatan_id' => $data['jabatan_id'] ?? null,
                'jabatan_keterangan' => $data['jabatan_keterangan'] ?? null,
                'unit_id' => $data['unit_id'] ?? null,
                'bidang_id' => $data['bidang_id'] ?? null,
                'hirarki' => $data['hirarki'] ?? 999,
                'nip' => $data['nip'] ?? null,
                'no_hp' => $data['no_hp'] ?? null,
                'atasan_langsung_id' => $data['atasan_langsung_id'] ?? null,
                'pejabat_berwenang_id' => $data['pejabat_berwenang_id'] ?? null,
            ]);

            $user->roles()->sync($data['role_ids']);
        });

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return redirect()->route('admin.users.index');
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateRequest($request, $user->id);

        DB::transaction(function () use ($data, $user) {
            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'jabatan_id' => $data['jabatan_id'] ?? null,
                'jabatan_keterangan' => $data['jabatan_keterangan'] ?? null,
                'unit_id' => $data['unit_id'] ?? null,
                'bidang_id' => $data['bidang_id'] ?? null,
                'hirarki' => $data['hirarki'] ?? 999,
                'nip' => $data['nip'] ?? null,
                'no_hp' => $data['no_hp'] ?? null,
                'atasan_langsung_id' => $data['atasan_langsung_id'] ?? null,
                'pejabat_berwenang_id' => $data['pejabat_berwenang_id'] ?? null,
            ];

            if (!empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            $user->update($payload);
            $user->roles()->sync($data['role_ids']);
        });

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ((int) auth()->id() === (int) $user->id) {
            return redirect()->route('admin.users.index')->with('error', 'Akun yang sedang dipakai tidak bisa dihapus.');
        }

        if (
            $user->suratMasukCreated()->exists() ||
            $user->suratKeluarCreated()->exists() ||
            $user->disposisiDikirim()->exists() ||
            $user->disposisiDiterima()->exists()
        ) {
            return redirect()->route('admin.users.index')->with('error', 'User sudah terhubung dengan data persuratan dan tidak bisa dihapus.');
        }

        $user->roles()->detach();
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }

    public function sendLoginInfo(User $user)
    {
        $result = $this->whatsAppService->notifyLoginInfo($user);
        $processed = $result || !$this->whatsAppService->isConfigured();

        return redirect()->route('admin.users.index')->with(
            $processed ? 'success' : 'error',
            $processed
                ? 'Informasi login berhasil diproses ke WhatsApp user.'
                : 'Informasi login tidak dapat dikirim. Pastikan nomor WhatsApp user sudah tersedia.'
        );
    }

    public function toggleStatus(User $user)
    {
        if ((int) auth()->id() === (int) $user->id) {
            return redirect()->route('admin.users.index')->with('error', 'Akun yang sedang dipakai tidak bisa dinonaktifkan.');
        }

        $user->update([
            'status_aktif_pegawai' => ! $user->status_aktif_pegawai,
        ]);

        return redirect()->route('admin.users.index')->with(
            'success',
            $user->status_aktif_pegawai ? 'User berhasil diaktifkan.' : 'User berhasil dinonaktifkan.'
        );
    }

    protected function validateRequest(Request $request, $userId = null)
    {
        $passwordRules = $userId
            ? ['nullable', 'string', 'min:8']
            : ['required', 'string', 'min:8'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => $passwordRules,
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['exists:roles,id'],
            'jabatan_id' => ['nullable', 'exists:jabatans,id'],
            'jabatan_keterangan' => ['nullable', 'string', 'max:255'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'bidang_id' => ['nullable', 'exists:bidangs,id'],
            'hirarki' => ['nullable', 'integer', 'min:1'],
            'nip' => ['nullable', 'string', 'max:50'],
            'no_hp' => ['nullable', 'string', 'max:50'],
            'atasan_langsung_id' => array_filter(['nullable', Rule::exists('users', 'id')->where('status_aktif_pegawai', true), $userId ? Rule::notIn([$userId]) : null]),
            'pejabat_berwenang_id' => array_filter(['nullable', Rule::exists('users', 'id')->where('status_aktif_pegawai', true), $userId ? Rule::notIn([$userId]) : null]),
        ]);
    }

    protected function getFormData(array $data)
    {
        $data['roles'] = Role::orderBy('display_name')->get();
        $data['jabatans'] = Jabatan::with('unit')->orderBy('nama')->get();
        $data['units'] = Unit::orderBy('nama')->get();
        $data['bidangs'] = Bidang::orderBy('nama')->get();
        $data['supervisorOptions'] = User::with('jabatan')->active()->ordered()->get(['id', 'name', 'nip', 'jabatan_id', 'hirarki']);

        return $data;
    }
}
