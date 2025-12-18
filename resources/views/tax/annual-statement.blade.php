<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Annual Earnings Statement {{ $year }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #333;
        }
        .container {
            width: 100%;
            max-width: 8.5in;
            margin: 0 auto;
            padding: 0.5in;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
        }
        .logo {
            font-size: 24pt;
            font-weight: bold;
            color: #2c3e50;
        }
        .document-title {
            font-size: 18pt;
            font-weight: bold;
            margin-top: 10px;
            color: #34495e;
        }
        .tax-year {
            font-size: 14pt;
            color: #7f8c8d;
            margin-top: 5px;
        }
        .recipient-box {
            background: #ecf0f1;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        .recipient-name {
            font-size: 14pt;
            font-weight: bold;
            color: #2c3e50;
        }
        .recipient-details {
            color: #7f8c8d;
            margin-top: 5px;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .summary-box {
            display: table-cell;
            width: 25%;
            padding: 15px;
            text-align: center;
            background: #fff;
            border: 1px solid #ddd;
        }
        .summary-box:first-child {
            border-radius: 5px 0 0 5px;
        }
        .summary-box:last-child {
            border-radius: 0 5px 5px 0;
        }
        .summary-label {
            font-size: 9pt;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        .summary-value {
            font-size: 16pt;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 5px;
        }
        .summary-value.positive {
            color: #27ae60;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #2c3e50;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 15px;
        }
        .earnings-table {
            width: 100%;
            border-collapse: collapse;
        }
        .earnings-table th {
            background: #2c3e50;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 9pt;
            text-transform: uppercase;
        }
        .earnings-table th:last-child,
        .earnings-table td:last-child {
            text-align: right;
        }
        .earnings-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .earnings-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .earnings-table .total-row {
            font-weight: bold;
            background: #ecf0f1;
        }
        .monthly-chart {
            margin-top: 15px;
        }
        .chart-bar {
            height: 25px;
            background: #ecf0f1;
            margin-bottom: 8px;
            position: relative;
        }
        .chart-fill {
            height: 100%;
            background: #3498db;
            position: absolute;
            left: 0;
            top: 0;
        }
        .chart-label {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 9pt;
            color: #2c3e50;
            z-index: 1;
        }
        .chart-value {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 9pt;
            color: #2c3e50;
            z-index: 1;
        }
        .jurisdiction-list {
            margin-top: 10px;
        }
        .jurisdiction-item {
            display: table;
            width: 100%;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .jurisdiction-name {
            display: table-cell;
            width: 50%;
        }
        .jurisdiction-amount {
            display: table-cell;
            text-align: right;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 9pt;
            color: #7f8c8d;
        }
        .footer-grid {
            display: table;
            width: 100%;
        }
        .footer-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .disclaimer {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            font-size: 9pt;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
            <div class="document-title">Annual Earnings Statement</div>
            <div class="tax-year">Tax Year {{ $year }}</div>
        </div>

        <div class="recipient-box">
            <div class="recipient-name">{{ $recipient['name'] }}</div>
            <div class="recipient-details">
                {{ $recipient['address'] }}<br>
                {{ $recipient['city'] }}, {{ $recipient['state'] }} {{ $recipient['zip'] }}<br>
                {{ $recipient['country'] }}
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-box">
                <div class="summary-label">Total Earnings</div>
                <div class="summary-value">${{ number_format($report->total_earnings, 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-label">Platform Fees</div>
                <div class="summary-value">${{ number_format($report->total_fees, 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-label">Taxes Withheld</div>
                <div class="summary-value">${{ number_format($report->total_taxes_withheld, 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-label">Net Earnings</div>
                <div class="summary-value positive">${{ number_format($report->net_earnings, 2) }}</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Earnings Summary</div>
            <table class="earnings-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Gross Earnings from {{ $report->total_shifts }} Shifts</td>
                        <td>${{ number_format($report->total_earnings, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Platform Service Fees</td>
                        <td>-${{ number_format($report->total_fees, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Taxes Withheld</td>
                        <td>-${{ number_format($report->total_taxes_withheld, 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>Net Earnings</td>
                        <td>${{ number_format($report->net_earnings, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Monthly Breakdown</div>
            @php
                $maxEarnings = collect($report->monthly_breakdown ?? [])->max('gross') ?: 1;
            @endphp
            <div class="monthly-chart">
                @foreach($report->monthly_breakdown ?? [] as $month)
                    @php
                        $percentage = ($month['gross'] / $maxEarnings) * 100;
                    @endphp
                    <div class="chart-bar">
                        <div class="chart-fill" style="width: {{ $percentage }}%;"></div>
                        <span class="chart-label">{{ $month['month_name'] }} ({{ $month['shifts'] }} shifts)</span>
                        <span class="chart-value">${{ number_format($month['gross'], 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        @if(!empty($report->jurisdiction_breakdown))
        <div class="section">
            <div class="section-title">Tax Withholdings by Jurisdiction</div>
            <div class="jurisdiction-list">
                @foreach($report->jurisdiction_breakdown ?? [] as $jurisdiction)
                    <div class="jurisdiction-item">
                        <span class="jurisdiction-name">
                            {{ $jurisdiction['jurisdiction_name'] }}
                            @if(isset($jurisdiction['country_code']))
                                ({{ $jurisdiction['country_code'] }})
                            @endif
                        </span>
                        <span class="jurisdiction-amount">${{ number_format($jurisdiction['total_withheld'] ?? 0, 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="disclaimer">
            <strong>Important Tax Information:</strong><br>
            This statement is provided for your records and tax filing purposes. The earnings shown represent payments
            made to you through the {{ config('app.name') }} platform during the {{ $year }} tax year. Depending on your
            location and tax status, you may also receive additional tax forms (such as Form 1099-NEC for US workers).
            Consult with a qualified tax professional regarding your specific tax obligations.
        </div>

        <div class="footer">
            <div class="footer-grid">
                <div class="footer-col">
                    <strong>Document Details</strong><br>
                    Report ID: {{ $report->id }}<br>
                    Generated: {{ $generatedAt->format('F j, Y \a\t g:i A') }}<br>
                    Tax Year: {{ $year }}
                </div>
                <div class="footer-col" style="text-align: right;">
                    <strong>{{ $payer['name'] }}</strong><br>
                    {{ $payer['address'] }}<br>
                    {{ $payer['city'] }}, {{ $payer['state'] }} {{ $payer['zip'] }}<br>
                    {{ $payer['phone'] }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
