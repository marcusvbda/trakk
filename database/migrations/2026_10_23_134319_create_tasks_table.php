<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->string('status')->default('todo');
            $table->unsignedTinyInteger('points')->default(1);
            $table->date('due_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->jsonb('days_of_week')->nullable();
            $table->string('tag')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();

            $table->index(['goal_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
