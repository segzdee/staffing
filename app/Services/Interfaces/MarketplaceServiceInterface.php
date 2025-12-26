<?php

namespace App\Services\Interfaces;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Marketplace Service Interface
 *
 * Defines the contract for marketplace operations.
 * All marketplace services must implement this interface.
 *
 * ARCH-005: Unified Marketplace Service Interface
 */
interface MarketplaceServiceInterface
{
    /**
     * Search shifts with filters.
     *
     * @param  array  $filters  Search filters
     * @param  int  $perPage  Results per page
     * @return LengthAwarePaginator Paginated shift results
     */
    public function searchShifts(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get recommended shifts for a worker.
     *
     * @param  User  $worker  The worker
     * @param  int  $limit  Number of recommendations
     * @return array Recommended shifts
     */
    public function getRecommendedShifts(User $worker, int $limit = 10): array;

    /**
     * Calculate match score between worker and shift.
     *
     * @param  User  $worker  The worker
     * @param  Shift  $shift  The shift
     * @return float Match score (0-100)
     */
    public function calculateMatchScore(User $worker, Shift $shift): float;

    /**
     * Get marketplace statistics.
     *
     * @param  array  $filters  Optional filters
     * @return array Statistics data
     */
    public function getMarketplaceStats(array $filters = []): array;

    /**
     * Get trending shifts.
     *
     * @param  int  $limit  Number of shifts
     * @return array Trending shifts
     */
    public function getTrendingShifts(int $limit = 10): array;
}
