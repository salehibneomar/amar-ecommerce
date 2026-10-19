<?php

use App\Enums\Catalog\ProductStatusEnum;
use App\Enums\Catalog\StockTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->index();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->unsignedTinyInteger('discount_percentage')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('status')->default(ProductStatusEnum::Archived->value)->index();
            $table->string('stock_type')->default(StockTypeEnum::Digital->value);
            $table->string('badge')->nullable();
            $table->boolean('is_free')->default(false)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('type', 30)->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
