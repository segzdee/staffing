@extends('admin.layout')

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
           {{ trans('admin.admin') }} <i class="fa fa-angle-right margin-separator"></i> {{ trans('admin.members') }} ({{$data->total()}})
          </h4>

        </section>

        <!-- Main content -->
        <section class="content">

      @if (Session::has('info_message'))
		    <div class="alert alert-warning">
		    	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">×</span>
								</button>
		      <i class="fa fa-warning margin-separator"></i>  {{ Session::get('info_message') }}
		    </div>
		@endif

		@if (Session::has('success_message'))
		    <div class="alert alert-success">
		    	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">×</span>
								</button>
		       <i class="fa fa-check margin-separator"></i>  {{ Session::get('success_message') }}
		    </div>
		@endif

        	<div class="row">
            <div class="col-xs-12">
              <div class="box">
                <div class="box-header padding-left-zero padding-right-zero">

                  <form action="{{ url('panel/admin/members') }}" id="formSort" method="get">
                     <select name="sort" id="sort" class="form-control input-sm" style="width: auto; padding-right: 20px;">
   	                    <option @if ($sort == '') selected="selected" @endif value="">{{ trans('admin.sort_id') }}</option>
                        <option @if ($sort == 'admins') selected="selected" @endif value="admins">{{ trans('users.admin') }}</option>
                          <option @if ($sort == 'creators') selected="selected" @endif value="creators">{{ trans('general.creators') }}</option>
           					  	<option @if ($sort == 'email_pending') selected="selected" @endif value="email_pending">{{ trans('general.verification_pending') }} ({{trans('auth.email')}})</option>
   	                  </select>
   	                  </form><!-- form -->

                <div class="search-box">
                 @if( $data->total() !=  0 )
                    <!-- form -->
                    <form role="search" autocomplete="off" action="{{ url('panel/admin/members') }}" method="get">
	                 <div class="input-group input-group-sm">
	                  <input type="text" name="q" class="form-control pull-right" placeholder="{{ trans('general.search') }}">

	                  <div class="input-group-btn">
	                    <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
	                  </div>
	                </div>
                </form><!-- form -->
                @endif
              </div>

            </div><!-- /.box-header -->

                <div class="box-body table-responsive no-padding">
                  <table class="table table-hover">
               <tbody>

               	@if( $data->total() !=  0 && $data->count() != 0 )
                   <tr>
                      <th class="active">ID</th>
                      <th class="active">{{ trans('auth.full_name') }}</th>
                      <th class="active">{{ trans('auth.email') }}</th>
                      <th class="active">{{ trans('general.balance') }}</th>
                      <th class="active">{{ trans('general.wallet') }}</th>
                      <th class="active">{{ trans('general.posts') }}</th>
                      <th class="active">{{ trans('admin.date') }}</th>
                      <th class="active">IP</th>
                      <th class="active">{{ trans('admin.role') }}</th>
                      <th class="active">{{ trans('admin.verified') }}</th>
                      <th class="active">{{ trans('admin.status') }}</th>
                      <th class="active">{{ trans('admin.actions') }}</th>
                    </tr>

                  @foreach( $data as $user )
                    <tr>
                      <td>{{ $user->id }}</td>
                      <td title="{{$user->name}}">
                        <a href="{{ url($user->username) }}" target="_blank">
                        <img src="{{Helper::getFile(config('path.avatar').$user->avatar)}}" width="40" height="40" class="img-circle" />
                        {{ str_limit($user->name, 20, '...') }}
                        </a>
                      </td>
                      <td title="{{$user->email}}">{{ str_limit($user->email, 20, '...') }}</td>
                      <td>{{ Helper::amountFormatDecimal($user->balance) }}</td>
                      <td>{{ Helper::amountFormatDecimal($user->wallet) }}</td>
                      <td>{{ $user->updates()->count() }}</td>
                      <td>{{ Helper::formatDate($user->date) }}</td>
                      <td>{{ $user->ip ? $user->ip : trans('general.no_available') }}</td>
                      <td>
                        @if ($user->role == 'admin' && $user->permissions == 'full_access')
                          <span class="label label-primary">{{ trans('general.super_admin') }}</span>
                        @elseif ($user->role == 'admin' && $user->permissions != 'full_access')
                            <span class="label label-primary">{{ trans('admin.role_admin') }}</span>
                        @else
                          <span class="label label-default">{{ trans('admin.normal') }}</span>
                        @endif
                      </td>

                <?php if( $user->verified_id == 'no' ) {
                       			$verified    = 'warning';
 								$_verified = trans('admin.pending');
              } elseif( $user->verified_id == 'yes' ) {
                       			$verified = 'success';
 								$_verified = trans('admin.verified');
                       		} else {
                       			$verified = 'danger';
 								$_verified = trans('admin.reject');
                       		}
                       		?>
                       <td><span class="label label-{{$verified}}">{{ $_verified }}</span></td>

                      <?php if( $user->status == 'pending' ) {
                       			$mode    = 'warning';
 								$_status = trans('admin.pending');
                       		} elseif( $user->status == 'active' ) {
                       			$mode = 'success';
 								$_status = trans('admin.active');
                       		} else {
                       			$mode = 'warning';
 								$_status = trans('admin.suspended');
                       		}
                       		?>
                       <td><span class="label label-{{$mode}}">{{ $_status }}</span></td>
                      <td>

                     @if ($user->id <> Auth::user()->id && $user->id <> 1)

                   <a href="{{ url('panel/admin/members/edit', $user->id) }}" class="btn btn-success btn-sm padding-btn">
                      		{{ trans('admin.edit') }}
                      	</a>

                   {!! Form::open([
			            'method' => 'post',
			            'url' => url('panel/admin/members', $user->id),
			            'id' => 'form'.$user->id,
			            'class' => 'displayInline'
				        ]) !!}
	            	{!! Form::submit(trans('admin.delete'), ['data-url' => $user->id, 'class' => 'btn btn-danger btn-sm padding-btn actionDelete']) !!}
	        	{!! Form::close() !!}

	       @else
	        ------------
                      		@endif

                      		</td>

                    </tr><!-- /.TR -->
                    @endforeach

                    @else
                    <hr />
                    	<h3 class="text-center no-found">{{ trans('general.no_results_found') }}</h3>

                    	@if( isset( $query ) )
                    	<div class="col-md-12 text-center padding-bottom-15">
                    		<a href="{{url('panel/admin/members')}}" class="btn btn-sm btn-danger">{{ trans('auth.back') }}</a>
                    	</div>

                    	@endif
                    @endif

                  </tbody>

                  </table>
                </div><!-- /.box-body -->
              </div><!-- /.box -->
              @if ($data->hasPages())
             {{ $data->appends(['q' => $query, 'sort' => $sort])->links() }}
           @endif
            </div>
          </div>
        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
@endsection

@section('javascript')

<script type="text/javascript">
$(document).on('change','#sort',function(){
	 	$('#formSort').submit();
	 });
</script>
@endsection
