<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up()
    {
        Schema::create('service_provisions', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->unsignedBigInteger('order_item_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('customer_id');
            
            // Provision Info
            $table->string('provision_type', 50);
            $table->enum('provision_status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])
                  ->default('pending');
            $table->json('provision_data')->nullable();
            $table->integer('priority')->default(5);
            $table->timestamp('estimated_completion')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Tracking
            $table->unsignedBigInteger('provisioned_by')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->text('provision_notes')->nullable();
            
            // Delivery
            $table->enum('delivery_status', ['pending', 'sent', 'viewed'])
                  ->default('pending');
            $table->set('delivery_method', ['email', 'dashboard'])
                  ->default('dashboard');
            $table->timestamp('delivered_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            
            // Metadata
            $table->string('external_id')->nullable();
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('order_item_id')->references('id')->on('order_items');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('provisioned_by')->references('id')->on('users');
            
            // Indexes
            $table->index('provision_status');
            $table->index('customer_id');
            $table->index('provision_type');
            $table->index('delivery_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provisions');
    }
};
