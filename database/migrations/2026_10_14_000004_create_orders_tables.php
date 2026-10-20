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
            $table->string('currency', 3);
            $table->string('status')->default(OrderStatusEnum::Pending->value)->index();
            $table->string('payment_status')->default(PaymentStatusEnum::Pending->value)->index();
            $table->string('contact_mode')->nullable();
            $table->timestamp('customer_contact_recorded_at')->nullable();
            $table->string('phone', 20);
            $table->text('shipping_address');
            $table->text('billing_address');
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
            $table->string('sku_snapshot', 64)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->unsignedTinyInteger('discount_percentage')->default(0);
            $table->string('offer_name_snapshot')->nullable();
            $table->unsignedTinyInteger('offer_discount_percentage')->default(0);
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 30)->index();
            $table->string('method', 30)->nullable();
            $table->string('status')->default(PaymentStatusEnum::Pending->value)->index();
            $table->unsignedInteger('amount');
            $table->string('currency', 3);
            $table->string('reference', 64)->unique();
            $table->string('transaction_id', 64)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'method', 'transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
