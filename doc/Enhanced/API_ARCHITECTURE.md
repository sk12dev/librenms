# LibreNMS API Architecture Overview

## Overview

LibreNMS is a Laravel-based network monitoring system. The API uses a hybrid approach combining Laravel routing with legacy function-based handlers.

## Architecture Components

### 1. Routing Structure (`routes/api.php`)

All API routes are prefixed with `/api/v0/` and organized by permission level:

-   **Public endpoints**: No authentication required (e.g., `system`, endpoint listing)
-   **Global read access** (`can:global-read`): Read-only access to most resources
-   **Admin access** (`can:admin`): Full CRUD operations
-   **Device/Port access**: Restricted by device/port permissions

### 2. Controller Pattern (`app/Api/Controllers/LegacyApiController.php`)

The `LegacyApiController` uses Laravel's magic `__call()` method to dynamically route method calls to functions in `includes/html/api_functions.inc.php`:

```php
public function __call($method_name, $arguments)
{
    $init_modules = ['web', 'alerts'];
    require base_path('/includes/init.php');
    require_once base_path('includes/html/api_functions.inc.php');

    return app()->call($method_name, $arguments);
}
```

This allows:

-   Route definitions to reference controller methods (e.g., `list_devices`)
-   Actual implementation to live in standalone functions
-   Laravel's dependency injection to work with function parameters

### 3. API Functions (`includes/html/api_functions.inc.php`)

All API endpoint logic is implemented as standalone functions in this file. Functions follow these patterns:

#### Function Naming Conventions:

-   `list_*` - List multiple resources (e.g., `list_devices`, `list_bgp`)
-   `get_*` - Get a single resource (e.g., `get_device`, `get_port_info`)
-   `add_*` - Create a new resource (e.g., `add_device`)
-   `update_*` / `edit_*` - Update a resource (e.g., `update_device`, `edit_bgp_descr`)
-   `del_*` / `delete_*` - Delete a resource (e.g., `del_device`)

#### Function Signatures:

Functions typically accept `Illuminate\Http\Request $request` as the first parameter. Route parameters are accessed via `$request->route('param_name')`.

#### Response Helpers:

-   `api_success($result, $result_name, $message, $code, $count, $extra)` - Success response
-   `api_error($statusCode, $message)` - Error response
-   `api_not_found()` - 404 response

#### Permission Checking:

-   `check_device_permission($device_id, $callback)` - Check device access
-   `check_port_permission($port_id, $device_id, $callback)` - Check port access
-   `check_bill_permission($bill_id, $callback)` - Check bill access

### 4. Route Definition Pattern

Routes are defined in `routes/api.php` following this pattern:

```php
Route::get('devices', [App\Api\Controllers\LegacyApiController::class, 'list_devices'])
    ->name('list_devices');
```

Route groups are used for:

-   **Prefixes**: Organize related endpoints (e.g., `devices`, `ports`, `bills`)
-   **Middleware**: Apply permissions (`can:global-read`, `can:admin`)
-   **Nested routes**: Create resource hierarchies

### 5. Example: Adding a New API Endpoint

To add a new API endpoint, follow these steps:

#### Step 1: Create the function in `includes/html/api_functions.inc.php`

```php
function get_my_resource(Illuminate\Http\Request $request)
{
    $id = $request->route('id');

    // Validate input
    if (empty($id)) {
        return api_error(400, 'ID is required');
    }

    // Check permissions (if needed)
    // check_device_permission($device_id, function() { ... });

    // Fetch data
    $resource = dbFetchRow('SELECT * FROM my_table WHERE id = ?', [$id]);

    if (!$resource) {
        return api_error(404, 'Resource not found');
    }

    // Return success response
    return api_success($resource, 'resource');
}
```

#### Step 2: Add route in `routes/api.php`

```php
// For read-only access (global-read)
Route::middleware(['can:global-read'])->group(function (): void {
    Route::get('my-resource/{id}',
        [App\Api\Controllers\LegacyApiController::class, 'get_my_resource'])
        ->name('get_my_resource');
});

// For admin-only access
Route::middleware(['can:admin'])->group(function (): void {
    Route::post('my-resource',
        [App\Api\Controllers\LegacyApiController::class, 'add_my_resource'])
        ->name('add_my_resource');
});
```

### 6. Key Features

#### Request Handling:

-   Route parameters: `$request->route('param_name')`
-   Query parameters: `$request->get('param_name')`
-   JSON body: `$request->json()->all()`
-   Request validation: Use Laravel's `Validator` facade

#### Database Access:

-   Legacy: `dbFetchRow()`, `dbFetchRows()`, `dbInsert()`, `dbUpdate()`, `dbDelete()`
-   Modern: Eloquent models (e.g., `Device::find()`, `Port::where()`)

#### Authentication:

-   Uses Laravel's authentication system
-   Permission checks via middleware (`can:global-read`, `can:admin`)
-   Device/port-level permissions via helper functions

#### Response Format:

Success:

```json
{
    "status": "ok",
    "resource_name": [...],
    "count": 1,
    "message": "Optional message"
}
```

Error:

```json
{
    "status": "error",
    "message": "Error description"
}
```

### 7. Common Patterns

#### Listing Resources with Filters:

```php
function list_resources(Illuminate\Http\Request $request)
{
    $query = $request->get('query');
    $type = $request->get('type');

    $sql = 'SELECT * FROM resources WHERE 1=1';
    $params = [];

    if ($type == 'active') {
        $sql .= ' AND status = ?';
        $params[] = 'active';
    }

    $results = dbFetchRows($sql, $params);
    return api_success($results, 'resources');
}
```

#### Creating Resources:

```php
function add_resource(Illuminate\Http\Request $request)
{
    $data = $request->json()->all();

    // Validation
    if (empty($data['name'])) {
        return api_error(400, 'Name is required');
    }

    // Insert
    $id = dbInsert(['name' => $data['name']], 'resources');

    return api_success(['id' => $id], 'resource', 'Resource created', 201);
}
```

#### Updating Resources:

```php
function update_resource(Illuminate\Http\Request $request)
{
    $id = $request->route('id');
    $data = $request->json()->all();

    dbUpdate($data, 'resources', 'id = ?', [$id]);

    return api_success_noresult(200, 'Resource updated');
}
```

### 8. File Structure

```
librenms/
├── routes/
│   └── api.php                    # API route definitions
├── app/
│   └── Api/
│       └── Controllers/
│           └── LegacyApiController.php  # Magic method router
└── includes/
    ├── init.php                   # Initialization (loads modules)
    └── html/
        └── api_functions.inc.php  # All API function implementations
```

### 9. Testing Endpoints

You can view all available endpoints by calling:

```
GET /api/v0
```

This returns a JSON object mapping endpoint names to their URLs.

### 10. Best Practices

1. **Always validate input** - Check required parameters and data types
2. **Use permission helpers** - `check_device_permission()`, `check_port_permission()`
3. **Return appropriate HTTP codes** - 200 (success), 201 (created), 400 (bad request), 404 (not found), 403 (forbidden)
4. **Use consistent naming** - Follow `list_*`, `get_*`, `add_*`, `update_*`, `del_*` patterns
5. **Handle errors gracefully** - Return `api_error()` with descriptive messages
6. **Use route names** - Always provide a `->name()` for routes
7. **Group related routes** - Use `Route::prefix()` and `Route::group()`
8. **Apply appropriate middleware** - Use `can:global-read` for read operations, `can:admin` for write operations
