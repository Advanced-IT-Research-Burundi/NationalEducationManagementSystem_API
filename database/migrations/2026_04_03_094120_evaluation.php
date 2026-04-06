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
        Schema::dropIfExists('evaluations');

        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classe_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('eleve_id')->constrained('eleves')->onDelete('cascade');
            $table->foreignId('matiere_id')->constrained('matieres')->onDelete('cascade');
            $table->foreignId('enseignant_id')->nullable()->constrained('enseignants')->onDelete('set null');
            $table->enum('trimestre', ['1er Trimestre', '2e Trimestre', '3e Trimestre']);
            $table->enum('categorie', ['TJ', 'Examen'])->default('TJ');
            $table->decimal('ponderation', 5, 2);
            $table->decimal('note', 5, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['eleve_id', 'matiere_id', 'trimestre', 'categorie'],
                'unique_evaluation'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
