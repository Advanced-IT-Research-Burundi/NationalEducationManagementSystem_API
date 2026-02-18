<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('ecoles')->onDelete('cascade');
            $table->foreignId('inspecteur_id')->constrained('users')->onDelete('cascade');
            $table->date('date_prevue');
            $table->date('date_realisation')->nullable();
            $table->enum('type', ['reguliere', 'inopinee', 'thematique'])->default('reguliere');
            $table->enum('statut', ['planifiee', 'en_cours', 'terminee', 'annulee'])->default('planifiee');
            $table->text('rapport')->nullable();
            $table->decimal('note_globale', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
