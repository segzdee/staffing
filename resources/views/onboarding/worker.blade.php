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

.skill-tag {
    display: inline-block;
    padding: 8px 15px;
    background: #e7f3ff;
    border: 2px solid #ccc;
    border-radius: 20px;
    margin: 5px;
    cursor: pointer;
    transition: all 0.3s;
}

.skill-tag:hover {
    border-color: #667eea;
}

.skill-tag.selected {
    background: #667eea;
    color: white;
    border-color: #667eea;
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
        <h1>Worker Profile Setup</h1>
        <p class="lead">Help us match you with the perfect shifts</p>
    </div>

    <form method="POST" action="{{ url('onboarding/worker') }}">
        @csrf

        <!-- Basic Information -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-user"></i> Basic Information</h4>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="phone" class="form-control" required value="{{ old('phone', auth()->user()->phone) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Date of Birth *</label>
                            <input type="date" name="date_of_birth" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>City *</label>
                    <input type="text" name="city" class="form-control" required value="{{ old('city', auth()->user()->city) }}">
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>State *</label>
                            <input type="text" name="state" class="form-control" required value="{{ old('state', auth()->user()->state) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>ZIP Code *</label>
                            <input type="text" name="zip_code" class="form-control" required value="{{ old('zip_code', auth()->user()->zip_code) }}">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" class="form-control" rows="4" placeholder="Tell businesses about yourself...">{{ old('bio') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Skills -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-wrench"></i> Your Skills</h4>
            </div>
            <div class="panel-body">
                <p>Select all skills that apply to you:</p>
                <div id="skillsContainer">
                    @php
                    $commonSkills = ['Forklift Operation', 'Warehouse', 'Customer Service', 'Cashier', 'Food Service',
                                     'Delivery', 'Data Entry', 'Cleaning', 'Security', 'Retail', 'Event Staff',
                                     'General Labor', 'Assembly', 'Packing', 'Loading/Unloading'];
                    @endphp

                    @foreach($commonSkills as $skill)
                        <span class="skill-tag" onclick="toggleSkill(this, '{{ $skill }}')">
                            {{ $skill }}
                        </span>
                    @endforeach
                </div>
                <input type="hidden" name="skills" id="selectedSkills" value="">

                <div class="form-group" style="margin-top: 20px;">
                    <label>Other Skills (comma-separated)</label>
                    <input type="text" name="other_skills" class="form-control" placeholder="e.g., Computer Repair, Welding, etc.">
                </div>
            </div>
        </div>

        <!-- Experience -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-briefcase"></i> Experience</h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label>Years of Work Experience</label>
                    <select name="years_experience" class="form-control">
                        <option value="0">Less than 1 year</option>
                        <option value="1">1-2 years</option>
                        <option value="3">3-5 years</option>
                        <option value="6">6-10 years</option>
                        <option value="11">10+ years</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Industries You've Worked In</label>
                    <select name="industries[]" class="form-control" multiple size="5">
                        <option value="hospitality">Hospitality</option>
                        <option value="retail">Retail</option>
                        <option value="warehouse">Warehouse</option>
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
            </div>
        </div>

        <!-- Payment Information -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-credit-card"></i> Payment Information</h4>
            </div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> You'll receive instant payouts after completing shifts. Payment details can be added later.
                </div>

                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="setup_payment_now" value="1">
                        Set up payment method now (recommended)
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

@section('javascript')
<script>
let selectedSkills = [];

function toggleSkill(element, skill) {
    element.classList.toggle('selected');

    if (selectedSkills.includes(skill)) {
        selectedSkills = selectedSkills.filter(s => s !== skill);
    } else {
        selectedSkills.push(skill);
    }

    document.getElementById('selectedSkills').value = selectedSkills.join(',');
}
</script>
@endsection
