<?php

namespace App\Http\Controllers;

use App\Enums\ScanStatus;
use App\Exceptions\AiFailure;
use App\Http\Requests\MealRequest;
use App\Http\Requests\ScanRequest;
use App\Models\Scan;
use App\Services\ScanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ScanController extends Controller
{
    public function __construct(private readonly ScanService $scans) {}

    public function create(Request $request)
    {
        return view('scan.create', [
            'recentScans' => $request->user()->scans()->latest()->take(6)->get(),
        ]);
    }

    public function store(ScanRequest $request)
    {
        try {
            $scan = $this->scans->createAndAnalyze($request->user(), $request->file('photo'));
        } catch (AiFailure $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('scan.show', $scan);
    }

    public function show(Scan $scan)
    {
        Gate::authorize('view', $scan);

        $scan->load('meal');

        return view('scan.show', [
            'scan' => $scan,
        ]);
    }

    public function retry(Scan $scan)
    {
        Gate::authorize('update', $scan);
        abort_unless($scan->status === ScanStatus::Failed, 409, 'Hanya scan yang gagal yang bisa dianalisis ulang.');

        try {
            $this->scans->analyze($scan);
        } catch (AiFailure $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('scan.show', $scan);
    }

    public function saveMeal(MealRequest $request, Scan $scan)
    {
        Gate::authorize('update', $scan);

        if ($scan->status !== ScanStatus::Completed) {
            return back()->with('error', 'Scan ini belum punya hasil yang bisa disimpan.');
        }

        if ($scan->meal()->exists()) {
            return redirect()->route('dashboard')->with('error', 'Hasil scan ini sudah disimpan sebelumnya.');
        }

        $meal = $this->scans->saveAsMeal(
            $scan,
            $request->mealType(),
            $request->eatenAt(),
            $request->validated('title'),
            $request->mealItems(),
            $request->validated('notes'),
        );

        return redirect()
            ->route('dashboard', ['date' => $meal->eaten_at->toDateString()])
            ->with('success', "{$meal->title} masuk ke piringmu.");
    }

    public function photo(Scan $scan)
    {
        Gate::authorize('view', $scan);

        $disk = Storage::disk(config('gizisnap.scan.disk'));

        abort_unless($disk->exists($scan->image_path), 404);

        return $disk->response($scan->image_path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
