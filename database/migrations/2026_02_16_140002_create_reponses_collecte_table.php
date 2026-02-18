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
        Schema::create('reponses_collecte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulaire_id')->constrained('formulaires_collecte')->onDelete('cascade');
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->json('donnees'); // { champ_name: value, ... }
            $table->enum('statut', [
                'brouillon',
                'soumis',
                'valide_zone',
                'valide_commune',
                'valide_province',
                'rejete'
            ])->default('brouillon');
            $table->foreignId('soumis_par')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('soumis_at')->nullable();
            $table->foreignId('valide_par')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('valide_at')->nullable();
            $table->string('niveau_validation')->nullable(); // zone, commune, province
            $table->text('motif_rejet')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['formulaire_id', 'school_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reponses_collecte');
    }
};
