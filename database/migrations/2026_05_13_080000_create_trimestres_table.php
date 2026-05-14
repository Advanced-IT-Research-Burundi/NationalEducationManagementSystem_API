<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trimestres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->string('nom');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->boolean('actif')->default(false);
            $table->boolean('verrouille')->default(false);
            $table->timestamps();

            $table->unique(['annee_scolaire_id', 'nom'], 'trimestres_annee_nom_unique');
            $table->index(['annee_scolaire_id', 'actif'], 'trimestres_annee_actif_idx');
            $table->index(['annee_scolaire_id', 'date_debut', 'date_fin'], 'trimestres_annee_dates_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trimestres');
    }
};
