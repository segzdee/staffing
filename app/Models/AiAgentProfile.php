<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiAgentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_name',
        'api_key',
        'capabilities',
        'rate_limits',
        'owner_id',
        'is_active',
        'last_activity_at',
        'total_api_calls',
        'total_shifts_created',
        'total_workers_matched',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'rate_limits' => 'array',
        'is_active' => 'boolean',
        'last_activity_at' => 'datetime',
        'total_api_calls' => 'integer',
        'total_shifts_created' => 'integer',
        'total_workers_matched' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function isActive()
    {
        return $this->is_active;
    }

    public function updateActivity()
    {
        $this->update(['last_activity_at' => now()]);
    }
}
