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
        Schema::create('provision_logs', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->unsignedBigInteger('provision_id');
            
            // Log Info
            $table->string('action', 50);
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            
            // User Tracking
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Additional Info
            $table->text('notes')->nullable();
            $table->string('source', 50)->default('system'); // system, api, manual
            
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('provision_id')->references('id')->on('service_provisions')
                  ->onDelete('cascade');
            $table->foreign('performed_by')->references('id')->on('users');
            
            // Indexes
            $table->index('provision_id');
            $table->index('action');
            $table->index('performed_by');
            $table->index(['provision_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provision_logs');
    }
};
