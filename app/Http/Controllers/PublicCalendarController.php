<?php

namespace App\Http\Controllers;

use App\AgendaPimpinan;
use App\Services\CalendarPublications;
use App\Services\IntegratedCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class PublicCalendarController extends Controller
{
    public function index(Request $request, CalendarPublications $publications, IntegratedCalendarService $calendar)
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = CarbonImmutable::parse(
            ($data['month'] ?? now('Asia/Jayapura')->format('Y-m')) . '-01',
            'Asia/Jayapura'
        );

        $events = AgendaPimpinan::query()
            ->whereNotNull('calendar_public_title')
            ->where('calendar_public_title', '!=', '')
            ->whereBetween('tanggal_kegiatan', [$month->toDateString(), $month->endOfMonth()->toDateString()])
            ->orderBy('tanggal_kegiatan')
            ->orderBy('waktu')
            ->orderBy('id')
            ->get(['calendar_public_title', 'tanggal_kegiatan', 'waktu'])
            ->map(function (AgendaPimpinan $agenda) {
                return [
                    'title' => $agenda->calendar_public_title,
                    'date' => $agenda->tanggal_kegiatan->format('Y-m-d'),
                    'time' => $agenda->waktu_formatted,
                ];
            });

        $events = $events->concat($publications->events($calendar, $month))->sortBy(function ($event) {
            return $event['date'] . ' ' . ($event['time'] ?? '');
        })->values();

        return response()->json(['events' => $events])->header('Cache-Control', 'no-store');
    }
}
