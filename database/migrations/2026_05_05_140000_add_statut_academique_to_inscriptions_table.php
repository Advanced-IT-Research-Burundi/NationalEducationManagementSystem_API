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

        $indexKeys = $this->inscriptionIndexKeyNames();

        // Add index on statut_academique if not present
        $hasStatutIndex = in_array('inscriptions_statut_academique_index', $indexKeys, true);
        $indexExists = function (string $table, string $keyName): bool {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                $rows = DB::select('PRAGMA index_list('.$table.')');

                return collect($rows)->contains(fn ($row) => ($row->name ?? '') === $keyName);
            }

            return collect(DB::select('SHOW INDEX FROM '.$table))
                ->contains(fn ($row) => ($row->Key_name ?? '') === $keyName);
        };

        $hasStatutIndex = $indexExists('inscriptions', 'inscriptions_statut_academique_index');
        if (! $hasStatutIndex) {
            Schema::table('inscriptions', function (Blueprint $table) {
                $table->index('statut_academique');
            });
        }

        // Replace the unique constraint: (eleve_id, annee_scolaire_id) -> (eleve_id, annee_scolaire_id, school_id)

$hasOldUnique = $indexExists(
    'inscriptions',
    'inscriptions_eleve_id_annee_scolaire_id_unique'
);

if ($hasOldUnique) {
    // MySQL requires dropping the FK before dropping the unique index it relies on
    Schema::table('inscriptions', function (Blueprint $table) {
        $table->dropForeign(['eleve_id']);
    });
}

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

    /**
     * @return list<string>
     */
    private function inscriptionIndexKeyNames(): array
    {
        $connection = Schema::getConnection();
        $table = 'inscriptions';
        $prefixed = $connection->getTablePrefix().$table;

        return match ($connection->getDriverName()) {
            'mysql', 'mariadb' => collect(DB::select('SHOW INDEX FROM `'.$prefixed.'`'))
                ->pluck('Key_name')
                ->unique()
                ->values()
                ->all(),
            'sqlite' => collect(DB::select(
                'SELECT name FROM sqlite_master WHERE tbl_name = ? AND type = ?',
                [$prefixed, 'index']
            ))
                ->pluck('name')
                ->filter()
                ->values()
                ->all(),
            default => [],
        };
    }
};
