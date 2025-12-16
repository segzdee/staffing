<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BIZ-REG-008: Team Permission Model
 *
 * Stores permission definitions and role-permission mappings.
 * Allows dynamic permission management.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string $category
 * @property int $sort_order
 * @property bool $is_sensitive
 * @property bool $default_value
 */
class TeamPermission extends Model
{
    use HasFactory;

    /**
     * Permission categories.
     */
    public const CATEGORIES = [
        'team' => 'Team Management',
        'venues' => 'Venue Management',
        'shifts' => 'Shift Management',
        'workers' => 'Worker Management',
        'billing' => 'Billing & Payments',
        'reports' => 'Reports & Analytics',
        'settings' => 'Settings & Integrations',
    ];

    /**
     * Default permissions list.
     */
    public const DEFAULT_PERMISSIONS = [
        [
            'slug' => 'manage_team',
            'name' => 'Manage Team',
            'description' => 'Invite team members, change roles, suspend or remove members',
            'category' => 'team',
            'sort_order' => 1,
            'is_sensitive' => true,
        ],
        [
            'slug' => 'manage_venues',
            'name' => 'Manage Venues',
            'description' => 'Create, edit, and manage venue locations',
            'category' => 'venues',
            'sort_order' => 2,
            'is_sensitive' => false,
        ],
        [
            'slug' => 'manage_billing',
            'name' => 'Manage Billing',
            'description' => 'View and manage payment methods, invoices, and billing settings',
            'category' => 'billing',
            'sort_order' => 3,
            'is_sensitive' => true,
        ],
        [
            'slug' => 'post_shifts',
            'name' => 'Post Shifts',
            'description' => 'Create and publish new shifts',
            'category' => 'shifts',
            'sort_order' => 4,
            'is_sensitive' => false,
        ],
        [
            'slug' => 'edit_shifts',
            'name' => 'Edit Shifts',
            'description' => 'Modify existing shift details',
            'category' => 'shifts',
            'sort_order' => 5,
            'is_sensitive' => false,
        ],
        [
            'slug' => 'cancel_shifts',
            'name' => 'Cancel Shifts',
            'description' => 'Cancel scheduled shifts',
            'category' => 'shifts',
            'sort_order' => 6,
            'is_sensitive' => false,
        ],
        [
            'slug' => 'approve_workers',
            'name' => 'Approve Workers',
            'description' => 'Review and approve worker applications',
            'category' => 'workers',
            'sort_order' => 7,
            'is_sensitive' => false,
        ],
        [
            'slug' => 'view_reports',
            'name' => 'View Reports',
            'description' => 'Access analytics and reporting dashboards',
            'category' => 'reports',
            'sort_order' => 8,
            'is_sensitive' => false,
        ],
        [
            'slug' => 'manage_favorites',
            'name' => 'Manage Favorites',
            'description' => 'Add and remove workers from favorites list',
            'category' => 'workers',
            'sort_order' => 9,
            'is_sensitive' => false,
        ],
        [
            'slug' => 'manage_integrations',
            'name' => 'Manage Integrations',
            'description' => 'Connect and configure third-party integrations',
            'category' => 'settings',
            'sort_order' => 10,
            'is_sensitive' => true,
        ],
        [
            'slug' => 'view_activity',
            'name' => 'View Activity',
            'description' => 'View team activity logs and audit trails',
            'category' => 'team',
            'sort_order' => 11,
            'is_sensitive' => false,
        ],
    ];

