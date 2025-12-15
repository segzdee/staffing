<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $shift_id
 * @property int $uploaded_by
 * @property string $file_name
 * @property string $file_path
 * @property string $file_type
 * @property int $file_size
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Shift $shift
 * @property-read \App\Models\User $uploader
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAttachment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAttachment whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAttachment whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAttachment whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAttachment whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAttachment whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAttachment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftAttachment whereUploadedBy($value)
 * @mixin \Eloquent
 */
class ShiftAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'description',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
