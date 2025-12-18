<x-layouts.dashboard title="User Management">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">User Management</h1>
                <p class="text-sm text-muted-foreground mt-1">View and manage all platform users</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-card border border-border rounded-xl p-4">
                <p class="text-2xl font-bold text-foreground">{{ number_format($stats['total'] ?? 0) }}</p>
                <p class="text-sm text-muted-foreground">Total Users</p>
            </div>
            <div class="bg-card border border-border rounded-xl p-4">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['workers'] ?? 0) }}</p>
                <p class="text-sm text-muted-foreground">Workers</p>
            </div>
            <div class="bg-card border border-border rounded-xl p-4">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($stats['businesses'] ?? 0) }}</p>
                <p class="text-sm text-muted-foreground">Businesses</p>
            </div>
            <div class="bg-card border border-border rounded-xl p-4">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['agencies'] ?? 0) }}</p>
                <p class="text-sm text-muted-foreground">Agencies</p>
            </div>
            <div class="bg-card border border-border rounded-xl p-4">
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($stats['suspended'] ?? 0) }}</p>
                <p class="text-sm text-muted-foreground">Suspended</p>
            </div>
            <div class="bg-card border border-border rounded-xl p-4">
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['new_today'] ?? 0) }}</p>
                <p class="text-sm text-muted-foreground">New Today</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-card border border-border rounded-xl p-4">
            <form method="GET" action="{{ route('admin.users') }}" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name, email, or phone..."
                        class="w-full px-4 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground placeholder-muted-foreground">
                </div>
                <select name="type" class="px-4 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground">
                    <option value="all" {{ ($type ?? 'all') == 'all' ? 'selected' : '' }}>All Types</option>
                    <option value="worker" {{ ($type ?? '') == 'worker' ? 'selected' : '' }}>Workers</option>
                    <option value="business" {{ ($type ?? '') == 'business' ? 'selected' : '' }}>Businesses</option>
                    <option value="agency" {{ ($type ?? '') == 'agency' ? 'selected' : '' }}>Agencies</option>
                    <option value="admin" {{ ($type ?? '') == 'admin' ? 'selected' : '' }}>Admins</option>
                </select>
                <select name="status" class="px-4 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground">
                    <option value="all" {{ ($status ?? 'all') == 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="active" {{ ($status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ ($status ?? '') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="inactive" {{ ($status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                    Search
                </button>
                @if($search || $type !== 'all' || $status !== 'all')
                    <a href="{{ route('admin.users') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Users Table -->
        <div class="bg-card border border-border rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/50 border-b border-border">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Joined</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Last Active</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($users ?? [] as $user)
                            <tr class="hover:bg-muted/50 transition-colors">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-muted flex items-center justify-center overflow-hidden">
                                            @if($user->avatar && $user->avatar !== 'avatar.jpg')
                                                <img src="{{ asset($user->avatar) }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                                            @else
                                                <span class="text-sm font-medium text-muted-foreground">{{ substr($user->name, 0, 2) }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-medium text-foreground">{{ $user->name }}</p>
                                            <p class="text-sm text-muted-foreground">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        {{ $user->user_type === 'worker' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                        {{ $user->user_type === 'business' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                                        {{ $user->user_type === 'agency' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                        {{ $user->user_type === 'admin' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                                        {{ ucfirst($user->user_type ?? 'Unknown') }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    @if($user->suspended_at)
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                            Suspended
                                        </span>
                                    @elseif($user->is_active ?? true)
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            Active
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm text-muted-foreground">
                                    {{ $user->created_at ? $user->created_at->format('M j, Y') : 'N/A' }}
                                </td>
                                <td class="px-4 py-4 text-sm text-muted-foreground">
                                    {{ $user->updated_at ? $user->updated_at->diffForHumans() : 'N/A' }}
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="#" class="p-2 text-muted-foreground hover:text-foreground transition-colors" title="View User">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="#" class="p-2 text-muted-foreground hover:text-foreground transition-colors" title="Edit User">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        @if(!$user->suspended_at)
                                            <button class="p-2 text-red-500 hover:text-red-700 transition-colors" title="Suspend User">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                            </button>
                                        @else
                                            <button class="p-2 text-green-500 hover:text-green-700 transition-colors" title="Unsuspend User">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center">
                                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <h3 class="mt-4 text-lg font-medium text-foreground">No users found</h3>
                                    <p class="mt-2 text-sm text-muted-foreground">Try adjusting your search or filter criteria.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if(($users ?? collect())->hasPages())
                <div class="p-4 border-t border-border">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <a href="{{ route('admin.users.workers') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-foreground">Workers</p>
                    <p class="text-sm text-muted-foreground">{{ number_format($stats['workers'] ?? 0) }} users</p>
                </div>
            </a>

            <a href="{{ route('admin.users.agencies') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-foreground">Agencies</p>
                    <p class="text-sm text-muted-foreground">{{ number_format($stats['agencies'] ?? 0) }} users</p>
                </div>
            </a>

            <a href="{{ route('admin.users.suspended') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-foreground">Suspended</p>
                    <p class="text-sm text-muted-foreground">{{ number_format($stats['suspended'] ?? 0) }} users</p>
                </div>
            </a>

            <a href="{{ route('admin.users.reports') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-foreground">Reports</p>
                    <p class="text-sm text-muted-foreground">User reports</p>
                </div>
            </a>
        </div>
    </div>
</x-layouts.dashboard>
