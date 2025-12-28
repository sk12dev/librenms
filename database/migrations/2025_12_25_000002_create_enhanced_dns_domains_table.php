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
        Schema::create('enhanced_dns_domains', function (Blueprint $table) {
            // Primary key
            $table->id('dns_domain_id');
            
            // Domain to check - unique
            $table->string('domain', 255)->unique()->index()->comment('Domain to check for DNS resolution');
            
            // Optional: Description/name for the domain
            $table->string('description', 255)->nullable()->comment('Description or name for this domain');
            
            // Optional: Link to device if this domain is associated with a device
            $table->integer('device_id')->unsigned()->nullable()->index();
            $table->foreign('device_id')
                  ->references('device_id')
                  ->on('devices')
                  ->onDelete('SET NULL');
            
            // Status tracking
            $table->boolean('enabled')->default(1)->index()->comment('Should this domain be checked');
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Index for enabled domains
            $table->index(['enabled', 'domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enhanced_dns_domains');
    }
};

