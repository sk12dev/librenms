<?php

/**
 * DnsLookup.php
 *
 * DNS Resolution Checker Poller Module
 * Checks DNS resolution times for domains using specified DNS servers and updates database
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
 *
 * @copyright  2025 Andy Hobbs
 * @author     Andy Hobbs
 */

namespace LibreNMS\Modules;

use App\Models\Device;
use App\Models\EnhancedDnsDomain;
use App\Models\EnhancedDnsLookup;
use App\Models\EnhancedDnsServer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Interfaces\Module;
use LibreNMS\OS;
use LibreNMS\Polling\ModuleStatus;

class DnsLookup implements Module
{
    private const CACHE_KEY = 'dns_lookup_poller_running';
    private const CACHE_TTL = 300; // 5 minutes - prevents duplicate runs

    /**
     * @inheritDoc
     */
    public function dependencies(): array
    {
        return [];
    }

    public function shouldDiscover(OS $os, ModuleStatus $status): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function discover(OS $os): void
    {
        // No discovery needed
    }

    /**
     * @inheritDoc
     */
    public function shouldPoll(OS $os, ModuleStatus $status): bool
    {
        // Only run if enabled and not already running (prevents duplicate runs across devices)
        if (! $status->isEnabled()) {
            return false;
        }

        // Use cache to ensure this only runs once per polling cycle
        // The first device to hit this will set the lock, others will skip
        if (Cache::has(self::CACHE_KEY)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function poll(OS $os, DataStorageInterface $datastore): void
    {
        // Set lock to prevent other devices from running this
        Cache::put(self::CACHE_KEY, true, self::CACHE_TTL);

        try {
            Log::info('Starting DNS lookup checks...');

            // Get all enabled DNS servers
            $dnsServers = EnhancedDnsServer::enabled()->ordered()->get();

            if ($dnsServers->isEmpty()) {
                Log::info('No enabled DNS servers found.');
                return;
            }

            // Get all enabled DNS domains
            $dnsDomains = EnhancedDnsDomain::enabled()->get();

            if ($dnsDomains->isEmpty()) {
                Log::info('No enabled DNS domains found.');
                return;
            }

            $checked = 0;
            $success = 0;
            $failed = 0;

            // Check each domain against each DNS server
            foreach ($dnsDomains as $dnsDomain) {
                foreach ($dnsServers as $dnsServer) {
                    $checked++;
                    $result = $this->resolveDns($dnsDomain->domain, $dnsServer->dns_server);

                    // Update or create the DNS lookup record
                    $dnsLookup = EnhancedDnsLookup::firstOrNew(
                        [
                            'domain' => $dnsDomain->domain,
                            'dns_server' => $dnsServer->dns_server,
                        ]
                    );

                    // Update fields
                    $dnsLookup->resolved_ip = $result['resolved_ip'];
                    $dnsLookup->resolve_time_ms = $result['resolve_time_ms'];
                    $dnsLookup->device_id = $dnsDomain->device_id;
                    $dnsLookup->last_checked = now();
                    $dnsLookup->error_message = $result['error_message'] ?? null;
                    $dnsLookup->check_failed = isset($result['error_message']) || $result['resolved_ip'] === null;
                    $dnsLookup->enabled = true;

                    // Increment check count
                    if ($dnsLookup->exists) {
                        $dnsLookup->increment('check_count');
                    } else {
                        $dnsLookup->check_count = 1;
                    }

                    $dnsLookup->save();

                    if ($result['resolved_ip'] && ! isset($result['error_message'])) {
                        $success++;
                        Log::info(sprintf(
                            '  ✓ %s via %s - %s (%s ms)',
                            $dnsDomain->domain,
                            $dnsServer->dns_server,
                            $result['resolved_ip'],
                            number_format($result['resolve_time_ms'], 2)
                        ));
                    } else {
                        $failed++;
                        $error = $result['error_message'] ?? 'Resolution failed';
                        Log::warning(sprintf(
                            '  ✗ %s via %s - %s',
                            $dnsDomain->domain,
                            $dnsServer->dns_server,
                            $error
                        ));
                    }
                }
            }

            Log::info(sprintf(
                'DNS lookup complete: %d checked, %d successful, %d failed',
                $checked,
                $success,
                $failed
            ));
        } finally {
            // Remove lock when done
            Cache::forget(self::CACHE_KEY);
        }
    }

    /**
     * Resolve DNS for a given domain using a specific DNS server
     *
     * @param  string  $domain
     * @param  string  $dnsServer
     * @param  int  $timeout
     * @return array
     */
    private function resolveDns(string $domain, string $dnsServer, int $timeout = 5): array
    {
        $result = [
            'resolved_ip' => null,
            'resolve_time_ms' => null,
            'error_message' => null,
        ];

        try {
            // Set the DNS server for this query
            // PHP's dns_get_record doesn't support specifying a DNS server directly,
            // so we'll use a workaround with system DNS resolution or use checkdnsrr
            // For better control, we'll use exec with dig or nslookup if available,
            // otherwise fall back to PHP's built-in functions

            $startTime = microtime(true);

            // Try using dig first (most reliable and supports custom DNS server)
            $digCommand = sprintf(
                "dig +short +timeout=%d @%s %s A 2>&1",
                $timeout,
                escapeshellarg($dnsServer),
                escapeshellarg($domain)
            );

            $output = [];
            $returnVar = 0;
            @exec($digCommand, $output, $returnVar);

            if ($returnVar === 0 && ! empty($output)) {
                // Filter out empty lines and get first IP
                $ips = array_filter($output, function ($line) {
                    return ! empty(trim($line)) && filter_var(trim($line), FILTER_VALIDATE_IP);
                });

                if (! empty($ips)) {
                    $resolvedIp = trim(reset($ips));
                    $endTime = microtime(true);
                    $resolveTimeMs = round(($endTime - $startTime) * 1000, 2);

                    $result['resolved_ip'] = $resolvedIp;
                    $result['resolve_time_ms'] = $resolveTimeMs;

                    return $result;
                }
            }

            // Fallback: Try nslookup
            $nslookupCommand = sprintf(
                "nslookup -timeout=%d %s %s 2>&1",
                $timeout,
                escapeshellarg($domain),
                escapeshellarg($dnsServer)
            );

            $output = [];
            $returnVar = 0;
            @exec($nslookupCommand, $output, $returnVar);

            if ($returnVar === 0 && ! empty($output)) {
                // Parse nslookup output for IP address
                foreach ($output as $line) {
                    if (preg_match('/Address:\s+(\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
                        // Skip the DNS server's own address
                        if ($matches[1] !== $dnsServer) {
                            $endTime = microtime(true);
                            $resolveTimeMs = round(($endTime - $startTime) * 1000, 2);

                            $result['resolved_ip'] = $matches[1];
                            $result['resolve_time_ms'] = $resolveTimeMs;

                            return $result;
                        }
                    }
                }
            }

            // Final fallback: Use PHP's built-in DNS functions (uses system DNS, not custom server)
            // This is less ideal but better than nothing
            $startTime = microtime(true);
            $ips = @gethostbyname($domain);

            if ($ips && $ips !== $domain && filter_var($ips, FILTER_VALIDATE_IP)) {
                $endTime = microtime(true);
                $resolveTimeMs = round(($endTime - $startTime) * 1000, 2);

                $result['resolved_ip'] = $ips;
                $result['resolve_time_ms'] = $resolveTimeMs;
                $result['error_message'] = 'Warning: Used system DNS instead of specified server';

                return $result;
            }

            // If all methods failed
            $result['error_message'] = 'DNS resolution failed: No valid IP address found';
        } catch (\Exception $e) {
            $result['error_message'] = 'Exception: ' . $e->getMessage();
        }

        return $result;
    }

    public function dataExists(Device $device): bool
    {
        // This module doesn't store device-specific data
        return false;
    }

    /**
     * @inheritDoc
     */
    public function cleanup(Device $device): int
    {
        // This module doesn't store device-specific data
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function dump(Device $device, string $type): ?array
    {
        // This module doesn't store device-specific data
        return null;
    }
}

