<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BIZ-012: Integration APIs - External Integration Model
 *
 * @property int $id
 * @property int $business_id
 * @property string $provider
 * @property string $name
 * @property string $type
 * @property array|null $credentials
 * @property array|null $settings
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $connected_at
 * @property \Illuminate\Support\Carbon|null $last_sync_at
 * @property int $sync_errors
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $business
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\IntegrationSync> $syncs
 * @property-read int|null $syncs_count
 */
class Integration extends Model
{
    use HasFactory;

    // Integration providers
    public const PROVIDER_DEPUTY = 'deputy';

    public const PROVIDER_WHEN_I_WORK = 'when_i_work';

    public const PROVIDER_GUSTO = 'gusto';

    public const PROVIDER_ADP = 'adp';

    public const PROVIDER_GOOGLE_CALENDAR = 'google_calendar';

    public const PROVIDER_OUTLOOK = 'outlook';

    public const PROVIDER_SQUARE_POS = 'square_pos';

    public const PROVIDER_TOAST_POS = 'toast_pos';

    public const PROVIDER_QUICKBOOKS = 'quickbooks';

    public const PROVIDER_XERO = 'xero';

    // Integration types
    public const TYPE_HR = 'hr';

    public const TYPE_SCHEDULING = 'scheduling';

    public const TYPE_PAYROLL = 'payroll';

    public const TYPE_POS = 'pos';

    public const TYPE_CALENDAR = 'calendar';

    public const TYPE_ACCOUNTING = 'accounting';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'provider',
        'name',
        'type',
        'credentials',
        'settings',
        'is_active',
        'connected_at',
        'last_sync_at',
        'sync_errors',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'connected_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'sync_errors' => 'integer',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'credentials',
    ];

    /**
     * Get the business that owns this integration.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get all syncs for this integration.
     */
    public function syncs(): HasMany
    {
        return $this->hasMany(IntegrationSync::class);
    }

    /**
     * Get the latest sync for this integration.
     */
    public function latestSync(): HasMany
    {
        return $this->syncs()->latest()->limit(1);
    }

    /**
     * Scope for active integrations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for integrations by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for integrations by provider.
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Check if the integration is connected.
     */
    public function isConnected(): bool
    {
        return $this->is_active && $this->connected_at !== null;
    }

    /**
     * Check if the integration needs re-authentication.
     */
    public function needsReauth(): bool
    {
        // If too many sync errors, likely needs re-authentication
        return $this->sync_errors >= 5;
    }

    /**
     * Mark the integration as connected.
     */
    public function markConnected(): void
    {
        $this->update([
            'connected_at' => now(),
            'is_active' => true,
            'sync_errors' => 0,
        ]);
    }

    /**
     * Mark the integration as disconnected.
     */
    public function markDisconnected(): void
    {
        $this->update([
            'connected_at' => null,
            'is_active' => false,
            'credentials' => null,
        ]);
    }

    /**
     * Record a sync error.
     */
    public function recordSyncError(): void
    {
        $this->increment('sync_errors');
    }

    /**
     * Reset sync errors after successful sync.
     */
    public function resetSyncErrors(): void
    {
        $this->update([
            'sync_errors' => 0,
            'last_sync_at' => now(),
        ]);
    }

    /**
     * Get available providers with metadata.
     */
    public static function getAvailableProviders(): array
    {
        return [
            self::PROVIDER_DEPUTY => [
                'name' => 'Deputy',
                'type' => self::TYPE_SCHEDULING,
                'description' => 'Workforce management and scheduling',
                'logo' => 'deputy.svg',
                'features' => ['shifts', 'timesheets', 'workers'],
            ],
            self::PROVIDER_WHEN_I_WORK => [
                'name' => 'When I Work',
                'type' => self::TYPE_SCHEDULING,
                'description' => 'Employee scheduling and time tracking',
                'logo' => 'wheniwork.svg',
                'features' => ['shifts', 'timesheets', 'workers'],
            ],
            self::PROVIDER_GUSTO => [
                'name' => 'Gusto',
                'type' => self::TYPE_PAYROLL,
                'description' => 'Payroll and HR platform',
                'logo' => 'gusto.svg',
                'features' => ['payroll', 'workers'],
            ],
            self::PROVIDER_ADP => [
                'name' => 'ADP',
                'type' => self::TYPE_HR,
                'description' => 'Human capital management',
                'logo' => 'adp.svg',
                'features' => ['payroll', 'workers', 'timesheets'],
            ],
            self::PROVIDER_GOOGLE_CALENDAR => [
                'name' => 'Google Calendar',
                'type' => self::TYPE_CALENDAR,
                'description' => 'Sync shifts to Google Calendar',
                'logo' => 'google-calendar.svg',
                'features' => ['shifts'],
            ],
            self::PROVIDER_OUTLOOK => [
                'name' => 'Outlook Calendar',
                'type' => self::TYPE_CALENDAR,
                'description' => 'Sync shifts to Microsoft Outlook',
                'logo' => 'outlook.svg',
                'features' => ['shifts'],
            ],
            self::PROVIDER_SQUARE_POS => [
                'name' => 'Square POS',
                'type' => self::TYPE_POS,
                'description' => 'Point of sale integration',
                'logo' => 'square.svg',
                'features' => ['timesheets', 'sales_data'],
            ],
            self::PROVIDER_TOAST_POS => [
                'name' => 'Toast POS',
                'type' => self::TYPE_POS,
                'description' => 'Restaurant POS integration',
                'logo' => 'toast.svg',
                'features' => ['timesheets', 'sales_data'],
            ],
            self::PROVIDER_QUICKBOOKS => [
                'name' => 'QuickBooks',
                'type' => self::TYPE_ACCOUNTING,
                'description' => 'Accounting and invoicing',
                'logo' => 'quickbooks.svg',
                'features' => ['payroll', 'invoices'],
            ],
            self::PROVIDER_XERO => [
                'name' => 'Xero',
                'type' => self::TYPE_ACCOUNTING,
                'description' => 'Accounting software',
                'logo' => 'xero.svg',
                'features' => ['payroll', 'invoices'],
            ],
        ];
    }

    /**
     * Get provider metadata.
     */
    public function getProviderMetadata(): ?array
    {
        $providers = self::getAvailableProviders();

        return $providers[$this->provider] ?? null;
    }

    /**
     * Get display name for the integration.
     */
    public function getDisplayNameAttribute(): string
    {
        $metadata = $this->getProviderMetadata();

        return $metadata['name'] ?? ucfirst(str_replace('_', ' ', $this->provider));
    }
}
