<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('certificats');
        Schema::dropIfExists('resultats');
        Schema::dropIfExists('inscriptions_examen');
        Schema::dropIfExists('centres_examen');
        Schema::dropIfExists('sessions_examen');
        Schema::dropIfExists('examens');

        Schema::create('examens', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('code')->unique();
            $blueprint->string('libelle');
            $blueprint->foreignId('niveau_id')->constrained('niveaux_scolaires');
            $blueprint->foreignId('annee_scolaire_id')->constrained('annee_scolaires');
            $blueprint->enum('type', ['national', 'provincial']);
            $blueprint->timestamps();
        });

        Schema::create('sessions_examen', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('examen_id')->constrained('examens')->onDelete('cascade');
            $blueprint->date('date_debut');
            $blueprint->date('date_fin');
            $blueprint->enum('statut', ['planifiee', 'ouverte', 'cloturee', 'annulee'])->default('planifiee');
            $blueprint->timestamps();
        });

        Schema::create('centres_examen', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('ecole_id')->constrained('ecoles');
            $blueprint->foreignId('session_id')->constrained('sessions_examen')->onDelete('cascade');
            $blueprint->integer('capacite');
            $blueprint->foreignId('responsable_id')->constrained('users');
            $blueprint->timestamps();
        });

        Schema::create('inscriptions_examen', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('eleve_id')->constrained('eleves');
            $blueprint->foreignId('session_id')->constrained('sessions_examen')->onDelete('cascade');
            $blueprint->foreignId('centre_id')->nullable()->constrained('centres_examen');
            $blueprint->string('numero_anonymat')->nullable()->unique();
            $blueprint->enum('statut', ['inscrit', 'present', 'absent', 'exclu'])->default('inscrit');
            $blueprint->timestamps();
        });

        Schema::create('resultats', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('inscription_examen_id')->constrained('inscriptions_examen')->onDelete('cascade');
            $blueprint->string('matiere');
            $blueprint->decimal('note', 5, 2);
            $blueprint->string('mention')->nullable();
            $blueprint->text('deliberation')->nullable();
            $blueprint->timestamps();
        });

        Schema::create('certificats', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('inscription_examen_id')->constrained('inscriptions_examen')->onDelete('cascade');
            $blueprint->string('numero_unique')->unique();
            $blueprint->date('date_emission');
            $blueprint->text('qr_code')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificats');
        Schema::dropIfExists('resultats');
        Schema::dropIfExists('inscriptions_examen');
        Schema::dropIfExists('centres_examen');
        Schema::dropIfExists('sessions_examen');
        Schema::dropIfExists('examens');
    }
};
