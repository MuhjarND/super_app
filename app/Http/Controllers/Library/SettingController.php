<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Library\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return view('library.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'fine_per_day'        => 'required|numeric|min:0',
            'loan_days'           => 'required|integer|min:1|max:90',
            'max_books_per_loan'  => 'required|integer|min:1|max:20',
            'library_name'        => 'required|string|max:255',
            'library_address'     => 'nullable|string',
            'library_phone'       => 'nullable|string|max:20',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
