@extends('layouts.dashboard')

@section('title', 'Bounced Emails')
@section('page-title', 'Bounced Emails')
@section('page-subtitle', 'Manage email delivery issues')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.email.index') }}" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-900">Bounced Emails</h1>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <!-- Summary -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-yellow-800">{{ $groupedBounces->count() }} unique email addresses with bounces</h3>
                <p class="text-sm text-yellow-700 mt-1">Review these addresses and consider cleaning your email list. Repeated bounces can harm your sender reputation.</p>
            </div>
        </div>
    </div>

    <!-- Grouped Bounces -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bounce Count</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Bounce</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Error</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($groupedBounces as $email => $emailBounces)
                    @php
                        $latestBounce = $emailBounces->first();
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($latestBounce->user)
                            <div class="text-sm text-gray-900">{{ $latestBounce->user->name }}</div>
                            <div class="text-xs text-gray-500">ID: {{ $latestBounce->user->id }}</div>
                            @else
                            <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-bold rounded-full {{ $emailBounces->count() > 2 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $emailBounces->count() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $latestBounce->created_at->format('M d, Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600 truncate max-w-xs">
                                {{ $latestBounce->error_message ?? 'No error message' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.email.logs', ['search' => $email, 'status' => 'bounced']) }}" class="text-sm text-blue-600 hover:text-blue-700">
                                View All
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No bounced emails found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
