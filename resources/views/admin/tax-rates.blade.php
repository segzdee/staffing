@extends('admin.layout')

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
           {{ trans('admin.admin') }} <i class="fa fa-angle-right margin-separator"></i> {{ trans('general.tax_rates') }}

           <a href="{{ url('panel/admin/tax-rates/add') }}" class="btn btn-sm btn-success no-shadow">
             <i class="glyphicon glyphicon-plus myicon-right"></i> {{ trans('general.add_new') }}
           </a>
          </h4>

        </section>

        <!-- Main content -->
        <section class="content">

		    @if (session('success_message'))
		    <div class="alert alert-success">
		    	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">×</span>
								</button>
		        {{ session('success_message') }}
		    </div>
		@endif

        	<div class="row">
            <div class="col-xs-12">
              <div class="box">
                <div class="box-header">
                  <h3 class="box-title"> {{ trans('general.tax_rates') }}</h3>
                </div><!-- /.box-header -->

                <div class="box-body table-responsive no-padding">
                  <table class="table table-hover">
               <tbody>

               	@if ($taxes->count() !=  0)
                   <tr>
                      <th class="active">{{ trans('admin.name') }}</th>
                      <th class="active">{{ trans('general.country') }}</th>
                      <th class="active">{{ trans('general.percentage') }}</th>
                      <th class="active">{{ trans('admin.status') }}</th>
                      <th class="active">{{ trans('admin.actions') }}</th>
                    </tr>

                  @foreach ($taxes as $tax)
                    <tr>
                      <td>{{ $tax->name }}</td>
                      <td>{{ $tax->country()->country_name }} @if ($tax->state) - {{ $tax->state }} @endif</td>
                      <td>{{ $tax->percentage }}</td>
                      <td><span class="label label-{{ $tax->status ? 'success' : 'default' }}">
                        {{ $tax->status ? trans('general.enabled') : trans('general.disabled') }}</span>
                      </td>
                      <td>
                      	<a href="{{ url('panel/admin/tax-rates/edit', $tax->id) }}" class="btn btn-success btn-sm padding-btn">
                      		{{ trans('admin.edit') }}
                      	</a>
                      </td>

                    </tr><!-- /.TR -->
                    @endforeach

                    @else
                    <hr />
                    	<h3 class="text-center no-found">{{ trans('general.no_results_found') }}</h3>
                    @endif

                  </tbody>
                  </table>
                </div><!-- /.box-body -->
              </div><!-- /.box -->
            </div>
          </div>
        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
@endsection
