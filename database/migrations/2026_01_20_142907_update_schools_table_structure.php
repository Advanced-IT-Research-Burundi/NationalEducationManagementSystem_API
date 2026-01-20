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
        Schema::table('schools', function (Blueprint $table) {
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
            
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['validated_by']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['validated_at', 'validated_by', 'created_by', 'statut', 'longitude', 'latitude', 'niveau', 'type_ecole']);
            
            $table->renameColumn('code_ecole', 'code');
        });
    }
};
