<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('author')->nullable();
            $table->unsignedInteger('pages')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
