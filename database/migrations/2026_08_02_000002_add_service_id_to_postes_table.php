<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postes', function (Blueprint $table) {
            if (! Schema::hasColumn('postes', 'service_id')) {
                $table->foreignId('service_id')->nullable()->after('nom');
                $table->index(['service_id', 'statut'], 'postes_service_statut_idx');
                $table->foreign('service_id', 'poste_service_fk')
                    ->references('id')->on('services')
                    ->nullOnDelete()->cascadeOnUpdate();
            }
        });
    }

    public function down(): void
    {
        Schema::table('postes', function (Blueprint $table) {
            if (Schema::hasColumn('postes', 'service_id')) {
                $table->dropForeign('poste_service_fk');
                $table->dropIndex('postes_service_statut_idx');
                $table->dropColumn('service_id');
            }
        });
    }
};
