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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->morphs('maintenable'); // Can be equipement or batiment
            $table->enum('type', ['PREVENTIVE', 'CORRECTIVE', 'URGENCE'])->default('CORRECTIVE');
            $table->text('description');
            $table->date('date_demande');
            $table->date('date_intervention')->nullable();
            $table->date('date_fin')->nullable();
            $table->decimal('cout', 12, 2)->nullable()->comment('Coût en devise locale');
            $table->enum('statut', ['DEMANDE', 'EN_COURS', 'TERMINE', 'ANNULE'])->default('DEMANDE');
            $table->text('rapport')->nullable();
            $table->foreignId('demandeur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('technicien_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
