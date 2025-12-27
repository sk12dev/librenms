<?php

/**
 * SslVerificationController.php
 *
 * SSL Certificate Verification Widget
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

use App\Models\EnhancedSslVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use LibreNMS\Util\Validate;

class SslVerificationController extends WidgetController
{
    protected string $name = 'ssl-verification';

    protected $defaults = [
        'count' => 10,
        'filter' => 'all',
        'expiring_days' => 30,
        'sort_by' => 'days_until_expires',
        'sort_order' => 'asc',
    ];

    public function getView(Request $request): string|View
    {
        $settings = $this->getSettings();

        // Validate sort_order
        $sort_order = Validate::ascDesc($settings['sort_order'], 'ASC');

        // Validate sort_by field
        $allowed_sort_fields = ['domain', 'days_until_expires', 'last_checked', 'valid_to'];
        $sort_by = in_array($settings['sort_by'], $allowed_sort_fields) 
            ? $settings['sort_by'] 
            : 'days_until_expires';

        // Build query
        $query = EnhancedSslVerification::enabled()->with('device');

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
            'valid' => $query->valid(),
            'invalid' => $query->invalid(),
            'expiring' => $query->expiringSoon($settings['expiring_days']),
            default => null, // 'all' - no additional filter
        };

        // Apply sorting
        $query->orderBy($sort_by, $sort_order);

        // Limit results
        $sslRecords = $query->limit($settings['count'])->get();

        $data = array_merge($settings, [
            'ssl_records' => $sslRecords,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order,
        ]);

        return view('widgets.ssl-verification', $data);
    }
}

