<?php

use App\Models\PushNotificationLog;
use App\Models\PushNotificationToken;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'user_type' => 'worker',
    ]);
});

describe('Token Registration', function () {
    it('can register a new push token', function () {
        $service = app(PushNotificationService::class);

        $token = $service->registerToken(
            $this->user,
            'test-fcm-token-123',
            'fcm',
            [
                'device_id' => 'device-123',
                'device_name' => 'iPhone 15 Pro',
                'device_model' => 'iPhone',
                'app_version' => '1.0.0',
            ]
        );

        expect($token)->toBeInstanceOf(PushNotificationToken::class);
        expect($token->user_id)->toBe($this->user->id);
        expect($token->token)->toBe('test-fcm-token-123');
        expect($token->platform)->toBe('fcm');
        expect($token->device_name)->toBe('iPhone 15 Pro');
        expect($token->is_active)->toBeTrue();
    });

    it('updates existing token if same token registered again', function () {
        $service = app(PushNotificationService::class);

        $token1 = $service->registerToken($this->user, 'duplicate-token', 'fcm');
        $token2 = $service->registerToken($this->user, 'duplicate-token', 'fcm', [
            'device_name' => 'Updated Device',
        ]);

        expect($token1->id)->toBe($token2->id);
        expect($token2->device_name)->toBe('Updated Device');
    });

    it('enforces maximum tokens per user', function () {
        $service = app(PushNotificationService::class);
        config(['firebase.tokens.max_per_user' => 3]);

        // Register 4 tokens
        for ($i = 1; $i <= 4; $i++) {
            $service->registerToken($this->user, "token-{$i}", 'fcm');
        }

        $activeTokens = PushNotificationToken::forUser($this->user->id)->active()->count();
        expect($activeTokens)->toBe(3);
    });
});

describe('Token Management', function () {
    it('can remove a token', function () {
        $service = app(PushNotificationService::class);

        $token = $service->registerToken($this->user, 'token-to-remove', 'fcm');
        expect(PushNotificationToken::find($token->id))->not->toBeNull();

        $service->removeToken('token-to-remove');
        expect(PushNotificationToken::find($token->id))->toBeNull();
    });

    it('can deactivate a token', function () {
        $service = app(PushNotificationService::class);

        $token = $service->registerToken($this->user, 'token-to-deactivate', 'fcm');
        expect($token->is_active)->toBeTrue();

        $service->deactivateToken('token-to-deactivate');
        $token->refresh();
        expect($token->is_active)->toBeFalse();
    });

    it('can cleanup inactive tokens', function () {
        // Create some old tokens
        PushNotificationToken::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'last_used_at' => now()->subDays(100),
        ]);

        // Create some recent tokens
        PushNotificationToken::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'last_used_at' => now()->subDays(30),
        ]);

        $service = app(PushNotificationService::class);
        $deleted = $service->cleanupInactiveTokens();

        expect($deleted)->toBe(3);
        expect(PushNotificationToken::forUser($this->user->id)->count())->toBe(2);
    });
});

describe('Push Notification Sending', function () {
    it('creates pending log when no tokens exist', function () {
        $service = app(PushNotificationService::class);

        $log = $service->send($this->user, 'Test Title', 'Test Body', ['key' => 'value']);

        expect($log)->toBeNull();
    });

    it('creates log entry when sending to token', function () {
        $service = app(PushNotificationService::class);

        $token = PushNotificationToken::factory()->create([
            'user_id' => $this->user->id,
            'platform' => 'fcm',
        ]);

        // Mock HTTP response (FCM will fail without credentials, but log should be created)
        Http::fake([
            'fcm.googleapis.com/*' => Http::response(['name' => 'test-message-id'], 200),
        ]);

        config(['firebase.credentials.file' => null]); // Disable actual FCM

        $log = $service->sendToToken($token, 'Test Title', 'Test Body', ['key' => 'value']);

        expect($log)->toBeInstanceOf(PushNotificationLog::class);
        expect($log->user_id)->toBe($this->user->id);
        expect($log->token_id)->toBe($token->id);
        expect($log->title)->toBe('Test Title');
        expect($log->body)->toBe('Test Body');
        expect($log->platform)->toBe('fcm');
    });
});

describe('Notification Log Model', function () {
    it('can mark log as sent', function () {
        $log = PushNotificationLog::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $log->markAsSent('fcm-message-123');

        expect($log->status)->toBe('sent');
        expect($log->message_id)->toBe('fcm-message-123');
        expect($log->sent_at)->not->toBeNull();
    });

    it('can mark log as delivered', function () {
        $log = PushNotificationLog::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'sent',
        ]);

        $log->markAsDelivered();

        expect($log->status)->toBe('delivered');
        expect($log->delivered_at)->not->toBeNull();
    });

    it('can mark log as failed', function () {
        $log = PushNotificationLog::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $log->markAsFailed('Token expired');

        expect($log->status)->toBe('failed');
        expect($log->error_message)->toBe('Token expired');
    });

    it('can mark log as clicked', function () {
        $log = PushNotificationLog::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'delivered',
        ]);

        $log->markAsClicked();

        expect($log->status)->toBe('clicked');
        expect($log->clicked_at)->not->toBeNull();
    });
});

describe('Delivery Receipt Handling', function () {
    it('can handle delivery receipt', function () {
        $service = app(PushNotificationService::class);

        $log = PushNotificationLog::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'sent',
            'message_id' => 'fcm-receipt-test',
        ]);

        $service->handleDeliveryReceipt('fcm-receipt-test', 'delivered');

        $log->refresh();
        expect($log->status)->toBe('delivered');
    });
});

describe('User Stats', function () {
    it('returns correct user statistics', function () {
        // Create some logs
        PushNotificationLog::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'status' => 'sent',
        ]);

        PushNotificationLog::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'failed',
        ]);

        PushNotificationLog::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'clicked',
        ]);

        // Create active tokens
        PushNotificationToken::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $service = app(PushNotificationService::class);
        $stats = $service->getUserStats($this->user);

        expect($stats['total_sent'])->toBe(7); // sent + clicked
        expect($stats['total_failed'])->toBe(3);
        expect($stats['total_clicked'])->toBe(2);
        expect($stats['active_tokens'])->toBe(2);
    });
});
