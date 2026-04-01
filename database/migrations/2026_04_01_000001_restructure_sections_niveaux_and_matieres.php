<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            if (! Schema::hasColumn('sections', 'niveau_id')) {
                $table->foreignId('niveau_id')
                    ->nullable()
                    ->after('type_id')
                    ->constrained('niveaux_scolaires')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasColumn('niveaux_scolaires', 'section_id')) {
            DB::table('niveaux_scolaires')
                ->whereNotNull('section_id')
                ->orderBy('id')
                ->get(['id', 'section_id'])
                ->each(function ($niveau) {
                    DB::table('sections')
                        ->where('id', $niveau->section_id)
                        ->update(['niveau_id' => $niveau->id]);
                });

            Schema::table('niveaux_scolaires', function (Blueprint $table) {
                $table->dropForeign(['section_id']);
                $table->dropColumn('section_id');
            });
        }

        Schema::table('matieres', function (Blueprint $table) {
            if (! Schema::hasColumn('matieres', 'niveau_id')) {
                $table->foreignId('niveau_id')
                    ->nullable()
                    ->after('code')
                    ->constrained('niveaux_scolaires')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('niveaux_scolaires', 'section_id')) {
            Schema::table('niveaux_scolaires', function (Blueprint $table) {
                $table->foreignId('section_id')
                    ->nullable()
                    ->after('cycle_id')
                    ->constrained('sections');
            });
        }

        if (Schema::hasColumn('sections', 'niveau_id')) {
            DB::table('sections')
                ->whereNotNull('niveau_id')
                ->orderBy('id')
                ->get(['id', 'niveau_id'])
                ->each(function ($section) {
                    DB::table('niveaux_scolaires')
                        ->where('id', $section->niveau_id)
                        ->update(['section_id' => $section->id]);
                });

            Schema::table('sections', function (Blueprint $table) {
                $table->dropForeign(['niveau_id']);
                $table->dropColumn('niveau_id');
            });
        }

        if (Schema::hasColumn('matieres', 'niveau_id')) {
            Schema::table('matieres', function (Blueprint $table) {
                $table->dropForeign(['niveau_id']);
                $table->dropColumn('niveau_id');
            });
        }
    }
};
