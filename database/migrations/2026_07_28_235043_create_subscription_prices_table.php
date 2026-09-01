<?php

use App\Enums\PriceInterval;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->decimal('price', 12, 2)->default(0.00);
            $table->enum(
                'interval',
                array_column(PriceInterval::cases(), 'value')
            );
            $table->boolean('has_trial')->default(false);
            $table->unsignedInteger('trial_days')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_active')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_price');
    }
};
