<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Form 1099-NEC {{ $year }}</title>
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
        .form-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .form-title {
            font-size: 14pt;
            font-weight: bold;
        }
        .form-subtitle {
            font-size: 10pt;
            color: #333;
        }
        .form-year {
            font-size: 24pt;
            font-weight: bold;
            color: #c00;
        }
        .form-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .form-row {
            display: table-row;
        }
        .form-cell {
            display: table-cell;
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }
        .form-cell-half {
            width: 50%;
        }
        .form-cell-third {
            width: 33.33%;
        }
        .form-cell-quarter {
            width: 25%;
        }
        .box-label {
            font-size: 8pt;
            color: #666;
            margin-bottom: 4px;
        }
        .box-number {
            font-weight: bold;
            font-size: 9pt;
        }
        .box-value {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 4px;
        }
        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        .section-title {
            background: #eee;
            padding: 5px 8px;
            font-weight: bold;
            font-size: 9pt;
            border: 1px solid #000;
            border-bottom: none;
        }
        .payer-info, .recipient-info {
            min-height: 100px;
        }
        .instructions {
            margin-top: 20px;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        .copy-label {
            position: absolute;
            top: 0.25in;
            right: 0.5in;
            font-size: 10pt;
            color: #c00;
            font-weight: bold;
        }
        .void-checkbox {
            margin-right: 20px;
        }
        .corrected-checkbox {
            margin-left: 20px;
        }
        .checkbox-row {
            text-align: center;
            padding: 5px;
            border: 1px solid #000;
            border-bottom: none;
        }
        .footer {
            margin-top: 30px;
            font-size: 8pt;
            text-align: center;
            color: #666;
        }
        .generated-notice {
            margin-top: 20px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            font-size: 8pt;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <div class="form-year">{{ $year }}</div>
            <div class="form-title">Form 1099-NEC</div>
            <div class="form-subtitle">Nonemployee Compensation</div>
            <div class="form-subtitle">Copy B - For Recipient</div>
        </div>

        <div class="checkbox-row">
            <span class="void-checkbox">[ ] VOID</span>
            <span class="corrected-checkbox">[ ] CORRECTED</span>
        </div>

        <div class="form-grid">
            <!-- Payer Information -->
            <div class="form-row">
                <div class="form-cell form-cell-half payer-info">
                    <div class="box-label">PAYER'S name, street address, city or town, state or province, country, ZIP or foreign postal code, and telephone no.</div>
                    <div class="box-value">
                        {{ $payer['name'] }}<br>
                        {{ $payer['address'] }}<br>
                        {{ $payer['city'] }}, {{ $payer['state'] }} {{ $payer['zip'] }}<br>
                        {{ $payer['country'] }}<br>
                        {{ $payer['phone'] }}
                    </div>
                </div>
                <div class="form-cell form-cell-quarter">
                    <div class="box-label"><span class="box-number">1</span> Nonemployee compensation</div>
                    <div class="box-value amount">${{ number_format($report->total_earnings, 2) }}</div>
                </div>
                <div class="form-cell form-cell-quarter">
                    <div class="box-label"><span class="box-number">2</span> Payer made direct sales totaling $5,000 or more of consumer products to recipient for resale</div>
                    <div class="box-value">[ ]</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-cell form-cell-half">
                    <div class="box-label">PAYER'S TIN</div>
                    <div class="box-value">{{ $payer['ein'] }}</div>
                </div>
                <div class="form-cell form-cell-quarter">
                    <div class="box-label"><span class="box-number">3</span> (Reserved)</div>
                    <div class="box-value"></div>
                </div>
                <div class="form-cell form-cell-quarter">
                    <div class="box-label"><span class="box-number">4</span> Federal income tax withheld</div>
                    <div class="box-value amount">${{ number_format($report->total_taxes_withheld, 2) }}</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-cell form-cell-half">
                    <div class="box-label">RECIPIENT'S TIN</div>
                    <div class="box-value">***-**-{{ $recipient['ssn_last4'] }}</div>
                </div>
                <div class="form-cell form-cell-quarter">
                    <div class="box-label"><span class="box-number">5</span> State tax withheld</div>
                    <div class="box-value amount">
                        @php
                            $stateTax = collect($report->jurisdiction_breakdown ?? [])->sum('state');
                        @endphp
                        ${{ number_format($stateTax, 2) }}
                    </div>
                </div>
                <div class="form-cell form-cell-quarter">
                    <div class="box-label"><span class="box-number">6</span> State/Payer's state no.</div>
                    <div class="box-value">{{ $recipient['state'] }}</div>
                </div>
            </div>

            <!-- Recipient Information -->
            <div class="form-row">
                <div class="form-cell form-cell-half recipient-info">
                    <div class="box-label">RECIPIENT'S name</div>
                    <div class="box-value">{{ $recipient['name'] }}</div>
                </div>
                <div class="form-cell form-cell-quarter">
                    <div class="box-label"><span class="box-number">7</span> State income</div>
                    <div class="box-value amount">${{ number_format($report->total_earnings, 2) }}</div>
                </div>
                <div class="form-cell form-cell-quarter">
                    <div class="box-label">Account number (see instructions)</div>
                    <div class="box-value">{{ $user->id }}</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-cell" style="width: 100%;">
                    <div class="box-label">Street address (including apt. no.)</div>
                    <div class="box-value">{{ $recipient['address'] }}</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-cell" style="width: 100%;">
                    <div class="box-label">City or town, state or province, country, and ZIP or foreign postal code</div>
                    <div class="box-value">{{ $recipient['city'] }}, {{ $recipient['state'] }} {{ $recipient['zip'] }} {{ $recipient['country'] }}</div>
                </div>
            </div>
        </div>

        <div class="instructions">
            <strong>Instructions for Recipient</strong><br><br>
            <strong>Box 1.</strong> Shows nonemployee compensation. If you are in the trade or business of being a payee for nonemployee compensation, you must report this amount on Schedule C (Form 1040) or Schedule C-EZ (Form 1040). You received this form instead of Form W-2 because the payer did not consider you an employee and did not withhold income tax or social security and Medicare tax.<br><br>
            <strong>Box 4.</strong> Shows backup withholding or withholding on payments that are not subject to backup withholding because, for example, you did not furnish your TIN to the payer. See Form W-9 and Pub. 505 for information on backup withholding. Include this amount on your income tax return as tax withheld.<br><br>
            <strong>Important tax return filing information:</strong> You are required to file your income tax return by the due date even if you have not received all of your Form 1099-NEC statements. You can use a reasonable estimate to report amounts on your return if you have not received all your information returns.
        </div>

        <div class="generated-notice">
            <strong>This document was electronically generated by {{ config('app.name') }}</strong><br>
            Generated on: {{ $generatedAt->format('F j, Y \a\t g:i A') }}<br>
            Tax Year: {{ $year }}<br>
            Report ID: {{ $report->id }}<br>
            Total Shifts Completed: {{ $report->total_shifts }}
        </div>

        <div class="footer">
            Form 1099-NEC (Rev. 1-{{ $year }})<br>
            Department of the Treasury - Internal Revenue Service
        </div>
    </div>
</body>
</html>
