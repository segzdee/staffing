{{--
OvertimeStaff Messaging System - Conversation Inbox
This replaces the legacy Paxpally messaging view with OvertimeStaff's Conversation-based system
--}}

@foreach ($conversations as $conversation)

	@php
	// Get the other participant in the conversation
	$otherParticipant = $conversation->getOtherParticipant(auth()->id());
	$lastMessage = $conversation->lastMessage;

	// User info
	$avatar = $otherParticipant->avatar;
	$name = $otherParticipant->name;
	$userID = $otherParticipant->id;
	$username = $otherParticipant->username;
	$verified_id = $otherParticipant->verified_id;

	// Check if message is unread
	$isUnread = $lastMessage && $lastMessage->to_user_id === auth()->id() && !$lastMessage->is_read;
	$styleStatus = $isUnread ? ' font-weight-bold unread-chat' : '';

	// Check if the last message was sent by current user
	$icon = null;
	if ($lastMessage && $lastMessage->from_user_id === auth()->id()) {
		$icon = $lastMessage->is_read
			? '<span><i class="bi bi-check2-all mr-1"></i></span>'
			: '<span><i class="bi bi-reply mr-1"></i></span>';
	}

	// Attachment icon
	$iconMedia = null;
	if ($lastMessage && $lastMessage->hasAttachment()) {
		switch ($lastMessage->attachment_type) {
			case 'jpg':
			case 'jpeg':
			case 'png':
				$iconMedia = '<i class="feather icon-image"></i> ';
				break;
			case 'pdf':
			case 'doc':
			case 'docx':
				$iconMedia = '<i class="far fa-file-alt"></i> ';
				break;
			default:
				$iconMedia = '<i class="feather icon-paperclip"></i> ';
		}
	}

	// Unread count for this conversation
	$unreadCount = $conversation->unreadMessagesFor(auth()->id())->count();

	@endphp

	<div class="card msg-inbox border-bottom m-0 rounded-0">
		<div class="list-group list-group-sm list-group-flush rounded-0">

			<a href="{{ route('messages.show', $conversation->id) }}" class="item-chat list-group-item list-group-item-action text-decoration-none p-4{{ $styleStatus }}">
				<div class="media">
				 <div class="media-left mr-3 position-relative">
						 <img class="media-object rounded-circle" src="{{ Helper::getFile(config('path.avatar').$avatar) }}"  width="50" height="50">
				 </div>

				 <div class="media-body overflow-hidden">
					 <div class="d-flex justify-content-between align-items-center">
						<h6 class="media-heading mb-2 text-truncate">
								 {{ $name }}

								 @if ($verified_id == 'yes')
					         <small class="verified">
					   						<i class="bi bi-patch-check-fill"></i>
					   					</small>
					       @endif
						 </h6>

						 @if($lastMessage)
						 	<small class="timeAgo text-truncate mb-2" data="{{ date('c', strtotime($lastMessage->created_at)) }}"></small>
						 @endif
					 </div>

					 <p class="text-truncate m-0">
						 @if ($unreadCount > 0)
						 	<span class="badge badge-pill badge-primary mr-1">{{ $unreadCount }}</span>
					 	@endif

						 {!! $icon ?? '' !!} {!! $iconMedia ?? '' !!}

						 @if($lastMessage)
						 	{{ Str::limit($lastMessage->message, 50) }}
						 @else
						 	<em class="text-muted">{{ trans('general.no_messages') }}</em>
						 @endif
					 </p>
				 </div><!-- media-body -->
		 </div><!-- media -->
			 </a>
		</div><!-- list-group -->
	</div><!-- card -->
@endforeach

@if ($conversations->count() == 0)
	<div class="card border-0 text-center">
  <div class="card-body">
    <h4 class="mb-0 font-montserrat mt-2">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-send-exclamation" viewBox="0 0 16 16">
				<path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855a.75.75 0 0 0-.124 1.329l4.995 3.178 1.531 2.406a.5.5 0 0 0 .844-.536L6.637 10.07l7.494-7.494-1.895 4.738a.5.5 0 1 0 .928.372l2.8-7Zm-2.54 1.183L5.93 9.363 1.591 6.602l11.833-4.733Z"/>
				<path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1.5a.5.5 0 0 1-1 0V11a.5.5 0 0 1 1 0Zm0 3a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0Z"/>
			</svg> {{ trans('general.chats') }}
		</h4>
		<p class="lead text-muted mt-0">{{ trans('general.no_chats') }}</p>
  </div>
</div>
@endif

@if ($conversations->hasMorePages())
  <div class="btn-block text-center d-none">
    {{ $conversations->appends(['filter' => request('filter')])->links('vendor.pagination.loadmore') }}
  </div>
@endif
