{{-- Mobile-optimized New Message Modal --}}
<div class="modal fade" id="newMessageForm" tabindex="-1" role="dialog" aria-labelledby="newMessageFormLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-sm" role="document">
		<div class="modal-content max-h-[100vh] sm:max-h-[90vh] flex flex-col rounded-none sm:rounded-lg overflow-hidden">
			{{-- Header --}}
			<div class="modal-header flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
				<h5 class="modal-title text-lg font-semibold text-gray-900 dark:text-white m-0" id="newMessageFormLabel">
					{{trans('general.new_message')}}
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
				@if (auth()->user()->verified_id == 'yes' && request()->is('messages') && auth()->user()->totalSubscriptionsActive() > 1)
					<div class="mb-4">
						<a href="javascript:void(0);" data-toggle="modal" data-target="#newMessageMassive" data-dismiss="modal" class="btn btn-primary w-100 min-h-[44px] sm:min-h-[40px] flex items-center justify-center gap-2 touch-manipulation">
							<i class="feather icon-users"></i> {{ trans('general.to_all_my_subscribers') }}
						</a>
					</div>
				@endif

				<div class="position-relative">
					<span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
						<i class="fa fa-search"></i>
					</span>

					<input
						class="form-control pl-10 min-h-[44px] sm:min-h-[40px] rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white touch-manipulation text-base sm:text-sm"
						id="searchCreator"
						type="search"
						name="q"
						autocomplete="off"
						inputmode="search"
						placeholder="{{ auth()->user()->verified_id == 'yes' ? trans('general.search') : trans('general.find_user') }}"
						aria-label="Search"
					>
				</div>

				<div class="w-100 text-center mt-4 display-none" id="spinner">
					<span class="spinner-border align-middle text-primary"></span>
				</div>

				<div id="containerUsers" class="mt-3 space-y-2"></div>
			</div>
		</div>
	</div>
</div><!-- End Modal new Message -->

{{-- Mobile-optimized New Message Massive Modal --}}
<div class="modal fade modalEditPost" id="newMessageMassive" tabindex="-1" role="dialog" aria-labelledby="newMessageMassiveLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
		<div class="modal-content max-h-[100vh] sm:max-h-[90vh] flex flex-col rounded-none sm:rounded-lg overflow-hidden">
			{{-- Header --}}
			<div class="modal-header flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
				<h5 class="modal-title text-lg font-semibold text-gray-900 dark:text-white m-0" id="newMessageMassiveLabel">
					{{trans('general.new_message_all_subscribers')}}
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
			<div class="modal-body flex-1 overflow-y-auto overscroll-contain p-0 bg-white dark:bg-gray-800">
				<form method="POST" action="{{url('new/message/massive')}}" enctype="multipart/form-data" id="formSendMsg">
					<input type="file" name="zip" id="zipFile" accept="application/x-zip-compressed" class="visibility-hidden">
					@csrf

					<div class="card mb-0 border-0 shadow-none">
						<div class="blocked display-none"></div>
						<div class="card-body pb-0 px-4 sm:px-5">
							<div class="media">
								<div class="media-body">
									<textarea
										rows="5"
										cols="40"
										placeholder="{{trans('general.write_something')}}"
										class="form-control textareaAutoSize border-0 resize-none text-base sm:text-sm min-h-[120px] touch-manipulation"
										id="message"
										name="message"
									></textarea>
								</div>
							</div>

							{{-- Alert --}}
							<div class="alert alert-danger my-3 display-none" id="errorMsg">
								<ul class="list-unstyled m-0" id="showErrorMsg"></ul>
							</div>
						</div>

						<div class="card-footer bg-white dark:bg-gray-800 border-0 pt-0 position-relative px-4 sm:px-5 pb-4">
							<div class="progress-upload-cover" style="width: 0%; top:0;"></div>

							<div class="form-group display-none mt-2" id="price">
								<div class="input-group mb-2">
									<div class="input-group-prepend">
										<span class="input-group-text min-h-[44px] sm:min-h-[40px]">{{$settings->currency_symbol}}</span>
									</div>
									<input class="form-control isNumber min-h-[44px] sm:min-h-[40px] text-base sm:text-sm" value="" autocomplete="off" name="price" placeholder="{{trans('general.price')}}" type="text" inputmode="decimal">
								</div>
							</div>

							<div class="w-100">
								<span id="previewImage"></span>
								<a href="javascript:void(0)" id="removePhoto" class="text-danger p-2 display-none btn-tooltip min-h-[44px] min-w-[44px] inline-flex items-center justify-center" data-toggle="tooltip" data-placement="top" title="{{trans('general.delete')}}">
									<i class="fa fa-times-circle"></i>
								</a>
							</div>

							<input type="file" name="media[]" id="file" accept="image/*,video/mp4,video/x-m4v,video/quicktime,audio/mp3" multiple class="visibility-hidden filepond">

							<div class="flex flex-wrap items-center justify-between gap-2 mt-3">
								<div class="flex flex-wrap gap-1">
									<button type="button" class="btnMultipleUpload btn btn-upload btn-tooltip min-h-[44px] min-w-[44px] p-2 @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill touch-manipulation" data-toggle="tooltip" data-placement="top" title="{{trans('general.upload_media')}} ({{ trans('general.media_type_upload') }})">
										<i class="feather icon-image f-size-25"></i>
									</button>

									<button type="button" class="btn btn-upload btn-tooltip min-h-[44px] min-w-[44px] p-2 @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill touch-manipulation" data-toggle="tooltip" data-placement="top" title="{{trans('general.upload_file_zip')}}" onclick="$('#zipFile').trigger('click')">
										<i class="bi bi-file-earmark-zip f-size-25"></i>
									</button>

									<button type="button" id="setPrice" class="btn btn-upload btn-tooltip min-h-[44px] min-w-[44px] p-2 @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill touch-manipulation" data-toggle="tooltip" data-placement="top" title="{{trans('general.set_price_for_msg')}}">
										<i class="feather icon-tag f-size-25"></i>
									</button>
								</div>

								<div class="position-relative">
									<div class="btn-blocked display-none"></div>
									<button disabled type="submit" id="button-reply-msg" class="btn btn-primary rounded-pill min-h-[44px] min-w-[44px] px-4 touch-manipulation">
										<i class="far fa-paper-plane"></i>
									</button>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div><!-- End Modal New Message Massive -->
