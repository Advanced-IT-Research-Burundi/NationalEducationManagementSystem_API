<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eleves', function (Blueprint $table) {
            $table->dropForeign(['ecole_origine_id']);
            $table->dropColumn('ecole_origine_id');

            $table->foreignId('province_origine_id')
                ->nullable()
                ->after('colline_origine_id')
                ->constrained('provinces')
                ->nullOnDelete();

            $table->foreignId('commune_origine_id')
                ->nullable()
                ->after('province_origine_id')
                ->constrained('communes')
                ->nullOnDelete();

            $table->foreignId('zone_origine_id')
                ->nullable()
                ->after('commune_origine_id')
                ->constrained('zones')
                ->nullOnDelete();

            $table->foreignId('niveau_id')
                ->nullable()
                ->after('colline_origine_id')
                ->constrained('niveaux_scolaires')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eleves', function (Blueprint $table) {
            $table->dropForeign(['province_origine_id']);
            $table->dropColumn('province_origine_id');

            $table->dropForeign(['commune_origine_id']);
            $table->dropColumn('commune_origine_id');

            $table->dropForeign(['zone_origine_id']);
            $table->dropColumn('zone_origine_id');

            $table->dropForeign(['niveau_id']);
            $table->dropColumn('niveau_id');

            $table->foreignId('ecole_origine_id')
                ->nullable()
                ->after('type_handicap')
                ->constrained('schools')
                ->nullOnDelete();
        });
    }
};
