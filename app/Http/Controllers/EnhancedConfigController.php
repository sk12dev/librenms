<?php

namespace App\Http\Controllers;

use App\Http\Interfaces\ToastInterface;
use App\Models\EnhancedDnsDomain;
use App\Models\EnhancedDnsServer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnhancedConfigController extends Controller
{
    /**
     * Display the enhanced configuration management page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            return view('enhanced-config.index', [
                'dns_servers' => EnhancedDnsServer::ordered()->get(),
                'dns_domains' => EnhancedDnsDomain::enabled()->with('device')->orderBy('domain')->get(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle case where tables don't exist yet
            if (str_contains($e->getMessage(), "doesn't exist")) {
                abort(500, 'Database tables not found. Please run migrations: php artisan migrate');
            }
            throw $e;
        }
    }

    // ============================================================================
    // DNS Servers Management
    // ============================================================================

    /**
     * Store a newly created DNS server.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeDnsServer(Request $request, ToastInterface $toast)
    {
        $this->validate($request, [
            'dns_server' => 'required|ip|unique:enhanced_dns_servers,dns_server',
            'description' => 'nullable|string|max:255',
            'enabled' => 'boolean',
            'priority' => 'nullable|integer|min:0',
        ]);

        $dnsServer = EnhancedDnsServer::create($request->only([
            'dns_server',
            'description',
            'enabled',
            'priority',
        ]));

        $toast->success(__('DNS Server :server added successfully', ['server' => $dnsServer->dns_server]));

        return redirect()->route('enhanced-config.index');
    }

    /**
     * Update the specified DNS server.
     *
     * @param  Request  $request
     * @param  EnhancedDnsServer  $dnsServer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateDnsServer(Request $request, EnhancedDnsServer $dnsServer, ToastInterface $toast)
    {
        $this->validate($request, [
            'dns_server' => [
                'required',
                'ip',
                Rule::unique('enhanced_dns_servers', 'dns_server')->where(function ($query) use ($dnsServer): void {
                    $query->where('dns_server_id', '!=', $dnsServer->dns_server_id);
                }),
            ],
            'description' => 'nullable|string|max:255',
            'enabled' => 'boolean',
            'priority' => 'nullable|integer|min:0',
        ]);

        $dnsServer->fill($request->only([
            'dns_server',
            'description',
            'enabled',
            'priority',
        ]));

        if ($dnsServer->save()) {
            $toast->success(__('DNS Server :server updated successfully', ['server' => $dnsServer->dns_server]));
        } else {
            $toast->error(__('Failed to save'));

            return redirect()->back()->withInput();
        }

        return redirect()->route('enhanced-config.index');
    }

    /**
     * Remove the specified DNS server.
     *
     * @param  EnhancedDnsServer  $dnsServer
     * @return \Illuminate\Http\Response
     */
    public function destroyDnsServer(EnhancedDnsServer $dnsServer)
    {
        $serverIp = $dnsServer->dns_server;
        $dnsServer->delete();

        $msg = __('DNS Server :server deleted', ['server' => htmlentities($serverIp)]);

        return response($msg, 200);
    }

    // ============================================================================
    // DNS Domains Management
    // ============================================================================

    /**
     * Store a newly created DNS domain.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeDnsDomain(Request $request, ToastInterface $toast)
    {
        $this->validate($request, [
            'domain' => 'required|string|max:255|unique:enhanced_dns_domains,domain',
            'description' => 'nullable|string|max:255',
            'device_id' => 'nullable|integer|exists:devices,device_id',
            'enabled' => 'boolean',
        ]);

        // Clean domain name
        $domain = $request->input('domain');
        if (str_contains($domain, '://')) {
            $domain = parse_url($domain, PHP_URL_HOST) ?: $domain;
        }
        if (str_contains($domain, '/')) {
            $domain = explode('/', $domain)[0];
        }
        if (str_contains($domain, ':')) {
            $domain = explode(':', $domain)[0];
        }

        $dnsDomain = EnhancedDnsDomain::create([
            'domain' => $domain,
            'description' => $request->input('description'),
            'device_id' => $request->input('device_id'),
            'enabled' => $request->input('enabled', true),
        ]);

        $toast->success(__('DNS Domain :domain added successfully', ['domain' => $dnsDomain->domain]));

        return redirect()->route('enhanced-config.index');
    }

    /**
     * Update the specified DNS domain.
     *
     * @param  Request  $request
     * @param  EnhancedDnsDomain  $dnsDomain
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateDnsDomain(Request $request, EnhancedDnsDomain $dnsDomain, ToastInterface $toast)
    {
        $this->validate($request, [
            'domain' => [
                'required',
                'string',
                'max:255',
                Rule::unique('enhanced_dns_domains', 'domain')->where(function ($query) use ($dnsDomain): void {
                    $query->where('dns_domain_id', '!=', $dnsDomain->dns_domain_id);
                }),
            ],
            'description' => 'nullable|string|max:255',
            'device_id' => 'nullable|integer|exists:devices,device_id',
            'enabled' => 'boolean',
        ]);

        // Clean domain name
        $domain = $request->input('domain');
        if (str_contains($domain, '://')) {
            $domain = parse_url($domain, PHP_URL_HOST) ?: $domain;
        }
        if (str_contains($domain, '/')) {
            $domain = explode('/', $domain)[0];
        }
        if (str_contains($domain, ':')) {
            $domain = explode(':', $domain)[0];
        }

        $dnsDomain->fill([
            'domain' => $domain,
            'description' => $request->input('description'),
            'device_id' => $request->input('device_id'),
            'enabled' => $request->input('enabled', true),
        ]);

        if ($dnsDomain->save()) {
            $toast->success(__('DNS Domain :domain updated successfully', ['domain' => $dnsDomain->domain]));
        } else {
            $toast->error(__('Failed to save'));

            return redirect()->back()->withInput();
        }

        return redirect()->route('enhanced-config.index');
    }

    /**
     * Remove the specified DNS domain.
     *
     * @param  EnhancedDnsDomain  $dnsDomain
     * @return \Illuminate\Http\Response
     */
    public function destroyDnsDomain(EnhancedDnsDomain $dnsDomain)
    {
        $domainName = $dnsDomain->domain;
        $dnsDomain->delete();

        $msg = __('DNS Domain :domain deleted', ['domain' => htmlentities($domainName)]);

        return response($msg, 200);
    }
}

