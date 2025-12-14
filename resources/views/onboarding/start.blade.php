@extends('layouts.app')

@section('css')
<style>
.onboarding-container {
    max-width: 1000px;
    margin: 50px auto;
}

.user-type-card {
    border: 3px solid #e0e0e0;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s;
    cursor: pointer;
    height: 100%;
    background: white;
}

.user-type-card:hover {
    border-color: #667eea;
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.user-type-card.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.user-type-card .icon {
    font-size: 64px;
    margin-bottom: 20px;
    color: #667eea;
}

.user-type-card.selected .icon {
    color: white;
}

.user-type-card h3 {
    margin: 15px 0;
    font-weight: bold;
}

.user-type-card p {
    margin: 0;
    color: #666;
}

.user-type-card.selected p {
    color: rgba(255,255,255,0.9);
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
        <div class="step active">1</div>
        <div class="step">2</div>
        <div class="step">3</div>
    </div>

    <div class="text-center" style="margin-bottom: 40px;">
        <h1>Welcome to OvertimeStaff!</h1>
        <p class="lead">Let's get your account set up. First, tell us what brings you here.</p>
    </div>

    <form method="POST" action="{{ url('onboarding') }}" id="onboardingForm">
        @csrf
        <input type="hidden" name="user_type" id="selectedUserType" value="">

        <div class="row">
            <!-- Worker Option -->
            <div class="col-md-4">
                <div class="user-type-card" onclick="selectUserType('worker')">
                    <div class="icon">
                        <i class="fa fa-user-hard-hat"></i>
                    </div>
                    <h3>I'm Looking for Work</h3>
                    <p>Find flexible shift opportunities that match your skills and schedule</p>
                    <ul class="text-left" style="margin-top: 20px; list-style: none; padding: 0;">
                        <li><i class="fa fa-check"></i> Browse available shifts</li>
                        <li><i class="fa fa-check"></i> Apply with one click</li>
                        <li><i class="fa fa-check"></i> Get paid instantly</li>
                        <li><i class="fa fa-check"></i> Build your reputation</li>
                    </ul>
                </div>
            </div>

            <!-- Business Option -->
            <div class="col-md-4">
                <div class="user-type-card" onclick="selectUserType('business')">
                    <div class="icon">
                        <i class="fa fa-building"></i>
                    </div>
                    <h3>I Need Workers</h3>
                    <p>Post shifts and find qualified workers instantly</p>
                    <ul class="text-left" style="margin-top: 20px; list-style: none; padding: 0;">
                        <li><i class="fa fa-check"></i> Post shifts in minutes</li>
                        <li><i class="fa fa-check"></i> AI-powered matching</li>
                        <li><i class="fa fa-check"></i> Manage your team</li>
                        <li><i class="fa fa-check"></i> Secure payments</li>
                    </ul>
                </div>
            </div>

            <!-- Agency Option -->
            <div class="col-md-4">
                <div class="user-type-card" onclick="selectUserType('agency')">
                    <div class="icon">
                        <i class="fa fa-users"></i>
                    </div>
                    <h3>I'm a Staffing Agency</h3>
                    <p>Manage multiple workers and fill shifts for businesses</p>
                    <ul class="text-left" style="margin-top: 20px; list-style: none; padding: 0;">
                        <li><i class="fa fa-check"></i> Manage worker pool</li>
                        <li><i class="fa fa-check"></i> Bulk shift management</li>
                        <li><i class="fa fa-check"></i> Commission tracking</li>
                        <li><i class="fa fa-check"></i> Advanced analytics</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Continue Button -->
        <div class="text-center" style="margin-top: 40px;">
            <button type="submit" class="btn btn-primary btn-lg" id="continueBtn" disabled>
                Continue <i class="fa fa-arrow-right"></i>
            </button>
        </div>

        <div class="text-center" style="margin-top: 20px;">
            <p class="text-muted">Already completed onboarding? <a href="{{ url('dashboard') }}">Go to Dashboard</a></p>
        </div>
    </form>
</div>
@endsection

@section('javascript')
<script>
let selectedType = null;

function selectUserType(type) {
    // Remove previous selection
    document.querySelectorAll('.user-type-card').forEach(card => {
        card.classList.remove('selected');
    });

    // Add selection to clicked card
    event.currentTarget.classList.add('selected');

    // Update hidden input
    document.getElementById('selectedUserType').value = type;
    selectedType = type;

    // Enable continue button
    document.getElementById('continueBtn').disabled = false;
}

// Prevent form submission if no type selected
document.getElementById('onboardingForm').addEventListener('submit', function(e) {
    if (!selectedType) {
        e.preventDefault();
        alert('Please select an account type to continue');
    }
});
</script>
@endsection
