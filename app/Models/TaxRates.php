<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $country
 * @property string|null $iso_state
 * @property numeric $percentage
 * @property string $status
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxRates newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxRates newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxRates query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxRates whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxRates whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxRates whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxRates whereIsoState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxRates whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxRates wherePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxRates whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxRates whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaxRates whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class TaxRates extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country',
        'iso_state',
        'percentage',
        'status',
        'type',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Countries, TaxRates>
     */
    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Countries::class, 'country', 'country_code');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<States, TaxRates>
     */
    public function state(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(States::class, 'iso_state', 'code');
    }
}
