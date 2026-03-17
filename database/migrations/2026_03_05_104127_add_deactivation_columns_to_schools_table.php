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
            $table->foreignId('deactivated_by')->after('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('deactivated_at')->after('deactivated_by')->nullable();
            $table->text('deactivation_reason')->after('deactivated_at')->nullable();
            
            $table->foreignId('reactivated_by')->after('deactivation_reason')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reactivated_at')->after('reactivated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropForeign(['deactivated_by']);
            $table->dropForeign(['reactivated_by']);
            $table->dropColumn([
                'deactivated_by',
                'deactivated_at',
                'deactivation_reason',
                'reactivated_by',
                'reactivated_at'
            ]);
        });
    }
};
