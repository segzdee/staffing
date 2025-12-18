{{-- Background Image with Overlay --}}
<div class="absolute inset-0">
    {{-- Professional Gradient Overlay --}}
    <div class="absolute inset-0 bg-gradient-to-br from-blue-600/90 to-slate-900 mix-blend-multiply z-10"></div>

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
        <div class="flex gap-8 pt-4">
            <div>
                <div class="text-3xl font-bold">500+</div>
                <div class="text-sm text-white/70">Active Venues</div>
            </div>
            <div>
                <div class="text-3xl font-bold">2,000+</div>
                <div class="text-sm text-white/70">Workers Matched</div>
            </div>
        </div>
    </div>

    {{-- Testimonial --}}
    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/10">
        <p class="italic text-white/90">
            "OvertimeStaff transformed how we handle staffing. We fill shifts in under an hour now."
        </p>
        <div class="mt-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold">MB</div>
            <div>
                <div class="font-medium">Maria Borg</div>
                <div class="text-sm text-white/70">Operations Manager, The Harbour Club</div>
            </div>
        </div>
    </div>
</div>