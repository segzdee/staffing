<?php

use App\Mail\TemplatedEmail;
use App\Models\EmailLog;
use App\Models\EmailPreference;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

describe('EmailTemplate Model', function () {
    it('can create an email template', function () {
        $template = EmailTemplate::create([
            'slug' => 'test_template',
            'name' => 'Test Template',
            'category' => 'transactional',
            'subject' => 'Hello {{ user_name }}',
            'body_html' => '<p>Hello {{ user_name }}</p>',
            'body_text' => 'Hello {{ user_name }}',
            'variables' => ['user_name'],
            'is_active' => true,
        ]);

        expect($template)->toBeInstanceOf(EmailTemplate::class)
            ->and($template->slug)->toBe('test_template')
            ->and($template->category)->toBe('transactional')
            ->and($template->is_active)->toBeTrue();
    });

    it('can find template by slug', function () {
        EmailTemplate::create([
            'slug' => 'welcome',
            'name' => 'Welcome Email',
            'category' => 'transactional',
            'subject' => 'Welcome!',
            'body_html' => '<p>Welcome!</p>',
            'variables' => [],
            'is_active' => true,
        ]);

        $found = EmailTemplate::findBySlug('welcome');

        expect($found)->not->toBeNull()
            ->and($found->name)->toBe('Welcome Email');
    });

    it('can find only active templates by slug', function () {
        EmailTemplate::create([
            'slug' => 'inactive_template',
            'name' => 'Inactive Template',
            'category' => 'marketing',
            'subject' => 'Test',
            'body_html' => '<p>Test</p>',
            'variables' => [],
            'is_active' => false,
        ]);

        $found = EmailTemplate::findActiveBySlug('inactive_template');

        expect($found)->toBeNull();
    });

    it('can render template with variables', function () {
        $template = EmailTemplate::create([
            'slug' => 'greeting',
            'name' => 'Greeting Email',
            'category' => 'notification',
            'subject' => 'Hello {{ user_name }}!',
            'body_html' => '<p>Hello {{ user_name }}, welcome to {{ app_name }}!</p>',
            'body_text' => 'Hello {{ user_name }}, welcome to {{ app_name }}!',
            'variables' => ['user_name', 'app_name'],
            'is_active' => true,
        ]);

        $rendered = $template->render([
            'user_name' => 'John',
            'app_name' => 'OvertimeStaff',
        ]);

        expect($rendered['subject'])->toBe('Hello John!')
            ->and($rendered['body_html'])->toContain('Hello John')
            ->and($rendered['body_html'])->toContain('OvertimeStaff');
    });
});

describe('EmailLog Model', function () {
    it('can create an email log', function () {
        $log = EmailLog::create([
            'to_email' => 'test@example.com',
            'subject' => 'Test Email',
            'status' => EmailLog::STATUS_QUEUED,
        ]);

        expect($log)->toBeInstanceOf(EmailLog::class)
            ->and($log->status)->toBe('queued');
    });

    it('can mark as sent', function () {
        $log = EmailLog::create([
            'to_email' => 'test@example.com',
            'subject' => 'Test Email',
            'status' => EmailLog::STATUS_QUEUED,
        ]);

        $log->markAsSent('msg-12345');

        expect($log->status)->toBe(EmailLog::STATUS_SENT)
            ->and($log->message_id)->toBe('msg-12345')
            ->and($log->sent_at)->not->toBeNull();
    });

    it('can mark as opened', function () {
        $log = EmailLog::create([
            'to_email' => 'test@example.com',
            'subject' => 'Test Email',
            'status' => EmailLog::STATUS_SENT,
        ]);

        $log->markAsOpened();

        expect($log->status)->toBe(EmailLog::STATUS_OPENED)
            ->and($log->opened_at)->not->toBeNull();
    });

    it('can mark as clicked', function () {
        $log = EmailLog::create([
            'to_email' => 'test@example.com',
            'subject' => 'Test Email',
            'status' => EmailLog::STATUS_OPENED,
        ]);

        $log->markAsClicked();

        expect($log->status)->toBe(EmailLog::STATUS_CLICKED)
            ->and($log->clicked_at)->not->toBeNull();
    });

    it('can mark as bounced', function () {
        $log = EmailLog::create([
            'to_email' => 'test@example.com',
            'subject' => 'Test Email',
            'status' => EmailLog::STATUS_SENT,
        ]);

        $log->markAsBounced('Mailbox not found');

        expect($log->status)->toBe(EmailLog::STATUS_BOUNCED)
            ->and($log->error_message)->toBe('Mailbox not found');
    });

    it('can find log by message ID', function () {
        EmailLog::create([
            'to_email' => 'test@example.com',
            'subject' => 'Test Email',
            'status' => EmailLog::STATUS_SENT,
            'message_id' => 'unique-msg-id-123',
        ]);

        $found = EmailLog::findByMessageId('unique-msg-id-123');

        expect($found)->not->toBeNull();
    });
});

