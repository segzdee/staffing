<header>
	<nav class="navbar navbar-expand-lg navbar-inverse fixed-top p-nav @if(auth()->guest() && request()->path() == '/') scroll @else p-3 @if (request()->is('live/*')) d-none @endif  @if (request()->is('messages/*')) d-none d-lg-block shadow-sm @elseif(request()->is('messages')) shadow-sm @else shadow-custom @endif {{ auth()->check() && auth()->user()->dark_mode == 'on' ? 'bg-white' : 'navbar_background_color' }} link-scroll @endif">
		<div class="container-fluid d-flex position-relative">

			@auth
			<div class="buttons-mobile-nav d-lg-none">
				<a class="btn-mobile-nav navbar-toggler-mobile" href="#"  data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" role="button">
					<i class="feather icon-menu icon-navbar"></i>
				</a>
				</div>
			@endauth

			<a class="navbar-brand" href="{{url('/')}}">
				@if (auth()->check() && auth()->user()->dark_mode == 'on' )
					<img src="{{url('img', $settings->logo)}}" data-logo="{{$settings->logo}}" data-logo-2="{{$settings->logo_2}}" alt="{{$settings->title}}" class="logo align-bottom max-w-100" />
				@else
				<img src="{{url('img', auth()->guest() && request()->path() == '/' ? $settings->logo : $settings->logo_2)}}" data-logo="{{$settings->logo}}" data-logo-2="{{$settings->logo_2}}" alt="{{$settings->title}}" class="logo align-bottom max-w-100" />
			@endif
			</a>

			@guest
				<button class="navbar-toggler @if(auth()->guest() && request()->path() == '/') text-white @endif" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
					<i class="fa fa-bars"></i>
				</button>
			@endguest

			<div class="collapse navbar-collapse navbar-mobile" id="navbarCollapse">

			<div class="d-lg-none text-right pr-2 mb-2">
				<button type="button" class="navbar-toggler close-menu-mobile" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false">
					<i class="bi bi-x-lg"></i>
				</button>
			</div>

			@if (auth()->guest() && $settings->who_can_see_content == 'all' || auth()->check())
				<ul class="navbar-nav mr-auto">
					<form class="form-inline my-lg-0 position-relative" method="get" action="{{url('shifts')}}">
						<input id="searchShiftNavbar" class="form-control search-bar @if(auth()->guest() && request()->path() == '/') border-0 @endif" type="text" required name="q" autocomplete="off" minlength="3" placeholder="{{ __('general.find_shift') }}" aria-label="Search">
						<button class="btn btn-outline-success my-sm-0 button-search e-none" type="submit"><i class="bi bi-search"></i></button>

						<div class="dropdown-menu dd-menu-user position-absolute" style="width: 95%; top: 48px;" id="dropdownShifts">

							<button type="button" class="d-none" id="triggerBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>

							<div class="w-100 text-center display-none py-2" id="spinnerSearch">
	                <span class="spinner-border spinner-border-sm align-middle text-primary"></span>
	              </div>

								<div id="containerShifts"></div>

								<div id="viewAll" class="display-none mt-2">
								    <a class="dropdown-item border-top py-2 text-center" href="#">{{ __('general.view_all') }}</a>
								</div>
					  </div><!-- dropdown-menu -->
					</form>

					@guest
						<li class="nav-item">
							<a class="nav-link" href="{{url('shifts')}}">Browse Shifts</a>
						</li>

						<li class="nav-item">
							<a class="nav-link" href="{{url('how-it-works')}}">How It Works</a>
						</li>
					@endguest

				</ul>
			@endif

				<ul class="navbar-nav ml-auto">
					@guest
					<li class="nav-item mr-1">
						{{-- <a @if (request()->is('/') && $settings->home_style == 0 || request()->route()->named('profile') || request()->is('creators') || request()->is('creators/*') || request()->is('category/*') || request()->is('p/*') || request()->is('blog') || request()->is('blog/post/*') || request()->is('shop') || request()->is('shop/product/*')) data-toggle="modal" data-target="#loginFormModal" @endif class="nav-link login-btn @if ($settings->registration_active == '0')  btn btn-main btn-primary pr-3 pl-3 @endif" href="{{$settings->home_style == 0 ? url('login') : url('/')}}">
							{{trans('auth.login')}}
						</a> --}}
						<a class="nav-link login-btn @if ($settings->registration_active == '0')  btn btn-main btn-primary pr-3 pl-3 @endif" href="{{$settings->home_style == 0 ? url('login') : url('/')}}">
							{{trans('auth.login')}}
						</a>
					</li>

					@if ($settings->registration_active == '1')
					<li class="nav-item">
						{{-- <a @if (request()->is('/') && $settings->home_style == 0 || request()->route()->named('profile') || request()->is('creators') || request()->is('creators/*') || request()->is('category/*') || request()->is('p/*') || request()->is('blog') || request()->is('blog/post/*') || request()->is('shop') || request()->is('shop/product/*')) data-toggle="modal" data-target="#loginFormModal" @endif class="toggleRegister nav-link btn btn-main btn-primary pr-3 pl-3 btn-arrow btn-arrow-sm" href="{{$settings->home_style == 0 ? url('signup') : url('/')}}">
							{{trans('general.getting_started')}}
						</a> --}}
						<a class="toggleRegister nav-link btn btn-main btn-primary pr-3 pl-3 btn-arrow btn-arrow-sm" href="{{$settings->home_style == 0 ? url('signup') : url('/')}}">
							{{trans('general.getting_started')}}
						</a>
					</li>
				@endif

			@else

				<!-- ============ Menu Mobile ============-->

				@if (auth()->user()->role == 'admin')
					<li class="nav-item dropdown d-lg-none mt-2 border-bottom">
						<a href="{{url('panel/admin')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-speedometer2 mr-2"></i>
								<span class="d-lg-none">{{trans('admin.admin')}}</span>
							</div>
						</a>
					</li>
				@endif

				<li class="nav-item dropdown d-lg-none @if (auth()->user()->role != 'admin') mt-2 @endif">
					<a href="{{url('settings/page')}}" class="nav-link px-2 link-menu-mobile py-1">
						<div>
							<img src="{{Helper::getFile(config('path.avatar').auth()->user()->avatar)}}" alt="User" class="rounded-circle avatarUser mr-1" width="20" height="20">
							<span class="d-lg-none">My Profile</span>
						</div>
					</a>
				</li>

				@if (auth()->user()->user_type == 'worker')
					<li class="nav-item dropdown d-lg-none">
						<a href="{{url('worker/dashboard')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-speedometer2 mr-2"></i>
								<span class="d-lg-none">Dashboard</span>
							</div>
						</a>
					</li>

					<li class="nav-item dropdown d-lg-none">
						<a href="{{url('shifts')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-briefcase mr-2"></i>
								<span class="d-lg-none">Browse Shifts</span>
							</div>
						</a>
					</li>

					<li class="nav-item dropdown d-lg-none">
						<a href="{{url('worker/applications')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-file-text mr-2"></i>
								<span class="d-lg-none">My Applications</span>
							</div>
						</a>
					</li>

					<li class="nav-item dropdown d-lg-none border-bottom">
						<a href="{{url('worker/assignments')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-calendar-check mr-2"></i>
								<span class="d-lg-none">My Assignments</span>
							</div>
						</a>
					</li>

				@elseif (auth()->user()->user_type == 'business')
					<li class="nav-item dropdown d-lg-none">
						<a href="{{url('business/dashboard')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-speedometer2 mr-2"></i>
								<span class="d-lg-none">Dashboard</span>
							</div>
						</a>
					</li>

					<li class="nav-item dropdown d-lg-none">
						<a href="{{url('shifts/create')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-plus-circle mr-2"></i>
								<span class="d-lg-none">Post Shift</span>
							</div>
						</a>
					</li>

					<li class="nav-item dropdown d-lg-none">
						<a href="{{url('business/shifts')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-briefcase mr-2"></i>
								<span class="d-lg-none">My Shifts</span>
							</div>
						</a>
					</li>

					<li class="nav-item dropdown d-lg-none border-bottom">
						<a href="{{url('business/applications')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-file-text mr-2"></i>
								<span class="d-lg-none">Applications</span>
							</div>
						</a>
					</li>

				@elseif (auth()->user()->user_type == 'agency')
					<li class="nav-item dropdown d-lg-none">
						<a href="{{url('agency/dashboard')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-speedometer2 mr-2"></i>
								<span class="d-lg-none">Dashboard</span>
							</div>
						</a>
					</li>

					<li class="nav-item dropdown d-lg-none">
						<a href="{{url('agency/workers')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-people mr-2"></i>
								<span class="d-lg-none">Manage Workers</span>
							</div>
						</a>
					</li>

					<li class="nav-item dropdown d-lg-none">
						<a href="{{url('agency/shifts/browse')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-briefcase mr-2"></i>
								<span class="d-lg-none">Browse Shifts</span>
							</div>
						</a>
					</li>

					<li class="nav-item dropdown d-lg-none">
						<a href="{{url('agency/assignments')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-calendar-check mr-2"></i>
								<span class="d-lg-none">Assignments</span>
							</div>
						</a>
					</li>

					<li class="nav-item dropdown d-lg-none">
						<a href="{{url('agency/commissions')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-cash-stack mr-2"></i>
								<span class="d-lg-none">Commissions</span>
							</div>
						</a>
					</li>

					<li class="nav-item dropdown d-lg-none border-bottom">
						<a href="{{url('agency/analytics')}}" class="nav-link px-2 link-menu-mobile py-1">
							<div>
								<i class="bi bi-graph-up mr-2"></i>
								<span class="d-lg-none">Analytics</span>
							</div>
						</a>
					</li>
				@endif

				@if (auth()->user()->balance != 0.00)
					<li class="nav-item dropdown d-lg-none">
						<a class="nav-link px-2 link-menu-mobile py-1 balance">
							<div>
								<i class="iconmoon icon-Dollar mr-2"></i>
								<span class="d-lg-none balance">{{ trans('general.balance') }}: {{Helper::amountFormatDecimal(auth()->user()->balance)}}</span>
							</div>
						</a>
					</li>
				@endif

				@if ($settings->disable_wallet == 'on' && auth()->user()->wallet != 0.00 || $settings->disable_wallet == 'off')
					<li class="nav-item dropdown d-lg-none border-bottom">
						<a @if ($settings->disable_wallet == 'off') href="{{url('my/wallet')}}" @endif class="nav-link px-2 link-menu-mobile py-1">
						<div>
							<i class="iconmoon icon-Wallet mr-2"></i>
							{{ trans('general.wallet') }}: <span class="balanceWallet">{{Helper::userWallet()}}</span>
						</div>
						</a>
					</li>
				@endif

				<li class="nav-item dropdown d-lg-none">
					<a href="{{auth()->user()->dark_mode == 'off' ? url('mode/dark') : url('mode/light')}}" class="nav-link px-2 link-menu-mobile py-1">
						<div>
							<i class="feather icon-{{ auth()->user()->dark_mode == 'off' ? 'moon' : 'sun'  }} mr-2"></i>
							<span class="d-lg-none">{{ auth()->user()->dark_mode == 'off' ? trans('general.dark_mode') : trans('general.light_mode') }}</span>
						</div>
					</a>
				</li>

				<li class="nav-item dropdown d-lg-none mb-2">
					<form method="POST" action="{{ route('logout') }}" class="m-0">
						@csrf
						<button type="submit" class="nav-link px-2 link-menu-mobile py-1 btn btn-link text-left w-100 border-0">
							<div>
								<i class="feather icon-log-out mr-2"></i>
								<span class="d-lg-none">{{ trans('auth.logout') }}</span>
							</div>
						</button>
					</form>
				</li>
				<!-- =========== End Menu Mobile ============-->


					<li class="nav-item dropdown d-lg-block d-none">
						<a class="nav-link px-2" href="{{url('/')}}" title="{{trans('admin.home')}}">
							<i class="feather icon-home icon-navbar"></i>
							<span class="d-lg-none align-middle ml-1">{{trans('admin.home')}}</span>
						</a>
					</li>

					<li class="nav-item dropdown d-lg-block d-none">
						<a class="nav-link px-2" href="{{url('shifts')}}" title="Browse Available Shifts">
							<i class="bi bi-briefcase icon-navbar"></i>
							<span class="d-lg-none align-middle ml-1">Browse Shifts</span>
						</a>
					</li>

				<li class="nav-item dropdown d-lg-block d-none">
					<a href="{{url('messages')}}" class="nav-link px-2" title="{{ trans('general.messages') }}">
						@php
							$unreadCount = auth()->check() ? \App\Models\Conversation::where(function($q) {
								$q->where('worker_id', auth()->id())->orWhere('business_id', auth()->id());
							})->whereHas('messages', function($q) {
								$q->where('is_read', 0)->where('to_user_id', auth()->id());
							})->count() : 0;
						@endphp
						<span class="noti_msg notify @if ($unreadCount != 0) d-block @endif">
							{{ $unreadCount }}
							</span>

						<i class="feather icon-send icon-navbar"></i>
						<span class="d-lg-none align-middle ml-1">{{ trans('general.messages') }}</span>
					</a>
				</li>

				<li class="nav-item dropdown d-lg-block d-none">
					<a href="{{url('notifications')}}" class="nav-link px-2" title="{{ trans('general.notifications') }}">

						<span class="noti_notifications notify @if (auth()->user()->notifications()->where('read', false)->count()) d-block @endif">
							{{ auth()->user()->notifications()->where('read', false)->count() }}
							</span>

						<i class="far fa-bell icon-navbar"></i>
						<span class="d-lg-none align-middle ml-1">{{ trans('general.notifications') }}</span>
					</a>
				</li>

				<li class="nav-item dropdown d-lg-block d-none">
					<a class="nav-link" href="#" id="nav-inner-success_dropdown_1" role="button" data-toggle="dropdown">
						<img src="{{Helper::getFile(config('path.avatar').auth()->user()->avatar)}}" alt="User" class="rounded-circle avatarUser mr-1" width="28" height="28">
						<span class="d-lg-none">{{auth()->user()->first_name}}</span>
						<i class="feather icon-chevron-down m-0 align-middle"></i>
					</a>
					<div class="dropdown-menu mb-1 dropdown-menu-right dd-menu-user" aria-labelledby="nav-inner-success_dropdown_1">
						@if(auth()->user()->role == 'admin')
								<a class="dropdown-item dropdown-navbar" href="{{url('panel/admin')}}"><i class="bi bi-speedometer2 mr-2"></i> {{trans('admin.admin')}}</a>
								<div class="dropdown-divider"></div>
						@endif

						@if (auth()->user()->verified_id == 'yes' || $settings->referral_system == 'on' || auth()->user()->balance != 0.00)
						<span class="dropdown-item dropdown-navbar balance">
							<i class="iconmoon icon-Dollar mr-2"></i> {{trans('general.balance')}}: {{Helper::amountFormatDecimal(auth()->user()->balance)}}
						</span>
					@endif

					@if ($settings->disable_wallet == 'on' && auth()->user()->wallet != 0.00 || $settings->disable_wallet == 'off')
						@if ($settings->disable_wallet == 'off')
							<a class="dropdown-item dropdown-navbar" href="{{url('my/wallet')}}">
								<i class="iconmoon icon-Wallet mr-2"></i> {{trans('general.wallet')}}:
								<span class="balanceWallet">{{Helper::userWallet()}}</span>
							</a>
						@else
							<span class="dropdown-item dropdown-navbar balance">
								<i class="iconmoon icon-Wallet mr-2"></i> {{trans('general.wallet')}}:
								<span class="balanceWallet">{{Helper::userWallet()}}</span>
							</span>
						@endif

						<div class="dropdown-divider"></div>
					@endif

					@if ($settings->disable_wallet == 'on' && auth()->user()->verified_id == 'yes')
						<div class="dropdown-divider"></div>
					@endif

						<a class="dropdown-item dropdown-navbar" href="{{url('settings/page')}}"><i class="feather icon-user mr-2"></i> My Profile</a>

						@if (auth()->user()->user_type == 'worker')
							<a class="dropdown-item dropdown-navbar" href="{{url('worker/dashboard')}}"><i class="bi bi-speedometer2 mr-2"></i> Dashboard</a>
							<a class="dropdown-item dropdown-navbar" href="{{url('worker/applications')}}"><i class="bi bi-file-text mr-2"></i> My Applications</a>
							<a class="dropdown-item dropdown-navbar" href="{{url('worker/assignments')}}"><i class="bi bi-calendar-check mr-2"></i> My Assignments</a>
						@elseif (auth()->user()->user_type == 'business')
							<a class="dropdown-item dropdown-navbar" href="{{url('business/dashboard')}}"><i class="bi bi-speedometer2 mr-2"></i> Dashboard</a>
							<a class="dropdown-item dropdown-navbar" href="{{url('shifts/create')}}"><i class="bi bi-plus-circle mr-2"></i> Post Shift</a>
							<a class="dropdown-item dropdown-navbar" href="{{url('business/shifts')}}"><i class="bi bi-briefcase mr-2"></i> My Shifts</a>
							<a class="dropdown-item dropdown-navbar" href="{{url('business/applications')}}"><i class="bi bi-file-text mr-2"></i> Applications</a>
						@elseif (auth()->user()->user_type == 'agency')
							<a class="dropdown-item dropdown-navbar" href="{{url('agency/dashboard')}}"><i class="bi bi-speedometer2 mr-2"></i> Dashboard</a>
							<a class="dropdown-item dropdown-navbar" href="{{url('agency/workers')}}"><i class="bi bi-people mr-2"></i> Manage Workers</a>
							<a class="dropdown-item dropdown-navbar" href="{{url('agency/assignments')}}"><i class="bi bi-calendar-check mr-2"></i> Assignments</a>
							<a class="dropdown-item dropdown-navbar" href="{{url('agency/commissions')}}"><i class="bi bi-cash-stack mr-2"></i> Commissions</a>
						@endif

						<div class="dropdown-divider"></div>
						<a class="dropdown-item dropdown-navbar" href="{{url('my/transactions')}}"><i class="bi bi-receipt mr-2"></i> Earnings & Transactions</a>

						<div class="dropdown-divider"></div>

						@if (auth()->user()->dark_mode == 'off')
							<a class="dropdown-item dropdown-navbar" href="{{url('mode/dark')}}"><i class="feather icon-moon mr-2"></i> {{trans('general.dark_mode')}}</a>
						@else
							<a class="dropdown-item dropdown-navbar" href="{{url('mode/light')}}"><i class="feather icon-sun mr-2"></i> {{trans('general.light_mode')}}</a>
						@endif

						<div class="dropdown-divider dropdown-navbar"></div>
						<form method="POST" action="{{ route('logout') }}" class="m-0">
							@csrf
							<button type="submit" class="dropdown-item dropdown-navbar btn btn-link text-left w-100 border-0"><i class="feather icon-log-out mr-2"></i> {{trans('auth.logout')}}</button>
						</form>
					</div>
				</li>

				<li class="nav-item">
					<a class="nav-link btn-arrow btn-arrow-sm btn btn-main btn-primary pr-3 pl-3" href="{{url('settings/page')}}">
						Settings</a>
				</li>

					@endguest

				</ul>
			</div>
		</div>
	</nav>
</header>
