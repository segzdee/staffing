@props(['class' => ''])

<footer class="bg-muted/30 border-t border-border mt-auto {{ $class }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            {{-- Logo --}}
            <div class="flex items-center gap-2">
                <x-logo class="h-8 w-auto" />
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