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
        Schema::create('affectations_classe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('classes')->cascadeOnDelete();
            $table->date('date_affectation');
            $table->date('date_fin')->nullable();
            $table->boolean('est_active')->default(true);
            $table->unsignedInteger('numero_ordre')->nullable();
            $table->string('motif_changement', 255)->nullable();
            $table->foreignId('affecte_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['inscription_id', 'classe_id'], 'unique_affectation_eleve');
            $table->index(['inscription_id', 'est_active']);
            $table->index(['classe_id', 'est_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectations_classe');
    }
};
