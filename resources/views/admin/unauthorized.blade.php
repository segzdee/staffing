@extends('admin.layout')

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h1>
           {{ trans('admin.dashboard') }} v{{$settings->version}}
         </h1>
        </section>

        <!-- Main content -->
        <section class="content">

        	<div class="row">
            <div class="col-xs-12">
              <div class="box">

                <div class="box-body table-responsive no-padding">
                  <span style="font-size: 100px; width: 100%; display:block; text-align:center; color:#ffc107"><i class="fas fa-exclamation-triangle"></i></span>
                  <h4 class="text-center no-found">{{ trans('general.unauthorized_section') }}</h4>
                </div><!-- /.box-body -->
              </div><!-- /.box -->
            </div>
          </div>
        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
@endsection
