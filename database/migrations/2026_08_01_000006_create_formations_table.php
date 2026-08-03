<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id');
            $table->string('intitule');
            $table->string('organisme')->nullable();
            $table->string('type')->default('FORMATION')->index();
            $table->string('domaine')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->string('duree')->nullable();
            $table->string('lieu')->nullable();
            $table->decimal('cout', 14, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('resultat')->nullable();
            $table->boolean('est_certifie')->default(false)->index();
            $table->string('numero_certificat')->nullable();
            $table->date('date_obtention')->nullable();
            $table->date('date_expiration')->nullable();
            $table->string('piece_jointe')->nullable();
            $table->string('presence')->default('PRESENT')->index();
            $table->decimal('taux_presence', 5, 2)->nullable();
            $table->text('commentaire')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employe_id', 'date_debut']);
            $table->index(['employe_id', 'est_certifie']);

            $table->foreign('employe_id', 'formation_employe_fk')
                ->references('id')->on('employes')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('created_by', 'formation_created_by_fk')
                ->references('id')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formations');
    }
};
