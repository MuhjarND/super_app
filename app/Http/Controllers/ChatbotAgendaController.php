<?php

namespace App\Http\Controllers;

use App\Services\IntegratedCalendarService;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChatbotAgendaController extends Controller
{
    public function index(Request $request, IntegratedCalendarService $calendar)
    {
        $data = $request->validate([
            'app_user_id' => ['required', 'string', 'max:255'],
        ]);

        $identifier = trim($data['app_user_id']);
        $user = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $identifier)->first()
            : (ctype_digit($identifier) ? User::find($identifier) : null);

        abort_unless($user && $user->canLogin(), 403);
        abort_unless($user->canAccessIntegratedCalendar(), 403);

        $start = Carbon::now('Asia/Jayapura')->startOfDay();
        $end = $start->copy()->addDays(6);
        // Never accept a caller-supplied scope or user ID from the WhatsApp message.
        $calendarData = $calendar->build($user, [
            'scope' => 'mine',
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ]);

        $events = collect($calendarData['events'])->map(function ($event) {
            return [
                'title' => $event['title'],
                'start' => $event['start'],
                'end' => $event['end'],
                'all_day' => $event['allDay'],
                'module' => $event['extendedProps']['module_label'],
                'status' => $event['extendedProps']['status_label'],
                'location' => $event['extendedProps']['location'],
                'time_label' => $event['extendedProps']['time_label'],
            ];
        })->values()->all();

        return response()->json([
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'events' => $events,
        ])->header('Cache-Control', 'no-store, private');
    }
}
