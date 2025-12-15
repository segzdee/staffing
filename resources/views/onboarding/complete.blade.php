@extends('layouts.authenticated')

@section('css')
<style>
.onboarding-container {
    max-width: 900px;
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

.progress-steps .step.completed {
    background: #28a745;
}

.success-icon {
    font-size: 80px;
    color: #28a745;
    margin-bottom: 20px;
}

.next-steps-card {
    background: #f8f9fa;
    border-left: 4px solid #667eea;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 4px;
}

.next-steps-card h4 {
    margin-top: 0;
    color: #667eea;
}

.next-steps-card ul {
    margin-bottom: 0;
}

.feature-box {
    text-align: center;
    padding: 30px;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 20px;
    transition: all 0.3s;
}

.feature-box:hover {
    border-color: #667eea;
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.feature-box .icon {
    font-size: 48px;
    color: #667eea;
    margin-bottom: 15px;
}

.feature-box h4 {
    color: #333;
    margin-bottom: 10px;
}

.feature-box p {
    color: #666;
    margin: 0;
}
</style>
@endsection

@section('content')
<div class="onboarding-container">
    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="step completed">1</div>
        <div class="step completed">2</div>
        <div class="step completed">3</div>
    </div>

    <!-- Success Message -->
    <div class="text-center" style="margin-bottom: 40px;">
        <div class="success-icon">
            <i class="fa fa-check-circle"></i>
        </div>
        <h1>Welcome to OvertimeStaff!</h1>
        <p class="lead">Your account is now set up and ready to go</p>
    </div>

    <!-- User Type Specific Next Steps -->
    @if(auth()->user()->user_type === 'worker')
        <!-- Worker Next Steps -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3><i class="fa fa-rocket"></i> What's Next?</h3>
            </div>
            <div class="panel-body">
                <div class="next-steps-card">
                    <h4>1. Complete Your Profile</h4>
                    <ul>
                        <li>Upload a professional profile photo</li>
                        <li>Add more skills and certifications</li>
                        <li>Write a compelling bio to stand out</li>
                    </ul>
                </div>

                <div class="next-steps-card">
                    <h4>2. Browse Available Shifts</h4>
                    <ul>
                        <li>Check out shifts in your area</li>
                        <li>Filter by industry, pay rate, and date</li>
                        <li>Apply to shifts that match your skills</li>
                    </ul>
                </div>

                <div class="next-steps-card">
                    <h4>3. Set Up Instant Payouts</h4>
                    <ul>
                        <li>Connect your bank account or debit card</li>
                        <li>Get paid within 15 minutes of completing shifts</li>
                        <li>Track your earnings in real-time</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Worker Features -->
        <div class="row">
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="icon">
                        <i class="fa fa-search"></i>
                    </div>
                    <h4>Find Shifts</h4>
                    <p>Browse thousands of available shifts in your area</p>
                    <a href="{{ url('shifts') }}" class="btn btn-primary btn-sm" style="margin-top: 10px;">Browse Shifts</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="icon">
                        <i class="fa fa-user"></i>
                    </div>
                    <h4>Edit Profile</h4>
                    <p>Complete your profile to attract more opportunities</p>
                    <a href="{{ url('settings/page') }}" class="btn btn-default btn-sm" style="margin-top: 10px;">Edit Profile</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="icon">
                        <i class="fa fa-credit-card"></i>
                    </div>
                    <h4>Payment Setup</h4>
                    <p>Set up instant payouts to get paid faster</p>
                    <a href="{{ url('settings/payments') }}" class="btn btn-default btn-sm" style="margin-top: 10px;">Setup Payments</a>
                </div>
            </div>
        </div>

    @elseif(auth()->user()->user_type === 'business')
        <!-- Business Next Steps -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3><i class="fa fa-rocket"></i> What's Next?</h3>
            </div>
            <div class="panel-body">
                <div class="next-steps-card">
                    <h4>1. Post Your First Shift</h4>
                    <ul>
                        <li>Create a detailed shift posting</li>
                        <li>Set competitive pay rates</li>
                        <li>Get AI-powered worker recommendations</li>
                    </ul>
                </div>

                <div class="next-steps-card">
                    <h4>2. Review Applications</h4>
                    <ul>
                        <li>View qualified worker profiles</li>
                        <li>Check ratings and experience</li>
                        <li>Assign workers to your shifts</li>
                    </ul>
                </div>

                <div class="next-steps-card">
                    <h4>3. Set Up Payment Method</h4>
                    <ul>
                        <li>Add your business payment method</li>
                        <li>Funds held securely in escrow</li>
                        <li>Workers paid instantly after completion</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Business Features -->
        <div class="row">
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="icon">
                        <i class="fa fa-plus-circle"></i>
                    </div>
                    <h4>Post a Shift</h4>
                    <p>Create your first shift posting and find workers</p>
                    <a href="{{ url('shifts/create') }}" class="btn btn-primary btn-sm" style="margin-top: 10px;">Post Shift</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="icon">
                        <i class="fa fa-users"></i>
                    </div>
                    <h4>Find Workers</h4>
                    <p>Browse worker profiles and invite them to shifts</p>
                    <a href="{{ url('workers/browse') }}" class="btn btn-default btn-sm" style="margin-top: 10px;">Browse Workers</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="icon">
                        <i class="fa fa-credit-card"></i>
                    </div>
                    <h4>Payment Setup</h4>
                    <p>Add payment method to post shifts</p>
                    <a href="{{ url('settings/payments') }}" class="btn btn-default btn-sm" style="margin-top: 10px;">Setup Payments</a>
                </div>
            </div>
        </div>

    @elseif(auth()->user()->user_type === 'agency')
        <!-- Agency Next Steps -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3><i class="fa fa-rocket"></i> What's Next?</h3>
            </div>
            <div class="panel-body">
                <div class="next-steps-card">
                    <h4>1. Add Your Workers</h4>
                    <ul>
                        <li>Import your existing worker database</li>
                        <li>Invite workers to join the platform</li>
                        <li>Manage worker profiles and availability</li>
                    </ul>
                </div>

                <div class="next-steps-card">
                    <h4>2. Browse Shift Opportunities</h4>
                    <ul>
                        <li>Find shifts that match your workers' skills</li>
                        <li>Assign workers to multiple shifts at once</li>
                        <li>Track commission and earnings</li>
                    </ul>
                </div>

                <div class="next-steps-card">
                    <h4>3. Verify Your Agency</h4>
                    <ul>
                        <li>Submit your licensing documents</li>
                        <li>Get verified badge for credibility</li>
                        <li>Access premium features</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Agency Features -->
        <div class="row">
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="icon">
                        <i class="fa fa-user-plus"></i>
                    </div>
                    <h4>Add Workers</h4>
                    <p>Import and manage your worker pool</p>
                    <a href="{{ url('agency/workers') }}" class="btn btn-primary btn-sm" style="margin-top: 10px;">Manage Workers</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="icon">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <h4>Browse Shifts</h4>
                    <p>Find opportunities for your workers</p>
                    <a href="{{ url('shifts') }}" class="btn btn-default btn-sm" style="margin-top: 10px;">Browse Shifts</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="icon">
                        <i class="fa fa-chart-line"></i>
                    </div>
                    <h4>Analytics</h4>
                    <p>Track performance and earnings</p>
                    <a href="{{ url('agency/analytics') }}" class="btn btn-default btn-sm" style="margin-top: 10px;">View Analytics</a>
                </div>
            </div>
        </div>
    @endif

    <!-- Help Resources -->
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3><i class="fa fa-life-ring"></i> Need Help?</h3>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <h4><i class="fa fa-book"></i> Knowledge Base</h4>
                    <p>Browse our comprehensive guides and tutorials to get the most out of OvertimeStaff.</p>
                    <a href="{{ url('help') }}" class="btn btn-default btn-sm">View Guides</a>
                </div>
                <div class="col-md-6">
                    <h4><i class="fa fa-question-circle"></i> Contact Support</h4>
                    <p>Have questions? Our support team is here to help you every step of the way.</p>
                    <a href="{{ url('contact') }}" class="btn btn-default btn-sm">Contact Us</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Go to Dashboard Button -->
    <div class="text-center" style="margin-top: 40px;">
        <a href="{{ url('dashboard') }}" class="btn btn-primary btn-lg">
            <i class="fa fa-tachometer-alt"></i> Go to Dashboard
        </a>
    </div>
</div>
@endsection
