(function ($) {

    $(document).ready(function () {

        actions.saveSettings();
        actions.saveClaimRoot();
        actions.saveContentOnChange();
        actions.saveContentOnInput();
        actions.getSitemaps();
    });

    let actions = {
        saveSettings: function () {
            $(document).on('change', '.settings .xagio-slider-container input', function(e){
                e.preventDefault();

                let form = $('.settings');

                let elements = $('[data-element="XAGIO_POST_TYPE_POST_ENABLED"], [data-element="XAGIO_POST_TYPE_PAGE_ENABLED"], [data-element="XAGIO_TAXONOMY_CATEGORY_ENABLED"], [data-element="XAGIO_TAXONOMY_POST_TAG_ENABLED"]');
                let inputs = $('#XAGIO_POST_TYPE_POST_ENABLED, #XAGIO_POST_TYPE_PAGE_ENABLED, #XAGIO_TAXONOMY_CATEGORY_ENABLED, #XAGIO_TAXONOMY_POST_TAG_ENABLED');

                if ($('#XAGIO_ENABLE_SITEMAPS').val() === '1') {
                    elements.addClass('on');
                    inputs.val('1');
                }

                // send post request
                $.post(xagio_data.wp_post, form.serialize(), function (response) {

                    xagioNotify(response.status, response.message);

                    let targetId = $(e.target).attr('id');

                    if (targetId === "XAGIO_ENABLE_SITEMAPS") {
                        actions.getSitemaps();

                        // The Crawler Compatibility panel is always visible; only the
                        // "Make Xagio primary" toggle inside it is gated on sitemaps being
                        // enabled (server-side shouldClaimRootSitemap() needs a live sitemap
                        // to redirect to). Enable/disable it to match — no page refresh.
                        let enabled = $('#XAGIO_ENABLE_SITEMAPS').val() === '1';
                        let claimToggle = $('.claim-root-toggle');

                        if (enabled) {
                            claimToggle.css({'opacity': '', 'pointer-events': ''});
                            $('#xagio-root-hint-disabled').hide();

                            // Show the "another plugin controls /sitemap.xml" alert when
                            // Xagio isn't claiming it (the element only exists when a
                            // foreign owner was detected). Swap in the matching hint.
                            let claiming = $('#XAGIO_SITEMAP_CLAIM_ROOT').val() === '1';
                            $('#xagio-root-foreign-alert').toggle(!claiming);
                            $('#xagio-root-hint-on').toggle(claiming);
                            $('#xagio-root-hint-off').toggle(!claiming);
                        } else {
                            // Disabling sitemaps also drops the /sitemap.xml claim (reset
                            // server-side), so turn the toggle off + grey it out in the UI.
                            $('#XAGIO_SITEMAP_CLAIM_ROOT').val(0);
                            $('.xagio-slider-button[data-element="XAGIO_SITEMAP_CLAIM_ROOT"]').removeClass('on');
                            claimToggle.css({'opacity': '0.5', 'pointer-events': 'none'});
                            $('#xagio-root-foreign-alert').hide();
                            $('#xagio-root-hint-on').hide();
                            $('#xagio-root-hint-off').hide();
                            $('#xagio-root-hint-disabled').show();
                        }
                    }
                });

            });
        },
        saveClaimRoot: function () {
            // Standalone toggle (outside the .settings form) so it doesn't trigger the
            // generic settings save. The _xagio_nonce is appended globally by global.js.
            $(document).on('change', '#XAGIO_SITEMAP_CLAIM_ROOT', function () {
                let claiming = $(this).val() === '1';

                $.post(xagio_data.wp_post, {
                    action: 'xagio_sitemap_claim_root',
                    XAGIO_SITEMAP_CLAIM_ROOT: $(this).val()
                }, function (response) {
                    xagioNotify(response.status, response.message);

                    // Only update the UI once the change is actually persisted in the DB.
                    if (response.status === 'success') {
                        // Hide the "another plugin controls /sitemap.xml" warning when Xagio
                        // takes over; bring it back when the claim is turned off again.
                        $('#xagio-root-foreign-alert').toggle(!claiming);

                        // Swap the helper text to match the new state.
                        $('#xagio-root-hint-on').toggle(claiming);
                        $('#xagio-root-hint-off').toggle(!claiming);
                    }
                });
            });
        },

        saveContentOnChange: function () {
            $(document).on('change', '.content .xagio-slider-container input, .content .xagio-flex-row input, .content .content-settings select', function(e){
                e.preventDefault();

                let form = $(".content");

                // send post request
                $.post(xagio_data.wp_post, form.serialize(), function (response) {

                    xagioNotify(response.status, response.message);

                    if ($(e.target).parents('.xagio-slider-container').length > 0) {
                        actions.getSitemaps();
                    }
                });

            })
        },

        debounce: function (func, delay) {
            let timeout;
            return function (...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            }
        },

        // Function to save data
        saveData: function () {
            let form = $(".content");

            // send post request
            $.post(xagio_data.wp_post, form.serialize(), function (response) {

                xagioNotify(response.status, response.message);

            });
        },

        saveContentOnInput: function () {
            $(document).on('keyup', '.content input[type="text"]', actions.debounce(actions.saveData, 500));
        },

        getSitemaps: function () {
            $.get(xagio_data.wp_post, `action=xagio_get_sitemaps&return`, function (response) {

                let content = '';
                let sitemaps = response.data;

                if(sitemaps.length < 1) {
                    content = "Please enable sitemaps in order to view them.";
                } else {
                    $.each(sitemaps, function(key, value) {
                        content += `<a target="_blank" href="/${key}">${xagio_data.site_url}/${key}</a>`
                    });
                }

                $('.sitemap-location-holder').html(`
                     ${content}
                 `);
            });
        }

    };

})(jQuery);
