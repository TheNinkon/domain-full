<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainDailyStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'date',
        'visits',
        'unique_visitors',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
