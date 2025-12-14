<?php

namespace App\Http\Controllers\Traits;

use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\BusinessProfile;
use App\Models\AgencyProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * User deletion functions used in admin controllers
 */
trait UserDelete
{
    /**
     * Delete a user and all associated data
     *
     * @param int $userId
     * @return bool
     */
    protected function deleteUser($userId)
    {
        $user = User::findOrFail($userId);

        DB::beginTransaction();

        try {
            // Delete profile based on user type
            $this->deleteUserProfile($user);

            // Delete related records
            $this->deleteUserRelatedData($user);

            // Delete the user
            $user->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete user's profile based on their type
     *
     * @param User $user
     * @return void
     */
    protected function deleteUserProfile(User $user)
    {
        switch ($user->user_type) {
            case 'worker':
                WorkerProfile::where('user_id', $user->id)->delete();
                // Delete worker-specific data
                DB::table('worker_skills')->where('user_id', $user->id)->delete();
                DB::table('worker_certifications')->where('user_id', $user->id)->delete();
                DB::table('worker_availability_schedules')->where('user_id', $user->id)->delete();
                DB::table('worker_blackout_dates')->where('user_id', $user->id)->delete();
                DB::table('availability_broadcasts')->where('user_id', $user->id)->delete();
                break;

            case 'business':
                BusinessProfile::where('user_id', $user->id)->delete();
                break;

            case 'agency':
                AgencyProfile::where('user_id', $user->id)->delete();
                break;
        }
    }

    /**
     * Delete user's related data (shifts, applications, etc.)
     *
     * @param User $user
     * @return void
     */
    protected function deleteUserRelatedData(User $user)
    {
        // Delete shift applications
        DB::table('shift_applications')->where('user_id', $user->id)->delete();

        // Delete shift assignments
        DB::table('shift_assignments')->where('worker_id', $user->id)->delete();

        // Delete ratings
        DB::table('ratings')
            ->where('rater_id', $user->id)
            ->orWhere('ratee_id', $user->id)
            ->delete();

        // Delete notifications
        DB::table('notifications')->where('notifiable_id', $user->id)->delete();

        // Delete messages
        DB::table('messages')
            ->where('sender_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->delete();

        // Delete shift swaps
        DB::table('shift_swaps')
            ->where('offerer_id', $user->id)
            ->orWhere('accepter_id', $user->id)
            ->delete();

        // For businesses, also delete their shifts
        if ($user->user_type === 'business') {
            DB::table('shifts')->where('business_id', $user->id)->delete();
            DB::table('shift_templates')->where('business_id', $user->id)->delete();
            DB::table('shift_invitations')->where('business_id', $user->id)->delete();
        }
    }

    /**
     * Soft delete a user (deactivate)
     *
     * @param int $userId
     * @return bool
     */
    protected function softDeleteUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->status = 'deleted';
        $user->email = $user->email . '_deleted_' . time();
        $user->save();

        return true;
    }

    /**
     * Suspend a user account
     *
     * @param int $userId
     * @param string|null $reason
     * @return bool
     */
    protected function suspendUser($userId, $reason = null)
    {
        $user = User::findOrFail($userId);
        $user->status = 'suspended';
        $user->suspension_reason = $reason;
        $user->suspended_at = now();
        $user->save();

        return true;
    }

    /**
     * Reactivate a suspended user
     *
     * @param int $userId
     * @return bool
     */
    protected function reactivateUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->status = 'active';
        $user->suspension_reason = null;
        $user->suspended_at = null;
        $user->save();

        return true;
    }
}
