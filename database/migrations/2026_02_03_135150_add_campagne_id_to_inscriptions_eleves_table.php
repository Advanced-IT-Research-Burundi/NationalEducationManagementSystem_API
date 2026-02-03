<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inscriptions_eleves', function (Blueprint $table) {
            $table->foreignId('campagne_id')
                ->nullable()
                ->after('annee_scolaire_id')
                ->constrained('campagnes_inscription')
                ->onDelete('set null');

            // Add statut workflow columns if not exist
            if (! Schema::hasColumn('inscriptions_eleves', 'date_soumission')) {
                $table->timestamp('date_soumission')->nullable();
            }
            if (! Schema::hasColumn('inscriptions_eleves', 'date_validation')) {
                $table->timestamp('date_validation')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscriptions_eleves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campagne_id');
        });
    }
};
