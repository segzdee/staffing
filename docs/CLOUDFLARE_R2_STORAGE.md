# Cloudflare R2 Storage Configuration

## Overview

This application supports Cloudflare R2 (S3-compatible object storage) for file storage. R2 provides S3-compatible APIs with no egress fees.

## Environment Variables

Add these environment variables to your `.env` file or Laravel Cloud environment settings:

```env
# Cloudflare R2 Configuration
AWS_ACCESS_KEY_ID=your_access_key_id
AWS_SECRET_ACCESS_KEY=your_secret_access_key
AWS_BUCKET=fls-a0a22c8d-0a2a-41a2-8bb2-33298e652ff2
AWS_DEFAULT_REGION=auto
AWS_ENDPOINT=https://367be3a2035528943240074d0096e0cd.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

## Configuration Details

### Required Variables

- **`AWS_ACCESS_KEY_ID`**: Your Cloudflare R2 API token access key ID
- **`AWS_SECRET_ACCESS_KEY`**: Your Cloudflare R2 API token secret access key
- **`AWS_BUCKET`**: Your R2 bucket name
- **`AWS_ENDPOINT`**: Your R2 endpoint URL (format: `https://[account-id].r2.cloudflarestorage.com`)
- **`AWS_DEFAULT_REGION`**: Set to `auto` for R2 (R2 doesn't use regions)
- **`AWS_USE_PATH_STYLE_ENDPOINT`**: Set to `false` for R2 (uses virtual-hosted-style)

### Optional Variables

- **`FILESYSTEM_DRIVER`**: Set to `s3` to use R2 as default storage (default: `local`)
- **`FILESYSTEM_CLOUD`**: Set to `s3` to use R2 as cloud storage (default: `s3`)

## Getting Your R2 Credentials

1. Log in to [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Navigate to **R2** → **Manage R2 API Tokens**
3. Create a new API token with:
   - **Permissions**: Object Read & Write
   - **TTL**: Set expiration or leave blank for no expiration
4. Copy the **Access Key ID** and **Secret Access Key**
5. Get your **Endpoint URL** from your R2 bucket settings

## Laravel Cloud Setup

1. Go to your Laravel Cloud project settings
2. Navigate to **Environment Variables**
3. Add all the required variables listed above
4. Save and redeploy your application

## Usage in Code

```php
use Illuminate\Support\Facades\Storage;

// Store a file
Storage::disk('s3')->put('path/to/file.jpg', $fileContents);

// Get file URL
$url = Storage::disk('s3')->url('path/to/file.jpg');

// Check if file exists
if (Storage::disk('s3')->exists('path/to/file.jpg')) {
    // File exists
}

// Delete a file
Storage::disk('s3')->delete('path/to/file.jpg');
```

## Testing Configuration

Test your R2 configuration with:

```bash
php artisan tinker
```

```php
Storage::disk('s3')->put('test.txt', 'Hello R2!');
echo Storage::disk('s3')->get('test.txt');
Storage::disk('s3')->delete('test.txt');
```

## Troubleshooting

### Error: "Access Denied"
- Verify your API token has correct permissions
- Check that the bucket name matches exactly
- Ensure the endpoint URL is correct

### Error: "Invalid endpoint"
- Verify `AWS_ENDPOINT` includes the full URL with `https://`
- Check that `AWS_USE_PATH_STYLE_ENDPOINT=false`

### Files not uploading
- Check file size limits (R2 supports large files)
- Verify bucket CORS settings if uploading from browser
- Check Laravel Cloud logs for detailed error messages

## CORS Configuration (For Browser Uploads)

If uploading directly from the browser, configure CORS in your R2 bucket:

1. Go to R2 bucket → **Settings** → **CORS Policy**
2. Add CORS policy:
```json
[
  {
    "AllowedOrigins": ["https://www.overtimestaff.com"],
    "AllowedMethods": ["GET", "PUT", "POST", "DELETE", "HEAD"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3600
  }
]
```

## Migration from Local Storage

To migrate existing files from local storage to R2:

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Storage;

$files = Storage::disk('local')->allFiles('public');
foreach ($files as $file) {
    $contents = Storage::disk('local')->get($file);
    Storage::disk('s3')->put($file, $contents);
    echo "Migrated: {$file}\n";
}
```

## Cost Optimization

- R2 has **no egress fees** (unlike S3)
- Only pay for storage and operations
- Consider setting up lifecycle policies for old files
- Use R2's built-in CDN for faster global delivery
