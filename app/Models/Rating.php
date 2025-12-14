<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_assignment_id',
        'rater_id',
        'rated_id',
        'rater_type',
        'rating',
        'review_text',
        'categories',
        'response_text',
        'responded_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'categories' => 'array',
        'responded_at' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(ShiftAssignment::class, 'shift_assignment_id');
    }

    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function rated()
    {
        return $this->belongsTo(User::class, 'rated_id');
    }

    public function scopeForWorker($query, $workerId)
    {
        return $query->where('rated_id', $workerId)->where('rater_type', 'business');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('rated_id', $businessId)->where('rater_type', 'worker');
    }
}
