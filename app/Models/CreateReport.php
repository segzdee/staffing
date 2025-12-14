<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class CreateReport extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable=['user_id','title','message','image'];


    public function users(){
        return $this->belongsTo(User::class,"user_id","id")->select("id","name","email","username");
    }
}
