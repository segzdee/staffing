@php 
use Carbon\Carbon;

@endphp
<style>
	.tap-item {
		background-color: #a05ecc;
		width: 20px;
		height: 20px;
		min-width: 20px;
		border-radius: 4px;
	}

	.tap-view-item, .open-checkbox-item {
		border: 1px solid #dedede;
		border-radius: 12px;
	}
	.camera-icon{
		background-color: black;
		width: 36px;
		height: 36px;
		border-radius: 40px;
		color: white;
		cursor: pointer;
	}
	.form-check-input{
		border: 2px solid #a05ecc
	}
	.form-check-input:checked {
		background-color: #a05ecc;
		border-color: #a05ecc;
	}
	.form-check-input:focus {
		border-color: #a05ecc;
		box-shadow: 0 0 0 0;
	}
	.tab-to-view-btn,.tab-to-open-btn{
		border:none;
            outline: none;
            width: 100% !important;
            background-color: inherit;
	}
	.tab-to-view-btn:hover{
		box-shadow: none !important;
	}
	.tab-to-open-btn:hover{
		box-shadow: none !important;
	}
	.tab-to-view-btn-24:hover{
		box-shadow: none !important;
	}
	.btn:hover{
		box-shadow: none !important;
	}
	.tap-view-item h6{
		margin-left: 10px;
	}
</style>
@if ($mediaImageVideoTotal == 1)

@foreach ($mediaImageVideo as $media)
	@php
		$urlImg = url('files/messages', $msg->id).'/'.$media->file;

		if ($media->width && $media->height > $media->width) {
			$styleImgVertical = 'img-vertical-lg';
		} else {
			$styleImgVertical = null;
		}
	@endphp

	@if ($media->type == 'image')
		<div class="media-grid-1">
			
			@if($msg->from_user_id==auth()->user()->id)
			<a href="{{ $urlImg }}" class="media-wrapper glightbox {{$styleImgVertical}} " data-gallery="gallery{{$msg->id}}" style="background-image: url('{{$urlImg}}?w=960&h=980')">
				<img src="{{$urlImg}}?w=960&h=980" {!! $media->width ? 'width="'. $media->width .'"' : null !!} {!! $media->height ? 'height="'. $media->height .'"' : null !!} class="post-img-grid">
		</a>
			@else
			<!---remove never --->
			@if($media->is_deleted=="remove-never")
			<a href="{{ $urlImg }}" class="media-wrapper glightbox {{$styleImgVertical}} " data-gallery="gallery{{$msg->id}}" style="background-image: url('{{$urlImg}}?w=960&h=980')">
				<img src="{{$urlImg}}?w=960&h=980" {!! $media->width ? 'width="'. $media->width .'"' : null !!} {!! $media->height ? 'height="'. $media->height .'"' : null !!} class="post-img-grid">
		</a>
			@endif
			<!--end remove never --->
			@if($media->watch==1)
			
			  @if(Carbon::parse($media->created_at)->format("Y-m-d")<Carbon::today()->format("Y-m-d") && $media->is_deleted=="remove-after-24" && $media->watch==1)
			  <button class="tab-to-open-btn btn" key="{{$key}}" form="{{$msg->from_user_id}}" id="{{$media->id}}" >
				<div class="open-checkbox-item">
					<div class="d-flex justify-content-between p-4">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
							<label class="form-check-label" for="flexCheckDefault">
								<h6>Openend </h6>
							</label>
						</div>
						<div class="camera-icon d-flex justify-content-center align-items-center"><i class="bi bi-camera"></i></div>
					</div>
				</div>
			</button>
			  @elseif($media->is_deleted=="remove-after-view" && $media->watch==1)
			  
			  <button class="tab-to-open-btn btn" key="{{$key}}" form="{{$msg->from_user_id}}" id="{{$media->id}}" >
				<div class="open-checkbox-item">
					<div class="d-flex justify-content-between p-4">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
							<label class="form-check-label" for="flexCheckDefault">
								<h6>Openend</h6>
							</label>
						</div>
						<div class="camera-icon d-flex justify-content-center align-items-center"><i class="bi bi-camera"></i></div>
					</div>
				</div>
			</button>
			
			

			  @endif
			@else
			@if($media->watch==0 && $media->is_deleted=="remove-after-view")
			<a href="{{ $urlImg }}" class="group-media-link  glightbox {{$styleImgVertical}}" data-gallery="gallery{{$msg->id}}" data="{{$media->id}}" is-delete="{{$media->is_deleted}}"> 
				{{-- style="background-image: url('{{$urlImg}}?w=960&h=980')" --}}
				{{-- media-wrapper --}}
				 <img src="{{$urlImg}}?w=960&h=980" {!! $media->width ? 'width="'. $media->width .'"' : null !!} {!! $media->height ? 'height="'. $media->height .'"' : null !!} class="post-img-grid">
				<button class="tab-to-view-btn btn" key="{{$key}}" form="{{$msg->from_user_id}}" id="{{$media->id}}" is-delete="{{$media->is_deleted}}" style="min-width: :300px !important;">
	
					<div class="tap-view-item d-flex p-4 align-items-center">
						<div class="tap-item me-3"></div>
						<h6 class="mb-0">Tap to View</h6>
					</div>
				
				</button>
		</a>
			@else
			<a href="{{ $urlImg }}" class="media-wrapper glightbox {{$styleImgVertical}} " data-gallery="gallery{{$msg->id}}" style="background-image: url('{{$urlImg}}?w=960&h=980')">
			<img src="{{$urlImg}}?w=960&h=980" {!! $media->width ? 'width="'. $media->width .'"' : null !!} {!! $media->height ? 'height="'. $media->height .'"' : null !!} class="post-img-grid">
	</a>
			@endif
		
			@endif
			@endif
		</div>
