<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="description" content="@yield('description_custom')@if(!Request::route()->named('seo') && !Request::route()->named('profile')){{trans('seo.description')}}@endif">
  <meta name="keywords" content="@yield('keywords_custom'){{ trans('seo.keywords') }}" />
  <meta name="theme-color" content="{{ auth()->check() && auth()->user()->dark_mode == 'on' ? '#303030' : $settings->color_default }}">
  <title>{{ auth()->check() && \App\Models\User::notificationsCount() ? '('. \App\Models\User::notificationsCount() .') ' : '' }}@section('title')@show @if( isset( $settings->title ) ){{$settings->title}}@endif</title>
  <!-- Favicon -->
  <script src="http://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="http://netdna.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <link href="{{ url('img', $settings->favicon) }}" rel="icon">
  <link rel="stylesheet" href="{{url('bootstrap-side-modals.css')}}">
  <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

  <link rel="stylesheet" href="{{url('imageuploadify.min.css')}}">
  
  {{-- Dashboard Design System --}}
  @if(auth()->check() && (request()->is('agency/*') || request()->is('worker/*') || request()->is('business/*') || request()->is('admin/*') || request()->is('dashboard*')))
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
  @endif

 
  <style>
    .upload-main-wrapper{
    width: 220px;
    
}
.sweet-alert h2{
 
  font-family: 'Poppins', sans-serif;
}
.sweet-alert p{
  font-family: 'Poppins', sans-serif;
  
}

.upload-wrapper {
    display: flex;
    align-items: center;
    justify-content: flex-start;
   
    position: relative;
    cursor: pointer;
    background-color: #c50279;
    padding: 8px 10px;
    border-radius: 4px;
    overflow: hidden;
    transition: 0.2s linear all;
    color: #ffffff;
    cursor: pointer;
}

/**** select multiple  *****/






#files{
  width: 100px !important;
  height: 80px !important;
}
.IMGthumbnail{
    max-width:168px;
    margin:auto;
  background-color: #ececec;
  padding:2px;
  border:none;
}

.IMGthumbnail img{
   max-width:100%;
max-height:100%;
}

.imgThumbContainer{

  margin:4px;
  border: solid;
  display: inline-block;
  justify-content: center;
    position: relative;
    border: 1px solid rgba(0,0,0,0.14);
  -webkit-box-shadow: 0 0 4px 0 rgba(0,0,0,0.2);
    box-shadow: 0 0 4px 0 rgba(0,0,0,.2);
}



.imgThumbContainer > .imgName{
  text-align:center;
  padding: 2px 6px;
  margin-top:4px;
  font-size:13px;
  height: 15px;
  overflow: hidden;
}

.imgThumbContainer > .imgRemoveBtn{
    position: absolute;
    color: #e91e63ba;
    right: 2px;
    top: 2px;
    cursor: pointer;
    display: none;
}

.RearangeBox:hover > .imgRemoveBtn{ 
    display: block;
}
/**** end select multiple image ********/
  </style>
  @include('includes.css_general')

  {{-- @laravelPWA --}}{{-- Disabled for local development --}}

  @yield('css')

 @if($settings->google_analytics != '')
  {!! $settings->google_analytics !!}
  @endif
</head>

<body s="{{ $size }}">
  @if ($settings->disable_banner_cookies == 'off')
  <div class="btn-block text-center showBanner padding-top-10 pb-3 display-none">
    <i class="fa fa-cookie-bite"></i> {{trans('general.cookies_text')}}
    @if ($settings->link_cookies != '')
      <a href="{{$settings->link_cookies}}" class="mr-2 text-white link-border" target="_blank">{{ trans('general.cookies_policy') }}</a>
    @endif
    <button class="btn btn-sm btn-primary" id="close-banner">{{trans('general.go_it')}}
    </button>
  </div>
@endif

  <div id="mobileMenuOverlay" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false"></div>

  @auth
    @if (! request()->is('messages/*') && ! request()->is('live/*'))
    @include('includes.menu-mobile')
  @endif
  @endauth

  @if ($settings->alert_adult == 'on')
    <div class="modal fade" tabindex="-1" id="alertAdult">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-body p-4">
          <p>{{ __('general.alert_content_adult') }}</p>
        </div>
        <div class="modal-footer border-0 pt-0">
          <a href="https://google.com" class="btn e-none p-0 mr-3">{{trans('general.leave')}}</a>
          <button type="button" class="btn btn-primary" id="btnAlertAdult">{{trans('general.i_am_age')}}</button>
        </div>
      </div>
    </div>
  </div>
  @endif


  <div class="popout popout-error font-default"></div>

