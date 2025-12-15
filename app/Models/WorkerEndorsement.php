<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Business endorsements for workers.
 * WKR-010: Enhanced Profile Marketing
 */
class WorkerEndorsement extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'business_id',
        'skill_id',
        'shift_id',
        'endorsement_type',
        'endorsement_text',
        'is_public',
        'featured',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'featured' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the worker being endorsed.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the business providing the endorsement.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the skill being endorsed (if skill-specific).
     */
    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    /**
     * Get the shift that prompted the endorsement (if applicable).
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Scope to get public endorsements only.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get featured endorsements.
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Scope to get skill-specific endorsements.
     */
    public function scopeForSkill($query, $skillId)
    {
        return $query->where('skill_id', $skillId);
    }
}
