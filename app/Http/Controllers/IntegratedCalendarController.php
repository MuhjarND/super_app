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
                $title = $titles->get($event['id']);
                $event['extendedProps']['public_title'] = $title;
                $event['extendedProps']['bulk_publication_title'] = $title === null ? CalendarPublications::bulkTitle($event) : null;
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

    public function publishBulk(Request $request, CalendarPublications $publications)
    {
        $user = $request->user();
        abort_unless($user->canLogin() && $user->isSuperAdmin() && $user->canAccessIntegratedCalendar(), 403);

        $data = $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['required', 'date_format:Y-m-d', 'after:start'],
            'events' => ['required', 'array', 'max:' . CalendarPublications::BULK_LIMIT],
            'events.*.id' => ['required', 'string', 'max:64', 'distinct', 'regex:' . CalendarPublications::BULK_EVENT_KEY],
            'events.*.title' => ['required', 'string', 'max:255'],
        ]);

        $start = CarbonImmutable::parse($data['start'], 'Asia/Jayapura');
        $end = CarbonImmutable::parse($data['end'], 'Asia/Jayapura');
        abort_if($start->diffInDays($end) > 42, 422, 'Pilih satu rentang kalender paling lama 42 hari.');

        $reviewed = array_column($data['events'], 'title', 'id');
        $schedule = $this->calendarService->build($user, [
            'start' => $start->toDateString(),
            'end' => $end->subDay()->toDateString(),
        ], array_keys($reviewed));
        $current = array_column($schedule['events'], null, 'id');
        $titles = [];

        foreach ($reviewed as $key => $title) {
            $event = $current[$key] ?? null;
            $publicTitle = $event ? CalendarPublications::bulkTitle($event) : null;
            abort_if($publicTitle !== $title || CarbonImmutable::parse($event['start']) >= $end
                || CarbonImmutable::parse($event['end']) <= $start, 409, 'Jadwal atau judul berubah. Muat ulang kalender dan tinjau kembali.');
            $titles[$key] = $publicTitle;
        }

        $count = $publications->publishNew($user, $titles);
        return response()->json(['published' => $count, 'skipped' => count($titles) - $count]);
    }
}
