<?php

use App\Enums\User\StaffCompensationTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('compensation_type')->default(StaffCompensationTypeEnum::Salary->value);
            $table->unsignedInteger('compensation_value')->default(0);
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedInteger('total_sales_snapshot')->nullable();
            $table->string('compensation_type_snapshot');
            $table->unsignedInteger('compensation_value_snapshot');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'period_month', 'period_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_payments');
        Schema::dropIfExists('staff_profiles');
    }
};
