(function ($) {

    $(document).ready(function () {

        $(document).on('click', '.xagio-connect-have-account', function(e){
            e.preventDefault();
            let panel_url = $('.xagio-panel-url');
            let account_email = $('.xagio-account-email');

            account_email.removeAttr('required');
            panel_url.val(panel_url.val().replace('signup', 'signin'));

            $('.activate-account').trigger('submit');

            account_email.attr('required', '');
            panel_url.val(panel_url.val().replace('signin', 'signup'));
        });

        $(document).on('submit', '.activate-account', function(e){
            e.preventDefault();
            $.post(xagio_data.wp_post, $(this).serialize(), function (d) {
                if (d.status === 'error') {
                    xagio_notify(d.status, d.message);
                } else {
                    let url = d.data.url + '?redirect=' + d.data.redirect;
                    if (d.data.email != '') {
                        url += '&email=' + d.data.email;
                    }
                    document.location.href = url;
                }
            });

        });

        $(document).on('click', '.disconnect-account', function (e) {
            e.preventDefault();

            let btn = $(this);

            xagioModal("Warning!", "You are about to disconnect your Xagio account from this website. <br> No SEO settings will be lost, and you can re-activate at anytime", function (result) {
                if (result) {
                    btn.disable();
                    $.post(xagio_data.wp_post, 'action=xagio_disconnect_account', function (d) {
                        btn.disable();
                        xagio_notify(d.status, d.message);
                        if (d.status === 'success') {
                            setTimeout(function () {
                                document.location.reload();
                            }, 2000);
                        }
                    });
                }
            });

        });

        $(document).on('click', '.xagio-button-welcome-play', function () {
            $("#welcome-video")[0].showModal();
        });

        const dialog = document.getElementById('welcome-video');

        dialog.addEventListener('click', (event) => {
            console.log(event.target.id);
            if (event.target.id !== 'welcome-vimeo-holder') {
                dialog.close();
            }
        });

        dialog.addEventListener("close", (event) => {
            let modal = $(event.target);
            let video = modal.find('.welcome-vimeo-holder iframe');
            let player = new Vimeo.Player(video);
            player.pause();
        });

        $.post(xagio_data.wp_post, 'action=xagio_get_links_dashboard', function (d) {
            if(d !== false) {
                let dashboard_btn = $('.xagio-button-dashboard-link');
                dashboard_btn.text(d.dashboard.text);
                dashboard_btn.attr('data-text',d.dashboard.text);
                dashboard_btn.attr('href', d.dashboard.url);
            }
        });

        /**
         *  Show changelog
         */
        $(document).on('click', '.view-changelog', function (e) {
            e.preventDefault();
            $("#changelog")[0].showModal();
        });

        /**
         *  Validate License
         */
        $('.validate-license').submit(function (e) {
            e.preventDefault();
            var button = $(this).find('button');
            button.disable('Loading ...');
            $.post(xagio_data.wp_post, $(this).serialize(), function (d) {
                setTimeout(function () {
                    //document.location.reload();
                }, 5000);
                if (d.status === 'success') {
                    xagioNotify("success", d.message);
                } else {
                    xagioNotify("danger", d.message);
                }
            });
        });

        /**
         *  Privacy Policy page
         */
        $(document).on('change', '.default-inputs input[type="checkbox"]', function (e) {
            e.preventDefault();
            $(`.${$(this).attr('name')}`).toggleClass('xagio-hidden');
        });

        /**
         *  Create Categories
         */
        $(document).on('change', '#create_categories', function (e) {
            e.preventDefault();
            $('#create_categories_container').toggleClass('xagio-hidden');
        });
        /**
         *  Create Pages
         */
        $(document).on('change', '#create_pages', function (e) {
            e.preventDefault();
            $('#create_pages_container').toggleClass('xagio-hidden');
        });
        /**
         *  Create Posts
         */
        $(document).on('change', '#create_posts', function (e) {
            e.preventDefault();
            $('#create_posts_container').toggleClass('xagio-hidden');
        });

        /** Add more pages/posts/categories */
        $(document).on('click', '.creator .actions .add', function (e) {
            e.preventDefault();
            let i = $(this).parents('.creator').find('input').last().clone();
            i.val('');
            i.insertBefore($(this).parents('.actions'));
        });

        /** Remove pages/posts/categories */
        $(document).on('click', '.creator .actions .remove', function (e) {
            e.preventDefault();
            let i = $(this).parents('.creator').find('input');
            if (i.length > 1) {
                i.last().remove();
            }
        });

        /**
         *  Select result and put it into a tag
         */
        $(document).on('click', '.select-result', function (e) {
            e.stopPropagation();
            let name = $(this).data('name');
            let type = $(this).data('type');
            let image = $(this).parents('.search-result-grid').find('img')[0].outerHTML;
            $(this).html('Added').attr('disabled', 'disabled');
            let container = $(this).parents('.install');
            container.append('<div class="added-install">' + image + '<span class="name">' + name + '</span><a href="#" class="remove-install">Remove</a></div>')
        });

        $(document).on('click', 'body', function () {
            if ($('#search_plugins_dropdown').is(':visible')) {
                $('#search_plugins_dropdown').addClass('xagio-hidden');
            }
            if ($('#search_themes_dropdown').is(':visible')) {
                $('#search_themes_dropdown').addClass('xagio-hidden');
            }
        });

        /**
         *  Key press events for Plugins/Themes search
         */
        var ajax_timeout;
        $('#search_plugins, #search_themes').on('keypress', function (e) {
            e.stopPropagation();
            var element = this;
            var type = $(element).data('type');
            var dropdown = $('#search_' + type + '_dropdown');
            clearTimeout(ajax_timeout);
            ajax_timeout = setTimeout(function () {
                $(element).attr('disabled', 'disabled');
                dropdown.empty();
                dropdown.removeClass('xagio-hidden');
                dropdown.append(
                    '<div class="search-loading"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Searching WordPress repository...</div>'
                );
                var data = [
                    {
                        name : 'action',
                        value: 'xagio_search_wp_api'
                    },
                    {
                        name : 'type',
                        value: type
                    },
                    {
                        name : 'search',
                        value: $(element).val()
                    }
                ];
                $.post(xagio_data.wp_post, data, function (d) {

                    dropdown.empty();

                    $(element).removeAttr('disabled');
                    var data = d[type];
                    if (data.length < 1) {
                        dropdown.append(
                            '<div class="search-no-results"><i class="xagio-icon xagio-icon-warning"></i> No results for search query <b>"' + $(element).val() + '"</b>.</div>'
                        );
                        return 0;
                    }

                    for (var i = 0; i < data.length; i++) {
                        var row = data[i];
                        dropdown.append(
                            '<div class="uk-grid uk-grid-small uk-margin-medium-bottom search-result-grid">' +
                            '<div class="uk-width-1-3">' +
                            '<img src="' + (row.hasOwnProperty('icons') ? row.icons[Object.keys(row.icons)[0]] : row.screenshot_url) + '" class="icon" alt="">' +
                            '</div>' +
                            '<div class="uk-width-2-3">' +
                            '<div class="search-result">' +
                            '<p class="search-result-title">' + row.name + ' <small>by <b>' + (row.author.hasOwnProperty('author') ? row.author.author : row.author) + '</b></small></p>' +
                            '<div class="search-result-actions">' +
                            '<button type="button" class="uk-button uk-button-success uk-button-micro select-result" data-type="' + type + '" data-name="' + row.name + '" data-slug="' + row.slug + '"><i class="xagio-icon xagio-icon-plus"></i> Add</button>' +
                            '' +
                            '' +
                            '</div>' +
                            '' +
                            '' +
                            '</div>' +
                            '</div>' +
                            '</div>'
                        );
                    }

                });
            }, 600);
        });

    });


})(jQuery);
