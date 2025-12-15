<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $country_code
 * @property string $name
 * @property string|null $currency_code
 * @property string|null $phone_code
 * @property int $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\States> $states
 * @property-read int|null $states_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countries newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countries newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countries query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countries whereCountryCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countries whereCurrencyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countries whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countries whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countries whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countries wherePhoneCode($value)
 * @mixin \Eloquent
 */
class Countries extends Model {

	protected $guarded = ['id'];
	public $timestamps = false;

	public function users()
	{
		return $this->hasMany(User::class);
	}

	public function states()
	{
		return $this->hasMany(States::class);
	}

}
