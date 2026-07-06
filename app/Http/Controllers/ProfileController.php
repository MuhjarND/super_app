<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Services\SignaturePadService;

class ProfileController extends Controller
{
    protected $signaturePadService;

    public function __construct(SignaturePadService $signaturePadService)
    {
        $this->middleware('auth');
        $this->signaturePadService = $signaturePadService;
    }

    public function edit()
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'signature_method' => ['nullable', 'in:draw,upload'],
            'profile_signature_data' => ['nullable', 'string'],
            'profile_signature_file' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'remove_signature' => ['nullable', 'boolean'],
        ], [
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung.',
            'profile_signature_file.image' => 'File tanda tangan harus berupa gambar.',
            'profile_signature_file.mimes' => 'File tanda tangan harus PNG, JPG, atau JPEG.',
        ]);

        $payload = [
            'name' => $validated['name'],
            'username' => strtolower(trim($validated['username'])),
        ];

        if ($request->boolean('remove_photo') && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $payload['profile_photo_path'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $payload['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        if ($request->boolean('remove_signature') && $user->profile_signature_path) {
            Storage::disk('public')->delete($user->profile_signature_path);
            $payload['profile_signature_path'] = null;
            $payload['profile_signature_mime'] = null;
            $payload['profile_signature_size'] = null;
            $payload['profile_signature_method'] = null;
        }

        $signature = null;
        if (($validated['signature_method'] ?? null) === 'draw' && !empty($validated['profile_signature_data'])) {
            $signature = $this->signaturePadService->storeDataUri($validated['profile_signature_data'], 'profile-signatures');
            $signature['method'] = 'draw';
        } elseif (($validated['signature_method'] ?? null) === 'upload' && $request->hasFile('profile_signature_file')) {
            $signature = $this->signaturePadService->storeUploadedFile($request->file('profile_signature_file'), 'profile-signatures');
            $signature['method'] = 'upload';
        }

        if ($signature) {
            if ($user->profile_signature_path) {
                Storage::disk('public')->delete($user->profile_signature_path);
            }

            $payload['profile_signature_path'] = $signature['path'];
            $payload['profile_signature_mime'] = $signature['mime'];
            $payload['profile_signature_size'] = $signature['size'];
            $payload['profile_signature_method'] = $signature['method'];
        }

        $user->update($payload);

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai.',
            ])->withInput();
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('profile.edit')->with('success', 'Password berhasil diperbarui.');
    }
}
