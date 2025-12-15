<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $file_size_allowed
 * @property string|null $created_at
 * @property string|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminSettings newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminSettings newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminSettings query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminSettings whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminSettings whereFileSizeAllowed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminSettings whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminSettings whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AdminSettings extends Model {

	protected $guarded = ['id'];
	public $timestamps = false;
}
