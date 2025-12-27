#!/usr/bin/env python3
"""
SSL Certificate Checker
Checks SSL certificate validity for websites and stores results via LibreNMS API.
version 2.0.0
Andy Hobbs - 12/20/2025
"""

import ssl
import socket
import json
import datetime
import configparser
import sys
import os
from pathlib import Path
from urllib.parse import urlparse, urljoin
from typing import Dict, Any, Optional, List
try:
    import requests
except ImportError:
    print("Error: 'requests' library is required. Install it with: pip install requests")
    sys.exit(1)


def extract_domain(url: str) -> str:
    """
    Extract domain name from URL.
    
    Note: This function is kept for backward compatibility but is no longer
    used in the main workflow since domains come directly from the API.
    """
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


def fetch_domains_from_api(api_url: str, api_token: str) -> List[Dict[str, Any]]:
    """
    Fetch enabled domains from LibreNMS API.
    
    Returns list of dictionaries with 'domain' and optionally 'port' fields.
    """
    endpoint = urljoin(api_url, '/api/v0/enhanced/ssl_verification')
    headers = {
        'X-Auth-Token': api_token,
        'Content-Type': 'application/json'
    }
    params = {
        'enabled': 1
    }
    
    try:
        response = requests.get(endpoint, headers=headers, params=params, timeout=30)
        response.raise_for_status()
        
        data = response.json()
        
        if data.get('status') != 'ok':
            raise ValueError(f"API returned error status: {data.get('message', 'Unknown error')}")
        
        ssl_verifications = data.get('ssl_verifications', [])
        
        # Extract domain and port from each verification record
        domains = []
        for record in ssl_verifications:
            domain_info = {
                'domain': record.get('domain'),
                'port': record.get('port', 443)  # Default to 443 if not specified
            }
            if domain_info['domain']:
                domains.append(domain_info)
        
        return domains
        
    except requests.exceptions.Timeout:
        raise Exception(f"API request timed out after 30 seconds")
    except requests.exceptions.ConnectionError as e:
        raise Exception(f"Failed to connect to API at {api_url}: {str(e)}")
    except requests.exceptions.HTTPError as e:
        if e.response.status_code == 401 or e.response.status_code == 403:
            raise Exception(f"Authentication failed. Check your API token.")
        raise Exception(f"API request failed with status {e.response.status_code}: {str(e)}")
    except json.JSONDecodeError:
        raise Exception(f"Invalid JSON response from API")
    except Exception as e:
        raise Exception(f"Error fetching domains from API: {str(e)}")


def update_ssl_verification_via_api(api_url: str, api_token: str, domain: str, 
                                   result: Dict[str, Any], port: int = 443) -> bool:
    """
    Update SSL verification data via LibreNMS API.
    
    Returns True if successful, False otherwise.
    """
    endpoint = urljoin(api_url, '/api/v0/enhanced/ssl_verification')
    headers = {
        'X-Auth-Token': api_token,
        'Content-Type': 'application/json'
    }
    
    # Prepare payload - map error to error_message
    payload = {
        'domain': domain,
        'port': port,
        'valid': result.get('valid', 'no'),
        'days_until_expires': result.get('days_until_expires'),
        'valid_from': result.get('valid_from'),
        'valid_to': result.get('valid_to'),
        'issuer': result.get('issuer'),
        'lastChecked': result.get('lastChecked')
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
        print(f"  Error: Failed to update SSL verification: {str(e)}")
        return False


def main():
    """Main function to orchestrate SSL certificate checking via LibreNMS API."""
    config_file = 'api_config.ini'
    
    # Load API configuration
    print(f"Loading API configuration from '{config_file}'...")
    api_config = load_api_config(config_file)
    api_url = api_config['url']
    api_token = api_config['token']
    
    # Fetch domains from API
    print(f"Fetching enabled domains from LibreNMS API...")
    try:
        domains = fetch_domains_from_api(api_url, api_token)
    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)
    
    if not domains:
        print("No enabled domains found in LibreNMS. Please add domains via the web interface or API.")
        return
    
    # Check SSL certificates for each domain
    print(f"Checking SSL certificates for {len(domains)} domain(s)...")
    success_count = 0
    fail_count = 0
    
    for domain_info in domains:
        domain = domain_info['domain']
        port = domain_info.get('port', 443)
        
        print(f"Checking {domain}:{port}...")
        
        # Check certificate
        result = check_ssl_certificate(domain, port=port)
        
        # Update via API
        if update_ssl_verification_via_api(api_url, api_token, domain, result, port):
            success_count += 1
            # Print status
            if result['valid'] == 'yes':
                print(f"  ✓ Valid - Expires in {result['days_until_expires']} days")
            else:
                error_msg = result.get('error', 'Certificate invalid')
                print(f"  ✗ Invalid - {error_msg}")
        else:
            fail_count += 1
    
    # Summary
    print(f"\nSSL certificate check complete.")
    print(f"  Successfully updated: {success_count}")
    if fail_count > 0:
        print(f"  Failed to update: {fail_count}")


if __name__ == '__main__':
    main()

