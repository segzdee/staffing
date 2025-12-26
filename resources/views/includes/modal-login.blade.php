{{-- Mobile-optimized Login Modal --}}
<div class="modal fade" id="loginFormModal" tabindex="-1" role="dialog" aria-labelledby="loginFormModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-sm modal-login" role="document">
		<div class="modal-content max-h-[100vh] sm:max-h-[90vh] flex flex-col rounded-none sm:rounded-lg overflow-hidden">
			{{-- Body - This modal has no header, title is inside body --}}
			<div class="modal-body flex-1 overflow-y-auto overscroll-contain p-0 bg-white dark:bg-gray-800">
				<div class="card-body px-4 py-5 sm:px-6 sm:py-6 position-relative">

					<h6 class="modal-title text-center text-lg font-semibold mb-4" id="loginRegisterContinue">{{ __('general.login_continue') }}</h6>

					@if ($settings->facebook_login == 'on' || $settings->google_login == 'on' || $settings->twitter_login == 'on')
					<div class="mb-4 w-100 space-y-2">

						@if ($settings->facebook_login == 'on')
							<a href="{{url('oauth/facebook')}}" class="btn btn-facebook auth-form-btn w-100 min-h-[44px] sm:min-h-[40px] flex items-center justify-center gap-2 touch-manipulation">
								<i class="fab fa-facebook"></i> <span class="loginRegisterWith">{{ __('auth.login_with') }}</span> Facebook
							</a>
						@endif

						@if ($settings->twitter_login == 'on')
						<a href="{{url('oauth/twitter')}}" class="btn btn-twitter auth-form-btn w-100 min-h-[44px] sm:min-h-[40px] flex items-center justify-center gap-2 touch-manipulation">
							<i class="fab fa-twitter"></i> <span class="loginRegisterWith">{{ __('auth.login_with') }}</span> Twitter
						</a>
						@endif

						@if ($settings->google_login == 'on')
						<a href="{{url('oauth/google')}}" class="btn btn-google auth-form-btn w-100 min-h-[44px] sm:min-h-[40px] flex items-center justify-center gap-2 touch-manipulation">
							<img src="{{ url('img/google.svg') }}" class="w-[18px] h-[18px]" width="18" height="18" alt="Google"> <span class="loginRegisterWith">{{ __('auth.login_with') }}</span> Google
						</a>
						@endif
					</div>

					<small class="btn-block text-center my-4 text-uppercase text-gray-500 or">{{__('general.or')}}</small>

					@endif

					<form method="POST" action="{{ route('login') }}" data-url-login="{{ route('login') }}" data-url-register="{{ route('register') }}" id="formLoginRegister" enctype="multipart/form-data">
							@csrf

							@if (request()->route()->named('profile'))
								<input type="hidden" name="isProfile" value="{{ $user->username }}">
							@endif

							<input type="hidden" name="isModal" id="isModal" value="true">

							@if ($settings->captcha == 'on')
								@captcha
							@endif

							<div class="form-group mb-3 display-none" id="full_name">
								<div class="input-group input-group-alternative">
									<div class="input-group-prepend">
										<span class="input-group-text min-h-[44px] sm:min-h-[40px]"><i class="feather icon-user"></i></span>
									</div>
									<input class="form-control min-h-[44px] sm:min-h-[40px] text-base sm:text-sm touch-manipulation" value="{{ old('name')}}" placeholder="{{trans('auth.full_name')}}" name="name" type="text" autocomplete="name">
								</div>
							</div>

						<div class="form-group mb-3 display-none" id="email">
							<div class="input-group input-group-alternative">
								<div class="input-group-prepend">
									<span class="input-group-text min-h-[44px] sm:min-h-[40px]"><i class="feather icon-mail"></i></span>
								</div>
								<input class="form-control min-h-[44px] sm:min-h-[40px] text-base sm:text-sm touch-manipulation" value="{{ old('email')}}" placeholder="{{trans('auth.email')}}" name="email" type="email" autocomplete="email" inputmode="email">
							</div>
						</div>

						<div class="form-group mb-3" id="username_email">
							<div class="input-group input-group-alternative">
								<div class="input-group-prepend">
									<span class="input-group-text min-h-[44px] sm:min-h-[40px]"><i class="feather icon-mail"></i></span>
								</div>
								<input class="form-control min-h-[44px] sm:min-h-[40px] text-base sm:text-sm touch-manipulation" value="{{ old('username_email') }}" placeholder="{{ trans('auth.username_or_email') }}" name="username_email" type="text" autocomplete="username">
							</div>
						</div>

						<div class="form-group mb-3">
							<div class="input-group input-group-alternative" id="showHidePassword">
								<div class="input-group-prepend">
									<span class="input-group-text min-h-[44px] sm:min-h-[40px]"><i class="iconmoon icon-Key"></i></span>
								</div>
								<input name="password" type="password" class="form-control min-h-[44px] sm:min-h-[40px] text-base sm:text-sm touch-manipulation" placeholder="{{ trans('auth.password') }}" autocomplete="current-password">
								<div class="input-group-append">
									<span class="input-group-text c-pointer min-h-[44px] sm:min-h-[40px] min-w-[44px] sm:min-w-[40px] flex items-center justify-center touch-manipulation"><i class="feather icon-eye-off"></i></span>
								</div>
							</div>
							<small class="form-text text-muted mt-2">
								<a href="{{url('password/reset')}}" id="forgotPassword" class="touch-manipulation">
									{{trans('auth.forgot_password')}}
								</a>
							</small>
						</div>

						<div class="custom-control custom-control-alternative custom-checkbox mb-3" id="remember">
							<input class="custom-control-input" id="customCheckLogin" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
							<label class="custom-control-label min-h-[44px] sm:min-h-[40px] flex items-center touch-manipulation" for="customCheckLogin">
								<span>{{trans('auth.remember_me')}}</span>
							</label>
						</div>

						<div class="custom-control custom-control-alternative custom-checkbox display-none mb-3" id="agree_gdpr">
							<input class="custom-control-input" id="customCheckRegister" type="checkbox" name="agree_gdpr">
								<label class="custom-control-label min-h-[44px] sm:min-h-[40px] flex items-center touch-manipulation" for="customCheckRegister">
									<span>{{trans('admin.i_agree_gdpr')}}
										<a href="{{$settings->link_privacy}}" target="_blank">{{trans('admin.privacy_policy')}}</a>
									</span>
								</label>
						</div>

						<div class="alert alert-danger display-none mb-0 mt-3" id="errorLogin">
								<ul class="list-unstyled m-0" id="showErrorsLogin"></ul>
							</div>

							<div class="alert alert-success display-none mb-0 mt-3" id="checkAccount"></div>

						<div class="text-center mt-4">
							<button type="submit" id="btnLoginRegister" class="btn btn-primary w-100 min-h-[44px] sm:min-h-[40px] text-base sm:text-sm font-medium touch-manipulation"><i></i> {{trans('auth.login')}}</button>

							<div class="w-100 mt-3">
								<button type="button" class="btn e-none p-0 min-h-[44px] touch-manipulation text-gray-600 hover:text-gray-800" data-dismiss="modal">{{ __('admin.cancel') }}</button>
							</div>
						</div>
					</form>

					@if ($settings->captcha == 'on')
						<small class="btn-block text-center mt-4 text-gray-500">{{trans('auth.protected_recaptcha')}} <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">{{trans('general.privacy')}}</a> - <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer">{{trans('general.terms')}}</a></small>
					@endif

					@if ($settings->registration_active == '1')
					<div class="row mt-4">
						<div class="col-12 text-center">
							<a href="javascript:void(0);" id="toggleLogin" class="touch-manipulation min-h-[44px] inline-flex items-center" data-not-account="{{trans('auth.not_have_account')}}" data-already-account="{{trans('auth.already_have_an_account')}}" data-text-login="{{trans('auth.login')}}" data-text-register="{{trans('auth.sign_up')}}">
								<strong>{{trans('auth.not_have_account')}}</strong>
							</a>
						</div>
					</div>
					@endif

				</div><!-- ./ card-body -->
			</div>
		</div>
	</div>
</div>
