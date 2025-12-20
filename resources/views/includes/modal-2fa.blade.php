{{-- Mobile-optimized 2FA Modal --}}
<div class="modal fade" id="modal2fa" tabindex="-1" role="dialog" aria-labelledby="modal2faLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-sm" role="document">
		<div class="modal-content max-h-[100vh] sm:max-h-[90vh] flex flex-col rounded-none sm:rounded-lg overflow-hidden">
			{{-- Header --}}
			<div class="modal-header flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
				<h5 class="modal-title text-lg font-semibold text-gray-900 dark:text-white m-0 flex items-center gap-2" id="modal2faLabel">
					<i class="bi bi-shield-lock"></i> {{ trans('general.two_step_auth') }}
				</h5>
				<button
					type="button"
					class="close min-h-[44px] min-w-[44px] sm:min-h-[36px] sm:min-w-[36px] flex items-center justify-center text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 active:text-gray-600 touch-manipulation rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 -mr-2 transition-colors"
					data-dismiss="modal"
					aria-label="Close"
				>
					<i class="bi bi-x-lg text-lg"></i>
				</button>
			</div>

			{{-- Body --}}
			<div class="modal-body flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-5 bg-white dark:bg-gray-800">
				<p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ trans('general.2fa_title_modal') }}</p>

				<form method="post" action="{{ url('verify/2fa') }}" id="formVerify2fa">
					@csrf

					@if (request()->route()->named('profile'))
						<input type="hidden" name="isProfileTwoFA" value="true">
					@endif

					<div class="form-group mb-3">
						<input
							type="number"
							autocomplete="one-time-code"
							id="onlyNumber"
							onKeyPress="if(this.value.length==4) return false;"
							class="form-control min-h-[48px] sm:min-h-[44px] text-center text-xl tracking-widest font-mono touch-manipulation"
							name="code"
							placeholder="{{ trans('general.enter_code') }}"
							inputmode="numeric"
							pattern="[0-9]*"
							maxlength="4"
							autofocus
						>
					</div>

					<div class="mb-3">
						<a href="javascript:void(0);" class="resend_code text-sm touch-manipulation inline-flex items-center gap-1 min-h-[44px]">
							<i class="bi bi-arrow-counterclockwise"></i> <span id="resendCode">{{ trans('general.resend_code') }}</span>
						</a>
					</div>

					<div class="alert alert-danger display-none" id="errorModal2fa">
						<ul class="list-unstyled m-0" id="showErrorsModal2fa"></ul>
					</div>
				</form>
			</div>

			{{-- Footer --}}
			<div class="modal-footer flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 justify-end pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:pb-4">
				<button
					type="button"
					class="btn btn-outline-secondary w-full sm:w-auto min-h-[44px] sm:min-h-[40px] touch-manipulation"
					data-dismiss="modal"
				>
					{{ trans('admin.cancel') }}
				</button>
				<button
					type="submit"
					form="formVerify2fa"
					id="btn2fa"
					class="btn btn-primary w-full sm:w-auto min-h-[44px] sm:min-h-[40px] touch-manipulation"
				>
					<i></i> {{ trans('auth.send') }}
				</button>
			</div>
		</div>
	</div>
</div><!-- End Modal 2FA -->
