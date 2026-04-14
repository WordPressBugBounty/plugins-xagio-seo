(function ($) {
    'use strict';

    $(document).ajaxSend(function (event, jqxhr, settings) {
        // Function to check if action contains 'xag'
        function actionContainsXag(data) {
            if (typeof data === 'string') {
                // Check if the action parameter contains 'xag'
                return data.includes('action=xag');
            } else if (data instanceof FormData) {
                // Check if the FormData has 'action' containing 'xag'
                for (var pair of data.entries()) {
                    if (pair[0] === 'action' && pair[1].includes('xag')) {
                        return true;
                    }
                }
            }
            return false;
        }

        // If the action contains 'xag', append the nonce
        if (actionContainsXag(settings.data)) {
            if (settings.data instanceof FormData) {
                // Append the nonce directly to the FormData object
                settings.data.append('_xagio_nonce', xagio_data.nonce);
            } else {
                // If it's a URL-encoded string, append the nonce
                settings.data += '&_xagio_nonce=' + xagio_data.nonce;
            }
        }
    });

    let actions = {
        trackAffiliateClicks: function () {
            $(document).on('click', '.xagio-tracking', function (e) {
                let id = $(this).data('id');
                let ms = $(this).hasClass('masked') ? '&masked=yes' : '';
                $.post(xagio_data.wp_post, 'action=xagio_trackShortcode&id=' + id + ms);
            });
        }
    };

    /**
     *  Global doc.ready function
     */
    $(document).ready(function () {
        actions.trackAffiliateClicks();
    });

})(jQuery);