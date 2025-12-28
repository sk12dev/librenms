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
        Schema::create('enhanced_dns_servers', function (Blueprint $table) {
            // Primary key
            $table->id('dns_server_id');
            
            // DNS server IP address - unique
            $table->string('dns_server', 45)->unique()->comment('DNS server IP address');
            
            // Optional: Description/name for the DNS server
            $table->string('description', 255)->nullable()->comment('Description or name for this DNS server');
            
            // Status tracking
            $table->boolean('enabled')->default(1)->index()->comment('Should this DNS server be used for checks');
            
            // Optional: Priority/order for DNS servers (lower number = higher priority)
            $table->integer('priority')->default(0)->index()->comment('Priority/order for DNS servers');
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Index for enabled servers ordered by priority
            $table->index(['enabled', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enhanced_dns_servers');
    }
};

