@extends('layouts.guest')

@section('title', 'Agency Registration - ' . ($stepTitle ?? 'Step ' . ($step ?? 1)))

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
            <!-- Sidebar Stepper -->
            <aside class="py-6 lg:col-span-3">
                <nav class="space-y-1">
                    @php
                        $steps = [
                            1 => 'Business Information',
                            2 => 'Contact Details',
                            3 => 'Document Upload',
                            4 => 'Partnership Tier',
                            5 => 'Worker Pool Details',
                            6 => 'Business References',
                            7 => 'Commercial Terms',
                            8 => 'Review & Submit',
                        ];
                        $currentStep = $step ?? 1;
                    @endphp

                    @foreach($steps as $number => $title)
                        <a href="{{ $number < $currentStep ? route('agency.register.step', $number) : '#' }}"
                            class="group flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $number == $currentStep ? 'bg-gray-100 text-gray-900' : ($number < $currentStep ? 'text-gray-900 hover:bg-gray-50' : 'text-gray-500 hover:text-gray-700') }} {{ $number > $currentStep ? 'cursor-default' : '' }}">

                            <span
                                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full border mr-3 {{ $number == $currentStep ? 'border-indigo-600 text-indigo-600' : ($number < $currentStep ? 'bg-indigo-600 border-transparent text-white' : 'border-gray-300 text-gray-500') }} text-xs transition-colors">
                                @if($number < $currentStep)
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                @else
                                    {{ $number }}
                                @endif
                            </span>
                            <span class="truncate">
                                {{ $title }}
                            </span>
                        </a>
                    @endforeach
                </nav>
            </aside>

            <!-- Main Form Area -->
            <div class="mt-8 lg:mt-0 lg:col-span-9">
                <div class="bg-white py-6 px-4 sm:p-6 shadow sm:rounded-lg">
                    <div class="mb-8">
                        <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                            {{ $stepTitle ?? 'Registration' }}
                        </h1>
                        @if(isset($stepDescription))
                            <p class="mt-1 text-sm text-gray-500">
                                {{ $stepDescription }}
                            </p>
                        @endif
                    </div>

                    <!-- Form Content -->
                    @yield('form-content')
                </div>
            </div>
        </div>
    </div>
@endsection