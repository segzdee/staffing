{{--
    GLO-005: GDPR/CCPA Compliance - Cookie Consent Banner Component

    This component displays a GDPR-compliant cookie consent banner.
    Include this in your main layout file before the closing </body> tag.

    Usage:
    <x-cookie-consent />
--}}

<div
    x-data="cookieConsent()"
    x-show="showBanner"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-4"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform translate-y-4"
    x-cloak
    class="fixed bottom-0 inset-x-0 z-50 pb-2 sm:pb-5"
    role="dialog"
    aria-labelledby="cookie-consent-title"
    aria-describedby="cookie-consent-description"
>
    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="p-2 rounded-lg bg-gray-900 shadow-lg sm:p-3">
            <div class="flex flex-wrap items-center justify-between">
                <div class="flex-1 flex items-center min-w-0">
                    <span class="flex p-2 rounded-lg bg-gray-800">
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>
                    <p id="cookie-consent-description" class="ml-3 font-medium text-white truncate">
                        <span class="md:hidden">
                            We use cookies to improve your experience.
                        </span>
                        <span class="hidden md:inline">
                            We use cookies and similar technologies to provide you with the best experience. Some cookies are necessary for the platform to function, while others help us improve your experience.
                        </span>
                    </p>
                </div>
                <div class="order-3 mt-2 flex-shrink-0 w-full sm:order-2 sm:mt-0 sm:w-auto flex space-x-2">
                    <button
                        @click="showPreferences = true"
                        type="button"
                        class="flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-gray-900 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white"
                    >
                        Customize
                    </button>
                    <button
                        @click="acceptAll()"
                        type="button"
                        class="flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-indigo-500"
                    >
                        Accept All
                    </button>
                </div>
                <div class="order-2 flex-shrink-0 sm:order-3 sm:ml-2">
                    <button
                        @click="rejectOptional()"
                        type="button"
                        class="-mr-1 flex p-2 rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-white"
                        title="Accept necessary cookies only"
                    >
                        <span class="sr-only">Accept necessary only</span>
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Cookie Preferences Modal --}}
    <div
        x-show="showPreferences"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="cookie-preferences-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showPreferences = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                x-show="showPreferences"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
            >
                <div>
                    <div class="text-center sm:text-left">
                        <h3 id="cookie-preferences-title" class="text-lg leading-6 font-medium text-gray-900">
                            Cookie Preferences
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Manage your cookie preferences below. Some cookies are necessary for the platform to function properly.
                        </p>
                    </div>

                    <div class="mt-6 space-y-4">
                        {{-- Necessary Cookies --}}
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input
                                    id="cookie-necessary"
                                    type="checkbox"
                                    checked
                                    disabled
                                    class="h-4 w-4 text-indigo-600 border-gray-300 rounded cursor-not-allowed"
                                >
                            </div>
                            <div class="ml-3">
                                <label for="cookie-necessary" class="font-medium text-gray-700">Necessary Cookies</label>
                                <p class="text-sm text-gray-500">Essential cookies required for the platform to function. Cannot be disabled.</p>
                            </div>
                        </div>

                        {{-- Functional Cookies --}}
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input
                                    id="cookie-functional"
                                    type="checkbox"
                                    x-model="preferences.functional"
                                    class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                >
                            </div>
                            <div class="ml-3">
                                <label for="cookie-functional" class="font-medium text-gray-700">Functional Cookies</label>
                                <p class="text-sm text-gray-500">Enable enhanced functionality and personalization.</p>
                            </div>
                        </div>

                        {{-- Analytics Cookies --}}
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input
                                    id="cookie-analytics"
                                    type="checkbox"
                                    x-model="preferences.analytics"
                                    class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                >
                            </div>
                            <div class="ml-3">
                                <label for="cookie-analytics" class="font-medium text-gray-700">Analytics Cookies</label>
                                <p class="text-sm text-gray-500">Help us understand how you use our platform to improve it.</p>
                            </div>
                        </div>

                        {{-- Marketing Cookies --}}
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input
                                    id="cookie-marketing"
                                    type="checkbox"
                                    x-model="preferences.marketing"
                                    class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                >
                            </div>
                            <div class="ml-3">
                                <label for="cookie-marketing" class="font-medium text-gray-700">Marketing Cookies</label>
                                <p class="text-sm text-gray-500">Allow personalized ads and promotional content.</p>
                            </div>
                        </div>

                        {{-- AI Matching --}}
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input
                                    id="cookie-profiling"
                                    type="checkbox"
                                    x-model="preferences.profiling"
                                    class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                >
                            </div>
                            <div class="ml-3">
                                <label for="cookie-profiling" class="font-medium text-gray-700">AI-Based Matching</label>
                                <p class="text-sm text-gray-500">Allow AI to analyze your profile for better shift recommendations.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 text-sm text-gray-500">
                        <p>
                            For more information, see our
                            <a href="{{ route('privacy.settings') }}" class="text-indigo-600 hover:text-indigo-500">Privacy Policy</a>.
                        </p>
                    </div>
                </div>

                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button
                        @click="savePreferences()"
                        type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm"
                    >
                        Save Preferences
                    </button>
                    <button
                        @click="showPreferences = false"
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cookieConsent() {
    return {
        showBanner: false,
        showPreferences: false,
        preferences: {
            necessary: true, // Always true, can't be changed
            functional: false,
            analytics: false,
            marketing: false,
            profiling: false,
        },

        init() {
            // Check if user has already made a choice
            const consent = this.getStoredConsent();
            if (!consent) {
                this.showBanner = true;
            } else {
                this.preferences = { ...this.preferences, ...consent };
                this.applyConsent();
            }
        },

        getStoredConsent() {
            const stored = localStorage.getItem('cookie_consent');
            if (stored) {
                try {
                    return JSON.parse(stored);
                } catch (e) {
                    return null;
                }
            }
            return null;
        },

        storeConsent(consents) {
            localStorage.setItem('cookie_consent', JSON.stringify(consents));
            localStorage.setItem('cookie_consent_date', new Date().toISOString());

            // Also send to server to record consent
            this.sendConsentToServer(consents);
        },

        async sendConsentToServer(consents) {
            try {
                const response = await fetch('{{ route("privacy.cookie-consent") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({ consents }),
                });

                if (!response.ok) {
                    console.error('Failed to record consent on server');
                }
            } catch (error) {
                console.error('Error sending consent to server:', error);
            }
        },

        acceptAll() {
            this.preferences = {
                necessary: true,
                functional: true,
                analytics: true,
                marketing: true,
                profiling: true,
            };
            this.savePreferences();
        },

        rejectOptional() {
            this.preferences = {
                necessary: true,
                functional: false,
                analytics: false,
                marketing: false,
                profiling: false,
            };
            this.savePreferences();
        },

        savePreferences() {
            // Ensure necessary is always true
            this.preferences.necessary = true;

            this.storeConsent(this.preferences);
            this.applyConsent();
            this.showBanner = false;
            this.showPreferences = false;
        },

        applyConsent() {
            // Dispatch event for other scripts to listen to
            window.dispatchEvent(new CustomEvent('cookie-consent-updated', {
                detail: this.preferences
            }));

            // Apply analytics consent
            if (this.preferences.analytics) {
                this.enableAnalytics();
            } else {
                this.disableAnalytics();
            }

            // Apply marketing consent
            if (this.preferences.marketing) {
                this.enableMarketing();
            } else {
                this.disableMarketing();
            }
        },

        enableAnalytics() {
            // Enable Google Analytics, etc.
            // This is a placeholder - implement based on your analytics setup
            if (typeof gtag === 'function') {
                gtag('consent', 'update', {
                    'analytics_storage': 'granted'
                });
            }
        },

        disableAnalytics() {
            // Disable Google Analytics, etc.
            if (typeof gtag === 'function') {
                gtag('consent', 'update', {
                    'analytics_storage': 'denied'
                });
            }
        },

        enableMarketing() {
            // Enable marketing/advertising cookies
            if (typeof gtag === 'function') {
                gtag('consent', 'update', {
                    'ad_storage': 'granted',
                    'ad_user_data': 'granted',
                    'ad_personalization': 'granted'
                });
            }
        },

        disableMarketing() {
            // Disable marketing/advertising cookies
            if (typeof gtag === 'function') {
                gtag('consent', 'update', {
                    'ad_storage': 'denied',
                    'ad_user_data': 'denied',
                    'ad_personalization': 'denied'
                });
            }
        }
    };
}
</script>
