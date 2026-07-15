<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pendente = 'pendente';
    case Aprovado = 'aprovado';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Aguardando aprovação',
            self::Aprovado => 'Aprovado',
        };
    }
}
