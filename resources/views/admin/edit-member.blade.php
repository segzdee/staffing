@extends('admin.layout')

@section('css')
<link href="{{ asset('plugins/iCheck/all.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
            {{ trans('admin.admin') }}
            	<i class="fa fa-angle-right margin-separator"></i>
            		{{ trans('admin.edit') }}

            		<i class="fa fa-angle-right margin-separator"></i>
            		{{ $user->name }}
              </h4>
            </section>

        <!-- Main content -->
        <section class="content">

        	<div class="content">

       <div class="row">

       	<div class="col-md-9">

        	<div class="box box-danger">
                <!-- form start -->
                <form class="form-horizontal" method="POST" action="{{ url('panel/admin/members/edit/'.$user->id) }}" enctype="multipart/form-data">

                	<input type="hidden" name="_token" value="{{ csrf_token() }}">

					@include('errors.errors-forms')

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group margin-bottom-zero">
              <label class="col-sm-2 control-label">{{ trans('general.avatar') }}</label>
              <div class="col-sm-10">
                <img src="{{Helper::getFile(config('path.avatar').$user->avatar)}}" width="auto" class="img-rounded margin-zero img-responsive" style="max-width: 200px;">
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('auth.name') }}</label>
              <div class="col-sm-10">
                <input type="text"  disabled value="{{ $user->name }}" class="form-control" placeholder="{{ trans('auth.name') }}">
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('auth.username') }}</label>
              <div class="col-sm-10">
                <input type="text"  disabled value="{{ $user->username }}" class="form-control" placeholder="{{ trans('auth.username') }}">
              </div>
            </div>
          </div><!-- /.box-body -->

          <!-- Start Box Body -->
          <div class="box-body">
            <div class="form-group">
              <label class="col-sm-2 control-label">{{ trans('auth.email') }}</label>
              <div class="col-sm-10">
                <input type="text" name="email" value="{{ $user->email }}" class="form-control" placeholder="{{ trans('auth.email') }}">
              </div>
            </div>
          </div><!-- /.box-body -->

        <!-- Start Box Body -->
        <div class="box-body">
          <div class="form-group">
            <label class="col-sm-2 control-label">{{ trans('admin.verified') }} ({{trans('general.creator')}})</label>
            <div class="col-sm-10">
            	<select name="verified" class="form-control">
                  <option @if ($user->verified_id == 'no') selected="selected" @endif value="no">{{ trans('admin.pending') }}</option>
	  	<option @if ($user->verified_id == 'yes') selected="selected" @endif value="yes">{{ trans('admin.verified') }}</option>
	  	<option @if ($user->verified_id == 'reject') selected="selected" @endif value="reject">{{ trans('admin.reject') }}</option>
                </select>
            </div>
          </div>
        </div><!-- /.box-body -->

        <!-- Start Box Body -->
        <div class="box-body">
          <div class="form-group">
            <label class="col-sm-2 control-label">{{ trans('admin.status') }}</label>
            <div class="col-sm-10">
            	<select name="status" class="form-control">
                  <option @if ($user->status == 'pending') selected="selected" @endif value="pending">{{ trans('admin.pending') }}</option>
	  	<option @if ($user->status == 'active') selected="selected" @endif value="active">{{ trans('admin.active') }}</option>
	  	<option @if ($user->status == 'suspended') selected="suspended" @endif value="suspended">{{ trans('admin.suspended') }}</option>

                </select>
            </div>
          </div>
        </div><!-- /.box-body -->

        @if ($user->verified_id == 'yes')
          <!-- Start Box Body -->
         <div class="box-body">
           <div class="form-group">
             <label class="col-sm-2 control-label">{{ trans('general.custom_fee') }}</label>
             <div class="col-sm-10">
               <select name="custom_fee" class="form-control">
                 <option @if ($user->custom_fee == 0) selected="selected" @endif value="0" >{{__('general.none')}}</option>
                 @for ($i=1; $i <= 50; ++$i)
                   <option @if ($user->custom_fee == $i) selected="selected" @endif value="{{$i}}">{{$i}}%</option>
                   @endfor
                   </select>
             </div>
           </div>
         </div><!-- /.box-body -->

        <!-- Start Box Body -->
        <div class="box-body">
          <div class="form-group">
            <label class="col-sm-2 control-label">{{ trans('general.featured')  }}</label>
            <div class="col-sm-10">

            	<div class="radio">
              <label class="padding-zero">
                <input type="radio" name="featured" @if ($user->featured == 'yes') checked="checked" @endif value="yes">
                {{ trans('general.yes')  }}
              </label>
            </div>

            <div class="radio">
              <label class="padding-zero">
                <input type="radio" name="featured" @if ($user->featured == 'no') checked="checked" @endif value="no">
               {{ trans('general.no')  }}
              </label>
            </div>

            </div>
          </div>
        </div><!-- /.box-body -->
        @endif

        <!-- Start Box Body -->
        <div class="box-body">
          <div class="form-group">
            <label class="col-sm-2 control-label">{{ trans('general.wallet') }}</label>
            <div class="col-sm-10">
              <input type="text" autocomplete="off" value="{{ $user->wallet }}" name="wallet" class="form-control isNumber" placeholder="{{ trans('general.wallet') }}">
            </div>
          </div>
        </div><!-- /.box-body -->

        <div class="box-footer">
        	 <a href="{{ url('panel/admin/members') }}" class="btn btn-default">{{ trans('admin.cancel') }}</a>
          <button type="submit" class="btn btn-success pull-right">{{ trans('admin.save') }}</button>
        </div><!-- /.box-footer -->
      </form>
    </div>

