<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Models\Schedules\UserSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleCalendarController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $start = $request->input('start');
        $end = $request->input('end');
        $merchantId = $request->input('merchant_id');
        $viewType = $request->input('view', 'dayGridMonth');

        $query = UserSchedule::query()
            ->with(['user', 'merchant']);

        if ($start && $end) {
            $query->whereBetween('date', [$start, $end]);
        }

        $isAdmin = auth()->user()->role === RoleType::SuperAdmin;

        if ($merchantId && $isAdmin) {
            $query->where('merchant_id', $merchantId);
        }

        if (! $isAdmin) {
            $merchantIds = auth()->user()->merchants()->pluck('merchants.id');
            $query->whereIn('merchant_id', $merchantIds);
        }

        $schedules = $query->get();

        $isMonthView = $viewType === 'dayGridMonth';

        if ($isMonthView) {
            return $this->groupedEvents($schedules);
        }

        return $this->individualEvents($schedules);
    }

    private function groupedEvents($schedules): JsonResponse
    {
        $grouped = $schedules->groupBy(function (UserSchedule $schedule) {
            return $schedule->date->format('Y-m-d').'|'.$schedule->merchant_id.'|'.$schedule->start_time.'|'.$schedule->end_time;
        });

        $events = $grouped->map(function ($items, $key) {
            [$date, $merchantId, $startTime, $endTime] = explode('|', $key);

            $first = $items->first();
            $color = $this->merchantColor((int) $merchantId);
            $count = $items->count();

            $start = $date.'T'.$startTime;
            $end = $date.'T'.$endTime;

            if ($count === 1) {
                $single = $items->first();

                return [
                    'id' => $single->id,
                    'title' => $single->user->name,
                    'start' => $start,
                    'end' => $end,
                    'backgroundColor' => $color['bg'],
                    'borderColor' => $color['border'],
                    'textColor' => $color['text'],
                    'extendedProps' => [
                        'merchant_name' => $single->merchant->name,
                        'start_time' => substr($startTime, 0, 5),
                        'end_time' => substr($endTime, 0, 5),
                        'count' => 1,
                        'users' => [[
                            'name' => $single->user->name,
                            'notes' => $single->notes,
                        ]],
                    ],
                ];
            }

            return [
                'id' => 'g_'.$first->merchant_id.'_'.$date.'_'.$startTime,
                'title' => $count.' orang',
                'start' => $start,
                'end' => $end,
                'backgroundColor' => $color['bg'],
                'borderColor' => $color['border'],
                'textColor' => $color['text'],
                'extendedProps' => [
                    'merchant_name' => $first->merchant->name,
                    'start_time' => substr($startTime, 0, 5),
                    'end_time' => substr($endTime, 0, 5),
                    'count' => $count,
                    'users' => $items->map(fn (UserSchedule $s) => [
                        'name' => $s->user->name,
                        'notes' => $s->notes,
                    ])->values()->toArray(),
                ],
            ];
        })->values();

        return response()->json($events);
    }

    private function individualEvents($schedules): JsonResponse
    {
        $events = $schedules->map(function (UserSchedule $schedule) {
            $color = $this->merchantColor($schedule->merchant_id);

            return [
                'id' => $schedule->id,
                'title' => $schedule->user->name.' @ '.$schedule->merchant->name,
                'start' => $schedule->date->format('Y-m-d').'T'.$schedule->start_time,
                'end' => $schedule->date->format('Y-m-d').'T'.$schedule->end_time,
                'backgroundColor' => $color['bg'],
                'borderColor' => $color['border'],
                'textColor' => $color['text'],
                'extendedProps' => [
                    'user_name' => $schedule->user->name,
                    'merchant_name' => $schedule->merchant->name,
                    'start_time' => substr($schedule->start_time, 0, 5),
                    'end_time' => substr($schedule->end_time, 0, 5),
                    'notes' => $schedule->notes,
                    'count' => 1,
                    'users' => [[
                        'name' => $schedule->user->name,
                        'notes' => $schedule->notes,
                    ]],
                ],
            ];
        });

        return response()->json($events);
    }

    private function merchantColor(int $id): array
    {
        $colors = [
            ['bg' => '#3B82F6', 'border' => '#2563EB', 'text' => '#FFFFFF'],
            ['bg' => '#10B981', 'border' => '#059669', 'text' => '#FFFFFF'],
            ['bg' => '#F59E0B', 'border' => '#D97706', 'text' => '#FFFFFF'],
            ['bg' => '#8B5CF6', 'border' => '#7C3AED', 'text' => '#FFFFFF'],
            ['bg' => '#EF4444', 'border' => '#DC2626', 'text' => '#FFFFFF'],
            ['bg' => '#EC4899', 'border' => '#DB2777', 'text' => '#FFFFFF'],
            ['bg' => '#06B6D4', 'border' => '#0891B2', 'text' => '#FFFFFF'],
            ['bg' => '#84CC16', 'border' => '#65A30D', 'text' => '#000000'],
        ];

        return $colors[$id % \count($colors)];
    }
}
