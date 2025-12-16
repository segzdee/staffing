<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BIZ-REG-008: Role Permission Pivot Model
 *
 * Links roles to permissions in the database.
 *
 * @property int $id
 * @property string $role
 * @property int $permission_id
 * @property bool $granted
 */
class RolePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'permission_id',
        'granted',
    ];

    protected $casts = [
        'granted' => 'boolean',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the permission.
     */
    public function permission()
    {
        return $this->belongsTo(TeamPermission::class, 'permission_id');
    }

    // =========================================
    // Query Scopes
    // =========================================

    /**
     * Scope for role.
     */
    public function scopeForRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope for granted permissions.
     */
    public function scopeGranted($query)
    {
        return $query->where('granted', true);
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Sync role permissions from matrix.
     */
    public static function syncFromMatrix(): void
    {
        $matrix = TeamPermission::ROLE_PERMISSIONS;
        $permissions = TeamPermission::all()->keyBy('slug');

        foreach ($matrix as $role => $rolePermissions) {
            foreach ($rolePermissions as $slug => $granted) {
                if ($permission = $permissions->get($slug)) {
                    self::updateOrCreate(
                        [
                            'role' => $role,
                            'permission_id' => $permission->id,
                        ],
                        ['granted' => $granted]
                    );
                }
            }
        }
    }

    /**
     * Get all permissions for a role.
     */
    public static function getPermissionsForRole(string $role): array
    {
        return self::forRole($role)
            ->granted()
            ->with('permission')
            ->get()
            ->pluck('permission.slug')
            ->toArray();
    }
}
