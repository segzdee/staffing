@extends('admin.layout')

@section('css')
<link href="{{ asset('plugins/iCheck/all.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('plugins/tagsinput/jquery.tagsinput.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
            {{ trans('admin.admin') }}
            	<i class="fa fa-angle-right margin-separator"></i>
            		{{ trans('admin.pages') }}
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


                <!-- form start -->
                <form class="form-horizontal" method="post" action="{{ url('panel/admin/pages/create') }}">

                	<input type="hidden" name="_token" value="{{ csrf_token() }}">

					@include('errors.errors-forms')

                 <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('admin.title') }}</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ old('title') }}" name="title" class="form-control" placeholder="{{ trans('admin.title') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('admin.slug') }}</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ old('slug') }}" name="slug" class="form-control" placeholder="{{ trans('admin.slug') }}">
                       <p class="help-block"><strong>{{ trans('general.important') }}: {{ trans('general.slug_lang_info') }}</strong></p>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('admin.keywords') }} (SEO)</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ old('keywords') }}" id="tagInput" name="keywords" placeholder="{{ trans('admin.keywords') }}" class="form-control">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('admin.description') }}</label>
                      <div class="col-sm-10">
                      	<textarea name="description" rows="4" id="description" class="form-control" placeholder="{{ trans('admin.description') }}">{{ old('description') }}</textarea>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.language') }}</label>
                      <div class="col-sm-10">
                      	<select name="lang" class="form-control">
                          @foreach (Languages::orderBy('name')->get() as $language)
                            <option @if ($language->abbreviation == session('locale')) selected="selected" @endif value="{{$language->abbreviation}}">{{ $language->name }}</option>
                          @endforeach
                          </select>
                          <p class="help-block">{{ trans('general.page_lang') }}</p>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.who_can_access_this_page') }}</label>
                      <div class="col-sm-10">
                      	<select name="access" class="form-control">
                          <option value="all">{{ __('general.all') }}</option>
                            <option value="members">{{ __('admin.only_users') }}</option>
                              <option value="creators">{{ __('general.only_creators') }}</option>
                          </select>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('admin.content') }}</label>
                      <div class="col-sm-10">

                      	<textarea name="content"rows="5" cols="40" id="content" class="form-control" placeholder="{{ trans('admin.content') }}">{{ old('content') }}</textarea>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <div class="box-footer">
                    <a href="{{ url('panel/admin/pages') }}" class="btn btn-default">{{ trans('admin.cancel') }}</a>
                    <button type="submit" class="btn btn-success pull-right">{{ trans('admin.save') }}</button>
                  </div><!-- /.box-footer -->
                </form>
              </div>

        		</div><!-- /.row -->

        	</div><!-- /.content -->

          <!-- Your Page Content Here -->

        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
@endsection

@section('javascript')
<script src="{{ asset('admin/js/ckeditor-init.js') }}?v={{$settings->version}}" type="text/javascript"></script>
@endsection
