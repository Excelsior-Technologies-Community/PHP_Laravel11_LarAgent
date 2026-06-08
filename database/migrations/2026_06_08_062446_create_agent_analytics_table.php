<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('agent_analytics', function (Blueprint $table) {
    $table->id();
    $table->string('agent_name');
    $table->integer('tokens_used');
    $table->float('cost')->default(0);
    $table->string('model_used');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_analytics');
    }
};
