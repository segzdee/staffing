@extends('layouts.dashboard')

@section('title', 'Review Suggestion')
@section('page-title', 'Review Suggestion')
@section('page-subtitle', $suggestion->title)

@section('content')

<div class="max-w-4xl">
    <!-- Back Link -->
    <div class="mb-6">
        <a href="{{ route('admin.improvements.suggestions') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Suggestions
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Suggestion Details -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <div class="text-center">
                            <span class="text-2xl font-bold {{ $suggestion->votes > 0 ? 'text-green-600' : ($suggestion->votes < 0 ? 'text-red-600' : 'text-gray-500') }}">
                                {{ $suggestion->votes }}
                            </span>
                            <span class="block text-xs text-gray-500">votes</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">{{ $suggestion->title }}</h2>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $suggestion->category_label }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $suggestion->priority_color }}">
                                    {{ $suggestion->priority_label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="prose prose-sm max-w-none text-gray-700">
                    {!! nl2br(e($suggestion->description)) !!}
                </div>

                @if($suggestion->expected_impact)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Expected Impact</h3>
                        <div class="prose prose-sm max-w-none text-gray-700">
                            {!! nl2br(e($suggestion->expected_impact)) !!}
                        </div>
                    </div>
                @endif
            </div>

            <!-- Submitter Info -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Submitted By</h3>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center text-xl font-semibold text-gray-600">
                        {{ strtoupper(substr($suggestion->submitter->name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $suggestion->submitter->name ?? 'Unknown User' }}</p>
                        <p class="text-sm text-gray-500">{{ $suggestion->submitter->email ?? '' }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ ucfirst($suggestion->submitter->user_type ?? 'user') }} &middot;
                            Joined {{ $suggestion->submitter->created_at?->format('M Y') ?? 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Votes -->
            @if($suggestion->suggestionVotes->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">
                        Votes ({{ $suggestion->suggestionVotes->count() }})
                    </h3>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        @foreach($suggestion->suggestionVotes as $vote)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <span class="text-sm text-gray-700">{{ $vote->user->name ?? 'Unknown' }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $vote->vote_type === 'up' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $vote->vote_type === 'up' ? '+1' : '-1' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar - Admin Actions -->
        <div class="space-y-6">
            <!-- Current Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Current Status</h3>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $suggestion->status_color }}">
                    {{ $suggestion->status_label }}
                </span>

                <div class="mt-4 space-y-2 text-sm text-gray-500">
                    <p>Submitted: {{ $suggestion->created_at->format('M d, Y \a\t g:i A') }}</p>
                    @if($suggestion->reviewed_at)
                        <p>Reviewed: {{ $suggestion->reviewed_at->format('M d, Y \a\t g:i A') }}</p>
                    @endif
                    @if($suggestion->completed_at)
                        <p>Completed: {{ $suggestion->completed_at->format('M d, Y \a\t g:i A') }}</p>
                    @endif
                </div>
            </div>

            <!-- Update Form -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Update Suggestion</h3>
                <form action="{{ route('admin.improvements.suggestion.update', $suggestion) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" {{ $suggestion->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">Assign To</label>
                        <select name="assigned_to" id="assigned_to" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                            <option value="">Unassigned</option>
                            @foreach($admins as $admin)
                                <option value="{{ $admin->id }}" {{ $suggestion->assigned_to == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-1">Admin Notes</label>
                        <textarea name="admin_notes" id="admin_notes" rows="3"
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm"
                                  placeholder="Internal notes or response to user...">{{ $suggestion->admin_notes }}</textarea>
                    </div>

                    <div id="rejection-reason-container" class="{{ $suggestion->status !== 'rejected' ? 'hidden' : '' }}">
                        <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason</label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="2"
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm"
                                  placeholder="Reason for rejection (visible to user)...">{{ $suggestion->rejection_reason }}</textarea>
                    </div>

                    <button type="submit" class="w-full px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors text-sm">
                        Update Suggestion
                    </button>
                </form>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    @if($suggestion->status === 'submitted')
                        <form action="{{ route('admin.improvements.suggestion.update', $suggestion) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="under_review">
                            <button type="submit" class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm">
                                Start Review
                            </button>
                        </form>
                    @endif

                    @if(in_array($suggestion->status, ['submitted', 'under_review']))
                        <form action="{{ route('admin.improvements.suggestion.update', $suggestion) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                Approve
                            </button>
                        </form>
                    @endif

                    @if($suggestion->status === 'approved')
                        <form action="{{ route('admin.improvements.suggestion.update', $suggestion) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="in_progress">
                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                Start Progress
                            </button>
                        </form>
                    @endif

                    @if($suggestion->status === 'in_progress')
                        <form action="{{ route('admin.improvements.suggestion.update', $suggestion) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm">
                                Mark Completed
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('status').addEventListener('change', function() {
    const container = document.getElementById('rejection-reason-container');
    if (this.value === 'rejected') {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
});
</script>

@endsection
