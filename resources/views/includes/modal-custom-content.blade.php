<!-- Start Modal payPerViewForm -->
<div class="modal fade" id="customContentForm{{$sale->id}}" tabindex="-1" role="dialog" aria-labelledby="modal-form" aria-hidden="true">
	<div class="modal-dialog modal- modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-body p-0">
				<div class="card bg-white shadow border-0">

					<div class="card-body px-lg-5 py-lg-5 position-relative">

						<div class="mb-4 position-relative">
							@if($sale->products()->type == 'physical')
								<strong>{{ __('general.details_ordered_product') }}</strong>
							@else
								<strong>{{ __('general.details_custom_content') }}</strong>
							@endif
							<small data-dismiss="modal" class="btn-cancel-msg"><i class="bi bi-x-lg"></i></small>
						</div>

						<h6>
							{{ __('auth.email') }}:

							@if (! isset($sale->user()->email))
								{{ trans('general.no_available') }}
							@else
							{{ $sale->user()->email }}
						@endif
						</h6>
						
						@if($sale->products()->type == 'physical')
							<p><strong>{{ __('general.quantity') }}: </strong>{{ $sale->quantity }}</p>
							<h6>{{ __('general.address') }}:</h6>
							<p>
								{{ $sale->address_line_1 }}, <br />
								{{ $sale->address_line_2 }}, <br />
								{{ $sale->city }}, {{ $sale->state }}, {{ $sale->country }} - {{ $sale->pincode }}
							</p>
						@endif

						<p>
							{!! Helper::checkText($sale->description_custom_content) !!}
						</p>

					</div><!-- End card-body -->
				</div><!-- End card -->
			</div><!-- End modal-body -->
		</div><!-- End modal-content -->
	</div><!-- End Modal-dialog -->
</div><!-- End Modal BuyNow -->
