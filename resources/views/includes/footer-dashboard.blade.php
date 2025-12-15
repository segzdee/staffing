{{-- Simplified Dashboard Footer --}}
<footer class="border-t border-gray-200 py-6 mt-12">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <p class="text-sm text-gray-500 mb-2 mb-md-0">
                        © {{ date('Y') }} {{ $settings->title ?? 'OvertimeStaff' }}. All rights reserved.
                    </p>
                    <div class="d-flex gap-4">
                        <a href="{{ url('contact') }}" class="text-sm text-gray-500 hover:text-gray-700">Contact</a>
                        <a href="{{ url('p/terms') }}" class="text-sm text-gray-500 hover:text-gray-700">Terms</a>
                        <a href="{{ url('p/privacy') }}" class="text-sm text-gray-500 hover:text-gray-700">Privacy</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
