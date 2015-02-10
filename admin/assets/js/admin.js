(function ($, woocommerce_faqs_data) {
    "use strict";
    $(function () {
        $('a.submitpublish').on('click', function (event) {
            console.log(event);
            event.preventDefault();
            $(this).css({'color': 'transparent', 'background': 'url(' + woocommerce_faqs_data.spinner + ') no-repeat'});
            var data = {
                action: 'approve_woo_faq',
                post_id: $(this).attr('data-id'),
                nonce: $(this).attr('data-nonce')
            };

            $.post(ajaxurl, data, function (response) {
                if (response.type == 'success') {
                    //reload the window, but without $_GET so that the faq is no longer highlighted.
                    window.location = response.redirect;
                }
                else if (response.indexOf('<') > -1) {
                    //in case we get html back (like wp debug throwing errors)
                    location.reload();
                }
                else {
                    alert(response.message);
                    return false;
                }
            }, 'json')
                .fail(function () {
                    //in case we don't get a useable response
                    location.reload();
                });
        });
        var highlight = function () {
            if (typeof woocommerce_faqs_data.highlight !== 'undefined') {
                $('#post-' + woocommerce_faqs_data.highlight).css('background-color', woocommerce_faqs_data.highlight_color);
            }
        }
        var init = function () {
            highlight();
        }
        $(window).load(init);
    });
})(jQuery, woocommerce_faqs_data);