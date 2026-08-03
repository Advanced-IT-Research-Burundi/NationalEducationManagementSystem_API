<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employe_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id');
            $table->string('type_document');
            $table->string('nom_original');
            $table->string('fichier_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('taille')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->index(['employe_id', 'type_document']);

            $table->foreign('employe_id', 'employe_documents_employe_fk')
                ->references('id')->on('employes')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('created_by', 'employe_documents_created_by_fk')
                ->references('id')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employe_documents');
    }
};