@endif


@if ($media->type == 'video' && $media->uploadedto_aws=="Y")
      
	@if($msg->from_user_id==auth()->user()->id)
	<div class="container-media-msg h-auto">
		<video class="js-player {{$classInvisible}}" controls style="height: 400px;" @if ($media->video_poster) data-poster="{{ Helper::getFile(config('path.messages').$media->video_poster ) }}" @endif>
		<source src="{{ Helper::getFile(config('path.messages').$media->file ) }}"  type="video/mp4" />
	</video>
</div>
	@else
	@if($media->watch==1)
	<button class="tab-to-open-btn btn btn-primary" key="{{$key}}" form="{{$msg->from_user_id}}" id="{{$media->id}}" >
		<div class="open-checkbox-item">
			<div class="d-flex justify-content-between p-4">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
					<label class="form-check-label" for="flexCheckDefault">
						<h6>Openend</h6>
					</label>
				</div>
				<div class="camera-icon d-flex justify-content-center align-items-center"><i class="bi bi-camera"></i></div>
			</div>
		</div>
	</button>
	<div class="container-media-msg h-auto d-none video-box-{{$key}}" @if ($media->video_poster) data-poster="{{ Helper::getFile(config('path.messages').$media->video_poster ) }}" @endif src="{{ Helper::getFile(config('path.messages').$media->file ) }}">
	
</div>
	@else
	<div class="col-sm-12">
		<button class="tab-to-view-btn btn" key="{{$key}}" form="{{$msg->from_user_id}}" id="{{$media->id}}">
	
			<div class="tap-view-item d-flex p-4 align-items-center">
				<div class="tap-item me-3"></div>
				<h6 class="mb-0">Tap to View</h6>
			</div>
		
		</button>
	</div>
	<div class="container-media-msg h-auto d-none video-box-{{$media->id}}" @if ($media->video_poster) data-poster="{{ Helper::getFile(config('path.messages').$media->video_poster ) }}" @endif src="{{ Helper::getFile(config('path.messages').$media->file ) }}">
		
	</video>
</div>
	@endif
	@endif
	
@endif


@if ($media->type == 'video' && $media->uploadedto_aws=="N")

	@if($msg->from_user_id==auth()->user()->id)
	<div class="container-media-msg h-auto">
		<video class="js-player {{$classInvisible}}" controls style="height: 400px;" @if ($media->video_poster) data-poster="{{ Helper::getFile(config('path.messages').$media->video_poster ) }}" @endif>
		<source src="{{ Helper::getFile(config('path.messages').$media->file ) }}"  type="video/mp4" />
	</video>
