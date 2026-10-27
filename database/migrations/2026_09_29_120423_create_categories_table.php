<?php

use App\Enums\Catalog\CategoryStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->index();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->unsignedTinyInteger('depth')->default(1)->index();
            $table->text('description')->nullable();
            $table->string('status')->default(CategoryStatusEnum::Draft->value)->index();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_featured')->default(false)->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['parent_id', 'slug', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
