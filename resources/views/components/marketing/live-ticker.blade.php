@props(['endpoint' => '/api/market/simulate'])

<div x-data="liveTicker('{{ $endpoint }}')" x-init="init()"
    class="w-full bg-black/5 border-y border-black/5 backdrop-blur-sm overflow-hidden py-2 hidden md:block"
    style="display: none;" x-show="activities.length > 0" x-transition.opacity>
    <div class="max-w-7xl mx-auto px-4 flex items-center gap-4">
        <div
            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-green-600 bg-green-100 px-2 py-0.5 rounded-full shrink-0">
            <span class="relative flex h-2 w-2">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
            </span>
            Live Market
        </div>

        <div class="relative flex-1 overflow-hidden h-6">
            <template x-for="(activity, index) in activities" :key="index">
                <div class="absolute inset-0 flex items-center transition-all duration-500 ease-in-out"
                    x-show="currentIndex === index" x-transition:enter="translate-y-full opacity-0"
                    x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="translate-y-0 opacity-100"
                    x-transition:leave-end="-translate-y-full opacity-0">
                    <span class="text-sm font-medium text-gray-700 truncate" x-text="activity.message"></span>
                    <span class="text-xs text-gray-400 ml-2" x-text="formatTime(activity.timestamp)"></span>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('liveTicker', (endpoint) => ({
            activities: [],
            currentIndex: 0,
            interval: null,
            fetchInterval: null,

            init() {
                this.fetchActivities();
                // Fetch new data every 30 seconds
                this.fetchInterval = setInterval(() => this.fetchActivities(), 30000);
                // Rotate messages every 4 seconds
                this.interval = setInterval(() => {
                    if (this.activities.length > 0) {
                        this.currentIndex = (this.currentIndex + 1) % this.activities.length;
                    }
                }, 4000);
            },

            async fetchActivities() {
                try {
                    const response = await fetch(endpoint);
                    const data = await response.json();
                    if (data.success && data.activities.length > 0) {
                        // If we have activities, verify duplicates or just replace for freshness
                        this.activities = data.activities;
                    }
                } catch (error) {
                    console.error('Failed to fetch live activity:', error);
                }
            },

            formatTime(timestamp) {
                // Simple "Just now" or "2m ago" logic could go here
                // For now, we assume the server provides a decent timestamp or we just say "Just now"
                return 'Just now';
            }
        }));
    });
</script>