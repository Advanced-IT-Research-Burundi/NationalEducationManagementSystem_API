<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'departement_id')) {
                $table->foreignId('departement_id')->nullable()->after('code');
            }
            if (! Schema::hasColumn('services', 'responsable_id')) {
                $table->foreignId('responsable_id')->nullable()->after('departement_id');
            }
            if (! Schema::hasColumn('services', 'telephone')) {
                $table->string('telephone', 30)->nullable()->after('description');
            }
            if (! Schema::hasColumn('services', 'email')) {
                $table->string('email')->nullable()->after('telephone');
            }
            if (! Schema::hasColumn('services', 'bureau')) {
                $table->string('bureau')->nullable()->after('email');
            }
            if (! Schema::hasColumn('services', 'statut')) {
                $table->string('statut')->default('ACTIF')->index()->after('bureau');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            $table->index(['departement_id', 'statut'], 'services_departement_statut_idx');
            $table->index(['nom', 'statut'], 'services_nom_statut_idx');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->foreign('departement_id', 'services_departement_fk')
                ->references('id')->on('departements')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('responsable_id', 'services_responsable_fk')
                ->references('id')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign('services_departement_fk');
            $table->dropForeign('services_responsable_fk');
            $table->dropIndex('services_departement_statut_idx');
            $table->dropIndex('services_nom_statut_idx');
            $table->dropColumn([
                'departement_id',
                'responsable_id',
                'telephone',
                'email',
                'bureau',
                'statut',
            ]);
        });
    }
};
