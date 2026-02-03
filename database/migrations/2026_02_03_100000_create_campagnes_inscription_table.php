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
        Schema::create('campagnes_inscription', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_scolaire_id')
                ->constrained('annee_scolaires')
                ->cascadeOnDelete();
            $table->foreignId('ecole_id')
                ->constrained('ecoles')
                ->cascadeOnDelete();
            $table->enum('type', ['nouvelle', 'reinscription']);
            $table->date('date_ouverture');
            $table->date('date_cloture');
            $table->enum('statut', ['planifiee', 'ouverte', 'cloturee'])
                ->default('planifiee');
            $table->unsignedInteger('quota_max')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['annee_scolaire_id', 'ecole_id']);
            $table->index('statut');
            $table->index('type');
            $table->unique(['annee_scolaire_id', 'ecole_id', 'type'], 'campagne_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campagnes_inscription');
    }
};
