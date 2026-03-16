<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jabatan;
use App\Role;
use App\Unit;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:super_admin']);
    }

    public function index(Request $request)
    {
        $query = User::with(['roles', 'jabatan', 'unit']);

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

        $users = $query->orderBy('name')->paginate(15)->appends($request->query());
        $roles = Role::orderBy('display_name')->get();
        $jabatans = Jabatan::with('unit')->orderBy('nama')->get();
        $units = Unit::orderBy('nama')->get();
        $filters = $request->only(['search', 'role_id', 'jabatan_id', 'unit_id']);

        return view('admin.users.index', compact('users', 'roles', 'jabatans', 'units', 'filters'));
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
                'unit_id' => $data['unit_id'] ?? null,
                'nip' => $data['nip'] ?? null,
                'no_hp' => $data['no_hp'] ?? null,
            ]);

            $user->roles()->sync([$data['role_id']]);
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
                'unit_id' => $data['unit_id'] ?? null,
                'nip' => $data['nip'] ?? null,
                'no_hp' => $data['no_hp'] ?? null,
            ];

            if (!empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            $user->update($payload);
            $user->roles()->sync([$data['role_id']]);
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

    protected function validateRequest(Request $request, $userId = null)
    {
        $passwordRules = $userId
            ? ['nullable', 'string', 'min:8']
            : ['required', 'string', 'min:8'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => $passwordRules,
            'role_id' => ['required', 'exists:roles,id'],
            'jabatan_id' => ['nullable', 'exists:jabatans,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'nip' => ['nullable', 'string', 'max:50'],
            'no_hp' => ['nullable', 'string', 'max:50'],
        ]);
    }

    protected function getFormData(array $data)
    {
        $data['roles'] = Role::orderBy('display_name')->get();
        $data['jabatans'] = Jabatan::with('unit')->orderBy('nama')->get();
        $data['units'] = Unit::orderBy('nama')->get();

        return $data;
    }
}
