<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DomainCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
    ];

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }
}
