<!-- Uniform Logo Component -->
@php
    $isDark = isset($dark) && $dark;
    $logoPath = $isDark ? '/images/logo-dark.svg' : '/images/logo.svg';
@endphp
<img src="{{ $logoPath }}" alt="OvertimeStaff" class="h-8">
