<?php

namespace App\Http\Controllers;

use App\Exceptions\AiFailure;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __invoke(Request $request, ReportService $reports)
    {
        if ($request->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $period = $this->period($request);

        return view('reports.index', [
            'period' => $period,
            ...$reports->page($request->user(), $period),
        ]);
    }

    /**
     * Dipanggil otomatis oleh halaman laporan saat analisis belum ada
     * atau data sudah berubah.
     */
    public function generate(Request $request, ReportService $reports)
    {
        if ($request->user()->isAdmin()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Halaman ini bukan untuk admin.'], 403)
                : redirect()->route('admin.dashboard');
        }

        $period = $this->period($request);

        try {
            $reports->generate($request->user(), $period);
        } catch (AiFailure $e) {
            $status = $e->reason === 'quota' ? 429 : 422;

            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], $status)
                : back()->with('error', $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', 'Laporan AI sudah diperbarui.');
    }

    private function period(Request $request): int
    {
        return in_array((int) $request->input('period', $request->query('period')), [7, 30], true)
            ? (int) $request->input('period', $request->query('period'))
            : 7;
    }
}
