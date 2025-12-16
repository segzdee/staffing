@extends('layouts.authenticated')

@section('title', 'Add New Client')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('agency.clients.index') }}">Clients</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add Client</li>
                </ol>
            </nav>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Add New Client</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('agency.clients.store') }}" method="POST">
                        @csrf

                        <h6 class="text-muted mb-3">Company Information</h6>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" id="company_name"
                                       class="form-control @error('company_name') is-invalid @enderror"
                                       value="{{ old('company_name') }}" required>
                                @error('company_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="industry" class="form-label">Industry</label>
                                <input type="text" name="industry" id="industry"
                                       class="form-control @error('industry') is-invalid @enderror"
                                       value="{{ old('industry') }}"
                                       placeholder="e.g., Hospitality, Healthcare">
                                @error('industry')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="text-muted mb-3">Contact Information</h6>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="contact_name" class="form-label">Contact Name</label>
                                <input type="text" name="contact_name" id="contact_name"
                                       class="form-control @error('contact_name') is-invalid @enderror"
                                       value="{{ old('contact_name') }}">
                                @error('contact_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="contact_email" class="form-label">Contact Email</label>
                                <input type="email" name="contact_email" id="contact_email"
                                       class="form-control @error('contact_email') is-invalid @enderror"
                                       value="{{ old('contact_email') }}">
                                @error('contact_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="contact_phone" class="form-label">Contact Phone</label>
                                <input type="tel" name="contact_phone" id="contact_phone"
                                       class="form-control @error('contact_phone') is-invalid @enderror"
                                       value="{{ old('contact_phone') }}">
                                @error('contact_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="commission_rate" class="form-label">Commission Rate (%)</label>
                                <input type="number" name="commission_rate" id="commission_rate"
                                       class="form-control @error('commission_rate') is-invalid @enderror"
                                       value="{{ old('commission_rate', 10) }}" min="0" max="50" step="0.5">
                                @error('commission_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="text-muted mb-3">Location</h6>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" name="address" id="address"
                                   class="form-control @error('address') is-invalid @enderror"
                                   value="{{ old('address') }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="city" class="form-label">City</label>
                                <input type="text" name="city" id="city"
                                       class="form-control @error('city') is-invalid @enderror"
                                       value="{{ old('city') }}">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="state" class="form-label">State/Province</label>
                                <input type="text" name="state" id="state"
                                       class="form-control @error('state') is-invalid @enderror"
                                       value="{{ old('state') }}">
                                @error('state')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                                <input type="text" name="zip_code" id="zip_code"
                                       class="form-control @error('zip_code') is-invalid @enderror"
                                       value="{{ old('zip_code') }}">
                                @error('zip_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes" rows="3"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Internal notes about this client">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('agency.clients.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Add Client
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
