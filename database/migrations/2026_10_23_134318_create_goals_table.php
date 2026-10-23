<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_goal_id')->nullable()->constrained('goals')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->text('done_criteria')->nullable();
            $table->string('type');
            $table->string('color');
            $table->string('priority');
            $table->date('start_date');
            $table->date('due_date');
            $table->unsignedTinyInteger('target_per_week')->nullable();
            $table->string('area')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
