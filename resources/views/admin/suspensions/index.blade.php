@extends('admin.layouts.admin')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Suspension Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Suspensions</li>
    </ol>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Analytics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs text-white-50">Active Suspensions</div>
                            <div class="h3 mb-0">{{ $analytics['total_active'] }}</div>
                        </div>
                        <i class="fas fa-ban fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs text-white-50">Pending Appeals</div>
                            <div class="h3 mb-0">{{ $analytics['pending_appeals'] }}</div>
                        </div>
                        <i class="fas fa-gavel fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('admin.suspensions.appeals') }}">Review Appeals</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs text-white-50">Avg Resolution Time</div>
                            <div class="h3 mb-0">{{ $analytics['average_appeal_resolution_hours'] }}h</div>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs text-white-50">Issue New</div>
                            <div class="h5 mb-0">Suspension</div>
                        </div>
                        <i class="fas fa-plus-circle fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('admin.suspensions.create') }}">Create Suspension</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Breakdown -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    By Category
                </div>
                <div class="card-body">
                    @forelse($analytics['by_category'] as $category => $count)
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ ucwords(str_replace('_', ' ', $category)) }}</span>
                            <span class="badge bg-secondary">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-muted">No active suspensions</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    By Type
                </div>
                <div class="card-body">
                    @forelse($analytics['by_type'] as $type => $count)
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ ucwords($type) }}</span>
                            <span class="badge bg-{{ $type === 'permanent' ? 'danger' : ($type === 'indefinite' ? 'warning' : 'info') }}">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-muted">No active suspensions</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filters
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.suspensions.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['type'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['category'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search Worker</label>
                        <input type="text" name="search" class="form-control" placeholder="Name or email" value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.suspensions.index') }}" class="btn btn-outline-secondary">Clear</a>
                    <a href="{{ route('admin.suspensions.export', request()->query()) }}" class="btn btn-outline-success float-end">
                        <i class="fas fa-download me-1"></i> Export CSV
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Suspensions Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Suspensions
        </div>
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Worker</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Strikes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suspensions as $suspension)
                        <tr>
                            <td>{{ $suspension->id }}</td>
                            <td>
                                <a href="{{ route('admin.users.show', $suspension->worker) }}">
                                    {{ $suspension->worker->name }}
                                </a>
                                <br><small class="text-muted">{{ $suspension->worker->email }}</small>
                            </td>
                            <td>
                                <span class="badge bg-{{ $suspension->type === 'permanent' ? 'danger' : ($suspension->type === 'indefinite' ? 'warning' : ($suspension->type === 'warning' ? 'info' : 'secondary')) }}">
                                    {{ $suspension->getTypeLabel() }}
                                </span>
                            </td>
                            <td>{{ $suspension->getReasonCategoryLabel() }}</td>
                            <td>
                                <span class="badge bg-{{ $suspension->getStatusBadgeColor() }}">
                                    {{ $suspension->getStatusLabel() }}
                                </span>
                            </td>
                            <td>{{ $suspension->starts_at->format('M d, Y') }}</td>
                            <td>{{ $suspension->ends_at ? $suspension->ends_at->format('M d, Y') : 'Indefinite' }}</td>
                            <td>{{ $suspension->strike_count }}</td>
                            <td>
                                <a href="{{ route('admin.suspensions.show', $suspension) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No suspensions found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $suspensions->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
