<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
