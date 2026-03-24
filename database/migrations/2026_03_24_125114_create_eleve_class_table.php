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
        Schema::create('eleve_class', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->onDelete('cascade');
            $table->foreignId('classe_id')->constrained('classes')->onDelete('cascade');
            $table->string('annee_scolaire')->nullable();
            $table->date('date_inscription')->nullable();
            $table->string('statut')->default('ACTIVE');
            $table->integer('numero_ordre')->nullable();
            $table->timestamps();

            // The user wanted unique combination
            $table->unique(['eleve_id', 'classe_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eleve_class');
    }
};
