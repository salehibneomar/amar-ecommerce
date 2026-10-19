<?php

use App\Enums\Content\OfferTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('headline');
            $table->string('type')->default(OfferTypeEnum::Global->value)->index();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('discount_percentage');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('offerables', function (Blueprint $table) {
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->morphs('offerable');
            $table->primary(['offer_id', 'offerable_type', 'offerable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offerables');
        Schema::dropIfExists('offers');
    }
};
