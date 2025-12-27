<?php

/**
 * DnsLookupController.php
 *
 * DNS Lookup Latency Widget
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

namespace App\Http\Controllers\Widgets;

use App\Models\EnhancedDnsLookup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use LibreNMS\Util\Validate;

class DnsLookupController extends WidgetController
{
    protected string $name = 'dns-lookup';

    protected $defaults = [
        'count' => 10,
        'filter' => 'all',
        'slow_threshold' => 100,
        'sort_by' => 'resolve_time_ms',
        'sort_order' => 'desc',
    ];

    public function getView(Request $request): string|View
    {
        $settings = $this->getSettings();

        // Validate sort_order
        $sort_order = Validate::ascDesc($settings['sort_order'], 'DESC');

        // Validate sort_by field
        $allowed_sort_fields = ['domain', 'dns_server', 'resolve_time_ms', 'last_checked'];
        $sort_by = in_array($settings['sort_by'], $allowed_sort_fields) 
            ? $settings['sort_by'] 
            : 'resolve_time_ms';

        // Build query
        $query = EnhancedDnsLookup::enabled()->with('device');

        // Apply device access filtering if device_id is set
        $user = Auth::user();
        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('device_id')
                  ->orWhereHas('device', function ($deviceQuery) use ($user) {
                      $deviceQuery->hasAccess($user);
                  });
            });
        }

        // Apply filters
        match ($settings['filter']) {
            'failed' => $query->failed(),
            'slow' => $query->slow($settings['slow_threshold']),
            default => null, // 'all' - no additional filter
        };

        // Apply sorting
        $query->orderBy($sort_by, $sort_order);

        // Limit results
        $dnsRecords = $query->limit($settings['count'])->get();

        $data = array_merge($settings, [
            'dns_records' => $dnsRecords,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order,
        ]);

        return view('widgets.dns-lookup', $data);
    }
}

