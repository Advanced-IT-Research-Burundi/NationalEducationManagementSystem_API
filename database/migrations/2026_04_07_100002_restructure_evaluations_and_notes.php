<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old evaluations table (test environment only)
        Schema::dropIfExists('evaluations');

        // New evaluations: one row per evaluation event
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('cours_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->enum('trimestre', ['1er Trimestre', '2e Trimestre', '3e Trimestre']);
            $table->enum('type_evaluation', ['TJ', 'Interrogation', 'Devoir', 'TP', 'Examen']);
            $table->date('date_passation');
            $table->decimal('note_maximale', 5, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['classe_id', 'cours_id', 'trimestre']);
        });

        // Notes: one row per student per evaluation
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->decimal('note', 5, 2);
            $table->timestamps();

            $table->unique(['evaluation_id', 'eleve_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
        Schema::dropIfExists('evaluations');
    }
};
