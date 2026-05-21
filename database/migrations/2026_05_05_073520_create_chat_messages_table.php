<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_message');
                $table->text('ai_response');
                $table->string('provider')->nullable();
                $table->string('model')->nullable();
                $table->integer('response_time_ms')->nullable();
                $table->boolean('used_tool')->default(false);
                $table->string('tool_name')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};