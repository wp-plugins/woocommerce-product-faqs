(function ($) {
	"use strict";
	$(function () {
		$('a.submitpublish').click(function(event){
			event.preventDefault();
			$(this).css({'color':'transparent','background':'url('+spinner+') no-repeat'});
			var data = {
				action: 'approve_woo_faq',
				post_id: $(this).attr('data-id'),
				nonce: $(this).attr('data-nonce')
			};

			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			$.post(ajaxurl, data, function(response) {
				if(response.type != 'success'){
					alert(response.message);
					return false;
				}else{
					window.location = response.redirect;
				}
			}, 'json');
		});
		if(typeof faq_highlight !== 'undefined'){
			$('tr.post-'+faq_highlight).css('background-color',faq_highlight_color);
		}
	});
}(jQuery));