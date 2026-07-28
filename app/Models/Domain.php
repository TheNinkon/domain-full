<?php

namespace App\Models;

use App\Enums\DomainPriority;
use App\Enums\DomainStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'domain_category_id',
        'registrar',
        'status',
        'priority',
        'purchase_date',
        'renewal_date',
        'expiration_date',
        'purchase_cost',
        'renewal_cost',
        'currency',
        'notes',
        'project_id',
        'is_for_sale',
        'auto_renew',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => DomainStatus::class,
            'priority' => DomainPriority::class,
            'purchase_date' => 'date',
            'renewal_date' => 'date',
            'expiration_date' => 'date',
            'purchase_cost' => 'decimal:2',
            'renewal_cost' => 'decimal:2',
            'is_for_sale' => 'boolean',
            'auto_renew' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DomainCategory::class, 'domain_category_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DomainLog::class)->latest('created_at');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(DomainOffer::class)->latest('created_at');
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(DomainDailyStat::class);
    }

    protected function daysUntilExpiration(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) now()->startOfDay()->diffInDays($this->expiration_date?->copy()->startOfDay(), false),
        );
    }
}