describe('EmailPreference Model', function () {
    it('can create preferences for user', function () {
        $user = User::factory()->create();

        $preferences = EmailPreference::getOrCreateForUser($user);

        expect($preferences)->toBeInstanceOf(EmailPreference::class)
            ->and($preferences->user_id)->toBe($user->id)
            ->and($preferences->marketing_emails)->toBeTrue()
            ->and($preferences->unsubscribe_token)->not->toBeEmpty();
    });

    it('generates unique unsubscribe token', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $prefs1 = EmailPreference::getOrCreateForUser($user1);
        $prefs2 = EmailPreference::getOrCreateForUser($user2);

        expect($prefs1->unsubscribe_token)->not->toBe($prefs2->unsubscribe_token);
    });

    it('can check if category is allowed', function () {
        $user = User::factory()->create();
        $preferences = EmailPreference::getOrCreateForUser($user);

        // All categories should be allowed by default
        expect($preferences->allowsCategory('marketing'))->toBeTrue()
            ->and($preferences->allowsCategory('transactional'))->toBeTrue();

        // Disable marketing
        $preferences->update(['marketing_emails' => false]);

        expect($preferences->fresh()->allowsCategory('marketing'))->toBeFalse();
    });

    it('always allows transactional emails', function () {
        $user = User::factory()->create();
        $preferences = EmailPreference::getOrCreateForUser($user);
        $preferences->unsubscribeFromAll();

        // Even after unsubscribing from all, transactional should be allowed
        expect($preferences->allowsCategory('transactional'))->toBeTrue();
    });

    it('can unsubscribe from specific category', function () {
        $user = User::factory()->create();
        $preferences = EmailPreference::getOrCreateForUser($user);

        $preferences->unsubscribeFrom('marketing');

        expect($preferences->fresh()->marketing_emails)->toBeFalse()
            ->and($preferences->fresh()->shift_notifications)->toBeTrue();
    });

    it('can unsubscribe from all', function () {
        $user = User::factory()->create();
        $preferences = EmailPreference::getOrCreateForUser($user);

        $preferences->unsubscribeFromAll();

        expect($preferences->fresh()->marketing_emails)->toBeFalse()
            ->and($preferences->fresh()->shift_notifications)->toBeFalse()
            ->and($preferences->fresh()->payment_notifications)->toBeFalse()
            ->and($preferences->fresh()->weekly_digest)->toBeFalse()
            ->and($preferences->fresh()->tips_and_updates)->toBeFalse();
    });

    it('can find by token', function () {
        $user = User::factory()->create();
        $preferences = EmailPreference::getOrCreateForUser($user);

        $found = EmailPreference::findByToken($preferences->unsubscribe_token);

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($preferences->id);
    });
});

