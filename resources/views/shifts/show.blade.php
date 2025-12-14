@extends('layouts.authenticated')

@section('title', 'Shift Details')
@section('page-title', 'Shift Details')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6">
    <div class="flex items-center space-x-4">
        <a href="{{ route('shifts.index') }}" class="text-gray-600 hover:text-gray-900">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Warehouse Worker Needed</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Shift Details</h2>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">Position</p>
                            <p class="text-gray-600">Warehouse Worker</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">Date & Time</p>
                            <p class="text-gray-600">Tomorrow, December 16, 2025</p>
                            <p class="text-gray-600">8:00 AM - 4:00 PM (8 hours)</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">Location</p>
                            <p class="text-gray-600">123 Warehouse St, Boston, MA 02118</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Description</h2>
                <p class="text-gray-600 leading-relaxed">
                    We are looking for reliable warehouse workers to help with inventory management and order fulfillment. 
                    This is a fast-paced environment requiring attention to detail and the ability to lift up to 50 lbs.
                </p>
                <div class="mt-4">
                    <h3 class="font-medium text-gray-900 mb-2">Requirements:</h3>
                    <ul class="list-disc list-inside text-gray-600 space-y-1">
                        <li>Ability to lift 50 lbs</li>
                        <li>Previous warehouse experience preferred</li>
                        <li>Reliable transportation</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6 sticky top-6">
                <div class="text-center mb-6">
                    <p class="text-3xl font-bold text-gray-900">$22.00/hr</p>
                    <p class="text-sm text-gray-500 mt-1">Total: $176.00</p>
                </div>
                
                @if(auth()->user()->isWorker())
                <button class="w-full px-6 py-3 bg-brand-600 text-white font-medium rounded-lg hover:bg-brand-700 transition-colors">
                    Apply for This Shift
                </button>
                @endif

                <div class="mt-6 pt-6 border-t border-gray-200 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Workers Needed</span>
                        <span class="font-medium text-gray-900">3</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Applicants</span>
                        <span class="font-medium text-gray-900">7</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Business Info</h3>
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-gray-200 rounded-lg"></div>
                    <div>
                        <p class="font-medium text-gray-900">Sample Business Inc.</p>
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            4.8 (156 reviews)
                        </div>
                    </div>
                </div>
                <p class="text-sm text-gray-600">Established warehouse and distribution company serving the Boston area for over 10 years.</p>
            </div>
        </div>
    </div>
</div>
@endsection
