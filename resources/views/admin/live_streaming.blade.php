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
            		{{ trans('general.live_streaming') }}
          </h4>

        </section>

        <!-- Main content -->
        <section class="content">

        	 @if (Session::has('success_message'))
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
                  <h3 class="box-title"><strong>{{ trans('general.live_streaming') }}</strong></h3>
                </div><!-- /.box-header -->

                <!-- form start -->
                <form class="form-horizontal" method="POST" action="{{ url('panel/admin/live-streaming') }}" enctype="multipart/form-data">

                	<input type="hidden" name="_token" value="{{ csrf_token() }}">

					@include('errors.errors-forms')

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('admin.status') }}</label>
              <div class="col-sm-10">

                <div class="radio">
                <label class="padding-zero">
                  <input type="radio" name="live_streaming_status" @if ($settings->live_streaming_status == 'on') checked="checked" @endif value="on" checked>
                  {{ trans('general.enabled') }}
                </label>
              </div>

              <div class="radio">
                <label class="padding-zero">
                  <input type="radio" name="live_streaming_status" @if ($settings->live_streaming_status == 'off') checked="checked" @endif value="off">
                  {{ trans('general.disabled') }}
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.live_streaming_free') }}</label>
              <div class="col-sm-10">

                <div class="radio">
                <label class="padding-zero">
                  <input type="radio" name="live_streaming_free" @if ($settings->live_streaming_free == '1') checked="checked" @endif value="1" checked>
                  {{ trans('general.enabled') }}
                </label>
              </div>

              <div class="radio">
                <label class="padding-zero">
                  <input type="radio" name="live_streaming_free" @if ($settings->live_streaming_free == '0') checked="checked" @endif value="0">
                  {{ trans('general.disabled') }}
                </label>
              </div>
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
           <div class="box-body">
             <div class="form-group">
               <label class="col-sm-2 control-label">Agora APP ID</label>
               <div class="col-sm-10">
                 <input type="text" value="{{ $settings->agora_app_id }}" name="agora_app_id" class="form-control" placeholder="Agora APP ID">
               </div>
             </div>
           </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.live_streaming_min_price') }}</label>
                      <div class="col-sm-10">
                        <input type="number" min="1" autocomplete="off" value="{{ $settings->live_streaming_minimum_price }}" name="live_streaming_minimum_price" class="form-control onlyNumber" placeholder="{{ trans('general.live_streaming_min_price') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.live_streaming_max_price') }}</label>
                      <div class="col-sm-10">
                        <input type="number" min="1" autocomplete="off" value="{{ $settings->live_streaming_max_price }}" name="live_streaming_max_price" class="form-control onlyNumber" placeholder="{{ trans('general.live_streaming_max_price') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group margin-zero">
                      <label class="col-sm-2 control-label">{{ trans('general.limit_live_streaming_paid') }} ({{ trans('general.minutes') }})</label>
                      <div class="col-sm-10">
                        <select name="limit_live_streaming_paid" class="form-control">
                          <option @if ($settings->limit_live_streaming_paid == 0) selected="selected" @endif value="0">{{ trans('admin.unlimited') }}</option>
                          @for ($i = 10; $i <= 100; $i+=10)
                            <option @if ($settings->limit_live_streaming_paid == $i) selected="selected" @endif value="{{ $i }}">{{$i}} {{ trans('general.minutes') }}</option>
                          @endfor
                          </select>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group margin-zero">
                      <label class="col-sm-2 control-label">{{ trans('general.limit_live_streaming_free') }} ({{ trans('general.minutes') }})</label>
                      <div class="col-sm-10">
                        <select name="limit_live_streaming_free" class="form-control">
                          <option @if ($settings->limit_live_streaming_free == 0) selected="selected" @endif value="0">{{ trans('admin.unlimited') }}</option>
                          @for ($i = 10; $i <= 100; $i+=10)
                            <option @if ($settings->limit_live_streaming_free == $i) selected="selected" @endif value="{{ $i }}">{{$i}} {{ trans('general.minutes') }}</option>
                          @endfor
                          </select>
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
