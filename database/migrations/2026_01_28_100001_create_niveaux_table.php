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
        Schema::create('niveaux_scolaires', function (Blueprint $table) {
            $table->id();
            $table->string('nom'); // 7ème, 8ème, 9ème, etc.
            $table->string('code')->unique(); // 7, 8, 9, etc.
            $table->integer('ordre')->default(0); // Pour le tri
            $table->enum('cycle', ['FONDAMENTAL', 'POST_FONDAMENTAL']);
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('niveaux_scolaires');
    }
};
