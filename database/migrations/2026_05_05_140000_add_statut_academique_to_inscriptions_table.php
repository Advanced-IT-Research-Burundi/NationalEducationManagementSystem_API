<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add statut_academique if it doesn't exist yet (idempotent for partial runs)
        if (! Schema::hasColumn('inscriptions', 'statut_academique')) {
            Schema::table('inscriptions', function (Blueprint $table) {
                $table->string('statut_academique', 20)->default('en_cours')->after('statut');
            });
        }

        // Add index on statut_academique if not present
        $hasStatutIndex = collect(DB::select('SHOW INDEX FROM inscriptions'))
            ->contains('Key_name', 'inscriptions_statut_academique_index');
        if (! $hasStatutIndex) {
            Schema::table('inscriptions', function (Blueprint $table) {
                $table->index('statut_academique');
            });
        }

        // Replace the unique constraint: (eleve_id, annee_scolaire_id) -> (eleve_id, annee_scolaire_id, school_id)
        $hasOldUnique = collect(DB::select('SHOW INDEX FROM inscriptions'))
            ->contains('Key_name', 'inscriptions_eleve_id_annee_scolaire_id_unique');

        if ($hasOldUnique) {
            // MySQL requires dropping the FK before dropping the unique index it relies on
            Schema::table('inscriptions', function (Blueprint $table) {
                $table->dropForeign(['eleve_id']);
            });

            Schema::table('inscriptions', function (Blueprint $table) {
                $table->dropUnique(['eleve_id', 'annee_scolaire_id']);
                $table->unique(['eleve_id', 'annee_scolaire_id', 'school_id'], 'inscriptions_eleve_annee_ecole_unique');
            });

            Schema::table('inscriptions', function (Blueprint $table) {
                $table->foreign('eleve_id')->references('id')->on('eleves')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropForeign(['eleve_id']);
        });

        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropUnique('inscriptions_eleve_annee_ecole_unique');
            $table->unique(['eleve_id', 'annee_scolaire_id']);
        });

        Schema::table('inscriptions', function (Blueprint $table) {
            $table->foreign('eleve_id')->references('id')->on('eleves')->cascadeOnDelete();
            $table->dropIndex(['statut_academique']);
            $table->dropColumn('statut_academique');
        });
    }
};
