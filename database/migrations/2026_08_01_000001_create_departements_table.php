<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->foreignId('departement_parent_id')->nullable();
            $table->foreignId('responsable_id')->nullable();
            $table->string('couleur', 20)->nullable();
            $table->string('statut')->default('ACTIF')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['nom', 'statut']);

            $table->foreign('departement_parent_id', 'departement_parent_fk')
                ->references('id')->on('departements')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('responsable_id', 'departement_responsable_fk')
                ->references('id')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departements');
    }
};
