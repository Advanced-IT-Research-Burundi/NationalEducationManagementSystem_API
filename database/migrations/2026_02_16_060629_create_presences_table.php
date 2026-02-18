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
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');
            $table->date('date');
            $table->time('heure_arrivee')->nullable();
            $table->time('heure_depart')->nullable();
            $table->enum('statut', ['PRESENT', 'ABSENT_JUSTIFIE', 'ABSENT_NON_JUSTIFIE', 'RETARD', 'CONGE'])->default('PRESENT');
            $table->text('justificatif')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint pour éviter les doublons
            $table->unique(['enseignant_id', 'date'], 'unique_presence_per_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
