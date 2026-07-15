<?php

namespace App\Enums;

enum UserRole: string
{
    case Direcao = 'direcao';
    case Professor = 'professor';

    public function label(): string
    {
        return match ($this) {
            self::Direcao => 'Direção',
            self::Professor => 'Professor',
        };
    }
}
