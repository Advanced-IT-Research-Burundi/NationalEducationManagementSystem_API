<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id(); // équivalent à bigIncrements
            $table->string('log_name')->nullable();
            $table->text('description');

            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_type')->nullable();

            $table->unsignedBigInteger('causer_id')->nullable();
            $table->string('causer_type')->nullable();

            $table->json('properties')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_log');
    }
}
