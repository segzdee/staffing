@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Create Manual Refund
            <small>Issue a new refund to a business</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/refunds') }}">Refunds</a></li>
            <li class="active">Create</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-8">
                <form method="POST" action="{{ url('panel/admin/refunds') }}" id="createRefundForm">
                    @csrf

                    {{-- Business Selection --}}
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-building"></i> Business Selection</h3>
                        </div>
                        <div class="box-body">
                            <div class="form-group {{ $errors->has('business_id') ? 'has-error' : '' }}">
                                <label for="business_id">Business <span class="text-danger">*</span></label>
                                <select name="business_id" id="business_id" class="form-control select2" required>
                                    <option value="">Search and select a business...</option>
                                    @if(isset($businesses))
                                        @foreach($businesses as $business)
                                            <option value="{{ $business->id }}" {{ old('business_id') == $business->id ? 'selected' : '' }}>
                                                {{ $business->name ?? $business->company_name }} ({{ $business->email }})
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @if($errors->has('business_id'))
                                    <span class="help-block">{{ $errors->first('business_id') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Related Records (Optional) --}}
                    <div class="box box-default">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-link"></i> Related Records (Optional)</h3>
                            <div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                    <i class="fa fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="form-group {{ $errors->has('shift_id') ? 'has-error' : '' }}">
                                <label for="shift_id">Related Shift</label>
                                <select name="shift_id" id="shift_id" class="form-control select2">
                                    <option value="">No related shift</option>
                                </select>
                                <p class="help-block">Select a shift if this refund is related to a specific shift</p>
                                @if($errors->has('shift_id'))
                                    <span class="help-block text-danger">{{ $errors->first('shift_id') }}</span>
                                @endif
                            </div>

                            <div class="form-group {{ $errors->has('shift_payment_id') ? 'has-error' : '' }}">
                                <label for="shift_payment_id">Related Payment</label>
                                <select name="shift_payment_id" id="shift_payment_id" class="form-control">
                                    <option value="">No related payment</option>
                                </select>
                                <p class="help-block">Select a payment if this refund is related to a specific payment</p>
                                @if($errors->has('shift_payment_id'))
                                    <span class="help-block text-danger">{{ $errors->first('shift_payment_id') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Refund Details --}}
                    <div class="box box-success">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-money"></i> Refund Details</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group {{ $errors->has('amount') ? 'has-error' : '' }}">
                                        <label for="amount">Refund Amount <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-addon">$</span>
                                            <input type="number" name="amount" id="amount" class="form-control"
                                                   step="0.01" min="0.01" required
                                                   value="{{ old('amount') }}" placeholder="0.00">
                                        </div>
                                        @if($errors->has('amount'))
                                            <span class="help-block">{{ $errors->first('amount') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group {{ $errors->has('reason') ? 'has-error' : '' }}">
                                        <label for="reason">Refund Reason <span class="text-danger">*</span></label>
                                        <select name="reason" id="reason" class="form-control" required>
                                            <option value="">Select a reason...</option>
                                            <option value="billing_error" {{ old('reason') === 'billing_error' ? 'selected' : '' }}>Billing Error</option>
                                            <option value="overcharge" {{ old('reason') === 'overcharge' ? 'selected' : '' }}>Overcharge</option>
                                            <option value="duplicate_charge" {{ old('reason') === 'duplicate_charge' ? 'selected' : '' }}>Duplicate Charge</option>
                                            <option value="dispute_resolved" {{ old('reason') === 'dispute_resolved' ? 'selected' : '' }}>Dispute Resolved</option>
                                            <option value="goodwill" {{ old('reason') === 'goodwill' ? 'selected' : '' }}>Goodwill</option>
                                            <option value="other" {{ old('reason') === 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @if($errors->has('reason'))
                                            <span class="help-block">{{ $errors->first('reason') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="form-group {{ $errors->has('reason_description') ? 'has-error' : '' }}">
                                <label for="reason_description">Reason Description <span class="text-danger">*</span></label>
                                <textarea name="reason_description" id="reason_description" class="form-control"
                                          rows="4" required minlength="20"
                                          placeholder="Provide a detailed description of why this refund is being issued (minimum 20 characters)...">{{ old('reason_description') }}</textarea>
                                <p class="help-block">
                                    <span id="charCount">0</span>/20 characters minimum
                                    @if($errors->has('reason_description'))
                                        <span class="text-danger">{{ $errors->first('reason_description') }}</span>
                                    @endif
                                </p>
                            </div>

                            <div class="form-group {{ $errors->has('refund_method') ? 'has-error' : '' }}">
                                <label for="refund_method">Refund Method <span class="text-danger">*</span></label>
                                <select name="refund_method" id="refund_method" class="form-control" required>
                                    <option value="original_payment_method" {{ old('refund_method', 'original_payment_method') === 'original_payment_method' ? 'selected' : '' }}>
                                        Original Payment Method
                                    </option>
                                    <option value="credit_balance" {{ old('refund_method') === 'credit_balance' ? 'selected' : '' }}>
                                        Credit to Account Balance
                                    </option>
                                    <option value="manual" {{ old('refund_method') === 'manual' ? 'selected' : '' }}>
                                        Manual (Offline Processing)
                                    </option>
                                </select>
                                <p class="help-block">
                                    <span id="methodHelp">Refund will be sent to the original payment method used</span>
                                </p>
                                @if($errors->has('refund_method'))
                                    <span class="help-block text-danger">{{ $errors->first('refund_method') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Admin Notes --}}
                    <div class="box box-default">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-sticky-note"></i> Admin Notes</h3>
                        </div>
                        <div class="box-body">
                            <div class="form-group {{ $errors->has('admin_notes') ? 'has-error' : '' }}">
                                <label for="admin_notes">Internal Notes (Optional)</label>
                                <textarea name="admin_notes" id="admin_notes" class="form-control"
                                          rows="3" placeholder="Add any internal notes about this refund (not visible to the business)...">{{ old('admin_notes') }}</textarea>
                                @if($errors->has('admin_notes'))
                                    <span class="help-block">{{ $errors->first('admin_notes') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="box box-solid">
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="{{ url('panel/admin/refunds') }}" class="btn btn-default btn-block">
                                        <i class="fa fa-arrow-left"></i> Cancel
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-success btn-block" id="submitBtn">
                                        <i class="fa fa-check"></i> Create Refund
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Sidebar --}}
            <div class="col-md-4">
                {{-- Guidelines --}}
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-info-circle"></i> Refund Guidelines</h3>
                    </div>
                    <div class="box-body">
                        <ul class="list-unstyled">
                            <li><i class="fa fa-check text-success"></i> Verify the business account before issuing</li>
                            <li><i class="fa fa-check text-success"></i> Ensure amount is correct and justified</li>
                            <li><i class="fa fa-check text-success"></i> Provide detailed reason description</li>
                            <li><i class="fa fa-check text-success"></i> Link to related shift/payment if applicable</li>
                            <li><i class="fa fa-check text-success"></i> Document everything in admin notes</li>
                        </ul>
                    </div>
                </div>

                {{-- Refund Method Info --}}
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-credit-card"></i> Refund Methods</h3>
                    </div>
                    <div class="box-body">
                        <dl>
                            <dt>Original Payment Method</dt>
                            <dd class="text-muted">Refund will be processed through the payment gateway to the original payment source. Processing time: 5-10 business days.</dd>

                            <dt class="mt-3">Credit Balance</dt>
                            <dd class="text-muted">Amount will be added to the business's account balance for future use. Instant processing.</dd>

                            <dt class="mt-3">Manual</dt>
                            <dd class="text-muted">For offline processing (bank transfer, check, etc.). Requires manual follow-up.</dd>
                        </dl>
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-bar-chart"></i> Today's Refunds</h3>
                    </div>
                    <div class="box-body">
                        <div class="row text-center">
                            <div class="col-xs-6 border-right">
                                <div class="description-block">
                                    <span class="description-text">COUNT</span>
                                    <h5 class="description-header">{{ $todayCount ?? 0 }}</h5>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <span class="description-text">AMOUNT</span>
                                    <h5 class="description-header">{{ Helper::amountFormatDecimal($todayAmount ?? 0) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Initialize Select2 for business search
    $('#business_id').select2({
        placeholder: 'Search and select a business...',
        allowClear: true,
        ajax: {
            url: '/panel/admin/api/businesses/search',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(business) {
                        return {
                            id: business.id,
                            text: (business.name || business.company_name) + ' (' + business.email + ')'
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 2
    });

    // Initialize Select2 for shift search
    $('#shift_id').select2({
        placeholder: 'No related shift',
        allowClear: true
    });

    // Load shifts when business is selected
    $('#business_id').on('change', function() {
        var businessId = $(this).val();
        var shiftSelect = $('#shift_id');
        var paymentSelect = $('#shift_payment_id');

        // Clear both selects
        shiftSelect.empty().append('<option value="">No related shift</option>');
        paymentSelect.empty().append('<option value="">No related payment</option>');

        if (businessId) {
            // Load shifts for this business
            $.get('/panel/admin/api/businesses/' + businessId + '/shifts', function(data) {
                data.forEach(function(shift) {
                    shiftSelect.append('<option value="' + shift.id + '">' +
                        shift.title + ' (' + shift.date + ')' +
                    '</option>');
                });
            });
        }
    });

    // Load payments when shift is selected
    $('#shift_id').on('change', function() {
        var shiftId = $(this).val();
        var paymentSelect = $('#shift_payment_id');

        paymentSelect.empty().append('<option value="">No related payment</option>');

        if (shiftId) {
            $.get('/panel/admin/api/shifts/' + shiftId + '/payments', function(data) {
                data.forEach(function(payment) {
                    paymentSelect.append('<option value="' + payment.id + '">' +
                        'Payment #' + payment.id + ' - $' + payment.amount + ' (' + payment.status + ')' +
                    '</option>');
                });
            });
        }
    });

    // Character count for reason description
    $('#reason_description').on('input', function() {
        var length = $(this).val().length;
        $('#charCount').text(length);

        if (length >= 20) {
            $('#charCount').removeClass('text-danger').addClass('text-success');
        } else {
            $('#charCount').removeClass('text-success').addClass('text-danger');
        }
    });

    // Update method help text
    $('#refund_method').on('change', function() {
        var method = $(this).val();
        var helpText = {
            'original_payment_method': 'Refund will be sent to the original payment method used',
            'credit_balance': 'Amount will be credited to the business\'s account balance',
            'manual': 'Refund will be processed manually (offline)'
        };
        $('#methodHelp').text(helpText[method] || '');
    });

    // Form validation
    $('#createRefundForm').on('submit', function(e) {
        var amount = parseFloat($('#amount').val());
        var reasonDesc = $('#reason_description').val();

        if (amount <= 0) {
            e.preventDefault();
            alert('Refund amount must be greater than 0');
            return false;
        }

        if (reasonDesc.length < 20) {
            e.preventDefault();
            alert('Reason description must be at least 20 characters');
            return false;
        }

        if (!confirm('Are you sure you want to create this refund for $' + amount.toFixed(2) + '?')) {
            e.preventDefault();
            return false;
        }

        $('#submitBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creating...');
    });

    // Trigger initial character count
    $('#reason_description').trigger('input');
});
</script>

<style>
.mt-3 {
    margin-top: 15px;
}
.border-right {
    border-right: 1px solid #f4f4f4;
}
</style>
@endsection
