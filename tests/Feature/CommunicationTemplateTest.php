<?php

use App\Models\CommunicationTemplate;
use App\Models\Shift;
use App\Models\TemplateSend;
use App\Models\User;
use App\Services\CommunicationTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    $this->templateService = app(CommunicationTemplateService::class);
    $this->business = User::factory()->create(['user_type' => 'business']);
    $this->worker = User::factory()->create(['user_type' => 'worker']);
});

describe('BIZ-010: Communication Templates', function () {

    describe('createTemplate', function () {
        it('creates a template for a business', function () {
            $template = $this->templateService->createTemplate($this->business, [
                'name' => 'Welcome Message',
                'type' => 'welcome',
                'channel' => 'email',
                'subject' => 'Welcome to {{business_name}}!',
                'body' => 'Hi {{worker_name}}, welcome to our team!',
            ]);

            expect($template)->toBeInstanceOf(CommunicationTemplate::class);
            expect($template->name)->toBe('Welcome Message');
            expect($template->business_id)->toBe($this->business->id);
            expect($template->type)->toBe('welcome');
            expect($template->channel)->toBe('email');
            expect($template->slug)->toBe('welcome-message');
        });

        it('generates unique slugs for duplicate names', function () {
            $template1 = $this->templateService->createTemplate($this->business, [
                'name' => 'Shift Instructions',
                'type' => 'shift_instruction',
                'channel' => 'all',
                'body' => 'Test body',
            ]);

            $template2 = $this->templateService->createTemplate($this->business, [
                'name' => 'Shift Instructions',
                'type' => 'shift_instruction',
                'channel' => 'all',
                'body' => 'Test body 2',
            ]);

            expect($template1->slug)->toBe('shift-instructions');
            expect($template2->slug)->toBe('shift-instructions-1');
        });

        it('sets default variables based on type', function () {
            $template = $this->templateService->createTemplate($this->business, [
                'name' => 'Shift Reminder',
                'type' => 'reminder',
                'channel' => 'all',
                'body' => 'Your shift is coming up!',
            ]);

            expect($template->variables)->toContain('worker_name');
            expect($template->variables)->toContain('shift_date');
            expect($template->variables)->toContain('venue_name');
        });
    });

    describe('updateTemplate', function () {
        it('updates a template', function () {
            $template = CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'name' => 'Original Name',
                'body' => 'Original body',
            ]);

            $updated = $this->templateService->updateTemplate($template, [
                'name' => 'Updated Name',
                'body' => 'Updated body content',
            ]);

            expect($updated->name)->toBe('Updated Name');
            expect($updated->body)->toBe('Updated body content');
            expect($updated->slug)->toBe('updated-name');
        });

        it('throws exception when updating system template', function () {
            $template = CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'is_system' => true,
            ]);

            $this->templateService->updateTemplate($template, ['name' => 'New Name']);
        })->throws(InvalidArgumentException::class);
    });

    describe('renderTemplate', function () {
        it('replaces variables in template', function () {
            $template = CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'subject' => 'Hello {{worker_name}}!',
                'body' => 'Hi {{worker_name}}, your shift at {{venue_name}} starts at {{shift_start_time}}.',
            ]);

            $rendered = $this->templateService->renderTemplate($template, [
                'worker_name' => 'John Smith',
                'venue_name' => 'Grand Hotel',
                'shift_start_time' => '9:00 AM',
            ]);

            expect($rendered['subject'])->toBe('Hello John Smith!');
            expect($rendered['body'])->toContain('Hi John Smith');
            expect($rendered['body'])->toContain('Grand Hotel');
            expect($rendered['body'])->toContain('9:00 AM');
        });

        it('handles missing variables gracefully', function () {
            $template = CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'body' => 'Hello {{worker_name}}, shift at {{venue_name}}.',
            ]);

            $rendered = $this->templateService->renderTemplate($template, [
                'worker_name' => 'John',
            ]);

            expect($rendered['body'])->toBe('Hello John, shift at .');
        });

        it('supports variable syntax with spaces', function () {
            $template = CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'body' => 'Hello {{ worker_name }}, welcome!',
            ]);

            $rendered = $this->templateService->renderTemplate($template, [
                'worker_name' => 'Jane',
            ]);

            expect($rendered['body'])->toBe('Hello Jane, welcome!');
        });
    });

    describe('sendTemplate', function () {
        it('creates a template send record', function () {
            $template = CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'channel' => 'in_app',
                'body' => 'Hello {{worker_name}}!',
            ]);

            $send = $this->templateService->sendTemplate(
                $template,
                $this->business,
                $this->worker,
                ['worker_name' => 'Test Worker']
            );

            expect($send)->toBeInstanceOf(TemplateSend::class);
            expect($send->template_id)->toBe($template->id);
            expect($send->sender_id)->toBe($this->business->id);
            expect($send->recipient_id)->toBe($this->worker->id);
            expect($send->rendered_content)->toContain('Hello Test Worker!');
        });

        it('increments template usage count', function () {
            $template = CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'channel' => 'in_app',
                'usage_count' => 5,
                'body' => 'Test message',
            ]);

            $this->templateService->sendTemplate(
                $template,
                $this->business,
                $this->worker,
                []
            );

            expect($template->fresh()->usage_count)->toBe(6);
        });

        it('includes shift context when provided', function () {
            $shift = Shift::factory()->create(['business_id' => $this->business->id]);

            $template = CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'channel' => 'in_app',
                'body' => 'Shift reminder',
            ]);

            $send = $this->templateService->sendTemplate(
                $template,
                $this->business,
                $this->worker,
                [],
                $shift
            );

            expect($send->shift_id)->toBe($shift->id);
        });
    });

    describe('sendBulkTemplate', function () {
        it('sends to multiple recipients', function () {
            $workers = User::factory()->count(3)->create(['user_type' => 'worker']);

            $template = CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'channel' => 'in_app',
                'body' => 'Hello everyone!',
            ]);

            $results = $this->templateService->sendBulkTemplate(
                $template,
                $this->business,
                $workers,
                []
            );

            expect($results['sent'])->toBe(3);
            expect($results['failed'])->toBe(0);
            expect(count($results['sends']))->toBe(3);
        });

        it('personalizes each message with recipient data', function () {
            $worker1 = User::factory()->create(['user_type' => 'worker', 'name' => 'Alice']);
            $worker2 = User::factory()->create(['user_type' => 'worker', 'name' => 'Bob']);

            $template = CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'channel' => 'in_app',
                'body' => 'Hello {{worker_name}}!',
            ]);

            $results = $this->templateService->sendBulkTemplate(
                $template,
                $this->business,
                collect([$worker1, $worker2]),
                []
            );

            $sends = TemplateSend::where('template_id', $template->id)->get();

            expect($sends->where('recipient_id', $worker1->id)->first()->rendered_content)->toContain('Alice');
            expect($sends->where('recipient_id', $worker2->id)->first()->rendered_content)->toContain('Bob');
        });
    });

    describe('duplicateTemplate', function () {
        it('creates a copy of the template', function () {
            $original = CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'name' => 'Original Template',
                'body' => 'Original content',
                'usage_count' => 10,
                'is_default' => true,
            ]);

            $copy = $this->templateService->duplicateTemplate($original);

            expect($copy->id)->not->toBe($original->id);
            expect($copy->name)->toBe('Original Template (Copy)');
            expect($copy->body)->toBe('Original content');
            expect($copy->usage_count)->toBe(0);
            expect($copy->is_default)->toBeFalse();
        });

        it('allows custom name for duplicate', function () {
            $original = CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'name' => 'Original',
            ]);

            $copy = $this->templateService->duplicateTemplate($original, 'My Custom Copy');

            expect($copy->name)->toBe('My Custom Copy');
        });
    });

    describe('getTemplateAnalytics', function () {
        it('returns correct analytics data', function () {
            CommunicationTemplate::factory()->count(3)->create([
                'business_id' => $this->business->id,
                'is_active' => true,
            ]);

            CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'is_active' => false,
            ]);

            $analytics = $this->templateService->getTemplateAnalytics($this->business);

            expect($analytics['total_templates'])->toBe(4);
            expect($analytics['active_templates'])->toBe(3);
        });
    });

    describe('getAvailableVariables', function () {
        it('returns variables for shift instruction type', function () {
            $variables = $this->templateService->getAvailableVariables('shift_instruction');

            expect($variables)->toHaveKey('worker');
            expect($variables)->toHaveKey('shift');
            expect($variables)->toHaveKey('venue');
        });

        it('returns limited variables for custom type', function () {
            $variables = $this->templateService->getAvailableVariables('custom');

            expect($variables)->toHaveKey('worker');
            expect($variables)->toHaveKey('business');
        });
    });

    describe('buildAllVariables', function () {
        it('builds complete variable set', function () {
            $shift = Shift::factory()->create([
                'business_id' => $this->business->id,
                'title' => 'Event Staff',
            ]);

            $variables = $this->templateService->buildAllVariables(
                $this->worker,
                $this->business,
                $shift
            );

            expect($variables)->toHaveKey('worker_name');
            expect($variables)->toHaveKey('business_name');
            expect($variables)->toHaveKey('shift_date');
            expect($variables)->toHaveKey('position_name');
            expect($variables['worker_name'])->toBe($this->worker->name);
        });
    });

    describe('previewTemplate', function () {
        it('renders template with sample data', function () {
            $template = CommunicationTemplate::factory()->create([
                'business_id' => $this->business->id,
                'subject' => 'Hello {{worker_name}}',
                'body' => 'Your shift is on {{shift_date}} at {{venue_name}}.',
            ]);

            $preview = $this->templateService->previewTemplate($template);

            expect($preview['subject'])->toContain('John Smith');
            expect($preview['body'])->toContain('Monday, January 15, 2025');
            expect($preview['body'])->toContain('Grand Convention Center');
        });
    });

    describe('ensureDefaultTemplates', function () {
        it('creates default templates for a business', function () {
            expect(CommunicationTemplate::forBusiness($this->business->id)->count())->toBe(0);

            $this->templateService->ensureDefaultTemplates($this->business);

            $templates = CommunicationTemplate::forBusiness($this->business->id)->get();

            expect($templates->count())->toBeGreaterThan(0);
            expect($templates->where('type', 'welcome')->count())->toBeGreaterThan(0);
            expect($templates->where('type', 'shift_instruction')->count())->toBeGreaterThan(0);
            expect($templates->where('type', 'reminder')->count())->toBeGreaterThan(0);
        });

        it('does not duplicate templates on subsequent calls', function () {
            $this->templateService->ensureDefaultTemplates($this->business);
            $countAfterFirst = CommunicationTemplate::forBusiness($this->business->id)->count();

            $this->templateService->ensureDefaultTemplates($this->business);
            $countAfterSecond = CommunicationTemplate::forBusiness($this->business->id)->count();

            expect($countAfterSecond)->toBe($countAfterFirst);
        });
    });
});

