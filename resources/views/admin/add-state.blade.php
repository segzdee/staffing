@extends('admin.layout')

@section('css')
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
            		{{ trans('general.states') }}
            			<i class="fa fa-angle-right margin-separator"></i>
            				{{ trans('general.add_new') }}
                  </h4>
                </section>

        <!-- Main content -->
        <section class="content">

        	<div class="content">

        		<div class="row">

        	<div class="box box-danger">
                <div class="box-header with-border">
                  <h3 class="box-title">{{ trans('general.add_new') }}</h3>
                </div><!-- /.box-header -->

                <div class="callout callout-info">
                  <p><i class="icon fa fa-info-circle myicon-right"></i> {{ __('general.alert_store_state') }}</p>
                </div>

                <!-- form start -->
                <form class="form-horizontal" method="post" action="{{ url('panel/admin/states/add') }}" enctype="multipart/form-data">

                	<input type="hidden" name="_token" value="{{ csrf_token() }}">

					@include('errors.errors-forms')

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('general.country') }}</label>
              <div class="col-sm-10">
                <select name="country" class="form-control custom-select select2" id="country">
                  @foreach (Countries::orderBy('country_name')->get() as $country)
                    <option value="{{$country->id}}">{{ $country->country_name }}</option>
                  @endforeach
                  </select>
              </div>
            </div>
          </div><!-- /.box-body -->

                 <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('admin.name') }}</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ old('name') }}" name="name" class="form-control" placeholder="{{ trans('admin.name') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                   <div class="box-body">
                     <div class="form-group">
                       <label class="col-sm-2 control-label">{{ trans('general.iso_code') }}</label>
                       <div class="col-sm-10">
                         <input type="text" value="{{ old('iso_code') }}" name="iso_code" class="form-control" placeholder="{{ trans('general.iso_code') }} (NY)">
                         <p class="help-block">{{ trans('general.iso_code_states') }} <a href="https://en.wikipedia.org/wiki/ISO_3166-2" target="_blank">(ISO 3166-2 subdivision code)</a></p>
                       </div>
                     </div>
                   </div><!-- /.box-body -->

                  <div class="box-footer">
                    <a href="{{ url('panel/admin/states') }}" class="btn btn-default">{{ trans('admin.cancel') }}</a>
                    <button type="submit" class="btn btn-success pull-right">{{ trans('admin.save') }}</button>
                  </div><!-- /.box-footer -->
                </form>
              </div>
        		</div><!-- /.row -->
        	</div><!-- /.content -->
        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
@endsection
