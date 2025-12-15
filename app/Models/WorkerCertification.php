<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $worker_id
 * @property int $certification_id
 * @property string|null $certification_number
 * @property \Illuminate\Support\Carbon|null $issue_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property string|null $certificate_file
 * @property string $verification_status
 * @property string|null $document_url
 * @property bool $verified
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property int|null $verified_by
 * @property string|null $verification_notes
 * @property int $expiry_reminder_sent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Certification $certification
 * @property-read \App\Models\User $worker
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereCertificateFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereCertificationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereCertificationNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereDocumentUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereExpiryReminderSent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereIssueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereVerificationNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereVerificationStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereVerifiedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerCertification whereWorkerId($value)
 * @mixin \Eloquent
 */
class WorkerCertification extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'certification_id',
        'certification_number',
        'issue_date',
        'expiry_date',
        'document_url',
        'verified',
        'verified_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function certification()
    {
        return $this->belongsTo(Certification::class);
    }

    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isValid()
    {
        return $this->verified && !$this->isExpired();
    }

    /**
     * Check expiry status and return days until expiry.
     * WKR-006: Document Expiry Management
     *
     * @return array
     */
    public function checkExpiry()
    {
        if (!$this->expiry_date) {
            return [
                'has_expiry' => false,
                'is_expired' => false,
                'days_until_expiry' => null,
                'status' => 'no_expiry',
            ];
        }

        $today = \Carbon\Carbon::today();
        $daysUntilExpiry = $today->diffInDays($this->expiry_date, false);

        $isExpired = $daysUntilExpiry < 0;
        $isExpiringSoon = !$isExpired && $daysUntilExpiry <= 60;

        return [
            'has_expiry' => true,
            'is_expired' => $isExpired,
            'is_expiring_soon' => $isExpiringSoon,
            'days_until_expiry' => $isExpired ? 0 : $daysUntilExpiry,
            'expiry_date' => $this->expiry_date,
            'status' => $this->getExpiryStatus($daysUntilExpiry),
        ];
    }

    /**
     * Get expiry status label.
     *
     * @param int $daysUntilExpiry
     * @return string
     */
    protected function getExpiryStatus($daysUntilExpiry)
    {
        if ($daysUntilExpiry < 0) {
            return 'expired';
        } elseif ($daysUntilExpiry <= 7) {
            return 'critical';
        } elseif ($daysUntilExpiry <= 14) {
            return 'urgent';
        } elseif ($daysUntilExpiry <= 30) {
            return 'warning';
        } elseif ($daysUntilExpiry <= 60) {
            return 'notice';
        }

        return 'valid';
    }

    /**
     * Get days until expiry.
     *
     * @return int|null
     */
    public function getDaysUntilExpiry()
    {
        if (!$this->expiry_date) {
            return null;
        }

        return \Carbon\Carbon::today()->diffInDays($this->expiry_date, false);
    }
}
