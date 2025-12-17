@extends('layouts.marketing')

@section('title', 'OvertimeStaff - Work. Covered.')
@section('meta_description', 'The minimal staffing marketplace. Connect with verified workers instantly.')

@section('content')
    {{-- Hero Section --}}
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden bg-background">
        {{-- Background Pattern --}}
        <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80">
            <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"
                style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)">
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-foreground mb-6">
                Staffing, <span class="text-primary">Simplified.</span>
            </h1>
            <p class="text-xl text-muted-foreground mb-10 max-w-2xl mx-auto leading-relaxed">
                The global shift marketplace. Connect with verified workers instantly. No agencies. No phone calls. Just
                work.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <x-ui.button size="lg" as="a" href="{{ route('register', ['type' => 'business']) }}">
                    Get Started
                </x-ui.button>
                <x-ui.button variant="outline" size="lg" as="a" href="{{ route('register', ['type' => 'worker']) }}">
                    View Open Shifts
                </x-ui.button>
            </div>
            <div class="mt-6">
                <a href="{{ route('register', ['type' => 'agency']) }}"
                    class="text-sm text-muted-foreground hover:text-primary transition-colors underline decoration-dotted">
                    Are you a Staffing Agency? Partner with us.
                </a>
            </div>

            {{-- Live Market Section Moved Here --}}
            <section id="live-market" class="py-12 bg-muted/30 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 mb-16">
                <div class="max-w-7xl mx-auto">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl mb-2">Live Shifts</h2>
                        <p class="text-base text-muted-foreground max-w-2xl mx-auto">
                            Real-time marketplace activity. See what's happening right now.
                        </p>
                    </div>

                    <x-live-shift-market variant="wallstreet" :limit="12" endpoint="/api/market/public" />

                    <div class="mt-6 text-center">
                        <x-ui.button variant="secondary" as="a" href="{{ route('register') }}?type=worker">
                            Browse All Active Shifts &rarr;
                        </x-ui.button>
                    </div>
                </div>
            </section>

            {{-- Trust Indicators --}}
            <div class="pt-8 border-t border-border">
                <p class="text-sm font-medium text-muted-foreground mb-6">Trusted by industry leaders worldwide</p>
                <div
                    class="flex flex-wrap justify-center gap-8 opacity-60 grayscale transition-all hover:grayscale-0 hover:opacity-100">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2f/Google_2015_logo.svg" alt="Google"
                        class="h-6">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/IBM_logo.svg" alt="IBM" class="h-6">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/6/69/Airbnb_Logo_B%C3%A9lo.svg" alt="Airbnb"
                        class="h-6">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg" alt="Amazon" class="h-6">
                </div>
            </div>
        </div>
    </section>



    {{-- Value Proposition --}}
    <section class="py-24 bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8">
                {{-- Feature 1 --}}
                <x-ui.card class="bg-card hover:shadow-md transition-shadow">
                    <x-ui.card-header>
                        <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4 text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <x-ui.card-title>Instant Booking</x-ui.card-title>
                    </x-ui.card-header>
                    <x-ui.card-content>
                        <p class="text-muted-foreground leading-relaxed">
                            Post a shift and fill it in minutes. Our matching algorithm connects you with the best available
                            talent instantly.
                        </p>
                    </x-ui.card-content>
                </x-ui.card>

                {{-- Feature 2 --}}
                <x-ui.card class="bg-card hover:shadow-md transition-shadow">
                    <x-ui.card-header>
                        <div
                            class="w-12 h-12 bg-green-500/10 rounded-lg flex items-center justify-center mb-4 text-green-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <x-ui.card-title>Verified Talent</x-ui.card-title>
                    </x-ui.card-header>
                    <x-ui.card-content>
                        <p class="text-muted-foreground leading-relaxed">
                            Every worker is vetted. ID checks, right-to-work verification, and skills assessment before they
                            can claim a shift.
                        </p>
                    </x-ui.card-content>
                </x-ui.card>

                {{-- Feature 3 --}}
                <x-ui.card class="bg-card hover:shadow-md transition-shadow">
                    <x-ui.card-header>
                        <div
                            class="w-12 h-12 bg-purple-500/10 rounded-lg flex items-center justify-center mb-4 text-purple-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <x-ui.card-title>Automatic Payroll</x-ui.card-title>
                    </x-ui.card-header>
                    <x-ui.card-content>
                        <p class="text-muted-foreground leading-relaxed">
                            We handle the payments, taxes, and invoices. You just approve the hours and get back to
                            business.
                        </p>
                    </x-ui.card-content>
                </x-ui.card>
            </div>
        </div>
    </section>



    @include('partials.scripts.live-market')
@endsection