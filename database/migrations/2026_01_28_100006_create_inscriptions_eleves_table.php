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
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('numero_inscription', 30)->unique();
            $table->foreignId('eleve_id')
                ->constrained('eleves')
                ->cascadeOnDelete();
            $table->foreignId('campagne_id')
                ->constrained('campagnes_inscription')
                ->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')
                ->constrained('annee_scolaires')
                ->cascadeOnDelete();
            $table->foreignId('ecole_id')
                ->constrained('ecoles')
                ->cascadeOnDelete();
            $table->foreignId('niveau_demande_id')
                ->constrained('niveaux_scolaires') // Note: need to ensure niveaux_scolaires is the table name, migration said 'niveaux'. Doc says 'niveaux_scolaires'.
                ->cascadeOnDelete();
            $table->enum('type_inscription', [
                'nouvelle', 'reinscription', 'transfert_entrant'
            ]);
            $table->enum('statut', [
                'brouillon', 'soumis', 'valide', 'rejete', 'annule'
            ])->default('brouillon');
            $table->date('date_inscription');
            $table->timestamp('date_soumission')->nullable();
            $table->timestamp('date_validation')->nullable();
            $table->text('motif_rejet')->nullable();
            $table->boolean('est_redoublant')->default(false);
            $table->json('pieces_fournies')->nullable();
            $table->text('observations')->nullable();
            $table->foreignId('soumis_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('valide_par')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['eleve_id', 'annee_scolaire_id']);
            $table->index('statut');
            $table->index(['ecole_id', 'annee_scolaire_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
