<script>
window.paceOptions = {
    ajax: false,
    restartOnRequestAfter: false,
};
</script>
<script src="{{ asset('js/core.min.js') }}?v={{$settings->version}}"></script>
<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/jqueryTimeago_'.Lang::locale().'.js') }}"></script>
<script src="{{ asset('js/lazysizes.min.js') }}" async=""></script>
<script src="{{ asset('js/plyr/plyr.min.js') }}?v={{$settings->version}}"></script>
<script src="{{ asset('js/plyr/plyr.polyfilled.min.js') }}?v={{$settings->version}}"></script>
<script src="{{ asset('js/app-functions.js') }}?v={{$settings->version}}"></script>

@if (! request()->is('live/*'))
<script src="{{ asset('js/install-app.js') }}?v={{$settings->version}}"></script>
@endif

@auth
  <script src="{{ asset('js/fileuploader/jquery.fileuploader.min.js') }}"></script>
  <script src="{{ asset('js/fileuploader/fileuploader-post.js') }}?v={{$settings->version}}"></script>

<script src="https://js.stripe.com/v3/"></script>
<script src='https://checkout.razorpay.com/v1/checkout.js'></script>
<script src='https://js.paystack.co/v1/inline.js'></script>
@if (request()->is('my/wallet'))
<script src="{{ asset('js/add-funds.js') }}?v={{$settings->version}}"></script>
@else
<script src="{{ asset('js/payment.js') }}?v={{$settings->version}}"></script>
<script src="{{ asset('js/payments-ppv.js') }}?v={{$settings->version}}"></script>
<script src="{{ asset('js/paymentForSendVideoImage.js') }}"></script>
@endif
@endauth

@if ($settings->custom_js)
  <script type="text/javascript">
  {!! $settings->custom_js !!}
  </script>
@endif

<script type="text/javascript">
const lightbox = GLightbox({
    touchNavigation: true,
    loop: false,
    closeEffect: 'fade'
});

@if (auth()->check())
$('.btnMultipleUpload').on('click', function() {
  $('.fileuploader').toggleClass('d-block');
});
@endif
</script>

@if (auth()->guest()
    && ! request()->is('password/reset')
    && ! request()->is('password/reset/*')
    && ! request()->is('contact')
    )
<script type="text/javascript">

	//<---------------- Login Register ----------->>>>

	_submitEvent = function() {
		  sendFormLoginRegister();
		};

	if (captcha == false) {

	    $(document).on('click','#btnLoginRegister',function(s) {

 		 s.preventDefault();
		 sendFormLoginRegister();

 		 });//<<<-------- * END FUNCTION CLICK * ---->>>>
	}

	function sendFormLoginRegister()
	{
		var element = $(this);
		$('#btnLoginRegister').attr({'disabled' : 'true'});
		$('#btnLoginRegister').find('i').addClass('spinner-border spinner-border-sm align-middle mr-1');

		(function(){
			  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
			 $("#formLoginRegister").ajaxForm({
			 dataType : 'json',
			 headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
			 success:  function(result) {
				console.log(result);

         if (result.actionRequired) {
           $('#modal2fa').modal({
    				    backdrop: 'static',
    				    keyboard: false,
    						show: true
    				});

            $('#loginFormModal').modal('hide');
           return false;
         }

				 // Success
				 if (result.success) {

           if (result.isModal && result.isLoginRegister) {
             window.location.reload();
           }

					 if (result.url_return && ! result.isModal) {
					 	window.location.href = result.url_return;
					 }

					 if (result.check_account) {
					 	$('#checkAccount').html(result.check_account).fadeIn(500);

						$('#btnLoginRegister').removeAttr('disabled');
						$('#btnLoginRegister').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
						$('#errorLogin').fadeOut(100);
						$("#formLoginRegister").reset();
					 }

				 }  else {

					 if (result.errors) {

						 var error = '';
						 var $key = '';

					for ($key in result.errors) {
							 error += '<li><i class="far fa-times-circle"></i> ' + result.errors[$key] + '</li>';
						 }

						 $('#showErrorsLogin').html(error);
						 $('#errorLogin').fadeIn(500);
						 $('#btnLoginRegister').removeAttr('disabled');
						 $('#btnLoginRegister').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
					 }
				 }

				},
				error: function(responseText, statusText, xhr, $form) {
					   if(responseText.status==200){
						window.location.reload();
					   }else{
						$('#btnLoginRegister').removeAttr('disabled');
						$('#btnLoginRegister').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
						swal({
								type: 'error',
								title: error_oops,
								text: error_occurred+' ('+xhr+')',
							});
					   }
					
						// error
						
						// window.location.reload();
				}
			}).submit();
		})(); //<--- FUNCTION %
	}// End function sendFormLoginRegister
</script>
@endif
