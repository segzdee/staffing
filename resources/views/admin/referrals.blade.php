@extends('admin.layout')

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
           {{ trans('admin.admin') }} <i class="fa fa-angle-right margin-separator"></i> {{ trans('general.referrals') }} ({{$data->total()}})
          </h4>

        </section>

        <!-- Main content -->
        <section class="content">

        	<div class="row">
            <div class="col-xs-12">
              <div class="box">
                <div class="box-header">
                  <h3 class="box-title">
                  		{{ trans('general.referrals') }}
                  	</h3>
                </div><!-- /.box-header -->

                <div class="box-body table-responsive no-padding">
             <table class="table table-hover">
               <tbody>

               	@if( $data->total() !=  0 && $data->count() != 0 )
                   <tr>
                      <th class="active">ID</th>
                      <th class="active">{{ trans('admin.user') }}</th>
                      <th class="active">{{ trans('general.referred_by') }}</th>
                      <th class="active">{{ trans('general.earnings') }}</th>
                      <th class="active">{{ trans('admin.date') }}</th>
                    </tr><!-- /.TR -->


                  @foreach ($data as $referral)
                    <tr>
                      <td>{{ $referral->id }}</td>
                      <td>
                        @if (isset($referral->user()->username))
                        <a href="{{url($referral->user()->username)}}" target="_blank">
                          {{$referral->user()->name}} <i class="fa fa-external-link-alt"></i>
                        </a>
                      @else
                        <em>{{ trans('general.no_available') }}</em>
                      @endif
                      </td>
                      <td>
                        @if (isset($referral->referredBy()->username))
                        <a href="{{url($referral->referredBy()->username)}}" target="_blank">
                          {{$referral->referredBy()->name}} <i class="fa fa-external-link-alt"></i>
                        </a>
                      @else
                        <em>{{ trans('general.no_available') }}</em>
                      @endif
                      </td>
                      <td>{{ Helper::amountFormatDecimal($referral->earnings()) }}</td>
                      <td>{{date($settings->date_format, strtotime($referral->created_at))}}</td>
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
