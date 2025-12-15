@extends('layouts.authenticated')

@section('title', 'New Message')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('messages.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Messages
        </a>
    </div>

    <!-- Message Card -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200">
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                New Message
            </h1>
        </div>

        <!-- Body -->
        <div class="p-6">
            <!-- Recipient Info -->
            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg mb-6">
                <div class="flex-shrink-0">
                    @if(isset($business))
                        <img src="{{ $business->avatar ?? asset('images/default-avatar.png') }}"
                             alt="{{ $business->name }}"
                             class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm">
                    @elseif(isset($worker))
                        <img src="{{ $worker->avatar ?? asset('images/default-avatar.png') }}"
                             alt="{{ $worker->name }}"
                             class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm">
                    @endif
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-gray-900">
                            @if(isset($business))
                                {{ $business->name }}
                            @elseif(isset($worker))
                                {{ $worker->name }}
                            @endif
                        </h3>
                        @if(isset($business))
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">
                                Business
                            </span>
                        @elseif(isset($worker))
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                                Worker
                            </span>
                        @endif
                    </div>
                    @if(isset($shift))
                        <p class="text-sm text-gray-500 mt-1">
                            Re: {{ $shift->title }} ({{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }})
                        </p>
                    @endif
                </div>
            </div>

            <!-- Message Form -->
            <form action="{{ route('messages.send') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                @if(isset($business))
                    <input type="hidden" name="to_user_id" value="{{ $business->id }}">
                @elseif(isset($worker))
                    <input type="hidden" name="to_user_id" value="{{ $worker->id }}">
                @endif

                @if(isset($shift))
                    <input type="hidden" name="shift_id" value="{{ $shift->id }}">
                @endif

                <!-- Message Textarea -->
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                        Message <span class="text-red-500">*</span>
                    </label>
                    <textarea name="message"
                              id="message"
                              rows="6"
                              placeholder="Type your message here..."
                              required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors @error('message') border-red-500 @enderror">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- File Attachment -->
                <div>
                    <label for="attachment" class="block text-sm font-medium text-gray-700 mb-2">
                        Attachment (optional)
                    </label>
                    <input type="file"
                           name="attachment"
                           id="attachment"
                           accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 @error('attachment') border-red-500 @enderror">
                    <p class="mt-2 text-sm text-gray-500">Max 10MB. Allowed: JPG, PNG, PDF, DOC, DOCX</p>
                    @error('attachment')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <a href="{{ route('messages.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
