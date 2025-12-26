<?php

namespace App\Services\Interfaces;

use App\Models\EscrowRecord;
use App\Models\ShiftAssignment;

/**
 * Escrow Service Interface
 *
 * Defines the contract for escrow management operations.
 * All escrow services must implement this interface.
 *
 * ARCH-003: Unified Escrow Service Interface
 */
interface EscrowServiceInterface
{
    /**
     * Capture and hold funds in escrow.
     *
     * @param  ShiftAssignment  $assignment  The shift assignment
     * @return EscrowRecord The created escrow record
     *
     * @throws \Exception If escrow capture fails
     */
    public function captureEscrow(ShiftAssignment $assignment): EscrowRecord;

    /**
     * Release escrow funds.
     *
     * @param  EscrowRecord  $escrow  The escrow record
     * @return bool Success status
     *
     * @throws \Exception If release fails
     */
    public function releaseEscrow(EscrowRecord $escrow): bool;

    /**
     * Refund escrow funds.
     *
     * @param  EscrowRecord  $escrow  The escrow record
     * @param  string  $reason  Refund reason
     * @return bool Success status
     *
     * @throws \Exception If refund fails
     */
    public function refundEscrow(EscrowRecord $escrow, string $reason = ''): bool;

    /**
     * Get escrow state.
     *
     * @param  EscrowRecord  $escrow  The escrow record
     * @return string Current state
     */
    public function getEscrowState(EscrowRecord $escrow): string;

    /**
     * Transition escrow state (ledger-backed).
     *
     * @param  EscrowRecord  $escrow  The escrow record
     * @param  string  $newState  Target state
     * @param  array  $metadata  Transition metadata
     * @return bool Success status
     *
     * @throws \Exception If transition is invalid
     */
    public function transitionState(EscrowRecord $escrow, string $newState, array $metadata = []): bool;

    /**
     * Get escrow ledger entries.
     *
     * @param  EscrowRecord  $escrow  The escrow record
     * @return array Ledger entries
     */
    public function getLedger(EscrowRecord $escrow): array;
}
