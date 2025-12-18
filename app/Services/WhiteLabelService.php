<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhiteLabelConfig;
use App\Models\WhiteLabelDomain;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WhiteLabelService
 *
 * AGY-006: White-Label Solution Service
 * Handles white-label configuration, domain verification, and branding for agencies.
 */
class WhiteLabelService
{
    /**
     * Cache TTL in seconds.
     */
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get white-label config for a custom domain.
     */
    public function getConfigForDomain(string $domain): ?WhiteLabelConfig
    {
        $cacheKey = "whitelabel:domain:{$domain}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($domain) {
            return WhiteLabelConfig::query()
                ->active()
                ->byCustomDomain($domain)
                ->with('agency', 'agencyProfile')
                ->first();
        });
    }

    /**
     * Get white-label config for a subdomain.
     */
    public function getConfigForSubdomain(string $subdomain): ?WhiteLabelConfig
    {
        $cacheKey = "whitelabel:subdomain:{$subdomain}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($subdomain) {
            return WhiteLabelConfig::query()
                ->active()
                ->bySubdomain($subdomain)
                ->with('agency', 'agencyProfile')
                ->first();
        });
    }

    /**
     * Get white-label config for a request host.
     * Automatically detects whether it's a subdomain or custom domain.
     */
    public function getConfigForHost(string $host): ?WhiteLabelConfig
    {
        $suffix = config('whitelabel.default_subdomain_suffix', '.overtimestaff.com');
        $suffix = ltrim($suffix, '.');

        // Check if it's a subdomain of our main domain
        if (Str::endsWith($host, $suffix)) {
            $subdomain = Str::before($host, ".{$suffix}");

            if ($subdomain && $subdomain !== 'www') {
                return $this->getConfigForSubdomain($subdomain);
            }
        }

        // Otherwise, treat it as a custom domain
        return $this->getConfigForDomain($host);
    }

    /**
     * Create a new white-label config for an agency.
     *
     * @param  array<string, mixed>  $data
     */
    public function createConfig(User $agency, array $data): WhiteLabelConfig
    {
        if ($agency->user_type !== 'agency') {
            throw new \InvalidArgumentException('User must be an agency to create white-label config');
        }

        // Check if agency already has a config
        $existing = WhiteLabelConfig::where('agency_id', $agency->id)->first();
        if ($existing) {
            throw new \RuntimeException('Agency already has a white-label configuration');
        }

        // Generate subdomain if not provided
        if (empty($data['subdomain'])) {
            $data['subdomain'] = $this->generateSubdomain($agency);
        }

        // Validate subdomain uniqueness
        $this->validateSubdomain($data['subdomain']);

        // Set brand name from agency if not provided
        if (empty($data['brand_name'])) {
            $data['brand_name'] = $agency->agencyProfile?->agency_name ?? $agency->name;
        }

        $config = WhiteLabelConfig::create([
            'agency_id' => $agency->id,
            'subdomain' => $data['subdomain'],
            'brand_name' => $data['brand_name'],
            'logo_url' => $data['logo_url'] ?? null,
            'favicon_url' => $data['favicon_url'] ?? null,
            'primary_color' => $data['primary_color'] ?? '#3B82F6',
            'secondary_color' => $data['secondary_color'] ?? '#1E40AF',
            'accent_color' => $data['accent_color'] ?? '#10B981',
            'theme_config' => $data['theme_config'] ?? null,
            'support_email' => $data['support_email'] ?? $agency->email,
            'support_phone' => $data['support_phone'] ?? null,
            'custom_css' => $data['custom_css'] ?? null,
            'email_templates' => $data['email_templates'] ?? null,
            'hide_powered_by' => $data['hide_powered_by'] ?? false,
            'is_active' => true,
        ]);

        $this->clearCache($config);

        Log::info('White-label config created', [
            'agency_id' => $agency->id,
            'config_id' => $config->id,
            'subdomain' => $config->subdomain,
        ]);

        return $config;
    }

    /**
     * Update branding for a white-label config.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBranding(WhiteLabelConfig $config, array $data): void
    {
        $allowedFields = [
            'brand_name',
            'logo_url',
            'favicon_url',
            'primary_color',
            'secondary_color',
            'accent_color',
            'theme_config',
            'support_email',
            'support_phone',
            'custom_css',
            'custom_js',
            'email_templates',
            'hide_powered_by',
        ];

        $updateData = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                // Validate CSS if updating
                if ($field === 'custom_css' && $data[$field]) {
                    $this->validateCustomCss($data[$field]);
                }

                // Validate colors
                if (in_array($field, ['primary_color', 'secondary_color', 'accent_color']) && $data[$field]) {
                    if (! $this->isValidHexColor($data[$field])) {
                        throw new \InvalidArgumentException("Invalid hex color format for {$field}");
                    }
                }

                $updateData[$field] = $data[$field];
            }
        }

        if (! empty($updateData)) {
            $config->update($updateData);
            $this->clearCache($config);

            Log::info('White-label branding updated', [
                'config_id' => $config->id,
                'fields' => array_keys($updateData),
            ]);
        }
    }

    /**
     * Generate a unique subdomain for an agency.
     */
    public function generateSubdomain(User $agency): string
    {
        $baseName = $agency->agencyProfile?->agency_name ?? $agency->name;

        // Create URL-safe subdomain
        $subdomain = Str::slug($baseName);

        // Ensure uniqueness
        $originalSubdomain = $subdomain;
        $counter = 1;

        while (WhiteLabelConfig::where('subdomain', $subdomain)->exists()) {
            $subdomain = "{$originalSubdomain}-{$counter}";
            $counter++;

            if ($counter > 100) {
                // Fallback to random string
                $subdomain = $originalSubdomain.'-'.Str::random(6);
                break;
            }
        }

        return $subdomain;
    }

    /**
     * Set up a custom domain for verification.
     */
    public function setupCustomDomain(WhiteLabelConfig $config, string $domain): WhiteLabelDomain
    {
        if (! config('whitelabel.allowed_custom_domains', true)) {
            throw new \RuntimeException('Custom domains are not enabled');
        }

        // Normalize domain
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = rtrim($domain, '/');

        // Validate domain format
        if (! $this->isValidDomain($domain)) {
            throw new \InvalidArgumentException('Invalid domain format');
        }

        // Check if domain is already in use
        $existingConfig = WhiteLabelConfig::where('custom_domain', $domain)
            ->where('custom_domain_verified', true)
            ->where('id', '!=', $config->id)
            ->first();

        if ($existingConfig) {
            throw new \RuntimeException('This domain is already in use by another agency');
        }

        // Create or update domain verification record
        $domainRecord = WhiteLabelDomain::updateOrCreate(
            [
                'white_label_config_id' => $config->id,
                'domain' => $domain,
            ],
            [
                'verification_token' => $this->generateVerificationToken(),
                'verification_method' => WhiteLabelDomain::METHOD_DNS_TXT,
                'is_verified' => false,
                'verified_at' => null,
            ]
        );

        // Update config with pending domain
        $config->update([
            'custom_domain' => $domain,
            'custom_domain_verified' => false,
        ]);

        $this->clearCache($config);

        Log::info('Custom domain setup initiated', [
            'config_id' => $config->id,
            'domain' => $domain,
            'domain_record_id' => $domainRecord->id,
        ]);

        return $domainRecord;
    }

    /**
     * Verify a domain.
     */
    public function verifyDomain(WhiteLabelDomain $domain): bool
    {
        if ($domain->is_verified) {
            return true;
        }

        if (! $domain->canRetryVerification()) {
            return false;
        }

        $domain->markChecked();

        $verified = $this->checkDNSVerification($domain);

        if ($verified) {
            $domain->markVerified();
            $this->clearCache($domain->config);

            Log::info('Domain verification successful', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain,
                'config_id' => $domain->white_label_config_id,
            ]);

            return true;
        }

        Log::info('Domain verification failed', [
            'domain_id' => $domain->id,
            'domain' => $domain->domain,
            'method' => $domain->verification_method,
        ]);

        return false;
    }

    /**
     * Generate a verification token.
     */
    public function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Check DNS verification for a domain.
     */
    public function checkDNSVerification(WhiteLabelDomain $domain): bool
    {
        try {
            return match ($domain->verification_method) {
                WhiteLabelDomain::METHOD_DNS_TXT => $this->checkDnsTxtRecord($domain),
                WhiteLabelDomain::METHOD_DNS_CNAME => $this->checkDnsCnameRecord($domain),
                WhiteLabelDomain::METHOD_FILE => $this->checkFileVerification($domain),
                default => false,
            };
        } catch (\Exception $e) {
            Log::warning('DNS verification check failed', [
                'domain_id' => $domain->id,
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check DNS TXT record verification.
     */
    protected function checkDnsTxtRecord(WhiteLabelDomain $domain): bool
    {
        $recordName = $domain->dns_txt_record_name;
        $expectedValue = $domain->dns_txt_record_value;

        $records = dns_get_record($recordName, DNS_TXT);

        if (! $records) {
            return false;
        }

        foreach ($records as $record) {
            if (isset($record['txt']) && $record['txt'] === $expectedValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check DNS CNAME record verification.
     */
    protected function checkDnsCnameRecord(WhiteLabelDomain $domain): bool
    {
        $recordName = $domain->dns_cname_record_name;
        $expectedTarget = $domain->dns_cname_target;

        $records = dns_get_record($recordName, DNS_CNAME);

        if (! $records) {
            return false;
        }

        foreach ($records as $record) {
            if (isset($record['target']) && $record['target'] === $expectedTarget) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check file-based verification.
     */
    protected function checkFileVerification(WhiteLabelDomain $domain): bool
    {
        $url = "https://{$domain->domain}{$domain->file_verification_path}";

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $content = @file_get_contents($url, false, $context);

            if ($content === false) {
                return false;
            }

            return trim($content) === $domain->file_verification_content;
        } catch (\Exception $e) {
            Log::warning('File verification check failed', [
                'domain' => $domain->domain,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get theme variables for a white-label config.
     *
     * @return array<string, string>
     */
    public function getThemeVariables(WhiteLabelConfig $config): array
    {
        $variables = [
            'brand_name' => $config->brand_name,
            'logo_url' => $config->getLogoUrlOrDefault(),
            'favicon_url' => $config->getFaviconUrlOrDefault(),
            'primary_color' => $config->primary_color,
            'secondary_color' => $config->secondary_color,
            'accent_color' => $config->accent_color,
            'support_email' => $config->support_email,
            'support_phone' => $config->support_phone,
            'hide_powered_by' => $config->hide_powered_by,
            'css_variables' => $config->css_variables,
        ];

        // Merge extended theme config
        if ($config->theme_config) {
            $variables = array_merge($variables, [
                'theme' => $config->theme_config,
            ]);
        }

        return $variables;
    }

    /**
     * Render custom CSS for a white-label config.
     */
    public function renderCustomCSS(WhiteLabelConfig $config): string
    {
        $css = [];

        // Generate CSS variables
        $css[] = ':root {';
        foreach ($config->css_variables as $var => $value) {
            $css[] = "  {$var}: {$value};";
        }
        $css[] = '}';

        // Add color utility classes
        $css[] = '.wl-bg-primary { background-color: var(--wl-primary-color); }';
        $css[] = '.wl-bg-secondary { background-color: var(--wl-secondary-color); }';
        $css[] = '.wl-bg-accent { background-color: var(--wl-accent-color); }';
        $css[] = '.wl-text-primary { color: var(--wl-primary-color); }';
        $css[] = '.wl-text-secondary { color: var(--wl-secondary-color); }';
        $css[] = '.wl-text-accent { color: var(--wl-accent-color); }';
        $css[] = '.wl-border-primary { border-color: var(--wl-primary-color); }';
        $css[] = '.wl-border-secondary { border-color: var(--wl-secondary-color); }';
        $css[] = '.wl-border-accent { border-color: var(--wl-accent-color); }';

        // Add custom CSS if valid
        if ($config->custom_css && $config->hasValidCustomCss()) {
            $css[] = '';
            $css[] = '/* Custom agency styles */';
            $css[] = $config->custom_css;
        }

        return implode("\n", $css);
    }

    /**
     * Validate a subdomain.
     */
    protected function validateSubdomain(string $subdomain): void
    {
        // Check format
        if (! preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $subdomain)) {
            throw new \InvalidArgumentException('Invalid subdomain format');
        }

        // Check reserved subdomains
        $reserved = [
            'www', 'app', 'api', 'admin', 'mail', 'smtp', 'pop', 'imap',
            'ftp', 'ssh', 'git', 'cdn', 'static', 'assets', 'images',
            'files', 'upload', 'uploads', 'download', 'downloads',
            'support', 'help', 'docs', 'blog', 'status', 'health',
        ];

        if (in_array($subdomain, $reserved)) {
            throw new \InvalidArgumentException('This subdomain is reserved');
        }

        // Check uniqueness
        if (WhiteLabelConfig::where('subdomain', $subdomain)->exists()) {
            throw new \InvalidArgumentException('This subdomain is already in use');
        }
    }

    /**
     * Validate domain format.
     */
    protected function isValidDomain(string $domain): bool
    {
        return filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }

    /**
     * Validate hex color format.
     */
    protected function isValidHexColor(string $color): bool
    {
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color) === 1;
    }

    /**
     * Validate custom CSS.
     */
    protected function validateCustomCss(string $css): void
    {
        $maxLength = config('whitelabel.max_custom_css_length', 50000);

        if (strlen($css) > $maxLength) {
            throw new \InvalidArgumentException("Custom CSS exceeds maximum length of {$maxLength} characters");
        }

        // Check for dangerous patterns
        $dangerousPatterns = [
            '/<script/i' => 'Script tags are not allowed',
            '/javascript:/i' => 'JavaScript URLs are not allowed',
            '/expression\s*\(/i' => 'CSS expressions are not allowed',
            '/behavior\s*:/i' => 'CSS behaviors are not allowed',
            '/@import\s+url\s*\(/i' => 'External imports are not allowed',
        ];

        foreach ($dangerousPatterns as $pattern => $message) {
            if (preg_match($pattern, $css)) {
                throw new \InvalidArgumentException($message);
            }
        }
    }

    /**
     * Clear cache for a white-label config.
     */
    public function clearCache(WhiteLabelConfig $config): void
    {
        Cache::forget("whitelabel:subdomain:{$config->subdomain}");

        if ($config->custom_domain) {
            Cache::forget("whitelabel:domain:{$config->custom_domain}");
        }
    }

    /**
     * Get all active white-label configs.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, WhiteLabelConfig>
     */
    public function getAllActiveConfigs()
    {
        return WhiteLabelConfig::query()
            ->active()
            ->with('agency', 'agencyProfile')
            ->get();
    }

    /**
     * Get config by agency.
     */
    public function getConfigByAgency(User $agency): ?WhiteLabelConfig
    {
        return WhiteLabelConfig::where('agency_id', $agency->id)->first();
    }

    /**
     * Delete a white-label config.
     */
    public function deleteConfig(WhiteLabelConfig $config): void
    {
        $this->clearCache($config);

        // Delete domain records first
        $config->domains()->delete();

        $config->delete();

        Log::info('White-label config deleted', [
            'config_id' => $config->id,
            'agency_id' => $config->agency_id,
        ]);
    }

    /**
     * Toggle white-label config active status.
     */
    public function toggleStatus(WhiteLabelConfig $config): void
    {
        $config->update(['is_active' => ! $config->is_active]);
        $this->clearCache($config);
    }

    /**
     * Update subdomain.
     */
    public function updateSubdomain(WhiteLabelConfig $config, string $newSubdomain): void
    {
        $this->validateSubdomain($newSubdomain);

        $oldSubdomain = $config->subdomain;

        $config->update(['subdomain' => $newSubdomain]);

        // Clear old cache
        Cache::forget("whitelabel:subdomain:{$oldSubdomain}");
        $this->clearCache($config);

        Log::info('White-label subdomain updated', [
            'config_id' => $config->id,
            'old_subdomain' => $oldSubdomain,
            'new_subdomain' => $newSubdomain,
        ]);
    }

    /**
     * Remove custom domain from config.
     */
    public function removeCustomDomain(WhiteLabelConfig $config): void
    {
        $domain = $config->custom_domain;

        // Delete domain verification records
        WhiteLabelDomain::where('white_label_config_id', $config->id)->delete();

        $config->clearCustomDomain();
        $this->clearCache($config);

        Log::info('Custom domain removed', [
            'config_id' => $config->id,
            'domain' => $domain,
        ]);
    }

    /**
     * Get pending domain verifications that need checking.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, WhiteLabelDomain>
     */
    public function getPendingDomainVerifications()
    {
        return WhiteLabelDomain::query()
            ->needsRecheck()
            ->with('config')
            ->get();
    }

    /**
     * Process all pending domain verifications.
     * Should be called from a scheduled job.
     *
     * @return array<string, int>
     */
    public function processPendingVerifications(): array
    {
        $domains = $this->getPendingDomainVerifications();
        $results = ['checked' => 0, 'verified' => 0, 'failed' => 0];

        foreach ($domains as $domain) {
            $results['checked']++;

            if ($this->verifyDomain($domain)) {
                $results['verified']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }
}
