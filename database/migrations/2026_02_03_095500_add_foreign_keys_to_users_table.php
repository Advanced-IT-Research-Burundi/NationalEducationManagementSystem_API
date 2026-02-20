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
            $table->foreign('pays_id')->references('id')->on('pays')->onDelete('set null');
            $table->foreign('ministere_id')->references('id')->on('ministeres')->onDelete('set null');
            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('set null');
            $table->foreign('commune_id')->references('id')->on('communes')->onDelete('set null');
            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('set null');
            $table->foreign('colline_id')->references('id')->on('collines')->onDelete('set null');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['pays_id']);
            $table->dropForeign(['ministere_id']);
            $table->dropForeign(['province_id']);
            $table->dropForeign(['commune_id']);
            $table->dropForeign(['zone_id']);
            $table->dropForeign(['colline_id']);
            $table->dropForeign(['school_id']);
        });
    }
};
