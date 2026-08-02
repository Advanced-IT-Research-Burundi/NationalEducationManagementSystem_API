<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->foreignId('departement_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('niveau_hierarchique')->default(1);
            $table->decimal('salaire_min', 14, 2)->nullable();
            $table->decimal('salaire_max', 14, 2)->nullable();
            $table->string('statut')->default('ACTIF')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['departement_id', 'statut']);
            $table->index(['nom', 'statut']);

            $table->foreign('departement_id', 'poste_departement_fk')
                ->references('id')->on('departements')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postes');
    }
};
