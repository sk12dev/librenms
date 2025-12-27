<?php

/**
 * SslVerification.php
 *
 * SSL Certificate Verification Poller Module
 * Checks SSL certificates for enabled domains and updates database
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
use App\Models\EnhancedSslVerification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Interfaces\Module;
use LibreNMS\OS;
use LibreNMS\Polling\ModuleStatus;

class SslVerification implements Module
{
    private const CACHE_KEY = 'ssl_verification_poller_running';
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
            Log::info('Starting SSL verification checks...');

            // Get all enabled SSL verifications
            $sslVerifications = EnhancedSslVerification::enabled()->get();

            if ($sslVerifications->isEmpty()) {
                Log::info('No enabled SSL verifications found.');
                return;
            }

            $checked = 0;
            $success = 0;
            $failed = 0;

            foreach ($sslVerifications as $sslVerification) {
                $checked++;
                $result = $this->checkSslCertificate(
                    $sslVerification->domain,
                    $sslVerification->port ?? 443
                );

                // Update the record
                $sslVerification->valid = $result['valid'];
                $sslVerification->days_until_expires = $result['days_until_expires'];
                $sslVerification->valid_from = $result['valid_from'];
                $sslVerification->valid_to = $result['valid_to'];
                $sslVerification->issuer = $result['issuer'];
                $sslVerification->last_checked = now();
                $sslVerification->check_count = ($sslVerification->check_count ?? 0) + 1;
                $sslVerification->error_message = $result['error_message'] ?? null;
                $sslVerification->check_failed = ! $result['valid'] || isset($result['error_message']);

                $sslVerification->save();

                if ($result['valid']) {
                    $success++;
                    Log::info(sprintf(
                        '  ✓ %s:%d - Valid, expires in %d days',
                        $sslVerification->domain,
                        $sslVerification->port ?? 443,
                        $result['days_until_expires'] ?? 0
                    ));
                } else {
                    $failed++;
                    $error = $result['error_message'] ?? 'Certificate invalid';
                    Log::warning(sprintf(
                        '  ✗ %s:%d - %s',
                        $sslVerification->domain,
                        $sslVerification->port ?? 443,
                        $error
                    ));
                }
            }

            Log::info(sprintf(
                'SSL verification complete: %d checked, %d valid, %d failed',
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
     * Check SSL certificate for a given domain and port
     *
     * @param  string  $domain
     * @param  int  $port
     * @param  int  $timeout
     * @return array
     */
    private function checkSslCertificate(string $domain, int $port = 443, int $timeout = 10): array
    {
        $result = [
            'valid' => false,
            'days_until_expires' => null,
            'valid_from' => null,
            'valid_to' => null,
            'issuer' => null,
            'error_message' => null,
        ];

        try {
            // Create SSL context
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'capture_peer_cert_chain' => false,
                    'verify_peer' => false, // We'll check validity manually
                    'verify_peer_name' => false,
                    'allow_self_signed' => false,
                    'peer_name' => $domain,
                ],
            ]);

            // Create connection
            $socket = @stream_socket_client(
                "ssl://{$domain}:{$port}",
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (! $socket) {
                $result['error_message'] = "Connection failed: {$errstr} (Code: {$errno})";

                return $result;
            }

            // Get certificate
            $params = stream_context_get_params($socket);
            $cert = $params['options']['ssl']['peer_certificate'] ?? null;

            if (! $cert) {
                $result['error_message'] = 'Failed to retrieve certificate';

                return $result;
            }

            // Get certificate details
            $certData = openssl_x509_parse($cert);

            if (! $certData) {
                $result['error_message'] = 'Failed to parse certificate';

                return $result;
            }

            // Extract dates
            $validFrom = $certData['validFrom_time_t'] ?? null;
            $validTo = $certData['validTo_time_t'] ?? null;
            $now = time();

            if ($validFrom && $validTo) {
                $result['valid_from'] = date('Y-m-d H:i:s', $validFrom);
                $result['valid_to'] = date('Y-m-d H:i:s', $validTo);

                // Check validity
                $isValid = $now >= $validFrom && $now <= $validTo;
                $result['valid'] = $isValid;

                // Calculate days until expiration
                if ($now <= $validTo) {
                    $result['days_until_expires'] = (int) floor(($validTo - $now) / 86400);
                } else {
                    $result['days_until_expires'] = 0;
                }
            }

            // Extract issuer
            if (isset($certData['issuer']['O'])) {
                $result['issuer'] = $certData['issuer']['O'];
            } elseif (isset($certData['issuer']['CN'])) {
                $result['issuer'] = $certData['issuer']['CN'];
            } else {
                $result['issuer'] = 'Unknown';
            }

            fclose($socket);
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

