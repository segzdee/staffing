@extends('admin.layout')

@section('css')
<link href="{{ asset('plugins/iCheck/all.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
            {{ trans('admin.admin') }}
            	<i class="fa fa-angle-right margin-separator"></i>
            		{{ trans('general.role_and_permissions') }}

            		<i class="fa fa-angle-right margin-separator"></i>
            		{{ $user->name }}
              </h4>
            </section>

        <!-- Main content -->
        <section class="content">

          @if (Session::has('success_message'))
      		    <div class="alert alert-success">
      		    	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
      								<span aria-hidden="true">×</span>
      								</button>
      		       <i class="fa fa-check margin-separator"></i>  {{ Session::get('success_message') }}
      		    </div>
      		@endif

          @if (Session::has('error_message'))
      		    <div class="alert alert-danger">
      		    	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
      								<span aria-hidden="true">×</span>
      								</button>
      		       <i class="far fa-times-circle margin-separator"></i>  {{ Session::get('error_message') }}
      		    </div>
      		@endif

      <div class="content">
        <div class="row">
          <div class="col-md-12">

        	<div class="box box-danger">
            <!-- form start -->
            <form class="form-horizontal" method="POST" action="{{ url('panel/admin/members/roles-and-permissions/'.$user->id) }}" enctype="multipart/form-data">

            	<input type="hidden" name="_token" value="{{ csrf_token() }}">

					@include('errors.errors-forms')

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.role') }}</label>
              <div class="col-sm-10">
                <select name="role" class="form-control" >
                  <option @if ($user->role == 'normal') selected="selected" @endif value="normal">{{trans('admin.normal')}}</option>
                  <option @if ($user->role == 'admin') selected="selected" @endif value="admin">{{trans('admin.role_admin')}}</option>
                  </select>
              </div>
            </div>
          </div><!-- /.box-body -->

          @if ($user->role == 'admin')
          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group margin-bottom-zero">
              <label class="col-sm-2 control-label">{{ trans('general.can_see_post_blocked') }}</label>
              <div class="col-sm-10">

                <div class="radio">
                <label class="padding-zero">
                  <input type="radio" name="permission" @if ($user->permission == 'all') checked="checked" @endif value="all">
                  {{ trans('general.yes') }}
                </label>
              </div>

              <div class="radio">
                <label class="padding-zero">
                  <input type="radio" name="permission" @if ($user->permission == 'none') checked="checked" @endif value="none">
                  {{ trans('general.no') }}
                </label>
              </div>
              <span class="help-block">{{ trans('general.info_can_see_post_blocked') }}</span>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.limited_access') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="limited_access" class="limitedAccess" value="limited_access" @if ($user->permissions == 'limited_access') checked="checked" @endif>
                </label>
              </div>
              <span class="help-block">{{ trans('general.info_limited_access') }}</span>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.select_all') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" id="select-all" class="check" name="select_all" value="yes">
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.dashboard') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="dashboard" @if (in_array('dashboard', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.general_settings') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="general" @if (in_array('general', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.announcements') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="announcements" @if (in_array('announcements', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.maintenance_mode') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="maintenance" @if (in_array('maintenance', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.billing_information') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="billing" @if (in_array('billing', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.tax_rates') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="tax" @if (in_array('tax', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.countries_states') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="countries_states" @if (in_array('countries_states', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.email_settings') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="email" @if (in_array('email', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.live_streaming') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="live_streaming" @if (in_array('live_streaming', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.shop') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="shop" @if (in_array('shop', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.products') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="products" @if (in_array('products', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.storage') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="storage" @if (in_array('storage', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.theme') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="theme" @if (in_array('theme', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.custom_css_js') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="custom_css_js" @if (in_array('custom_css_js', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.posts') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="posts" @if (in_array('posts', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.subscriptions') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="subscriptions" @if (in_array('subscriptions', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.transactions') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="transactions" @if (in_array('transactions', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.deposits') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="deposits" @if (in_array('deposits', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.members') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="members" @if (in_array('members', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.referrals') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="referrals" @if (in_array('referrals', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.languages') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="languages" @if (in_array('languages', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.categories') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="categories" @if (in_array('categories', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.reports') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="reports" @if (in_array('reports', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.withdrawals') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="withdrawals" @if (in_array('withdrawals', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.verification_requests') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="verification_requests" @if (in_array('verification_requests', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.pages') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="pages" @if (in_array('pages', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.blog') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="blog" @if (in_array('blog', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.payment_settings') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="payment_settings" @if (in_array('payment_settings', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.profiles_social') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="profiles_social" @if (in_array('profiles_social', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.social_login') }}</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="social_login" @if (in_array('social_login', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">Google</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="google" @if (in_array('google', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">PWA</label>
              <div class="col-sm-10">
                <div class="radio">
                <label class="padding-zero">
                  <input type="checkbox" name="permissions[]" class="check" value="pwa" @if (in_array('pwa', $permissions)) checked="checked" @endif>
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          @endif
          {{-- End is Admin --}}

        <div class="box-footer">
        	 <a href="{{ url('panel/admin/members/edit', $user->id) }}" class="btn btn-default">{{ trans('general.go_back') }}</a>
          <button type="submit" class="btn btn-success pull-right">{{ trans('admin.save') }}</button>
        </div><!-- /.box-footer -->
      </form>
    </div>

</div><!-- /. col-md-9 -->

  </section><!-- /.content -->
</div><!-- /.content-wrapper -->
@endsection

@section('javascript')

<script>

var triggeredByChild = false;

$('.limitedAccess').on('ifChecked', function (event) {
    triggeredByChild = false;
    $('.check').iCheck('uncheck');
    $('#select-all').iCheck('uncheck');
});

$('#select-all').on('ifChecked', function (event) {
    $('.check').iCheck('check');
    $('.limitedAccess').iCheck('uncheck');
    triggeredByChild = false;
});

$('.check').on('ifChecked', function (event) {
    triggeredByChild = false;
    $('.limitedAccess').iCheck('uncheck');
});

$('#select-all').on('ifUnchecked', function (event) {
    if (! triggeredByChild) {
        $('.check').iCheck('uncheck');
    }
    triggeredByChild = false;
});
// Removed the checked state from "All" if any checkbox is unchecked
$('.check').on('ifUnchecked', function (event) {
    triggeredByChild = true;
    $('#select-all').iCheck('uncheck');
});

$('.check').on('ifChecked', function (event) {
    if ($('.check').filter(':checked').length == $('.check').length) {
        $('#select-all').iCheck('check');
    }
});
</script>
@endsection
