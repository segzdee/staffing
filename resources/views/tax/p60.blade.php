<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>P60 End of Year Certificate {{ $year }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
        }
        .form-container {
            width: 100%;
            max-width: 8.5in;
            margin: 0 auto;
            padding: 0.5in;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #1a5276;
            padding-bottom: 15px;
        }
        .header-logo {
            font-size: 18pt;
            font-weight: bold;
            color: #1a5276;
        }
        .header-title {
            font-size: 16pt;
            font-weight: bold;
            margin-top: 10px;
        }
        .header-subtitle {
            font-size: 12pt;
            color: #666;
        }
        .tax-year-box {
            display: inline-block;
            background: #1a5276;
            color: white;
            padding: 10px 30px;
            font-size: 14pt;
            font-weight: bold;
            margin: 15px 0;
        }
        .section {
            margin-bottom: 20px;
            border: 1px solid #ccc;
        }
        .section-header {
            background: #f5f5f5;
            padding: 8px 12px;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
        }
        .section-body {
            padding: 12px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 40%;
            padding: 6px 0;
            color: #666;
        }
        .info-value {
            display: table-cell;
            padding: 6px 0;
            font-weight: bold;
        }
        .amount-box {
            background: #f9f9f9;
            border: 2px solid #1a5276;
            padding: 15px;
            text-align: center;
            margin: 10px 0;
        }
        .amount-label {
            font-size: 10pt;
            color: #666;
            margin-bottom: 5px;
        }
        .amount-value {
            font-size: 20pt;
            font-weight: bold;
            color: #1a5276;
            font-family: 'Courier New', monospace;
        }
        .two-column {
            display: table;
            width: 100%;
        }
        .column {
            display: table-cell;
            width: 50%;
            padding-right: 10px;
            vertical-align: top;
        }
        .column:last-child {
            padding-right: 0;
            padding-left: 10px;
        }
        .ni-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .ni-table th, .ni-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: right;
        }
        .ni-table th {
            background: #f5f5f5;
            text-align: center;
        }
        .ni-table td:first-child {
            text-align: left;
        }
        .footer-notice {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            font-size: 9pt;
        }
        .generated-notice {
            margin-top: 20px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            font-size: 8pt;
        }
        .official-notice {
            text-align: center;
            font-size: 8pt;
            color: #666;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="header">
            <div class="header-logo">{{ $payer['name'] }}</div>
            <div class="header-title">P60 End of Year Certificate</div>
            <div class="header-subtitle">Employee's Certificate of Pay and Tax Deducted</div>
            <div class="tax-year-box">Tax Year {{ $year }}/{{ $year + 1 }}</div>
        </div>

        <div class="section">
            <div class="section-header">Employee Details</div>
            <div class="section-body">
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Full Name:</div>
                        <div class="info-value">{{ $recipient['name'] }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Address:</div>
                        <div class="info-value">
                            {{ $recipient['address'] }}<br>
                            {{ $recipient['city'] }}, {{ $recipient['state'] }} {{ $recipient['zip'] }}
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">National Insurance Number:</div>
                        <div class="info-value">{{ $recipient['tin'] ?: 'Not Provided' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Employee Reference:</div>
                        <div class="info-value">{{ $user->id }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">Employer Details</div>
            <div class="section-body">
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Employer Name:</div>
                        <div class="info-value">{{ $payer['name'] }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Employer Address:</div>
                        <div class="info-value">
                            {{ $payer['address'] }}<br>
                            {{ $payer['city'] }}, {{ $payer['state'] }} {{ $payer['zip'] }}
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">PAYE Reference:</div>
                        <div class="info-value">{{ $payer['ein'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="two-column">
            <div class="column">
                <div class="amount-box">
                    <div class="amount-label">Total Pay in This Employment</div>
                    <div class="amount-value">&pound;{{ number_format($report->total_earnings, 2) }}</div>
                </div>
            </div>
            <div class="column">
                <div class="amount-box">
                    <div class="amount-label">Total Tax Deducted</div>
                    <div class="amount-value">&pound;{{ number_format($report->total_taxes_withheld, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">National Insurance Contributions</div>
            <div class="section-body">
                <table class="ni-table">
                    <thead>
                        <tr>
                            <th>NI Category</th>
                            <th>Earnings at LEL</th>
                            <th>Earnings at PT</th>
                            <th>Earnings at UEL</th>
                            <th>Employee's NIC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>A</td>
                            <td>&pound;{{ number_format($report->total_earnings, 2) }}</td>
                            <td>&pound;{{ number_format($report->total_earnings, 2) }}</td>
                            <td>&pound;{{ number_format($report->total_earnings, 2) }}</td>
                            @php
                                $niContributions = collect($report->jurisdiction_breakdown ?? [])->sum('social_security');
                            @endphp
                            <td>&pound;{{ number_format($niContributions, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="section-header">Summary of Earnings</div>
            <div class="section-body">
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Total Shifts Worked:</div>
                        <div class="info-value">{{ $report->total_shifts }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Gross Earnings:</div>
                        <div class="info-value">&pound;{{ number_format($report->total_earnings, 2) }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Platform Fees Deducted:</div>
                        <div class="info-value">&pound;{{ number_format($report->total_fees, 2) }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tax Deducted:</div>
                        <div class="info-value">&pound;{{ number_format($report->total_taxes_withheld, 2) }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Net Earnings:</div>
                        <div class="info-value">&pound;{{ number_format($report->net_earnings, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-notice">
            <strong>Important:</strong> Keep this certificate safe. You will need it if you have to fill in a tax return.
            It also helps you check that your employer is using the right tax code. The figures shown on this certificate
            include any Statutory Sick Pay, Statutory Maternity Pay, Statutory Paternity Pay, Statutory Adoption Pay,
            or Statutory Shared Parental Pay paid by your employer.
        </div>

        <div class="generated-notice">
            <strong>This document was electronically generated by {{ config('app.name') }}</strong><br>
            Generated on: {{ $generatedAt->format('j F Y \a\t H:i') }}<br>
            Tax Year: {{ $year }}/{{ $year + 1 }}<br>
            Report ID: {{ $report->id }}
        </div>

        <div class="official-notice">
            This P60 has been issued in accordance with HMRC requirements.<br>
            For queries, please contact {{ config('app.name') }} support.
        </div>
    </div>
</body>
</html>
