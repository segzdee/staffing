<x-layouts.dashboard title="Pending Verifications">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Verification Queue</h1>
                <p class="text-sm text-muted-foreground mt-1">Review and process pending verification requests</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-card border border-border rounded-xl p-4">
                <p class="text-2xl font-bold text-foreground">{{ number_format($stats['total_pending'] ?? 0) }}</p>
                <p class="text-sm text-muted-foreground">Pending</p>
            </div>
            <div class="bg-card border border-border rounded-xl p-4">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['in_review'] ?? 0) }}</p>
                <p class="text-sm text-muted-foreground">In Review</p>
            </div>
            <div class="bg-card border border-border rounded-xl p-4">
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['at_risk'] ?? 0) }}</p>
                <p class="text-sm text-muted-foreground">At Risk (SLA)</p>
            </div>
            <div class="bg-card border border-border rounded-xl p-4">
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($stats['breached'] ?? 0) }}</p>
                <p class="text-sm text-muted-foreground">SLA Breached</p>
            </div>
            <div class="bg-card border border-border rounded-xl p-4">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($stats['identity'] ?? 0) }}</p>
                <p class="text-sm text-muted-foreground">Identity</p>
            </div>
            <div class="bg-card border border-border rounded-xl p-4">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['business'] ?? 0) }}</p>
                <p class="text-sm text-muted-foreground">Business</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-card border border-border rounded-xl p-4">
            <form method="GET" action="{{ route('admin.verifications.pending') }}" class="flex flex-wrap items-center gap-4">
                <select name="type" class="px-4 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground">
                    <option value="all" {{ ($type ?? 'all') == 'all' ? 'selected' : '' }}>All Types</option>
                    <option value="identity" {{ ($type ?? '') == 'identity' ? 'selected' : '' }}>Identity</option>
                    <option value="background_check" {{ ($type ?? '') == 'background_check' ? 'selected' : '' }}>Background Check</option>
                    <option value="certification" {{ ($type ?? '') == 'certification' ? 'selected' : '' }}>Certification</option>
                    <option value="business_license" {{ ($type ?? '') == 'business_license' ? 'selected' : '' }}>Business License</option>
                    <option value="agency" {{ ($type ?? '') == 'agency' ? 'selected' : '' }}>Agency</option>
                </select>
                <select name="sla" class="px-4 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground">
                    <option value="all" {{ ($sla ?? 'all') == 'all' ? 'selected' : '' }}>All SLA Status</option>
                    <option value="on_track" {{ ($sla ?? '') == 'on_track' ? 'selected' : '' }}>On Track</option>
                    <option value="at_risk" {{ ($sla ?? '') == 'at_risk' ? 'selected' : '' }}>At Risk</option>
                    <option value="breached" {{ ($sla ?? '') == 'breached' ? 'selected' : '' }}>Breached</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                    Filter
                </button>
                @if($type !== 'all' || $sla !== 'all')
                    <a href="{{ route('admin.verifications.pending') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground transition-colors">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Verification Queue -->
        <div class="bg-card border border-border rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/50 border-b border-border">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Request</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">SLA</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Submitted</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Priority</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($verifications ?? [] as $verification)
                            <tr class="hover:bg-muted/50 transition-colors {{ $verification->sla_status === 'breached' ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-muted flex items-center justify-center">
                                            @if($verification->verifiable && $verification->verifiable->avatar)
                                                <img src="{{ asset($verification->verifiable->avatar) }}" alt="" class="w-full h-full rounded-full object-cover">
                                            @else
                                                <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-medium text-foreground">{{ $verification->verifiable->name ?? 'Unknown User' }}</p>
                                            <p class="text-sm text-muted-foreground">{{ $verification->verifiable->email ?? '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        {{ $verification->verification_type === 'identity' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                                        {{ $verification->verification_type === 'background_check' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                        {{ $verification->verification_type === 'certification' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                        {{ $verification->verification_type === 'business_license' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                        {{ $verification->verification_type === 'agency' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400' : '' }}">
                                        {{ ucfirst(str_replace('_', ' ', $verification->verification_type ?? 'Unknown')) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        {{ $verification->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                        {{ $verification->status === 'in_review' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}">
                                        {{ ucfirst(str_replace('_', ' ', $verification->status ?? 'Unknown')) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    @php
                                        $slaColor = match($verification->sla_status ?? 'on_track') {
                                            'on_track' => 'text-green-600 dark:text-green-400',
                                            'at_risk' => 'text-yellow-600 dark:text-yellow-400',
                                            'breached' => 'text-red-600 dark:text-red-400',
                                            default => 'text-muted-foreground',
                                        };
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full
                                            {{ $verification->sla_status === 'on_track' ? 'bg-green-500' : '' }}
                                            {{ $verification->sla_status === 'at_risk' ? 'bg-yellow-500' : '' }}
                                            {{ $verification->sla_status === 'breached' ? 'bg-red-500' : '' }}">
                                        </span>
                                        <span class="text-sm {{ $slaColor }}">
                                            @if($verification->sla_deadline)
                                                {{ $verification->sla_deadline->diffForHumans() }}
                                            @else
                                                No deadline
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-muted-foreground">
                                    {{ $verification->submitted_at ? $verification->submitted_at->format('M j, Y g:ia') : 'N/A' }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 bg-muted rounded-full h-2">
                                            <div class="bg-primary h-2 rounded-full" style="width: {{ min(($verification->priority_score ?? 0), 100) }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-foreground">{{ $verification->priority_score ?? 0 }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="#" class="px-3 py-1.5 bg-primary text-primary-foreground rounded-lg text-sm hover:bg-primary/90 transition-colors">
                                            Review
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center">
                                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <h3 class="mt-4 text-lg font-medium text-foreground">No pending verifications</h3>
                                    <p class="mt-2 text-sm text-muted-foreground">All verification requests have been processed.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if(($verifications ?? collect())->hasPages())
                <div class="p-4 border-t border-border">
                    {{ $verifications->links() }}
                </div>
            @endif
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <a href="{{ route('admin.verification.id') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-foreground">ID Verification</p>
                    <p class="text-sm text-muted-foreground">Identity documents</p>
                </div>
            </a>

            <a href="{{ route('admin.verification.documents') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-foreground">Documents</p>
                    <p class="text-sm text-muted-foreground">Uploaded files</p>
                </div>
            </a>

            <a href="{{ route('admin.verification.business') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-foreground">Business</p>
                    <p class="text-sm text-muted-foreground">Business licenses</p>
                </div>
            </a>

            <a href="{{ route('admin.verification.compliance') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-foreground">Compliance</p>
                    <p class="text-sm text-muted-foreground">Compliance review</p>
                </div>
            </a>
        </div>
    </div>
</x-layouts.dashboard>
