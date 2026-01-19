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
        Schema::create('collines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->foreignId('commune_id')->nullable()->constrained('communes')->onDelete('cascade');
            $table->foreignId('province_id')->nullable()->constrained('provinces')->onDelete('cascade');
            $table->foreignId('ministere_id')->nullable()->constrained('ministeres')->onDelete('cascade');
            $table->foreignId('pays_id')->nullable()->constrained('pays')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collines');
    }
};
