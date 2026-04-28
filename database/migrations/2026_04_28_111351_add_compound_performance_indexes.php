<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->index(['school_id', 'niveau_id'], 'classes_school_niveau_idx');
            });
        }

        if (Schema::hasTable('eleve_class')) {
            Schema::table('eleve_class', function (Blueprint $table) {
                $table->index(['classe_id', 'eleve_id'], 'eleve_class_classe_eleve_idx');
            });
        }

        if (Schema::hasTable('note_conduites')) {
            Schema::table('note_conduites', function (Blueprint $table) {
                $table->index(['classe_id', 'eleve_id', 'trimestre'], 'note_conduites_classe_eleve_trim_idx');
            });
        }

        if (Schema::hasTable('sanction_eleves') && Schema::hasColumn('sanction_eleves', 'eleve_id')) {
            Schema::table('sanction_eleves', function (Blueprint $table) {
                $table->index(['eleve_id', 'classe_id'], 'sanction_eleves_eleve_classe_idx');
            });
        }

        if (Schema::hasTable('evaluations')) {
            Schema::table('evaluations', function (Blueprint $table) {
                $table->index(['classe_id', 'annee_scolaire_id', 'trimestre'], 'evaluations_classe_annee_trim_idx');
            });
        }

        if (Schema::hasTable('notes') && Schema::hasColumn('notes', 'eleve_id')) {
            Schema::table('notes', function (Blueprint $table) {
                $table->index(['evaluation_id', 'eleve_id'], 'notes_evaluation_eleve_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('classes')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropIndex('classes_school_niveau_idx');
            });
        }

        if (Schema::hasTable('eleve_class')) {
            Schema::table('eleve_class', function (Blueprint $table) {
                $table->dropIndex('eleve_class_classe_eleve_idx');
            });
        }

        if (Schema::hasTable('note_conduites')) {
            Schema::table('note_conduites', function (Blueprint $table) {
                $table->dropIndex('note_conduites_classe_eleve_trim_idx');
            });
        }

        if (Schema::hasTable('sanction_eleves')) {
            Schema::table('sanction_eleves', function (Blueprint $table) {
                $table->dropIndex('sanction_eleves_eleve_classe_idx');
            });
        }

        if (Schema::hasTable('evaluations')) {
            Schema::table('evaluations', function (Blueprint $table) {
                $table->dropIndex('evaluations_classe_annee_trim_idx');
            });
        }

        if (Schema::hasTable('notes')) {
            Schema::table('notes', function (Blueprint $table) {
                $table->dropIndex('notes_evaluation_eleve_idx');
            });
        }
    }
};
