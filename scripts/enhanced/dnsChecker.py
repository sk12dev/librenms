#!/usr/bin/env python3
"""
DNS Resolution Checker
Checks DNS resolution times for domains using specified DNS servers and stores results in JSON format.
version 1.0.0
Andy Hobbs - 12/20/2025
"""

import dns.resolver
import json
import datetime
import time
from typing import Dict, Any, List, Optional


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


def load_json_data(json_file: str = 'dns_check.json') -> Dict[str, Any]:
    """Load existing JSON data or return empty dict."""
    try:
        with open(json_file, 'r', encoding='utf-8') as f:
            return json.load(f)
    except FileNotFoundError:
        return {}
    except json.JSONDecodeError:
        print(f"Warning: Invalid JSON in '{json_file}'. Starting with empty data.")
        return {}


def save_json_data(data: Dict[str, Any], json_file: str = 'dns_check.json') -> None:
    """Save data to JSON file."""
    try:
        with open(json_file, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print(f"Results saved to '{json_file}'")
    except Exception as e:
        print(f"Error saving JSON file: {e}")


def main():
    """Main function to orchestrate DNS resolution checking."""
    dns_servers_file = 'dns_servers.txt'
    domains_file = 'config.txt'
    json_file = 'dns_check.json'
    
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
    
    # Load existing JSON data
    json_data = load_json_data(json_file)
    
    # Check DNS resolution for each domain against each DNS server
    print(f"Checking DNS resolution for {len(domains)} domain(s) using {len(dns_servers)} DNS server(s)...")
    
    for dns_server in dns_servers:
        print(f"\nUsing DNS server: {dns_server}")
        
        # Initialize server entry if it doesn't exist
        if dns_server not in json_data:
            json_data[dns_server] = {}
        
        for domain in domains:
            print(f"  Resolving {domain}...", end=' ', flush=True)
            
            # Resolve DNS
            result = resolve_dns(domain, dns_server)
            
            # Update JSON data
            json_data[dns_server][domain] = result
            
            # Print status
            if result['error']:
                print(f"X Error: {result['error']}")
            else:
                print(f"OK {result['resolved_ip']} ({result['resolve_time_ms']}ms)")
    
    # Save updated JSON data
    save_json_data(json_data, json_file)
    print("\nDNS resolution check complete.")


if __name__ == '__main__':
    main()

