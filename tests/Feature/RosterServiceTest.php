<?php

use App\Models\BusinessRoster;
use App\Models\RosterInvitation;
use App\Models\RosterMember;
use App\Models\Shift;
use App\Models\User;
use App\Services\RosterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    $this->rosterService = app(RosterService::class);
    $this->business = User::factory()->create(['user_type' => 'business']);
    $this->worker = User::factory()->create(['user_type' => 'worker']);
});

describe('BIZ-005: Roster Management', function () {

    describe('createRoster', function () {
        it('creates a roster for a business', function () {
            $roster = $this->rosterService->createRoster($this->business, [
                'name' => 'VIP Staff',
                'description' => 'Our best workers',
                'type' => 'preferred',
                'is_default' => true,
            ]);

            expect($roster)->toBeInstanceOf(BusinessRoster::class);
            expect($roster->name)->toBe('VIP Staff');
            expect($roster->business_id)->toBe($this->business->id);
            expect($roster->type)->toBe('preferred');
            expect($roster->is_default)->toBeTrue();
        });

        it('unsets other defaults when creating a new default roster', function () {
            // Create first default roster
            $roster1 = $this->rosterService->createRoster($this->business, [
                'name' => 'First Roster',
                'type' => 'preferred',
                'is_default' => true,
            ]);

            // Create second default roster of same type
            $roster2 = $this->rosterService->createRoster($this->business, [
                'name' => 'Second Roster',
                'type' => 'preferred',
                'is_default' => true,
            ]);

            expect($roster1->fresh()->is_default)->toBeFalse();
            expect($roster2->is_default)->toBeTrue();
        });
    });

    describe('addWorkerToRoster', function () {
        it('adds a worker directly to a roster', function () {
            $roster = BusinessRoster::factory()->create(['business_id' => $this->business->id]);

            $member = $this->rosterService->addWorkerToRoster($roster, $this->worker, [
                'priority' => 10,
                'custom_rate' => 25.50,
                'notes' => 'Great worker',
                'added_by' => $this->business->id,
            ]);

            expect($member)->toBeInstanceOf(RosterMember::class);
            expect($member->worker_id)->toBe($this->worker->id);
            expect($member->priority)->toBe(10);
            expect($member->custom_rate)->toBe('25.50');
            expect($member->notes)->toBe('Great worker');
        });

        it('updates existing member if worker already in roster', function () {
            $roster = BusinessRoster::factory()->create(['business_id' => $this->business->id]);

            $member1 = $this->rosterService->addWorkerToRoster($roster, $this->worker, [
                'priority' => 5,
                'added_by' => $this->business->id,
            ]);

            $member2 = $this->rosterService->addWorkerToRoster($roster, $this->worker, [
                'priority' => 15,
                'added_by' => $this->business->id,
            ]);

            expect($member1->id)->toBe($member2->id);
            expect($member2->priority)->toBe(15);
            expect(RosterMember::where('roster_id', $roster->id)->count())->toBe(1);
        });
    });

    describe('removeWorkerFromRoster', function () {
        it('removes a worker from a roster', function () {
            $roster = BusinessRoster::factory()->create(['business_id' => $this->business->id]);
            $member = RosterMember::factory()->create([
                'roster_id' => $roster->id,
                'worker_id' => $this->worker->id,
            ]);

            $result = $this->rosterService->removeWorkerFromRoster($member);

            expect($result)->toBeTrue();
            expect(RosterMember::find($member->id))->toBeNull();
        });
    });

    describe('inviteWorkerToRoster', function () {
        it('creates an invitation for a worker', function () {
            $this->actingAs($this->business);

            $roster = BusinessRoster::factory()->create(['business_id' => $this->business->id]);

            $invitation = $this->rosterService->inviteWorkerToRoster(
                $roster,
                $this->worker,
                'We would like you to join our team!'
            );

            expect($invitation)->toBeInstanceOf(RosterInvitation::class);
            expect($invitation->worker_id)->toBe($this->worker->id);
            expect($invitation->roster_id)->toBe($roster->id);
            expect($invitation->status)->toBe('pending');
            expect($invitation->message)->toBe('We would like you to join our team!');
            expect($invitation->expires_at)->toBeGreaterThan(now());
        });

        it('expires existing pending invitations', function () {
            $this->actingAs($this->business);

            $roster = BusinessRoster::factory()->create(['business_id' => $this->business->id]);

            $invitation1 = $this->rosterService->inviteWorkerToRoster($roster, $this->worker);
            $invitation2 = $this->rosterService->inviteWorkerToRoster($roster, $this->worker);

            expect($invitation1->fresh()->status)->toBe('expired');
            expect($invitation2->status)->toBe('pending');
        });
    });

    describe('acceptInvitation', function () {
        it('adds worker to roster when accepting invitation', function () {
            $this->actingAs($this->business);

            $roster = BusinessRoster::factory()->create(['business_id' => $this->business->id]);
            $invitation = RosterInvitation::factory()->create([
                'roster_id' => $roster->id,
                'worker_id' => $this->worker->id,
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
                'invited_by' => $this->business->id,
            ]);

            $member = $this->rosterService->acceptInvitation($invitation);

            expect($member)->toBeInstanceOf(RosterMember::class);
            expect($member->worker_id)->toBe($this->worker->id);
            expect($member->status)->toBe('active');
            expect($invitation->fresh()->status)->toBe('accepted');
        });

        it('throws exception for expired invitation', function () {
            $roster = BusinessRoster::factory()->create(['business_id' => $this->business->id]);
            $invitation = RosterInvitation::factory()->create([
                'roster_id' => $roster->id,
                'worker_id' => $this->worker->id,
                'status' => 'pending',
                'expires_at' => now()->subDay(),
                'invited_by' => $this->business->id,
            ]);

            $this->rosterService->acceptInvitation($invitation);
        })->throws(Exception::class);
    });

    describe('declineInvitation', function () {
        it('marks invitation as declined', function () {
            $roster = BusinessRoster::factory()->create(['business_id' => $this->business->id]);
            $invitation = RosterInvitation::factory()->create([
                'roster_id' => $roster->id,
                'worker_id' => $this->worker->id,
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
                'invited_by' => $this->business->id,
            ]);

            $result = $this->rosterService->declineInvitation($invitation);

            expect($result->status)->toBe('declined');
            expect($result->responded_at)->not->toBeNull();
        });
    });

    describe('isWorkerBlacklisted', function () {
        it('returns true if worker is blacklisted', function () {
            $blacklist = BusinessRoster::factory()->create([
                'business_id' => $this->business->id,
                'type' => 'blacklist',
            ]);

            RosterMember::factory()->create([
                'roster_id' => $blacklist->id,
                'worker_id' => $this->worker->id,
                'status' => 'active',
            ]);

            expect($this->rosterService->isWorkerBlacklisted($this->business, $this->worker))->toBeTrue();
        });

        it('returns false if worker is not blacklisted', function () {
            expect($this->rosterService->isWorkerBlacklisted($this->business, $this->worker))->toBeFalse();
        });
    });

    describe('blacklistWorker', function () {
        it('adds worker to blacklist and removes from other rosters', function () {
            $this->actingAs($this->business);

            $regularRoster = BusinessRoster::factory()->create([
                'business_id' => $this->business->id,
                'type' => 'regular',
            ]);

            RosterMember::factory()->create([
                'roster_id' => $regularRoster->id,
                'worker_id' => $this->worker->id,
                'status' => 'active',
            ]);

            $member = $this->rosterService->blacklistWorker($this->business, $this->worker, 'No-show');

            expect($member->roster->type)->toBe('blacklist');
            expect($member->notes)->toBe('No-show');

            // Check removed from regular roster
            expect(RosterMember::where('roster_id', $regularRoster->id)
                ->where('worker_id', $this->worker->id)
                ->exists())->toBeFalse();
        });
    });

    describe('getAvailableRosterWorkers', function () {
        it('excludes blacklisted workers', function () {
            $preferredRoster = BusinessRoster::factory()->create([
                'business_id' => $this->business->id,
                'type' => 'preferred',
            ]);

            $blacklist = BusinessRoster::factory()->create([
                'business_id' => $this->business->id,
                'type' => 'blacklist',
            ]);

            $goodWorker = User::factory()->create(['user_type' => 'worker']);
            $badWorker = User::factory()->create(['user_type' => 'worker']);

            RosterMember::factory()->create([
                'roster_id' => $preferredRoster->id,
                'worker_id' => $goodWorker->id,
                'status' => 'active',
            ]);

            RosterMember::factory()->create([
                'roster_id' => $blacklist->id,
                'worker_id' => $badWorker->id,
                'status' => 'active',
            ]);

            $shift = Shift::factory()->create(['business_id' => $this->business->id]);
            $available = $this->rosterService->getAvailableRosterWorkers($this->business, $shift);

            $workerIds = $available->pluck('worker_id')->toArray();

            expect($workerIds)->toContain($goodWorker->id);
            expect($workerIds)->not->toContain($badWorker->id);
        });

        it('orders by roster type priority', function () {
            $preferredRoster = BusinessRoster::factory()->create([
                'business_id' => $this->business->id,
                'type' => 'preferred',
            ]);

            $backupRoster = BusinessRoster::factory()->create([
                'business_id' => $this->business->id,
                'type' => 'backup',
            ]);

            $preferredWorker = User::factory()->create(['user_type' => 'worker']);
            $backupWorker = User::factory()->create(['user_type' => 'worker']);

            RosterMember::factory()->create([
                'roster_id' => $preferredRoster->id,
                'worker_id' => $preferredWorker->id,
                'status' => 'active',
            ]);

            RosterMember::factory()->create([
                'roster_id' => $backupRoster->id,
                'worker_id' => $backupWorker->id,
                'status' => 'active',
            ]);

            $shift = Shift::factory()->create(['business_id' => $this->business->id]);
            $available = $this->rosterService->getAvailableRosterWorkers($this->business, $shift);

            // Preferred worker should come first
            expect($available->first()->worker_id)->toBe($preferredWorker->id);
        });
    });

    describe('expireOldInvitations', function () {
        it('expires invitations past their expiry date', function () {
            $roster = BusinessRoster::factory()->create(['business_id' => $this->business->id]);

            $expiredInvitation = RosterInvitation::factory()->create([
                'roster_id' => $roster->id,
                'worker_id' => $this->worker->id,
                'status' => 'pending',
                'expires_at' => now()->subDay(),
                'invited_by' => $this->business->id,
            ]);

            $validInvitation = RosterInvitation::factory()->create([
                'roster_id' => $roster->id,
                'worker_id' => User::factory()->create(['user_type' => 'worker'])->id,
                'status' => 'pending',
                'expires_at' => now()->addWeek(),
                'invited_by' => $this->business->id,
            ]);

            $count = $this->rosterService->expireOldInvitations();

            expect($count)->toBe(1);
            expect($expiredInvitation->fresh()->status)->toBe('expired');
            expect($validInvitation->fresh()->status)->toBe('pending');
        });
    });

    describe('getRosterStats', function () {
        it('returns correct statistics', function () {
            $preferredRoster = BusinessRoster::factory()->create([
                'business_id' => $this->business->id,
                'type' => 'preferred',
            ]);

            $regularRoster = BusinessRoster::factory()->create([
                'business_id' => $this->business->id,
                'type' => 'regular',
            ]);

            RosterMember::factory()->count(3)->create([
                'roster_id' => $preferredRoster->id,
                'status' => 'active',
            ]);

            RosterMember::factory()->count(2)->create([
                'roster_id' => $regularRoster->id,
                'status' => 'active',
            ]);

            RosterMember::factory()->create([
                'roster_id' => $regularRoster->id,
                'status' => 'inactive',
            ]);

            $stats = $this->rosterService->getRosterStats($this->business);

            expect($stats['total_rosters'])->toBe(2);
            expect($stats['total_workers'])->toBe(6);
            expect($stats['total_active_workers'])->toBe(5);
            expect($stats['by_type']['preferred']['active_count'])->toBe(3);
            expect($stats['by_type']['regular']['active_count'])->toBe(2);
        });
    });
});
