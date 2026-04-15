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
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (! Schema::hasColumn('roles', 'description')) {
                    $table->text('description')->nullable()->after('guard_name');
                }

                if (! Schema::hasColumn('roles', 'is_system')) {
                    $table->boolean('is_system')->default(false)->after('description');
                }

                if (! Schema::hasColumn('roles', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(0)->after('is_system');
                }
            });
        }

        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (! Schema::hasColumn('permissions', 'description')) {
                    $table->text('description')->nullable()->after('guard_name');
                }

                if (! Schema::hasColumn('permissions', 'group_name')) {
                    $table->string('group_name')->nullable()->after('description');
                }

                if (! Schema::hasColumn('permissions', 'is_system')) {
                    $table->boolean('is_system')->default(false)->after('group_name');
                }

                if (! Schema::hasColumn('permissions', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(0)->after('is_system');
                }
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'is_super_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_super_admin')->default(false)->after('statut');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                foreach (['sort_order', 'is_system', 'description'] as $column) {
                    if (Schema::hasColumn('roles', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                foreach (['sort_order', 'is_system', 'group_name', 'description'] as $column) {
                    if (Schema::hasColumn('permissions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_super_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_super_admin');
            });
        }
    }
};
