<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('matieres', 'ponderation_competence')) {
            Schema::table('matieres', function (Blueprint $table) {
                $table->decimal('ponderation_competence', 5, 2)->default(0)->after('ponderation_examen');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('matieres', 'ponderation_competence')) {
            Schema::table('matieres', function (Blueprint $table) {
                $table->dropColumn('ponderation_competence');
            });
        }
    }
};
