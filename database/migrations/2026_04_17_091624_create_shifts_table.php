<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->date('date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('label');
            $table->timestamps();

            $table->index(['date', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};