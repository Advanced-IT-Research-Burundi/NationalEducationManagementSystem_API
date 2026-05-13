<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TRIMESTRE_NAMES = ['1er Trimestre', '2e Trimestre', '3e Trimestre'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreignId('trimestre_id')->nullable()->after('annee_scolaire_id')->constrained('trimestres')->nullOnDelete();
            $table->index(['classe_id', 'annee_scolaire_id', 'trimestre_id'], 'evaluations_classe_annee_trimestre_id_idx');
        });

        Schema::table('note_conduites', function (Blueprint $table) {
            $table->foreignId('trimestre_id')->nullable()->after('annee_scolaire_id')->constrained('trimestres')->nullOnDelete();
            $table->index(['classe_id', 'annee_scolaire_id', 'trimestre_id'], 'note_conduites_classe_annee_trimestre_id_idx');
        });

        Schema::table('sanction_eleves', function (Blueprint $table) {
            $table->foreignId('trimestre_id')->nullable()->after('annee_scolaire_id')->constrained('trimestres')->nullOnDelete();
            $table->index(['classe_id', 'annee_scolaire_id', 'trimestre_id'], 'sanction_eleves_classe_annee_trimestre_id_idx');
        });

        $yearIds = DB::table('annee_scolaires')->pluck('id');

        foreach ($yearIds as $yearId) {
            foreach (self::TRIMESTRE_NAMES as $name) {
                DB::table('trimestres')->updateOrInsert(
                    [
                        'annee_scolaire_id' => $yearId,
                        'nom' => $name,
                    ],
                    [
                        'date_debut' => null,
                        'date_fin' => null,
                        'actif' => false,
                        'verrouille' => false,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        DB::statement('
            UPDATE evaluations e
            INNER JOIN trimestres t
                ON t.annee_scolaire_id = e.annee_scolaire_id
               AND t.nom = e.trimestre
            SET e.trimestre_id = t.id
            WHERE e.trimestre IS NOT NULL
        ');

        DB::statement('
            UPDATE note_conduites nc
            INNER JOIN trimestres t
                ON t.annee_scolaire_id = nc.annee_scolaire_id
               AND t.nom = nc.trimestre
            SET nc.trimestre_id = t.id
            WHERE nc.trimestre IS NOT NULL
        ');

        DB::statement('
            UPDATE sanction_eleves se
            INNER JOIN trimestres t
                ON t.annee_scolaire_id = se.annee_scolaire_id
               AND t.nom = se.trimestre
            SET se.trimestre_id = t.id
            WHERE se.trimestre IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sanction_eleves', function (Blueprint $table) {
            $table->dropIndex('sanction_eleves_classe_annee_trimestre_id_idx');
            $table->dropConstrainedForeignId('trimestre_id');
        });

        Schema::table('note_conduites', function (Blueprint $table) {
            $table->dropIndex('note_conduites_classe_annee_trimestre_id_idx');
            $table->dropConstrainedForeignId('trimestre_id');
        });

        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropIndex('evaluations_classe_annee_trimestre_id_idx');
            $table->dropConstrainedForeignId('trimestre_id');
        });
    }
};
