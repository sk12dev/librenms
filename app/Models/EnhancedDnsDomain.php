<?php

/**
 * EnhancedDnsDomain.php
 *
 * DNS Domain Configuration
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

class EnhancedDnsDomain extends BaseModel
{
    protected $table = 'enhanced_dns_domains';
    protected $primaryKey = 'dns_domain_id';
    public $timestamps = true;

    protected $fillable = [
        'domain',
        'description',
        'device_id',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
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
     * Scope to filter enabled domains
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', 1);
    }
}

