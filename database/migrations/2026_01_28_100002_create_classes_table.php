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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('nom'); // 7ème A, 7ème B, 8ème A, etc.
            $table->string('code')->nullable(); // Code unique de la classe
            $table->foreignId('niveau_id')->constrained('niveaux')->onDelete('cascade');
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('annee_scolaire'); // 2025-2026
            $table->string('local')->nullable(); // Numéro de salle
            $table->integer('capacite')->nullable(); // Capacité maximale
            $table->enum('statut', ['ACTIVE', 'INACTIVE', 'ARCHIVEE'])->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Index pour recherche rapide
            $table->index(['school_id', 'niveau_id', 'annee_scolaire']);
            $table->unique(['school_id', 'nom', 'annee_scolaire']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
