@extends('layouts.dashboard')

@section('title', 'My Profile')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-3xl font-bold tracking-tight">My Profile</h2>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                <div class="flex flex-col space-y-1.5 p-6">
                    <h3 class="text-2xl font-semibold leading-none tracking-tight">Personal Information</h3>
                </div>
                <div class="p-6 pt-0">
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <label class="text-sm font-medium leading-none">Name</label>
                            <p class="text-sm text-muted-foreground">{{ $user->name }}</p>
                        </div>
                        <div class="grid gap-2">
                            <label class="text-sm font-medium leading-none">Email</label>
                            <p class="text-sm text-muted-foreground">{{ $user->email }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection