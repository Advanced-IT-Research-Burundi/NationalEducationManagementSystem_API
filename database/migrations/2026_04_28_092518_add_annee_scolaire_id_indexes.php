<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that need an index on annee_scolaire_id.
     * Tables that already have a composite index including this column
     * (classes, inscriptions, mouvements_eleve, campagnes_inscription)
     * are excluded.
     *
     * @var array<string>
     */
    private array $tables = [
        'evaluations',
        'note_conduites',
        'sanction_eleves',
        'affectations_matieres',
        'examens',
        'campagnes_collecte',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'annee_scolaire_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->index('annee_scolaire_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'annee_scolaire_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropIndex(['annee_scolaire_id']);
                });
            }
        }
    }
};
