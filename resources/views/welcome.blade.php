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

        {{-- Live Activity Ticker --}}
        <div class="absolute top-0 left-0 right-0 z-10 pt-20">
            <x-marketing.live-ticker />
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-foreground mb-6">
                Staffing, <span class="text-primary">Simplified.</span>
            </h1>
            <p class="text-xl text-muted-foreground mb-6 max-w-3xl mx-auto leading-relaxed">
                Unlike traditional agencies, OvertimeStaff is a direct marketplace. Post shifts, workers apply instantly, and our AI matches you with verified talent—no middlemen, no markups, no phone tag.
            </p>
            <p class="text-lg text-muted-foreground/80 mb-10 max-w-2xl mx-auto">
                Escrow-protected payments. Instant payouts. Real-time matching. Built for the modern workforce.
            </p>

            {{-- Market Stats - Prominent Display --}}
            <div class="mb-10 flex flex-wrap justify-center items-center gap-6 sm:gap-8 text-sm">
                <div class="flex items-center gap-2 px-4 py-2 bg-primary/10 rounded-lg border border-primary/20">
                    <span class="text-muted-foreground font-medium">VOL:</span>
                    <span class="text-2xl font-bold text-primary" x-data x-text="'247'">247</span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-green-500/10 rounded-lg border border-green-500/20">
                    <span class="text-muted-foreground font-medium">VAL:</span>
                    <span class="text-2xl font-bold text-green-600" x-data x-text="'$42.5K'">$42.5K</span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-blue-500/10 rounded-lg border border-blue-500/20">
                    <span class="text-muted-foreground font-medium">AVG:</span>
                    <span class="text-2xl font-bold text-blue-600" x-data x-text="'$32/hr'">$32/hr</span>
                </div>
            </div>

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

            {{-- Trust Indicators - Removed until we have verified client relationships --}}
            {{-- If you have real clients, uncomment and add "Trusted by teams at..." prefix --}}
        </div>
    </section>



    {{-- Value Proposition --}}
    <section class="py-24 bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8">
                {{-- Feature 1 --}}
                <x-ui.card class="bg-card hover:shadow-lg transition-all border-2 border-transparent hover:border-primary/20">
                    <x-ui.card-header class="pb-4">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <x-ui.card-title class="text-xl mb-2">Instant Booking</x-ui.card-title>
                                <p class="text-muted-foreground leading-relaxed text-sm">
                                    Post a shift and fill it in minutes. Our AI matching algorithm connects you with the best available
                                    talent instantly—no waiting, no back-and-forth.
                                </p>
                            </div>
                        </div>
                    </x-ui.card-header>
                </x-ui.card>

                {{-- Feature 2 --}}
                <x-ui.card class="bg-card hover:shadow-lg transition-all border-2 border-transparent hover:border-green-500/20">
                    <x-ui.card-header class="pb-4">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <x-ui.card-title class="text-xl mb-2">Verified Talent</x-ui.card-title>
                                <p class="text-muted-foreground leading-relaxed text-sm">
                                    Every worker is vetted before they can claim shifts. ID verification, right-to-work checks, and skills assessment—all automated, all transparent.
                                </p>
                            </div>
                        </div>
                    </x-ui.card-header>
                </x-ui.card>

                {{-- Feature 3 --}}
                <x-ui.card class="bg-card hover:shadow-lg transition-all border-2 border-transparent hover:border-purple-500/20">
                    <x-ui.card-header class="pb-4">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <x-ui.card-title class="text-xl mb-2">Automatic Payroll</x-ui.card-title>
                                <p class="text-muted-foreground leading-relaxed text-sm">
                                    Escrow-protected payments, automatic tax handling, and instant worker payouts. You approve hours, we handle everything else.
                                </p>
                            </div>
                        </div>
                    </x-ui.card-header>
                </x-ui.card>
            </div>
        </div>
    </section>



    @include('partials.scripts.live-market')
@endsection