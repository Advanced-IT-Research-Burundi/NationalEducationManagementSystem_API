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
            $table->foreignId('niveau_id')->constrained('niveaux_scolaires')->onDelete('cascade');
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('cascade');
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->onDelete('cascade');
            $table->foreignId('niveau_scolaire_id')->nullable()->constrained('niveaux_scolaires')->onDelete('cascade');
            $table->foreignId('enseignant_principal_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('local')->nullable(); // Numéro de salle
            $table->integer('capacite')->nullable(); // Capacité maximale
             $table->string('salle', 50)->nullable();
            $table->enum('statut', ['ACTIVE', 'INACTIVE', 'ARCHIVEE'])->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Index pour recherche rapide
            $table->index(['ecole_id', 'niveau_id', 'annee_scolaire_id']);
            $table->unique(['ecole_id', 'nom', 'annee_scolaire_id'], 'unique_class_per_school_year');
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
