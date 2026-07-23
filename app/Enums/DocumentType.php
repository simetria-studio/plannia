<?php

namespace App\Enums;

enum DocumentType: string
{
    case Pei = 'pei';
    case Paee = 'paee';
    case PeiPaee = 'pei_paee';

    public function label(): string
    {
        return match ($this) {
            self::Pei => 'PEI',
            self::Paee => 'PAEE',
            self::PeiPaee => 'PEI + PAEE',
        };
    }
}
