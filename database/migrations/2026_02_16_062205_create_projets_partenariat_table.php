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
        Schema::create('projets_partenariat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partenaire_id')->constrained('partenaires')->onDelete('cascade');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->enum('domaine', ['INFRASTRUCTURE', 'EQUIPEMENT', 'FORMATION', 'RECHERCHE', 'AUTRE'])->default('INFRASTRUCTURE');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->decimal('budget_total', 15, 2)->nullable()->comment('Budget en devise locale');
            $table->enum('statut', ['PLANIFIE', 'EN_COURS', 'TERMINE', 'SUSPENDU', 'ANNULE'])->default('PLANIFIE');
            $table->integer('nombre_beneficiaires')->nullable();
            $table->text('objectifs')->nullable();
            $table->text('resultats')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projets_partenariat');
    }
};
