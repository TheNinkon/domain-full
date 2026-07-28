<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaptchaSetting extends Model
{
    protected $fillable = [
        'site_key',
        'secret_key',
    ];

    protected function casts(): array
    {
        return [
            'secret_key' => 'encrypted',
        ];
    }

    public function isConfigured(): bool
    {
        return filled($this->site_key) && filled($this->secret_key);
    }

    /**
     * There is always exactly one settings row (id 1).
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