describe('EmailService', function () {
    beforeEach(function () {
        Mail::fake();
    });

    it('can send template email', function () {
        $user = User::factory()->create(['email' => 'test@example.com']);

        EmailTemplate::create([
            'slug' => 'test_email',
            'name' => 'Test Email',
            'category' => 'transactional',
            'subject' => 'Hello {{ user_name }}',
            'body_html' => '<p>Hello {{ user_name }}</p>',
            'variables' => ['user_name'],
            'is_active' => true,
        ]);

        $service = app(EmailService::class);
        $log = $service->sendTemplateEmail($user, 'test_email', []);

        expect($log)->not->toBeNull()
            ->and($log->to_email)->toBe('test@example.com')
            ->and($log->template_slug)->toBe('test_email');

        Mail::assertQueued(TemplatedEmail::class);
    });

    it('respects user preferences', function () {
        $user = User::factory()->create();
        $preferences = EmailPreference::getOrCreateForUser($user);
        $preferences->update(['marketing_emails' => false]);

        EmailTemplate::create([
            'slug' => 'marketing_email',
            'name' => 'Marketing Email',
            'category' => 'marketing',
            'subject' => 'Special Offer',
            'body_html' => '<p>Special offer!</p>',
            'variables' => [],
            'is_active' => true,
        ]);

        $service = app(EmailService::class);
        $log = $service->sendTemplateEmail($user, 'marketing_email', []);

        expect($log)->toBeNull();
        Mail::assertNotQueued(TemplatedEmail::class);
    });

    it('always sends transactional emails', function () {
        $user = User::factory()->create();
        $preferences = EmailPreference::getOrCreateForUser($user);
        $preferences->unsubscribeFromAll();

        EmailTemplate::create([
            'slug' => 'password_reset',
            'name' => 'Password Reset',
            'category' => 'transactional',
            'subject' => 'Reset Your Password',
            'body_html' => '<p>Click to reset</p>',
            'variables' => [],
            'is_active' => true,
        ]);

        $service = app(EmailService::class);
        $log = $service->sendTemplateEmail($user, 'password_reset', []);

        expect($log)->not->toBeNull();
        Mail::assertQueued(TemplatedEmail::class);
    });

    it('returns null for non-existent template', function () {
        $user = User::factory()->create();

        $service = app(EmailService::class);
        $log = $service->sendTemplateEmail($user, 'non_existent_template', []);

        expect($log)->toBeNull();
        Mail::assertNotQueued(TemplatedEmail::class);
    });

    it('logs email details correctly', function () {
        $service = app(EmailService::class);

        $log = $service->logEmail([
            'to_email' => 'test@example.com',
            'subject' => 'Test Subject',
            'template_slug' => 'test_template',
            'status' => EmailLog::STATUS_QUEUED,
        ]);

        expect($log)->toBeInstanceOf(EmailLog::class)
            ->and($log->to_email)->toBe('test@example.com')
            ->and($log->subject)->toBe('Test Subject');
    });

    it('can render template for preview', function () {
        $template = EmailTemplate::create([
            'slug' => 'preview_test',
            'name' => 'Preview Test',
            'category' => 'notification',
            'subject' => 'Hello {{ user_name }}',
            'body_html' => '<p>Welcome {{ user_name }} to {{ app_name }}</p>',
            'variables' => ['user_name', 'app_name'],
            'is_active' => true,
        ]);

        $service = app(EmailService::class);
        $rendered = $service->renderTemplate($template, [
            'user_name' => 'Jane',
            'app_name' => 'TestApp',
        ]);

        expect($rendered['subject'])->toBe('Hello Jane')
            ->and($rendered['body_html'])->toContain('Welcome Jane')
            ->and($rendered['body_html'])->toContain('TestApp');
    });
});

describe('Email Webhook Processing', function () {
    it('can process SendGrid delivered event', function () {
        $log = EmailLog::create([
            'to_email' => 'test@example.com',
            'subject' => 'Test',
            'status' => EmailLog::STATUS_SENT,
            'message_id' => 'sg-12345',
        ]);

        $service = app(EmailService::class);
        $result = $service->processWebhook('sendgrid', [
            [
                'event' => 'delivered',
                'sg_message_id' => 'sg-12345.filter',
            ],
        ]);

        expect($result)->toBeTrue()
            ->and($log->fresh()->status)->toBe(EmailLog::STATUS_DELIVERED);
    });

    it('can process SendGrid open event', function () {
        $log = EmailLog::create([
            'to_email' => 'test@example.com',
            'subject' => 'Test',
            'status' => EmailLog::STATUS_DELIVERED,
            'message_id' => 'sg-open-test',
        ]);

        $service = app(EmailService::class);
        $service->processWebhook('sendgrid', [
            [
                'event' => 'open',
                'sg_message_id' => 'sg-open-test.suffix',
            ],
        ]);

        expect($log->fresh()->status)->toBe(EmailLog::STATUS_OPENED)
            ->and($log->fresh()->opened_at)->not->toBeNull();
    });

    it('can process SendGrid bounce event', function () {
        $log = EmailLog::create([
            'to_email' => 'test@example.com',
            'subject' => 'Test',
            'status' => EmailLog::STATUS_SENT,
            'message_id' => 'sg-bounce-test',
        ]);

        $service = app(EmailService::class);
        $service->processWebhook('sendgrid', [
            [
                'event' => 'bounce',
                'sg_message_id' => 'sg-bounce-test',
                'reason' => 'Mailbox does not exist',
            ],
        ]);

        expect($log->fresh()->status)->toBe(EmailLog::STATUS_BOUNCED)
            ->and($log->fresh()->error_message)->toBe('Mailbox does not exist');
    });
});

