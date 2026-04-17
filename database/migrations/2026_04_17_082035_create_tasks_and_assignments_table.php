<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('todo');
            $table->date('due_date');
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->timestamps();
            
            $table->index(['status', 'due_date']);
        });

        Schema::create('task_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('task_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('logged_hours', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
        Schema::dropIfExists('tasks');
    }
};