describe('CommunicationTemplate Model', function () {

    it('has correct type labels', function () {
        $types = CommunicationTemplate::getTypes();

        expect($types)->toHaveKey('shift_instruction');
        expect($types)->toHaveKey('welcome');
        expect($types)->toHaveKey('reminder');
        expect($types)->toHaveKey('thank_you');
        expect($types)->toHaveKey('feedback_request');
        expect($types)->toHaveKey('custom');
    });

    it('has correct channel labels', function () {
        $channels = CommunicationTemplate::getChannels();

        expect($channels)->toHaveKey('email');
        expect($channels)->toHaveKey('sms');
        expect($channels)->toHaveKey('in_app');
        expect($channels)->toHaveKey('all');
    });

    it('can set as default', function () {
        $business = User::factory()->create(['user_type' => 'business']);

        $template1 = CommunicationTemplate::factory()->create([
            'business_id' => $business->id,
            'type' => 'welcome',
            'is_default' => true,
        ]);

        $template2 = CommunicationTemplate::factory()->create([
            'business_id' => $business->id,
            'type' => 'welcome',
            'is_default' => false,
        ]);

        $template2->setAsDefault();

        expect($template1->fresh()->is_default)->toBeFalse();
        expect($template2->fresh()->is_default)->toBeTrue();
    });

    it('returns usage stats', function () {
        $business = User::factory()->create(['user_type' => 'business']);
        $worker = User::factory()->create(['user_type' => 'worker']);

        $template = CommunicationTemplate::factory()->create([
            'business_id' => $business->id,
        ]);

        TemplateSend::factory()->count(5)->create([
            'template_id' => $template->id,
            'sender_id' => $business->id,
            'recipient_id' => $worker->id,
            'status' => 'sent',
        ]);

        TemplateSend::factory()->count(2)->create([
            'template_id' => $template->id,
            'sender_id' => $business->id,
            'recipient_id' => $worker->id,
            'status' => 'failed',
        ]);

        $stats = $template->getUsageStats();

        expect($stats['total_sends'])->toBe(7);
        expect($stats['sent'])->toBe(5);
        expect($stats['failed'])->toBe(2);
    });
});

