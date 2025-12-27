# DNS Lookup Latency Feature - Deployment Steps

## 1. Run Database Migration

Run the migration to create the `enhanced_dns_lookup` table:

```bash
php artisan migrate --force
```

Or if you prefer to see what will be migrated first:

```bash
php artisan migrate:status
php artisan migrate
```

## 2. Clear Application Caches

Clear Laravel caches to ensure new routes, views, and config are loaded:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## 3. (Optional) Restart Web Server

If you're using a process manager or need to restart services:

```bash
# For systemd (if applicable)
sudo systemctl restart php-fpm
sudo systemctl restart nginx  # or apache2/httpd

# Or restart your web server service as needed
```

## 4. Verify Installation

### Check Migration Status
```bash
php artisan migrate:status
```

You should see the migration `2025_12_24_000000_create_enhanced_dns_lookup_table` listed as "Ran".

### Test API Endpoint
```bash
curl -H "X-Auth-Token: YOUR_API_TOKEN" \
     http://your-librenms-url/api/v0/enhanced/dns_lookup
```

Should return an empty array `[]` if no records exist yet.

### Check Widget Availability
1. Go to your LibreNMS dashboard
2. Click "Add Widget"
3. Look for "DNS Lookup Latency" in the widget list

## 5. Configure Python Script

If you haven't already, set up the `api_config.ini` file in your `scripts/enhanced/` directory:

```ini
[api]
url = http://your-librenms-url
token = your-api-token-here
```

## 6. Test the Python Script

Run the DNS checker script to test the API integration:

```bash
cd scripts/enhanced
python3 dnsChecker.py
```

The script should:
- Load API config
- Read DNS servers from `dns_servers.txt`
- Read domains from `config.txt`
- Check DNS resolution for each domain/server combination
- POST results to the API

## Troubleshooting

### Migration Fails
- Check database permissions
- Verify database connection in `.env`
- Check for existing table conflicts

### Widget Not Appearing
- Clear all caches (step 2)
- Check browser cache (hard refresh: Ctrl+F5)
- Verify route is registered: `php artisan route:list | grep dns-lookup`

### API Errors
- Verify API token is valid
- Check API endpoint: `/api/v0/enhanced/dns_lookup`
- Review logs: `storage/logs/laravel.log`

### Python Script Errors
- Ensure `requests` library is installed: `pip3 install requests`
- Verify `api_config.ini` format and location
- Check API URL is accessible from script location

