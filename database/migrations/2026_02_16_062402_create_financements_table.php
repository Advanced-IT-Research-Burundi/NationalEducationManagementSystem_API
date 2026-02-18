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
        Schema::create('financements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_partenariat_id')->constrained('projets_partenariat')->onDelete('cascade');
            $table->decimal('montant', 15, 2)->comment('Montant en devise locale');
            $table->date('date_decaissement');
            $table->enum('type', ['INITIAL', 'TRANCHE', 'COMPLEMENT', 'AJUSTEMENT'])->default('TRANCHE');
            $table->string('reference_transaction')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financements');
    }
};
