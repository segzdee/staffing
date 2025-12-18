<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * WhiteLabelConfig Model
 *
 * AGY-006: White-Label Solution for Agencies
 * Allows agencies to customize branding, colors, and domains for their white-label portal.
 *
 * @property int $id
 * @property int $agency_id
 * @property string|null $subdomain
 * @property string|null $custom_domain
 * @property bool $custom_domain_verified
 * @property string $brand_name
 * @property string|null $logo_url
 * @property string|null $favicon_url
 * @property string $primary_color
 * @property string $secondary_color
 * @property string $accent_color
 * @property array|null $theme_config
 * @property string|null $support_email
 * @property string|null $support_phone
 * @property string|null $custom_css
 * @property string|null $custom_js
 * @property array|null $email_templates
 * @property bool $hide_powered_by
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $agency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WhiteLabelDomain> $domains
 * @property-read \App\Models\WhiteLabelDomain|null $activeDomain
 */
class WhiteLabelConfig extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agency_id',
        'subdomain',
        'custom_domain',
        'custom_domain_verified',
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
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'custom_domain_verified' => 'boolean',
        'theme_config' => 'array',
        'email_templates' => 'array',
        'hide_powered_by' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'primary_color' => '#3B82F6',
        'secondary_color' => '#1E40AF',
        'accent_color' => '#10B981',
        'custom_domain_verified' => false,
        'hide_powered_by' => false,
        'is_active' => true,
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the agency (user) that owns this white-label config.
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    /**
     * Get the agency profile for this white-label config.
     */
    public function agencyProfile(): BelongsTo
    {
        return $this->belongsTo(AgencyProfile::class, 'agency_id', 'user_id');
    }

    /**
     * Get all domain verification records.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(WhiteLabelDomain::class);
    }

    /**
     * Get the active verified domain.
     */
    public function activeDomain(): HasOne
    {
        return $this->hasOne(WhiteLabelDomain::class)
            ->where('is_verified', true)
            ->latest('verified_at');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter active configs.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to find by subdomain.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySubdomain($query, string $subdomain)
    {
        return $query->where('subdomain', $subdomain);
    }

    /**
     * Scope to find by custom domain.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCustomDomain($query, string $domain)
    {
        return $query->where('custom_domain', $domain)
            ->where('custom_domain_verified', true);
    }

    // =========================================================================
    // ACCESSORS & MUTATORS
    // =========================================================================

    /**
     * Get the full subdomain URL.
     */
    public function getSubdomainUrlAttribute(): ?string
    {
        if (! $this->subdomain) {
            return null;
        }

        $suffix = config('whitelabel.default_subdomain_suffix', '.overtimestaff.com');
        $protocol = config('app.env') === 'production' ? 'https' : 'http';

        return "{$protocol}://{$this->subdomain}{$suffix}";
    }

    /**
     * Get the full custom domain URL.
     */
    public function getCustomDomainUrlAttribute(): ?string
    {
        if (! $this->custom_domain || ! $this->custom_domain_verified) {
            return null;
        }

        $protocol = config('app.env') === 'production' ? 'https' : 'http';

        return "{$protocol}://{$this->custom_domain}";
    }

    /**
     * Get the active URL (prefers custom domain over subdomain).
     */
    public function getActiveUrlAttribute(): ?string
    {
        return $this->custom_domain_url ?? $this->subdomain_url;
    }

    /**
     * Get CSS variables from theme config.
     *
     * @return array<string, string>
     */
    public function getCssVariablesAttribute(): array
    {
        $variables = [
            '--wl-primary-color' => $this->primary_color,
            '--wl-secondary-color' => $this->secondary_color,
            '--wl-accent-color' => $this->accent_color,
        ];

        // Add extended theme config variables
        if ($this->theme_config) {
            foreach ($this->theme_config as $key => $value) {
                $cssKey = '--wl-'.str_replace('_', '-', $key);
                $variables[$cssKey] = $value;
            }
        }

        return $variables;
    }

    /**
     * Generate inline CSS variables style string.
     */
    public function getCssVariablesStyleAttribute(): string
    {
        $variables = $this->css_variables;
        $styles = [];

        foreach ($variables as $key => $value) {
            $styles[] = "{$key}: {$value}";
        }

        return implode('; ', $styles);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if the white-label config is fully set up.
     */
    public function isFullyConfigured(): bool
    {
        return $this->is_active
            && $this->brand_name
            && ($this->subdomain || ($this->custom_domain && $this->custom_domain_verified));
    }

    /**
     * Check if custom domain is pending verification.
     */
    public function isCustomDomainPending(): bool
    {
        return $this->custom_domain && ! $this->custom_domain_verified;
    }

    /**
     * Check if this config has a verified custom domain.
     */
    public function hasVerifiedCustomDomain(): bool
    {
        return $this->custom_domain && $this->custom_domain_verified;
    }

    /**
     * Get the display name for the white-label portal.
     */
    public function getDisplayName(): string
    {
        return $this->brand_name ?: $this->agency->name ?? 'Portal';
    }

    /**
     * Get support contact info as array.
     *
     * @return array<string, string|null>
     */
    public function getSupportInfo(): array
    {
        return [
            'email' => $this->support_email,
            'phone' => $this->support_phone,
        ];
    }

    /**
     * Check if custom CSS is safe (basic validation).
     */
    public function hasValidCustomCss(): bool
    {
        if (! $this->custom_css) {
            return true;
        }

        $maxLength = config('whitelabel.max_custom_css_length', 50000);

        if (strlen($this->custom_css) > $maxLength) {
            return false;
        }

        // Block potentially dangerous patterns
        $dangerousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/expression\s*\(/i',
            '/behavior\s*:/i',
            '/@import\s+url\s*\(/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $this->custom_css)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get sanitized custom CSS.
     */
    public function getSanitizedCustomCss(): ?string
    {
        if (! $this->custom_css || ! $this->hasValidCustomCss()) {
            return null;
        }

        return $this->custom_css;
    }

    /**
     * Get email template for a specific type.
     *
     * @return array<string, mixed>|null
     */
    public function getEmailTemplate(string $type): ?array
    {
        if (! $this->email_templates) {
            return null;
        }

        return $this->email_templates[$type] ?? null;
    }

    /**
     * Check if a specific email template is customized.
     */
    public function hasCustomEmailTemplate(string $type): bool
    {
        return $this->getEmailTemplate($type) !== null;
    }

    /**
     * Get the logo URL or a default.
     */
    public function getLogoUrlOrDefault(): string
    {
        return $this->logo_url ?? config('app.logo_url', '/images/logo.png');
    }

    /**
     * Get the favicon URL or a default.
     */
    public function getFaviconUrlOrDefault(): string
    {
        return $this->favicon_url ?? '/favicon.ico';
    }

    /**
     * Deactivate this white-label config.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Activate this white-label config.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Mark custom domain as verified.
     */
    public function markCustomDomainVerified(): void
    {
        $this->update(['custom_domain_verified' => true]);
    }

    /**
     * Clear custom domain.
     */
    public function clearCustomDomain(): void
    {
        $this->update([
            'custom_domain' => null,
            'custom_domain_verified' => false,
        ]);
    }

    /**
     * Update branding colors.
     *
     * @param  array<string, string>  $colors
     */
    public function updateColors(array $colors): void
    {
        $allowedColors = ['primary_color', 'secondary_color', 'accent_color'];
        $updateData = [];

        foreach ($allowedColors as $color) {
            if (isset($colors[$color]) && $this->isValidHexColor($colors[$color])) {
                $updateData[$color] = $colors[$color];
            }
        }

        if (! empty($updateData)) {
            $this->update($updateData);
        }
    }

    /**
     * Validate hex color format.
     */
    protected function isValidHexColor(string $color): bool
    {
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color) === 1;
    }
}
