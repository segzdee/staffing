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
                <form class="form-horizontal" method="post" action="{{ url('panel/admin/tax-rates/update') }}" enctype="multipart/form-data">

                	<input type="hidden" name="_token" value="{{ csrf_token() }}">
                  <input type="hidden" name="id" value="{{ $tax->id }}">

					@include('errors.errors-forms')

                 <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('admin.name') }}</label>
                      <div class="col-sm-10">
                        <input type="text" value="{{ $tax->name }}" name="name" class="form-control" placeholder="{{ trans('admin.name') }} (VAT, GST)">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.country') }}</label>
                      <div class="col-sm-10">
                      	<select disabled name="country" class="form-control custom-select select2" id="country">
                          @foreach (Countries::orderBy('country_name')->get() as $country)
                            <option @if ($tax->country == $country->country_code) selected @endif value="{{$country->id}}">{{ $country->country_name }}</option>
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
                      	<select disabled class="form-control custom-select" id="state">
                          @if ($tax->country()->states()->count())

                            @foreach ($tax->country()->states()->get() as $state)
                              <option @if ($tax->state && $tax->state == $state->name) selected @endif value="{{ $state->code }}">{{ $state->name }}</option>
                            @endforeach

                          @endif

                            <option value="" @if(! $tax->state) selected @endif>{{ trans('general.all_states') }}</option>
                          </select>
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('general.percentage') }}</label>
                      <div class="col-sm-10">
                        <input type="text" autocomplete="off" value="{{ $tax->percentage }}" disabled class="form-control isNumber" placeholder="{{ trans('general.percentage') }}">
                      </div>
                    </div>
                  </div><!-- /.box-body -->

                  <!-- Start Box Body -->
                  <div class="box-body">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">{{ trans('admin.status') }}</label>
                      <div class="col-sm-10">

                      	<div class="radio">
                        <label class="padding-zero">
                          <input type="radio" name="status" value="1" @if ($tax->status) checked @endif>
                          {{ trans('general.enabled') }}
                        </label>
                      </div>

                      <div class="radio">
                        <label class="padding-zero">
                          <input type="radio" name="status" value="0" @if (! $tax->status) checked @endif>
                          {{ trans('general.disabled') }}
                        </label>
                      </div>

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
