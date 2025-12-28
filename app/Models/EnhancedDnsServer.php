<?php

/**
 * EnhancedDnsServer.php
 *
 * DNS Server Configuration
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

class EnhancedDnsServer extends BaseModel
{
    protected $table = 'enhanced_dns_servers';
    protected $primaryKey = 'dns_server_id';
    public $timestamps = true;

    protected $fillable = [
        'dns_server',
        'description',
        'enabled',
        'priority',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Scope to filter enabled DNS servers
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', 1);
    }

    /**
     * Scope to order by priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'asc')->orderBy('dns_server', 'asc');
    }
}

