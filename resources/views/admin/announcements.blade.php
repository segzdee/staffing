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
            		{{ trans('general.announcements') }}
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
                <!-- form start -->
                <form class="form-horizontal" method="POST" action="{{ url()->current() }}" enctype="multipart/form-data">

                	<input type="hidden" name="_token" value="{{ csrf_token() }}">

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.announcement_content') }}</label>
                      <div class="col-sm-10">

                      	<textarea name="announcement_content" rows="8" class="form-control" placeholder="{{ trans('general.announcement_content') }}">{{ $settings->announcement }}</textarea>
                        <span class="help-block">{{ trans('general.announcement_info') }}</span>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.type_announcement') }}</label>
                      <div class="col-sm-10">

                      	<div class="radio">
                        <label class="padding-zero">
                          <input type="radio" name="type_announcement" @if( $settings->type_announcement == 'primary' ) checked="checked" @endif value="primary" checked>
                          {{ trans('general.informative') }}
                        </label>
                      </div>

                      <div class="radio">
                        <label class="padding-zero">
                          <input type="radio" name="type_announcement" @if( $settings->type_announcement == 'danger' ) checked="checked" @endif value="danger">
                          {{ trans('general.important') }}
                        </label>
                      </div>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.show_announcement_to') }}</label>
                      <div class="col-sm-10">

                      	<div class="radio">
                        <label class="padding-zero">
                          <input type="radio" name="announcement_show" @if( $settings->announcement_show == 'all' ) checked="checked" @endif value="all" checked>
                          {{ trans('general.all_users') }}
                        </label>
                      </div>

                      <div class="radio">
                        <label class="padding-zero">
                          <input type="radio" name="announcement_show" @if( $settings->announcement_show == 'creators' ) checked="checked" @endif value="creators">
                          {{ trans('general.only_creators') }}
                        </label>
                      </div>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <div class="box-footer">
                    <button type="submit" class="btn btn-success">{{ trans('admin.save') }}</button>
                  </div><!-- /.box-footer -->
                </form>
              </div>
        		</div><!-- /.row -->
        	</div><!-- /.content -->
        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
@endsection