@if (auth()->guest() && request()->path() == '/' && $settings->home_style == 0
    || auth()->guest() && request()->path() != '/' && $settings->home_style == 0
    || auth()->guest() && request()->path() != '/' && $settings->home_style == 1
    || auth()->check()
    )
  @include('includes.navbar')
  @endif

  <main @if (request()->is('messages/*') || request()->is('live/*')) class="h-100" @endif role="main">
    @yield('content')

    @if (auth()->guest() && ! request()->route()->named('profile')
          || auth()->check()
          && request()->path() != '/'
          && ! request()->is('my/bookmarks')
          && ! request()->is('my/purchases')
          && ! request()->is('explore')
          && ! request()->route()->named('profile')
          && ! request()->is('messages')
          && ! request()->is('messages/*')
          && ! request()->is('live/*')
          )

          @if (auth()->guest() && request()->path() == '/' && $settings->home_style == 0
                || auth()->guest() && request()->path() != '/' && $settings->home_style == 0
                || auth()->guest() && request()->path() != '/' && $settings->home_style == 1
                || auth()->check()
                  )

                  @if (auth()->guest() && $settings->who_can_see_content == 'users')
                    <div class="text-center py-3 px-3">
                      @include('includes.footer-tiny')
                    </div>
                  @else
                    @include('includes.footer')
                  @endif

          @endif

  @endif

  @guest

  @if (request()->is('/')
      && $settings->home_style == 0
      || request()->is('shifts')
      || request()->is('shifts/*')
      || request()->is('category/*')
      || request()->is('p/*')
      || request()->is('blog')
      || request()->is('blog/post/*')
      || request()->route()->named('profile')
      )

      @include('includes.modal-login')

    @endif
  @endguest

  @auth

    {{-- Legacy Paxpally features - disabled for OvertimeStaff
    @if ($settings->disable_tips == 'off')
     @include('includes.modal-tip')
   @endif

    @include('includes.modal-payperview')

    @if ($settings->live_streaming_status == 'on')
      @include('includes.modal-live-stream')
    @endif
    --}}

  @endauth

  @guest
    @include('includes.modal-2fa')
  @endguest
</main>

  @include('includes.javascript_general')

  @yield('javascript')

@auth
  <div id="bodyContainer" ></div>
@endauth
<!---video player modal --------->
<div class="modal" tabindex="-1" role="dialog" id="video-show-modal">
	<div class="modal-dialog modal-dialog-centered" role="document">
	  <div class="modal-content rounded-0 ">
		<div class="modal-header">
		 
      <i class="bi bi-x-circle close btn text-dark" data-dismiss="modal" style="box-shadow: none !important"></i>
		</div>
		<div class="modal-body video-show-modal-body">
      <video class="js-player" controls style="height: 400px;" >
        <source   type="video/mp4" />
		</div>
		
	  </div>
	</div>
  </div>
<!--end video player modal ----------->	
<!----  report modal ------->
<div class="modal modal-right fade" id="reportModal" tabindex="-1" role="dialog" aria-labelledby="left_modal">

    <div class="modal-dialog" role="document">

      <div class="modal-content mb-0">

        <div class="modal-header">

          <h5 class="modal-title d-flex justify-content-between align-items-center" style="font-family: 'Poppins', sans-serif;">Report</h5>

          <button type="button" class="close btn" data-dismiss="modal" aria-label="Close" style="box-shadow: none">

            <i class="bi bi-x-circle" style="color:black;font-size:18px;"></i>

          </button>

        </div>

        <div class="modal-body mb-0">
         <form id="report-form" style="font-family: 'Poppins', sans-serif;" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="user_id" id="" value="{{auth()->check()?auth()->user()->id:""}}">
          <div class="form-group">
            <label for="" >Title</label>
            <input type="text" name="title" id="" class="form-control required" placeholder="Title" title="Title">
          </div>
          <div class="form-group">
            <label for="">Select Type</label>
            <select name="type" id="" class="form-control">
             <option value="general">General</option>
              <option value="bug">Bug</option>
              <option value="suggestion">Suggestion</option>
              <option value="payment-issue">Payment Issue</option>
              
            </select>
          </div>
          <div class="form-group">
            <label for="">Message Or comment</label>
            <textarea name="message" id="" cols="6" rows="6" class="form-control required" placeholder="Messages or Comment" maxlength="500" title="Message"></textarea>
            <p style="font-size:10px;padding:0px;margin:0px;"><span class="no-character">0</span>/500</p>
          </div>
          <div class="form-group">
           
            <div class="row col-sm-12">
              <div style='padding:14px'>
                {{-- <label for="files" class="btn btn-primary">Select files </label> --}}
                {{-- <input id="files" type="file"name="file-input[]" multiple/>         --}}
                <input type="file" accept="image/*" multiple id="files" name="file-input[]">
            </div>
            <div style='padding:14px; margin:auto';>
            <div id="sortableImgThumbnailPreview">
                
            </div>
            </div>
            </div>
          </div>
          <div class="form-group mb-0" style="position:absolute;bottom:10px;left:10px;">
            <button class="btn btn-info">Submit</button>
          </div>
         </form>

        

        </div>

       

      </div>

    </div>

  </div>
  
<!---end report modal ------->
<script src="{{url('js/comment.js')}}"></script>

<script src="{{url('imageuploadify.js')}}"></script>
<script>
   $(document).ready(function(){

    $('#files').imageuploadify();
          });
</script>

{{-- Real-time Notifications --}}
@auth
<script>
    // Set user ID for Echo
    window.userId = {{ auth()->id() }};
</script>
<link rel="stylesheet" href="{{ asset('css/toast-notifications.css') }}">
<link rel="stylesheet" href="{{ asset('css/dashboard-updates.css') }}">
<script src="{{ asset('js/notifications.js') }}"></script>
<script src="{{ asset('js/dashboard-updates.js') }}"></script>
@endauth
</body>
</html>