    /**
     * Role permission matrix.
     */
    public const ROLE_PERMISSIONS = [
        'owner' => [
            'manage_team' => true,
            'manage_venues' => true,
            'manage_billing' => true,
            'post_shifts' => true,
            'edit_shifts' => true,
            'cancel_shifts' => true,
            'approve_workers' => true,
            'view_reports' => true,
            'manage_favorites' => true,
            'manage_integrations' => true,
            'view_activity' => true,
        ],
        'admin' => [
            'manage_team' => true,
            'manage_venues' => true,
            'manage_billing' => false,
            'post_shifts' => true,
            'edit_shifts' => true,
            'cancel_shifts' => true,
            'approve_workers' => true,
            'view_reports' => true,
            'manage_favorites' => true,
            'manage_integrations' => false,
            'view_activity' => true,
        ],
        'manager' => [
            'manage_team' => false,
            'manage_venues' => false,
            'manage_billing' => false,
            'post_shifts' => true,
            'edit_shifts' => true,
            'cancel_shifts' => true,
            'approve_workers' => true,
            'view_reports' => true,
            'manage_favorites' => true,
            'manage_integrations' => false,
            'view_activity' => false,
        ],
        'scheduler' => [
            'manage_team' => false,
            'manage_venues' => false,
            'manage_billing' => false,
            'post_shifts' => true,
            'edit_shifts' => true,
            'cancel_shifts' => false,
            'approve_workers' => true,
            'view_reports' => false,
            'manage_favorites' => false,
            'manage_integrations' => false,
            'view_activity' => false,
        ],
        'viewer' => [
            'manage_team' => false,
            'manage_venues' => false,
            'manage_billing' => false,
            'post_shifts' => false,
            'edit_shifts' => false,
            'cancel_shifts' => false,
            'approve_workers' => false,
            'view_reports' => false,
            'manage_favorites' => false,
            'manage_integrations' => false,
            'view_activity' => false,
        ],
    ];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'category',
        'sort_order',
        'is_sensitive',
        'default_value',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_sensitive' => 'boolean',
        'default_value' => 'boolean',
    ];

    protected $appends = [
        'category_label',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get roles that have this permission.
     */
    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class, 'permission_id');
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    // =========================================
    // Query Scopes
    // =========================================

    /**
     * Scope for category.
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for sensitive permissions.
     */
    public function scopeSensitive($query)
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Scope ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Get permissions for a role.
     */
    public static function getPermissionsForRole(string $role): array
    {
        return self::ROLE_PERMISSIONS[$role] ?? self::ROLE_PERMISSIONS['viewer'];
    }

    /**
     * Check if role has permission.
     */
    public static function roleHasPermission(string $role, string $permission): bool
    {
        $permissions = self::getPermissionsForRole($role);

        return $permissions[$permission] ?? false;
    }

    /**
     * Get all roles.
     */
    public static function getRoles(): array
    {
        return [
            'owner' => 'Owner',
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'scheduler' => 'Scheduler',
            'viewer' => 'Viewer',
        ];
    }

    /**
     * Get roles available for invitation (excludes owner).
     */
    public static function getInvitableRoles(): array
    {
        $roles = self::getRoles();
        unset($roles['owner']);

        return $roles;
    }

    /**
     * Get roles that a given role can invite.
     * Users can only invite roles at or below their level.
     */
    public static function getInvitableRolesFor(string $inviterRole): array
    {
        $hierarchy = ['owner', 'admin', 'manager', 'scheduler', 'viewer'];
        $inviterIndex = array_search($inviterRole, $hierarchy);

        if ($inviterIndex === false) {
            return [];
        }

        $availableRoles = [];
        $allRoles = self::getInvitableRoles();

        foreach ($hierarchy as $index => $role) {
            if ($index > $inviterIndex && isset($allRoles[$role])) {
                $availableRoles[$role] = $allRoles[$role];
            }
        }

        // Admin can invite all roles except owner
        if ($inviterRole === 'owner' || $inviterRole === 'admin') {
            return $allRoles;
        }

        return $availableRoles;
    }

    /**
     * Get permission matrix for display.
     */
    public static function getPermissionMatrix(): array
    {
        $matrix = [];
        $permissions = self::DEFAULT_PERMISSIONS;
        $roles = self::getRoles();

        foreach ($permissions as $permission) {
            $matrix[$permission['slug']] = [
                'name' => $permission['name'],
                'description' => $permission['description'],
                'category' => $permission['category'],
                'is_sensitive' => $permission['is_sensitive'] ?? false,
                'roles' => [],
            ];

            foreach (array_keys($roles) as $role) {
                $matrix[$permission['slug']]['roles'][$role] = self::roleHasPermission($role, $permission['slug']);
            }
        }

        return $matrix;
    }

    /**
     * Get permissions grouped by category.
     */
    public static function getPermissionsByCategory(): array
    {
        $grouped = [];

        foreach (self::DEFAULT_PERMISSIONS as $permission) {
            $category = $permission['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'label' => self::CATEGORIES[$category] ?? ucfirst($category),
                    'permissions' => [],
                ];
            }

            $grouped[$category]['permissions'][] = $permission;
        }

        return $grouped;
    }

    /**
     * Seed default permissions.
     */
    public static function seedDefaults(): void
    {
        foreach (self::DEFAULT_PERMISSIONS as $permission) {
            self::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
