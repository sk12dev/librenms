#!/usr/bin/env python3
"""
SSL Certificate Checker
Checks SSL certificate validity for websites and stores results in JSON format.
version 1.0.0
Andy Hobbs - 12/20/2025
"""

import ssl
import socket
import json
import datetime
from urllib.parse import urlparse
from typing import Dict, Any, Optional


def extract_domain(url: str) -> str:
    """Extract domain name from URL."""
    if not url.startswith(('http://', 'https://')):
        url = 'https://' + url
    
    parsed = urlparse(url)
    domain = parsed.netloc or parsed.path.split('/')[0]
    return domain.split(':')[0]  # Remove port if present


def check_ssl_certificate(domain: str, port: int = 443, timeout: int = 10) -> Dict[str, Any]:
    """
    Check SSL certificate for a given domain.
    
    Returns a dictionary with certificate information or error details.
    """
    result = {
        'valid': 'no',
        'days_until_expires': None,
        'valid_from': None,
        'valid_to': None,
        'issuer': None,
        'lastChecked': datetime.datetime.now().isoformat()
    }
    
    try:
        # Create SSL context
        context = ssl.create_default_context()
        
        # Create socket and wrap with SSL
        sock = socket.create_connection((domain, port), timeout=timeout)
        with context.wrap_socket(sock, server_hostname=domain) as ssock:
            cert = ssock.getpeercert()
            
            # Extract certificate information
            if cert:
                # Parse dates
                not_before = datetime.datetime.strptime(cert['notBefore'], '%b %d %H:%M:%S %Y %Z')
                not_after = datetime.datetime.strptime(cert['notAfter'], '%b %d %H:%M:%S %Y %Z')
                now = datetime.datetime.now()
                
                # Check validity
                is_valid = now >= not_before and now <= not_after
                
                # Calculate days until expiration
                if now <= not_after:
                    days_until_expires = (not_after - now).days
                else:
                    days_until_expires = 0
                
                # Extract issuer
                issuer_dict = dict(x[0] for x in cert.get('issuer', []))
                issuer = issuer_dict.get('organizationName', issuer_dict.get('commonName', 'Unknown'))
                
                # Build result
                result['valid'] = 'yes' if is_valid else 'no'
                result['days_until_expires'] = days_until_expires
                result['valid_from'] = not_before.isoformat()
                result['valid_to'] = not_after.isoformat()
                result['issuer'] = issuer
                
    except socket.timeout:
        result['error'] = f'Connection timeout after {timeout} seconds'
    except socket.gaierror as e:
        result['error'] = f'DNS resolution failed: {str(e)}'
    except ssl.SSLError as e:
        result['error'] = f'SSL error: {str(e)}'
    except ConnectionRefusedError:
        result['error'] = 'Connection refused'
    except Exception as e:
        result['error'] = f'Unexpected error: {str(e)}'
    
    return result


def read_config(config_file: str = 'config.txt') -> list:
    """Read website URLs from config file."""
    urls = []
    try:
        with open(config_file, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#'):  # Skip empty lines and comments
                    urls.append(line)
    except FileNotFoundError:
        print(f"Warning: Config file '{config_file}' not found. Creating empty config file.")
        with open(config_file, 'w', encoding='utf-8') as f:
            f.write("# Add one website URL per line\n")
            f.write("# Example: example.com\n")
            f.write("# Example: https://www.example.com\n")
    except Exception as e:
        print(f"Error reading config file: {e}")
    
    return urls


def load_json_data(json_file: str = 'ssl_check.json') -> Dict[str, Any]:
    """Load existing JSON data or return empty dict."""
    try:
        with open(json_file, 'r', encoding='utf-8') as f:
            return json.load(f)
    except FileNotFoundError:
        return {}
    except json.JSONDecodeError:
        print(f"Warning: Invalid JSON in '{json_file}'. Starting with empty data.")
        return {}


def save_json_data(data: Dict[str, Any], json_file: str = 'ssl_check.json') -> None:
    """Save data to JSON file."""
    try:
        with open(json_file, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print(f"Results saved to '{json_file}'")
    except Exception as e:
        print(f"Error saving JSON file: {e}")


def main():
    """Main function to orchestrate SSL certificate checking."""
    config_file = 'config.txt'
    json_file = 'ssl_check.json'
    
    # Read URLs from config file
    urls = read_config(config_file)
    
    if not urls:
        print("No URLs found in config file. Please add URLs to check.")
        return
    
    # Load existing JSON data
    json_data = load_json_data(json_file)
    
    # Check SSL certificates for each URL
    print(f"Checking SSL certificates for {len(urls)} website(s)...")
    for url in urls:
        domain = extract_domain(url)
        print(f"Checking {domain}...")
        
        # Check certificate
        result = check_ssl_certificate(domain)
        
        # Update JSON data
        json_data[domain] = result
        
        # Print status
        if result['valid'] == 'yes':
            print(f"  ✓ Valid - Expires in {result['days_until_expires']} days")
        else:
            error_msg = result.get('error', 'Certificate invalid')
            print(f"  ✗ Invalid - {error_msg}")
    
    # Save updated JSON data
    save_json_data(json_data, json_file)
    print("SSL certificate check complete.")


if __name__ == '__main__':
    main()

