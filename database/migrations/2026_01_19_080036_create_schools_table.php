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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->foreignId('colline_id')->constrained('collines')->onDelete('cascade');
            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('cascade');
            $table->foreignId('commune_id')->nullable()->constrained('communes')->onDelete('cascade');
            $table->foreignId('province_id')->nullable()->constrained('provinces')->onDelete('cascade');
            $table->foreignId('ministere_id')->nullable()->constrained('ministeres')->onDelete('cascade');
            $table->foreignId('pays_id')->nullable()->constrained('pays')->onDelete('cascade');
             // Renaming code to code_ecole if possible, otherwise adding code_ecole
            if (Schema::hasColumn('schools', 'code')) {
                 $table->renameColumn('code', 'code_ecole');
            } else {
                 $table->string('code_ecole')->nullable()->unique();
            }

            $table->enum('type_ecole', ['PUBLIQUE', 'PRIVEE', 'ECC', 'AUTRE'])->after('name')->nullable();
            $table->enum('niveau', ['FONDAMENTAL', 'POST_FONDAMENTAL', 'SECONDAIRE', 'SUPERIEUR'])->after('type_ecole')->nullable();

            $table->decimal('latitude', 10, 8)->nullable()->after('zone_id');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');

            $table->enum('statut', ['BROUILLON', 'EN_ATTENTE_VALIDATION', 'ACTIVE', 'INACTIVE'])->default('BROUILLON')->after('longitude');

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
