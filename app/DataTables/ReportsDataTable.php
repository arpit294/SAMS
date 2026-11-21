<?php

namespace App\DataTables;

use App\Models\PlayerFee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ReportsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder<User>  $query
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $paidFees = PlayerFee::query()
            ->where('status', 'paid')
            ->get(['player_id', 'batch_id', 'start_date', 'end_date']);

        $year = $this->reportYear();
        $table = new EloquentDataTable($query);

        $table->addColumn('player', function ($row) {
            $name = e(trim($row->firstname.' '.$row->lastname));
            $sport = e($row->sport_name ?: '—');
            $batch = e($row->batch_name ?: '—');
            $level = e($row->level_name ?: '—');

            $joined = $row->joined_at
                ? Carbon::parse($row->joined_at)->format('d M Y')
                : '—';

            return '<div class="fw-semibold text-dark text-nowrap">'.$name.' - '.$sport.'</div>'
                .'<div class="text-secondary text-nowrap" style="font-size: 11px;">'.$batch.' - '.$level.'</div>'
                .'<div class="text-secondary text-nowrap" style="font-size: 11px;">Joined '.$joined.'</div>';
        });

        $rawColumns = ['player'];

        foreach (range(1, 12) as $month) {
            $column = 'month_'.$month;
            $rawColumns[] = $column;

            $table->addColumn($column, function ($row) use ($paidFees, $year, $month) {
                return $this->monthStatus($row, $paidFees, $year, $month);
            });
        }

        return $table
            ->rawColumns($rawColumns)
            ->filterColumn('player', function ($query, $keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('users.firstname', 'like', "%{$keyword}%")
                        ->orWhere('users.lastname', 'like', "%{$keyword}%")
                        ->orWhere('sports.name', 'like', "%{$keyword}%")
                        ->orWhere('batches.name', 'like', "%{$keyword}%")
                        ->orWhere('levels.name', 'like', "%{$keyword}%");
                });
            })
            ->orderColumn('player', 'users.firstname $1')
            ->setRowId(function ($row) {
                return $row->id.'-'.$row->batch_id;
            });
    }

    /**
     * One row per student and batch. The year filter chooses which January–December grid to show.
     *
     * @return QueryBuilder<User>
     */
    public function query(User $model): QueryBuilder
    {
        $endOfYear = Carbon::createFromDate($this->reportYear(), 12, 31)->toDateString();

        $query = $model->newQuery()
            ->role('player')
            ->join('batch_player', 'users.id', '=', 'batch_player.player_id')
            ->join('batches', 'batches.id', '=', 'batch_player.batch_id')
            ->leftJoin('sports', 'sports.id', '=', 'batches.sport_id')
            ->leftJoin('levels', 'levels.id', '=', 'batches.level_id')
            ->where(function ($inner) use ($endOfYear) {
                $inner->whereNull('batch_player.joined_at')
                    ->orWhere('batch_player.joined_at', '<=', $endOfYear);
            })
            ->select([
                'users.id',
                'users.firstname',
                'users.lastname',
                'batches.id as batch_id',
                'batches.name as batch_name',
                'sports.name as sport_name',
                'levels.name as level_name',
                'batch_player.joined_at',
            ]);

        if (request()->filled('sport')) {
            $query->where('batches.sport_id', request('sport'));
        }

        if (request()->filled('batch')) {
            $query->where('batches.id', request('batch'));
        }

        if (request()->filled('level')) {
            $query->where('batches.level_id', request('level'));
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('datatable')
            ->columns($this->getColumns())
            ->minifiedAjax('', 'data.sport = $("#sportFilter").val(); data.batch = $("#batchFilter").val(); data.level = $("#levelFilter").val(); data.year = $("#yearFilter").val();')
            ->orderBy(0)
            ->parameters([
                'searchDelay' => 400,
                'scrollX' => true,
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            Column::computed('player')
                ->title('Students')
                ->name('users.firstname')
                ->searchable(true)
                ->orderable(true),
        ];

        foreach (range(1, 12) as $month) {
            $columns[] = Column::computed('month_'.$month)
                ->title(Carbon::create(null, $month, 1)->format('M'))
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center text-nowrap');
        }

        return $columns;
    }

    /**
     * Paid, Pending, or not applicable for one month of the selected year.
     */
    private function monthStatus(object $row, Collection $paidFees, int $year, int $month): string
    {
        $monthStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $joinedMonth = $row->joined_at
            ? Carbon::parse($row->joined_at)->startOfMonth()
            : $monthStart->copy();

        if ($monthStart->lt($joinedMonth) || $monthStart->gt(now()->startOfMonth())) {
            return '<span class="text-muted">—</span>';
        }

        $start = $monthStart->toDateString();
        $end = $monthStart->copy()->endOfMonth()->toDateString();

        $isPaid = $paidFees->contains(function (PlayerFee $fee) use ($row, $start, $end) {
            return (int) $fee->player_id === (int) $row->id
                && (int) $fee->batch_id === (int) $row->batch_id
                && $fee->start_date
                && $fee->end_date
                && $fee->start_date->toDateString() <= $end
                && $fee->end_date->toDateString() >= $start;
        });

        if ($isPaid) {
            return '<span class="badge bg-success">Paid</span>';
        }

        return '<span class="badge bg-danger">Pending</span>';
    }

    private function reportYear(): int
    {
        return request()->filled('year') ? (int) request('year') : (int) now()->year;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'StudentReport_'.date('YmdHis');
    }
}
