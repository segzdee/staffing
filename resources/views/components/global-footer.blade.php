@props(['class' => '', 'showCta' => true])

{{-- CTA Banner Section --}}
@if($showCta)
<section class="bg-[#2563eb] text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">
            One shift. See the difference.
        </h2>
        <p class="text-lg text-blue-100 mb-8">
            Join thousands of businesses and workers on the global shift marketplace.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ route('business.post-shifts') }}" class="px-8 py-4 text-base font-medium bg-white text-gray-900 rounded-lg hover:bg-gray-100 transition-colors">
                Post Shifts
            </a>
            <a href="{{ route('workers.find-shifts') }}" class="px-8 py-4 text-base font-medium border-2 border-white text-white bg-transparent rounded-lg hover:bg-white/10 transition-colors">
                Find Shifts
            </a>
        </div>
    </div>
</section>
@endif

<footer class="bg-[#0f172a] text-[#94a3b8] {{ $class }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Main Footer Content --}}
        <div class="py-12 lg:py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-12">
                {{-- Logo & Description --}}
                <div class="lg:col-span-2">
                    <a href="{{ url('/') }}" class="flex items-center gap-2 mb-4">
                        {{-- Logo Icon --}}
                        <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-white">OVERTIMESTAFF</span>
                    </a>
                    <p class="text-[#94a3b8] text-sm leading-relaxed mb-6 max-w-sm">
                        The global shift marketplace connecting businesses with verified, reliable workers. Available 24/7 in over 70 countries.
                    </p>

                    {{-- Social Media Icons --}}
                    <div class="flex items-center gap-3">
                        <a href="#" class="w-10 h-10 rounded-full border border-[#1e293b] bg-transparent hover:bg-[#1e293b] flex items-center justify-center transition-colors" aria-label="Twitter">
                            <svg class="w-5 h-5 text-[#94a3b8]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full border border-[#1e293b] bg-transparent hover:bg-[#1e293b] flex items-center justify-center transition-colors" aria-label="LinkedIn">
                            <svg class="w-5 h-5 text-[#94a3b8]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full border border-[#1e293b] bg-transparent hover:bg-[#1e293b] flex items-center justify-center transition-colors" aria-label="Instagram">
                            <svg class="w-5 h-5 text-[#94a3b8]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full border border-[#1e293b] bg-transparent hover:bg-[#1e293b] flex items-center justify-center transition-colors" aria-label="Facebook">
                            <svg class="w-5 h-5 text-[#94a3b8]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                {{-- Workers Links --}}
                <div>
                    <h4 class="text-white font-semibold mb-4">Workers</h4>
                    <ul class="space-y-3">
                        <li><a href="{{ route('workers.find-shifts') }}" class="text-sm text-[#94a3b8] hover:text-white transition-colors">Find Shifts</a></li>
                        <li><a href="{{ route('workers.features') }}" class="text-sm text-[#94a3b8] hover:text-white transition-colors">Features</a></li>
                        <li><a href="{{ route('register', ['type' => 'worker']) }}" class="text-sm text-[#94a3b8] hover:text-white transition-colors">Register</a></li>
                        <li><a href="{{ route('login') }}" class="text-sm text-[#94a3b8] hover:text-white transition-colors">Worker Login</a></li>
                    </ul>
                </div>

                {{-- Businesses Links --}}
                <div>
                    <h4 class="text-white font-semibold mb-4">Businesses</h4>
                    <ul class="space-y-3">
                        <li><a href="{{ route('business.post-shifts') }}" class="text-sm text-[#94a3b8] hover:text-white transition-colors">Post Shifts</a></li>
                        <li><a href="{{ route('business.pricing') }}" class="text-sm text-[#94a3b8] hover:text-white transition-colors">Pricing</a></li>
                        <li><a href="{{ route('business.post-shifts') }}" class="text-sm text-[#94a3b8] hover:text-white transition-colors">Post Shifts</a></li>
                        <li><a href="{{ route('login') }}" class="text-sm text-[#94a3b8] hover:text-white transition-colors">Business Login</a></li>
                    </ul>
                </div>

                {{-- Company Links --}}
                <div>
                    <h4 class="text-white font-semibold mb-4">Company</h4>
                    <ul class="space-y-3">
                        <li><a href="{{ route('about') }}" class="text-sm text-[#94a3b8] hover:text-white transition-colors">About Us</a></li>
                        <li><a href="{{ route('contact') }}" class="text-sm text-[#94a3b8] hover:text-white transition-colors">Contact</a></li>
                        <li><a href="{{ route('terms') }}" class="text-sm text-[#94a3b8] hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="{{ route('privacy') }}" class="text-sm text-[#94a3b8] hover:text-white transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- App Store Badges --}}
        <div class="py-8 border-t border-[#1e293b]">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="text-sm text-[#94a3b8]">
                    Download our app for the best experience
                </div>
                <div class="flex items-center gap-4">
                    <a href="#" class="inline-block">
                        <img src="{{ asset('images/app-store-badge.svg') }}" alt="Download on the App Store" class="h-10" onerror="this.style.display='none'">
                    </a>
                    <a href="#" class="inline-block">
                        <img src="{{ asset('images/google-play-badge.svg') }}" alt="Get it on Google Play" class="h-10" onerror="this.style.display='none'">
                    </a>
                </div>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="py-6 border-t border-[#1e293b]">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-sm text-[#64748b]">
                    &copy; 2025 OvertimeStaff. All rights reserved.
                </div>
                <div class="flex items-center gap-2 text-sm text-[#94a3b8]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span>support@overtimestaff.com</span>
                </div>
            </div>
        </div>
    </div>
</footer>
