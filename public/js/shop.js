// const { error } = require("jquery");

(function($) {
	"use strict";
	
	$('input[name=payment_gateway_buy]').on('click', function() {
		if($(this).val() == 2) {
		  $('#stripeContainerBuy').slideDown();
		} else {
		  $('#stripeContainerBuy').slideUp();
		}
	});

	if (stripeKey != '' && ! liveMode && $('#card-element-2').length > 0) {

		var stripe = Stripe(stripeKey);
		
		// Create an instance of Elements.
		var elements = stripe.elements();
		
		// Custom styling can be passed to options when creating an Element.
		// (Note that this demo uses a wider set of styles than the guide below.)
		var style = {
			base: {
				color: colorStripe,
				fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
				fontSmoothing: 'antialiased',
				fontSize: '16px',
				'::placeholder': {
					color: '#aab7c4'
				}
			},
			invalid: {
				color: '#fa755a',
				iconColor: '#fa755a'
			}
		};
		
		// Create an instance of the card Element.
		var cardElement = elements.create('card', {style: style, hidePostalCode: true});
		
		// Add an instance of the card Element into the `card-element` <div>.
		cardElement.mount('#card-element-2');
		
		// Handle real-time validation errors from the card Element.
		cardElement.addEventListener('change', function(event) {
			var displayError = document.getElementById('card-errors');
			var payment = $('input[name=payment_gateway_tip]:checked').val();
		
			if (payment == 2) {
				if (event.error) {
					displayError.classList.remove('display-none');
					displayError.textContent = event.error.message;
					$('#tipBtn').removeAttr('disabled');
					$('#tipBtn').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
				} else {
					displayError.classList.add('display-none');
					displayError.textContent = '';
				}
			}
		
		});
		
		var cardholderName = document.getElementById('cardholder-name');
		var cardholderEmail = document.getElementById('cardholder-email');

	}

	var cardButtonShop = document.getElementById('shopProductBtn');
	
	if(cardButtonShop){
		cardButtonShop.addEventListener('click', function(ev) {
			var payment = $('input[name=payment_gateway_buy]:checked').val();
	
			if (payment == 2) {
	
			stripe.createPaymentMethod('card', cardElement, {
				billing_details: {name: cardholderName.value, email: cardholderEmail.value}
			}).then(function(result) {
				if (result.error) {
	
					if (result.error.type == 'invalid_request_error') {
	
						if(result.error.code == 'parameter_invalid_empty') {
							$('.popout').addClass('popout-error').html(error).fadeIn('500').delay('8000').fadeOut('500');
						} else {
							$('.popout').addClass('popout-error').html(result.error.message).fadeIn('500').delay('8000').fadeOut('500');
						}
					}
					$('#shopProductBtn').removeAttr('disabled');
					$('#shopProductBtn').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
	
				} else {
	
					$('#shopProductBtn').attr({'disabled' : 'true'});
					$('#shopProductBtn').find('i').addClass('spinner-border spinner-border-sm align-middle mr-1');
	
					// Otherwise send paymentMethod.id to your server
					$('input[name=payment_method_id]').remove();
	
					var $input = $('<input id=payment_method_id type=hidden name=payment_method_id />').val(result.paymentMethod.id);
					$('#shopProductForm').append($input);
	
					$.ajax({
					headers: {
							'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
						},
						type: "POST",
						dataType: 'json',
						url: URL_BASE+"/buy/now/product",
						data: $('#shopProductForm').serialize(),
						success: function(result) {
							handleShopServerResponse(result);
	
							if(result.success == false) {
								$('#shopProductBtn').removeAttr('disabled');
								$('#shopProductBtn').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
							}
						},//<-- RESULT
						error: function(responseText, statusText, xhr, $form){
							console.log(responseText);
						}
					})
	
				}//ELSE
			});
		}//PAYMENT STRIPE
		else if(payment == 'wallet'){
			purchaseThroughWallet();
		}
		});
	}

	function handleShopServerResponse(response) {
		if (response.errors) {
			var $key = '';
			var error = '';

			for ($key in response.errors) {
				error += '<li><i class="fa fa-times-circle"></i> ' + response.errors[$key] + '</li>';
			}

			$('#showErrorsShopProduct').html(error);
			$('#errorShopProduct').fadeIn(500);

			$('#shopProductBtn').removeAttr('disabled');
			$('#shopProductBtn').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
   
		} else if (response.requires_action) {
			// Use Stripe.js to handle required card action
			// stripe.handleCardAction(
			// 	response.payment_intent_client_secret
			// )
			stripe.confirmCardPayment(response.payment_intent_client_secret, {
				payment_method: response.payment_method
			})
			.then(function(result) {
				if (result.error) {
					var error = '<li><i class="fa fa-times-circle"></i> ' + error_payment_stripe_3d + '</li>';
	
					$('#showErrorsShopProduct').html(error);
					$('#errorShopProduct').fadeIn(500);
					$('#shopProductBtn').removeAttr('disabled');
					$('#shopProductBtn').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
   
				} else {
					// The card action has been handled
					// The PaymentIntent can be confirmed again on the server
   
					var $input = $('<input type=hidden name=payment_intent_id />').val(result.paymentIntent.id);
					$('#shopProductForm').append($input);
   
					$('input[name=payment_method_id]').remove();
   
					$.ajax({
					headers: {
							'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
						},
					   type: "POST",
					   dataType: 'json',
					   url: URL_BASE+"/buy/now/product",
					   data: $('#shopProductForm').serialize(),
					   success: function(result){
   
						   if(result.success) {
								swal({
									title: thanks,
									text: purchase_processed_shortly,
									type: "success",
									confirmButtonText: ok
								});
								 $('#buyNowForm').modal('hide');
								 $('.InputElement').val('');
								 $('#shopProductBtn').removeAttr('disabled');
								$('#shopProductForm').trigger("reset");
								 $('#shopProductBtn').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
								 cardElement.clear();
								$('#errorTip').hide();
								if (result.wallet) {
									$('.balanceWallet').html(result.wallet);
								}
   
						   } else {
								if(result.errors){
									var $key = '';
									var error = '';
									for ($key in result.errors) {
										error += '<li><i class="fa fa-times-circle"></i> ' + result.errors[$key] + '</li>';
									}
					
									$('#showErrorsShopProduct').html(error);
									$('#errorShopProduct').fadeIn(500);
								}
								$('#shopProductBtn').removeAttr('disabled');
								$('#shopProductBtn').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
						   }
				   }//<-- RESULT
				   })
				}// ELSE
			});
		} else {
			// Show success message
			if (response.success) {
					swal({
						title: thanks,
						text: purchase_processed_shortly,
						type: "success",
						confirmButtonText: ok
					});
					$('#buyNowForm').modal('hide');
					$('.InputElement').val('');
					$('#shopProductForm').trigger("reset");
					$('#shopProductBtn').removeAttr('disabled');
					$('#shopProductBtn').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
					cardElement.clear();
					$('.balanceWallet').html(response.wallet);
			}
		}
	}
   
   // Stripe Elements

	//<---------------- Shop Product ----------->>>>
	function purchaseThroughWallet() {

		var element = $('#shopProductBtn');

		element.attr({'disabled' : 'true'});
		element.find('i').addClass('spinner-border spinner-border-sm align-middle mr-1');

		(function() {

			 $("#shopProductForm").ajaxForm({
			 dataType : 'json',
			 error: function(responseText, statusText, xhr, $form) {
				element.removeAttr('disabled');

				if (! xhr) {
					xhr = '- ' + error_occurred;
				} else {
					xhr = '- ' + xhr;
				}

				$('.popout').removeClass('popout-success').addClass('popout-error').html(error_oops+' '+xhr+'').fadeIn('500').delay('5000').fadeOut('500');
					 element.find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
			 },
			 success: function(result) {

			 //===== SUCCESS =====//
			 if (result.success && result.url) {
				 window.location.href = result.url;

			 } else if (result.success && result.buyCustomContent) {

				 $('#buyNowForm').modal('hide');

				 swal({
			     title: thanks,
			     text: purchase_processed_shortly,
			     type: "success",
			     confirmButtonText: ok
			     });

					element.removeAttr('disabled');
	 				element.find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');

					$('#errorShopProduct').hide();
					$('.balanceWallet').html(result.wallet);
					$('#descriptionCustomContent').val('');

			 } else {
				var error = '';
				var $key = '';

				if(result.errors){
					for ($key in result.errors) {
						error += '<li><i class="fa fa-times-circle"></i> ' + result.errors[$key] + '</li>';
					}
	
					$('#showErrorsShopProduct').html(error);
					$('#errorShopProduct').fadeIn(500);
				}

				element.removeAttr('disabled');
				element.find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
				}
			}//<----- SUCCESS
			}).submit();
		})(); //<--- FUNCTION %
	};//<<<-------- * END FUNCTION CLICK * ---->>>>

	$(document).on('click','.actionDeleteItem', function(e) {

	   e.preventDefault();

	   var element = $(this);
	   var form    = $(element).parents('form');
	   element.blur();

	 swal(
	   {   title: delete_confirm,
	     type: "error",
	     showLoaderOnConfirm: true,
	     showCancelButton: true,
	     confirmButtonColor: "#DD6B55",
	      confirmButtonText: yes_confirm,
	      cancelButtonText: cancel_confirm,
	       closeOnConfirm: false,
	       },
	       function(isConfirm){

					 if (isConfirm) {
						 (function() {
				        form.ajaxForm({
				        dataType : 'json',
				        success:  function(response) {
				          if (response.success) {
										window.location.href = response.url;
				          } else {
										swal({
												type: 'info',
												title: error_oops,
												text: error_occurred,
											});
				          }
				        },
				        error: function(responseText, statusText, xhr, $form) {
				             // error
				             swal({
				                 type: 'error',
				                 title: error_oops,
				                 text: ''+error_occurred+' ('+xhr+')',
				               });
				         }
				       }).submit();
				     })(); //<--- FUNCTION %
					 } // isConfirm
	        });
	    });// End Delete

		var stock = $("#quantity-val").data('stock');
		$(document).on('click', "#inc-quantity", function(e){
			var quantity = parseInt($("#quantity-val").val());
			if(quantity < stock){
				$("#quantity-val").val(quantity + 1);
				$("input[name=quantity]").val($("#quantity-val").val());
				$("#dec-quantity").attr('disabled', false);
			}
			else{
				$("#inc-quantity").attr('disabled', true);
			}
		});

		$(document).on('click', "#dec-quantity", function(e){
			var quantity = parseInt($("#quantity-val").val());
			if(quantity > 1){
				$("#quantity-val").val(quantity - 1);
				$("input[name=quantity]").val($("#quantity-val").val());
				$("#inc-quantity").attr('disabled', false);
			}
			else{
				$("#dec-quantity").attr('disabled', true);
			}
		});




		$(document).on('click', '#addProductBtn', function() {
			console.log('sdfsdfsdf');
			var element = $(this);
			element.attr({'disabled' : 'true'});
			element.find('i').addClass('spinner-border spinner-border-sm align-middle mr-1');

			(function() {

				$("#addProductForm").ajaxForm({
				dataType : 'json',
				error: function(responseText, statusText, xhr, $form) {
					element.removeAttr('disabled');

					if (! xhr) {
						xhr = '- ' + error_occurred;
					} else {
						xhr = '- ' + xhr;
					}

					$('.popout').removeClass('popout-success').addClass('popout-error').html(error_oops+' '+xhr+'').fadeIn('500').delay('5000').fadeOut('500');
						element.find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
				},
				success: function(result) {

				//===== SUCCESS =====//
				if (result.success && result.url) {
					window.location.href = result.url;

				} else {
					var error = '';
					var $key = '';

					if(result.errors){
						for ($key in result.errors) {
							error += '<li><i class="fa fa-times-circle"></i> ' + result.errors[$key] + '</li>';
						}
		
						$('#showErrorsShopProduct').html(error);
						$('#errorShopProduct').fadeIn(500);
					}

					element.removeAttr('disabled');
					element.find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
					}
				}//<----- SUCCESS
				}).submit();
			})();
		});


})(jQuery);
