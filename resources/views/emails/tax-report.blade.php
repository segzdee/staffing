@component('mail::message')
# Your {{ $report->report_type_name }} is Ready

Dear {{ $user->name }},

Your {{ $report->report_type_name }} for tax year **{{ $report->tax_year }}** has been generated and is attached to this email.

## Summary

| Description | Amount |
|:------------|-------:|
| Total Earnings | ${{ number_format($report->total_earnings, 2) }} |
| Platform Fees | ${{ number_format($report->total_fees, 2) }} |
| Taxes Withheld | ${{ number_format($report->total_taxes_withheld, 2) }} |
| **Net Earnings** | **${{ number_format($report->net_earnings, 2) }}** |

**Total Shifts Completed:** {{ $report->total_shifts }}

## Important Information

@if($report->report_type === '1099_nec')
This Form 1099-NEC reports nonemployee compensation paid to you during the tax year. You should include this income when filing your tax return.

If you are required to file a tax return, you must report this income even if you did not receive a Form 1099-NEC.
@elseif($report->report_type === 'p60')
This P60 is your End of Year Certificate showing the total pay and tax deducted for the tax year. Please keep this document safe as you may need it:

- To fill in a tax return
- As proof of income for mortgage or loan applications
- To check your employer is using the correct tax code
@else
This annual statement summarizes all earnings and deductions from your work through {{ config('app.name') }} during the {{ $report->tax_year }} tax year.
@endif

## Need Help?

If you have any questions about your tax documents or need assistance, please contact our support team.

@component('mail::button', ['url' => route('worker.tax-reports.show', $report)])
View Your Tax Report
@endcomponent

Thank you for being a valued member of {{ config('app.name') }}.

Best regards,<br>
{{ config('app.name') }}

---

<small>This is an automated message. Please do not reply directly to this email. The attached document contains sensitive tax information - please store it securely.</small>
@endcomponent
