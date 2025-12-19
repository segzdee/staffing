<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Note {{ $credit_note_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
        }

        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 11px;
            color: #666;
        }

        .credit-note-title {
            font-size: 28px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
        }

        .credit-note-number {
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
        }

        .credit-note-date {
            font-size: 12px;
            color: #666;
        }

        /* Info Section */
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }

        .info-box:last-child {
            padding-right: 0;
            padding-left: 20px;
        }

        .info-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .info-content {
            font-size: 12px;
            color: #333;
        }

        .info-content p {
            margin-bottom: 3px;
        }

        .info-content .name {
            font-weight: bold;
            font-size: 14px;
        }

        /* Details Table */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .details-table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 12px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
        }

        .details-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }

        .details-table .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        .details-table .description {
            width: 60%;
        }

        /* Totals */
        .totals-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .totals-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .totals-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 12px;
            font-size: 12px;
        }

        .totals-table .label {
            text-align: right;
            color: #666;
        }

        .totals-table .value {
            text-align: right;
            font-family: 'Courier New', monospace;
            width: 120px;
        }

        .totals-table .total-row td {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
            padding-top: 12px;
        }

        .totals-table .total-row .value {
            color: #4f46e5;
        }

        /* Reason Box */
        .reason-box {
            background-color: #f8f9fa;
            border-left: 4px solid #4f46e5;
            padding: 15px 20px;
            margin-bottom: 30px;
        }

        .reason-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
        }

        .reason-text {
            font-size: 12px;
            color: #333;
        }

        /* Payment Info */
        .payment-info {
            background-color: #e8f4f8;
            border-radius: 4px;
            padding: 15px 20px;
            margin-bottom: 30px;
        }

        .payment-info-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0c5460;
            margin-bottom: 8px;
        }

        .payment-info-content {
            font-size: 12px;
            color: #0c5460;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 10px;
            color: #888;
        }

        .footer p {
            margin-bottom: 3px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="logo">{{ $company['name'] }}</div>
                <div class="company-details">
                    <p>{{ $company['address'] }}</p>
                    <p>{{ $company['city'] }}, {{ $company['state'] }} {{ $company['zip'] }}</p>
                    <p>{{ $company['email'] }}</p>
                    @if($company['tax_id'])
                        <p>Tax ID: {{ $company['tax_id'] }}</p>
                    @endif
                </div>
            </div>
            <div class="header-right">
                <div class="credit-note-title">CREDIT NOTE</div>
                <div class="credit-note-number"><strong>{{ $credit_note_number }}</strong></div>
                <div class="credit-note-date">Issue Date: {{ $issue_date->format('F d, Y') }}</div>
                <div style="margin-top: 10px;">
                    <span class="status-badge">Completed</span>
                </div>
            </div>
        </div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-box">
                <div class="info-title">Credited To</div>
                <div class="info-content">
                    <p class="name">{{ $customer['name'] }}</p>
                    @if($customer['company'])
                        <p>{{ $customer['company'] }}</p>
                    @endif
                    @if($customer['address'])
                        <p>{{ $customer['address'] }}</p>
                        <p>{{ $customer['city'] }}, {{ $customer['state'] }} {{ $customer['zip'] }}</p>
                    @endif
                    <p>{{ $customer['email'] }}</p>
                </div>
            </div>
            <div class="info-box">
                <div class="info-title">Original Payment Reference</div>
                <div class="info-content">
                    <p><strong>Transaction ID:</strong> {{ $original_payment['reference'] }}</p>
                    <p><strong>Payment Date:</strong> {{ $original_payment['date'] }}</p>
                    <p><strong>Refund Method:</strong> {{ $refund_method }}</p>
                    <p><strong>Gateway:</strong> {{ $payment_gateway }}</p>
                </div>
            </div>
        </div>

        <!-- Details Table -->
        <table class="details-table">
            <thead>
                <tr>
                    <th class="description">Description</th>
                    <th class="amount">Original Amount</th>
                    <th class="amount">Credit Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="description">
                        @if($shift)
                            <strong>Shift Refund:</strong> {{ $shift['title'] }}<br>
                            <span style="color: #666; font-size: 11px;">Date: {{ $shift['date'] }}</span>
                        @else
                            <strong>Account Credit</strong><br>
                            <span style="color: #666; font-size: 11px;">{{ $refund_reason }}</span>
                        @endif
                    </td>
                    <td class="amount">${{ number_format($original_amount, 2) }}</td>
                    <td class="amount">${{ number_format($refund_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="totals-section">
            <div class="totals-left">
                <!-- Empty for layout -->
            </div>
            <div class="totals-right">
                <table class="totals-table">
                    <tr>
                        <td class="label">Subtotal:</td>
                        <td class="value">${{ number_format($refund_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tax (0%):</td>
                        <td class="value">$0.00</td>
                    </tr>
                    <tr class="total-row">
                        <td class="label">Total Credit:</td>
                        <td class="value">${{ number_format($refund_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Reason Box -->
        <div class="reason-box">
            <div class="reason-title">Reason for Credit</div>
            <div class="reason-text">
                <strong>{{ $refund_reason }}</strong><br>
                {{ $reason_description }}
            </div>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <div class="payment-info-title">Refund Information</div>
            <div class="payment-info-content">
                @if($refund_method === 'Original Payment Method')
                    This credit has been refunded to the original payment method used for the transaction.
                    Please allow 5-10 business days for the refund to appear on your statement.
                @elseif($refund_method === 'Account Credit')
                    This credit has been applied to your account balance and is available for use immediately.
                @else
                    This refund is being processed manually. Our team will contact you with further details.
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an official credit note from {{ $company['name'] }}.</p>
            <p>For questions regarding this credit note, please contact {{ $company['email'] }}</p>
            <p>&copy; {{ date('Y') }} {{ $company['name'] }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
