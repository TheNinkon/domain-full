<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $fillable = [
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
        ];
    }

    public function isConfigured(): bool
    {
        return filled($this->host) && filled($this->port) && filled($this->from_address);
    }

    /**
     * There is always exactly one settings row (id 1).
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
