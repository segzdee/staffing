@php
    $whiteLabelConfig = $whiteLabelConfig ?? null;
    $logoUrl = $whiteLabelConfig?->logo_url ?? config('mail.logo_url', config('app.logo_url'));
    $brandName = $whiteLabelConfig?->brand_name ?? config('app.name');
    $primaryColor = $whiteLabelConfig?->primary_color ?? '#3B82F6';

    // Check for custom email template header
    $customHeader = $whiteLabelConfig?->getEmailTemplate('header');
@endphp

<tr>
    <td class="header">
        <a href="{{ config('app.url') }}" style="display: inline-block;">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" class="logo" alt="{{ $brandName }}" style="max-width: 200px; height: auto;">
            @else
                <span style="font-size: 24px; font-weight: bold; color: {{ $primaryColor }};">{{ $brandName }}</span>
            @endif
        </a>
    </td>
</tr>

@if($customHeader)
<tr>
    <td style="padding: 10px 35px; background-color: {{ $primaryColor }}10;">
        {!! $customHeader !!}
    </td>
</tr>
@endif
