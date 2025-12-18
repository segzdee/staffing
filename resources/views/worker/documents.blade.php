<x-layouts.dashboard title="My Documents">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">My Documents</h1>
                <p class="text-sm text-muted-foreground mt-1">Manage your uploaded documents and certifications</p>
            </div>
            <a href="{{ route('worker.certifications') }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Certification
            </a>
        </div>

        <!-- Status Banner -->
        @if(($documentStatus['pending_verification'] ?? 0) > 0)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        You have {{ $documentStatus['pending_verification'] }} document(s) pending verification.
                    </p>
                </div>
            </div>
        @endif

        <!-- Document Status Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Identity Verification -->
            <div class="bg-card border border-border rounded-xl p-4">
                <div class="flex items-center gap-3 mb-3">
                    @if($documentStatus['identity_verified'] ?? false)
                        <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                    @else
                        <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    @endif
                    <div>
                        <p class="font-medium text-foreground">Identity Verification</p>
                        <p class="text-sm text-muted-foreground">
                            @if($documentStatus['identity_verified'] ?? false)
                                Verified
                            @elseif(($identityVerification?->status ?? '') === 'pending')
                                Under Review
                            @else
                                Not Verified
                            @endif
                        </p>
                    </div>
                </div>
                @if(!($documentStatus['identity_verified'] ?? false))
                    <a href="#" class="text-sm text-primary hover:underline">Start Verification</a>
                @endif
            </div>

            <!-- Certifications -->
            <div class="bg-card border border-border rounded-xl p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-foreground">Certifications</p>
                        <p class="text-sm text-muted-foreground">
                            {{ $documentStatus['certifications_verified'] ?? 0 }} verified of {{ $documentGroups['certifications']['count'] ?? 0 }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('worker.certifications') }}" class="text-sm text-primary hover:underline">Manage Certifications</a>
            </div>

            <!-- Right to Work -->
            <div class="bg-card border border-border rounded-xl p-4">
                <div class="flex items-center gap-3 mb-3">
                    @php
                        $rtwDocs = $documentGroups['rtw']['documents'] ?? collect();
                        $hasRtw = $rtwDocs->count() > 0;
                    @endphp
                    @if($hasRtw)
                        <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    @else
                        <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    @endif
                    <div>
                        <p class="font-medium text-foreground">Right to Work</p>
                        <p class="text-sm text-muted-foreground">
                            {{ $hasRtw ? 'Submitted' : 'Not Submitted' }}
                        </p>
                    </div>
                </div>
                @if(!$hasRtw)
                    <a href="#" class="text-sm text-primary hover:underline">Upload Documents</a>
                @endif
            </div>
        </div>

        <!-- Identity Documents Section -->
        <div class="bg-card border border-border rounded-xl overflow-hidden">
            <div class="p-4 border-b border-border">
                <h3 class="font-semibold text-foreground">{{ $documentGroups['identity']['title'] }}</h3>
                <p class="text-sm text-muted-foreground">{{ $documentGroups['identity']['description'] }}</p>
            </div>
            @if(($documentGroups['identity']['documents'] ?? collect())->count() > 0)
                <div class="divide-y divide-border">
                    @foreach($documentGroups['identity']['documents'] as $document)
                        <div class="p-4 flex items-center justify-between hover:bg-muted/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-muted flex items-center justify-center">
                                    <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-foreground">{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        Uploaded {{ $document->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $document->status === 'verified' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                {{ $document->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                {{ $document->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                                {{ ucfirst($document->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-foreground">No identity documents</h3>
                    <p class="mt-2 text-sm text-muted-foreground">Verify your identity to unlock more opportunities.</p>
                    <button class="mt-4 inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                        Start Verification
                    </button>
                </div>
            @endif
        </div>

        <!-- Certifications Section -->
        <div class="bg-card border border-border rounded-xl overflow-hidden">
            <div class="p-4 border-b border-border flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-foreground">{{ $documentGroups['certifications']['title'] }}</h3>
                    <p class="text-sm text-muted-foreground">{{ $documentGroups['certifications']['description'] }}</p>
                </div>
                <a href="{{ route('worker.certifications') }}" class="text-sm text-primary hover:underline">View All</a>
            </div>
            @if(($documentGroups['certifications']['documents'] ?? collect())->count() > 0)
                <div class="divide-y divide-border">
                    @foreach(($documentGroups['certifications']['documents'] ?? collect())->take(5) as $cert)
                        <div class="p-4 flex items-center justify-between hover:bg-muted/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-muted flex items-center justify-center">
                                    <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-foreground">{{ $cert->certification?->name ?? $cert->certificationType?->name ?? 'Certification' }}</p>
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                        @if($cert->certification_number)
                                            <span>#{{ $cert->certification_number }}</span>
                                        @endif
                                        @if($cert->expiry_date)
                                            <span>|</span>
                                            <span class="{{ \Carbon\Carbon::parse($cert->expiry_date)->isPast() ? 'text-red-500' : '' }}">
                                                Expires {{ \Carbon\Carbon::parse($cert->expiry_date)->format('M j, Y') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $cert->verified ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                                {{ $cert->verified ? 'Verified' : 'Pending' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-foreground">No certifications</h3>
                    <p class="mt-2 text-sm text-muted-foreground">Add your professional certifications to stand out to employers.</p>
                    <a href="{{ route('worker.certifications') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                        Add Certification
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-layouts.dashboard>
