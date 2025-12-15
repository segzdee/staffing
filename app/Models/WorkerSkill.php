<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $worker_id
 * @property int $skill_id
 * @property string $proficiency_level
 * @property int $years_experience
 * @property bool $verified
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Skill $skill
 * @property-read \App\Models\User $worker
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerSkill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerSkill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerSkill query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerSkill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerSkill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerSkill whereProficiencyLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerSkill whereSkillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerSkill whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerSkill whereVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerSkill whereWorkerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerSkill whereYearsExperience($value)
 * @mixin \Eloquent
 */
class WorkerSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'skill_id',
        'proficiency_level',
        'years_experience',
        'verified',
    ];

    protected $casts = [
        'years_experience' => 'integer',
        'verified' => 'boolean',
    ];

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}
