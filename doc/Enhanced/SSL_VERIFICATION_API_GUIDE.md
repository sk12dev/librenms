# SSL Verification API Guide

## What is an Eloquent Model?

An **Eloquent Model** is Laravel's Object-Relational Mapping (ORM) system. It provides an elegant way to interact with your database tables using PHP objects instead of writing raw SQL queries.

### Benefits:

1. **Type Safety**: Models define the structure and types of your data
2. **Relationships**: Easy to define relationships between tables (e.g., SSL verification belongs to a device)
3. **Query Builder**: Chain methods to build complex queries
4. **Scopes**: Reusable query filters (e.g., `expiringSoon()`, `valid()`)
5. **Data Casting**: Automatically converts data types (dates, booleans, etc.)
6. **Mass Assignment Protection**: Only specified fields can be bulk updated

### Example Usage:

```php
// Instead of: dbFetchRow('SELECT * FROM enhanced_ssl_verification WHERE domain = ?', ['github.com'])
$ssl = EnhancedSslVerification::where('domain', 'github.com')->first();

// Update
$ssl->valid = 1;
$ssl->save();

// Create
EnhancedSslVerification::create([
    'domain' => 'example.com',
    'valid' => 1,
    // ...
]);
```

## Created Files

### 1. Eloquent Model: `app/Models/EnhancedSslVerification.php`

**Features:**

-   Extends `BaseModel` (LibreNMS base model)
-   Defines fillable fields (what can be mass-assigned)
-   Defines casts (automatic type conversion)
-   Relationship to Device (optional)
-   Query scopes:
    -   `valid()` - Filter valid certificates
    -   `invalid()` - Filter invalid certificates
    -   `expiringSoon($days)` - Filter certificates expiring within X days
    -   `enabled()` - Filter enabled domains

### 2. API Functions: Added to `includes/html/api_functions.inc.php`

**Functions Created:**

#### `list_ssl_verifications(Request $request)`

List all SSL verification records with optional filters.

**Query Parameters:**

-   `domain` - Filter by domain (exact match or use `%`/`*` for wildcard)
-   `valid` - Filter by validity (0 or 1)
-   `expiring_soon` - Filter expiring certificates (requires `expiring_days`)
-   `expiring_days` - Days threshold for expiring soon (default: 30)
-   `enabled` - Filter by enabled status (default: only enabled)
-   `device_id` - Filter by device ID
-   `check_failed` - Filter by check failure status
-   `order` - Order by field (default: 'domain')
-   `order_dir` - Order direction: 'asc' or 'desc' (default: 'asc')

**Example:**

```
GET /api/v0/enhanced/ssl_verification?expiring_soon=1&expiring_days=30
GET /api/v0/enhanced/ssl_verification?domain=github.com
GET /api/v0/enhanced/ssl_verification?valid=1&order=days_until_expires&order_dir=asc
```

#### `get_ssl_verification(Request $request)`

Get a single SSL verification record by domain.

**Example:**

```
GET /api/v0/enhanced/ssl_verification/github.com
```

#### `add_ssl_verification(Request $request)`

Add or update (upsert) an SSL verification record. If domain exists, updates it; otherwise creates new.

**Request Body (JSON):**

```json
{
    "domain": "github.com",
    "valid": "yes",
    "days_until_expires": 47,
    "valid_from": "2025-02-05T00:00:00",
    "valid_to": "2026-02-05T23:59:59",
    "issuer": "Sectigo Limited",
    "lastChecked": "2025-12-20T21:38:31.932792",
    "device_id": 123,
    "port": 443,
    "enabled": true,
    "alert_on_expiring": true,
    "alert_days_before": 30
}
```

**Features:**

-   Automatically converts `valid: "yes"` to boolean
-   Handles date string conversion
-   Maps `lastChecked` to `last_checked`
-   Increments `check_count` automatically
-   Sets `check_failed` based on error or validity

**Example:**

```
POST /api/v0/enhanced/ssl_verification
Content-Type: application/json
```

#### `update_ssl_verification(Request $request)`

Update an existing SSL verification record.

**Example:**

```
PUT /api/v0/enhanced/ssl_verification/github.com
Content-Type: application/json

{
  "valid": 0,
  "error_message": "Certificate expired"
}
```

#### `delete_ssl_verification(Request $request)`

Delete an SSL verification record.

**Example:**

