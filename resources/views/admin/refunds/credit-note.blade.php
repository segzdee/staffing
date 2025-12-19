<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Note - {{ $refund->refund_number ?? 'REF-' . str_pad($refund->id, 6, '0', STR_PAD_LEFT) }}</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }

        .credit-note {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            background: #fff;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #333;
        }

        .logo-section {
            flex: 1;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .logo-tagline {
            font-size: 12px;
            color: #666;
        }

        .document-info {
            text-align: right;
        }

        .document-title {
            font-size: 32px;
            font-weight: 700;
            color: #e74c3c;
            margin-bottom: 10px;
        }

        .document-number {
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }

        .document-date {
            font-size: 14px;
            color: #666;
        }

        /* Parties Section */
        .parties {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }

        .party {
            flex: 1;
        }

        .party-label {
            font-size: 11px;
            font-weight: 600;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .party-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .party-details {
            font-size: 13px;
            color: #666;
        }

        .party-details p {
            margin-bottom: 3px;
        }

        /* Reference Section */
        .reference-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .reference-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .reference-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .reference-item {
            text-align: center;
        }

        .reference-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .reference-value {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        /* Table */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .details-table th {
            background: #333;
            color: #fff;
            padding: 12px 15px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .details-table th:last-child {
            text-align: right;
        }

        .details-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .details-table td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .item-description {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }

        .item-details {
            font-size: 12px;
            color: #666;
        }

        /* Reason Section */
        .reason-section {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 30px;
        }

        .reason-label {
            font-size: 12px;
            font-weight: 600;
            color: #856404;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .reason-text {
            font-size: 14px;
            color: #856404;
        }

        /* Totals */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }

        .totals-table {
            width: 300px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .totals-row.total {
            border-bottom: none;
            border-top: 2px solid #333;
            margin-top: 10px;
            padding-top: 15px;
        }

        .totals-label {
            font-size: 14px;
            color: #666;
        }

        .totals-value {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }

        .totals-row.total .totals-label,
        .totals-row.total .totals-value {
            font-size: 18px;
            font-weight: 700;
            color: #e74c3c;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #28a745;
            color: #fff;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Footer */
        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
        }

        .footer-company {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .footer-details {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }

        .footer-note {
            margin-top: 20px;
            font-size: 11px;
            color: #999;
            font-style: italic;
        }

        /* Print Styles */
        @media print {
            body {
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .credit-note {
                max-width: 100%;
                padding: 20px;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            .header {
                page-break-inside: avoid;
            }

            .details-table {
                page-break-inside: avoid;
            }
        }

        /* Print Button */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #333;
            color: #fff;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.2s;
        }

        .print-button:hover {
            background: #555;
            transform: translateY(-2px);
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #6c757d;
            color: #fff;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.2s;
        }

        .back-button:hover {
            background: #5a6268;
            color: #fff;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    {{-- Print Controls --}}
    <a href="{{ url('panel/admin/refunds/' . $refund->id) }}" class="back-button no-print">
        &larr; Back
    </a>
    <button onclick="window.print()" class="print-button no-print">
        Print Credit Note
    </button>

    <div class="credit-note">
        {{-- Header --}}
        <div class="header">
            <div class="logo-section">
                <div class="logo">OvertimeStaff</div>
                <div class="logo-tagline">Global Shift Marketplace</div>
            </div>
            <div class="document-info">
                <div class="document-title">CREDIT NOTE</div>
                <div class="document-number">
                    {{ $refund->refund_number ?? 'REF-' . str_pad($refund->id, 6, '0', STR_PAD_LEFT) }}
                </div>
                <div class="document-date">
                    Date: {{ ($refund->completed_at ?? $refund->created_at)->format('F d, Y') }}
                </div>
            </div>
        </div>

        {{-- Parties --}}
        <div class="parties">
            <div class="party">
                <div class="party-label">Issued By</div>
                <div class="party-name">{{ config('app.name', 'OvertimeStaff') }}</div>
                <div class="party-details">
                    @if(config('company.address'))
                        <p>{{ config('company.address') }}</p>
                    @endif
                    @if(config('company.city') || config('company.country'))
                        <p>{{ config('company.city') }}{{ config('company.city') && config('company.country') ? ', ' : '' }}{{ config('company.country') }}</p>
                    @endif
                    @if(config('company.email'))
                        <p>{{ config('company.email') }}</p>
                    @endif
                    @if(config('company.phone'))
                        <p>{{ config('company.phone') }}</p>
                    @endif
                    @if(config('company.tax_id'))
                        <p>Tax ID: {{ config('company.tax_id') }}</p>
                    @endif
                </div>
            </div>
            <div class="party" style="text-align: right;">
                <div class="party-label">Issued To</div>
                @if($refund->business)
                    <div class="party-name">{{ $refund->business->name ?? $refund->business->company_name }}</div>
                    <div class="party-details">
                        @if($refund->business->address)
                            <p>{{ $refund->business->address }}</p>
                        @endif
                        @if($refund->business->city || $refund->business->country)
                            <p>{{ $refund->business->city }}{{ $refund->business->city && $refund->business->country ? ', ' : '' }}{{ $refund->business->country }}</p>
                        @endif
                        <p>{{ $refund->business->email }}</p>
                        @if($refund->business->phone)
                            <p>{{ $refund->business->phone }}</p>
                        @endif
                        @if($refund->business->tax_id)
                            <p>Tax ID: {{ $refund->business->tax_id }}</p>
                        @endif
                    </div>
                @else
                    <div class="party-name">Business Not Available</div>
                @endif
            </div>
        </div>

        {{-- Reference Information --}}
        <div class="reference-section">
            <div class="reference-title">Reference Information</div>
            <div class="reference-grid">
                <div class="reference-item">
                    <div class="reference-label">Credit Note Number</div>
                    <div class="reference-value">{{ $refund->refund_number ?? 'REF-' . str_pad($refund->id, 6, '0', STR_PAD_LEFT) }}</div>
                </div>
                <div class="reference-item">
                    <div class="reference-label">Original Payment</div>
                    <div class="reference-value">
                        @if($refund->gateway_transaction_id)
                            {{ $refund->gateway_transaction_id }}
                        @elseif($refund->shiftPayment)
                            Payment #{{ $refund->shift_payment_id }}
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div class="reference-item">
                    <div class="reference-label">Status</div>
                    <div class="reference-value">
                        <span class="status-badge">{{ ucfirst($refund->status) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Details Table --}}
        <table class="details-table">
            <thead>
                <tr>
                    <th style="width: 60%;">Description</th>
                    <th style="width: 20%;">Original Amount</th>
                    <th style="width: 20%;">Refund Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="item-description">
                            @if($refund->shift)
                                Refund for Shift: {{ $refund->shift->title }}
                            @else
                                Account Credit / Refund
                            @endif
                        </div>
                        <div class="item-details">
                            @php
                                $reasonLabels = [
                                    'auto_cancellation' => 'Automatic cancellation refund',
                                    'dispute_resolved' => 'Dispute resolution credit',
                                    'overcharge' => 'Overcharge correction',
                                    'goodwill' => 'Goodwill credit',
                                    'billing_error' => 'Billing error correction',
                                    'duplicate_charge' => 'Duplicate charge refund',
                                    'other' => 'Manual adjustment',
                                ];
                            @endphp
                            {{ $reasonLabels[$refund->reason ?? $refund->type] ?? 'Credit adjustment' }}
                            @if($refund->shift)
                                <br>
                                Shift Date: {{ $refund->shift->start_date?->format('M d, Y') ?? 'N/A' }}
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($refund->shiftPayment)
                            {{ Helper::amountFormatDecimal($refund->shiftPayment->total_amount ?? $refund->shiftPayment->amount ?? $refund->amount) }}
                        @else
                            {{ Helper::amountFormatDecimal($refund->original_amount ?? $refund->amount) }}
                        @endif
                    </td>
                    <td>{{ Helper::amountFormatDecimal($refund->amount) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Reason --}}
        @if($refund->reason_description)
        <div class="reason-section">
            <div class="reason-label">Refund Reason</div>
            <div class="reason-text">{{ $refund->reason_description }}</div>
        </div>
        @endif

        {{-- Totals --}}
        <div class="totals-section">
            <div class="totals-table">
                @if($refund->shiftPayment && ($refund->shiftPayment->total_amount ?? $refund->shiftPayment->amount) != $refund->amount)
                <div class="totals-row">
                    <span class="totals-label">Original Payment</span>
                    <span class="totals-value">{{ Helper::amountFormatDecimal($refund->shiftPayment->total_amount ?? $refund->shiftPayment->amount) }}</span>
                </div>
                @endif
                <div class="totals-row total">
                    <span class="totals-label">Total Credit</span>
                    <span class="totals-value">{{ Helper::amountFormatDecimal($refund->amount) }}</span>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <div class="footer-company">{{ config('app.name', 'OvertimeStaff') }}</div>
            @if(config('company.address'))
                <div class="footer-details">{{ config('company.address') }}</div>
            @endif
            @if(config('company.email'))
                <div class="footer-details">{{ config('company.email') }}</div>
            @endif
            @if(config('company.website'))
                <div class="footer-details">{{ config('company.website') }}</div>
            @endif
            <div class="footer-note">
                This credit note is issued as confirmation of the refund processed to your account.
                Please retain this document for your records. If you have any questions, please contact our support team.
            </div>
        </div>
    </div>

    @if(request('print'))
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
    @endif
</body>
</html>
