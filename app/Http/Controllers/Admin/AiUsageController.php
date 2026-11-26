<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use Illuminate\Http\Request;

class AiUsageController extends Controller
{
    public function __invoke(Request $request)
    {
        $days = in_array((int) $request->query('days'), [7, 30], true) ? (int) $request->query('days') : 7;
        $since = now()->subDays($days - 1)->startOfDay();

        $logs = AiUsageLog::where('created_at', '>=', $since)->get();

        $byFeature = $logs->groupBy('feature')->map(fn ($group) => [
            'calls' => $group->count(),
            'errors' => $group->where('status', 'error')->count(),
            'input_tokens' => $group->sum('input_tokens'),
            'output_tokens' => $group->sum('output_tokens'),
            'avg_ms' => (int) round($group->avg('duration_ms')),
        ]);

        $byDay = collect(range($days - 1, 0))->map(function ($i) use ($logs) {
            $date = now()->subDays($i)->toDateString();
            $dayLogs = $logs->filter(fn ($log) => $log->created_at->toDateString() === $date);

            return [
                'date' => now()->subDays($i),
                'scan' => $dayLogs->where('feature', 'scan')->count(),
                'chat' => $dayLogs->where('feature', 'chat')->count(),
                'errors' => $dayLogs->where('status', 'error')->count(),
                'tokens' => $dayLogs->sum('input_tokens') + $dayLogs->sum('output_tokens'),
            ];
        });

        $errors = $logs->where('status', 'error')->groupBy('error_code')->map->count()->sortDesc();

        return view('admin.ai-usage', [
            'days' => $days,
            'byFeature' => $byFeature,
            'byDay' => $byDay,
            'errorCodes' => $errors,
            'totals' => [
                'calls' => $logs->count(),
                'input_tokens' => $logs->sum('input_tokens'),
                'output_tokens' => $logs->sum('output_tokens'),
            ],
        ]);
    }
}
