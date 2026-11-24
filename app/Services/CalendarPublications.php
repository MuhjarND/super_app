<?php

namespace App\Services;

use App\AgendaPimpinan;
use App\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalendarPublications
{
    private const ZONE = 'Asia/Jayapura';

    public function titles(array $keys): Collection
    {
        $agendaIds = [];
        foreach ($keys as $key) {
            if (preg_match('/^agenda-([1-9][0-9]*)$/D', $key, $match)) {
                $agendaIds[] = (int) $match[1];
            }
        }

        $titles = DB::table('calendar_publications')->whereIn('event_key', $keys)->pluck('public_title', 'event_key');
        foreach (AgendaPimpinan::whereIn('id', $agendaIds)->whereNotNull('calendar_public_title')->pluck('calendar_public_title', 'id') as $id => $title) {
            $titles->put('agenda-' . $id, $title);
        }

        return $titles;
    }

    public function save(User $publisher, string $key, ?string $title): void
    {
        if (preg_match('/^agenda-([1-9][0-9]*)$/D', $key, $match)) {
            AgendaPimpinan::whereKey((int) $match[1])->update(['calendar_public_title' => $title]);
        } elseif ($title === null) {
            DB::table('calendar_publications')->where('event_key', $key)->delete();
        } else {
            DB::table('calendar_publications')->updateOrInsert(
                ['event_key' => $key],
                ['public_title' => $title, 'published_by' => $publisher->id]
            );
        }
    }

    public function events(IntegratedCalendarService $calendar, CarbonImmutable $month): Collection
    {
        $last = $month->endOfMonth();
        $filters = ['start' => $month->toDateString(), 'end' => $last->toDateString()];
        $publications = DB::table('calendar_publications')->get()->groupBy('published_by');
        if ($publications->isEmpty()) {
            return collect();
        }

        $publishers = User::with('roles')->whereIn('id', $publications->keys())->get()->keyBy('id');
        $events = collect();

        foreach ($publications as $publisherId => $rows) {
            $publisher = $publishers->get($publisherId);
            if (!$publisher || !$publisher->canLogin() || !$publisher->isSuperAdmin()) {
                continue;
            }

            $titles = $rows->pluck('public_title', 'event_key');
            $schedule = $calendar->build($publisher, $filters, $titles->keys()->all());
            foreach ($schedule['events'] as $event) {
                if (!$titles->has($event['id'])) {
                    continue;
                }

                $start = CarbonImmutable::parse($event['start'])->setTimezone(self::ZONE);
                $end = CarbonImmutable::parse($event['end'])->setTimezone(self::ZONE)->subSecond();
                $date = ($start < $month ? $month : $start)->toDateString();
                $endDate = ($end > $last ? $last : $end)->toDateString();
                $publiclyVisible = [
                    'title' => $titles->get($event['id']),
                    'date' => $date,
                    'time' => $event['allDay'] || $start < $month ? null : $start->format('H:i'),
                ];
                if ($endDate > $date) {
                    $publiclyVisible['end_date'] = $endDate;
                }
                $events->push($publiclyVisible);
            }
        }

        return $events;
    }
}
