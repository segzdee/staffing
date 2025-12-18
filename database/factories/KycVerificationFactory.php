<?php

namespace Database\Factories;

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KycVerification>
 */
class KycVerificationFactory extends Factory
{
    protected $model = KycVerification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $documentTypes = [
            KycVerification::DOC_TYPE_PASSPORT,
            KycVerification::DOC_TYPE_DRIVERS_LICENSE,
            KycVerification::DOC_TYPE_NATIONAL_ID,
            KycVerification::DOC_TYPE_RESIDENCE_PERMIT,
        ];

        $countries = ['US', 'GB', 'CA', 'AU', 'DE', 'FR', 'IN', 'NG', 'ZA', 'BR'];

        return [
            'user_id' => User::factory(),
            'status' => KycVerification::STATUS_PENDING,
            'document_type' => $this->faker->randomElement($documentTypes),
            'document_number' => $this->faker->regexify('[A-Z]{2}[0-9]{7}'),
            'document_country' => $this->faker->randomElement($countries),
            'document_expiry' => $this->faker->dateTimeBetween('+1 year', '+5 years'),
            'document_front_path' => 'kyc/test/front_'.$this->faker->uuid.'.jpg',
            'document_back_path' => $this->faker->optional(0.7)->passthrough('kyc/test/back_'.$this->faker->uuid.'.jpg'),
            'selfie_path' => $this->faker->optional(0.8)->passthrough('kyc/test/selfie_'.$this->faker->uuid.'.jpg'),
            'provider' => KycVerification::PROVIDER_MANUAL,
            'attempt_count' => 1,
            'max_attempts' => 3,
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
        ];
    }

    /**
     * Indicate that the verification is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycVerification::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the verification is in review.
     */
    public function inReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycVerification::STATUS_IN_REVIEW,
        ]);
    }

    /**
     * Indicate that the verification is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycVerification::STATUS_APPROVED,
            'reviewed_at' => now(),
            'expires_at' => now()->addYear(),
            'confidence_score' => $this->faker->randomFloat(4, 0.85, 1.0),
        ]);
    }

    /**
     * Indicate that the verification is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycVerification::STATUS_REJECTED,
            'rejection_reason' => $this->faker->randomElement([
                'Document is blurry and unreadable',
                'Document appears to be expired',
                'Selfie does not match document photo',
                'Document authenticity could not be verified',
            ]),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Indicate that the verification is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycVerification::STATUS_EXPIRED,
            'expires_at' => now()->subDay(),
            'document_expiry' => now()->subWeek(),
        ]);
    }

    /**
     * Set the verification to use a passport.
     */
    public function passport(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => KycVerification::DOC_TYPE_PASSPORT,
            'document_back_path' => null, // Passports typically don't have a back
        ]);
    }

    /**
     * Set the verification to use a driver's license.
     */
    public function driversLicense(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => KycVerification::DOC_TYPE_DRIVERS_LICENSE,
        ]);
    }

    /**
     * Set with high confidence score (full KYC level).
     */
    public function fullLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => KycVerification::DOC_TYPE_PASSPORT,
            'selfie_path' => 'kyc/test/selfie_'.$this->faker->uuid.'.jpg',
            'confidence_score' => $this->faker->randomFloat(4, 0.95, 1.0),
        ]);
    }

    /**
     * Set verification to be expiring soon.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KycVerification::STATUS_APPROVED,
            'document_expiry' => now()->addDays(15),
            'expires_at' => now()->addDays(15),
        ]);
    }

    /**
     * Set verification using Onfido provider.
     */
    public function onfido(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => KycVerification::PROVIDER_ONFIDO,
            'provider_applicant_id' => 'onfido_applicant_'.$this->faker->uuid,
            'provider_reference' => 'onfido_check_'.$this->faker->uuid,
        ]);
    }
}
