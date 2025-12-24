# Database Migrations Guide for LibreNMS

## Overview

LibreNMS uses Laravel's migration system to manage database schema changes. All migrations are stored in `database/migrations/` and follow Laravel's naming conventions.

## Migration File Naming

Migrations follow this pattern:
```
YYYY_MM_DD_HHMMSS_description.php
```

Example:
- `2025_12_05_205509_devices_add_mtu_status.php` - Adds a column to devices table
- `2024_10_20_154356_create_qos_table.php` - Creates a new table
- `2020_12_14_091314_create_port_groups_table.php` - Creates port_groups table

## Creating a New Migration

### Using Artisan (Recommended)

```bash
php artisan make:migration create_your_table_name_table
```

This will create a file like: `2025_01_15_120000_create_your_table_name_table.php`

### Manual Creation

Create a file in `database/migrations/` with the current timestamp format.

## Migration Structure

### Basic Template for Creating a Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('your_table_name', function (Blueprint $table) {
            // Primary key
            $table->id('your_table_id'); // or $table->increments('your_table_id');
            
            // Foreign keys (if referencing other tables)
            $table->integer('device_id')->unsigned()->index();
            $table->foreign('device_id')
                  ->references('device_id')
                  ->on('devices')
                  ->onDelete('CASCADE'); // or 'SET NULL', 'RESTRICT'
            
            // Columns
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(1);
            $table->integer('value')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Indexes
            $table->index('name');
            $table->unique('name'); // Unique constraint
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('your_table_name');
    }
};
```

### Template for Adding Columns to Existing Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('existing_table', function (Blueprint $table) {
            $table->string('new_column')->nullable();
            $table->boolean('status')->default(1);
            $table->index('new_column');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('existing_table', function (Blueprint $table) {
            $table->dropColumn(['new_column', 'status']);
        });
    }
};
```

## Common Column Types

### Numeric Types
```php
$table->integer('count');                    // Integer
$table->unsignedInteger('device_id');        // Unsigned integer
$table->bigInteger('large_number');         // Big integer
$table->tinyInteger('status');              // Tiny integer (0-255)
$table->decimal('percentage', 5, 2);        // Decimal(5,2)
$table->float('rate');                      // Float
```

### String Types
```php
$table->string('name', 255);                 // VARCHAR(255)
$table->text('description');                // TEXT
$table->longText('details');                // LONGTEXT
$table->char('code', 10);                   // CHAR(10)
```

### Date/Time Types
```php
$table->timestamp('created_at');            // TIMESTAMP
$table->date('date_field');                 // DATE
$table->time('time_field');                  // TIME
$table->dateTime('datetime_field');         // DATETIME
```

### Boolean/Enum Types
```php
$table->boolean('enabled')->default(1);     // TINYINT(1)
$table->enum('status', ['active', 'inactive']); // ENUM
```

### Binary Types
```php
$table->binary('data');                     // BLOB
$table->longBinary('large_data');           // LONGBLOB
```

## Common Patterns in LibreNMS

### 1. Device-Related Tables

Most tables that relate to devices follow this pattern:

```php
Schema::create('your_table', function (Blueprint $table) {
    $table->id('your_table_id');
    $table->integer('device_id')->unsigned()->index();
    $table->foreign('device_id')
          ->references('device_id')
          ->on('devices')
          ->onDelete('CASCADE');
    
    // Your columns here
    
    $table->timestamps(); // or manually add created_at/updated_at
});
```

### 2. Port-Related Tables

```php
Schema::create('your_table', function (Blueprint $table) {
    $table->id('your_table_id');
    $table->integer('port_id')->unsigned()->index();
    $table->foreign('port_id')
          ->references('port_id')
          ->on('ports')
          ->onDelete('CASCADE');
    
    // Your columns here
});
```

### 3. Junction/Pivot Tables (Many-to-Many)

```php
Schema::create('table1_table2', function (Blueprint $table) {
    $table->integer('table1_id')->unsigned();
    $table->integer('table2_id')->unsigned();
    
    $table->foreign('table1_id')
          ->references('id')
          ->on('table1')
          ->onDelete('CASCADE');
    
    $table->foreign('table2_id')
          ->references('id')
          ->on('table2')
          ->onDelete('CASCADE');
    
    $table->primary(['table1_id', 'table2_id']);
    // or
    $table->unique(['table1_id', 'table2_id']);
});
```

### 4. Soft Deletes Pattern

Some tables use a `deleted` column instead of Laravel's soft deletes:

```php
$table->boolean('deleted')->default(0);
$table->index('deleted');
```

## Indexes

### Single Column Index
```php
$table->index('column_name');
```

### Composite Index
```php
$table->index(['column1', 'column2']);
```

### Unique Index
```php
$table->unique('column_name');
$table->unique(['column1', 'column2']); // Composite unique
```

### Named Index
```php
$table->index('column_name', 'index_name');
```

