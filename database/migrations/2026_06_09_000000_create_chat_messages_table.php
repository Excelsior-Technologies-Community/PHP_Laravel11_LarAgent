<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
            $table->string('status')->default('pending');
            $table->boolean('is_approved')->default(false);
            $table->integer('token_usage')->nullable();
            $table->string('model_name')->nullable();
            $table->timestamps();
        });

        Schema::create('human_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')->constrained('chat_messages')->onDelete('cascade');
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('human_approvals');
        Schema::dropIfExists('chat_messages');
    }
};