describe('Email Preferences Controller', function () {
    it('can view email preferences when authenticated', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.email-preferences'));

        $response->assertSuccessful();
    });

    it('can update email preferences', function () {
        $user = User::factory()->create();
        EmailPreference::getOrCreateForUser($user);

        $response = $this->actingAs($user)->post(route('settings.email-preferences.update'), [
            'marketing_emails' => false,
            'shift_notifications' => true,
            'payment_notifications' => true,
            'weekly_digest' => false,
            'tips_and_updates' => false,
        ]);

        $response->assertRedirect();

        $prefs = $user->fresh()->emailPreferences;
        expect($prefs->marketing_emails)->toBeFalse()
            ->and($prefs->shift_notifications)->toBeTrue()
            ->and($prefs->weekly_digest)->toBeFalse();
    });

    it('can unsubscribe via token', function () {
        $user = User::factory()->create();
        $preferences = EmailPreference::getOrCreateForUser($user);

        $response = $this->get(route('email.unsubscribe', $preferences->unsubscribe_token));

        $response->assertSuccessful();
    });

    it('shows error for invalid unsubscribe token', function () {
        $response = $this->get(route('email.unsubscribe', 'invalid-token'));

        $response->assertSuccessful();
        $response->assertViewIs('emails.unsubscribe-invalid');
    });

    it('can process unsubscribe from all', function () {
        $user = User::factory()->create();
        $preferences = EmailPreference::getOrCreateForUser($user);

        $response = $this->post(route('email.unsubscribe.process', $preferences->unsubscribe_token), [
            'unsubscribe_all' => true,
        ]);

        $prefs = $preferences->fresh();
        expect($prefs->marketing_emails)->toBeFalse()
            ->and($prefs->shift_notifications)->toBeFalse();
    });
});

describe('Admin Email Controller', function () {
    it('requires admin role to access', function () {
        $user = User::factory()->create(['user_type' => 'worker']);

        $response = $this->actingAs($user)->get(route('admin.email.index'));

        $response->assertForbidden();
    });

    it('admin can view email dashboard', function () {
        $admin = User::factory()->create(['user_type' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.email.index'));

        $response->assertSuccessful();
    });

    it('admin can view templates list', function () {
        $admin = User::factory()->create(['user_type' => 'admin']);

        EmailTemplate::create([
            'slug' => 'test',
            'name' => 'Test Template',
            'category' => 'transactional',
            'subject' => 'Test',
            'body_html' => '<p>Test</p>',
            'variables' => [],
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.email.templates'));

        $response->assertSuccessful();
        $response->assertSee('Test Template');
    });

    it('admin can create new template', function () {
        $admin = User::factory()->create(['user_type' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.email.templates.store'), [
            'slug' => 'new_template',
            'name' => 'New Template',
            'category' => 'notification',
            'subject' => 'New Subject',
            'body_html' => '<p>New body</p>',
            'is_active' => true,
            'variables' => [],
        ]);

        $response->assertRedirect(route('admin.email.templates'));

        expect(EmailTemplate::where('slug', 'new_template')->exists())->toBeTrue();
    });

    it('admin can view email logs', function () {
        $admin = User::factory()->create(['user_type' => 'admin']);

        EmailLog::create([
            'to_email' => 'test@example.com',
            'subject' => 'Test Subject',
            'status' => EmailLog::STATUS_SENT,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.email.logs'));

        $response->assertSuccessful();
        $response->assertSee('test@example.com');
    });
});