## Foreign Keys

### Basic Foreign Key
```php
$table->foreign('device_id')
      ->references('device_id')
      ->on('devices')
      ->onDelete('CASCADE');
```

### Common onDelete Options
- `CASCADE` - Delete related records when parent is deleted
- `SET NULL` - Set foreign key to NULL when parent is deleted
- `RESTRICT` - Prevent deletion if related records exist
- `NO ACTION` - Similar to RESTRICT

### Common onUpdate Options
- `CASCADE` - Update foreign key when parent key changes
- `RESTRICT` - Prevent update if related records exist

## Running Migrations

### Run All Pending Migrations
```bash
php artisan migrate
```

### Run Migrations in Production
```bash
php artisan migrate --force
```

### Rollback Last Migration
```bash
php artisan migrate:rollback
```

### Rollback All Migrations
```bash
php artisan migrate:reset
```

### Check Migration Status
```bash
php artisan migrate:status
```

## Best Practices

### 1. Always Include `down()` Method
Always provide a way to reverse the migration in case you need to rollback.

### 2. Use Appropriate Data Types
- Use `unsignedInteger` for IDs and foreign keys
- Use `tinyInteger` for small numeric values (0-255)
- Use `decimal` for precise numeric values (percentages, rates)
- Use `text` or `longText` for variable-length strings

### 3. Add Indexes for Performance
Index columns that will be used in:
- WHERE clauses
- JOIN conditions
- ORDER BY clauses
- Foreign keys (automatically indexed, but good to be explicit)

### 4. Use Nullable Appropriately
```php
$table->string('optional_field')->nullable();
$table->string('required_field'); // Not nullable
```

### 5. Set Default Values
```php
$table->boolean('enabled')->default(1);
$table->integer('count')->default(0);
```

### 6. Add Comments for Clarity
```php
$table->string('snmp_idx')->comment('SNMP Index for polling data');
$table->boolean('disabled')->default(0)->comment('Should this be polled');
```

### 7. Handle Existing Tables
If a migration might run on a system where the table already exists:

```php
if (Schema::hasTable('your_table')) {
    // Modify existing table
    Schema::table('your_table', function (Blueprint $table) {
        // Add columns, indexes, etc.
    });
} else {
    // Create new table
    Schema::create('your_table', function (Blueprint $table) {
        // Table definition
    });
}
```

## Example: Complete Migration

Here's a complete example based on LibreNMS patterns:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('custom_metrics', function (Blueprint $table) {
            // Primary key
            $table->id('metric_id');
            
            // Foreign key to devices
            $table->integer('device_id')->unsigned()->index();
            $table->foreign('device_id')
                  ->references('device_id')
                  ->on('devices')
                  ->onDelete('CASCADE');
            
            // Optional foreign key to ports
            $table->integer('port_id')->unsigned()->nullable()->index();
            $table->foreign('port_id')
                  ->references('port_id')
                  ->on('ports')
                  ->onDelete('SET NULL');
            
            // Data columns
            $table->string('metric_name', 100)->index();
            $table->string('metric_type', 50)->comment('Type of metric');
            $table->decimal('value', 10, 2)->nullable();
            $table->bigInteger('timestamp')->nullable()->comment('Unix timestamp');
            $table->text('description')->nullable();
            
            // Status columns
            $table->boolean('enabled')->default(1)->comment('Is this metric active');
            $table->boolean('deleted')->default(0)->index();
            
            // Timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Composite index for common queries
            $table->index(['device_id', 'metric_name', 'deleted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_metrics');
    }
};
```

## Creating Eloquent Models

After creating your table, you'll likely want to create an Eloquent model:

### Basic Model Template

```php
<?php

namespace App\Models;

class YourModel extends BaseModel
{
    public $timestamps = false; // or true if you have timestamps
    protected $primaryKey = 'your_table_id';
    protected $fillable = [
        'device_id',
        'name',
        'value',
        // ... other fillable fields
    ];
    
    // Relationships
    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }
}
```

## Next Steps

1. **Create your migration file** using the templates above
2. **Run the migration** with `php artisan migrate`
3. **Create an Eloquent model** (if needed) in `app/Models/`
4. **Update the API functions** to use your new table
5. **Test thoroughly** before deploying

## Common Issues

### Issue: Foreign key constraint fails
**Solution**: Ensure referenced table exists and column types match exactly.

### Issue: Migration already exists
**Solution**: Check `migrations` table in database. You may need to manually mark it as run or rollback first.

### Issue: Column already exists
**Solution**: Check if column exists before adding:
```php
if (!Schema::hasColumn('table_name', 'column_name')) {
    $table->string('column_name');
}
```

## Resources

- [Laravel Migration Documentation](https://laravel.com/docs/migrations)
- Existing migrations in `database/migrations/` for reference
- Database schema in `database/schema/mysql-schema.sql` for table structures

