@extends('layouts.authenticated')

@section('title', 'Edit Client - ' . $client->company_name)

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <nav class="flex mb-4" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="{{ route('dashboard.index') }}"
                                class="text-sm font-medium text-muted-foreground hover:text-foreground">Dashboard</a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-muted-foreground mx-1" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 9 4-4-4-4" />
                                </svg>
                                <a href="{{ route('agency.clients.index') }}"
                                    class="ml-1 text-sm font-medium text-muted-foreground hover:text-foreground md:ml-2">Clients</a>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-muted-foreground mx-1" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 9 4-4-4-4" />
                                </svg>
                                <a href="{{ route('agency.clients.show', $client->id) }}"
                                    class="ml-1 text-sm font-medium text-muted-foreground hover:text-foreground md:ml-2">{{ $client->company_name }}</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-muted-foreground mx-1" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 9 4-4-4-4" />
                                </svg>
                                <span class="ml-1 text-sm font-medium text-foreground md:ml-2">Edit</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <div class="bg-card border rounded-lg shadow-sm">
                    <div class="p-6 border-b">
                        <h5 class="text-lg font-semibold flex items-center"><i class="fas fa-edit me-2"></i>Edit Client</h5>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('agency.clients.update', $client->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <h6 class="text-muted-foreground mb-3 font-medium">Company Information</h6>

                            <div class="grid md:grid-cols-2 gap-6 mb-4">
                                <div class="space-y-2">
                                    <x-ui.label for="company_name"
                                        class="after:content-['*'] after:ml-0.5 after:text-destructive"
                                        value="Company Name" />
                                    <x-ui.input type="text" name="company_name" id="company_name"
                                        value="{{ old('company_name', $client->company_name) }}" required />
                                    @error('company_name')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <x-ui.label for="industry" value="Industry" />
                                    <x-ui.input type="text" name="industry" id="industry"
                                        value="{{ old('industry', $client->industry) }}"
                                        placeholder="e.g., Hospitality, Healthcare" />
                                    @error('industry')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3 space-y-2">
                                <x-ui.label for="status" value="Status" />
                                <x-ui.select name="status" id="status">
                                    <option value="active" {{ old('status', $client->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status', $client->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </x-ui.select>
                            </div>

                            <hr class="my-6 border-border">
                            <h6 class="text-muted-foreground mb-3 font-medium">Contact Information</h6>

                            <div class="grid md:grid-cols-2 gap-6 mb-4">
                                <div class="space-y-2">
                                    <x-ui.label for="contact_name" value="Contact Name" />
                                    <x-ui.input type="text" name="contact_name" id="contact_name"
                                        value="{{ old('contact_name', $client->contact_name) }}" />
                                    @error('contact_name')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <x-ui.label for="contact_email" value="Contact Email" />
                                    <x-ui.input type="email" name="contact_email" id="contact_email"
                                        value="{{ old('contact_email', $client->contact_email) }}" />
                                    @error('contact_email')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6 mb-4">
                                <div class="space-y-2">
                                    <x-ui.label for="contact_phone" value="Contact Phone" />
                                    <x-ui.input type="tel" name="contact_phone" id="contact_phone"
                                        value="{{ old('contact_phone', $client->contact_phone) }}" />
                                    @error('contact_phone')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <x-ui.label for="commission_rate" value="Commission Rate (%)" />
                                    <x-ui.input type="number" name="commission_rate" id="commission_rate"
                                        value="{{ old('commission_rate', $client->commission_rate ?? 10) }}" min="0"
                                        max="50" step="0.5" />
                                    @error('commission_rate')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <hr class="my-6 border-border">
                            <h6 class="text-muted-foreground mb-3 font-medium">Location</h6>

                            <div class="mb-3 space-y-2">
                                <x-ui.label for="address" value="Address" />
                                <x-ui.input type="text" name="address" id="address"
                                    value="{{ old('address', $client->address) }}" />
                                @error('address')
                                    <div class="text-sm text-destructive">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="grid md:grid-cols-3 gap-6 mb-4">
                                <div class="space-y-2">
                                    <x-ui.label for="city" value="City" />
                                    <x-ui.input type="text" name="city" id="city"
                                        value="{{ old('city', $client->city) }}" />
                                    @error('city')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <x-ui.label for="state" value="State/Province" />
                                    <x-ui.input type="text" name="state" id="state"
                                        value="{{ old('state', $client->state) }}" />
                                    @error('state')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <x-ui.label for="zip_code" value="ZIP/Postal Code" />
                                    <x-ui.input type="text" name="zip_code" id="zip_code"
                                        value="{{ old('zip_code', $client->zip_code) }}" />
                                    @error('zip_code')
                                        <div class="text-sm text-destructive">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-6 space-y-2">
                                <x-ui.label for="notes" value="Notes" />
                                <x-ui.textarea name="notes" id="notes" rows="3"
                                    placeholder="Internal notes about this client">{{ old('notes', $client->notes) }}</x-ui.textarea>
                                @error('notes')
                                    <div class="text-sm text-destructive">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="flex justify-between items-center">
                                <x-ui.button variant="outline" tag="a"
                                    href="{{ route('agency.clients.show', $client->id) }}">
                                    <i class="fas fa-arrow-left me-1"></i>Cancel
                                </x-ui.button>
                                <x-ui.button type="submit">
                                    <i class="fas fa-save me-1"></i>Save Changes
                                </x-ui.button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection