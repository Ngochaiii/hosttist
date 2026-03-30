<?php

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
        Schema::create('customer_services', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('provision_id')->constrained('service_provisions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('order_item_id')->constrained('order_items');

            // Lifecycle
            $table->enum('status', ['active', 'expired', 'suspended', 'cancelled'])->default('active');
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('next_renewal_date')->nullable();

            // Billing
            $table->boolean('auto_renew')->default(false);
            $table->decimal('renewal_price', 15, 2)->nullable();
            $table->string('billing_cycle', 20)->default('yearly'); // monthly | yearly

            // Notifications — tránh gửi email nhắc trùng
            $table->timestamp('notified_30d_at')->nullable();
            $table->timestamp('notified_15d_at')->nullable();
            $table->timestamp('notified_7d_at')->nullable();
            $table->timestamp('notified_1d_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('status');
            $table->index('expires_at');
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_services');
    }
};
