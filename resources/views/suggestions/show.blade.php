@extends('layouts.app')

@section('title', $suggestion->title)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('suggestions.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Suggestions
        </a>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6">
            <div class="flex gap-6">
                <!-- Vote Column -->
                <div class="flex flex-col items-center min-w-[80px]">
                    @auth
                        @if($suggestion->submitted_by !== auth()->id() && $suggestion->canBeVotedOn())
                            <button type="button"
                                    onclick="voteSuggestion({{ $suggestion->id }}, 'up', this)"
                                    class="p-2 rounded-lg hover:bg-gray-100 transition-colors {{ $userVote === 'up' ? 'text-green-600' : 'text-gray-400' }}">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        @endif
                    @endauth
                    <span class="text-2xl font-bold text-gray-700 vote-count">{{ $suggestion->votes }}</span>
                    <span class="text-sm text-gray-500">votes</span>
                    @auth
                        @if($suggestion->submitted_by !== auth()->id() && $suggestion->canBeVotedOn())
                            <button type="button"
                                    onclick="voteSuggestion({{ $suggestion->id }}, 'down', this)"
                                    class="p-2 rounded-lg hover:bg-gray-100 transition-colors {{ $userVote === 'down' ? 'text-red-600' : 'text-gray-400' }}">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        @endif
                    @endauth
                </div>

                <!-- Content Column -->
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $suggestion->title }}</h1>

                    <!-- Badges -->
                    <div class="flex flex-wrap gap-2 mt-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                            {{ $suggestion->category_label }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $suggestion->status_color }}">
                            {{ $suggestion->status_label }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $suggestion->priority_color }}">
                            {{ $suggestion->priority_label }} Priority
                        </span>
                    </div>

                    <!-- Meta -->
                    <div class="flex items-center gap-4 mt-4 text-sm text-gray-500">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            {{ $suggestion->submitter->name ?? 'Anonymous' }}
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            {{ $suggestion->created_at->format('M d, Y') }}
                        </div>
                        @if($suggestion->assignee)
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Assigned to: {{ $suggestion->assignee->name }}
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Actions (for owner) -->
                @auth
                    @if($suggestion->submitted_by === auth()->id() && $suggestion->canBeEdited())
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('suggestions.edit', $suggestion) }}"
                               class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg text-center">
                                Edit
                            </a>
                            <form action="{{ route('suggestions.destroy', $suggestion) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this suggestion?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg">
                                    Delete
                                </button>
                            </form>
                        </div>
                    @endif
                @endauth
            </div>
        </div>

        <!-- Description -->
        <div class="border-t border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Description</h2>
            <div class="prose prose-gray max-w-none">
                {!! nl2br(e($suggestion->description)) !!}
            </div>
        </div>

        <!-- Expected Impact -->
        @if($suggestion->expected_impact)
        <div class="border-t border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Expected Impact</h2>
            <div class="prose prose-gray max-w-none">
                {!! nl2br(e($suggestion->expected_impact)) !!}
            </div>
        </div>
        @endif

        <!-- Admin Notes (visible to submitter) -->
        @if($suggestion->admin_notes && auth()->id() === $suggestion->submitted_by)
        <div class="border-t border-gray-200 p-6 bg-blue-50">
            <h2 class="text-lg font-semibold text-blue-900 mb-3">Admin Response</h2>
            <div class="prose prose-gray max-w-none text-blue-800">
                {!! nl2br(e($suggestion->admin_notes)) !!}
            </div>
        </div>
        @endif

        <!-- Rejection Reason -->
        @if($suggestion->status === 'rejected' && $suggestion->rejection_reason)
        <div class="border-t border-gray-200 p-6 bg-red-50">
            <h2 class="text-lg font-semibold text-red-900 mb-3">Reason for Rejection</h2>
            <div class="prose prose-gray max-w-none text-red-800">
                {!! nl2br(e($suggestion->rejection_reason)) !!}
            </div>
        </div>
        @endif

        <!-- Status Timeline -->
        <div class="border-t border-gray-200 p-6 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Timeline</h2>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <div>
                        <span class="font-medium">Submitted</span>
                        <span class="text-gray-500 text-sm ml-2">{{ $suggestion->created_at->format('M d, Y \a\t g:i A') }}</span>
                    </div>
                </div>
                @if($suggestion->reviewed_at)
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                    <div>
                        <span class="font-medium">Reviewed</span>
                        <span class="text-gray-500 text-sm ml-2">{{ $suggestion->reviewed_at->format('M d, Y \a\t g:i A') }}</span>
                    </div>
                </div>
                @endif
                @if($suggestion->completed_at)
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <div>
                        <span class="font-medium">Completed</span>
                        <span class="text-gray-500 text-sm ml-2">{{ $suggestion->completed_at->format('M d, Y \a\t g:i A') }}</span>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@auth
<script>
function voteSuggestion(suggestionId, voteType, button) {
    fetch(`/suggestions/${suggestionId}/vote`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ vote_type: voteType })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelector('.vote-count').textContent = data.data.votes;
            // Update button states
            const container = button.closest('.flex.flex-col');
            const buttons = container.querySelectorAll('button');
            const upBtn = buttons[0];
            const downBtn = buttons[1];

            upBtn.classList.remove('text-green-600');
            downBtn.classList.remove('text-red-600');
            upBtn.classList.add('text-gray-400');
            downBtn.classList.add('text-gray-400');

            if (data.data.user_vote === 'up') {
                upBtn.classList.remove('text-gray-400');
                upBtn.classList.add('text-green-600');
            } else if (data.data.user_vote === 'down') {
                downBtn.classList.remove('text-gray-400');
                downBtn.classList.add('text-red-600');
            }
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
@endauth
@endsection
