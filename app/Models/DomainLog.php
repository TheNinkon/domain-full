<?php

namespace App\Models;

use App\Enums\DomainLogType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'domain_id',
        'user_id',
        'type',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => DomainLogType::class,
            'meta' => 'array',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
