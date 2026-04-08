<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            $table->foreignId('categorie_cours_id')->nullable()->after('code')
                ->constrained('categories_cours')->nullOnDelete();
            $table->boolean('est_principale')->default(false)->after('categorie_cours_id');
            $table->decimal('ponderation_tj', 5, 2)->default(0)->after('est_principale');
            $table->decimal('ponderation_examen', 5, 2)->default(0)->after('ponderation_tj');
            $table->decimal('credit_heures', 5, 2)->default(0)->after('ponderation_examen');
            $table->foreignId('enseignant_id')->nullable()->after('credit_heures')
                ->constrained('enseignants')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->after('enseignant_id')
                ->constrained('sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categorie_cours_id');
            $table->dropColumn('est_principale');
            $table->dropColumn('ponderation_tj');
            $table->dropColumn('ponderation_examen');
            $table->dropColumn('credit_heures');
            $table->dropConstrainedForeignId('enseignant_id');
            $table->dropConstrainedForeignId('section_id');
        });
    }
};
