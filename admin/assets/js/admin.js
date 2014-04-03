(function ($) {
	"use strict";
	$(function () {
		$('a.submitpublish').click(function(event){
			event.preventDefault();
			$(this).css({'color':'transparent','background':'url('+woocommerce_faqs_data.spinner+') no-repeat'});
			var data = {
				action: 'approve_woo_faq',
				post_id: $(this).attr('data-id'),
				nonce: $(this).attr('data-nonce')
			};

			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			$.post(ajaxurl, data, function(response) {
				console.log(response);
				if(response.type == 'success'){
					//reload the window, but without $_GET so that the faq is no longer highlighted.
					window.location = response.redirect;
				}
				else if( response.indexOf('<') > -1) {
					//in case we get html back (like wp debug throwing errors)
					location.reload();
				}
				else{
					alert(response.message);
					return false;
				}
			}, 'json')
			.fail(function() {
				//in case we don't get a useable response
    			location.reload();
  			});
		});
		if(typeof woocommerce_faqs_data.faq_highlight !== 'undefined'){
			$('tr.post-'+woocommerce_faqs_data.faq_highlight).css('background-color',woocommerce_faqs_data.faq_highlight_color);
		}
	});
}(jQuery));