<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    public const TYPE_FEES = 'fees';

    public const TYPE_EXPENSES = 'expenses';

    public const TYPE_PLAYERS = 'players';

    public const TYPE_BATCHES = 'batches';

    protected $fillable = [
        'title',
        'type',
        'start_date',
        'end_date',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function ($report) {
            if (auth()->check() && empty($report->created_by)) {
                $report->created_by = auth()->id();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
