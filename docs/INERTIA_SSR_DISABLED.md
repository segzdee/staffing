# Inertia SSR Disabled

## Status

Inertia SSR (Server-Side Rendering) is **disabled** for this application.

## Reason

This application uses **Livewire** for server-side interactivity, not Inertia SSR. The Inertia.js package is installed but SSR is not used.

## Configuration

SSR is disabled in `config/inertia.php`:

```php
'ssr' => [
    'enabled' => env('INERTIA_SSR_ENABLED', false),
    'bundle' => env('INERTIA_SSR_BUNDLE', null),
],
```

## Laravel Cloud

If Laravel Cloud attempts to start SSR automatically, the `inertia:start-ssr` command will exit gracefully with:

```
Inertia SSR is not enabled. Enable it via the `inertia.ssr.enabled` config option.
```

This is expected behavior and will not cause application failures.

## Enabling SSR (If Needed)

If you need to enable SSR in the future:

1. Set environment variable: `INERTIA_SSR_ENABLED=true`
2. Build SSR bundle: `npm run build:ssr`
3. Set bundle path: `INERTIA_SSR_BUNDLE=/path/to/ssr-bundle.js`

## Current Setup

- **Frontend**: Livewire + Alpine.js + Tailwind CSS
- **SSR**: Disabled
- **Inertia.js**: Installed but not used for SSR
