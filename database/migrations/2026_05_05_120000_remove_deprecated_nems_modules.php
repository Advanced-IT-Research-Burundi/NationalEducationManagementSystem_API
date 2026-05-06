<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supprime les modules : campagnes d'inscription, collecte de données,
 * pédagogie (inspections, standards, formations), examens nationaux.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('inscriptions') && Schema::hasColumn('inscriptions', 'campagne_id')) {
            Schema::table('inscriptions', function (Blueprint $table) {
                $table->dropForeign(['campagne_id']);
            });
            Schema::table('inscriptions', function (Blueprint $table) {
                $table->dropColumn('campagne_id');
            });
        }

        foreach ([
            'reponses_collecte',
            'formulaires_collecte',
            'campagnes_collecte',
            'certificats',
            'resultats',
            'inscriptions_examen',
            'centres_examen',
            'sessions_examen',
            'examens',
            'formation_eleve_participants',
            'participants_formation',
            'formations',
            'inspections',
            'standards_qualite',
            'campagnes_inscription',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Irréversible : les modules ont été retirés du code applicatif.
    }
};
