@extends('layouts.app')

@section('title', 'Improvement Suggestions')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Improvement Suggestions</h1>
            <p class="mt-1 text-gray-600">Help us make OvertimeStaff better by sharing your ideas</p>
        </div>
        @auth
        <div class="mt-4 md:mt-0">
            <a href="{{ route('suggestions.create') }}"
               class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Submit Suggestion
            </a>
        </div>
        @endauth
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" action="{{ route('suggestions.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" id="category"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">All Categories</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ $currentCategory === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $key => $label)
                        @if($key !== 'rejected')
                        <option value="{{ $key }}" {{ $currentStatus === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                <select name="sort" id="sort"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                    <option value="votes" {{ $currentSort === 'votes' ? 'selected' : '' }}>Most Voted</option>
                    <option value="recent" {{ $currentSort === 'recent' ? 'selected' : '' }}>Most Recent</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit"
                        class="w-full px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/80 transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Suggestions List -->
    <div class="space-y-4">
        @forelse($suggestions as $suggestion)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex gap-4">
                    <!-- Vote Column -->
                    <div class="flex flex-col items-center min-w-[60px]" x-data="{ votes: {{ $suggestion->votes }} }">
                        @auth
                            @if($suggestion->submitted_by !== auth()->id())
                                <button type="button"
                                        onclick="voteSuggestion({{ $suggestion->id }}, 'up', this)"
                                        class="p-1 rounded hover:bg-gray-100 {{ ($userVotes[$suggestion->id] ?? null) === 'up' ? 'text-green-600' : 'text-gray-400' }}">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            @endif
                        @endauth
                        <span class="text-lg font-bold text-gray-700 vote-count-{{ $suggestion->id }}">{{ $suggestion->votes }}</span>
                        @auth
                            @if($suggestion->submitted_by !== auth()->id())
                                <button type="button"
                                        onclick="voteSuggestion({{ $suggestion->id }}, 'down', this)"
                                        class="p-1 rounded hover:bg-gray-100 {{ ($userVotes[$suggestion->id] ?? null) === 'down' ? 'text-red-600' : 'text-gray-400' }}">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            @endif
                        @endauth
                    </div>

                    <!-- Content Column -->
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <a href="{{ route('suggestions.show', $suggestion) }}"
                                   class="text-lg font-semibold text-gray-900 hover:text-primary">
                                    {{ $suggestion->title }}
                                </a>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $suggestion->category_color }}">
                                        {{ $suggestion->category_label }}
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $suggestion->status_color }}">
                                        {{ $suggestion->status_label }}
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $suggestion->priority_color }}">
                                        {{ $suggestion->priority_label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 text-gray-600 line-clamp-2">{{ Str::limit($suggestion->description, 200) }}</p>
                        <div class="mt-4 flex items-center text-sm text-gray-500">
                            <span>By {{ $suggestion->submitter->name ?? 'Anonymous' }}</span>
                            <span class="mx-2">&middot;</span>
                            <span>{{ $suggestion->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No suggestions yet</h3>
                <p class="mt-2 text-gray-500">Be the first to share an idea for improving OvertimeStaff.</p>
                @auth
                <div class="mt-6">
                    <a href="{{ route('suggestions.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90">
                        Submit a Suggestion
                    </a>
                </div>
                @endauth
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($suggestions->hasPages())
        <div class="mt-6">
            {{ $suggestions->withQueryString()->links() }}
        </div>
    @endif
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
            document.querySelector(`.vote-count-${suggestionId}`).textContent = data.data.votes;
            // Update button states
            const container = button.closest('.flex.flex-col');
            const upBtn = container.querySelector('button:first-child');
            const downBtn = container.querySelector('button:last-child');

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
