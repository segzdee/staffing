<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

/**
 * COM-003: Email Template Seeder
 *
 * Seeds the default email templates for the OvertimeStaff platform.
 */
class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'slug' => 'welcome',
                'name' => 'Welcome Email',
                'category' => 'transactional',
                'subject' => 'Welcome to {{ app_name }}!',
                'variables' => ['user_name', 'user_first_name', 'app_name', 'app_url'],
                'body_html' => $this->getWelcomeHtml(),
                'body_text' => $this->getWelcomeText(),
            ],
            [
                'slug' => 'shift_confirmation',
                'name' => 'Shift Confirmation',
                'category' => 'transactional',
                'subject' => 'Your shift is confirmed',
                'variables' => ['user_name', 'shift_title', 'shift_date', 'shift_time', 'shift_location', 'business_name'],
                'body_html' => $this->getShiftConfirmationHtml(),
                'body_text' => $this->getShiftConfirmationText(),
            ],
            [
                'slug' => 'shift_reminder',
                'name' => 'Shift Reminder',
                'category' => 'reminder',
                'subject' => 'Reminder: Your shift starts tomorrow',
                'variables' => ['user_name', 'shift_title', 'shift_date', 'shift_time', 'shift_location', 'business_name'],
                'body_html' => $this->getShiftReminderHtml(),
                'body_text' => $this->getShiftReminderText(),
            ],
            [
                'slug' => 'payment_received',
                'name' => 'Payment Received',
                'category' => 'transactional',
                'subject' => 'Payment of {{ amount }} received',
                'variables' => ['user_name', 'amount', 'shift_title', 'payment_date'],
                'body_html' => $this->getPaymentReceivedHtml(),
                'body_text' => $this->getPaymentReceivedText(),
            ],
            [
                'slug' => 'verification_approved',
                'name' => 'Verification Approved',
                'category' => 'transactional',
                'subject' => 'Your verification is approved!',
                'variables' => ['user_name', 'verification_type'],
                'body_html' => $this->getVerificationApprovedHtml(),
                'body_text' => $this->getVerificationApprovedText(),
            ],
            [
                'slug' => 'weekly_digest',
                'name' => 'Weekly Digest',
                'category' => 'notification',
                'subject' => 'Your weekly summary',
                'variables' => ['user_name', 'shifts_completed', 'earnings', 'upcoming_shifts', 'new_opportunities'],
                'body_html' => $this->getWeeklyDigestHtml(),
                'body_text' => $this->getWeeklyDigestText(),
            ],
            [
                'slug' => 'new_shift_available',
                'name' => 'New Shift Available',
                'category' => 'notification',
                'subject' => 'New shift opportunity: {{ shift_title }}',
                'variables' => ['user_name', 'shift_title', 'shift_date', 'shift_time', 'hourly_rate', 'business_name', 'shift_url'],
                'body_html' => $this->getNewShiftHtml(),
                'body_text' => $this->getNewShiftText(),
            ],
            [
                'slug' => 'application_received',
                'name' => 'Application Received',
                'category' => 'transactional',
                'subject' => 'Application received for {{ shift_title }}',
                'variables' => ['user_name', 'shift_title', 'business_name', 'worker_name'],
                'body_html' => $this->getApplicationReceivedHtml(),
                'body_text' => $this->getApplicationReceivedText(),
            ],
            [
                'slug' => 'application_accepted',
                'name' => 'Application Accepted',
                'category' => 'transactional',
                'subject' => 'Great news! Your application was accepted',
                'variables' => ['user_name', 'shift_title', 'shift_date', 'shift_time', 'business_name'],
                'body_html' => $this->getApplicationAcceptedHtml(),
                'body_text' => $this->getApplicationAcceptedText(),
            ],
            [
                'slug' => 'password_reset',
                'name' => 'Password Reset',
                'category' => 'transactional',
                'subject' => 'Reset Your Password',
                'variables' => ['user_name', 'reset_url', 'expiry_minutes'],
                'body_html' => $this->getPasswordResetHtml(),
                'body_text' => $this->getPasswordResetText(),
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        $this->command->info('Email templates seeded successfully.');
    }

    private function getWelcomeHtml(): string
    {
        return <<<'HTML'
<h2>Welcome to {{ app_name }}, {{ user_first_name }}!</h2>

<p>We're excited to have you join our community of professionals.</p>

<p>Here's what you can do next:</p>

<ul>
    <li><strong>Complete your profile</strong> - Add your skills, certifications, and availability</li>
    <li><strong>Browse shifts</strong> - Find opportunities that match your expertise</li>
    <li><strong>Get verified</strong> - Increase your chances of getting hired</li>
</ul>

<p style="text-align: center; margin: 30px 0;">
    <a href="{{ app_url }}/dashboard" class="button">Go to Dashboard</a>
</p>

<p>If you have any questions, our support team is here to help.</p>

<p>Best regards,<br>The {{ app_name }} Team</p>
HTML;
    }

    private function getWelcomeText(): string
    {
        return <<<'TEXT'
Welcome to {{ app_name }}, {{ user_first_name }}!

We're excited to have you join our community of professionals.

Here's what you can do next:
- Complete your profile - Add your skills, certifications, and availability
- Browse shifts - Find opportunities that match your expertise
- Get verified - Increase your chances of getting hired

Visit your dashboard: {{ app_url }}/dashboard

If you have any questions, our support team is here to help.

Best regards,
The {{ app_name }} Team
TEXT;
    }

    private function getShiftConfirmationHtml(): string
    {
        return <<<'HTML'
<h2>Your Shift is Confirmed!</h2>

<p>Hi {{ user_name }},</p>

<p>Great news! Your shift has been confirmed. Here are the details:</p>

<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <p><strong>Shift:</strong> {{ shift_title }}</p>
    <p><strong>Date:</strong> {{ shift_date }}</p>
    <p><strong>Time:</strong> {{ shift_time }}</p>
    <p><strong>Location:</strong> {{ shift_location }}</p>
    <p><strong>Business:</strong> {{ business_name }}</p>
</div>

<p><strong>Important reminders:</strong></p>
<ul>
    <li>Arrive 10-15 minutes early</li>
    <li>Bring a valid ID</li>
    <li>Wear appropriate attire for the role</li>
</ul>

<p>Good luck with your shift!</p>
HTML;
    }

    private function getShiftConfirmationText(): string
    {
        return <<<'TEXT'
Your Shift is Confirmed!

Hi {{ user_name }},

Great news! Your shift has been confirmed. Here are the details:

Shift: {{ shift_title }}
Date: {{ shift_date }}
Time: {{ shift_time }}
Location: {{ shift_location }}
Business: {{ business_name }}

Important reminders:
- Arrive 10-15 minutes early
- Bring a valid ID
- Wear appropriate attire for the role

Good luck with your shift!
TEXT;
    }

    private function getShiftReminderHtml(): string
    {
        return <<<'HTML'
<h2>Shift Reminder</h2>

<p>Hi {{ user_name }},</p>

<p>This is a friendly reminder that you have a shift tomorrow.</p>

<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <p><strong>Shift:</strong> {{ shift_title }}</p>
    <p><strong>Date:</strong> {{ shift_date }}</p>
    <p><strong>Time:</strong> {{ shift_time }}</p>
    <p><strong>Location:</strong> {{ shift_location }}</p>
    <p><strong>Business:</strong> {{ business_name }}</p>
</div>

<p>Remember to:</p>
<ul>
    <li>Get a good night's rest</li>
    <li>Plan your route in advance</li>
    <li>Arrive 10-15 minutes early</li>
</ul>

<p>Have a great shift!</p>
HTML;
    }

    private function getShiftReminderText(): string
    {
        return <<<'TEXT'
Shift Reminder

Hi {{ user_name }},

This is a friendly reminder that you have a shift tomorrow.

Shift: {{ shift_title }}
Date: {{ shift_date }}
Time: {{ shift_time }}
Location: {{ shift_location }}
Business: {{ business_name }}

Remember to:
- Get a good night's rest
- Plan your route in advance
- Arrive 10-15 minutes early

Have a great shift!
TEXT;
    }

    private function getPaymentReceivedHtml(): string
    {
        return <<<'HTML'
<h2>Payment Received</h2>

<p>Hi {{ user_name }},</p>

<p>Great news! You've received a payment.</p>

<div style="background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
    <p style="font-size: 24px; font-weight: bold; color: #155724; margin: 0;">{{ amount }}</p>
    <p style="color: #155724; margin: 5px 0 0 0;">has been deposited</p>
</div>

<p><strong>Payment Details:</strong></p>
<ul>
    <li><strong>Shift:</strong> {{ shift_title }}</li>
    <li><strong>Date:</strong> {{ payment_date }}</li>
</ul>

<p>The funds should appear in your account within 1-3 business days, depending on your bank.</p>

<p>Thank you for your hard work!</p>
HTML;
    }

    private function getPaymentReceivedText(): string
    {
        return <<<'TEXT'
Payment Received

Hi {{ user_name }},

Great news! You've received a payment of {{ amount }}.

Payment Details:
- Shift: {{ shift_title }}
- Date: {{ payment_date }}

The funds should appear in your account within 1-3 business days, depending on your bank.

Thank you for your hard work!
TEXT;
    }

    private function getVerificationApprovedHtml(): string
    {
        return <<<'HTML'
<h2>Verification Approved!</h2>

<p>Hi {{ user_name }},</p>

<p>Congratulations! Your {{ verification_type }} has been approved.</p>

<div style="background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
    <p style="font-size: 18px; color: #155724; margin: 0;">&#10003; Verified</p>
</div>

<p>This verification badge will be displayed on your profile, helping you stand out to potential employers and increasing your chances of getting hired.</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="{{ app_url }}/dashboard" class="button">View Your Profile</a>
</p>

<p>Keep up the great work!</p>
HTML;
    }

    private function getVerificationApprovedText(): string
    {
        return <<<'TEXT'
Verification Approved!

Hi {{ user_name }},

Congratulations! Your {{ verification_type }} has been approved.

This verification badge will be displayed on your profile, helping you stand out to potential employers and increasing your chances of getting hired.

View your profile: {{ app_url }}/dashboard

Keep up the great work!
TEXT;
    }

    private function getWeeklyDigestHtml(): string
    {
        return <<<'HTML'
<h2>Your Weekly Summary</h2>

<p>Hi {{ user_name }},</p>

<p>Here's a quick overview of your week:</p>

<div style="display: flex; gap: 20px; margin: 20px 0;">
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; flex: 1; text-align: center;">
        <p style="font-size: 24px; font-weight: bold; margin: 0;">{{ shifts_completed }}</p>
        <p style="color: #666; margin: 5px 0 0 0;">Shifts Completed</p>
    </div>
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; flex: 1; text-align: center;">
        <p style="font-size: 24px; font-weight: bold; color: #28a745; margin: 0;">{{ earnings }}</p>
        <p style="color: #666; margin: 5px 0 0 0;">Earned</p>
    </div>
</div>

<p><strong>Coming up:</strong> You have {{ upcoming_shifts }} shifts scheduled for next week.</p>

<p><strong>New opportunities:</strong> {{ new_opportunities }} new shifts matching your profile were posted this week.</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="{{ app_url }}/shifts" class="button">Browse Available Shifts</a>
</p>

<p>Have a great week ahead!</p>
HTML;
    }

    private function getWeeklyDigestText(): string
    {
        return <<<'TEXT'
Your Weekly Summary

Hi {{ user_name }},

Here's a quick overview of your week:

Shifts Completed: {{ shifts_completed }}
Earned: {{ earnings }}

Coming up: You have {{ upcoming_shifts }} shifts scheduled for next week.

New opportunities: {{ new_opportunities }} new shifts matching your profile were posted this week.

Browse available shifts: {{ app_url }}/shifts

Have a great week ahead!
TEXT;
    }

    private function getNewShiftHtml(): string
    {
        return <<<'HTML'
<h2>New Shift Available</h2>

<p>Hi {{ user_name }},</p>

<p>A new shift matching your profile has been posted!</p>

<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="margin-top: 0;">{{ shift_title }}</h3>
    <p><strong>Date:</strong> {{ shift_date }}</p>
    <p><strong>Time:</strong> {{ shift_time }}</p>
    <p><strong>Rate:</strong> {{ hourly_rate }}/hour</p>
    <p><strong>Business:</strong> {{ business_name }}</p>
</div>

<p style="text-align: center; margin: 30px 0;">
    <a href="{{ shift_url }}" class="button">View Shift Details</a>
</p>

<p>Apply now before it's filled!</p>
HTML;
    }

    private function getNewShiftText(): string
    {
        return <<<'TEXT'
New Shift Available

Hi {{ user_name }},

A new shift matching your profile has been posted!

{{ shift_title }}
Date: {{ shift_date }}
Time: {{ shift_time }}
Rate: {{ hourly_rate }}/hour
Business: {{ business_name }}

View shift details: {{ shift_url }}

Apply now before it's filled!
TEXT;
    }

    private function getApplicationReceivedHtml(): string
    {
        return <<<'HTML'
<h2>New Application Received</h2>

<p>Hi {{ user_name }},</p>

<p>You've received a new application for your shift.</p>

<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <p><strong>Shift:</strong> {{ shift_title }}</p>
    <p><strong>Applicant:</strong> {{ worker_name }}</p>
</div>

<p style="text-align: center; margin: 30px 0;">
    <a href="{{ app_url }}/business/applications" class="button">Review Applications</a>
</p>

<p>Review their profile and respond to keep the best candidates engaged.</p>
HTML;
    }

    private function getApplicationReceivedText(): string
    {
        return <<<'TEXT'
New Application Received

Hi {{ user_name }},

You've received a new application for your shift.

Shift: {{ shift_title }}
Applicant: {{ worker_name }}

Review applications: {{ app_url }}/business/applications

Review their profile and respond to keep the best candidates engaged.
TEXT;
    }

    private function getApplicationAcceptedHtml(): string
    {
        return <<<'HTML'
<h2>Application Accepted!</h2>

<p>Hi {{ user_name }},</p>

<p>Great news! Your application has been accepted.</p>

<div style="background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <p><strong>Shift:</strong> {{ shift_title }}</p>
    <p><strong>Date:</strong> {{ shift_date }}</p>
    <p><strong>Time:</strong> {{ shift_time }}</p>
    <p><strong>Business:</strong> {{ business_name }}</p>
</div>

<p>This shift has been added to your schedule. Make sure to:</p>
<ul>
    <li>Review the shift details carefully</li>
    <li>Arrive on time (10-15 minutes early is recommended)</li>
    <li>Contact the business if you have any questions</li>
</ul>

<p style="text-align: center; margin: 30px 0;">
    <a href="{{ app_url }}/worker/schedule" class="button">View Your Schedule</a>
</p>

<p>Good luck!</p>
HTML;
    }

    private function getApplicationAcceptedText(): string
    {
        return <<<'TEXT'
Application Accepted!

Hi {{ user_name }},

Great news! Your application has been accepted.

Shift: {{ shift_title }}
Date: {{ shift_date }}
Time: {{ shift_time }}
Business: {{ business_name }}

This shift has been added to your schedule. Make sure to:
- Review the shift details carefully
- Arrive on time (10-15 minutes early is recommended)
- Contact the business if you have any questions

View your schedule: {{ app_url }}/worker/schedule

Good luck!
TEXT;
    }

    private function getPasswordResetHtml(): string
    {
        return <<<'HTML'
<h2>Reset Your Password</h2>

<p>Hi {{ user_name }},</p>

<p>We received a request to reset your password. Click the button below to create a new password:</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="{{ reset_url }}" class="button">Reset Password</a>
</p>

<p>This link will expire in {{ expiry_minutes }} minutes.</p>

<p>If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>

<p>For security reasons, never share this link with anyone.</p>
HTML;
    }

    private function getPasswordResetText(): string
    {
        return <<<'TEXT'
Reset Your Password

Hi {{ user_name }},

We received a request to reset your password. Visit the following link to create a new password:

{{ reset_url }}

This link will expire in {{ expiry_minutes }} minutes.

If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.

For security reasons, never share this link with anyone.
TEXT;
    }
}
