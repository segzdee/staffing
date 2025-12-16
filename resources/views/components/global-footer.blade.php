@props(['class' => ''])

<footer class="bg-muted/30 border-t border-border mt-auto {{ $class }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            {{-- Logo --}}
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary-foreground" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                    </svg>
                </div>
                <span class="text-lg font-bold text-foreground">OVERTIMESTAFF</span>
            </div>

            {{-- Links --}}
            <div class="flex gap-6 text-sm text-muted-foreground">
                <a href="{{ route('terms') }}" class="hover:text-foreground transition-colors">Terms of Service</a>
                <a href="{{ route('privacy') }}" class="hover:text-foreground transition-colors">Privacy Policy</a>
            </div>

            {{-- Copyright --}}
            <div class="text-sm text-muted-foreground">
                &copy; {{ date('Y') }} OvertimeStaff. All rights reserved.
            </div>
        </div>
    </div>
</footer>