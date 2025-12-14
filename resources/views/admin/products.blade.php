@extends('admin.layout')

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
           {{ trans('admin.admin') }} <i class="fa fa-angle-right margin-separator"></i> {{ trans('general.products') }} ({{$data->total()}})
          </h4>

        </section>

        <!-- Main content -->
        <section class="content">

        	<div class="row">
            <div class="col-xs-12">
              <div class="box">
                <div class="box-header">
                  <h3 class="box-title">
                  		{{ trans('general.products') }}
                  	</h3>
                </div><!-- /.box-header -->

                <div class="box-body table-responsive no-padding">
             <table class="table table-hover">
               <tbody>

               	@if( $data->total() !=  0 && $data->count() != 0 )
                   <tr>
                      <th class="active">ID</th>
                      <th class="active">{{ trans('general.item') }}</th>
                      <th class="active">{{ trans('general.creator') }}</th>
                      <th class="active">{{trans('admin.type')}}</th>
                      <th class="active">{{trans('general.price')}}</th>
                      <th class="active">{{trans('general.sales')}}</th>
                      <th class="active">{{ trans('admin.date') }}</th>
                      <th class="active">{{ trans('admin.actions') }}</th>
                    </tr><!-- /.TR -->


                  @foreach ($data as $product)
                    <tr>
                      <td>{{ $product->id }}</td>
                      <td>
                        <a href="{{url('shop/product', $product->id)}}" target="_blank">
                          {{ Str::limit($product->name, 25, '...') }} <i class="fa fa-external-link-alt"></i>
                        </a>
                        </td>
                      <td>
                        @if (isset($product->user()->username))
                        <a href="{{url($product->user()->username)}}" target="_blank">
                          {{$product->user()->name}} <i class="fa fa-external-link-alt"></i>
                        </a>
                      @else
                        <em>{{ trans('general.no_available') }}</em>
                      @endif
                      </td>
                      <td>
                        @if($product->type == 'digital')
                          <a href="{{ url('product/download', $product->id) }}">{{ __('general.digital_download') }}</a>
                        @elseif($product->type == 'physical')
                          {{ __('general.physical_product') }}
                        @elseif($product->type == 'custom')
                          {{ __('general.custom_content') }}
                        @else
                          Unknown
                        @endif
                      </td>

                      <td>{{ Helper::amountFormatDecimal($product->price) }}</td>
                      <td>{{ $product->purchases->count() }}</td>
                      <td>{{date($settings->date_format, strtotime($product->created_at))}}</td>

                      <td>
                        {!! Form::open([
                          'method' => 'POST',
                          'url' => url('panel/admin/product/delete', $product->id),
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
              @if ($data->lastPage() > 1)
             {{ $data->links() }}
             @endif
            </div>
          </div>

          <!-- Your Page Content Here -->

        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
@endsection
