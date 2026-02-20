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
        Schema::create('mouvements_eleve', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves')->cascadeOnDelete();
            $table->foreignId('annee_scolaire_id')->constrained('annee_scolaires')->cascadeOnDelete();
            $table->enum('type_mouvement', ['transfert_sortant','transfert_entrant','abandon','exclusion','deces','passage','redoublement','reintegration']);
            $table->date('date_mouvement');
            $table->foreignId('ecole_origine_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('ecole_destination_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('classe_origine_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->text('motif')->nullable();
            $table->string('document_reference', 255)->nullable();
            $table->string('document_path', 255)->nullable();
            $table->enum('statut', ['en_attente', 'valide', 'rejete']) ->default('en_attente');
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_validation')->nullable();
            $table->text('observations')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['eleve_id', 'annee_scolaire_id']);
            $table->index('type_mouvement');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mouvements_eleve');
    }
};
