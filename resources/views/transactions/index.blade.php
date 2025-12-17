@extends('layouts.dashboard')

@section('title', 'Transactions')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-3xl font-bold tracking-tight">Transactions</h2>
        </div>

        <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <p class="text-muted-foreground">No transactions found.</p>
            </div>
        </div>
    </div>
@endsection