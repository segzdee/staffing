{{-- Mobile-optimized Custom Content / Order Details Modal --}}
<div class="modal fade" id="customContentForm{{$sale->id}}" tabindex="-1" role="dialog" aria-labelledby="customContentForm{{$sale->id}}Label" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
		<div class="modal-content max-h-[100vh] sm:max-h-[90vh] flex flex-col rounded-none sm:rounded-lg overflow-hidden">
			{{-- Header --}}
			<div class="modal-header flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
				<h5 class="modal-title text-lg font-semibold text-gray-900 dark:text-white m-0" id="customContentForm{{$sale->id}}Label">
					@if($sale->products()->type == 'physical')
						{{ __('general.details_ordered_product') }}
					@else
						{{ __('general.details_custom_content') }}
					@endif
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
				{{-- Email --}}
				<div class="mb-4">
					<h6 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('auth.email') }}</h6>
					<p class="text-base text-gray-900 dark:text-white break-words">
						@if (! isset($sale->user()->email))
							<span class="text-gray-400">{{ trans('general.no_available') }}</span>
						@else
							{{ $sale->user()->email }}
						@endif
					</p>
				</div>

				@if($sale->products()->type == 'physical')
					{{-- Quantity --}}
					<div class="mb-4">
						<h6 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('general.quantity') }}</h6>
						<p class="text-base text-gray-900 dark:text-white">{{ $sale->quantity }}</p>
					</div>

					{{-- Shipping Address --}}
					<div class="mb-4">
						<h6 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('general.address') }}</h6>
						<div class="text-base text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
							<p class="m-0">{{ $sale->address_line_1 }}</p>
							@if($sale->address_line_2)
								<p class="m-0">{{ $sale->address_line_2 }}</p>
							@endif
							<p class="m-0">{{ $sale->city }}, {{ $sale->state }}</p>
							<p class="m-0">{{ $sale->country }} - {{ $sale->pincode }}</p>
						</div>
					</div>
				@endif

				{{-- Description --}}
				@if($sale->description_custom_content)
				<div class="mb-4">
					<h6 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('general.description') }}</h6>
					<div class="text-base text-gray-900 dark:text-white prose prose-sm dark:prose-invert max-w-none">
						{!! Helper::checkText($sale->description_custom_content) !!}
					</div>
				</div>
				@endif
			</div>

			{{-- Footer --}}
			<div class="modal-footer flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 justify-end pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:pb-4">
				<button
					type="button"
					class="btn btn-primary w-full sm:w-auto min-h-[44px] sm:min-h-[40px] touch-manipulation"
					data-dismiss="modal"
				>
					{{ __('admin.close') }}
				</button>
			</div>
		</div>
	</div>
</div><!-- End Modal Custom Content -->
