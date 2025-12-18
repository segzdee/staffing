/**
 * Live Shift Market Alpine.js Component
 * Real-time shift marketplace with demo fallback
 */

window.liveShiftMarket = function (config = {}) {
    return {
        // Configuration
        variant: config.variant || 'full',
        limit: config.limit || 20,
        endpoint: config.endpoint || '/api/market/live',

        // State
        shifts: [],
        statistics: {
            shifts_live: 247,
            total_value: 42500,
            avg_hourly_rate: 32,
            rate_change_percent: 3.2,
            filled_today: 89,
            workers_online: 1247
        },
        activityFeed: [],
        loading: true,
        isWorker: window.userRole === 'worker',
        isAgency: window.userRole === 'agency',

        // Modal state
        showAssignModal: false,
        selectedShift: null,
        selectedWorkerId: '',

        // Polling
        pollInterval: null,
        activityInterval: null,

        /**
         * Initialize the component
         */
        init() {
            this.fetchShifts();
            this.startPolling();
            this.simulateDemoActivity();
        },

        /**
         * Fetch shifts from API
         */
        async fetchShifts() {
            this.loading = true;
            try {
                const response = await fetch(`${this.endpoint}?limit=${this.limit}`);
                const data = await response.json();

                if (data.success) {
                    this.shifts = data.shifts;
                    this.statistics = data.statistics;

                    // If we have demo shifts, simulate some activity
                    if (data.has_demo_shifts) {
                        this.simulateDemoActivity();
                    }
                }
            } catch (error) {
                console.error('Failed to fetch shifts:', error);
            } finally {
                this.loading = false;
            }
        },

        /**
         * Start polling for updates
         */
        startPolling() {
            // Poll every 30 seconds
            this.pollInterval = setInterval(() => {
                this.fetchShifts();
            }, 30000);

            // Simulate activity every 5 seconds
            this.activityInterval = setInterval(() => {
                this.simulateDemoActivity();
            }, 5000);
        },

        /**
         * Simulate demo activity for feed
         */
        async simulateDemoActivity() {
            try {
                const response = await fetch('/api/market/simulate');
                const data = await response.json();

                if (data.success && data.activities) {
                    // Add new activities to the front
                    this.activityFeed = [
                        ...data.activities,
                        ...this.activityFeed.slice(0, 20)
                    ];
                }
            } catch (error) {
                // Silent fail for demo activity
                console.debug('Demo activity simulation error:', error);
            }
        },

        /**
         * Apply to a shift
         */
        async applyToShift(shift) {
            if (!this.isWorker) {
                alert('You must be logged in as a worker to apply');
                return;
            }

            if (shift.is_demo) {
                alert('This is a demo shift. Sign up to apply to real shifts!');
                return;
            }

            try {
                const response = await fetch(`/shifts/${shift.id}/apply`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('Application submitted successfully!');
                    this.fetchShifts(); // Refresh
                } else {
                    alert(data.message || 'Failed to apply');
                }
            } catch (error) {
                console.error('Apply error:', error);
                alert('Failed to apply to shift');
            }
        },

        /**
         * Instant claim a shift
         */
        async instantClaim(shift) {
            if (!this.isWorker) {
                alert('You must be logged in as a worker to claim');
                return;
            }

            if (shift.is_demo) {
                alert('This is a demo shift. Sign up to claim real shifts!');
                return;
            }

            if (!confirm(`Claim this ${shift.title} shift for $${shift.effective_rate.toFixed(2)}/hr?`)) {
                return;
            }

            try {
                const response = await fetch(`/shifts/${shift.id}/claim`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('Shift claimed successfully! 🎉');
                    this.fetchShifts(); // Refresh
                } else {
                    alert(data.message || 'Failed to claim shift');
                }
            } catch (error) {
                console.error('Claim error:', error);
                alert('Failed to claim shift');
            }
        },

        /**
         * Open agency assign modal
         */
        openAgencyAssignModal(shift) {
            this.selectedShift = shift;
            this.selectedWorkerId = '';
            this.showAssignModal = true;
        },

        /**
         * Agency assign worker to shift
         */
        async agencyAssign() {
            if (!this.selectedWorkerId) {
                alert('Please select a worker');
                return;
            }

            try {
                const response = await fetch(`/shifts/${this.selectedShift.id}/assign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        worker_id: this.selectedWorkerId
                    })
                });

                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('CRITICAL API ERROR: Expected JSON but got:', text.substring(0, 500));
                        throw e;
                    }
                });

                if (data.success) {
                    alert('Worker assigned successfully!');
                    this.showAssignModal = false;
                    this.fetchShifts(); // Refresh
                } else {
                    alert(data.message || 'Failed to assign worker');
                }
            } catch (error) {
                console.error('Assign error:', error);
                alert('Failed to assign worker');
            }
        },

        /**
         * Format shift time for display
         */
        formatShiftTime(shift) {
            try {
                const date = new Date(shift.shift_date);
                const options = { month: 'short', day: 'numeric' };
                return date.toLocaleDateString('en-US', options) + ' ' + shift.start_time;
            } catch (e) {
                return shift.shift_date;
            }
        },

        /**
         * Calculate total earnings for a shift
         */
        calculateEarnings(shift) {
            const total = shift.effective_rate * shift.duration_hours;
            return '$' + total.toFixed(2);
        },

        /**
         * Format money values
         */
        formatMoney(value) {
            if (value >= 1000000) {
                return '$' + (value / 1000000).toFixed(1) + 'M';
            } else if (value >= 1000) {
                return '$' + (value / 1000).toFixed(1) + 'K';
            }
            return '$' + value.toFixed(0);
        },

        /**
         * Cleanup on destroy
         */
        destroy() {
            if (this.pollInterval) clearInterval(this.pollInterval);
            if (this.activityInterval) clearInterval(this.activityInterval);
        }
    };
};
