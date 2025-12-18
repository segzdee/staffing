@php
    $config = $whiteLabelConfig ?? null;
    $name = $brandName ?? ($config->brand_name ?? config('app.name'));
    $supportEmail = $supportEmail ?? ($config->support_email ?? config('mail.from.address'));
    $supportPhone = $supportPhone ?? ($config->support_phone ?? null);
    $hidePoweredBy = $hidePoweredBy ?? ($config->hide_powered_by ?? false);
    $primaryColor = $brandPrimaryColor ?? ($config->primary_color ?? '#3B82F6');
@endphp

<footer class="bg-gray-900 text-gray-300" style="{{ $cssVariables ?? '' }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            {{-- Brand Column --}}
            <div class="col-span-1 md:col-span-2">
                <h3 class="text-white text-xl font-bold mb-4">{{ $name }}</h3>
                <p class="text-gray-400 mb-4 max-w-md">
                    Your trusted partner for shift staffing solutions. Connect with qualified workers and fill your shifts efficiently.
                </p>
                @if($supportEmail || $supportPhone)
                    <div class="space-y-2">
                        @if($supportEmail)
                            <a href="mailto:{{ $supportEmail }}" class="flex items-center gap-2 text-gray-400 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                {{ $supportEmail }}
                            </a>
                        @endif
                        @if($supportPhone)
                            <a href="tel:{{ $supportPhone }}" class="flex items-center gap-2 text-gray-400 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ $supportPhone }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Quick Links --}}
            <div>
                <h4 class="text-white font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('home') }}" class="text-gray-400 hover:text-white transition-colors">Home</a>
                    </li>
                    @guest
                        <li>
                            <a href="{{ route('login') }}" class="text-gray-400 hover:text-white transition-colors">Sign In</a>
                        </li>
                        <li>
                            <a href="{{ route('register') }}" class="text-gray-400 hover:text-white transition-colors">Register</a>
                        </li>
                    @else
                        <li>
                            <a href="{{ auth()->user()->dashboard_route }}" class="text-gray-400 hover:text-white transition-colors">Dashboard</a>
                        </li>
                    @endguest
                </ul>
            </div>

            {{-- Legal --}}
            <div>
                <h4 class="text-white font-semibold mb-4">Legal</h4>
                <ul class="space-y-2">
                    @if(Route::has('privacy'))
                        <li>
                            <a href="{{ route('privacy') }}" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a>
                        </li>
                    @endif
                    @if(Route::has('terms'))
                        <li>
                            <a href="{{ route('terms') }}" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-gray-500 text-sm">
                &copy; {{ date('Y') }} {{ $name }}. All rights reserved.
            </p>

            @if(!$hidePoweredBy)
                <p class="text-gray-500 text-sm">
                    Powered by <a href="https://overtimestaff.com" target="_blank" rel="noopener" class="hover:text-white transition-colors" style="color: {{ $primaryColor }}">OvertimeStaff</a>
                </p>
            @endif
        </div>
    </div>
</footer>
