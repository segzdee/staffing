<!-- FOOTER -->
@php
  $settings = $settings ?? new \stdClass();
@endphp
<div
  class="py-5 @if (Auth::check() && auth()->user()->dark_mode == 'off' || Auth::guest()) footer_background_color footer_text_color @else bg-white @endif @if (Auth::check() && auth()->user()->dark_mode == 'off' && ($settings->footer_background_color ?? '#ffffff') == '#ffffff' || Auth::guest() && ($settings->footer_background_color ?? '#ffffff') == '#ffffff') border-top @endif">
  <footer class="container">
    <div class="row">
      <div class="col-md-3">
        <a href="{{url('/')}}">
          <x-logo class="max-w-125 h-10 w-auto" />
        </a>
        @if(
            ($settings->twitter ?? '') != ''
            || ($settings->facebook ?? '') != ''
            || ($settings->instagram ?? '') != ''
            || ($settings->pinterest ?? '') != ''
            || ($settings->youtube ?? '') != ''
            || ($settings->github ?? '') != ''
            || ($settings->tiktok ?? '') != ''
            || ($settings->snapchat ?? '') != ''
          )
          <div class="w-100">
            <span class="w-100">{{trans('general.keep_connect_with_us')}} {{trans('general.follow_us_social')}}</span>
            <ul class="list-inline list-social">
              @if (($settings->twitter ?? '') != '')
                <li class="list-inline-item"><a href="{{$settings->twitter ?? '#'}}" target="_blank" class="ico-social"><i
                      class="fab fa-twitter"></i></a></li>
              @endif

              @if (($settings->facebook ?? '') != '')
                <li class="list-inline-item"><a href="{{$settings->facebook ?? '#'}}" target="_blank" class="ico-social"><i
                      class="fab fa-facebook"></i></a></li>
              @endif

              @if (($settings->instagram ?? '') != '')
                <li class="list-inline-item"><a href="{{$settings->instagram ?? '#'}}" target="_blank" class="ico-social"><i
                      class="fab fa-instagram"></i></a></li>
              @endif

              @if (($settings->pinterest ?? '') != '')
                <li class="list-inline-item"><a href="{{$settings->pinterest ?? '#'}}" target="_blank" class="ico-social"><i
                      class="fab fa-pinterest"></i></a></li>
              @endif

              @if (($settings->youtube ?? '') != '')
                <li class="list-inline-item"><a href="{{$settings->youtube ?? '#'}}" target="_blank" class="ico-social"><i
                      class="fab fa-youtube"></i></a></li>
              @endif

              @if (($settings->github ?? '') != '')
                <li class="list-inline-item"><a href="{{$settings->github ?? '#'}}" target="_blank" class="ico-social"><i
                      class="fab fa-github"></i></a></li>
              @endif

              @if (($settings->tiktok ?? '') != '')
                <li class="list-inline-item"><a href="{{$settings->tiktok ?? '#'}}" target="_blank" class="ico-social"><i
                      class="bi bi-tiktok"></i></a></li>
              @endif

              @if (($settings->snapchat ?? '') != '')
                <li class="list-inline-item"><a href="{{$settings->snapchat ?? '#'}}" target="_blank" class="ico-social"><i
                      class="bi bi-snapchat"></i></a></li>
              @endif
            </ul>
          </div>
        @endif

        <li>
          <div id="installContainer" class="display-none">
            <button class="btn btn-primary w-100 rounded-pill mb-4" id="butInstall" type="button">
              <i class="bi-phone mr-1"></i> {{ __('general.install_web_app') }}
            </button>
          </div>
        </li>

      </div>
      <div class="col-md-3">
        <h6 class="text-uppercase">@lang('general.about')</h6>
        <ul class="list-unstyled">
          @foreach (Helper::pages() as $page)

            @if ($page->access == 'all')

              <li>
                <a class="link-footer" href="{{ url('/p', $page->slug) }}">
                  {{ $page->title }}
                </a>
              </li>

            @elseif ($page->access == 'creators' && auth()->check() && auth()->user()->verified_id == 'yes')
              <li>
                <a class="link-footer" href="{{ url('/p', $page->slug) }}">
                  {{ $page->title }}
                </a>
              </li>

            @elseif ($page->access == 'members' && auth()->check())
              <li>
                <a class="link-footer" href="{{ url('/p', $page->slug) }}">
                  {{ $page->title }}
                </a>
              </li>
            @endif

          @endforeach
          <li><a class="link-footer" href="{{ url('contact') }}">{{ trans('general.contact') }}</a></li>

          @if (App\Models\Blogs::count() != 0)
            <li><a class="link-footer" href="{{ url('blog') }}">{{ trans('general.blog') }}</a></li>
          @endif
        </ul>
      </div>
      {{-- Legacy Paxpally Categories section - removed for OvertimeStaff --}}
      <div class="col-md-3">
        <h6 class="text-uppercase">@lang('general.links')</h6>
        <ul class="list-unstyled">
          @guest
            <li><a class="link-footer"
                href="{{($settings->home_style ?? 0) == 0 ? url('login') : url('/')}}">{{ trans('auth.login') }}</a></li>
            <li>
              @if (($settings->registration_active ?? '1') == '1')
                <li><a class="link-footer"
                    href="{{($settings->home_style ?? 0) == 0 ? url('signup') : url('/')}}">{{ trans('auth.sign_up') }}</a>
                </li>
                <li>
              @endif
          @else
              <li><a class="link-footer url-user"
                  href="{{ url(Auth::User()->username) }}">{{ auth()->user()->verified_id == 'yes' ? trans('general.my_page') : trans('users.my_profile') }}</a>
              </li>
              <li>
              <li><a class="link-footer"
                  href="{{ url('settings/page') }}">{{ auth()->user()->verified_id == 'yes' ? trans('general.edit_my_page') : trans('users.edit_profile')}}</a>
              </li>
              <li>
              <li><a class="link-footer" href="{{ url('my/subscriptions') }}">{{ trans('users.my_subscriptions') }}</a></li>
              <li>
              <li>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                  @csrf
                  <button type="submit"
                    class="link-footer btn btn-link p-0 border-0 align-baseline">{{ trans('users.logout') }}</button>
                </form>
              </li>
              <li>
            @endguest

            @guest
              <li class="dropdown mt-1">
                <a class="btn btn-outline-secondary rounded-pill mt-2 dropdown-toggle px-4 dropdown-toggle text-decoration-none"
                  href="javascript:;" data-toggle="dropdown">
                  <i class="feather icon-globe mr-1"></i>
                  @foreach (Languages::orderBy('name')->get() as $languages)
                    @if ($languages->abbreviation == config('app.locale')) {{ $languages->name }} @endif
                  @endforeach
                </a>

                <div class="dropdown-menu">
                  @foreach (Languages::orderBy('name')->get() as $languages)
                    <a @if ($languages->abbreviation != config('app.locale'))
                    href="{{ url('lang', $languages->abbreviation) }}" @endif
                      class="dropdown-item mb-1 dropdown-lang @if($languages->abbreviation == config('app.locale')) active text-white @endif">
                      @if ($languages->abbreviation == config('app.locale')) <i class="fa fa-check mr-1"></i> @endif
                      {{ $languages->name }}
                    </a>
                  @endforeach
                </div>
              </li>

            @endguest

        </ul>
      </div>
    </div>
  </footer>
</div>

<footer
  class="py-3 @if (Auth::check() && auth()->user()->dark_mode == 'off' || Auth::guest()) footer_background_color @endif text-center">
  <div class="container">
    <div class="row">
      <div class="col-md-12 copyright">
        &copy; {{date('Y')}} {{$settings->title ?? config('app.name', 'OvertimeStaff')}}
      </div>
    </div>
  </div>
</footer>