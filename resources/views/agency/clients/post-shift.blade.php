@extends('layouts.authenticated')

@section('title', 'Post Shift for ' . $client->company_name)

@section('content')
<div class="container py-4">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard.index') }}" class="text-sm font-medium text-muted-foreground hover:text-foreground">Dashboard</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-muted-foreground mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="{{ route('agency.clients.index') }}" class="ml-1 text-sm font-medium text-muted-foreground hover:text-foreground md:ml-2">Clients</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-muted-foreground mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="{{ route('agency.clients.show', $client->id) }}" class="ml-1 text-sm font-medium text-muted-foreground hover:text-foreground md:ml-2">{{ $client->company_name }}</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-muted-foreground mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-foreground md:ml-2">Post Shift</span>
                        </div>
                    </li>
                </ol>
            </nav>

             <div class="bg-card border rounded-lg shadow-sm">
                <div class="p-6 border-b">
                    <h5 class="text-lg font-semibold flex items-center">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Post Shift for {{ $client->company_name }}
                    </h5>
                </div>
                <div class="p-6">
                    <form action="{{ route('agency.clients.shifts.store', $client->id) }}" method="POST">
                        @csrf

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <h6 class="text-muted-foreground mb-3 font-medium">Shift Details</h6>

                                <div class="mb-3 space-y-2">
                                    <x-ui.label for="title" class="after:content-['*'] after:ml-0.5 after:text-destructive" value="Shift Title" />
                                    <x-ui.input type="text" name="title" id="title"
                                           value="{{ old('title') }}"
                                           placeholder="e.g., Server, Warehouse Associate" required />
                                    @error('title')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3 space-y-2">
                                    <x-ui.label for="description" value="Description" />
                                    <x-ui.textarea name="description" id="description" rows="4"
                                              placeholder="Job responsibilities, requirements, etc.">{{ old('description') }}</x-ui.textarea>
                                    @error('description')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="grid md:grid-cols-2 gap-4 mb-3">
                                    <div class="space-y-2">
                                        <x-ui.label for="shift_date" class="after:content-['*'] after:ml-0.5 after:text-destructive" value="Date" />
                                        <x-ui.input type="date" name="shift_date" id="shift_date"
                                               value="{{ old('shift_date') }}"
                                               min="{{ date('Y-m-d') }}" required />
                                        @error('shift_date')
                                            <div class="text-sm text-destructive">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="space-y-2">
                                        <x-ui.label for="required_workers" class="after:content-['*'] after:ml-0.5 after:text-destructive" value="Workers Needed" />
                                        <x-ui.input type="number" name="required_workers" id="required_workers"
                                               value="{{ old('required_workers', 1) }}" min="1" required />
                                        @error('required_workers')
                                            <div class="text-sm text-destructive">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="grid md:grid-cols-2 gap-4 mb-3">
                                    <div class="space-y-2">
                                        <x-ui.label for="start_time" class="after:content-['*'] after:ml-0.5 after:text-destructive" value="Start Time" />
                                        <x-ui.input type="time" name="start_time" id="start_time"
                                               value="{{ old('start_time') }}" required />
                                        @error('start_time')
                                            <div class="text-sm text-destructive">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="space-y-2">
                                        <x-ui.label for="end_time" class="after:content-['*'] after:ml-0.5 after:text-destructive" value="End Time" />
                                        <x-ui.input type="time" name="end_time" id="end_time"
                                               value="{{ old('end_time') }}" required />
                                        @error('end_time')
                                            <div class="text-sm text-destructive">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h6 class="text-muted-foreground mb-3 font-medium">Pay & Location</h6>

                                <div class="grid md:grid-cols-2 gap-4 mb-3">
                                    <div class="space-y-2">
                                        <x-ui.label for="hourly_rate" class="after:content-['*'] after:ml-0.5 after:text-destructive" value="Hourly Rate ($)" />
                                        <x-ui.input type="number" name="hourly_rate" id="hourly_rate"
                                               value="{{ old('hourly_rate') }}" min="0" step="0.01" required />
                                        @error('hourly_rate')
                                            <div class="text-sm text-destructive">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="space-y-2">
                                        <x-ui.label for="urgency_level" value="Urgency" />
                                        <x-ui.select name="urgency_level" id="urgency_level">
                                            <option value="normal" {{ old('urgency_level') === 'normal' ? 'selected' : '' }}>Normal</option>
                                            <option value="urgent" {{ old('urgency_level') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                            <option value="critical" {{ old('urgency_level') === 'critical' ? 'selected' : '' }}>Critical</option>
                                        </x-ui.select>
                                    </div>
                                </div>

                                <div class="mb-3 space-y-2">
                                    <x-ui.label for="location_name" value="Location Name" />
                                    <x-ui.input type="text" name="location_name" id="location_name"
                                           value="{{ old('location_name', $client->company_name) }}" />
                                    @error('location_name')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3 space-y-2">
                                    <x-ui.label for="location_address" class="after:content-['*'] after:ml-0.5 after:text-destructive" value="Address" />
                                    <x-ui.input type="text" name="location_address" id="location_address"
                                           value="{{ old('location_address', $client->address) }}" required />
                                    @error('location_address')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="grid md:grid-cols-3 gap-4 mb-3">
                                    <div class="space-y-2">
                                        <x-ui.label for="location_city" class="after:content-['*'] after:ml-0.5 after:text-destructive" value="City" />
                                        <x-ui.input type="text" name="location_city" id="location_city"
                                               value="{{ old('location_city', $client->city) }}" required />
                                        @error('location_city')
                                            <div class="text-sm text-destructive">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="space-y-2">
                                        <x-ui.label for="location_state" class="after:content-['*'] after:ml-0.5 after:text-destructive" value="State" />
                                        <x-ui.input type="text" name="location_state" id="location_state"
                                               value="{{ old('location_state', $client->state) }}" required />
                                        @error('location_state')
                                            <div class="text-sm text-destructive">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="space-y-2">
                                        <x-ui.label for="location_zip" value="ZIP" />
                                        <x-ui.input type="text" name="location_zip" id="location_zip"
                                               value="{{ old('location_zip', $client->zip_code) }}" />
                                        @error('location_zip')
                                            <div class="text-sm text-destructive">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3 space-y-2">
                                    <x-ui.label for="industry" value="Industry" />
                                    <x-ui.input type="text" name="industry" id="industry"
                                           value="{{ old('industry', $client->industry) }}" />
                                    @error('industry')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-6 border-border">

                        <div class="flex justify-between items-center">
                            <x-ui.button variant="outline" tag="a" href="{{ route('agency.clients.show', $client->id) }}">
                                <i class="fas fa-arrow-left me-1"></i>Cancel
                            </x-ui.button>
                            <x-ui.button type="submit" class="bg-green-600 hover:bg-green-700 text-white">
                                <i class="fas fa-plus-circle me-1"></i>Post Shift
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
