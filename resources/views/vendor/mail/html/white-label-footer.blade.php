@php
    $whiteLabelConfig = $whiteLabelConfig ?? null;
    $brandName = $whiteLabelConfig?->brand_name ?? config('app.name');
    $supportEmail = $whiteLabelConfig?->support_email ?? config('mail.from.address');
    $supportPhone = $whiteLabelConfig?->support_phone ?? null;
    $hidePoweredBy = $whiteLabelConfig?->hide_powered_by ?? false;
    $primaryColor = $whiteLabelConfig?->primary_color ?? '#3B82F6';

    // Check for custom email template footer
    $customFooter = $whiteLabelConfig?->getEmailTemplate('footer');
@endphp

<tr>
    <td>
        <table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td class="content-cell" align="center">
                    @if($customFooter)
                        {!! $customFooter !!}
                    @else
                        <p style="color: #718096; font-size: 12px; margin-bottom: 10px;">
                            &copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.
                        </p>

                        @if($supportEmail || $supportPhone)
                            <p style="color: #718096; font-size: 12px; margin-bottom: 10px;">
                                Need help?
                                @if($supportEmail)
                                    <a href="mailto:{{ $supportEmail }}" style="color: {{ $primaryColor }};">{{ $supportEmail }}</a>
                                @endif
                                @if($supportPhone)
                                    @if($supportEmail) | @endif
                                    <a href="tel:{{ $supportPhone }}" style="color: {{ $primaryColor }};">{{ $supportPhone }}</a>
                                @endif
                            </p>
                        @endif

                        @if(!$hidePoweredBy)
                            <p style="color: #a0aec0; font-size: 11px;">
                                Powered by <a href="https://overtimestaff.com" style="color: {{ $primaryColor }};">OvertimeStaff</a>
                            </p>
                        @endif
                    @endif
                </td>
            </tr>
        </table>
    </td>
</tr>