</div>
	@else

	<!---remove never video ------->
	@if($media->is_deleted=="remove-never")
	<div class="container-media-msg h-auto">
		<video class="js-player {{$classInvisible}}" controls style="height: 400px;" @if ($media->video_poster) data-poster="{{ Helper::getFile(config('path.messages').$media->video_poster ) }}" @endif>
		<source src="{{ Helper::getFile(config('path.messages').$media->file ) }}"  type="video/mp4" />
	</video>
</div>
	@endif
	<!---end remove never video ----->
	@if($media->watch==1)
	 @if($media->watch==1 && $media->is_deleted=="remove-after-24" && Carbon::parse($media->created_at)->format("Y-m-d")<Carbon::today()->format("Y-m-d"))
		<button class="tab-to-open-btn btn" key="{{$key}}" form="{{$msg->from_user_id}}" id="{{$media->id}}">
			<div class="open-checkbox-item">
				<div class="d-flex justify-content-between p-4">
					<div class="form-check">
						<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
						<label class="form-check-label" for="flexCheckDefault">
							<h6>Video {{$media->is_deleted}} Hours</h6>
						</label>
					</div>
					<div class="camera-icon d-flex justify-content-center align-items-center"><i class="bi bi-camera"></i></div>
				</div>
			</div>
		</button>
		<div class="container-media-msg h-auto d-none video-box-{{$media->id}}" @if ($media->video_poster) data-poster="{{ Helper::getFile(config('path.messages').$media->video_poster ) }}" @endif src="{{ Helper::getFile(config('path.messages').$media->file ) }}">
			
		
		</div>
		
	 @elseif($media->watch==1 && $media->is_deleted=="remove-after-view")
	 <button class="tab-to-open-btn btn" key="{{$key}}" form="{{$msg->from_user_id}}" id="{{$media->id}}">
		<div class="open-checkbox-item">
			<div class="d-flex justify-content-between p-4">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
					<label class="form-check-label" for="flexCheckDefault">
						<h6>Openend</h6>
					</label>
				</div>
				<div class="camera-icon d-flex justify-content-center align-items-center"><i class="bi bi-camera"></i></div>
			</div>
		</div>
	</button>
	<div class="container-media-msg h-auto d-none video-box-{{$media->id}}" @if ($media->video_poster) data-poster="{{ Helper::getFile(config('path.messages').$media->video_poster ) }}" @endif src="{{ Helper::getFile(config('path.messages').$media->file ) }}">
		
	
	</div>
	
	 @endif
	 @else
	  <!---- single video show for 24 hours --->
	  @if($media->watch==0 && $media->is_deleted=="remove-after-24")
	  <div class="container-media-msg h-auto">
		<video class="js-player {{$classInvisible}}" controls style="height: 400px;" @if ($media->video_poster) data-poster="{{ Helper::getFile(config('path.messages').$media->video_poster ) }}" @endif>
		<source src="{{ Helper::getFile(config('path.messages').$media->file ) }}"  type="video/mp4" />
	</video>
</div>
	  @endif
	  <!--end video show for 24 hours --->


	  <!--- single video show for tab to view ----->

	  @if($media->watch==0 && $media->is_deleted=="remove-after-view")
	  <a href="{{ Helper::getFile(config('path.messages').$media->file ) }}" class=" glightbox  group-media-link" data-gallery="gallery{{$msg->id}}"  data="{{$media->id}}"  is-delete="{{$media->is_deleted}}"  >
		{{-- style="background-image: url('{{ $videoPoster ?? $urlMedia }}?w=960&h=980')" --}}
		

		
	<video playsinline muted class="video-poster-html d-none " data-poster="{{ Helper::getFile(config('path.messages').$media->video_poster ) }}">
				<source src="{{ Helper::getFile(config('path.messages').$media->file ) }}" type="video/mp4" />
			</video>
		

		
		<button class="btn" key="{{$key}}" form="{{$msg->from_user_id}}" id="{{$media->id}}" is-delete="{{$media->is_deleted}}" style="min-width: 100% !important;">

			<div class="tap-view-item d-flex p-4 align-items-center">
				<div class="tap-item me-3"></div>
				<h6 class="mb-0">Tap to View</h6>
			</div>
		
		</button>
	</a> 
	  @endif
	  <!---end video show for tab to view ---->
	

	
	
	@endif
	@endif
