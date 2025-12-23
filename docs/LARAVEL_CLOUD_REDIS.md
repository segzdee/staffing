# Laravel Cloud Redis Configuration

## Redis Connection Details

Your Laravel Cloud Redis cache is configured with TLS encryption and username authentication.

### Connection Command
```bash
redis-cli -h cache-a0a22d2a-b96f-4397-ba7f-1110e25f50c1.eu-central-1.public.caches.laravel.cloud \
  -p 6379 \
  --user application \
  --pass BYeRt00Hn3CKLojaGVys \
  --sni cache-a0a22d2a-b96f-4397-ba7f-1110e25f50c1.eu-central-1.public.caches.laravel.cloud \
  --tls
```

### Environment Variables

Add these to your Laravel Cloud environment variables:

```env
REDIS_HOST=tls://cache-a0a22d2a-b96f-4397-ba7f-1110e25f50c1.eu-central-1.public.caches.laravel.cloud
REDIS_USERNAME=application
REDIS_PORT=6379
REDIS_PASSWORD=BYeRt00Hn3CKLojaGVys
REDIS_DB=0
REDIS_CACHE_DB=0
```

### Connection Details

- **Host**: `cache-a0a22d2a-b96f-4397-ba7f-1110e25f50c1.eu-central-1.public.caches.laravel.cloud`
- **Port**: `6379`
- **Username**: `application`
- **Password**: `BYeRt00Hn3CKLojaGVys`
- **Scheme**: `tls` (TLS encryption enabled)
- **SNI**: `cache-a0a22d2a-b96f-4397-ba7f-1110e25f50c1.eu-central-1.public.caches.laravel.cloud`
- **Region**: `eu-central-1` (Europe - Frankfurt)
- **Provider**: Laravel Cloud Managed Redis

## Configuration Details

### TLS Support

Laravel Cloud Redis uses TLS encryption. The configuration automatically:
- Detects `tls://` or `rediss://` prefix in `REDIS_HOST`
- Sets the `scheme` to `tls` for secure connections
- Strips the protocol prefix from the hostname

### Username Authentication

Redis 6+ ACL (Access Control List) is supported:
- Username is set via `REDIS_USERNAME` environment variable
- Password authentication is still required via `REDIS_PASSWORD`
- Both are used together for authentication

### Database Separation

- **Default Database (0)**: General Redis operations, queues, sessions
- **Cache Database (0)**: Dedicated cache store (can be changed via `REDIS_CACHE_DB`)

**Note:** Both databases are set to `0` by default. You can use different databases if needed.

## Verifying Connection

### In Laravel Cloud Console

Run this command to test the Redis connection:

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Redis;

// Test connection
Redis::ping();
echo "Redis connected successfully!";

// Test cache
Cache::put('test_key', 'test_value', 60);
echo Cache::get('test_key');

// Test queue
Redis::connection('default')->ping();
```

### Using Redis CLI

Connect directly using the redis-cli command:

```bash
redis-cli -h cache-a0a22d2a-b96f-4397-ba7f-1110e25f50c1.eu-central-1.public.caches.laravel.cloud \
  -p 6379 \
  --user application \
  --pass BYeRt00Hn3CKLojaGVys \
  --sni cache-a0a22d2a-b96f-4397-ba7f-1110e25f50c1.eu-central-1.public.caches.laravel.cloud \
  --tls
```

Once connected, test with:
```redis
PING
# Should return: PONG

SET test_key "Hello Redis"
GET test_key
# Should return: "Hello Redis"
```

## Cache Configuration

After setting up Redis, update your cache driver:

```env
CACHE_DRIVER=redis
```

Then clear and rebuild cache:

```bash
php artisan cache:clear
php artisan config:cache
```

## Session Configuration

Update session driver to use Redis:

```env
SESSION_DRIVER=redis
SESSION_CONNECTION=default
```

## Queue Configuration

Queue is already configured to use Redis:

```env
QUEUE_CONNECTION=redis
```

## Security Notes

⚠️ **Important Security Reminders:**

1. **Never commit credentials to Git** - These credentials are already in your Laravel Cloud environment
2. **Rotate credentials regularly** - Change passwords periodically
3. **Use environment variables** - Never hardcode Redis credentials in code
4. **TLS encryption** - All connections are encrypted via TLS
5. **ACL authentication** - Username + password provides additional security

## Troubleshooting

### Connection Refused

If you get "Connection refused" errors:

1. **Check environment variables** are set correctly in Laravel Cloud
2. **Verify Redis is running** in Laravel Cloud dashboard
3. **Check firewall rules** - Laravel Cloud Redis is typically only accessible from Laravel Cloud compute clusters
4. **Verify credentials** - Ensure username and password are correct
5. **Check TLS** - Ensure `tls://` prefix is in `REDIS_HOST`

### TLS Connection Errors

If TLS connections fail:

1. **Verify host format** - Should be `tls://hostname` or `rediss://hostname`
2. **Check SNI** - Ensure SNI matches the hostname
3. **Verify port** - Should be `6379` (standard Redis port)
4. **Check phpredis extension** - Ensure `phpredis` extension is installed and supports TLS

### Authentication Errors

If authentication fails:

1. **Check username** - Verify `REDIS_USERNAME` is set correctly
2. **Check password** - Verify `REDIS_PASSWORD` is correct
3. **Verify ACL** - Ensure the user has proper permissions in Redis
4. **Test with redis-cli** - Use the provided redis-cli command to test authentication

### Timeout Errors

If connections timeout:

1. **Check Redis status** in Laravel Cloud dashboard
2. **Review connection limits** - Laravel Cloud has connection limits per plan
3. **Check application logs** for detailed error messages
4. **Verify network connectivity** between compute and Redis
5. **Increase timeout** - Set `REDIS_READ_TIMEOUT` environment variable (default: 60 seconds)

## Performance Optimization

For better performance with Laravel Cloud Redis:

1. **Use connection pooling** (already configured)
2. **Enable persistent connections** - Reduces connection overhead
3. **Use appropriate TTLs** - Don't cache indefinitely
4. **Monitor memory usage** - Redis is in-memory, monitor usage in Laravel Cloud dashboard
5. **Use pipelining** - For bulk operations, use Redis pipelines

## Monitoring

Monitor Redis performance in Laravel Cloud dashboard:

- Connection count
- Memory usage
- Commands per second
- Key count
- Hit/miss ratio (if available)

## Local Development Connection

To connect from your local machine (if allowed by Laravel Cloud):

```env
REDIS_HOST=tls://cache-a0a22d2a-b96f-4397-ba7f-1110e25f50c1.eu-central-1.public.caches.laravel.cloud
REDIS_USERNAME=application
REDIS_PORT=6379
REDIS_PASSWORD=BYeRt00Hn3CKLojaGVys
REDIS_DB=0
REDIS_CACHE_DB=0
```

**Note:** Public Redis instances may have IP restrictions. Check Laravel Cloud firewall settings.

## Testing Cache, Session, and Queue

After configuration, test all three:

### Test Cache
```php
Cache::put('test', 'value', 60);
echo Cache::get('test');
```

### Test Session
```php
session(['test' => 'value']);
echo session('test');
```

### Test Queue
```php
dispatch(new \App\Jobs\TestJob());
// Check Horizon dashboard or queue:work output
```
