@extends('layouts.app')

@section('title', 'My Suggestions')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">My Suggestions</h1>
            <p class="mt-1 text-gray-600">Track the status of your submitted suggestions</p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
            <a href="{{ route('suggestions.index') }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                View All Suggestions
            </a>
            <a href="{{ route('suggestions.create') }}"
               class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Suggestion
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-2xl font-bold text-gray-900">{{ $suggestions->count() }}</div>
            <div class="text-sm text-gray-500">Total Submitted</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-2xl font-bold text-yellow-600">
                {{ $suggestions->whereIn('status', ['submitted', 'under_review'])->count() }}
            </div>
            <div class="text-sm text-gray-500">Pending Review</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-2xl font-bold text-blue-600">
                {{ $suggestions->whereIn('status', ['approved', 'in_progress'])->count() }}
            </div>
            <div class="text-sm text-gray-500">In Progress</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-2xl font-bold text-green-600">
                {{ $suggestions->where('status', 'completed')->count() }}
            </div>
            <div class="text-sm text-gray-500">Completed</div>
        </div>
    </div>

    <!-- Suggestions List -->
    <div class="space-y-4">
        @forelse($suggestions as $suggestion)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('suggestions.show', $suggestion) }}"
                               class="text-lg font-semibold text-gray-900 hover:text-primary">
                                {{ $suggestion->title }}
                            </a>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $suggestion->status_color }}">
                                {{ $suggestion->status_label }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $suggestion->category_label }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $suggestion->priority_color }}">
                                {{ $suggestion->priority_label }}
                            </span>
                        </div>
                        <p class="mt-3 text-gray-600 line-clamp-2">{{ Str::limit($suggestion->description, 150) }}</p>
                        <div class="mt-4 flex items-center gap-4 text-sm text-gray-500">
                            <span>{{ $suggestion->votes }} votes</span>
                            <span>&middot;</span>
                            <span>{{ $suggestion->created_at->diffForHumans() }}</span>
                            @if($suggestion->reviewed_at)
                                <span>&middot;</span>
                                <span>Reviewed {{ $suggestion->reviewed_at->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        @if($suggestion->canBeEdited())
                            <a href="{{ route('suggestions.edit', $suggestion) }}"
                               class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg"
                               title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                        @endif
                        <a href="{{ route('suggestions.show', $suggestion) }}"
                           class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg"
                           title="View">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Admin Response Preview -->
                @if($suggestion->admin_notes)
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center gap-2 text-sm font-medium text-blue-900 mb-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            Admin Response
                        </div>
                        <p class="text-sm text-blue-800 line-clamp-2">{{ Str::limit($suggestion->admin_notes, 150) }}</p>
                    </div>
                @endif

                <!-- Rejection Reason -->
                @if($suggestion->status === 'rejected' && $suggestion->rejection_reason)
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center gap-2 text-sm font-medium text-red-900 mb-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            Reason for Rejection
                        </div>
                        <p class="text-sm text-red-800 line-clamp-2">{{ Str::limit($suggestion->rejection_reason, 150) }}</p>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No suggestions yet</h3>
                <p class="mt-2 text-gray-500">Share your first idea for improving OvertimeStaff.</p>
                <div class="mt-6">
                    <a href="{{ route('suggestions.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90">
                        Submit a Suggestion
                    </a>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
