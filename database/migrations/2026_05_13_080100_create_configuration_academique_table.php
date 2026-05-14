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
        Schema::create('configuration_academique', function (Blueprint $table) {
            $table->id();
            $table->foreignId('current_annee_scolaire_id')->nullable()->constrained('annee_scolaires')->nullOnDelete();
            $table->foreignId('current_trimestre_id')->nullable()->constrained('trimestres')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuration_academique');
    }
};
