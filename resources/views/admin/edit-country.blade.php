@extends('admin.layout')

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
            {{ trans('admin.admin') }}
            	<i class="fa fa-angle-right margin-separator"></i>
            		{{ trans('general.countries') }}
            			<i class="fa fa-angle-right margin-separator"></i>
            				{{ trans('admin.edit') }}
                  </h4>
                </section>

        <!-- Main content -->
        <section class="content">

        	<div class="content">

        		<div class="row">

        	<div class="box box-danger">
                <div class="box-header with-border">
                  <h3 class="box-title">{{ trans('admin.edit') }}</h3>
                </div><!-- /.box-header -->

                <!-- form start -->
                <form class="form-horizontal" method="post" action="{{ url('panel/admin/countries/update') }}" enctype="multipart/form-data">

                	<input type="hidden" name="_token" value="{{ csrf_token() }}">
                  <input type="hidden" name="id" value="{{ $country->id }}">

					@include('errors.errors-forms')

                 <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('admin.name') }}</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ $country->country_name }}" name="name" class="form-control" placeholder="{{ trans('admin.name') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                   <div class="box-body">
                     <div class="form-group">
                       <label class="col-sm-2 control-label">{{ trans('general.iso_code') }}</label>
                       <div class="col-sm-10">
                         <input type="text" value="{{ $country->country_code }}" name="iso_code" class="form-control" placeholder="{{ trans('general.iso_code') }} (US)">
                         <p class="help-block">{{ trans('general.iso_code_country') }} <a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2" target="_blank">(ISO 3166-1 alpha-2)</a></p>
                       </div>
                     </div>
                   </div><!-- /.box-body -->

                  <div class="box-footer">
                    <a href="{{ url('panel/admin/countries') }}" class="btn btn-default">{{ trans('admin.cancel') }}</a>
                    <button type="submit" class="btn btn-success pull-right">{{ trans('admin.save') }}</button>
                  </div><!-- /.box-footer -->
                </form>
              </div>
        		</div><!-- /.row -->
        	</div><!-- /.content -->
        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
@endsection
