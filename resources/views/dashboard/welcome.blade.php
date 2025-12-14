@extends('layouts.app')

@section('css')
<style>
.welcome-container {
    max-width: 1000px;
    margin: 50px auto;
}

.welcome-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 40px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 40px;
}

.welcome-header h1 {
    font-size: 42px;
    margin-bottom: 15px;
}

.welcome-header p {
    font-size: 20px;
    opacity: 0.9;
}

.benefit-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    height: 100%;
    transition: all 0.3s;
}

.benefit-card:hover {
    border-color: #667eea;
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.benefit-card .icon {
    font-size: 64px;
    color: #667eea;
    margin-bottom: 20px;
}

.benefit-card h3 {
    color: #333;
    margin-bottom: 15px;
}

.benefit-card p {
    color: #666;
    margin: 0;
}

.cta-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    margin-top: 40px;
}

.cta-section h2 {
    color: #333;
    margin-bottom: 20px;
}

.cta-section p {
    color: #666;
    font-size: 18px;
    margin-bottom: 30px;
}

.progress-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
}

.progress-indicator .step {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #e0e0e0;
}

.progress-indicator .step.active {
    background: #667eea;
    width: 40px;
}

.stats-banner {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 40px;
}

.stats-banner .row {
    text-align: center;
}

.stats-banner .stat {
    padding: 15px;
}

.stats-banner .stat h3 {
    color: #667eea;
    font-size: 36px;
    margin-bottom: 5px;
}

.stats-banner .stat p {
    color: #666;
    margin: 0;
}
</style>
@endsection

@section('content')
<div class="welcome-container">
    <!-- Welcome Header -->
    <div class="welcome-header">
        <h1>Welcome, {{ auth()->user()->name }}!</h1>
        <p>You're just a few steps away from getting started</p>
    </div>

    <!-- Platform Stats -->
    <div class="stats-banner">
        <div class="row">
            <div class="col-md-3">
                <div class="stat">
                    <h3>10,000+</h3>
                    <p>Active Workers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat">
                    <h3>5,000+</h3>
                    <p>Businesses</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat">
                    <h3>50,000+</h3>
                    <p>Shifts Completed</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat">
                    <h3>15 min</h3>
                    <p>Average Payout Time</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Benefits Section -->
    <div class="row" style="margin-bottom: 40px;">
        <div class="col-md-12">
            <h2 class="text-center" style="margin-bottom: 30px;">Complete Your Profile to Unlock</h2>
        </div>

        <div class="col-md-4">
            <div class="benefit-card">
                <div class="icon">
                    <i class="fa fa-bolt"></i>
                </div>
                <h3>Instant Payouts</h3>
                <p>Get paid within 15 minutes of completing shifts. Money in your account when you need it.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="benefit-card">
                <div class="icon">
                    <i class="fa fa-robot"></i>
                </div>
                <h3>AI-Powered Matching</h3>
                <p>Our intelligent system connects you with the perfect shifts or workers based on skills and preferences.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="benefit-card">
                <div class="icon">
                    <i class="fa fa-shield-check"></i>
                </div>
                <h3>Secure Platform</h3>
                <p>Payments held in escrow, verified profiles, and comprehensive rating system for peace of mind.</p>
            </div>
        </div>
    </div>

    <div class="row" style="margin-bottom: 40px;">
        <div class="col-md-4">
            <div class="benefit-card">
                <div class="icon">
                    <i class="fa fa-mobile-alt"></i>
                </div>
                <h3>Mobile Friendly</h3>
                <p>Manage shifts, communicate, and get paid on the go with our mobile-optimized platform.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="benefit-card">
                <div class="icon">
                    <i class="fa fa-comments"></i>
                </div>
                <h3>Real-Time Messaging</h3>
                <p>Built-in chat system to communicate with workers or businesses directly about shifts.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="benefit-card">
                <div class="icon">
                    <i class="fa fa-chart-line"></i>
                </div>
                <h3>Track Everything</h3>
                <p>Comprehensive dashboard to track shifts, earnings, ratings, and performance metrics.</p>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="cta-section">
        <h2>Ready to Get Started?</h2>
        <p>Complete your profile in just a few minutes and start {{ auth()->user()->user_type === 'worker' ? 'finding shifts' : 'posting shifts' }} today.</p>

        <a href="{{ url('onboarding') }}" class="btn btn-primary btn-lg">
            <i class="fa fa-rocket"></i> Complete Setup Now
        </a>

        <div class="progress-indicator">
            <div class="step active"></div>
            <div class="step"></div>
            <div class="step"></div>
        </div>
        <p style="margin-top: 10px; color: #999;">Only takes 3 minutes</p>
    </div>

    <!-- Quick Links -->
    <div class="row" style="margin-top: 40px;">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4><i class="fa fa-question-circle"></i> Frequently Asked Questions</h4>
                </div>
                <div class="panel-body">
                    <div style="margin-bottom: 15px;">
                        <strong>How do instant payouts work?</strong>
                        <p style="margin: 5px 0 0 0; color: #666;">Funds are held in escrow during shifts and released to workers within 15 minutes of completion.</p>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <strong>Is there a fee to join?</strong>
                        <p style="margin: 5px 0 0 0; color: #666;">Joining is completely free. We only charge a small platform fee when shifts are completed.</p>
                    </div>
                    <div style="margin-bottom: 0;">
                        <strong>How do I get verified?</strong>
                        <p style="margin: 5px 0 0 0; color: #666;">Complete your profile and submit required documents. Verification typically takes 24-48 hours.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4><i class="fa fa-life-ring"></i> Need Help?</h4>
                </div>
                <div class="panel-body">
                    <p>Our support team is here to help you get started.</p>
                    <div style="margin-top: 15px;">
                        <a href="{{ url('help') }}" class="btn btn-default btn-block">
                            <i class="fa fa-book"></i> View Help Center
                        </a>
                    </div>
                    <div style="margin-top: 10px;">
                        <a href="{{ url('contact') }}" class="btn btn-default btn-block">
                            <i class="fa fa-envelope"></i> Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Skip Option (not recommended) -->
    <div class="text-center" style="margin-top: 20px; margin-bottom: 40px;">
        <p class="text-muted">
            Want to explore first?
            <a href="{{ url('dashboard') }}?skip_onboarding=1" style="color: #999;">
                Skip setup for now
            </a>
        </p>
    </div>
</div>
@endsection
