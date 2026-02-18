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
        Schema::create('eleves', function (Blueprint $table) {
            $table->id();
            $table->string('matricule', 20)->unique();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->enum('sexe', ['M', 'F']);
            $table->date('date_naissance');
            $table->string('lieu_naissance', 150);
            $table->string('nationalite', 50)->default('Burundaise');
            $table->foreignId('colline_origine_id')
                ->nullable()
                ->constrained('collines')
                ->nullOnDelete();
            $table->text('adresse')->nullable();
            $table->string('nom_pere', 200)->nullable();
            $table->string('nom_mere', 200)->nullable();
            $table->string('contact_tuteur', 20)->nullable();
            $table->string('nom_tuteur', 200)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->boolean('est_orphelin')->default(false);
            $table->boolean('a_handicap')->default(false);
            $table->string('type_handicap', 100)->nullable();
            $table->foreignId('ecole_origine_id')
                ->nullable()
                ->constrained('ecoles')
                ->nullOnDelete();
            $table->foreignId('school_id')
                ->nullable()
                ->constrained('ecoles')
                ->nullOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->enum('statut_global', [
                'actif', 'inactif', 'transfere', 'abandonne', 'decede'
            ])->default('actif');
            $table->softDeletes();
            $table->timestamps();

            $table->index('matricule');
            $table->index(['nom', 'prenom']);
            $table->index('statut_global');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eleves');
    }
};
