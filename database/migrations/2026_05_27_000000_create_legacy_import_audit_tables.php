<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 100)->default('lysedb');
            $table->string('status', 30)->default('running');
            $table->json('options')->nullable();
            $table->json('stats')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_import_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legacy_import_run_id')->nullable()->constrained('legacy_import_runs')->nullOnDelete();
            $table->string('source', 100)->default('lysedb');
            $table->string('source_table', 100);
            $table->string('source_key', 120);
            $table->string('source_context', 120)->default('');
            $table->string('target_table', 100);
            $table->unsignedBigInteger('target_id');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['source', 'source_table', 'source_key', 'source_context', 'target_table'],
                'legacy_maps_source_unique'
            );
            $table->index(['target_table', 'target_id'], 'legacy_maps_target_idx');
        });

        Schema::create('legacy_import_rejections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legacy_import_run_id')->nullable()->constrained('legacy_import_runs')->nullOnDelete();
            $table->string('source', 100)->default('lysedb');
            $table->string('source_table', 100);
            $table->string('source_key', 120)->nullable();
            $table->string('reason', 255);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['source_table', 'source_key'], 'legacy_rejections_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_import_rejections');
        Schema::dropIfExists('legacy_import_maps');
        Schema::dropIfExists('legacy_import_runs');
    }
};
