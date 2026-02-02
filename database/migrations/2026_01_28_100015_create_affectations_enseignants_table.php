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
        Schema::create('affectations_enseignants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');
            $table->foreignId('classe_id')->constrained('classes')->onDelete('cascade');
            $table->string('matiere')->nullable(); // Matière enseignée dans cette classe
            $table->boolean('est_titulaire')->default(false); // Est-ce l'enseignant titulaire de la classe?
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->enum('statut', ['ACTIVE', 'TERMINEE', 'ANNULEE'])->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Un enseignant ne peut être affecté qu'une fois à une classe pour une matière
            $table->unique(['enseignant_id', 'classe_id', 'matiere'], 'unique_affectation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectations_enseignants');
    }
};
