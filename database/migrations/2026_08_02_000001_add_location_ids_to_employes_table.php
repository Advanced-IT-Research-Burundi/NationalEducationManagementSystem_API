<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->foreignId('pays_id')->nullable()->after('pays')->constrained('pays')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->after('pays_id')->constrained('provinces')->nullOnDelete();
            $table->foreignId('commune_id')->nullable()->after('province_id')->constrained('communes')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->after('commune_id')->constrained('zones')->nullOnDelete();
            $table->foreignId('colline_id')->nullable()->after('zone_id')->constrained('collines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('colline_id');
            $table->dropConstrainedForeignId('zone_id');
            $table->dropConstrainedForeignId('commune_id');
            $table->dropConstrainedForeignId('province_id');
            $table->dropConstrainedForeignId('pays_id');
        });
    }
};
