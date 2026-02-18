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
        Schema::create('batiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('ecoles')->onDelete('cascade');
            $table->string('nom');
            $table->enum('type', ['ACADEMIQUE', 'ADMINISTRATIF', 'SPORTIF', 'AUTRE'])->default('ACADEMIQUE');
            $table->year('annee_construction')->nullable();
            $table->decimal('superficie', 10, 2)->nullable()->comment('Superficie en m²');
            $table->integer('nombre_etages')->default(1);
            $table->enum('etat', ['BON', 'MOYEN', 'MAUVAIS', 'DANGEREUX'])->default('BON');
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
        Schema::dropIfExists('batiments');
    }
};
