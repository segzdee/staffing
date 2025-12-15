@extends('layouts.authenticated')

@section('title', 'Agency Clients')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Agency Clients</h4>
            <p class="text-muted mb-0">Manage your business clients</p>
        </div>
        <a href="{{ route('agency.clients.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add Client
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($clients->count() > 0)
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Client</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Active Shifts</th>
                            <th>Total Shifts</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $client)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $client->logo ?? asset('images/default-company.png') }}"
                                             alt="{{ $client->company_name }}"
                                             class="rounded me-3"
                                             width="40" height="40">
                                        <div>
                                            <strong>{{ $client->company_name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $client->industry ?? 'N/A' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{ $client->contact_name ?? 'N/A' }}
                                    @if($client->contact_email)
                                        <br><small class="text-muted">{{ $client->contact_email }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $client->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ ucfirst($client->status) }}
                                    </span>
                                </td>
                                <td>{{ $client->active_shifts_count ?? 0 }}</td>
                                <td>{{ $client->total_shifts_count ?? 0 }}</td>
                                <td class="text-end">
                                    <a href="{{ route('agency.clients.show', $client->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('agency.clients.edit', $client->id) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('agency.clients.post-shift', $client->id) }}" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-plus-circle"></i> Post Shift
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                <h5>No Clients Yet</h5>
                <p class="text-muted mb-3">Add your first client to start managing their shifts.</p>
                <a href="{{ route('agency.clients.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Add Your First Client
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
