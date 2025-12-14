@extends('layouts.app')

@section('css')
<style>
.onboarding-container {
    max-width: 800px;
    margin: 50px auto;
}

.progress-steps {
    display: flex;
    justify-content: center;
    margin-bottom: 40px;
}

.progress-steps .step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e0e0e0;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin: 0 10px;
}

.progress-steps .step.active {
    background: #667eea;
}

.progress-steps .step.completed {
    background: #28a745;
}
</style>
@endsection

@section('content')
<div class="onboarding-container">
    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="step completed">1</div>
        <div class="step active">2</div>
        <div class="step">3</div>
    </div>

    <div class="text-center" style="margin-bottom: 40px;">
        <h1>Agency Profile Setup</h1>
        <p class="lead">Tell us about your staffing agency</p>
    </div>

    <form method="POST" action="{{ url('onboarding/agency') }}" enctype="multipart/form-data">
        @csrf

        <!-- Agency Information -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-users"></i> Agency Information</h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label>Agency Name *</label>
                    <input type="text" name="agency_name" class="form-control" required value="{{ old('agency_name', auth()->user()->name) }}">
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Years in Business *</label>
                            <select name="years_in_business" class="form-control" required>
                                <option value="">Select Years</option>
                                <option value="0-1">Less than 1 year</option>
                                <option value="1-3">1-3 years</option>
                                <option value="3-5">3-5 years</option>
                                <option value="5-10">5-10 years</option>
                                <option value="10+">10+ years</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Number of Active Workers</label>
                            <select name="worker_count" class="form-control">
                                <option value="1-10">1-10 workers</option>
                                <option value="11-25">11-25 workers</option>
                                <option value="26-50">26-50 workers</option>
                                <option value="51-100">51-100 workers</option>
                                <option value="100+">100+ workers</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Agency Description</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Tell businesses about your agency and services...">{{ old('description') }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="phone" class="form-control" required value="{{ old('phone', auth()->user()->phone) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Website</label>
                            <input type="url" name="website" class="form-control" placeholder="https://...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agency Address -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-map-marker"></i> Agency Address</h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label>Street Address *</label>
                    <input type="text" name="address" class="form-control" required value="{{ old('address') }}">
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>City *</label>
                            <input type="text" name="city" class="form-control" required value="{{ old('city') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>State *</label>
                            <input type="text" name="state" class="form-control" required value="{{ old('state') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>ZIP Code *</label>
                            <input type="text" name="zip_code" class="form-control" required value="{{ old('zip_code') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Licensing & Verification -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-certificate"></i> Licensing & Verification</h4>
            </div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Verified agencies get priority in search results and access to premium features.
                </div>

                <div class="form-group">
                    <label>Staffing Agency License Number *</label>
                    <input type="text" name="license_number" class="form-control" required placeholder="License #">
                    <small class="text-muted">Your state-issued staffing agency license</small>
                </div>

                <div class="form-group">
                    <label>Employer Identification Number (EIN) *</label>
                    <input type="text" name="ein" class="form-control" required placeholder="XX-XXXXXXX">
                </div>

                <div class="form-group">
                    <label>Upload Agency License (PDF, JPG, PNG) *</label>
                    <input type="file" name="license_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <div class="form-group">
                    <label>Upload Proof of Insurance (PDF, JPG, PNG)</label>
                    <input type="file" name="insurance_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    <small class="text-muted">Workers' compensation and liability insurance</small>
                </div>
            </div>
        </div>

        <!-- Industries & Services -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-industry"></i> Industries & Services</h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label>Industries You Serve *</label>
                    <select name="industries[]" class="form-control" multiple size="7" required>
                        <option value="hospitality">Hospitality</option>
                        <option value="retail">Retail</option>
                        <option value="warehouse">Warehouse/Logistics</option>
                        <option value="construction">Construction</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="manufacturing">Manufacturing</option>
                        <option value="events">Events</option>
                        <option value="transportation">Transportation</option>
                        <option value="security">Security</option>
                        <option value="other">Other</option>
                    </select>
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                </div>

                <div class="form-group">
                    <label>Worker Roles You Provide</label>
                    <select name="worker_roles[]" class="form-control" multiple size="5">
                        <option value="general_labor">General Labor</option>
                        <option value="warehouse">Warehouse Worker</option>
                        <option value="forklift">Forklift Operator</option>
                        <option value="customer_service">Customer Service</option>
                        <option value="cashier">Cashier</option>
                        <option value="food_service">Food Service</option>
                        <option value="delivery">Delivery Driver</option>
                        <option value="security">Security</option>
                        <option value="event_staff">Event Staff</option>
                        <option value="cleaning">Cleaning Staff</option>
                    </select>
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                </div>

                <div class="form-group">
                    <label>Average Shifts Filled Per Month</label>
                    <select name="avg_shifts_per_month" class="form-control">
                        <option value="1-10">1-10 shifts</option>
                        <option value="11-25">11-25 shifts</option>
                        <option value="26-50">26-50 shifts</option>
                        <option value="51-100">51-100 shifts</option>
                        <option value="100+">100+ shifts</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Payment Setup -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-credit-card"></i> Payment Setup</h4>
            </div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Set up your payment method to receive payouts for filled shifts. Stripe Connect enables instant payouts to your workers.
                </div>

                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="setup_payment_now" value="1" checked>
                        Set up payment method now (required to fill shifts)
                    </label>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="form-group text-center">
            <a href="{{ url('onboarding') }}" class="btn btn-default btn-lg">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            <button type="submit" class="btn btn-primary btn-lg">
                Continue <i class="fa fa-arrow-right"></i>
            </button>
        </div>
    </form>
</div>
@endsection
