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
        Schema::create('enhanced_dns_lookup', function (Blueprint $table) {
            // Primary key
            $table->id('dns_lookup_id');
            
            // Domain being checked
            $table->string('domain', 255)->index()->comment('Domain being checked');
            
            // DNS server IP address
            $table->string('dns_server', 45)->index()->comment('DNS server IP address');
            
            // Unique composite index on (domain, dns_server)
            $table->unique(['domain', 'dns_server'], 'enhanced_dns_lookup_domain_dns_server_unique');
            
            // Resolved IP address
            $table->string('resolved_ip', 45)->nullable()->comment('Resolved IP address');
            
            // Resolution time in milliseconds
            $table->decimal('resolve_time_ms', 10, 2)->nullable()->comment('Resolution time in milliseconds');
            
            // Optional: Link to device if this domain is associated with a device
            $table->integer('device_id')->unsigned()->nullable()->index();
            $table->foreign('device_id')
                  ->references('device_id')
                  ->on('devices')
                  ->onDelete('SET NULL');
            
            // Check tracking
            $table->dateTime('last_checked')->nullable()->index()->comment('Last time DNS was checked');
            $table->integer('check_count')->default(0)->comment('Number of times checked');
            
            // Optional: Error tracking
            $table->text('error_message')->nullable()->comment('Error message if check failed');
            $table->boolean('check_failed')->default(0)->index()->comment('Did the last check fail');
            
            // Status tracking
            $table->boolean('enabled')->default(1)->comment('Should this domain/DNS server be checked');
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Composite indexes for common queries
            $table->index(['enabled', 'last_checked']);
            $table->index(['check_failed', 'resolve_time_ms']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enhanced_dns_lookup');
    }
};

