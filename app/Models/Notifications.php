<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Notifications extends Model
{
	protected $guarded = ['id'];
  const UPDATED_AT = null;

	protected $casts = [
		'read' => 'boolean',
		'read_at' => 'datetime',
	];

	public function user()
	{
		return $this->belongsTo(User::class)->first();
	}

	/**
	 * Override to sync read and read_at columns
	 */
	public function setReadAttribute($value)
	{
		$this->attributes['read'] = $value;
		// Sync read_at with read boolean
		$this->attributes['read_at'] = $value ? now() : null;
	}

	/**
	 * Mark notification as read
	 */
	public function markAsRead()
	{
		$this->update([
			'read' => true,
			'read_at' => now(),
		]);
	}

	public static function send($destination, $session_id, $type, $target)
	{
		$user = User::find($destination);

		if ($type == 5 && $user->notify_new_tip == 'no'
				|| $type == 6 && $user->notify_new_ppv == 'no')
				{
					return false;
				}

				self::create([
				'destination' => $destination,
				'author' => $session_id,
				'type' => $type,
				'target' => $target,
				'read' => false,
				'read_at' => null,
			]);
	}

}
