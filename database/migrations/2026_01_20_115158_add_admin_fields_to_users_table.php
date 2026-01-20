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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('admin_level', [
                'PAYS',
                'MINISTERE',
                'PROVINCE',
                'COMMUNE',
                'ZONE',
                'ECOLE'
            ])->nullable()->after('statut');
            $table->bigInteger('admin_entity_id')->unsigned()->nullable()->after('admin_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['admin_level', 'admin_entity_id']);
        });
    }
};
