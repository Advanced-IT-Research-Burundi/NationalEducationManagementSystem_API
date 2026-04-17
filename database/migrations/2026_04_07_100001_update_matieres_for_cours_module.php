<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            if (!Schema::hasColumn('matieres', 'categorie_cours_id')) {
                $table->foreignId('categorie_cours_id')->nullable()->after('code')
                    ->constrained('categories_cours')->nullOnDelete();
            }
            if (!Schema::hasColumn('matieres', 'est_principale')) {
                $table->boolean('est_principale')->default(false)->after('categorie_cours_id');
            }
            if (!Schema::hasColumn('matieres', 'ponderation_tj')) {
                $table->decimal('ponderation_tj', 5, 2)->default(0)->after('est_principale');
            }
            if (!Schema::hasColumn('matieres', 'ponderation_examen')) {
                $table->decimal('ponderation_examen', 5, 2)->default(0)->after('ponderation_tj');
            }
            if (!Schema::hasColumn('matieres', 'credit_heures')) {
                $table->integer('credit_heures')->default(0)->after('ponderation_examen');
            }
            if (!Schema::hasColumn('matieres', 'section_id')) {
                $table->foreignId('section_id')->nullable()->after('niveau_id')
                    ->constrained('sections')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            if (Schema::hasColumn('matieres', 'categorie_cours_id')) {
                $table->dropConstrainedForeignId('categorie_cours_id');
            }
            if (Schema::hasColumn('matieres', 'est_principale')) {
                $table->dropColumn('est_principale');
            }
            if (Schema::hasColumn('matieres', 'ponderation_tj')) {
                $table->dropColumn('ponderation_tj');
            }
            if (Schema::hasColumn('matieres', 'ponderation_examen')) {
                $table->dropColumn('ponderation_examen');
            }
            if (Schema::hasColumn('matieres', 'credit_heures')) {
                $table->dropColumn('credit_heures');
            }
            if (Schema::hasColumn('matieres', 'section_id')) {
                $table->dropConstrainedForeignId('section_id');
            }
        });
    }
};
