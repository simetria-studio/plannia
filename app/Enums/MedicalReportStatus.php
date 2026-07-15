<?php

namespace App\Enums;

enum MedicalReportStatus: string
{
    case Possui = 'possui';
    case EmAndamento = 'em_andamento';
    case NaoPossui = 'nao_possui';

    public function label(): string
    {
        return match ($this) {
            self::Possui => 'Possui',
            self::EmAndamento => 'Em andamento',
            self::NaoPossui => 'Não possui',
        };
    }
}
