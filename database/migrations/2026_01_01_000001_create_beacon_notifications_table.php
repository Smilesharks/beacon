<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beacon_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('handle')->unique();
            $table->string('type', 20);
            $table->boolean('enabled')->default(false);
            $table->string('position')->default('bottom-right');
            $table->string('trigger')->default('immediate');
            $table->string('trigger_value')->nullable();
            $table->string('frequency')->default('session');
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beacon_notifications');
    }
};
