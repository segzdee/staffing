<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReliabilityScoreHistory extends Model
{
    use HasFactory;

    protected $table = 'reliability_score_history';

    protected $fillable = [
        'user_id',
        'score',
        'attendance_score',
        'cancellation_score',
        'punctuality_score',
        'responsiveness_score',
        'metrics',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'score' => 'float',
        'attendance_score' => 'float',
        'cancellation_score' => 'float',
        'punctuality_score' => 'float',
        'responsiveness_score' => 'float',
        'metrics' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get grade for this score
     *
     * @return string
     */
    public function getGradeAttribute()
    {
        return match (true) {
            $this->score >= 90 => 'A',
            $this->score >= 80 => 'B',
            $this->score >= 70 => 'C',
            $this->score >= 60 => 'D',
            default => 'F'
        };
    }

    /**
     * Scope: Latest scores first
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope: For a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
