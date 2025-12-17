@extends('layouts.authenticated')

@section('title', 'Create New Placement')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
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
                            <a href="{{ route('agency.assignments') }}" class="ml-1 text-sm font-medium text-muted-foreground hover:text-foreground md:ml-2">Placements</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-muted-foreground mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-foreground md:ml-2">Create Placement</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="bg-card border rounded-lg shadow-sm">
                <div class="p-6 border-b">
                    <h5 class="text-lg font-semibold flex items-center"><i class="fas fa-user-plus me-2"></i>Create New Placement</h5>
                </div>
                <div class="p-6">
                    @if(session('error'))
                        <div class="bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                            <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none';">
                                <span class="text-xl">&times;</span>
                            </button>
                        </div>
                    @endif

                    <form action="{{ route('agency.shifts.assign') }}" method="POST">
                        @csrf

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <h6 class="text-muted-foreground mb-3 font-medium">Select Worker</h6>

                                @if($availableWorkers->count() > 0)
                                    <div class="mb-3 space-y-2">
                                        <x-ui.label for="worker_id" class="after:content-['*'] after:ml-0.5 after:text-destructive" value="Worker" />
                                        <x-ui.select name="worker_id" id="worker_id" required>
                                            <option value="">-- Select a Worker --</option>
                                            @foreach($availableWorkers as $agencyWorker)
                                                @if($agencyWorker->worker)
                                                    <option value="{{ $agencyWorker->worker->id }}" {{ old('worker_id') == $agencyWorker->worker->id ? 'selected' : '' }}>
                                                        {{ $agencyWorker->worker->name }}
                                                        @if($agencyWorker->worker->workerProfile)
                                                            ({{ $agencyWorker->worker->workerProfile->city ?? 'No location' }})
                                                        @endif
                                                    </option>
                                                @endif
                                            @endforeach
                                        </x-ui.select>
                                        @error('worker_id')
                                            <div class="text-sm text-destructive">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div id="workerDetails" class="bg-muted/50 rounded-lg p-4 mb-3 hidden">
                                        <div class="flex items-center">
                                            <img id="workerAvatar" src="{{ asset('images/default-avatar.png') }}"
                                                 class="rounded-full mr-3 object-cover" width="60" height="60">
                                            <div>
                                                <h6 id="workerName" class="font-medium mb-1">Worker Name</h6>
                                                <div id="workerSkills" class="text-muted-foreground text-sm"></div>
                                                <div id="workerRating" class="mt-1"></div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-4">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        No workers available. <a href="{{ route('agency.workers.add') }}" class="underline">Add workers</a> to your agency first.
                                    </div>
                                @endif
                            </div>

                            <div>
                                <h6 class="text-muted-foreground mb-3 font-medium">Select Shift</h6>

                                @if($availableShifts->count() > 0)
                                    <div class="mb-3 space-y-2">
                                        <x-ui.label for="shift_id" class="after:content-['*'] after:ml-0.5 after:text-destructive" value="Shift" />
                                        <x-ui.select name="shift_id" id="shift_id" required>
                                            <option value="">-- Select a Shift --</option>
                                            @foreach($availableShifts as $shift)
                                                <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>
                                                    {{ $shift->title }} - {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d') }}
                                                    (${{ number_format($shift->hourly_rate, 2) }}/hr)
                                                </option>
                                            @endforeach
                                        </x-ui.select>
                                        @error('shift_id')
                                            <div class="text-sm text-destructive">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div id="shiftDetails" class="bg-muted/50 rounded-lg p-4 mb-3 hidden">
                                        <h6 id="shiftTitle" class="font-medium mb-2">Shift Title</h6>
                                        <p id="shiftDate" class="mb-1 text-sm"><i class="far fa-calendar me-1"></i></p>
                                        <p id="shiftTime" class="mb-1 text-sm"><i class="far fa-clock me-1"></i></p>
                                        <p id="shiftLocation" class="mb-1 text-sm"><i class="fas fa-map-marker-alt me-1"></i></p>
                                        <p id="shiftRate" class="mb-0 text-green-600 font-bold"></p>
                                    </div>
                                @else
                                    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-4">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No open shifts available. Check back later or <a href="{{ route('agency.shifts.browse') }}" class="underline">browse shifts</a>.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <hr class="my-6 border-border">

                        <div class="flex justify-between items-center">
                            <x-ui.button variant="outline" tag="a" href="{{ route('agency.assignments') }}">
                                <i class="fas fa-arrow-left me-1"></i>Cancel
                            </x-ui.button>
                            <x-ui.button type="submit" class="bg-green-600 hover:bg-green-700 text-white" :disabled="$availableWorkers->count() == 0 || $availableShifts->count() == 0">
                                <i class="fas fa-check me-1"></i>Create Placement
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
