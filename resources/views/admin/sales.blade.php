@extends('admin.layout')

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
           {{ trans('admin.admin') }} <i class="fa fa-angle-right margin-separator"></i> {{ trans('general.sales') }} ({{$sales->total()}})
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
                  <h3 class="box-title">
                  		{{ trans('general.sales') }}
                  	</h3>
                </div><!-- /.box-header -->

                <div class="box-body table-responsive no-padding">
             <table class="table table-hover">
               <tbody>

               	@if( $sales->total() !=  0 && $sales->count() != 0 )
                   <tr>
                      <th class="active">ID</th>
                      <th class="active">{{trans('general.item')}}</th>
                      <th class="active">{{trans('general.creator')}}</th>
                      <th class="active">{{trans('general.buyer')}}</th>
                      <th class="active">{{trans('general.delivery_status')}}</th>
                      <th class="active">{{trans('general.price')}}</th>
                      <th class="active">{{trans('admin.date')}}</th>
                      <th class="active">{{trans('admin.actions')}}</th>
                    </tr><!-- /.TR -->

                    @foreach ($sales as $sale)
                      <tr>
                        <td>
                          {{ $sale->id }}
                        </td>
                        <td>
                          <a href="{{url('shop/product', $sale->products()->id)}}">
                            {{ Str::limit($sale->products()->name, 25, '...') }}
                          </a>
                          </td>
                          <td>
                            @if (! isset($sale->products()->user()->username))
                              {{ trans('general.no_available') }}
                            @else
                            <a href="{{ url($sale->products()->user()->username) }}" target="_blank">{{ '@'.$sale->products()->user()->username }}</a>
                          @endif
                          </td>
                          <td>
                            @if (! isset($sale->user()->username))
                              {{ trans('general.no_available') }}
                            @else
                            <a href="{{ url($sale->user()->username) }}" target="_blank">{{ '@'.$sale->user()->username }}</a>
                          @endif
                          </td>
                          <td>
                            @if ($sale->delivery_status == 'delivered')
                              <span class="label label-success text-uppercase">{{ __('general.delivered') }}</span>

                            @else
                              <span class="label label-warning text-uppercase">{{ __('general.pending') }}</span>
                            @endif
                          </td>
                        <td>{{ Helper::amountFormatDecimal($sale->transactions()->amount) }}</td>
                        <td>{{Helper::formatDate($sale->created_at)}}</td>

                        <td>
                          @if ($sale->products()->type == 'custom')
                          <div style="display: flex">

                            <a title="{{ __('general.see_details') }}" class="d-inline-block myicon-right btn btn-primary btn-sm-custom" data-toggle="modal" data-target="#customContentForm{{$sale->id}}" href="#">
                            <i class="fa fa-eye"></i>
                            </a>
                            @endif

                            <form class="d-inline-block" method="post" action="{{ url('panel/admin/sales/refund', $sale->id) }}">
                              @csrf
                              <button title="{{ __('general.refund') }}" class="btn btn-danger btn-sm-custom actionRefund" type="button">
                                {{ __('general.refund') }}
                              </button>
                            </form>
                        </td>
                      </tr>

                      @include('includes.modal-custom-content')

                    @endforeach

                    @else
                    <hr />
                    	<h3 class="text-center no-found">{{ trans('general.no_results_found') }}</h3>

                    @endif

                  </tbody>

                  </table>

                </div><!-- /.box-body -->
              </div><!-- /.box -->
              @if ($sales->lastPage() > 1)
             {{ $sales->links() }}
             @endif
            </div>
          </div>

          <!-- Your Page Content Here -->

        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
@endsection
