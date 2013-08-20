(function ($) {
	
	"use strict";

	$(function () {

		if(window.location.hash === '#tab-faqs'){

			$('a[href="'+window.location.hash+'"]').trigger('click');

		}

		$('.faq-question').click(function(){

			$(this).siblings('.faq-content').toggle();

		});

		if(typeof faq_highlight !== 'undefined'){

			$('.faq-'+faq_highlight+' .faq-content').toggle();

		}

		$('#quick-approve-faq').submit(function(event){

			$(this).children('input[type="submit"]').replaceWith('<img src="'+spinner+'" />');

			event.preventDefault();

			var data = {

				action: 'approve_woo_faq',

				post_id: $('#qaf_post_id').val(),

				nonce: $('#qaf_nonce').val()

			};

			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			$.post(ajaxurl, data, function(response) {

				if(response.type != 'success'){

					alert(response.message);

					return false;

				}

				else{

					location.reload(true);

				}

			}, 'json');

		});

	});

}(jQuery));