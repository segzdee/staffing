<?php

namespace App\Traits;

use App\Models\User;
use App\Models\WhiteLabelConfig;

/**
 * WhiteLabelMailable Trait
 *
 * AGY-006: Adds white-label branding support to Laravel Mailables.
 * Use this trait in any Mailable class that should support agency white-labeling.
 *
 * Usage:
 *   class ShiftAssignedMail extends Mailable
 *   {
 *       use WhiteLabelMailable;
 *
 *       public function build()
 *       {
 *           $this->detectWhiteLabelConfig($this->worker);
 *           // ... rest of your build method
 *       }
 *   }
 */
trait WhiteLabelMailable
{
    /**
     * White-label configuration for this email.
     */
    protected ?WhiteLabelConfig $whiteLabelConfig = null;

    /**
     * Detect and load white-label config for a user.
     *
     * @param  User|null  $user  The recipient user
     * @param  int|null  $agencyId  Optional specific agency ID
     */
    protected function detectWhiteLabelConfig(?User $user, ?int $agencyId = null): void
    {
        if (! config('whitelabel.enabled') || ! config('whitelabel.email_branding.enabled')) {
            return;
        }

        // If specific agency ID provided, use that
        if ($agencyId) {
            $this->whiteLabelConfig = WhiteLabelConfig::where('agency_id', $agencyId)
                ->where('is_active', true)
                ->first();

            return;
        }

        // Try to detect agency from user relationship
        if ($user) {
            $agencyId = $this->detectAgencyForUser($user);

            if ($agencyId) {
                $this->whiteLabelConfig = WhiteLabelConfig::where('agency_id', $agencyId)
                    ->where('is_active', true)
                    ->first();
            }
        }
    }

    /**
     * Detect the agency a user belongs to.
     */
    protected function detectAgencyForUser(User $user): ?int
    {
        // If user is a worker, check if they belong to an agency
        if ($user->user_type === 'worker') {
            // Check agency_workers pivot table
            $agencyWorker = \DB::table('agency_workers')
                ->where('worker_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($agencyWorker) {
                return $agencyWorker->agency_id;
            }
        }

        // If user is an agency, use their own config
        if ($user->user_type === 'agency') {
            return $user->id;
        }

        return null;
    }

    /**
     * Set the white-label config directly.
     *
     * @return $this
     */
    public function withWhiteLabelConfig(?WhiteLabelConfig $config): self
    {
        $this->whiteLabelConfig = $config;

        return $this;
    }

    /**
     * Set white-label from agency ID.
     *
     * @return $this
     */
    public function forAgency(int $agencyId): self
    {
        $this->whiteLabelConfig = WhiteLabelConfig::where('agency_id', $agencyId)
            ->where('is_active', true)
            ->first();

        return $this;
    }

    /**
     * Get view data with white-label config included.
     *
     * @return array<string, mixed>
     */
    protected function getWhiteLabelViewData(): array
    {
        if (! $this->whiteLabelConfig) {
            return [
                'whiteLabelConfig' => null,
                'brandName' => config('app.name'),
                'brandLogo' => config('mail.logo_url'),
                'supportEmail' => config('mail.from.address'),
                'supportPhone' => null,
                'primaryColor' => '#3B82F6',
                'hidePoweredBy' => false,
            ];
        }

        return [
            'whiteLabelConfig' => $this->whiteLabelConfig,
            'brandName' => $this->whiteLabelConfig->brand_name,
            'brandLogo' => $this->whiteLabelConfig->logo_url,
            'supportEmail' => $this->whiteLabelConfig->support_email,
            'supportPhone' => $this->whiteLabelConfig->support_phone,
            'primaryColor' => $this->whiteLabelConfig->primary_color,
            'secondaryColor' => $this->whiteLabelConfig->secondary_color,
            'accentColor' => $this->whiteLabelConfig->accent_color,
            'hidePoweredBy' => $this->whiteLabelConfig->hide_powered_by,
        ];
    }

    /**
     * Apply white-label from address if configured.
     */
    protected function applyWhiteLabelFrom(): void
    {
        if ($this->whiteLabelConfig && $this->whiteLabelConfig->support_email) {
            $this->from(
                $this->whiteLabelConfig->support_email,
                $this->whiteLabelConfig->brand_name
            );
        }
    }

    /**
     * Merge white-label data with existing view data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mergeWhiteLabelData(array $data): array
    {
        return array_merge($data, $this->getWhiteLabelViewData());
    }
}
