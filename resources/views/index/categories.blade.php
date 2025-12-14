@extends('layouts.app')

@section('title') {{$title}} -@endsection

    @section('description_custom'){{$description ? $description : trans('seo.description')}}@endsection
    @section('keywords_custom'){{$keywords ? $keywords.',' : null}}@endsection

@section('content')
<section class="section section-sm">
    <div class="container">
      <div class="row justify-content-center text-center mb-sm">
        <div class="col-lg-12 py-5">
          <h2 class="mb-0 font-montserrat">
            <img src="{{url('img-category', $image)}}" class="mr-2 rounded" width="30" /> {{$title}}</h2>
          <p class="lead text-muted mt-0">({{$users->total()}}) {{trans_choice('users.creators_in_this_category',$users->total() )}}</p>
        </div>
      </div>

<div class="row">

<div class="col-md-3 mb-4">

  <button type="button" class="btn-menu-expand btn btn-primary btn-block mb-4 d-lg-none" type="button" data-toggle="collapse" data-target="#navbarFilters" aria-controls="navbarCollapse" aria-expanded="false">
      <i class="bi bi-filter-right mr-2"></i> {{trans('general.filter_by')}}
    </button>

<div class="navbar-collapse collapse d-lg-block" id="navbarFilters">
  <div class="btn-block mb-3">
    <span>

      <span class="category-filter">
      <i class="bi bi-filter-right mr-2"></i> {{trans('general.filter_by')}}
      </span>

  <a class="text-muted btn btn-sm bg-white border mb-2 e-none btn-category @if(request()->is('category/'.$slug.''))active-category @endif" href="{{url('category', $slug)}}">
    	<img src="{{url('img/popular.png')}}" class="mr-2" width="30" /> {{trans('general.popular')}}
  </a>

  <a class="text-muted btn btn-sm bg-white border mb-2 e-none btn-category @if(request()->is('category/'.$slug.'/featured'))active-category @endif" href="{{url('category/'.$slug.'','featured')}}">
    <img src="{{url('img/featured.png')}}" class="mr-2" width="30" /> {{trans('general.featured_creators')}}
  </a>

  <a class="text-muted btn btn-sm bg-white border mb-2 e-none btn-category @if(request()->is('category/'.$slug.'/more-active'))active-category @endif" href="{{url('category/'.$slug.'','more-active')}}">
    <img src="{{url('img/more-active.png')}}" class="mr-2" width="30" /> {{trans('general.more_active')}}
  </a>

  <a class="text-muted btn btn-sm bg-white border mb-2 e-none btn-category @if(request()->is('category/'.$slug.'/new'))active-category @endif" href="{{url('category/'.$slug.'','new')}}">
    <img src="{{url('img/creators.png')}}" class="mr-2" width="30" />  {{trans('general.new_creators')}}
  </a>

  <a class="text-muted btn btn-sm bg-white border mb-2 e-none btn-category @if(request()->is('category/'.$slug.'/free'))active-category @endif" href="{{url('category/'.$slug.'','free')}}">
    <img src="{{url('img/unlock.png')}}" class="mr-2" width="30" /> {{trans('general.free_subscription')}}
  </a>

  @if ($settings->live_streaming_status == 'on')
    <a class="text-muted btn btn-sm bg-white border mb-2 e-none btn-category @if(request()->is('explore/creators/live'))active-category @endif" href="{{url('explore/creators/live')}}">
      <img src="{{url('img/live.png')}}" class="mr-2" width="30" /> {{trans('general.live')}}
    </a>
  @endif

    </span>
  </div>
</div>

            @include('includes.listing-categories')

        </div><!-- end col-md-3 -->

        @if ($users->total() != 0)
          <div class="col-md-9 mb-4">
            <div class="row">

              @foreach($users as $response)
              <div class="col-md-6 mb-4">
                @include('includes.listing-creators')
              </div><!-- end col-md-4 -->
              @endforeach

              @if($users->lastPage() > 1)
                <div class="w-100 d-block">
                  {{ $users->onEachSide(0)->links() }}
                </div>
              @endif
            </div><!-- row -->
          </div><!-- col-md-9 -->

        @else
          <div class="col-md-9">
            <div class="my-5 text-center no-updates">
              <span class="btn-block mb-3">
                <i class="fa fa-user-slash ico-no-result"></i>
              </span>
            <h4 class="font-weight-light">{{trans('general.not_found_creators_category')}}</h4>
            </div>
          </div>
        @endif
      </div>
    </div>
  </section>
@endsection
