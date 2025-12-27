<?php

/**
 * EnhancedDnsLookup.php
 *
 * DNS Lookup Latency Data
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

class EnhancedDnsLookup extends BaseModel
{
    protected $table = 'enhanced_dns_lookup';
    protected $primaryKey = 'dns_lookup_id';
    public $timestamps = true;

    protected $fillable = [
        'domain',
        'dns_server',
        'resolved_ip',
        'resolve_time_ms',
        'device_id',
        'last_checked',
        'check_count',
        'error_message',
        'check_failed',
        'enabled',
    ];

    protected $casts = [
        'check_failed' => 'boolean',
        'enabled' => 'boolean',
        'resolve_time_ms' => 'decimal:2',
        'check_count' => 'integer',
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
     * Scope to filter enabled records
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', 1);
    }

    /**
     * Scope to filter failed checks
     */
    public function scopeFailed($query)
    {
        return $query->where('check_failed', 1);
    }

    /**
     * Scope to filter slow resolutions (above threshold)
     */
    public function scopeSlow($query, $threshold_ms = 100)
    {
        return $query->where('resolve_time_ms', '>', $threshold_ms)
                     ->where('check_failed', 0);
    }
}

