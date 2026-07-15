<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::create([
            'name' => 'Escola Municipal Exemplo',
            'address' => 'Rua das Flores, 123 — Centro',
            'cnpj' => '00.000.000/0001-00',
        ]);

        Turma::create(['school_id' => $school->id, 'name' => '3º Ano A', 'turno' => 'Manhã']);
        Turma::create(['school_id' => $school->id, 'name' => '4º Ano B', 'turno' => 'Tarde']);
        Turma::create(['school_id' => $school->id, 'name' => '5º Ano C', 'turno' => 'Integral']);

        User::create([
            'school_id' => $school->id,
            'name' => 'Direção Escolar',
            'email' => 'direcao@plannia.com',
            'password' => 'password',
            'role' => UserRole::Direcao,
            'birth_date' => '1980-05-15',
            'email_verified_at' => now(),
        ]);

        User::create([
            'school_id' => $school->id,
            'name' => 'Prof. Maria Silva',
            'email' => 'professor@plannia.com',
            'password' => 'password',
            'role' => UserRole::Professor,
            'birth_date' => '1990-08-20',
            'email_verified_at' => now(),
        ]);
    }
}
