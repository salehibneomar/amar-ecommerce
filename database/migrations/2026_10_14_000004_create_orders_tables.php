<?php

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('public_order_id', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('customer_name');
            $table->string('customer_email')->index();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount_amount')->default(0);
            $table->json('applied_offers')->nullable();
            $table->unsignedTinyInteger('extra_discount_percentage')->default(0);
            $table->unsignedInteger('extra_discount_amount')->default(0);
            $table->unsignedInteger('total');
            $table->string('status')->default(OrderStatusEnum::Requested->value)->index();
            $table->string('payment_status')->default(PaymentStatusEnum::Pending->value)->index();
            $table->string('payment_method')->nullable();
            $table->string('transaction_id', 64)->nullable();
            $table->string('contact_mode')->nullable();
            $table->timestamp('customer_contact_recorded_at')->nullable();
            $table->string('phone', 20);
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->unsignedTinyInteger('discount_percentage')->default(0);
            $table->string('offer_name_snapshot')->nullable();
            $table->unsignedTinyInteger('offer_discount_percentage')->default(0);
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
