<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_administratifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('service_id')->nullable();
            $table->foreignId('fonction_id')->nullable();
            $table->string('matricule')->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->string('sexe', 10)->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('type_personnel')->nullable()->index();
            $table->string('statut')->default('ACTIF')->index();
            $table->date('date_recrutement')->nullable();
            $table->text('adresse')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'fonction_id']);
            $table->index(['nom', 'prenom']);

            $table->foreign('user_id', 'pa_user_fk')
                ->references('id')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('service_id', 'pa_service_fk')
                ->references('id')->on('services')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('fonction_id', 'pa_fonction_fk')
                ->references('id')->on('fonctions')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('created_by', 'pa_created_by_fk')
                ->references('id')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_administratifs');
    }
};
