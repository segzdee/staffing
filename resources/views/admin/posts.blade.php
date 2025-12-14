@extends('admin.layout')

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
           {{ trans('admin.admin') }} <i class="fa fa-angle-right margin-separator"></i> {{ trans('general.posts') }} ({{$data->total()}})
          </h4>
        </section>

        <!-- Main content -->
        <section class="content">

		    @if (Session::has('success_message'))
		    <div class="alert alert-success">
		    	<button type="button" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">×</span>
								</button>
		      <i class="fa fa-check margin-separator"></i> {{ Session::get('success_message') }}
		    </div>
		@endif

        	<div class="row">
            <div class="col-xs-12">
              <div class="box">
                <div class="box-header">

                  @if ($data->total() !=  0 && $data->count() != 0)
                  <form action="{{ url('panel/admin/posts') }}" id="formSort" method="get">
                     <select name="sort" id="sort" class="form-control input-sm" style="width: auto; padding-right: 20px;">
   	                    <option @if ($sort == '') selected="selected" @endif value="">{{ trans('admin.sort_id') }}</option>
   	                    <option @if ($sort == 'pending') selected="selected" @endif value="pending">{{ trans('admin.pending') }}</option>
   	                  </select>
   	                  </form><!-- form -->
                    @endif

                </div><!-- /.box-header -->

                <div class="box-body table-responsive no-padding">
                  <table class="table table-hover">
               <tbody>

               	@if ($data->count() !=  0)
                   <tr>
                      <th class="active">ID</th>
                      <th class="active">{{ trans('admin.content') }}</th>
                      <th class="active">{{ trans('admin.description') }}</th>
                      <th class="active">{{ trans('auth.username') }}</th>
                      <th class="active">{{ trans('admin.date') }}</th>
                      <th class="active">{{ trans('admin.status') }}</th>
                      <th class="active">{{ trans('admin.actions') }}</th>
                    </tr>

                  @foreach ($data as $update)

                    @php
                      $allFiles = $update->media()->groupBy('type')->get();
                    @endphp

                    <tr>
                      <td>{{ $update->id }}</td>

                      <td>
                        @if ($allFiles->count() != 0)
                        	@foreach ($allFiles as $media)

                        		@if ($media->type == 'image')
                        			<i class="far fa-image myicon-right"></i>
                        		@endif

                        		@if ($media->type == 'video')
                        			<i class="far fa-play-circle myicon-right"></i>
                        		@endif

                        		@if ($media->type == 'music')
                        			<i class="fa fa-microphone myicon-right"></i>
                        			@endif

                        			@if ($media->type == 'file')
                        		<i class="far fa-file-archive"></i>
                        		@endif

                        	@endforeach

                        @else
                          <i class="fa fa-font"></i>
                        @endif
                      </td>

                      <td>{{ str_limit($update->description, 50, '...') }}</td>
                      <td>
                        @if (isset($update->user()->username))
                          <a href="{{url($update->user()->username)}}" target="_blank">
                            {{$update->user()->username}} <i class="fa fa-external-link-square-alt"></i>
                          </a>
                        @else
                          <em>{{ trans('general.no_available') }}</em>
                        @endif

                        </td>
                      <td>{{ Helper::formatDate($update->date) }}</td>
                      <td>
                        <span class="label label-{{ $update->status == 'active' ? 'success' : ($update->status == 'pending' ? 'warning' : 'info') }}">
                          {{ $update->status == 'active' ? trans('admin.active') :  ($update->status == 'pending' ? trans('admin.pending') : trans('general.encode')) }}
                        </span>
                        </td>
                      <td>
                        @if (isset($update->user()->username) && $update->status != 'encode')
                      	<a href="{{ url($update->user()->username, 'post').'/'.$update->id }}" target="_blank" class="btn btn-success btn-sm padding-btn">
                      		{{ trans('admin.view') }} <i class="fa fa-external-link-square-alt"></i>
                      	</a>
                      @endif

                        @if ($update->status == 'pending')
                        {!! Form::open([
                          'method' => 'POST',
                          'url' => "panel/admin/posts/approve/$update->id",
                          'class' => 'displayInline'
                        ]) !!}

                        {!! Form::button(trans('admin.approve'), ['class' => 'btn btn-success btn-sm padding-btn actionApprovePost']) !!}
                        {!! Form::close() !!}
                        @endif

                       {!! Form::open([
                         'method' => 'POST',
                         'url' => "panel/admin/posts/delete/$update->id",
                         'class' => 'displayInline'
                       ]) !!}

                       @if ($update->status == 'active' || $update->status == 'encode')
                         {!! Form::button(trans('admin.delete'), ['class' => 'btn btn-danger btn-sm padding-btn actionDelete']) !!}

                       @else
                         {!! Form::button(trans('general.reject'), ['class' => 'btn btn-danger btn-sm padding-btn actionDeletePost']) !!}
                       @endif

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
              @if ($data->hasPages())
                {{ $data->appends(['sort' => $sort])->links() }}
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
