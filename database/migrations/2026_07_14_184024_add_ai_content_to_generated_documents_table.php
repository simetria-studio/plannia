<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->json('ai_content')->nullable()->after('file_path');
            $table->json('extracted_sources')->nullable()->after('ai_content');
        });
    }

    public function down(): void
    {
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropColumn(['ai_content', 'extracted_sources']);
        });
    }
};
