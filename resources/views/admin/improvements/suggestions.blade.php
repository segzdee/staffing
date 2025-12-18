@extends('layouts.dashboard')

@section('title', 'Manage Suggestions')
@section('page-title', 'Improvement Suggestions')
@section('page-subtitle', 'Review and manage user-submitted suggestions')

@section('content')

<!-- Filters -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" action="{{ route('admin.improvements.suggestions') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                <option value="">All Statuses</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" {{ $filters['status'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select name="category" id="category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                <option value="">All Categories</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ $filters['category'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
            <select name="priority" id="priority" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                <option value="">All Priorities</option>
                @foreach($priorities as $key => $label)
                    <option value="{{ $key }}" {{ $filters['priority'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
            <select name="assigned_to" id="assigned_to" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                <option value="">All</option>
                <option value="unassigned" {{ $filters['assigned_to'] === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                @foreach($admins as $admin)
                    <option value="{{ $admin->id }}" {{ (string)$filters['assigned_to'] === (string)$admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
            <select name="sort" id="sort" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                <option value="recent" {{ $filters['sort'] === 'recent' ? 'selected' : '' }}>Most Recent</option>
                <option value="votes" {{ $filters['sort'] === 'votes' ? 'selected' : '' }}>Most Votes</option>
                <option value="priority" {{ $filters['sort'] === 'priority' ? 'selected' : '' }}>Priority</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/80 transition-colors text-sm">
                Filter
            </button>
        </div>
    </form>
</div>

<!-- Suggestions Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Votes</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Suggestion</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($suggestions as $suggestion)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="text-lg font-bold {{ $suggestion->votes > 0 ? 'text-green-600' : ($suggestion->votes < 0 ? 'text-red-600' : 'text-gray-500') }}">
                                {{ $suggestion->votes }}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="max-w-xs">
                                <a href="{{ route('admin.improvements.suggestion', $suggestion) }}" class="font-medium text-gray-900 hover:text-primary">
                                    {{ $suggestion->title }}
                                </a>
                                <p class="text-sm text-gray-500 truncate">{{ Str::limit($suggestion->description, 60) }}</p>
                                <p class="text-xs text-gray-400 mt-1">by {{ $suggestion->submitter->name ?? 'Unknown' }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $suggestion->category_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $suggestion->priority_color }}">
                                {{ $suggestion->priority_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $suggestion->status_color }}">
                                {{ $suggestion->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $suggestion->assignee->name ?? '-' }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $suggestion->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.improvements.suggestion', $suggestion) }}"
                               class="text-primary hover:text-primary/80">
                                Review
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                            No suggestions found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($suggestions->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $suggestions->withQueryString()->links() }}
        </div>
    @endif
</div>

@endsection
