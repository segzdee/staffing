# Laravel Cloud Database Configuration

## Database Connection Details

Your Laravel Cloud database is configured as follows:

### Connection String
```
mysql://YOUR_DATABASE_USERNAME:YOUR_DATABASE_PASSWORD@db-a0a22c02-3a24-4c4f-a286-c2ce91c97325.eu-central-1.public.db.laravel.cloud?name=Cloud%20-%20staffing
```

### Environment Variables

Add these to your Laravel Cloud environment variables:

**Option 1: Using Individual Variables (Recommended)**
```env
DB_CONNECTION=mysql
DB_HOST=db-a0a22c02-3a24-4c4f-a286-c2ce91c97325.eu-central-1.public.db.laravel.cloud
DB_PORT=3306
DB_USERNAME=YOUR_DATABASE_USERNAME
DB_PASSWORD=YOUR_DATABASE_PASSWORD
DB_DATABASE=staffing
```

**Option 2: Using DATABASE_URL (Alternative)**
```env
DATABASE_URL=mysql://YOUR_DATABASE_USERNAME:YOUR_DATABASE_PASSWORD@db-a0a22c02-3a24-4c4f-a286-c2ce91c97325.eu-central-1.public.db.laravel.cloud:3306/staffing
```

**Note:** 
- The database name (`staffing`) may need to be confirmed in Laravel Cloud dashboard
- If using `DATABASE_URL`, Laravel will automatically parse it and override individual `DB_*` variables
- Individual variables are recommended for better control and debugging

## Connection Details

- **Host**: `db-a0a22c02-3a24-4c4f-a286-c2ce91c97325.eu-central-1.public.db.laravel.cloud`
- **Port**: `3306`
- **Username**: `YOUR_DATABASE_USERNAME`
- **Password**: `YOUR_DATABASE_PASSWORD`
- **Region**: `eu-central-1` (Europe - Frankfurt)
- **Provider**: Laravel Cloud Managed Database

## Verifying Connection

### In Laravel Cloud Console

Run this command to test the database connection:

```bash
php artisan db:show
```

### Using Tinker

```bash
php artisan tinker
```

```php
DB::connection()->getPdo();
echo "Database connected successfully!";
```

### Check Migration Status

```bash
php artisan migrate:status
```

## Security Notes

⚠️ **Important Security Reminders:**

1. **Never commit credentials to Git** - These credentials are already in your Laravel Cloud environment
2. **Rotate credentials regularly** - Change passwords periodically
3. **Use environment variables** - Never hardcode database credentials in code
4. **Restrict access** - Only allow connections from authorized IPs if possible

## Connection Pooling

Laravel Cloud manages connection pooling automatically. The default configuration in `config/database.php` handles:

- Connection timeouts
- Maximum connections per process
- Persistent connections
- Connection retries

## Troubleshooting

### Connection Refused

If you get "Connection refused" errors:

1. **Check environment variables** are set correctly in Laravel Cloud
2. **Verify database is running** in Laravel Cloud dashboard
3. **Check firewall rules** - Laravel Cloud databases are typically only accessible from Laravel Cloud compute clusters
4. **Verify credentials** - Ensure username and password are correct

### Connection Timeout

If connections timeout:

1. **Check database status** in Laravel Cloud dashboard
2. **Review connection limits** - Laravel Cloud has connection limits per plan
3. **Check application logs** for detailed error messages
4. **Verify network connectivity** between compute and database

### SSL/TLS Connection

Laravel Cloud databases may require SSL connections. If needed, add to `config/database.php`:

```php
'options' => [
    PDO::MYSQL_ATTR_SSL_CA => env('DB_SSL_CA'),
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
],
```

## Local Development Connection

To connect from your local machine (if allowed by Laravel Cloud):

```env
DB_CONNECTION=mysql
DB_HOST=db-a0a22c02-3a24-4c4f-a286-c2ce91c97325.eu-central-1.public.db.laravel.cloud
DB_PORT=3306
DB_USERNAME=YOUR_DATABASE_USERNAME
DB_PASSWORD=YOUR_DATABASE_PASSWORD
DB_DATABASE=staffing
```

**Note:** Public databases may have IP restrictions. Check Laravel Cloud firewall settings.

## Migration Commands

After setting up the connection, run migrations:

```bash
# Check migration status
php artisan migrate:status

# Run pending migrations
php artisan migrate --force

# Rollback last migration (if needed)
php artisan migrate:rollback
```

## Backup and Restore

Laravel Cloud provides automatic backups. To restore:

1. Go to Laravel Cloud dashboard
2. Navigate to Database → Backups
3. Select a backup point
4. Restore to a new database or overwrite existing

## Performance Optimization

For better performance with Laravel Cloud databases:

1. **Use connection pooling** (already configured)
2. **Enable query caching** in `config/database.php`
3. **Use read replicas** if available in your plan
4. **Optimize queries** - Use eager loading to prevent N+1 queries
5. **Index frequently queried columns**

## Monitoring

Monitor database performance in Laravel Cloud dashboard:

- Connection count
- Query performance
- Slow query logs
- Database size
- Backup status
