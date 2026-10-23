<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('completed_on');
            $table->unsignedTinyInteger('points');
            $table->timestamps();

            $table->unique(['task_id', 'completed_on']);
            $table->index(['user_id', 'completed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('completions');
    }
};
