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
        Schema::create('enhanced_ssl_verification', function (Blueprint $table) {
            // Primary key
            $table->id('ssl_verification_id');
            
            // Domain/URL - unique identifier (the key from your JSON)
            $table->string('domain', 255)->unique()->index()->comment('Domain or URL being checked');
            
            // Optional: Link to device if this domain is associated with a device
            $table->integer('device_id')->unsigned()->nullable()->index();
            $table->foreign('device_id')
                  ->references('device_id')
                  ->on('devices')
                  ->onDelete('SET NULL');
            
            // Optional: Port number (default 443 for HTTPS)
            $table->integer('port')->default(443)->comment('Port number for SSL check');
            
            // SSL Certificate validity status
            $table->boolean('valid')->default(0)->index()->comment('Is the SSL certificate valid');
            $table->integer('days_until_expires')->nullable()->comment('Days until certificate expires');
            
            // Certificate validity dates
            $table->dateTime('valid_from')->nullable()->comment('Certificate valid from date');
            $table->dateTime('valid_to')->nullable()->comment('Certificate valid to (expiration) date');
            
            // Certificate issuer
            $table->string('issuer', 255)->nullable()->comment('Certificate issuer (CA)');
            
            // Check tracking
            $table->dateTime('last_checked')->nullable()->index()->comment('Last time SSL was checked');
            $table->integer('check_count')->default(0)->comment('Number of times checked');
            
            // Optional: Error tracking
            $table->text('error_message')->nullable()->comment('Error message if check failed');
            $table->boolean('check_failed')->default(0)->index()->comment('Did the last check fail');
            
            // Status tracking
            $table->boolean('enabled')->default(1)->comment('Should this domain be checked');
            $table->boolean('alert_on_expiring')->default(1)->comment('Alert when certificate is expiring soon');
            $table->integer('alert_days_before')->default(30)->comment('Days before expiration to alert');
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Composite indexes for common queries
            $table->index(['valid', 'days_until_expires']);
            $table->index(['enabled', 'last_checked']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enhanced_ssl_verification');
    }
};

