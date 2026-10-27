<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('maintenance_enabled')->default(false);
            $table->timestamp('maintenance_starts_at')->nullable();
            $table->timestamp('maintenance_ends_at')->nullable();
            $table->string('maintenance_secret')->nullable();
            $table->string('theme_color', 32)->default('default');
            $table->boolean('seo_indexing_enabled')->default(true);
            $table->string('google_site_verification')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
