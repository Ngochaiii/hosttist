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
        Schema::create('provision_templates', function (Blueprint $table) {
            $table->id();
            
            // Template Info
            $table->string('provision_type', 50);
            $table->string('name', 255);
            $table->json('fields'); // Definition của fields cần provision
            $table->boolean('is_default')->default(false);
            
            // Metadata
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('provision_type');
            $table->index(['provision_type', 'is_default']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provision_templates');
    }
};
