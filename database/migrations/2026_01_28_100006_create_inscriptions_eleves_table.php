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
        Schema::create('inscriptions_eleves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->onDelete('cascade');
            $table->foreignId('classe_id')->nullable()->constrained('classes')->onDelete('cascade');
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('cascade');
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->onDelete('cascade');
            $table->foreignId('niveau_demande_id')->nullable()->nullable()->constrained('niveaux')->onDelete('cascade');
            $table->date('date_inscription')->default(DB::raw('CURRENT_DATE'));
            $table->enum('type_inscription', [
                'nouvelle', 'reinscription', 'transfert_entrant'
            ]);
            $table->enum('statut', ['ACTIVE', 'TRANSFEREE', 'TERMINEE', 'ANNULEE'])->default('ACTIVE');
            $table->integer('numero_inscription')->nullable(); // Numéro d'ordre dans la classe (INSCR0001, INSCR0002, etc..)
             $table->text('motif_rejet')->nullable();
            $table->boolean('est_redoublant')->default(false);
            $table->json('pieces_fournies')->nullable();
            $table->text('observations')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('valide_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Un élève ne peut être inscrit qu'une fois dans une classe pour une année
            $table->unique(['eleve_id', 'classe_id', 'annee_scolaire_id'], 'unique_inscription');
            $table->index(['ecole_id', 'annee_scolaire_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscriptions_eleves');
    }
};
