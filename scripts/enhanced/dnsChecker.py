#!/usr/bin/env python3
"""
DNS Resolution Checker
Checks DNS resolution times for domains using specified DNS servers and stores results via LibreNMS API.
version 2.0.0
Andy Hobbs - 12/20/2025
"""

import dns.resolver
import json
import datetime
import time
import configparser
import sys
import os
from pathlib import Path
from urllib.parse import urlparse, urljoin
from typing import Dict, Any, List, Optional
try:
    import requests
except ImportError:
    print("Error: 'requests' library is required. Install it with: pip install requests")
    sys.exit(1)


def read_dns_servers(config_file: str = 'dns_servers.txt') -> List[str]:
    """Read DNS server IPs from config file."""
    servers = []
    try:
        with open(config_file, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#'):  # Skip empty lines and comments
                    servers.append(line)
    except FileNotFoundError:
        print(f"Warning: DNS servers config file '{config_file}' not found. Creating empty config file.")
        with open(config_file, 'w', encoding='utf-8') as f:
            f.write("# Add one DNS server IP address per line\n")
            f.write("# Examples:\n")
            f.write("# 8.8.8.8\n")
            f.write("# 1.1.1.1\n")
            f.write("# 208.67.222.222\n")
    except Exception as e:
        print(f"Error reading DNS servers config file: {e}")
    
    return servers


def read_domains(config_file: str = 'config.txt') -> List[str]:
    """Read website URLs/domains from config file."""
    domains = []
    try:
        with open(config_file, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#'):  # Skip empty lines and comments
                    # Extract domain from URL if needed
                    domain = line
                    if '://' in domain:
                        domain = domain.split('://')[1]
                    if '/' in domain:
                        domain = domain.split('/')[0]
                    if ':' in domain:
                        domain = domain.split(':')[0]
                    domains.append(domain)
    except FileNotFoundError:
        print(f"Warning: Config file '{config_file}' not found.")
    except Exception as e:
        print(f"Error reading config file: {e}")
    
    return domains


def resolve_dns(domain: str, dns_server: str, timeout: int = 5) -> Dict[str, Any]:
    """
    Resolve DNS for a given domain using a specific DNS server.
    
    Returns a dictionary with resolution information or error details.
    """
    result = {
        'resolved_ip': None,
        'resolve_time_ms': None,
        'timestamp': datetime.datetime.now().isoformat(),
        'error': None
    }
    
    try:
        # Create resolver with specific DNS server
        resolver = dns.resolver.Resolver()
        resolver.nameservers = [dns_server]
        resolver.timeout = timeout
        resolver.lifetime = timeout
        
        # Measure resolution time
        start_time = time.time()
        answers = resolver.resolve(domain, 'A')
        end_time = time.time()
        
        # Calculate resolution time in milliseconds
        resolve_time_ms = round((end_time - start_time) * 1000, 2)
        
        # Get the first IP address (primary)
        resolved_ip = str(answers[0])
        
        result['resolved_ip'] = resolved_ip
        result['resolve_time_ms'] = resolve_time_ms
        
    except dns.resolver.Timeout:
        result['error'] = f'DNS query timeout after {timeout} seconds'
    except dns.resolver.NXDOMAIN:
        result['error'] = 'Domain does not exist (NXDOMAIN)'
    except dns.resolver.NoAnswer:
        result['error'] = 'No answer received from DNS server'
    except dns.resolver.NoNameservers:
        result['error'] = 'No nameservers available'
    except dns.exception.DNSException as e:
        result['error'] = f'DNS error: {str(e)}'
    except Exception as e:
        result['error'] = f'Unexpected error: {str(e)}'
    
    return result


def load_api_config(config_file: str = 'api_config.ini') -> Dict[str, str]:
    """
    Load API configuration from config file.
    
    The config file is looked for in the following order:
    1. Same directory as the script (preferred)
    2. Current working directory
    
    Expected format (api_config.ini):
    [api]
    url = http://librenms.example.com
    token = your-api-token-here
    
    Returns dictionary with 'url' and 'token' keys.
    """
    config = configparser.ConfigParser()
    
    # Get the script's directory
    script_dir = Path(__file__).parent.absolute()
    
    # Try to find config file: first in script directory, then current working directory
    config_paths = [
        script_dir / config_file,  # Same directory as script
        Path(config_file)  # Current working directory (absolute or relative)
    ]
    
    config_path = None
    for path in config_paths:
        if path.exists():
            config_path = path
            break
    
    if config_path is None:
        # Show helpful error with script directory location
        print(f"Error: Config file '{config_file}' not found.")
        print(f"Looked in:")
        print(f"  1. {script_dir / config_file}")
        print(f"  2. {Path(config_file).absolute()}")
        print(f"\nPlease create '{config_file}' in the script directory ({script_dir})")
        print(f"with the following format:")
        print("[api]")
        print("url = http://librenms.example.com")
        print("token = your-api-token-here")
        sys.exit(1)
    
    try:
        config.read(config_path)
        
        if 'api' not in config:
            raise ValueError(f"Config file '{config_path}' missing [api] section")
        
        api_url = config.get('api', 'url', fallback=None)
        api_token = config.get('api', 'token', fallback=None)
        
        if not api_url:
            raise ValueError(f"Config file '{config_path}' missing 'url' in [api] section")
        if not api_token:
            raise ValueError(f"Config file '{config_path}' missing 'token' in [api] section")
        
        # Ensure URL doesn't end with trailing slash
        api_url = api_url.rstrip('/')
        
        return {
            'url': api_url,
            'token': api_token
        }
    except Exception as e:
        print(f"Error reading config file '{config_path}': {e}")
        sys.exit(1)


def update_dns_lookup_via_api(api_url: str, api_token: str, domain: str, dns_server: str,
                               result: Dict[str, Any]) -> bool:
    """
    Update DNS lookup data via LibreNMS API.
    
    Returns True if successful, False otherwise.
    """
    endpoint = urljoin(api_url, '/api/v0/enhanced/dns_lookup')
    headers = {
        'X-Auth-Token': api_token,
        'Content-Type': 'application/json'
    }
    
    # Prepare payload - map error to error_message
    payload = {
        'domain': domain,
        'dns_server': dns_server,
        'resolved_ip': result.get('resolved_ip'),
        'resolve_time_ms': result.get('resolve_time_ms'),
        'lastChecked': result.get('timestamp')
    }
    
    # Map error field to error_message if present
    if 'error' in result:
        payload['error_message'] = result['error']
    
    # Remove None values
    payload = {k: v for k, v in payload.items() if v is not None}
    
    try:
        response = requests.post(endpoint, headers=headers, json=payload, timeout=30)
        response.raise_for_status()
        
        data = response.json()
        
        if data.get('status') != 'ok':
            print(f"  Warning: API returned error status: {data.get('message', 'Unknown error')}")
            return False
        
        return True
        
    except requests.exceptions.Timeout:
        print(f"  Error: API request timed out after 30 seconds")
        return False
    except requests.exceptions.ConnectionError as e:
        print(f"  Error: Failed to connect to API: {str(e)}")
        return False
    except requests.exceptions.HTTPError as e:
        if e.response.status_code == 401 or e.response.status_code == 403:
            print(f"  Error: Authentication failed. Check your API token.")
        else:
            print(f"  Error: API request failed with status {e.response.status_code}: {str(e)}")
        return False
    except json.JSONDecodeError:
        print(f"  Error: Invalid JSON response from API")
        return False
    except Exception as e:
        print(f"  Error: Failed to update DNS lookup: {str(e)}")
        return False


def main():
    """Main function to orchestrate DNS resolution checking via LibreNMS API."""
    config_file = 'api_config.ini'
    dns_servers_file = 'dns_servers.txt'
    domains_file = 'config.txt'
    
    # Load API configuration
    print(f"Loading API configuration from '{config_file}'...")
    api_config = load_api_config(config_file)
    api_url = api_config['url']
    api_token = api_config['token']
    
    # Read DNS servers from config file
    dns_servers = read_dns_servers(dns_servers_file)
    
    if not dns_servers:
        print("No DNS servers found in config file. Please add DNS server IPs to check.")
        return
    
    # Read domains from config file
    domains = read_domains(domains_file)
    
    if not domains:
        print("No domains found in config file. Please add domains to check.")
        return
    
    # Check DNS resolution for each domain against each DNS server
    print(f"Checking DNS resolution for {len(domains)} domain(s) using {len(dns_servers)} DNS server(s)...")
    success_count = 0
    fail_count = 0
    
    for dns_server in dns_servers:
        print(f"\nUsing DNS server: {dns_server}")
        
        for domain in domains:
            print(f"Checking {domain} via {dns_server}...", end=' ', flush=True)
            
            # Resolve DNS
            result = resolve_dns(domain, dns_server)
            
            # Update via API
            if update_dns_lookup_via_api(api_url, api_token, domain, dns_server, result):
                success_count += 1
                # Print status
                if result['error']:
                    print(f"✗ Error: {result['error']}")
                else:
                    print(f"✓ {result['resolved_ip']} ({result['resolve_time_ms']}ms)")
            else:
                fail_count += 1
                if result['error']:
                    print(f"✗ Error: {result['error']} (API update failed)")
                else:
                    print(f"✗ {result['resolved_ip']} ({result['resolve_time_ms']}ms) (API update failed)")
    
    # Summary
    print(f"\nDNS resolution check complete.")
    print(f"  Successfully updated: {success_count}")
    if fail_count > 0:
        print(f"  Failed to update: {fail_count}")


if __name__ == '__main__':
    main()

