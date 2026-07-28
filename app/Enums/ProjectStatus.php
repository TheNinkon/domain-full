<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Idea = 'idea';
    case InProgress = 'in_progress';
    case Launched = 'launched';
    case Paused = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::Idea => 'Idea',
            self::InProgress => 'En progreso',
            self::Launched => 'Lanzado',
            self::Paused => 'Pausado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Idea => 'secondary',
            self::InProgress => 'warning',
            self::Launched => 'success',
            self::Paused => 'info',
        };
    }
}
