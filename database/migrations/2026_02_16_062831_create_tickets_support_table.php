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
        Schema::create('tickets_support', function (Blueprint $table) {
            $table->id();
            $table->string('numero_ticket')->unique();
            $table->foreignId('demandeur_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('sujet');
            $table->text('description');
            $table->enum('priorite', ['BASSE', 'NORMALE', 'HAUTE', 'URGENTE'])->default('NORMALE');
            $table->enum('categorie', ['TECHNIQUE', 'FONCTIONNELLE', 'DEMANDE', 'AUTRE'])->default('TECHNIQUE');
            $table->enum('statut', ['OUVERT', 'EN_COURS', 'RESOLU', 'FERME', 'REJETE'])->default('OUVERT');
            $table->text('reponse')->nullable();
            $table->timestamp('date_resolution')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets_support');
    }
};
