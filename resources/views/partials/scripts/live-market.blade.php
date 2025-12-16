<script nonce="{{ $cspNonce ?? '' }}">
    // User role detection (for guests on landing page)
    window.userRole = '{{ auth()->check() ? auth()->user()->user_type : "guest" }}';

    // Live Shift Market Component
    window.liveShiftMarket = function (config = {}) {
        return {
            // Configuration
            variant: config.variant || 'full',
            limit: config.limit || 20,

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
            },

            /**
             * Fetch shifts from API
             */
            async fetchShifts() {
                this.loading = true;
                try {
                    const response = await fetch(`/api/market?limit=${this.limit}`);
                    const data = await response.json();

                    if (data.success) {
                        this.shifts = data.shifts || [];
                        if (data.statistics) {
                            this.statistics = { ...this.statistics, ...data.statistics };
                        }
                    }
                } catch (error) {
                    console.debug('Market fetch error (demo mode):', error);
                    // Use demo data on error
                    this.generateDemoShifts();
                } finally {
                    this.loading = false;
                }
            },

            /**
             * Generate demo shifts for landing page display
             */
            generateDemoShifts() {
                const industries = ['Hospitality', 'Healthcare', 'Retail', 'Logistics'];
                const cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix'];
                const states = ['NY', 'CA', 'IL', 'TX', 'AZ'];
                const titles = ['Event Server', 'Warehouse Associate', 'Retail Associate', 'Healthcare Aide', 'Kitchen Staff'];
                const businesses = ['Grand Hotel', 'City Hospital', 'Metro Warehouse', 'Downtown Store', 'Premier Events'];

                this.shifts = Array.from({ length: this.limit }, (_, i) => ({
                    id: i + 1,
                    title: titles[i % titles.length],
                    business_name: businesses[i % businesses.length],
                    industry: industries[i % industries.length].toLowerCase(),
                    location_city: cities[i % cities.length],
                    location_state: states[i % states.length],
                    shift_date: new Date(Date.now() + (i + 1) * 86400000).toISOString().split('T')[0],
                    start_time: `${8 + (i % 8)}:00`,
                    duration_hours: 4 + (i % 5),
                    base_rate: 18 + (i % 15),
                    effective_rate: 18 + (i % 15) + (i % 3 === 0 ? 5 : 0),
                    surge_multiplier: i % 3 === 0 ? 1.25 : 1.0,
                    required_workers: 3 + (i % 5),
                    spots_remaining: 1 + (i % 3),
                    fill_percentage: 30 + (i % 60),
                    is_urgent: i % 4 === 0,
                    instant_claim_enabled: i % 3 === 0,
                    is_new: i < 3,
                    is_demo: true,
                    match_score: null,
                    market_posted_at: '2 hours ago',
                    market_views: 45 + (i * 12)
                }));
            },

            /**
             * Start polling for updates
             */
            startPolling() {
                // Poll every 60 seconds (reduced frequency for landing)
                this.pollInterval = setInterval(() => {
                    this.fetchShifts();
                }, 60000);
            },

            /**
             * Apply to a shift
             */
            async applyToShift(shift) {
                if (shift.is_demo) {
                    window.location.href = '{{ route("register", ["type" => "worker"]) }}';
                    return;
                }
                // Redirect to login if not authenticated
                if (window.userRole === 'guest') {
                    window.location.href = '{{ route("login") }}';
                    return;
                }
            },

            /**
             * Instant claim a shift
             */
            async instantClaim(shift) {
                if (shift.is_demo) {
                    window.location.href = '{{ route("register", ["type" => "worker"]) }}';
                    return;
                }
                if (window.userRole === 'guest') {
                    window.location.href = '{{ route("login") }}';
                    return;
                }
            },

            /**
             * Open agency assign modal
             */
            openAgencyAssignModal(shift) {
                if (shift.is_demo) {
                    window.location.href = '{{ route("register", ["type" => "agency"]) }}';
                    return;
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
             * Cleanup on destroy
             */
            destroy() {
                if (this.pollInterval) clearInterval(this.pollInterval);
                if (this.activityInterval) clearInterval(this.activityInterval);
            }
        };
    };
</script>