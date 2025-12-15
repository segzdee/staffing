@extends('layouts.authenticated')

@section('title', 'Client Details - ' . $client->company_name)

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('agency.clients.index') }}">Clients</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $client->company_name }}</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ $client->company_name }}</h4>
            <span class="badge {{ $client->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                {{ ucfirst($client->status) }}
            </span>
        </div>
        <div>
            <a href="{{ route('agency.clients.post-shift', $client->id) }}" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Post Shift
            </a>
            <a href="{{ route('agency.clients.edit', $client->id) }}" class="btn btn-outline-primary">
                <i class="fas fa-edit me-1"></i>Edit
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Client Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Industry:</strong> {{ $client->industry ?? 'N/A' }}</p>
                            <p><strong>Contact Name:</strong> {{ $client->contact_name ?? 'N/A' }}</p>
                            <p><strong>Contact Email:</strong> {{ $client->contact_email ?? 'N/A' }}</p>
                            <p><strong>Contact Phone:</strong> {{ $client->contact_phone ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Commission Rate:</strong> {{ $client->commission_rate ?? 10 }}%</p>
                            <p><strong>Address:</strong> {{ $client->address ?? 'N/A' }}</p>
                            <p><strong>Location:</strong>
                                @if($client->city || $client->state)
                                    {{ $client->city }}{{ $client->city && $client->state ? ', ' : '' }}{{ $client->state }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>
                    @if($client->notes)
                        <hr>
                        <p><strong>Notes:</strong></p>
                        <p class="text-muted">{{ $client->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Recent Shifts</h6>
                    <a href="{{ route('agency.clients.post-shift', $client->id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>New Shift
                    </a>
                </div>
                <div class="card-body">
                    @if(isset($client->shifts) && $client->shifts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Workers</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($client->shifts->take(10) as $shift)
                                        <tr>
                                            <td>{{ $shift->title }}</td>
                                            <td>{{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}</td>
                                            <td>
                                                <span class="badge bg-{{ $shift->status === 'open' ? 'success' : ($shift->status === 'completed' ? 'secondary' : 'warning') }}">
                                                    {{ ucfirst($shift->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $shift->filled_workers ?? 0 }}/{{ $shift->required_workers ?? 1 }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No shifts posted for this client yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h4 class="mb-0">{{ $client->total_shifts_count ?? 0 }}</h4>
                            <small class="text-muted">Total Shifts</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="mb-0">{{ $client->active_shifts_count ?? 0 }}</h4>
                            <small class="text-muted">Active Shifts</small>
                        </div>
                        <div class="col-6">
                            <h4 class="mb-0">{{ $client->completed_shifts_count ?? 0 }}</h4>
                            <small class="text-muted">Completed</small>
                        </div>
                        <div class="col-6">
                            <h4 class="mb-0">${{ number_format($client->total_commission ?? 0, 2) }}</h4>
                            <small class="text-muted">Commission Earned</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('agency.clients.post-shift', $client->id) }}" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i>Post New Shift
                        </a>
                        <a href="{{ route('agency.clients.edit', $client->id) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-edit me-1"></i>Edit Client
                        </a>
                        <form action="{{ route('agency.clients.destroy', $client->id) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Are you sure you want to remove this client?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-trash me-1"></i>Remove Client
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
