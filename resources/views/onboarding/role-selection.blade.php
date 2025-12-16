@extends('layouts.authenticated')

@section('title', 'Select Account Type')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 relative overflow-hidden"
     x-data="roleSelection()"
     x-init="init()"
     x-cloak>

    {{-- Enhanced Background with Shapes --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-20 -right-20 w-72 h-72 bg-blue-400/20 dark:bg-blue-900/20 rounded-full blur-3xl opacity-60"></div>
        <div class="absolute top-40 -left-20 w-96 h-96 bg-purple-400/20 dark:bg-purple-900/20 rounded-full blur-3xl opacity-40"></div>
        <div class="absolute bottom-20 right-40 w-64 h-64 bg-indigo-400/20 dark:bg-indigo-900/20 rounded-full blur-3xl opacity-30"></div>
        <div class="absolute top-1/2 left-1/4 w-80 h-80 bg-blue-400/10 dark:bg-blue-900/10 rounded-full blur-2xl opacity-50"></div>
        {{-- Decorative dots --}}
        <div class="absolute top-1/3 right-1/4 w-2 h-2 bg-blue-500/30 rounded-full"></div>
        <div class="absolute top-1/4 right-1/3 w-3 h-3 bg-purple-500/30 rounded-full"></div>
        <div class="absolute bottom-1/4 left-1/3 w-2 h-2 bg-indigo-500/30 rounded-full"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
        
        {{-- Progress Indicator --}}
        <div class="flex justify-center mb-8">
            <div class="flex items-center space-x-2">
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white font-semibold shadow-lg shadow-blue-600/30">
                    1
                </div>
                <div class="w-12 h-0.5 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-400 font-semibold">
                    2
                </div>
                <div class="w-12 h-0.5 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-400 font-semibold">
                    3
                </div>
                <div class="flex items-center justify-center w-8 h-8 bg-slate-200 dark:bg-slate-700 text-slate-400 font-semibold">
                    4
                </div>
            </div>
        </div>

        {{-- Header --}}
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-4">
                Choose Your Account Type
            </h1>
            <p class="text-xl text-slate-600 dark:text-slate-300 max-w-2xl mx-auto leading-relaxed">
                Welcome to OvertimeStaff! Let's get your account set up with the right role.
            </p>
        </div>

        {{-- Success Message --}}
        <div x-show="successMessage" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-4"
             class="mb-8 max-w-2xl mx-auto">
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 flex items-start space-x-3" role="status">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200" x-text="successMessage"></p>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">Great choice! Let's continue.</p>
                </div>
            </div>
        </div>

        {{-- Error Alert --}}
        <div x-show="errorMessage" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-4"
             class="mb-8 max-w-2xl mx-auto">
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 flex items-start space-x-3" role="alert">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200" x-text="errorMessage"></p>
                    <button @click="clearError" class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 underline">
                        Clear
                    </button>
                </div>
            </div>
        </div>

        {{-- Validation Errors --}}
        <div x-show="validationErrors.length > 0" 
             class="mb-8 max-w-2xl mx-auto">
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                <h3 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">Please fix the following issues:</h3>
                <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-1">
                    <template x-for="error in validationErrors">
                        <li x-text="error"></li>
                    </template>
                </ul>
            </div>
        </div>

        {{-- Role Selection Form --}}
        <form method="POST"
              action="{{ route('onboarding.select-role.store') }}"
              id="roleSelectionForm"
              @submit.prevent="handleSubmit">
            @csrf
            <input type="hidden" name="user_type" :value="selectedRole" id="selectedUserType">

            {{-- Role Cards Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
                
                {{-- Worker Card --}}
                <div class="relative">
                    <input type="radio"
                           name="role_radio"
                           value="worker"
                           id="role_worker"
                           class="sr-only peer"
                           x-model="selectedRole">
                    
                    <label for="role_worker"
                           class="block h-full bg-white dark:bg-slate-800 border-2 rounded-2xl p-8 cursor-pointer transition-all duration-300 relative overflow-hidden
                                  hover:border-blue-400 hover:shadow-lg hover:shadow-blue-100 dark:hover:shadow-blue-900/20 hover:scale-[1.02]
                                  peer-checked:border-blue-500 peer-checked:shadow-xl peer-checked:shadow-blue-100 dark:peer-checked:shadow-blue-900/30 peer-checked:scale-[1.02]
                                  border-slate-200 dark:border-slate-700"
                                  :class="{ 'border-blue-500 shadow-xl shadow-blue-100 dark:shadow-blue-900/30 scale-[1.02] bg-blue-50 dark:bg-blue-900/10': selectedRole === 'worker' }">

                        {{-- Animated Background Pattern --}}
                        <div class="absolute inset-0 opacity-5" :class="selectedRole === 'worker' ? 'bg-gradient-to-br from-blue-400/20 to-blue-600/20' : ''">
                            <div class="absolute top-0 left-0 w-32 h-32 bg-blue-400/10 rounded-full blur-xl -translate-x-16 -translate-y-16"></div>
                            <div class="absolute bottom-0 right-0 w-24 h-24 bg-blue-500/10 rounded-full blur-lg -translate-x-12 translate-y-12"></div>
                            <div class="absolute top-1/2 right-1/4 w-16 h-16 bg-blue-600/10 rounded-full blur-md -translate-x-8 -translate-y-8"></div>
                        </div>

                        {{-- Checkmark Badge --}}
                        <div class="absolute -top-4 -right-4 w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center shadow-lg transition-all duration-300 z-10"
                             :class="selectedRole === 'worker' ? 'opacity-100 scale-110' : 'opacity-0 scale-75'">
                            <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>

                        {{-- Role Icon --}}
                        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl flex items-center justify-center transition-all duration-300 relative z-10"
                             :class="selectedRole === 'worker' ? 'bg-blue-600 dark:bg-blue-700' : 'bg-slate-100 dark:bg-slate-700'">
                             <svg class="w-10 h-10 transition-colors duration-300"
                                 :class="selectedRole === 'worker' ? 'text-blue-100' : 'text-slate-500 dark:text-slate-400'"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="20" height="14" x="2" y="7" rx="2" ry="2"/>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                            </svg>
                        </div>

                        <div class="text-center relative z-20">
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">
                                Worker
                            </h3>
                            <p class="text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                                Find flexible shift opportunities that match your skills
                            </p>
                            
                            {{-- Benefits List --}}
                            <ul class="mt-6 space-y-3 text-sm">
                                <li class="flex items-center text-slate-700 dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Browse available shifts nearby
                                </li>
                                <li class="flex items-center text-slate-700 dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Apply with one click
                                </li>
                                <li class="flex items-center text-slate-700 dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Get paid instantly
                                </li>
                                <li class="flex items-center text-slate-700 dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Build your reputation
                                </li>
                            </ul>
                        </div>
                    </label>
                </div>

                        {{-- Business Card --}}
                <div class="relative">
                    <input type="radio"
                           name="role_radio"
                           value="business"
                           id="role_business"
                           class="sr-only peer"
                           x-model="selectedRole">
                    
                    <label for="role_business"
                           class="block h-full bg-white dark:bg-slate-800 border-2 rounded-2xl p-8 cursor-pointer transition-all duration-300 relative overflow-hidden
                                  hover:border-blue-400 hover:shadow-lg hover:shadow-blue-100 dark:hover:shadow-blue-900/20 hover:scale-[1.02]
                                  peer-checked:border-blue-500 peer-checked:shadow-xl peer-checked:shadow-blue-100 dark:peer-checked:shadow-blue-900/30 peer-checked:scale-[1.02]
                                  border-slate-200 dark:border-slate-700"
                                  :class="{
                                     'border-blue-500 shadow-xl shadow-blue-100 dark:shadow-blue-900/30 scale-[1.02] bg-blue-50 dark:bg-blue-900/10': selectedRole === 'business'
                                  }">

                        {{-- Animated Background Pattern --}}
                        <div class="absolute inset-0 opacity-5" :class="selectedRole === 'business' ? 'bg-gradient-to-br from-blue-400/20 to-indigo-600/20' : ''">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-400/10 rounded-full blur-xl -translate-x-16 -translate-y-16"></div>
                            <div class="absolute bottom-0 left-0 w-24 h-24 bg-indigo-500/10 rounded-full blur-lg -translate-x-12 translate-y-12"></div>
                            <div class="absolute top-1/2 left-1/4 w-16 h-16 bg-indigo-600/10 rounded-full blur-md -translate-x-8 -translate-y-8"></div>
                        </div>

                        {{-- Checkmark Badge --}}
                        <div class="absolute -top-4 -right-4 w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center shadow-lg transition-all duration-300 z-10"
                             :class="selectedRole === 'business' ? 'opacity-100 scale-110' : 'opacity-0 scale-75'">
                            <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>

                        {{-- Role Icon --}}
                        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl flex items-center justify-center transition-all duration-300 relative z-10"
                             :class="selectedRole === 'business' ? 'bg-blue-600 dark:bg-blue-700' : 'bg-slate-100 dark:bg-slate-700'">
                            <svg class="w-10 h-10 transition-colors duration-300"
                                 :class="selectedRole === 'business' ? 'text-blue-100' : 'text-slate-500 dark:text-slate-400'"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/>
                                <path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/>
                                <path d="M18 9h2a2 2 0 0 0 0-2 2v9a2 2 0 0 0 2 2h2"/>
                                <path d="M10 6h4"/>
                                <path d="M10 10h4"/>
                                <path d="M10 14h4"/>
                            </svg>
                        </div>

                        <div class="text-center relative z-20">
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">
                                Business
                            </h3>
                            <p class="text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                                Post shifts and find qualified workers instantly
                            </p>
                            
                            {{-- Benefits List --}}
                            <ul class="mt-6 space-y-3 text-sm">
                                <li class="flex items-center text-slate-700 dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Post shifts in minutes
                                </li>
                                <li class="flex items-center text-slate-700 dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    AI-powered matching
                                </li>
                                <li class="flex items-center text-slate-700 dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Manage your team
                                </li>
                                <li class="flex items-center text-slate-700 dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Secure payments
                                </li>
                            </ul>
                        </div>
                    </label>
                </div>

                {{-- Agency Card --}}
                <div class="relative">
                    <input type="radio"
                           name="role_radio"
                           value="agency"
                           id="role_agency"
                           class="sr-only peer"
                           x-model="selectedRole">
                    
                    <label for="role_agency"
                           class="block h-full bg-white dark:bg-slate-800 border-2 rounded-2xl p-8 cursor-pointer transition-all duration-300 relative overflow-hidden
                                  hover:border-blue-400 hover:shadow-lg hover:shadow-blue-100 dark:hover:shadow-blue-900/20 hover:scale-[1.02]
                                  peer-checked:border-blue-500 peer-checked:shadow-xl peer-checked:shadow-blue-100 dark:peer-checked:shadow-blue-900/30 peer-checked:scale-[1.02]
                                  border-slate-200 dark:border-slate-700"
                                  :class="{ 'border-blue-500 shadow-xl shadow-blue-100 dark:shadow-blue-900/30 scale-[1.02] bg-blue-50 dark:bg-blue-900/10': selectedRole === 'agency' }">

                        {{-- Animated Background Pattern --}}
                        <div class="absolute inset-0 opacity-5" :class="selectedRole === 'agency' ? 'bg-gradient-to-br from-purple-400/20 to-blue-600/20' : ''">
                            <div class="absolute top-0 left-0 w-32 h-32 bg-purple-400/10 rounded-full blur-xl -translate-x-16 -translate-y-16"></div>
                            <div class="absolute bottom-0 right-0 w-24 h-24 bg-blue-500/10 rounded-full blur-lg -translate-x-12 translate-y-12"></div>
                            <div class="absolute top-1/4 right-1/2 w-16 h-16 bg-indigo-600/10 rounded-full blur-md -translate-x-8 -translate-y-8"></div>
                        </div>

                        {{-- Checkmark Badge --}}
                        <div class="absolute -top-4 -right-4 w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center shadow-lg transition-all duration-300 z-10"
                             :class="selectedRole === 'agency' ? 'opacity-100 scale-110' : 'opacity-0 scale-75'">
                            <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>

                        {{-- Role Icon --}}
                        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl flex items-center justify-center transition-all duration-300 relative z-10"
                             :class="selectedRole === 'agency' ? 'bg-blue-600 dark:bg-blue-700' : 'bg-slate-100 dark:bg-slate-700'">
                            <svg class="w-10 h-10 transition-colors duration-300"
                                 :class="selectedRole === 'agency' ? 'text-blue-100' : 'text-slate-500 dark:text-slate-400'"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-4-4h-1.87M13 19a4 4 0 0 0-3.87 0z"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75v2a4 4 0 0 1-4 4v2z"/>
                            </svg>
                        </div>

                        <div class="text-center relative z-20">
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">
                                Agency
                            </h3>
                            <p class="text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                                Manage multiple workers and fill shifts for businesses
                            </p>
                            
                            {{-- Benefits List --}}
                            <ul class="mt-6 space-y-3 text-sm">
                                <li class="flex items-center text-slate-700 dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Manage worker pool
                                </li>
                                <li class="flex items-center text-slate-700 dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Bulk shift management
                                </li>
                                <li class="flex items-center text-slate-700 dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Commission tracking
                                </li>
                                <li class="flex items-center text-slate-700 dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Advanced analytics
                                </li>
                            </ul>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Continue Button --}}
            <div class="text-center">
                <button type="submit"
                        :disabled="!selectedRole || isSubmitting"
                        class="inline-flex items-center justify-center px-10 py-4 text-lg font-semibold rounded-2xl transition-all duration-300 relative overflow-hidden group
                               focus:outline-none focus:ring-4 focus:ring-blue-500/20 focus:ring-offset-2
                               disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none
                               bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white shadow-lg hover:shadow-xl hover:from-blue-700 hover:to-blue-900 disabled:from-slate-300 disabled:to-slate-400 disabled:shadow-none
                               transform hover:-translate-y-0.5 active:translate-y-0">
                    
                    {{-- Button Background Animation --}}
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-400/20 to-purple-600/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    
                    {{-- Button Content --}}
                    <span class="relative z-10 flex items-center">
                        {{-- Loading State --}}
                        <span x-show="isSubmitting" class="flex items-center">
                            <svg class="w-5 h-5 mr-2 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12 12h4zm2 5.291A7.962 7.962 0 014 12 12h4z"/>
                            </svg>
                            <span class="ml-1">Setting up your account...</span>
                        </span>
                        
                        {{-- Normal State --}}
                        <span x-show="!isSubmitting" class="flex items-center">
                            Continue
                            <svg class="w-5 h-5 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                                <polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </span>
                    </span>
                </button>

                <p class="mt-6 text-sm text-slate-500 dark:text-slate-400">
                    Already completed onboarding?
                    <a href="{{ route('dashboard.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium ml-1">
                        Go to Dashboard
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function roleSelection() {
    return {
        selectedRole: '{{ old('user_type', '') }}',
        isSubmitting: false,
        successMessage: null,
        errorMessage: null,
        validationErrors: [],

        init() {
            // Pre-select if there was a previous selection from error
            const oldValue = '{{ old('user_type', '') }}';
            if (oldValue && ['worker', 'business', 'agency'].includes(oldValue)) {
                this.selectedRole = oldValue;
            }

            // Clear any success/error messages on load
            this.clearMessages();
        },

        clearMessages() {
            this.successMessage = null;
            this.errorMessage = null;
            this.validationErrors = [];
        },

        clearError() {
            this.errorMessage = null;
            this.validationErrors = [];
        },

        async handleSubmit() {
            if (!this.selectedRole) {
                this.showError('Please select an account type to continue.');
                return;
            }

            if (this.isSubmitting) {
                return; // Prevent double submission
            }

            this.clearMessages();
            this.isSubmitting = true;

            try {
                const form = document.getElementById('roleSelectionForm');
                const formData = new FormData(form);

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    this.successMessage = 'Great choice! Setting up your ' + this.selectedRole + ' account...';
                    
                    // Redirect to next step after a short delay to show success message
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    this.handleError(data);
                }
            } catch (error) {
                // Network or parsing error - fall back to regular form submission
                console.error('AJAX submission failed, falling back to form submission:', error);
                form.submit();
            } finally {
                this.isSubmitting = false;
            }
        },

        handleError(data) {
            this.isSubmitting = false;

            // Show error message based on error code
            const errorMap = {
                'E001': 'Authorization error. Please log in again.',
                'E002': 'Invalid role selected. Please try again.',
                'E003': 'Profile already exists. Refresh the page and try again.',
                'E004': 'Database error. Please try again.',
                'E005': 'Connection error. Please check your internet and try again.',
            };

            const message = errorMap[data.error_code] || data.message || 'An error occurred. Please try again.';
            
            this.errorMessage = message;
            
            // For duplicate profiles, show message with retry option
            if (data.error_code === 'E003') {
                this.errorMessage += ' You can refresh the page to continue.';
            }
        }
    };
}
</script>
@endpush