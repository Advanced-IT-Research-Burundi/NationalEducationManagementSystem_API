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
        Schema::create('salles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batiment_id')->constrained('batiments')->onDelete('cascade');
            $table->string('numero');
            $table->enum('type', ['CLASSE', 'LABORATOIRE', 'BUREAU', 'SANITAIRE', 'BIBLIOTHEQUE', 'AUTRE'])->default('CLASSE');
            $table->integer('capacite')->nullable()->comment('Capacité en nombre de places');
            $table->decimal('superficie', 8, 2)->nullable()->comment('Superficie en m²');
            $table->enum('etat', ['BON', 'MOYEN', 'MAUVAIS', 'DANGEREUX'])->default('BON');
            $table->boolean('accessible_handicap')->default(false);
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
        Schema::dropIfExists('salles');
    }
};
