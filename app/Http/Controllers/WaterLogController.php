<?php

namespace App\Http\Controllers;

use App\Services\DailySummaryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WaterLogController extends Controller
{
    public function store(Request $request, DailySummaryService $summary)
    {
        $data = $request->validate([
            'amount_ml' => ['required', 'integer', 'between:50,2000'],
            'vessel' => ['nullable', 'string', Rule::in(['gelas', 'mug', 'tumbler', 'botol', 'botol-besar'])],
        ]);

        $request->user()->waterLogs()->create([
            'amount_ml' => $data['amount_ml'],
            'vessel' => $data['vessel'] ?? null,
            'logged_at' => now(),
        ]);

        return $this->respond($request, $summary, 'Air minum tercatat.');
    }

    public function destroyLatest(Request $request, DailySummaryService $summary)
    {
        $request->user()->waterLogs()
            ->where('logged_at', '>=', now()->startOfDay())
            ->latest('logged_at')
            ->first()
            ?->delete();

        return $this->respond($request, $summary, 'Satu gelas dibatalkan.');
    }

    private function respond(Request $request, DailySummaryService $summary, string $message)
    {
        if ($request->expectsJson()) {
            $today = $summary->forDate($request->user(), now());

            return response()->json([
                'water_ml' => $today['water_ml'],
                'water_target_ml' => $today['water_target_ml'],
            ]);
        }

        return back()->with('success', $message);
    }
}
