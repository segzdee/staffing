<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'industry',
        'description',
    ];

    public function workers()
    {
        return $this->belongsToMany(User::class, 'worker_skills', 'skill_id', 'worker_id')
            ->withPivot('proficiency_level', 'years_experience', 'verified')
            ->withTimestamps();
    }
}
