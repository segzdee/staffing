{{--
    Mobile-Optimized Modal Component

    Usage:
    <x-ui.modal id="myModal" title="Modal Title" size="lg">
        <x-slot:body>Modal content here</x-slot:body>
        <x-slot:footer>
            <button type="button" class="..." data-dismiss="modal">Cancel</button>
            <button type="submit" class="...">Submit</button>
        </x-slot:footer>
    </x-ui.modal>

    Props:
    - id: (required) Modal ID for targeting
    - title: Modal header title
    - size: sm, md, lg, xl (default: md)
    - scrollable: Enable scrollable body (default: true)
    - centered: Vertically center (default: true)
    - static: Disable close on backdrop click (default: false)
--}}

@props([
    'id',
    'title' => '',
    'size' => 'md',
    'scrollable' => true,
    'centered' => true,
    'static' => false,
])

@php
    $sizeClasses = [
        'sm' => 'modal-sm',
        'md' => '',
        'lg' => 'modal-lg',
        'xl' => 'modal-xl',
    ];
    $modalSize = $sizeClasses[$size] ?? '';
@endphp

<div
    class="modal fade"
    id="{{ $id }}"
    tabindex="-1"
    role="dialog"
    aria-labelledby="{{ $id }}Label"
    aria-hidden="true"
    @if($static) data-backdrop="static" data-keyboard="false" @endif
>
    <div class="modal-dialog {{ $modalSize }} {{ $scrollable ? 'modal-dialog-scrollable' : '' }} {{ $centered ? 'modal-dialog-centered' : '' }}" role="document">
        <div class="modal-content max-h-[100vh] sm:max-h-[90vh] flex flex-col rounded-none sm:rounded-lg overflow-hidden">
            {{-- Header --}}
            @if($title || isset($header))
            <div class="modal-header flex-shrink-0 px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                @if(isset($header))
                    {{ $header }}
                @else
                    <h5 class="modal-title text-lg font-semibold text-gray-900 dark:text-white pr-8" id="{{ $id }}Label">
                        {{ $title }}
                    </h5>
                @endif
                <button
                    type="button"
                    class="close min-h-[44px] min-w-[44px] sm:min-h-[36px] sm:min-w-[36px] flex items-center justify-center text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 active:text-gray-600 touch-manipulation rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 -mr-2 transition-colors"
                    data-dismiss="modal"
                    aria-label="Close"
                >
                    <span aria-hidden="true" class="text-2xl leading-none">&times;</span>
                </button>
            </div>
            @endif

            {{-- Body --}}
            <div class="modal-body flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-6 bg-white dark:bg-gray-800">
                @if(isset($body))
                    {{ $body }}
                @else
                    {{ $slot }}
                @endif
            </div>

            {{-- Footer --}}
            @if(isset($footer))
            <div class="modal-footer flex-shrink-0 px-4 py-3 sm:px-6 sm:py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 justify-end pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:pb-4">
                {{ $footer }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Mobile optimization styles for this modal --}}
@once
@push('css')
<style>
/* Mobile-first modal optimizations */
@media (max-width: 575.98px) {
    .modal-dialog {
        margin: 0;
        max-width: 100%;
        min-height: 100vh;
        min-height: 100dvh;
    }

    .modal-content {
        min-height: 100vh;
        min-height: 100dvh;
        border-radius: 0 !important;
    }

    .modal-dialog-centered .modal-content {
        min-height: auto;
    }

    .modal-dialog-scrollable .modal-body {
        max-height: calc(100vh - 140px);
        max-height: calc(100dvh - 140px);
    }

    /* Safe area insets for notched devices */
    .modal-header {
        padding-top: max(0.75rem, env(safe-area-inset-top));
    }

    .modal-footer {
        padding-bottom: max(0.75rem, env(safe-area-inset-bottom));
    }
}

/* Touch-friendly targets */
.modal .btn,
.modal button[type="button"],
.modal button[type="submit"],
.modal .form-control,
.modal .form-select,
.modal select.form-control {
    min-height: 44px;
}

@media (min-width: 576px) {
    .modal .btn,
    .modal button[type="button"],
    .modal button[type="submit"],
    .modal .form-control,
    .modal .form-select,
    .modal select.form-control {
        min-height: 40px;
    }
}

/* Ensure modal backdrop covers notch area */
.modal-backdrop {
    position: fixed;
    inset: 0;
}

/* Better scrolling on mobile */
.modal-body {
    -webkit-overflow-scrolling: touch;
}

/* Prevent body scroll when modal is open */
body.modal-open {
    overflow: hidden;
    position: fixed;
    width: 100%;
}
</style>
@endpush
@endonce
