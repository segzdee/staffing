<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
