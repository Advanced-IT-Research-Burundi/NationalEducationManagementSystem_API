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
        Schema::create('carrieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants')->onDelete('cascade');
            $table->string('poste');
            $table->foreignId('ecole_id')->nullable()->constrained('ecoles')->onDelete('set null');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->enum('motif_fin', ['MUTATION', 'PROMOTION', 'RETRAITE', 'DEMISSION', 'AUTRE'])->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carrieres');
    }
};
