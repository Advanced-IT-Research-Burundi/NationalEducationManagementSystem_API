<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_administratif_mouvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_administratif_id');
            $table->foreignId('ancien_service_id')->nullable();
            $table->foreignId('nouveau_service_id')->nullable();
            $table->foreignId('ancienne_fonction_id')->nullable();
            $table->foreignId('nouvelle_fonction_id')->nullable();
            $table->string('type_mouvement');
            $table->date('date_mouvement');
            $table->text('motif')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->index('type_mouvement', 'pam_type_idx');
            $table->index('date_mouvement', 'pam_date_idx');
            $table->index(['personnel_administratif_id', 'type_mouvement'], 'pam_personnel_type_idx');

            $table->foreign('personnel_administratif_id', 'pam_personnel_fk')
                ->references('id')->on('personnel_administratifs')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('ancien_service_id', 'pam_old_service_fk')
                ->references('id')->on('services')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('nouveau_service_id', 'pam_new_service_fk')
                ->references('id')->on('services')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('ancienne_fonction_id', 'pam_old_fonction_fk')
                ->references('id')->on('fonctions')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('nouvelle_fonction_id', 'pam_new_fonction_fk')
                ->references('id')->on('fonctions')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('created_by', 'pam_created_by_fk')
                ->references('id')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_administratif_mouvements');
    }
};