@endif



@endforeach

@endif
<!---multiple images or videos show ---->

@if ($mediaImageVideoTotal >= 2)

     
	<div class="media-grid-{{ $mediaImageVideoTotal > 4 ? 4 : $mediaImageVideoTotal }}">

@foreach ($mediaImageVideo as $media)
  
	@php

	if ($media->type == 'video') {
		
		if($media->uploadedto_aws!=="Y"){
			$urlMedia =config('path.videos').$media->file;
			$videoPoster =config('path.videos').$media->video_poster;

		}else{
			$urlMedia =  Helper::getFile(config('path.messages').$media->file);
			$videoPoster = $media->video_poster ? Helper::getFile(config('path.messages').$media->video_poster) : false;
		}


		
	} else {
		$urlMedia =  url("files/messages", $msg->id).'/'.$media->file;
		$videoPoster = null;
	}

		$nth++;
	@endphp

		@if ($media->type == 'image' || $media->type == 'video')

			@if($msg->from_user_id==auth()->user()->id)
		
            <a href="{{ $urlMedia }}" class="media-wrapper glightbox" data-gallery="gallery{{$msg->id}}"  data="{{$media->id}}" style="background-image:url('{{ $videoPoster ?? $urlMedia }}?w=960&h=980')">
				
				@if ($nth == 4 && $mediaImageVideoTotal > 4)
		        <span class="more-media">
							<h2>+{{ $mediaImageVideoTotal - 4 }}</h2>
						</span>
		    @endif

				@if ($media->type == 'video')
					<span class="button-play">
						<i class="bi bi-play-fill text-white"></i>
					</span>
				@endif

				@if (! $videoPoster)
					<video playsinline muted class="video-poster-html">
						<source src="{{ $urlMedia }}" type="video/mp4"  hsvdghvhd/>
					</video>
				@endif

				@if ($videoPoster)
					<img src="{{ $videoPoster ?? $urlMedia }}?w=960&h=980" {!! $media->width ? 'width="'. $media->width .'"' : null !!} {!! $media->height ? 'height="'. $media->height .'"' : null !!} class="post-img-grid" dsggdsc>
				@endif
				
				
			</a>

			@else

			@if($media->is_deleted=="remove-never")
			<a href="{{ $urlMedia }}" class="media-wrapper glightbox dfjhfdgh" data-gallery="gallery{{$msg->id}}"  data="{{$media->id}}" style="background-image:url('{{ $videoPoster ?? $urlMedia }}?w=960&h=980')">
				
				@if ($nth == 4 && $mediaImageVideoTotal > 4)
		        <span class="more-media">
							<h2>+{{ $mediaImageVideoTotal - 4 }}</h2>
						</span>
		    @endif

				@if ($media->type == 'video')
					<span class="button-play">
						<i class="bi bi-play-fill text-white"></i>
					</span>
				@endif

				@if (! $videoPoster)
					<video playsinline muted class="video-poster-html">
						<source src="{{ $urlMedia }}" type="video/mp4" />
					</video>
				@endif

				@if ($videoPoster)
					<img src="{{ $videoPoster ?? $urlMedia }}?w=960&h=980" {!! $media->width ? 'width="'. $media->width .'"' : null !!} {!! $media->height ? 'height="'. $media->height .'"' : null !!} class="post-img-grid">
				@endif
				
				
			</a>
			@endif
			
		
			  @if($media->watch==1)
			  
			  @if($media->is_deleted=="remove-after-view" && $media->watch==1)
			  <button class="tab-to-open-btn btn" key="{{$key}}" form="{{$msg->from_user_id}}" id="{{$media->id}}" style="min-width: 300px !important;">
				<div class="open-checkbox-item">
					<div class="d-flex justify-content-between p-4">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
							<label class="form-check-label" for="flexCheckDefault">
								<h6>Openend</h6>
							</label>
						</div>
						<div class="camera-icon d-flex justify-content-center align-items-center"><i class="bi bi-camera"></i></div>
					</div>
				</div>
			</button>
			  @else
			  <a href="{{ $urlMedia }}" class="media-wrapper glightbox dfjhfdgh ashish group-media-link" data-gallery="gallery{{$msg->id}}"  data="{{$media->id}}" style="background-image:url('{{ $videoPoster ?? $urlMedia }}?w=960&h=980')" is-delete="{{$media->is_deleted}}">
				
				@if ($nth == 4 && $mediaImageVideoTotal > 4)
				<span class="more-media">
							<h2>+{{ $mediaImageVideoTotal - 4 }}</h2>
						</span>
			@endif

				@if ($media->type == 'video')
					<span class="button-play">
						<i class="bi bi-play-fill text-white"></i>
					</span>
				@endif

				@if (! $videoPoster)
					<video playsinline muted class="video-poster-html">
						<source src="{{ $urlMedia }}" type="video/mp4" />
					</video>
				@endif

				@if ($videoPoster)
					<img src="{{ $videoPoster ?? $urlMedia }}?w=960&h=980" {!! $media->width ? 'width="'. $media->width .'"' : null !!} {!! $media->height ? 'height="'. $media->height .'"' : null !!} class="post-img-grid">
				@endif
				
				
			</a> 
			  @endif
			  
			  @if($media->is_deleted=="remove-after-24" && Carbon::parse($media->created_at)->format("Y-m-d")<Carbon::today()->format("Y-m-d") && $media->watch==1)
				<button class="tab-to-open-btn btn" key="{{$key}}" form="{{$msg->from_user_id}}" id="{{$media->id}}" style="min-width: 300px !important;">
					<div class="open-checkbox-item">
						<div class="d-flex justify-content-between p-4">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
								<label class="form-check-label" for="flexCheckDefault">
									<h6>{{$media->type=="image"?"Photo":"Video"}} has been remove after 24 Hrs</h6>
								</label>
							</div>
							<div class="camera-icon d-flex justify-content-center align-items-center"><i class="bi bi-camera"></i></div>
						</div>
					</div>
				</button>
				@else

			 
			 
			 
			  @endif
			  @else

			  <!---whenenver video not watch ----->
			     @if($media->is_deleted=="remove-after-view")
				 <a href=" {{$urlMedia}}" class=" glightbox  group-media-link" data-gallery="gallery{{$msg->id}}"  data="{{$media->id}}"  is-delete="{{$media->is_deleted}}"  >
					{{-- style="background-image: url('{{ $videoPoster ?? $urlMedia }}?w=960&h=980')" --}}
					@if ($nth == 4 && $mediaImageVideoTotal > 4)
					<span class="more-media">
								<h2>+{{ $mediaImageVideoTotal - 4 }}</h2>
							</span>
				@endif
	
					
					
					@if ($media->type == 'video')
						<span class="button-play d-none">
							<i class="bi bi-play-fill text-white"></i>
						</span>
					@endif
	
					@if (! $videoPoster)
						<video playsinline muted class="video-poster-html">
							<source src="{{ $urlMedia }}" type="video/mp4" />
						</video>
					@endif
	
					@if ($videoPoster)
						<img src="{{ $videoPoster ?? $urlMedia }}?w=960&h=980" {!! $media->width ? 'width="'. $media->width .'"' : null !!} {!! $media->height ? 'height="'. $media->height .'"' : null !!} class="post-img-grid">
					
					@endif
					<button class="tab-to-view-btn btn" key="{{$key}}" form="{{$msg->from_user_id}}" id="{{$media->id}}" is-delete="{{$media->is_deleted}}" style="min-width: 300px !important;">
		
						<div class="tap-view-item d-flex p-4 align-items-center">
							<div class="tap-item me-3"></div>
							<h6 class="mb-0">Tap to View</h6>
						</div>
					
					</button>
				</a> 
				 @else
				 {{-- <a href="{{ $urlMedia }}" class="media-wrapper glightbox dfjhfdgh122 {{$media->is_deleted}} group-media-link" data-gallery="gallery{{$msg->id}}"  data="{{$media->id}}" style="background-image:url('{{ $videoPoster ?? $urlMedia }}?w=960&h=980')" is-delete="{{$media->is_deleted}}">
				
					@if ($nth == 4 && $mediaImageVideoTotal > 4)
					<span class="more-media">
								<h2>+{{ $mediaImageVideoTotal - 4 }}</h2>
							</span>
				@endif
	
					@if ($media->type == 'video')
						<span class="button-play">
							<i class="bi bi-play-fill text-white"></i>
						</span>
					@endif
	
					@if (! $videoPoster)
						<video playsinline muted class="video-poster-html">
							<source src="{{ $urlMedia }}" type="video/mp4" />
						</video>
					@endif
	
					@if ($videoPoster)
						<img src="{{ $videoPoster ?? $urlMedia }}?w=960&h=980" {!! $media->width ? 'width="'. $media->width .'"' : null !!} {!! $media->height ? 'height="'. $media->height .'"' : null !!} class="post-img-grid">
					@endif
					
					
				</a>  --}}
				 @endif

			  <!---whenenver video not watch ---->
			  
			
			  @endif
			
			@endif
			

		
		@endif

