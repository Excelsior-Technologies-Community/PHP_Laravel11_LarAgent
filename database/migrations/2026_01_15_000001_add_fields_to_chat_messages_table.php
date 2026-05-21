<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_messages', 'session_id')) {
                $table->string('session_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('chat_messages', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('session_id');
            }
            if (!Schema::hasColumn('chat_messages', 'response_time_ms')) {
                $table->integer('response_time_ms')->nullable()->after('model');
            }
            if (!Schema::hasColumn('chat_messages', 'used_tool')) {
                $table->boolean('used_tool')->default(false)->after('response_time_ms');
            }
            if (!Schema::hasColumn('chat_messages', 'tool_name')) {
                $table->string('tool_name')->nullable()->after('used_tool');
            }
            if (!Schema::hasColumn('chat_messages', 'metadata')) {
                $table->json('metadata')->nullable()->after('tool_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn([
                'session_id', 
                'ip_address', 
                'response_time_ms', 
                'used_tool', 
                'tool_name', 
                'metadata'
            ]);
        });
    }
};