(function ($) {

    $(document).ready(function () {

        actions.saveSettings();
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
