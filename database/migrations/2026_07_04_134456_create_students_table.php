<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('turma_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('full_name');
            $table->date('birth_date')->nullable();
            $table->string('cpf', 14);
            $table->string('legal_guardian');
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->text('address');
            $table->unsignedSmallInteger('entry_year');
            $table->string('medical_report_status');
            $table->string('cid');
            $table->text('observations');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
