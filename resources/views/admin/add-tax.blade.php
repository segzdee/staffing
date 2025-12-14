@extends('admin.layout')

@section('css')
<link href="{{ asset('plugins/iCheck/all.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('plugins/tagsinput/jquery.tagsinput.min.css') }}" rel="stylesheet" type="text/css" />
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
            		{{ trans('general.tax_rates') }}
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
                  <p><i class="icon fa fa-info-circle myicon-right"></i> {{ __('general.alert_store_tax') }}</p>
                </div>

                <!-- form start -->
                <form class="form-horizontal" method="post" action="{{ url('panel/admin/tax-rates/add') }}" enctype="multipart/form-data">

                	<input type="hidden" name="_token" value="{{ csrf_token() }}">

					@include('errors.errors-forms')

                 <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('admin.name') }}</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ old('name') }}" name="name" class="form-control" placeholder="{{ trans('admin.name') }} (VAT, GST)">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

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
                      <label class="col-sm-2 control-label">{{ trans('general.state') }}</label>
                      <div class="col-sm-10">
                      	<select name="state" class="form-control custom-select" id="state">
                            <option value="">{{ trans('general.all_states') }}</option>
                          </select>
                          <p class="help-block"><a href="{{ url('panel/admin/states/add') }}">{{ trans('general.create_state') }}</a></p>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.percentage') }}</label>
                      <div class="col-sm-10">
                        <input type="text" autocomplete="off" value="{{ old('percentage') }}" name="percentage" class="form-control isNumber" placeholder="{{ trans('general.percentage') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <div class="box-footer">
                    <a href="{{ url('panel/admin/tax-rates') }}" class="btn btn-default">{{ trans('admin.cancel') }}</a>
                    <button type="submit" class="btn btn-success pull-right">{{ trans('admin.save') }}</button>
                  </div><!-- /.box-footer -->
                </form>
              </div>
        		</div><!-- /.row -->
        	</div><!-- /.content -->
        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
@endsection

@section('javascript')
  <script>
  $('#country').change(function () {
    var id = $(this).find(':selected').val();
    $.ajax({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        },
        type: 'POST',
        url: URL_BASE+'/panel/admin/ajax/states',
        data: {
            'id': id
        },
        success: function (data) {
            // the next thing you want to do
            var $state = $('#state');
            $state.empty();

            for (var i = 0; i < data.length; i++) {
                $state.append('<option value=' + data[i].code + '>' + data[i].name + '</option>');
            }

            $state.append('<option value="">{{ trans('general.all_states') }}</option>');
        }
    });
});
  </script>
@endsection