```
DELETE /api/v0/enhanced/ssl_verification/github.com
```

### 3. Routes: Added to `routes/api.php`

All routes are under `/api/v0/enhanced/ssl_verification/`:

-   `GET /api/v0/enhanced/ssl_verification` - List all
-   `GET /api/v0/enhanced/ssl_verification/{domain}` - Get one
-   `POST /api/v0/enhanced/ssl_verification` - Add/Update (upsert)
-   `PUT /api/v0/enhanced/ssl_verification/{domain}` - Update existing
-   `DELETE /api/v0/enhanced/ssl_verification/{domain}` - Delete

**Note:** These routes are in the global read-only section, so they require `can:global-read` permission. If you need admin-only for write operations, move POST/PUT/DELETE to the admin middleware group.

## Usage Examples

### Importing from Your JSON File

Your script can now POST data directly to the API:

```bash
# Read your JSON file and POST each domain
curl -X POST http://your-librenms/api/v0/enhanced/ssl_verification \
  -H "Content-Type: application/json" \
  -H "X-Auth-Token: your-api-token" \
  -d '{
    "domain": "github.com",
    "valid": "yes",
    "days_until_expires": 47,
    "valid_from": "2025-02-05T00:00:00",
    "valid_to": "2026-02-05T23:59:59",
    "issuer": "Sectigo Limited",
    "lastChecked": "2025-12-20T21:38:31.932792"
  }'
```

### Python Script Example

```python
import requests
import json

# Read your JSON file
with open('ssl_results.json', 'r') as f:
    data = json.load(f)

# API endpoint
api_url = "http://your-librenms/api/v0/enhanced/ssl_verification"
headers = {
    "Content-Type": "application/json",
    "X-Auth-Token": "your-api-token"
}

# Post each domain
for domain, ssl_data in data.items():
    response = requests.post(
        api_url,
        headers=headers,
        json={
            "domain": domain,
            **ssl_data  # Spread all fields from your JSON
        }
    )
    print(f"{domain}: {response.status_code}")
```

### Querying Expiring Certificates

```bash
# Get certificates expiring in the next 30 days
curl "http://your-librenms/api/v0/enhanced/ssl_verification?expiring_soon=1&expiring_days=30&order=days_until_expires&order_dir=asc"
```

## Response Format

### Success Response:

```json
{
    "status": "ok",
    "ssl_verifications": [
        {
            "ssl_verification_id": 1,
            "domain": "github.com",
            "valid": 1,
            "days_until_expires": 47,
            "valid_from": "2025-02-05T00:00:00",
            "valid_to": "2026-02-05T23:59:59",
            "issuer": "Sectigo Limited",
            "last_checked": "2025-12-20T21:38:31",
            "check_count": 5,
            "enabled": 1,
            "alert_on_expiring": 1,
            "alert_days_before": 30
        }
    ],
    "count": 1,
    "message": "SSL verification for 'github.com' updated successfully"
}
```

### Error Response:

```json
{
    "status": "error",
    "message": "Domain is required"
}
```

## Next Steps

1. **Run the migration** when you have PHP set up:

    ```bash
    php artisan migrate
    ```

2. **Test the API endpoints** using curl, Postman, or your script

3. **Optional: Create a script** to import your existing JSON file

4. **Optional: Add alerting** - You can create alert rules based on `days_until_expires` and `valid` fields

5. **Optional: Link to devices** - If domains are associated with devices, set the `device_id` field

## Field Mapping

Your JSON structure maps to the database as follows:

| JSON Field           | Database Field       | Notes                      |
| -------------------- | -------------------- | -------------------------- |
| `domain` (key)       | `domain`             | Unique identifier          |
| `valid: "yes"`       | `valid`              | Converted to boolean (1/0) |
| `days_until_expires` | `days_until_expires` | Integer                    |
| `valid_from`         | `valid_from`         | DateTime                   |
| `valid_to`           | `valid_to`           | DateTime                   |
| `issuer`             | `issuer`             | String                     |
| `lastChecked`        | `last_checked`       | DateTime (auto-mapped)     |

Additional fields available:

-   `device_id` - Link to a device
-   `port` - Port number (default: 443)
-   `error_message` - Error details if check failed
-   `enabled` - Enable/disable checking
-   `alert_on_expiring` - Toggle alerts
-   `alert_days_before` - Days before expiration to alert
