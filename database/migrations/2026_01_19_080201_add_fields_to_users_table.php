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
            $table->string('statut')->default('actif');
            
            // Administrative Hierarchy
            $table->foreignId('pays_id')->nullable()->constrained('pays')->onDelete('set null');
            $table->foreignId('ministere_id')->nullable()->constrained('ministeres')->onDelete('set null');
            $table->foreignId('province_id')->nullable()->constrained('provinces')->onDelete('set null');
            $table->foreignId('commune_id')->nullable()->constrained('communes')->onDelete('set null');
            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null');
            $table->foreignId('colline_id')->nullable()->constrained('collines')->onDelete('set null');
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('set null');
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
            
            $table->dropColumn([
                'statut', 
                'pays_id', 
                'ministere_id', 
                'province_id', 
                'commune_id', 
                'zone_id', 
                'colline_id', 
                'school_id'
            ]);
        });
    }
};
