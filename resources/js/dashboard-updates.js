/**
 * Dashboard Live Updates
 * Polls /api/dashboard/stats every 30 seconds and updates dashboard widgets
 */

(function() {
    'use strict';

    let pollInterval = null;
    const POLL_INTERVAL = 30000; // 30 seconds
    const API_ENDPOINT = '/api/dashboard/stats';
    const NOTIFICATIONS_ENDPOINT = '/api/dashboard/notifications/count';

    /**
     * Initialize dashboard polling
     */
    function initDashboardUpdates() {
        if (!window.userId || !window.axios) {
            console.warn('Dashboard updates: Missing userId or axios');
            return;
        }

        // Initial load
        updateDashboardStats();
        updateNotificationBadge();

        // Set up polling
        pollInterval = setInterval(() => {
            updateDashboardStats();
            updateNotificationBadge();
        }, POLL_INTERVAL);

        // Pause polling when tab is hidden
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = null;
                }
            } else {
                if (!pollInterval) {
                    updateDashboardStats();
                    updateNotificationBadge();
                    pollInterval = setInterval(() => {
                        updateDashboardStats();
                        updateNotificationBadge();
                    }, POLL_INTERVAL);
                }
            }
        });
    }

    /**
     * Update dashboard statistics
     */
    async function updateDashboardStats() {
        try {
            const response = await window.axios.get(API_ENDPOINT, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.data) {
                updateStatsDisplay(response.data);
            }
        } catch (error) {
            // Silently fail - don't spam console on network errors
            if (error.response && error.response.status !== 401) {
                console.debug('Dashboard stats update failed:', error.message);
            }
        }
    }

    /**
     * Update notification badge count
     */
    async function updateNotificationBadge() {
        try {
            const response = await window.axios.get(NOTIFICATIONS_ENDPOINT, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.data && typeof response.data.count !== 'undefined') {
                // Use global updateNotificationBadge function if available (from notifications.js)
                if (typeof window.updateNotificationBadge === 'function') {
                    window.updateNotificationBadge(response.data.count);
                } else {
                    // Fallback: Update badge directly
                    const badge = document.querySelector('[data-notification-badge]');
                    if (badge) {
                        badge.textContent = response.data.count > 0 ? response.data.count : '';
                        badge.style.display = response.data.count > 0 ? 'block' : 'none';
                    }
                }
            }
        } catch (error) {
            // Silently fail
            if (error.response && error.response.status !== 401) {
                console.debug('Notification count update failed:', error.message);
            }
        }
    }

    /**
     * Update stats display based on user type
     */
    function updateStatsDisplay(stats) {
        // Worker dashboard updates
        if (stats.shifts_today !== undefined) {
            updateElement('[data-stat="shifts_today"]', stats.shifts_today);
        }
        if (stats.shifts_this_week !== undefined) {
            updateElement('[data-stat="shifts_this_week"]', stats.shifts_this_week);
        }
        if (stats.pending_applications !== undefined) {
            updateElement('[data-stat="pending_applications"]', stats.pending_applications);
        }
        if (stats.earnings_this_week !== undefined) {
            updateElement('[data-stat="earnings_this_week"]', formatCurrency(stats.earnings_this_week));
        }
        if (stats.earnings_this_month !== undefined) {
            updateElement('[data-stat="earnings_this_month"]', formatCurrency(stats.earnings_this_month));
        }
        if (stats.total_completed !== undefined) {
            updateElement('[data-stat="total_completed"]', stats.total_completed);
        }
        if (stats.rating !== undefined) {
            updateElement('[data-stat="rating"]', stats.rating.toFixed(1));
        }

        // Business dashboard updates
        if (stats.active_shifts !== undefined) {
            updateElement('[data-stat="active_shifts"]', stats.active_shifts);
        }
        if (stats.pending_applications !== undefined) {
            updateElement('[data-stat="pending_applications"]', stats.pending_applications);
        }
        if (stats.workers_today !== undefined) {
            updateElement('[data-stat="workers_today"]', stats.workers_today);
        }
        if (stats.cost_this_week !== undefined) {
            updateElement('[data-stat="cost_this_week"]', formatCurrency(stats.cost_this_week));
        }
        if (stats.cost_this_month !== undefined) {
            updateElement('[data-stat="cost_this_month"]', formatCurrency(stats.cost_this_month));
        }
        if (stats.total_shifts_posted !== undefined) {
            updateElement('[data-stat="total_shifts_posted"]', stats.total_shifts_posted);
        }

        // Agency dashboard updates
        if (stats.total_workers !== undefined) {
            updateElement('[data-stat="total_workers"]', stats.total_workers);
        }
        if (stats.active_placements !== undefined) {
            updateElement('[data-stat="active_placements"]', stats.active_placements);
        }
        if (stats.revenue_this_month !== undefined) {
            updateElement('[data-stat="revenue_this_month"]', formatCurrency(stats.revenue_this_month));
        }
        if (stats.total_placements_month !== undefined) {
            updateElement('[data-stat="total_placements_month"]', stats.total_placements_month);
        }
    }

    /**
     * Update a DOM element with new value
     */
    function updateElement(selector, value) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => {
            const oldValue = el.textContent.trim();
            if (oldValue !== String(value)) {
                // Add animation class
                el.classList.add('stat-updated');
                el.textContent = value;
                
                // Remove animation class after animation
                setTimeout(() => {
                    el.classList.remove('stat-updated');
                }, 500);
            }
        });
    }

    /**
     * Format currency value
     */
    function formatCurrency(value) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    }

    /**
     * Cleanup polling on page unload
     */
    function cleanup() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDashboardUpdates);
    } else {
        initDashboardUpdates();
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', cleanup);

    // Export for manual control if needed
    window.dashboardUpdates = {
        update: updateDashboardStats,
        stop: cleanup,
        start: initDashboardUpdates
    };
})();
