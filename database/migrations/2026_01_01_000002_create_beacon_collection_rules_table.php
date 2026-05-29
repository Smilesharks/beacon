<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beacon_collection_rules', function (Blueprint $table) {
            $table->id();
            $table->string('collection_handle');
            $table->foreignId('notification_id')
                ->nullable()
                ->constrained('beacon_notifications')
                ->nullOnDelete();
            $table->string('url_pattern')->nullable();
            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beacon_collection_rules');
    }
};
