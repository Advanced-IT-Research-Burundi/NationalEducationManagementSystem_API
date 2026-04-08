<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories_cours', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->integer('ordre')->default(0);
            $table->boolean('afficher_bulletin')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories_cours');
    }
};
