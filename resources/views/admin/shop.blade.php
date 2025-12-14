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
            		{{ trans('general.shop') }}
          </h4>

        </section>

        <!-- Main content -->
        <section class="content">

    @if (session('success_message'))
		    <div class="alert alert-success">
		    	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">×</span>
								</button>
		       <i class="fa fa-check margin-separator"></i> {{ session('success_message') }}
		    </div>
		@endif

        	<div class="content">

        		<div class="row">

        	<div class="box">
                <div class="box-header">
                  <h3 class="box-title"><strong>{{ trans('general.shop') }}</strong></h3>
                </div><!-- /.box-header -->

                <!-- form start -->
                <form class="form-horizontal" method="POST" action="{{ url('panel/admin/shop') }}" enctype="multipart/form-data">

                	<input type="hidden" name="_token" value="{{ csrf_token() }}">

					@include('errors.errors-forms')

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.status') }}</label>
              <div class="col-sm-10">

                <div class="radio">
                <label class="padding-zero">
                  <input type="radio" name="shop" @if ($settings->shop) checked="checked" @endif value="1" checked>
                  {{ trans('general.enabled') }}
                </label>
              </div>

              <div class="radio">
                <label class="padding-zero">
                  <input type="radio" name="shop" @if (! $settings->shop) checked="checked" @endif value="0">
                  {{ trans('general.disabled') }}
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.minimum_price_of_sale') }}</label>
              <div class="col-sm-10">
                <input type="number" min="1" autocomplete="off" value="{{ $settings->min_price_product }}" name="min_price_product" class="form-control onlyNumber" placeholder="{{ trans('general.minimum_price_of_sale') }}">
              </div>
            </div>
          </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.maximum_price_of_sale') }}</label>
                      <div class="col-sm-10">
                        <input type="number" min="1" autocomplete="off" value="{{ $settings->max_price_product }}" name="max_price_product" class="form-control onlyNumber" placeholder="{{ trans('general.maximum_price_of_sale') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.type_sale') }}</label>
                      <div class="col-sm-10">
                        <div class="radio">
                          <label class="padding-zero">
                            <input type="checkbox" name="digital_product_sale" class="check" value="1" @if ($settings->digital_product_sale) checked="checked" @endif>
                              <span class="margin-left-5">{{ __('general.digital_products') }}</span>
                          </label>
                        </div>

                        <div class="radio">
                          <label class="padding-zero">
                            <input type="checkbox" name="physical_product_sale" class="check" value="1" @if ($settings->physical_product_sale) checked="checked" @endif>
                              <span class="margin-left-5">{{ __('general.physical_products') }}</span>
                          </label>
                        </div>

                        <div class="radio">
                          <label class="padding-zero">
                            <input type="checkbox" name="custom_content" class="check" value="1" @if ($settings->custom_content) checked="checked" @endif>
                              <span class="margin-left-5">{{ __('general.custom_content') }}</span>
                          </label>
                        </div>
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
