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
        <h1>Business Profile Setup</h1>
        <p class="lead">Tell us about your business and what you need</p>
    </div>

    <form method="POST" action="{{ url('onboarding/business') }}">
        @csrf

        <!-- Business Information -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-building"></i> Business Information</h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label>Business Name *</label>
                    <input type="text" name="business_name" class="form-control" required value="{{ old('business_name', auth()->user()->name) }}">
                </div>

                <div class="form-group">
                    <label>Industry *</label>
                    <select name="industry" class="form-control" required>
                        <option value="">Select Industry</option>
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
                </div>

                <div class="form-group">
                    <label>Business Description</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Tell workers about your business...">{{ old('description') }}</textarea>
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

        <!-- Business Address -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-map-marker"></i> Business Address</h4>
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

        <!-- Business Verification (Optional) -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-certificate"></i> Business Verification (Optional)</h4>
            </div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Verified businesses get priority in search results and attract more qualified workers.
                </div>

                <div class="form-group">
                    <label>Employer Identification Number (EIN)</label>
                    <input type="text" name="ein" class="form-control" placeholder="XX-XXXXXXX">
                    <small class="text-muted">Your business tax ID</small>
                </div>

                <div class="form-group">
                    <label>Business License Number</label>
                    <input type="text" name="business_license_number" class="form-control">
                </div>

                <div class="form-group">
                    <label>Upload Business License (PDF, JPG, PNG)</label>
                    <input type="file" name="license_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>
        </div>

        <!-- Shift Preferences -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-calendar"></i> Shift Preferences</h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label>Typical Worker Roles Needed</label>
                    <select name="typical_roles[]" class="form-control" multiple size="5">
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

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Average Shifts Per Month</label>
                            <select name="avg_shifts_per_month" class="form-control">
                                <option value="1-5">1-5 shifts</option>
                                <option value="6-10">6-10 shifts</option>
                                <option value="11-20">11-20 shifts</option>
                                <option value="21-50">21-50 shifts</option>
                                <option value="50+">50+ shifts</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Typical Shift Duration</label>
                            <select name="typical_shift_duration" class="form-control">
                                <option value="2-4">2-4 hours</option>
                                <option value="4-6">4-6 hours</option>
                                <option value="6-8">6-8 hours (Standard)</option>
                                <option value="8-12">8-12 hours</option>
                                <option value="12+">12+ hours</option>
                            </select>
                        </div>
                    </div>
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
                    <i class="fa fa-info-circle"></i> You'll need a payment method to post shifts. Funds are held securely until shift completion.
                </div>

                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="setup_payment_now" value="1" checked>
                        Set up payment method now (required to post shifts)
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
