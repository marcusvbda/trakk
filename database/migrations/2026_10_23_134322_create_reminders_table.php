<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goal_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('label');
            $table->time('time');
            $table->jsonb('days_of_week')->nullable();
            $table->date('fire_on')->nullable();
            $table->jsonb('channels');
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
