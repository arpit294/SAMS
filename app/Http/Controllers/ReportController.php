<?php

namespace App\Http\Controllers;

use App\DataTables\ReportsDataTable;
use App\Models\Batch;
use App\Models\Level;
use App\Models\Sport;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    /**
     * Student enrollment report.
     */
    public function index(ReportsDataTable $dataTable)
    {
        abort_if(! Auth::user()->can('dashboard_view'), 403);

        try {
            $sports = Sport::orderBy('name')->pluck('name', 'id');
            $levels = Level::orderBy('name')->pluck('name', 'id');
            $batches = Batch::orderBy('name')->pluck('name', 'id');

            $currentYear = (int) now()->year;

            $years = DB::table('batch_player')
                ->whereNotNull('joined_at')
                ->selectRaw('YEAR(joined_at) as year')
                ->distinct()
                ->pluck('year')
                ->map(fn ($year) => (int) $year)
                ->merge([$currentYear, $currentYear - 1])
                ->unique()
                ->sortDesc()
                ->mapWithKeys(fn ($year) => [(string) $year => (string) $year])
                ->all();

            return $dataTable->render('reports.index', compact('sports', 'levels', 'batches', 'years'));
        } catch (Exception $e) {
            Log::error('Report Index Error: '.$e->getMessage());

            return back()->with('error', 'Something went wrong.');
        }
    }
}
