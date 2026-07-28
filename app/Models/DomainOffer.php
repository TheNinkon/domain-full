<?php

namespace App\Models;

use App\Enums\OfferStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'name',
        'email',
        'phone',
        'amount',
        'currency',
        'message',
        'status',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'status' => OfferStatus::class,
            'amount' => 'decimal:2',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * A privacy-friendly version of the offerer's name for public display
     * (e.g. the public "Offers" page on the marketplace landing): first
     * name shown in full, other words reduced to an initial.
     */
    protected function maskedName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];
                $words = array_values(array_filter($words, fn ($word) => $word !== ''));

                if (empty($words)) {
                    return 'Anonymous';
                }

                $first = array_shift($words);
                $rest = array_map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)) . '.', $words);

                return trim($first . ' ' . implode(' ', $rest));
            },
        );
    }
}
