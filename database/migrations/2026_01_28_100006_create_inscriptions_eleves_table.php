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
            $table->foreignId('classe_id')->constrained('classes')->onDelete('cascade');
            $table->string('annee_scolaire'); // 2025-2026
            $table->date('date_inscription');
            $table->enum('statut', ['ACTIVE', 'TRANSFEREE', 'TERMINEE', 'ANNULEE'])->default('ACTIVE');
            $table->integer('numero_ordre')->nullable(); // Numéro d'ordre dans la classe
            $table->text('observations')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Un élève ne peut être inscrit qu'une fois dans une classe pour une année
            $table->unique(['eleve_id', 'classe_id', 'annee_scolaire'], 'unique_inscription');
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