@endforeach

</div><!-- img-grid -->

@endif

@foreach ($msg->media as $media)

	@if ($media->type == 'music')
	<div class="wrapper-media-music @if ($mediaCount >= 2) mt-2 @endif">
		<audio class="js-player {{$classInvisible}}" controls>
		<source src="{{Helper::getFile(config('path.messages').$media->file)}}" type="audio/mp3">
		Your browser does not support the audio tag.
	</audio>
</div>
	@endif

@if ($media->type == 'zip')
	<a href="{{url('download/message/file', $msg->id)}}" class="d-block text-decoration-none @if ($mediaCount >= 2) mt-2 @endif">
	 <div class="card">
		 <div class="row no-gutters">
			 <div class="col-md-3 text-center bg-primary">
				 <i class="far fa-file-archive m-2 text-white" style="font-size: 40px;"></i>
			 </div>
			 <div class="col-md-9">
				 <div class="card-body py-2 px-4">
					 <h6 class="card-title text-primary text-truncate mb-0">
						 {{$media->file_name}}.zip
					 </h6>
					 <p class="card-text">
						 <small class="text-muted">{{$media->file_size}}</small>
					 </p>
				 </div>
			 </div>
		 </div>
	 </div>
	 </a>
	 @endif

 @endforeach

 @if ($msg->tip == 'yes')
	<div class="card">
		 <div class="row no-gutters">
			 <div class="col-md-12">
				 <div class="card-body py-2 px-4">
					 <h6 class="card-title text-primary text-truncate mb-0">
						 <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" class="bi bi-coin mr-1" viewBox="0 0 16 16">
							 <path d="M5.5 9.511c.076.954.83 1.697 2.182 1.785V12h.6v-.709c1.4-.098 2.218-.846 2.218-1.932 0-.987-.626-1.496-1.745-1.76l-.473-.112V5.57c.6.068.982.396 1.074.85h1.052c-.076-.919-.864-1.638-2.126-1.716V4h-.6v.719c-1.195.117-2.01.836-2.01 1.853 0 .9.606 1.472 1.613 1.707l.397.098v2.034c-.615-.093-1.022-.43-1.114-.9H5.5zm2.177-2.166c-.59-.137-.91-.416-.91-.836 0-.47.345-.822.915-.925v1.76h-.005zm.692 1.193c.717.166 1.048.435 1.048.91 0 .542-.412.914-1.135.982V8.518l.087.02z"/>
							 <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
							 <path fill-rule="evenodd" d="M8 13.5a5.5 5.5 0 1 0 0-11 5.5 5.5 0 0 0 0 11zm0 .5A6 6 0 1 0 8 2a6 6 0 0 0 0 12z"/>
						 </svg> {{__('general.tip'). ' -- ' .Helper::amountWithoutFormat($msg->tip_amount)}}
					 </h6>
				 </div>
			 </div>
		 </div>
	 </div>
	 @endif

@if ($mediaCount == 0)
	{!! e($chatMessage) !!}
@endif



@section("javascript")
<script>
	alert();
	$(document).on("click",".tap-to-view-btn",function(){
		alert();
	});
  </script>
@endsection



