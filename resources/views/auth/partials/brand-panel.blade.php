{{-- Background Image with Overlay --}}
<div class="absolute inset-0">
    {{-- Warmer, More Approachable Gradient --}}
    <div class="absolute inset-0 bg-gradient-to-br from-amber-600/95 via-orange-600/90 to-rose-700/95 mix-blend-multiply z-10"></div>

    {{-- Abstract Pattern --}}
    <svg class="absolute inset-0 w-full h-full opacity-10 z-0" viewBox="0 0 100 100" preserveAspectRatio="none">
        <pattern id="grid-pattern" width="8" height="8" patternUnits="userSpaceOnUse">
            <path d="M0 8L8 0" stroke="white" stroke-width="0.5" />
        </pattern>
        <rect width="100" height="100" fill="url(#grid-pattern)" />
    </svg>
</div>

{{-- Content --}}
<div class="relative z-20 p-12 flex flex-col justify-between h-full text-white">
    {{-- Logo --}}
    <a href="{{ url('/') }}">
        <x-logo class="h-10 w-auto text-white" :dark="true" />
    </a>

    {{-- Value Proposition --}}
    <div class="space-y-6">
        <h1 class="text-4xl font-bold leading-tight">
            {{ $brandHeading ?? 'Find your next shift in seconds' }}
        </h1>
        <p class="text-xl text-white/80">
            {{ $brandSubheading ?? 'Connect with top hospitality venues across the globe' }}
        </p>

        {{-- Stats/Social Proof --}}
        <div class="grid grid-cols-3 gap-6 pt-4">
            <div>
                <div class="text-3xl font-bold">500+</div>
                <div class="text-sm text-white/70">Active Venues</div>
            </div>
            <div>
                <div class="text-3xl font-bold">2,000+</div>
                <div class="text-sm text-white/70">Workers Matched</div>
            </div>
            <div>
                <div class="text-3xl font-bold">98%</div>
                <div class="text-sm text-white/70">Fill Rate</div>
            </div>
        </div>

        {{-- Testimonial - Moved Higher --}}
        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/10 mt-6">
            <p class="italic text-white/90 mb-4">
                "OvertimeStaff transformed how we handle staffing. We fill shifts in under an hour now."
            </p>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold">MB</div>
                <div>
                    <div class="font-medium">Maria Borg</div>
                    <div class="text-sm text-white/70">Operations Manager, The Harbour Club</div>
                </div>
            </div>
        </div>

        {{-- Additional Social Proof --}}
        <div class="space-y-3 pt-4">
            <div class="flex items-center gap-3 text-sm text-white/80">
                <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>Verified workers only</span>
            </div>
            <div class="flex items-center gap-3 text-sm text-white/80">
                <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>Escrow-protected payments</span>
            </div>
            <div class="flex items-center gap-3 text-sm text-white/80">
                <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>24/7 support</span>
            </div>
        </div>
    </div>
</div>