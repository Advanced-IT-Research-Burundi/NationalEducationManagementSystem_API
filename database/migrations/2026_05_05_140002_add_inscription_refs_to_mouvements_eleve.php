<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mouvements_eleve', function (Blueprint $table) {
            $table->foreignId('inscription_origine_id')
                ->nullable()
                ->after('classe_origine_id')
                ->constrained('inscriptions')
                ->nullOnDelete();

            $table->foreignId('inscription_destination_id')
                ->nullable()
                ->after('inscription_origine_id')
                ->constrained('inscriptions')
                ->nullOnDelete();

            $table->foreignId('niveau_origine_id')
                ->nullable()
                ->after('inscription_destination_id')
                ->constrained('niveaux_scolaires')
                ->nullOnDelete();

            $table->foreignId('niveau_destination_id')
                ->nullable()
                ->after('niveau_origine_id')
                ->constrained('niveaux_scolaires')
                ->nullOnDelete();

            $table->foreignId('classe_destination_id')
                ->nullable()
                ->after('niveau_destination_id')
                ->constrained('classes')
                ->nullOnDelete();

            $table->foreignId('annee_scolaire_destination_id')
                ->nullable()
                ->after('classe_destination_id')
                ->constrained('annee_scolaires')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mouvements_eleve', function (Blueprint $table) {
            $table->dropConstrainedForeignId('annee_scolaire_destination_id');
            $table->dropConstrainedForeignId('classe_destination_id');
            $table->dropConstrainedForeignId('niveau_destination_id');
            $table->dropConstrainedForeignId('niveau_origine_id');
            $table->dropConstrainedForeignId('inscription_destination_id');
            $table->dropConstrainedForeignId('inscription_origine_id');
        });
    }
};
