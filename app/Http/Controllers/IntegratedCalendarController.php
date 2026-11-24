<?php

namespace App\Http\Controllers;

use App\Services\CalendarPublications;
use App\Services\IntegratedCalendarService;
use App\Unit;
use Carbon\CarbonImmutable;
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
                'modules' => request()->has('modules') ? (array) request('modules', []) : ['rapat', 'agenda_pimpinan', 'virtual_meeting', 'cuti', 'zi', 'surat_tugas'],
            ],
        ]);
    }

    public function events(Request $request, CalendarPublications $publications)
    {
        abort_unless($request->user()->canAccessIntegratedCalendar(), 403);

        $payload = $this->calendarService->build($request->user(), $request->all());
        if ($request->user()->isSuperAdmin()) {
            $titles = $publications->titles(array_column($payload['events'], 'id'));
            foreach ($payload['events'] as &$event) {
                $event['extendedProps']['public_title'] = $titles->get($event['id']);
            }
            unset($event);
        }

        return response()->json($payload)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function publish(Request $request, CalendarPublications $publications)
    {
        $user = $request->user();
        abort_unless($user->canLogin() && $user->isSuperAdmin() && $user->canAccessIntegratedCalendar(), 403);

        $data = $request->validate([
            'event_key' => ['required', 'string', 'max:64', 'regex:/^[a-z-]+-[1-9][0-9]*$/D'],
            'start' => ['required', 'date'],
            'public_title' => ['present', 'nullable', 'string', 'max:255'],
        ]);
        $month = CarbonImmutable::parse($data['start'])->setTimezone('Asia/Jayapura');
        $schedule = $this->calendarService->build($user, [
            'start' => $month->startOfMonth()->toDateString(),
            'end' => $month->endOfMonth()->toDateString(),
        ], [$data['event_key']]);
        abort_unless(in_array($data['event_key'], array_column($schedule['events'], 'id'), true), 404);

        $publications->save($user, $data['event_key'], $data['public_title']);

        return response()->json(['public_title' => $data['public_title']]);
    }
}