</div><!-- /. col-md-9 -->

    <div class="col-md-3">
      <div class="block-block text-center">

      <a href="{{url($user->username)}}" target="_blank"class="btn btn-lg btn-success btn-block margin-bottom">
        {{trans('general.go_to_page')}} <i class="fa fa-external-link-square-alt margin-left-5"></i>
      </a>

      <a href="{{url('panel/admin/members/roles-and-permissions', $user->id)}}" class="btn btn-lg btn-primary btn-block margin-bottom">
        {{trans('general.role_and_permissions')}}
      </a>

      @if ($user->status == 'pending')
        <a href="{{url('panel/admin/resend/email', $user->id)}}" class="btn btn-lg btn-default btn-block margin-bottom">
          {{trans('general.resend_confirmation_email')}}
        </a>
      @endif

      {!! Form::open([
            'method' => 'post',
            'url' => ['panel/admin/login/user', $user->id],
            'class' => 'displayInline'
          ]) !!}
  	        {!! Form::submit(trans('general.login_as_user'), ['class' => 'btn btn-lg btn-info btn-block margin-bottom loginAsUser']) !!}
  	    {!! Form::close() !!}

  		{!! Form::open([
            'method' => 'post',
            'url' => url('panel/admin/members', $user->id),
            'class' => 'displayInline'
          ]) !!}
  	        {!! Form::submit(trans('admin.delete'), ['data-url' => $user->id, 'class' => 'btn btn-lg btn-danger btn-block margin-bottom actionDelete']) !!}
  	    {!! Form::close() !!}
	        </div>

          @php

          if ($user->status == 'pending') {
            $_status = trans('admin.pending');
          } elseif ($user->status == 'active') {
            $_status = trans('admin.active');
          } else {
            $_status = trans('admin.suspended');
          }

        @endphp

          <ol class="list-group">
            <li class="list-group-item border-none"> <strong>{{trans('admin.registered')}}</strong> <span class="pull-right color-strong">{{ Helper::formatDate($user->date) }}</span></li>
            <li class="list-group-item border-none"> <strong>{{trans('admin.status')}}</strong> <span class="pull-right color-strong">{{ $_status }}</span></li>
            <li class="list-group-item border-none"> <strong>{{trans('admin.role')}}</strong> <span class="pull-right color-strong">{{ $user->role == 'admin' ? trans('admin.role_admin') : trans('admin.normal') }}</span></li>
            <li class="list-group-item border-none"> <strong>{{trans('general.country')}}</strong> <span class="pull-right color-strong">@if ($user->countries_id != '') {{ $user->country()->country_name }} @else {{ trans('admin.not_established') }} @endif</span></li>
            <li class="list-group-item border-none"> <strong>{{trans('general.gender')}}</strong> <span class="pull-right color-strong">{{ $user->gender ? __('general.'.$user->gender) : __('general.not_specified') }}</span></li>
            <li class="list-group-item border-none"> <strong>{{trans('general.birthdate')}}</strong> <span class="pull-right color-strong">{{ $user->birthdate ? $user->birthdate : __('general.no_available') }}</span></li>
          </ol>

        </div><!-- col-md-3 -->
  		</div><!-- /.row -->
  	</div><!-- /.content -->
  </section><!-- /.content -->
</div><!-- /.content-wrapper -->
@endsection
