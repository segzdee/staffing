<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

/**
 * @property-read User|null $users
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreateReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreateReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreateReport onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreateReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreateReport withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreateReport withoutTrashed()
 * @mixin \Eloquent
 */
class CreateReport extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable=['user_id','title','message','image'];


    public function users(){
        return $this->belongsTo(User::class,"user_id","id")->select("id","name","email","username");
    }
}
