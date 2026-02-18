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
        Schema::create('equipements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salle_id')->nullable()->constrained('salles')->onDelete('set null');
            $table->foreignId('school_id')->constrained('ecoles')->onDelete('cascade');
            $table->string('nom');
            $table->enum('type', ['MOBILIER', 'INFORMATIQUE', 'LABORATOIRE', 'SPORT', 'AUTRE'])->default('MOBILIER');
            $table->string('marque')->nullable();
            $table->string('modele')->nullable();
            $table->string('numero_serie')->nullable()->unique();
            $table->date('date_acquisition')->nullable();
            $table->enum('etat', ['NEUF', 'BON', 'MOYEN', 'MAUVAIS', 'HORS_SERVICE'])->default('BON');
            $table->decimal('valeur', 12, 2)->nullable()->comment('Valeur en devise locale');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipements');
    }
};
