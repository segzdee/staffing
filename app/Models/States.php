<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $countries_id
 * @property string $code
 * @property string $name
 * @property int $is_active
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|States newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|States newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|States query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|States whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|States whereCountriesId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|States whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|States whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|States whereName($value)
 *
 * @mixin \Eloquent
 */
class States extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Countries, States>
     */
    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Countries::class, 'countries_id');
    }
}
