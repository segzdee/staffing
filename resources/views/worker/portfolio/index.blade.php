@extends('layouts.dashboard')

@section('title', 'My Portfolio')
@section('page-title', 'Portfolio & Showcase')
@section('page-subtitle', 'Manage your portfolio items and public profile visibility')

@section('content')
<div class="space-y-6">
    <!-- Analytics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Profile Views</span>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($analytics['total_views'] ?? 0) }}</p>
            <p class="text-xs text-gray-500">Last 30 days</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Business Views</span>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($analytics['business_views'] ?? 0) }}</p>
            <p class="text-xs text-gray-500">From hiring managers</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Conversions</span>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($analytics['conversions'] ?? 0) }}</p>
            <p class="text-xs text-gray-500">Views to applications</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Conversion Rate</span>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $analytics['conversion_rate'] ?? 0 }}%</p>
            <p class="text-xs text-gray-500">Industry avg: 3.2%</p>
        </div>
    </div>

    <!-- Public Profile Toggle -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Public Profile</h3>
                <p class="text-sm text-gray-600 mt-1">
                    @if($publicProfileEnabled)
                        Your profile is visible at:
                        <a href="{{ route('profile.public', $publicProfileSlug) }}" target="_blank" class="text-blue-600 hover:underline">
                            {{ route('profile.public', $publicProfileSlug) }}
                        </a>
                    @else
                        Enable public profile to share with potential employers
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-4">
                @if($publicProfileEnabled)
                    <a href="{{ route('worker.portfolio.preview') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Preview
                    </a>
                @endif

                <form action="{{ route('worker.portfolio.visibility') }}" method="POST" class="inline">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="enabled" value="{{ $publicProfileEnabled ? '0' : '1' }}">
                    <button type="submit" class="px-4 py-2 text-sm font-medium {{ $publicProfileEnabled ? 'text-red-700 bg-red-100 hover:bg-red-200' : 'text-green-700 bg-green-100 hover:bg-green-200' }} rounded-lg transition-colors">
                        {{ $publicProfileEnabled ? 'Disable Public Profile' : 'Enable Public Profile' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Featured Status -->
    <div class="bg-gradient-to-r {{ $featuredStatus ? ($featuredStatus->tier === 'gold' ? 'from-yellow-500 to-amber-600' : ($featuredStatus->tier === 'silver' ? 'from-gray-400 to-gray-500' : 'from-amber-700 to-orange-800')) : 'from-gray-700 to-gray-800' }} rounded-lg p-6 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                @if($featuredStatus)
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <h3 class="text-lg font-bold">{{ ucfirst($featuredStatus->tier) }} Featured Status</h3>
                    </div>
                    <p class="text-sm text-white/80 mt-1">
                        {{ $featuredStatus->days_remaining }} days remaining - Expires {{ $featuredStatus->end_date->format('M d, Y') }}
                    </p>
                @else
                    <h3 class="text-lg font-bold">Boost Your Profile</h3>
                    <p class="text-sm text-white/80 mt-1">Get featured in search results and attract more employers</p>
                @endif
            </div>

            <a href="{{ route('worker.profile.featured') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white hover:bg-gray-100 rounded-lg transition-colors">
                {{ $featuredStatus ? 'Manage Featured Status' : 'Get Featured' }}
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Portfolio Items -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between p-6 border-b border-gray-200">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Portfolio Items</h3>
                <p class="text-sm text-gray-600 mt-1">{{ $portfolioItems->count() }} of {{ $maxItems }} items</p>
            </div>

            @if($canAddMore)
                <a href="{{ route('worker.portfolio.create') }}" class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Item
                </a>
            @endif
        </div>

        @if($portfolioItems->count() > 0)
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" id="portfolio-grid" x-data="portfolioSorter()">
                    @foreach($portfolioItems as $item)
                        <div class="group relative bg-gray-50 rounded-lg overflow-hidden border border-gray-200 hover:border-gray-300 transition-colors" data-id="{{ $item->id }}">
                            <!-- Thumbnail -->
                            <div class="aspect-square bg-gray-100 relative">
                                @if($item->isImage())
                                    <img src="{{ $item->thumbnail_url }}" alt="{{ $item->title }}" class="w-full h-full object-cover">
                                @elseif($item->isVideo())
                                    <div class="w-full h-full flex items-center justify-center bg-gray-800">
                                        <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                @endif

                                <!-- Featured Badge -->
                                @if($item->is_featured)
                                    <div class="absolute top-2 left-2 px-2 py-1 bg-yellow-500 text-white text-xs font-medium rounded">
                                        Featured
                                    </div>
                                @endif

                                <!-- Type Badge -->
                                <div class="absolute top-2 right-2 px-2 py-1 bg-gray-900/70 text-white text-xs font-medium rounded capitalize">
                                    {{ $item->type }}
                                </div>

                                <!-- Hover Actions -->
                                <div class="absolute inset-0 bg-gray-900/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <a href="{{ route('worker.portfolio.edit', $item) }}" class="p-2 bg-white rounded-full text-gray-900 hover:bg-gray-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>

                                    @if(!$item->is_featured)
                                        <form action="{{ route('worker.portfolio.featured', $item) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="p-2 bg-white rounded-full text-gray-900 hover:bg-gray-100" title="Set as Featured">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('worker.portfolio.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 bg-red-500 rounded-full text-white hover:bg-red-600" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Item Info -->
                            <div class="p-3">
                                <h4 class="font-medium text-gray-900 truncate">{{ $item->title }}</h4>
                                <p class="text-xs text-gray-500 mt-1">{{ $item->formatted_file_size }}</p>
                            </div>

                            <!-- Drag Handle -->
                            <div class="absolute bottom-2 right-2 cursor-move opacity-0 group-hover:opacity-100 transition-opacity handle">
                                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 6h2v2H8V6zm6 0h2v2h-2V6zM8 11h2v2H8v-2zm6 0h2v2h-2v-2zm-6 5h2v2H8v-2zm6 0h2v2h-2v-2z"/>
                                </svg>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="text-sm text-gray-500 mt-4">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Drag items to reorder. The first item appears as your profile thumbnail.
                </p>
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No portfolio items yet</h3>
                <p class="text-sm text-gray-600 mb-4">Showcase your work to attract more employers</p>
                <a href="{{ route('worker.portfolio.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Your First Item
                </a>
            </div>
        @endif
    </div>

    <!-- Tips -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">Portfolio Tips</h3>
        <ul class="space-y-2 text-sm text-blue-800">
            <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Upload high-quality photos of your work, certifications, or awards
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Include brief videos showcasing your skills (under 2 minutes work best)
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Upload certifications and licenses to increase trust and get verified
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Set your best item as "Featured" to appear as your profile thumbnail
            </li>
        </ul>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function portfolioSorter() {
    return {
        init() {
            const grid = document.getElementById('portfolio-grid');
            if (grid) {
                new Sortable(grid, {
                    animation: 150,
                    handle: '.handle',
                    onEnd: (evt) => {
                        const items = Array.from(grid.querySelectorAll('[data-id]')).map(el => el.dataset.id);
                        this.saveOrder(items);
                    }
                });
            }
        },
        async saveOrder(items) {
            try {
                const response = await fetch('{{ route("worker.portfolio.reorder") }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ items })
                });
                if (!response.ok) throw new Error('Failed to save order');
            } catch (error) {
                console.error('Error saving order:', error);
                alert('Failed to save order. Please try again.');
            }
        }
    };
}
</script>
@endpush
