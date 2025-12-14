@extends('admin.layout')

@section('css')
<link href="{{ asset('plugins/iCheck/all.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('plugins/select2/select2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
            {{ trans('admin.admin') }}
            	<i class="fa fa-angle-right margin-separator"></i>
            		{{ trans('admin.payment_settings') }}
          </h4>
        </section>

        <!-- Main content -->
        <section class="content">

        	 @if(Session::has('success_message'))
		    <div class="alert alert-success">
		    	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">×</span>
								</button>
		       <i class="fa fa-check margin-separator"></i> {{ Session::get('success_message') }}
		    </div>
		@endif

        	<div class="content">

        		<div class="row">

        	<div class="box">
                <div class="box-header">
                  <h3 class="box-title"><strong>{{ trans('admin.payment_settings') }}</strong></h3>
                </div><!-- /.box-header -->

                <!-- form start -->
                <form class="form-horizontal" method="POST" action="{{ url('panel/admin/payments') }}" enctype="multipart/form-data">

                	<input type="hidden" name="_token" value="{{ csrf_token() }}">

					@include('errors.errors-forms')

                <!-- Start Box Body -->
                <div class="box-body">
                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ trans('admin.currency_code') }}</label>
                    <div class="col-sm-10">
                      <input type="text" value="{{ $settings->currency_code }}" name="currency_code" class="form-control" placeholder="{{ trans('admin.currency_code') }}">
                    </div>
                  </div>
                </div><!-- /.box-body -->

                <div class="box-body">
                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ trans('admin.currency_symbol') }}</label>
                    <div class="col-sm-10">
                      <input type="text" value="{{ $settings->currency_symbol }}" name="currency_symbol" class="form-control" placeholder="{{ trans('admin.currency_symbol') }}">
                      <p class="help-block">{{ trans('admin.notice_currency') }}</p>
                    </div>
                  </div>
                </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('admin.fee_commission') }}</label>
                      <div class="col-sm-10">
                      	<select name="fee_commission" class="form-control">
                          @for ($i=0; $i <= 50; ++$i)
                            <option @if ($settings->fee_commission == $i) selected="selected" @endif value="{{$i}}">{{$i}}%</option>
                            @endfor
                            </select>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.percentage_referred') }}</label>
                      <div class="col-sm-10">
                      	<select name="percentage_referred" class="form-control">
                          @for ($i=2; $i <= 10; ++$i)
                            <option @if ($settings->percentage_referred == $i) selected="selected" @endif value="{{$i}}">{{$i}}%</option>
                            @endfor
                            </select>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.referral_transaction_limit') }}</label>
                      <div class="col-sm-10">
                      	<select name="referral_transaction_limit" class="form-control">
                          <option @if ($settings->referral_transaction_limit == 'unlimited') selected="selected" @endif value="unlimited">
                            {{ trans('admin.unlimited') }}
                          </option>
                          @for ($i=1; $i <= 100; ++$i)
                            <option @if ($settings->referral_transaction_limit == $i) selected="selected" @endif value="{{$i}}">{{$i}}</option>
                            @endfor
                            </select>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('admin.min_subscription_amount') }}</label>
                      <div class="col-sm-10">
                        <input type="number" min="1" autocomplete="off" value="{{ $settings->min_subscription_amount }}" name="min_subscription_amount" class="form-control onlyNumber" placeholder="{{ trans('admin.min_subscription_amount') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                   <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('admin.max_subscription_amount') }}</label>
                      <div class="col-sm-10">
                        <input type="number" min="1" autocomplete="off" value="{{ $settings->max_subscription_amount }}" name="max_subscription_amount" class="form-control onlyNumber" placeholder="{{ trans('admin.max_subscription_amount') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                 <div class="box-body">
                   <div class="form-group">
                     <label class="col-sm-2 control-label">{{ trans('general.min_tip_amount') }}</label>
                     <div class="col-sm-10">
                       <input type="number" min="1" autocomplete="off" value="{{ $settings->min_tip_amount }}" name="min_tip_amount" class="form-control onlyNumber" placeholder="{{ trans('general.min_tip_amount') }}">
                     </div>
                   </div>
                 </div><!-- /.box-body -->

                 <!-- Start Box Body -->
                 <div class="box-body">
                   <div class="form-group">
                     <label class="col-sm-2 control-label">{{ trans('general.max_tip_amount') }}</label>
                     <div class="col-sm-10">
                       <input type="number" min="1" autocomplete="off" value="{{ $settings->max_tip_amount }}" name="max_tip_amount" class="form-control onlyNumber" placeholder="{{ trans('general.max_tip_amount') }}">
                     </div>
                   </div>
                 </div><!-- /.box-body -->

                 <!-- Start Box Body -->
                 <div class="box-body">
                   <div class="form-group">
                     <label class="col-sm-2 control-label">{{ trans('general.min_ppv_amount') }}</label>
                     <div class="col-sm-10">
                       <input type="number" min="1" autocomplete="off" value="{{ $settings->min_ppv_amount }}" name="min_ppv_amount" class="form-control onlyNumber" placeholder="{{ trans('general.min_ppv_amount') }}">
                     </div>
                   </div>
                 </div><!-- /.box-body -->

                 <!-- Start Box Body -->
                 <div class="box-body">
                   <div class="form-group">
                     <label class="col-sm-2 control-label">{{ trans('general.max_ppv_amount') }}</label>
                     <div class="col-sm-10">
                       <input type="number" min="1" autocomplete="off" value="{{ $settings->max_ppv_amount }}" name="max_ppv_amount" class="form-control onlyNumber" placeholder="{{ trans('general.max_ppv_amount') }}">
                     </div>
                   </div>
                 </div><!-- /.box-body -->

                 <!-- Start Box Body -->
                 <div class="box-body">
                   <div class="form-group">
                     <label class="col-sm-2 control-label">{{ trans('general.min_deposits_amount') }}</label>
                     <div class="col-sm-10">
                       <input type="number" min="1" autocomplete="off" value="{{ $settings->min_deposits_amount }}" name="min_deposits_amount" class="form-control onlyNumber" placeholder="{{ trans('general.min_deposits_amount') }}">
                     </div>
                   </div>
                 </div><!-- /.box-body -->

                 <!-- Start Box Body -->
                 <div class="box-body">
                   <div class="form-group">
                     <label class="col-sm-2 control-label">{{ trans('general.max_deposits_amount') }}</label>
                     <div class="col-sm-10">
                       <input type="number" min="1" autocomplete="off" value="{{ $settings->max_deposits_amount }}" name="max_deposits_amount" class="form-control onlyNumber" placeholder="{{ trans('general.max_deposits_amount') }}">
                     </div>
                   </div>
                 </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.amount_min_withdrawal') }}</label>
                      <div class="col-sm-10">
                        <input type="number" min="1" autocomplete="off" value="{{ $settings->amount_min_withdrawal }}" name="amount_min_withdrawal" class="form-control onlyNumber" placeholder="{{ trans('general.amount_min_withdrawal') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                 <div class="box-body">
                   <div class="form-group">
                     <label class="col-sm-2 control-label">{{ trans('admin.currency_position') }}</label>
                     <div class="col-sm-10">
                       <select name="currency_position" class="form-control">
                         <option @if( $settings->currency_position == 'left' ) selected="selected" @endif value="left">{{$settings->currency_symbol}}99 - {{trans('admin.left')}}</option>
                         <option @if( $settings->currency_position == 'right' ) selected="selected" @endif value="right">99{{$settings->currency_symbol}} {{trans('admin.right')}}</option>
                         </select>
                     </div>
                   </div>
                 </div><!-- /.box-body -->

                 <!-- Start Box Body -->
                <div class="box-body">
                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ trans('general.decimal_format') }}</label>
                    <div class="col-sm-10">
                      <select name="decimal_format" class="form-control input-lg">
                        <option @if( $settings->decimal_format == 'dot' ) selected="selected" @endif value="dot">1,989.95</option>
                        <option @if( $settings->decimal_format == 'comma' ) selected="selected" @endif value="comma">1.989,95</option>
                        </select>
                    </div>
                  </div>
                </div><!-- /.box-body -->

                <!-- Start Box Body -->
                <div class="box-body">
                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ trans('admin.days_process_withdrawals') }}</label>
                    <div class="col-sm-10">
                      <select name="days_process_withdrawals" class="form-control">
                        @for ($i=1; $i <= 30; ++$i)
                          <option @if( $settings->days_process_withdrawals == $i ) selected="selected" @endif value="{{$i}}">{{$i}} ({{trans_choice('general.days', $i)}})</option>
                          @endfor
                          </select>
                    </div>
                  </div>
                </div><!-- /.box-body -->

                <!-- Start Box Body -->
                <div class="box-body">
                  <div class="form-group">
                    <label class="col-sm-2 control-label">{{ trans('general.type_withdrawals') }}</label>
                    <div class="col-sm-10">
                      <select name="type_withdrawals" class="form-control">
                          <option @if ($settings->type_withdrawals == 'custom') selected="selected" @endif value="custom">{{ trans('general.custom_amount') }}</option>
                          <option @if ($settings->type_withdrawals == 'balance') selected="selected" @endif value="balance">{{ trans('general.total_balance') }}</option>
                          </select>
                    </div>
                  </div>
                </div><!-- /.box-body -->

                <!-- Start Box Body -->
               <div class="box-body">
                 <div class="form-group">
                   <label class="col-sm-2 control-label">{{ trans('users.payout_method') }} (PayPal)</label>
                   <div class="col-sm-10">
                     <select name="payout_method_paypal" class="form-control">
                         <option @if( $settings->payout_method_paypal == 'on' ) selected="selected" @endif value="on">{{ trans('general.enabled') }}</option>
                           <option @if( $settings->payout_method_paypal == 'off' ) selected="selected" @endif value="off">{{ trans('general.disabled') }}</option>
                         </select>
                         <p class="help-block">{{ trans('general.payout_method_desc') }}</p>
                   </div>
                 </div>
               </div><!-- /.box-body -->

               <!-- Start Box Body -->
              <div class="box-body">
                <div class="form-group">
                  <label class="col-sm-2 control-label">{{ trans('users.payout_method') }} (Payoneer)</label>
                  <div class="col-sm-10">
                    <select name="payout_method_payoneer" class="form-control">
                        <option @if( $settings->payout_method_payoneer == 'on' ) selected="selected" @endif value="on">{{ trans('general.enabled') }}</option>
                          <option @if( $settings->payout_method_payoneer == 'off' ) selected="selected" @endif value="off">{{ trans('general.disabled') }}</option>
                        </select>
                        <p class="help-block">{{ trans('general.payout_method_desc') }}</p>
                  </div>
                </div>
              </div><!-- /.box-body -->

              <!-- Start Box Body -->
             <div class="box-body">
               <div class="form-group">
                 <label class="col-sm-2 control-label">{{ trans('users.payout_method') }} (Zelle)</label>
                 <div class="col-sm-10">
                   <select name="payout_method_zelle" class="form-control">
                       <option @if( $settings->payout_method_zelle == 'on' ) selected="selected" @endif value="on">{{ trans('general.enabled') }}</option>
                         <option @if( $settings->payout_method_zelle == 'off' ) selected="selected" @endif value="off">{{ trans('general.disabled') }}</option>
                       </select>
                       <p class="help-block">{{ trans('general.payout_method_desc') }}</p>
                 </div>
               </div>
             </div><!-- /.box-body -->

               <!-- Start Box Body -->
              <div class="box-body">
                <div class="form-group">
                  <label class="col-sm-2 control-label">{{ trans('users.payout_method') }} ({{ trans('general.bank') }})</label>
                  <div class="col-sm-10">
                    <select name="payout_method_bank" class="form-control">
                        <option @if( $settings->payout_method_bank == 'on' ) selected="selected" @endif value="on">{{ trans('general.enabled') }}</option>
                          <option @if( $settings->payout_method_bank == 'off' ) selected="selected" @endif value="off">{{ trans('general.disabled') }}</option>
                        </select>
                        <p class="help-block">{{ trans('general.payout_method_desc') }}</p>
                  </div>
                </div>
              </div><!-- /.box-body -->

              <!-- Start Box Body -->
             <div class="box-body">
               <div class="form-group">
                 <label class="col-sm-2 control-label">{{ trans('general.wallet') }}</label>
                 <div class="col-sm-10">
                   <select name="disable_wallet" class="form-control">
                       <option @if( $settings->disable_wallet == 'off' ) selected="selected" @endif value="off">{{ trans('general.enabled') }}</option>
                         <option @if( $settings->disable_wallet == 'on' ) selected="selected" @endif value="on">{{ trans('general.disabled') }}</option>
                       </select>
                 </div>
               </div>
             </div><!-- /.box-body -->

             <!-- Start Box Body -->
            <div class="box-body">
              <div class="form-group">
                <label class="col-sm-2 control-label">{{ trans('general.apply_taxes_wallet') }}</label>
                <div class="col-sm-10">
                  <select name="tax_on_wallet" class="form-control">
                      <option @if($settings->tax_on_wallet) selected="selected" @endif value="1">{{ trans('general.enabled') }}</option>
                        <option @if(! $settings->tax_on_wallet) selected="selected" @endif value="0">{{ trans('general.disabled') }}</option>
                      </select>
                </div>
              </div>
            </div><!-- /.box-body -->

             <!-- Start Box Body -->
            <div class="box-body">
              <div class="form-group">
                <label class="col-sm-2 control-label">{{ trans('general.wallet_format') }}</label>
                <div class="col-sm-10">
                  <select name="wallet_format" class="form-control">
                      <option @if( $settings->wallet_format == 'real_money' ) selected="selected" @endif value="real_money">{{ trans('general.real_money') }} ({{ $settings->currency_symbol }})</option>
                      <option @if( $settings->wallet_format == 'credits' ) selected="selected" @endif value="credits">{{ trans('general.credits') }}</option>
                      <option @if( $settings->wallet_format == 'points' ) selected="selected" @endif value="points">{{ trans('general.points') }}</option>
                      <option @if( $settings->wallet_format == 'tokens' ) selected="selected" @endif value="tokens">{{ trans('general.tokens') }}</option>
                      </select>
                      <p class="help-block">
                        {{ trans('general.equivalent_money_format') }} {{ Helper::amountFormatDecimal(1)}} {{$settings->currency_code}}
                      </p>
                </div>
              </div>
            </div><!-- /.box-body -->

            <!-- Start Box Body -->
           <div class="box-body">
             <div class="form-group">
               <label class="col-sm-2 control-label">Stripe Connect</label>
               <div class="col-sm-10">
                 <select name="stripe_connect" class="form-control">
                     <option @if ($settings->stripe_connect) selected="selected" @endif value="1">{{ trans('general.enabled') }}</option>
                       <option @if (! $settings->stripe_connect) selected="selected" @endif value="0">{{ trans('general.disabled') }}</option>
                     </select>
               </div>
             </div>
           </div><!-- /.box-body -->

           <!-- Start Box Body -->
           <div class="box-body">
             <div class="form-group">
               <label class="col-sm-2 control-label">{{ trans('general.stripe_connect_countries') }}</label>
               <div class="col-sm-10">
                 <select name="stripe_connect_countries[]" multiple class="form-control custom-select stripeConnectCountries">
                   @foreach (Countries::orderBy('country_name')->get() as $country)
                     <option @if (in_array($country->country_code, $stripeConnectCountries)) selected="selected" @endif value="{{$country->country_code}}">{{ $country->country_name }}</option>
                   @endforeach
                   </select>
                   <p class="help-block">
                     {{ trans('general.info_stripe_connect_countries') }} <a href="https://dashboard.stripe.com/settings/connect/express" target="_blank">https://dashboard.stripe.com/settings/connect/express</a>
                   </p>
               </div>
             </div>
           </div><!-- /.box-body -->

               <div class="box-footer">
                 <button type="submit" class="btn btn-success">{{ trans('admin.save') }}</button>
               </div><!-- /.box-footer -->
               </form>

              </div><!-- /.row -->
        	</div><!-- /.content -->
        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
@endsection

@section('javascript')
  <script>
  $('.stripeConnectCountries').select2({
  tags: false,
  tokenSeparators: [','],
  placeholder: '{{ trans('general.country') }}',
});

  </script>
@endsection
