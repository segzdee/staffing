<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $shift_assignment_id
 * @property int $rater_id
 * @property int $rated_id
 * @property string $rater_type
 * @property int $rating
 * @property string|null $review_text
 * @property array<array-key, mixed>|null $categories
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ShiftAssignment $assignment
 * @property-read \App\Models\User $rated
 * @property-read \App\Models\User $rater
 * @property-read \App\Models\Shift|null $shift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating forBusiness($businessId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating forWorker($workerId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereCategories($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereRatedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereRaterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereRaterType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereReviewText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereShiftAssignmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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

    /**
     * Alias for rater() - used in views for semantic clarity.
     */
    public function ratedBy()
    {
        return $this->rater();
    }

    public function rated()
    {
        return $this->belongsTo(User::class, 'rated_id');
    }

    /**
     * Get the shift associated with this rating (via assignment).
     */
    public function shift()
    {
        return $this->hasOneThrough(
            Shift::class,
            ShiftAssignment::class,
            'id',                    // Foreign key on ShiftAssignment
            'id',                    // Foreign key on Shift
            'shift_assignment_id',   // Local key on Rating
            'shift_id'               // Local key on ShiftAssignment
        );
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
