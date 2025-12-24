<?php

/**
 * EnhancedSslVerification.php
 *
 * SSL Certificate Verification Data
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnhancedSslVerification extends BaseModel
{
    protected $table = 'enhanced_ssl_verification';
    protected $primaryKey = 'ssl_verification_id';
    public $timestamps = true;

    protected $fillable = [
        'domain',
        'device_id',
        'port',
        'valid',
        'days_until_expires',
        'valid_from',
        'valid_to',
        'issuer',
        'last_checked',
        'check_count',
        'error_message',
        'check_failed',
        'enabled',
        'alert_on_expiring',
        'alert_days_before',
    ];

    protected $casts = [
        'valid' => 'boolean',
        'check_failed' => 'boolean',
        'enabled' => 'boolean',
        'alert_on_expiring' => 'boolean',
        'days_until_expires' => 'integer',
        'port' => 'integer',
        'check_count' => 'integer',
        'alert_days_before' => 'integer',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'last_checked' => 'datetime',
    ];

    /**
     * Relationship to Device (optional)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }

    /**
     * Scope to filter by valid certificates
     */
    public function scopeValid($query)
    {
        return $query->where('valid', 1);
    }

    /**
     * Scope to filter by invalid/expired certificates
     */
    public function scopeInvalid($query)
    {
        return $query->where('valid', 0);
    }

    /**
     * Scope to filter by expiring soon
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('valid', 1)
                     ->where('days_until_expires', '<=', $days)
                     ->where('days_until_expires', '>=', 0);
    }

    /**
     * Scope to filter enabled domains
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', 1);
    }
}

