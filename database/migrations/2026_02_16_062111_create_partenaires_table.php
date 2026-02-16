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
        Schema::create('partenaires', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->enum('type', ['PTF', 'ONG', 'UNIVERSITE', 'ENTREPRISE', 'AUTRE'])->default('PTF');
            $table->string('pays')->nullable();
            $table->string('contact_nom')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_telephone')->nullable();
            $table->text('adresse')->nullable();
            $table->text('description')->nullable();
            $table->date('date_debut_partenariat')->nullable();
            $table->enum('statut', ['ACTIF', 'INACTIF', 'SUSPENDU'])->default('ACTIF');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partenaires');
    }
};
