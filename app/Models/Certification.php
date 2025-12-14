<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'industry',
        'issuing_organization',
        'description',
    ];

    public function workers()
    {
        return $this->belongsToMany(User::class, 'worker_certifications', 'certification_id', 'worker_id')
            ->withPivot('certification_number', 'issue_date', 'expiry_date', 'document_url', 'verified', 'verified_at')
            ->withTimestamps();
    }
}