describe('TemplateSend Model', function () {

    it('can be marked as sent', function () {
        $business = User::factory()->create(['user_type' => 'business']);
        $worker = User::factory()->create(['user_type' => 'worker']);
        $template = CommunicationTemplate::factory()->create(['business_id' => $business->id]);

        $send = TemplateSend::factory()->create([
            'template_id' => $template->id,
            'sender_id' => $business->id,
            'recipient_id' => $worker->id,
            'status' => 'pending',
        ]);

        $send->markAsSent();

        expect($send->status)->toBe('sent');
        expect($send->sent_at)->not->toBeNull();
    });

    it('can be marked as failed', function () {
        $business = User::factory()->create(['user_type' => 'business']);
        $worker = User::factory()->create(['user_type' => 'worker']);
        $template = CommunicationTemplate::factory()->create(['business_id' => $business->id]);

        $send = TemplateSend::factory()->create([
            'template_id' => $template->id,
            'sender_id' => $business->id,
            'recipient_id' => $worker->id,
            'status' => 'pending',
        ]);

        $send->markAsFailed('Connection timeout');

        expect($send->status)->toBe('failed');
        expect($send->error_message)->toBe('Connection timeout');
    });

    it('can be marked as read', function () {
        $business = User::factory()->create(['user_type' => 'business']);
        $worker = User::factory()->create(['user_type' => 'worker']);
        $template = CommunicationTemplate::factory()->create(['business_id' => $business->id]);

        $send = TemplateSend::factory()->create([
            'template_id' => $template->id,
            'sender_id' => $business->id,
            'recipient_id' => $worker->id,
            'status' => 'sent',
            'read_at' => null,
        ]);

        $send->markAsRead();

        expect($send->read_at)->not->toBeNull();
        expect($send->isRead())->toBeTrue();
    });
});
