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
        Schema::create('conges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');
            $table->enum('type', ['ANNUEL', 'MALADIE', 'MATERNITE', 'PATERNITE', 'EXCEPTIONNEL'])->default('ANNUEL');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->integer('nombre_jours')->default(0);
            $table->text('motif')->nullable();
            $table->enum('statut', ['DEMANDE', 'APPROUVE', 'REFUSE', 'ANNULE'])->default('DEMANDE');
            $table->foreignId('approuveur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('date_approbation')->nullable();
            $table->text('commentaire_approbateur')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conges');
    }
};
