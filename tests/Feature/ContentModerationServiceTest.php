<?php

use App\Models\BlockedPhrase;
use App\Models\MessageModerationLog;
use App\Models\User;
use App\Services\ContentModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(ContentModerationService::class);
    $this->user = User::factory()->create(['user_type' => 'worker']);
});

describe('ContentModerationService', function () {
    describe('PII Detection', function () {
        it('detects SSN patterns', function () {
            $issues = $this->service->detectPII('My SSN is 123-45-6789');

            expect($issues)->toHaveCount(1);
            expect($issues[0]['type'])->toBe('pii');
            expect($issues[0]['subtype'])->toBe('ssn');
            expect($issues[0]['confidence'])->toBeGreaterThan(0.9);
        });

        it('detects credit card numbers', function () {
            $issues = $this->service->detectPII('My card is 4111111111111111');

            $hasCreditCard = collect($issues)->contains(fn ($issue) => $issue['subtype'] === 'credit_card');

            expect($hasCreditCard)->toBeTrue();
        });

        it('ignores normal text', function () {
            $issues = $this->service->detectPII('Hello, this is a normal message.');

            expect($issues)->toBeEmpty();
        });
    });

    describe('Harassment Detection', function () {
        it('detects harassment keywords', function () {
            $issues = $this->service->detectHarassment('You are worthless and pathetic');

            expect($issues)->not->toBeEmpty();
            expect(collect($issues)->pluck('type')->unique()->first())->toBe('harassment');
        });

        it('detects threats', function () {
            $issues = $this->service->detectHarassment('I will kill you');

            expect($issues)->not->toBeEmpty();
            expect(collect($issues)->contains(fn ($issue) => str_contains($issue['matched_text'], 'kill')))->toBeTrue();
        });

        it('allows normal messages', function () {
            $issues = $this->service->detectHarassment('Great job today! Looking forward to working with you again.');

            expect($issues)->toBeEmpty();
        });
    });

    describe('Contact Info Detection', function () {
        it('detects email addresses', function () {
            $issues = $this->service->detectContactInfo('Email me at test@example.com');

            expect($issues)->not->toBeEmpty();
            expect(collect($issues)->contains(fn ($issue) => $issue['subtype'] === 'email'))->toBeTrue();
        });

        it('detects phone numbers', function () {
            $issues = $this->service->detectContactInfo('Call me at 555-123-4567');

            expect($issues)->not->toBeEmpty();
            expect(collect($issues)->contains(fn ($issue) => str_contains($issue['subtype'], 'phone')))->toBeTrue();
        });

        it('detects social media handles', function () {
            $issues = $this->service->detectContactInfo('Follow me on @myhandle');

            expect($issues)->not->toBeEmpty();
        });
    });

    describe('Full Content Moderation', function () {
        it('allows clean messages', function () {
            $result = $this->service->moderateContent(
                'Thank you for the opportunity. I am looking forward to the shift.',
                $this->user
            );

            expect($result['allowed'])->toBeTrue();
            expect($result['action'])->toBe('allowed');
            expect($result['issues'])->toBeEmpty();
        });

        it('flags harassment content', function () {
            $result = $this->service->moderateContent(
                'You are worthless and should not be working here.',
                $this->user
            );

            expect($result['allowed'])->toBeTrue(); // Flagged but not blocked
            expect($result['action'])->toBe('flagged');
            expect($result['issues'])->not->toBeEmpty();
        });

        it('sets appropriate severity for multiple issues', function () {
            $result = $this->service->moderateContent(
                'You are worthless! My SSN is 123-45-6789.',
                $this->user
            );

            expect($result['severity'])->not->toBe('low');
        });
    });

    describe('Blocked Phrases', function () {
        it('detects profanity from database', function () {
            BlockedPhrase::create([
                'phrase' => 'testbadword',
                'type' => 'profanity',
                'action' => 'flag',
                'is_active' => true,
            ]);

            $this->service->clearBlockedPhrasesCache();

            $issues = $this->service->detectProfanity('This message contains testbadword');

            expect($issues)->not->toBeEmpty();
            expect($issues[0]['type'])->toBe('profanity');
        });

        it('respects regex patterns', function () {
            BlockedPhrase::create([
                'phrase' => 'b[a@4]+d',
                'type' => 'profanity',
                'action' => 'flag',
                'is_regex' => true,
                'is_active' => true,
            ]);

            $this->service->clearBlockedPhrasesCache();

            $phrase = BlockedPhrase::where('phrase', 'b[a@4]+d')->first();

            expect($phrase->matches('This is b4d'))->toBeTrue();
            expect($phrase->matches('This is bad'))->toBeTrue();
            expect($phrase->matches('This is good'))->toBeFalse();
        });
    });

    describe('PII Redaction', function () {
        it('redacts SSN', function () {
            $content = 'My SSN is 123-45-6789';
            $redacted = $this->service->redactPII($content);

            expect($redacted)->not->toContain('123-45-6789');
            expect($redacted)->toContain('[SSN REDACTED]');
        });

        it('redacts email addresses', function () {
            $content = 'Contact me at test@example.com';
            $redacted = $this->service->redactPII($content);

            expect($redacted)->not->toContain('test@example.com');
            expect($redacted)->toContain('[EMAIL REDACTED]');
        });
    });

    describe('Flag For Review', function () {
        it('creates moderation log for message', function () {
            $message = new \App\Models\Message([
                'from_user_id' => $this->user->id,
                'message' => 'Test message with issues',
            ]);
            $message->id = 1;

            $issues = [
                [
                    'type' => 'harassment',
                    'confidence' => 0.85,
                    'matched_text' => 'test',
                    'action' => 'flag',
                ],
            ];

            $log = $this->service->flagForReview($message, $issues);

            expect($log)->toBeInstanceOf(MessageModerationLog::class);
            expect($log->user_id)->toBe($this->user->id);
            expect($log->action)->toBe('flagged');
            expect($log->detected_issues)->toBeArray();
        });
    });

    describe('User Statistics', function () {
        it('returns empty stats for new user', function () {
            $stats = $this->service->getUserModerationStats($this->user->id);

            expect($stats['total_moderated'])->toBe(0);
            expect($stats['blocked'])->toBe(0);
            expect($stats['flagged'])->toBe(0);
        });
    });
});
