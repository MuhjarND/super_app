<?php

namespace App\Http\Controllers;

use App\Services\IntegratedCalendarService;
use App\Unit;
use Illuminate\Http\Request;

class IntegratedCalendarController extends Controller
{
    protected $calendarService;

    public function __construct(IntegratedCalendarService $calendarService)
    {
        $this->middleware('auth');
        $this->calendarService = $calendarService;
    }

    public function index()
    {
        abort_unless(auth()->user()->canAccessIntegratedCalendar(), 403);

        return view('calendar.integrated.index', [
            'units' => Unit::orderBy('nama')->get(),
            'calendarFilters' => [
                'scope' => request('scope', 'all'),
                'unit_id' => request('unit_id'),
                'status' => request('status'),
                'modules' => request()->has('modules') ? (array) request('modules', []) : ['rapat', 'cuti', 'zi'],
            ],
        ]);
    }

    public function events(Request $request)
    {
        abort_unless($request->user()->canAccessIntegratedCalendar(), 403);

        return response()->json(
            $this->calendarService->build($request->user(), $request->all())
        );
    }
}
