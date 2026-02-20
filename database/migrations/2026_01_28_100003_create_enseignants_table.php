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
        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            $table->string('matricule')->unique();
            $table->string('specialite')->nullable(); // Mathématiques, Français, etc.
            $table->enum('qualification', ['LICENCE', 'MASTER', 'DOCTORAT', 'DIPLOME_PEDAGOGIQUE', 'AUTRE'])->nullable();
            $table->integer('annees_experience')->default(0);
            $table->date('date_embauche')->nullable();
            $table->string('telephone')->nullable();
            $table->enum('statut', ['ACTIF', 'INACTIF', 'CONGE', 'SUSPENDU', 'RETRAITE'])->default('ACTIF');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Un utilisateur ne peut être enseignant que dans une seule école
            $table->unique(['user_id', 'school_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enseignants');
    }
};
