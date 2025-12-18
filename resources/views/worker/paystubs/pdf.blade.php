<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Paystub - {{ $payrollRun->reference }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            padding: 20px;
        }
        .header {
            background: linear-gradient(to right, #4f46e5, #6366f1);
            color: white;
            padding: 20px;
            margin: -20px -20px 20px -20px;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .header-left {
            display: table-cell;
            vertical-align: middle;
        }
        .header-right {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
        }
        .pay-date {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
        }
        .net-pay-label {
            font-size: 10px;
            opacity: 0.8;
        }
        .net-pay-amount {
            font-size: 32px;
            font-weight: bold;
        }
        .info-section {
            background: #f9fafb;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
        }
        .info-row {
            display: table;
            width: 100%;
        }
        .info-col {
            display: table-cell;
            width: 50%;
        }
        .info-col.right {
            text-align: right;
        }
        .label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .value {
            font-weight: 600;
            color: #111827;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            text-align: left;
            padding: 8px;
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }
        th.right {
            text-align: right;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
        }
        td.right {
            text-align: right;
        }
        .type-badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: 600;
            border-radius: 3px;
        }
        .type-regular {
            background: #dbeafe;
            color: #1e40af;
        }
        .type-overtime {
            background: #e9d5ff;
            color: #6b21a8;
        }
        .type-bonus {
            background: #d1fae5;
            color: #065f46;
        }
        .type-adjustment {
            background: #fef3c7;
            color: #92400e;
        }
        .type-reimbursement {
            background: #f3f4f6;
            color: #374151;
        }
        .shift-info {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }
        .totals-row td {
            font-weight: bold;
            border-top: 2px solid #e5e7eb;
            border-bottom: none;
        }
        .deductions-section {
            background: #f9fafb;
            padding: 15px;
            margin-bottom: 20px;
        }
        .deduction-row {
            display: table;
            width: 100%;
            padding: 5px 0;
        }
        .deduction-label {
            display: table-cell;
            color: #374151;
        }
        .deduction-amount {
            display: table-cell;
            text-align: right;
            color: #dc2626;
        }
        .net-pay-section {
            background: #f0fdf4;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
        }
        .net-pay-row {
            display: table;
            width: 100%;
        }
        .net-pay-left {
            display: table-cell;
            font-size: 16px;
            font-weight: bold;
            color: #111827;
        }
        .net-pay-right {
            display: table-cell;
            text-align: right;
            font-size: 24px;
            font-weight: bold;
            color: #16a34a;
        }
        .ytd-section {
            background: #f3f4f6;
            padding: 15px;
        }
        .ytd-grid {
            display: table;
            width: 100%;
        }
        .ytd-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
        }
        .ytd-value {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
        }
        .ytd-value.negative {
            color: #dc2626;
        }
        .ytd-value.positive {
            color: #16a34a;
        }
        .ytd-label {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    <div class="company-name">{{ config('app.name', 'OvertimeStaff') }}</div>
                    <div class="pay-date">Pay Date: {{ $paystub['payroll_run']['pay_date'] }}</div>
                </div>
                <div class="header-right">
                    <div class="net-pay-label">Net Pay</div>
                    <div class="net-pay-amount">${{ number_format($paystub['totals']['net'], 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Employee Info -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-col">
                    <div class="label">Employee</div>
                    <div class="value">{{ $paystub['worker']['name'] }}</div>
                    <div style="font-size: 11px; color: #6b7280;">{{ $paystub['worker']['email'] }}</div>
                </div>
                <div class="info-col right">
                    <div class="label">Pay Period</div>
                    <div class="value">{{ $paystub['payroll_run']['period_start'] }} - {{ $paystub['payroll_run']['period_end'] }}</div>
                    <div style="font-size: 11px; color: #6b7280;">Reference: {{ $paystub['payroll_run']['reference'] }}</div>
                </div>
            </div>
        </div>

        <!-- Earnings -->
        <div class="section-title">Earnings</div>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right">Hours</th>
                    <th class="right">Rate</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($paystub['earnings'] as $earning)
                <tr>
                    <td>
                        <span class="type-badge type-{{ $earning['type'] }}">{{ $earning['type_label'] }}</span>
                        {{ $earning['description'] }}
                        @if($earning['shift'])
                        <div class="shift-info">{{ $earning['shift']['title'] }} - {{ $earning['shift']['date'] }}</div>
                        @endif
                    </td>
                    <td class="right">{{ number_format($earning['hours'], 2) }}</td>
                    <td class="right">${{ number_format($earning['rate'], 2) }}/hr</td>
                    <td class="right">${{ number_format($earning['gross_amount'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="3" style="text-align: right;">Gross Earnings</td>
                    <td class="right">${{ number_format($paystub['totals']['gross'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Deductions -->
        <div class="deductions-section">
            <div class="section-title" style="margin-bottom: 10px;">Deductions</div>
            @if($paystub['deductions']['platform_fee'] > 0)
            <div class="deduction-row">
                <div class="deduction-label">Platform Fee</div>
                <div class="deduction-amount">-${{ number_format($paystub['deductions']['platform_fee'], 2) }}</div>
            </div>
            @endif
            @if($paystub['deductions']['tax'] > 0)
            <div class="deduction-row">
                <div class="deduction-label">Tax Withholding</div>
                <div class="deduction-amount">-${{ number_format($paystub['deductions']['tax'], 2) }}</div>
            </div>
            @endif
            @if($paystub['deductions']['garnishment'] > 0)
            <div class="deduction-row">
                <div class="deduction-label">Garnishment</div>
                <div class="deduction-amount">-${{ number_format($paystub['deductions']['garnishment'], 2) }}</div>
            </div>
            @endif
            @if($paystub['deductions']['advance_repayment'] > 0)
            <div class="deduction-row">
                <div class="deduction-label">Advance Repayment</div>
                <div class="deduction-amount">-${{ number_format($paystub['deductions']['advance_repayment'], 2) }}</div>
            </div>
            @endif
            @if($paystub['deductions']['other'] > 0)
            <div class="deduction-row">
                <div class="deduction-label">Other Deductions</div>
                <div class="deduction-amount">-${{ number_format($paystub['deductions']['other'], 2) }}</div>
            </div>
            @endif
            <div class="deduction-row" style="border-top: 1px solid #d1d5db; padding-top: 8px; margin-top: 5px;">
                <div class="deduction-label" style="font-weight: bold;">Total Deductions</div>
                <div class="deduction-amount" style="font-weight: bold;">-${{ number_format($paystub['totals']['deductions'] + $paystub['totals']['taxes'], 2) }}</div>
            </div>
        </div>

        <!-- Net Pay -->
        <div class="net-pay-section">
            <div class="net-pay-row">
                <div class="net-pay-left">Net Pay</div>
                <div class="net-pay-right">${{ number_format($paystub['totals']['net'], 2) }}</div>
            </div>
        </div>

        <!-- YTD Summary -->
        <div class="ytd-section">
            <div class="section-title" style="margin-bottom: 10px;">Year-to-Date Summary ({{ $payrollRun->pay_date->year }})</div>
            <div class="ytd-grid">
                <div class="ytd-item">
                    <div class="ytd-value">${{ number_format($ytdTotals['gross'], 2) }}</div>
                    <div class="ytd-label">YTD Gross</div>
                </div>
                <div class="ytd-item">
                    <div class="ytd-value negative">-${{ number_format($ytdTotals['deductions'], 2) }}</div>
                    <div class="ytd-label">YTD Deductions</div>
                </div>
                <div class="ytd-item">
                    <div class="ytd-value negative">-${{ number_format($ytdTotals['taxes'], 2) }}</div>
                    <div class="ytd-label">YTD Taxes</div>
                </div>
                <div class="ytd-item">
                    <div class="ytd-value positive">${{ number_format($ytdTotals['net'], 2) }}</div>
                    <div class="ytd-label">YTD Net</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an official paystub generated by {{ config('app.name', 'OvertimeStaff') }}</p>
            <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
            <p>Reference: {{ $payrollRun->reference }} | Worker ID: {{ $worker->id }}</p>
        </div>
    </div>
</body>
</html>
