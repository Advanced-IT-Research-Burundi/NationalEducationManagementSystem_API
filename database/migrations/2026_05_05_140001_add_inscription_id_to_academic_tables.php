<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->foreignId('inscription_id')
                ->nullable()
                ->after('eleve_id')
                ->constrained('inscriptions')
                ->nullOnDelete();
        });

        Schema::table('note_conduites', function (Blueprint $table) {
            $table->foreignId('inscription_id')
                ->nullable()
                ->after('eleve_id')
                ->constrained('inscriptions')
                ->nullOnDelete();
        });

        Schema::table('sanction_eleves', function (Blueprint $table) {
            $table->foreignId('inscription_id')
                ->nullable()
                ->after('eleve_id')
                ->constrained('inscriptions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inscription_id');
        });

        Schema::table('note_conduites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inscription_id');
        });

        Schema::table('sanction_eleves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inscription_id');
        });
    }
};
