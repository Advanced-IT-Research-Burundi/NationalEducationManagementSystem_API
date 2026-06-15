<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            if (!Schema::hasColumn('matieres', 'ordre')) {
                $table->unsignedInteger('ordre')->default(0)->after('code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            if (Schema::hasColumn('matieres', 'ordre')) {
                $table->dropColumn('ordre');
            }
        });
    }
};
