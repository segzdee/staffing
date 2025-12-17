@extends('layouts.dashboard')

@section('title', 'Available Workers')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-3xl font-bold tracking-tight">Available Workers</h2>
        </div>

        <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <p class="text-muted-foreground">No workers currently available.</p>
            </div>
        </div>
    </div>
@endsection