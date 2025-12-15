<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $industry
 * @property string|null $issuing_organization
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $workers
 * @property-read int|null $workers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereIndustry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereIssuingOrganization($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
