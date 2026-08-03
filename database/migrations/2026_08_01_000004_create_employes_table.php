<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('departement_id')->nullable();
            $table->foreignId('poste_id')->nullable();
            $table->foreignId('service_id')->nullable();
            $table->foreignId('superieur_id')->nullable();
            $table->string('matricule')->unique();
            $table->string('photo_path')->nullable();
            $table->string('nom');
            $table->string('prenom');
            $table->string('sexe', 10)->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->string('nationalite')->nullable();
            $table->string('etat_civil')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('email')->nullable()->unique();
            $table->text('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->string('province')->nullable();
            $table->string('pays')->nullable();
            $table->date('date_embauche')->nullable();
            $table->string('type_contrat')->nullable();
            $table->date('date_debut_contrat')->nullable();
            $table->date('date_fin_contrat')->nullable();
            $table->decimal('salaire_base', 14, 2)->nullable();
            $table->string('mode_paiement')->nullable();
            $table->string('numero_cnss')->nullable();
            $table->string('numero_nif')->nullable();
            $table->string('lieu_travail')->nullable();
            $table->string('temps_travail')->default('PLEIN_TEMPS')->index();
            $table->string('statut')->default('ACTIF')->index();
            $table->boolean('is_archived')->default(false)->index();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['departement_id', 'poste_id']);
            $table->index(['service_id', 'statut']);
            $table->index(['nom', 'prenom']);

            $table->foreign('user_id', 'employe_user_fk')
                ->references('id')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('departement_id', 'employe_departement_fk')
                ->references('id')->on('departements')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('poste_id', 'employe_poste_fk')
                ->references('id')->on('postes')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('service_id', 'employe_service_fk')
                ->references('id')->on('services')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('superieur_id', 'employe_superieur_fk')
                ->references('id')->on('employes')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('created_by', 'employe_created_by_fk')
                ->references('id')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('updated_by', 'employe_updated_by_fk')
                ->references('id')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employes');
    }
};
