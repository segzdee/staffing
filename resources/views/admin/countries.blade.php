@extends('admin.layout')

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
           {{ trans('admin.admin') }} <i class="fa fa-angle-right margin-separator"></i> {{ trans('general.countries') }}

           <a href="{{ url('panel/admin/countries/add') }}" class="btn btn-sm btn-success no-shadow">
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
                  <h3 class="box-title"> {{ trans('general.countries') }}</h3>
                </div><!-- /.box-header -->

                <div class="box-body table-responsive no-padding">
                  <table class="table table-hover">
               <tbody>

               	@if ($countries->count() !=  0)
                   <tr>
                      <th class="active">ID</th>
                      <th class="active">{{ trans('general.iso_code') }}</th>
                      <th class="active">{{ trans('general.country') }}</th>
                      <th class="active">{{ trans('admin.actions') }}</th>
                    </tr>

                  @foreach ($countries as $country)
                    <tr>
                      <td>{{ $country->id }}</td>
                      <td>{{ $country->country_code }}</td>
                      <td>{{ $country->country_name }}</td>
                      <td>
                      	<a href="{{ url('panel/admin/countries/edit', $country->id) }}" class="btn btn-success btn-sm padding-btn">
                      		{{ trans('admin.edit') }}
                      	</a>

                        {!! Form::open([
                          'method' => 'POST',
                          'url' => "panel/admin/countries/delete/$country->id",
                          'class' => 'displayInline'
                        ]) !!}

                        {!! Form::button(trans('admin.delete'), ['class' => 'btn btn-danger btn-sm padding-btn actionDelete']) !!}
                        {!! Form::close() !!}
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

              @if ($countries->hasPages())
                 {{ $countries->links() }}
                 @endif

            </div>
          </div>
        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
@endsection
