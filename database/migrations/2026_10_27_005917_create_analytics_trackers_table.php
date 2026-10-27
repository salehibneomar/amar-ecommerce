<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_trackers', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique();
            $table->string('tracking_id', 32)->nullable();
            $table->boolean('is_enabled')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_trackers');
    }
};
