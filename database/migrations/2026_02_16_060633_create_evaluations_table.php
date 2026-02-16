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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');
            $table->year('annee');
            $table->foreignId('evaluateur_id')->constrained('users')->onDelete('cascade');
            $table->decimal('note', 5, 2)->nullable()->comment('Note sur 100');
            $table->text('points_forts')->nullable();
            $table->text('points_ameliorer')->nullable();
            $table->text('objectifs')->nullable();
            $table->text('commentaires')->nullable();
            $table->date('date_evaluation');
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint pour éviter les doublons
            $table->unique(['enseignant_id', 'annee'], 'unique_evaluation_per_year');
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
