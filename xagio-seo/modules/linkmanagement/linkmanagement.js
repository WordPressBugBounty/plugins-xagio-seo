(function ($) {
    'use strict';

    $(document).ready(function () {
        // actions.pagination();
        actions.loadShortcodes();
        actions.createShortcode();
        actions.saveShortcode();
        actions.selectImages();
        actions.shortcodePreview();
        actions.shortcodeTracking();
        actions.shortcodeUrlTracking();
        actions.shortcodeDuplicate();
        actions.shortcodeEdit();
        actions.shortcodeDelete();
        actions.shortcodeTruncateTracking();
        actions.shortcodeUrlTruncateTracking();
        actions.showHideFilters();
        actions.applyFilters();
        actions.exportLinks();
        actions.copyShortcode();
        actions.saveShortcodeSetup();
        actions.maskedModal();
        actions.pagination();
    });

    var actions = {
        maskedModal        : function () {
            $(document).on('click', '.copy-masked-tag', function () {

                var modal = $('#maskedModal');
                $('#maskedURL').val($(this).data('content'));

            });
            $(document).on('click', '.copy-masked-url', function () {

                var modal = $('#maskedModal');
                modal[0].close();
                actions.copyTextToClipboard($('#maskedURL').val());

                xagioNotify("success", "Successfully copied masked url to clipboard!");

            });
        },
        exportLinks : function () {
            $(document).on('click', '.export-links', function () {
                window.location = xagio_data.wp_post + '?action=xagio_exportLinks&_xagio_nonce=' + xagio_data.nonce;
            });

            $('#importLinks').xagio_uploader('xagio_importLinks', actions.loadShortcodes);
        },
        saveShortcodeSetup : function () {
            $(document).on('change keyup paste', '#redirect_mask', function () {
                $('.redirect_mask_preview').html($(this).val());
            });

            $(document).on('submit', '#shortcode_setup', function (e) {
                e.preventDefault();
                var btn = $(this).find('button[type="submit"]');
                btn.disable();

                $.post(xagio_data.wp_post, $(this).serialize()).done(function (d) {
                    btn.disable();
                    xagioNotify(d.status, d.message);
                });
            });
        },
        copyTextToClipboard: function (text) {
            var textArea = document.createElement("textarea");

            // Place in top-left corner of screen regardless of scroll position.
            textArea.style.position = 'fixed';
            textArea.style.top = 0;
            textArea.style.left = 0;

            // Ensure it has a small width and height. Setting to 1px / 1em
            // doesn't work as this gives a negative w/h on some browsers.
            textArea.style.width = '2em';
            textArea.style.height = '2em';

            // We don't need padding, reducing the size if it does flash render.
            textArea.style.padding = 0;

            // Clean up any borders.
            textArea.style.border = 'none';
            textArea.style.outline = 'none';
            textArea.style.boxShadow = 'none';

            // Avoid flash of white box if rendered for any reason.
            textArea.style.background = 'transparent';


            textArea.value = text;

            document.body.appendChild(textArea);

            textArea.select();

            try {
                var successful = document.execCommand('copy');
                var msg = successful ? 'successful' : 'unsuccessful';
                console.log('Copying text command was ' + msg);
            } catch (err) {
                console.log('Oops, unable to copy');
            }

            document.body.removeChild(textArea);
        },

        copyShortcode  : function () {
            $(document).on('click', '.copy-shortcode-tag', function () {

                var modal = $('#shortModal');
                $('#shortURL').val($(this).data('content'));

            });
            $(document).on('click', '.copy-short-url', function () {

                var modal = $('#shortModal');
                modal[0].close();
                actions.copyTextToClipboard($('#shortURL').val());

                xagioNotify("success", "Successfully copied short code to clipboard!");

            });
        },
        applyFilters   : function () {
            $('.filters').submit(function (e) {
                e.preventDefault();
                $('#page').val(0);
                actions.loadShortcodes();
            });
        },
        showHideFilters: function () {
            $(document).on('click', '.show-filters', function () {
                var filters = $('.shortcode-filters');
                filters.toggleClass('hidden');
            });
        },

        shortcodeTruncateTracking   : function () {
            $(document).on('click', '.uk-button-truncate-tracking', function (e) {
                var id = $('#trackingModal').find('.ID').val();
                $.post(xagio_data.wp_post, 'action=xagio_truncateTrackingData&id=' + id, function () {
                    actions.loadShortcodes();
                });
            });
        },
        shortcodeUrlTruncateTracking: function () {
            $(document).on('click', '.uk-button-url-truncate-tracking', function (e) {
                var id = $('#urlTrackingModal').find('.ID').val();
                $.post(xagio_data.wp_post, 'action=xagio_urlTruncateTrackingData&id=' + id, function () {
                    actions.loadShortcodes();
                });
            });
        },
        shortcodeTracking           : function () {
            $(document).on('click', '.shortcode-tracking', function () {
                var shortcode = $(this).parents('.shortcode');
                var id = shortcode.data('id');
                var modal = $('#trackingModal');
                var name = shortcode.find('.name').text().split(']')[0].replace('[', '');
                var btn = $(this);
                modal.find('.shortcode').html(name);
                modal.find('.ID').val(id);

                btn.disable();
                $.post(xagio_data.wp_post, 'action=xagio_getTrackingCharts&id=' + id)
                 .done(function (d) {
                     btn.disable();
                     if (d.status == 'success') {

                         $('#tracking_charts').empty();

                         if (d.data.length > 1) {
                             var formatted_data = [];
                             var raw_data = d.data;
                             for (var i = 0; i < raw_data.length; i++) {
                                 var obj = raw_data[i];
                                 if (i !== 0) {
                                     obj[0] = new Date(obj[0]);
                                 }
                                 formatted_data.push(obj);
                             }

                             var data = google.visualization.arrayToDataTable(formatted_data);

                             var options = {
                                 title: 'Shortcode Tracking Details',
                                 hAxis: {
                                     title         : 'Date',
                                     titleTextStyle: {color: '#333'}
                                 },
                                 vAxis: {minValue: 0}
                             };

                             var chart = new google.visualization.AreaChart(document.getElementById('tracking_charts'));
                             chart.draw(data, options);
                         } else {
                             $('#tracking_charts').append('<p><i class="xagio-icon xagio-icon-warning"></i> There is not enough tracking data. Please use your shortcode in some of your posts/pages in order to start tracking impressions/unique clicks.</p>');
                         }

                     } else {
                         xagioNotify(d.status, d.message);
                     }
                 });
            });
        },
        shortcodeUrlTracking        : function () {
            $(document).on('click', '.shortcode-url-tracking', function () {
                var shortcode = $(this).parents('.shortcode');
                var id = shortcode.data('id');
                var modal = $('#urlTrackingModal');
                var name = shortcode.find('.name').text().split(']')[0].replace('[', '');
                var btn = $(this);
                modal.find('.shortcode').html(name);
                modal.find('.ID').val(id);

                btn.disable();
                $.post(xagio_data.wp_post, 'action=xagio_getTrackingUrlCharts&id=' + id)
                 .done(function (d) {
                     btn.disable();
                     if (d.status == 'success') {

                         $('#url_tracking_charts').empty();

                         if (d.data.length > 1) {
                             var formatted_data = [];
                             var raw_data = d.data;
                             for (var i = 0; i < raw_data.length; i++) {
                                 var obj = raw_data[i];
                                 if (i !== 0) {
                                     obj[0] = new Date(obj[0]);
                                 }
                                 formatted_data.push(obj);
                             }

                             var data = google.visualization.arrayToDataTable(formatted_data);

                             var options = {
                                 title: 'Shortcode Masked URL Tracking Details',
                                 hAxis: {
                                     title         : 'Date',
                                     titleTextStyle: {color: '#333'}
                                 },
                                 vAxis: {minValue: 0}
                             };

                             var chart = new google.visualization.AreaChart(document.getElementById('url_tracking_charts'));
                             chart.draw(data, options);
                         } else {
                             $('#url_tracking_charts').append('<p><i class="xagio-icon xagio-icon-warning"></i> There is not enough tracking data. Please use your shortcode in some of your posts/pages in order to start tracking impressions/unique clicks.</p>');
                         }

                     } else {
                         xagioNotify(d.status, d.message);
                     }
                 });
            });
        },
        shortcodeDuplicate          : function () {
            $(document).on('click', '.shortcode-duplicate', function () {
                var shortcode = $(this).parents('.shortcode');
                var id = shortcode.data('id');
                var btn = $(this);
                btn.disable();
                $.post(xagio_data.wp_post, 'action=xagio_duplicateShortcode&id=' + id)
                 .done(function (d) {
                     btn.disable();
                     if (d.status == 'success') {
                         actions.loadShortcodes();
                     }
                     xagioNotify(d.status, d.message);
                 });
            });
        },
        shortcodeEdit               : function () {
            $(document).on('click', '.shortcode-edit', function () {
                var shortcode = $(this).parents('.shortcode');
                var id = shortcode.data('id');

                var modal = $('#shortcodeModal');
                var btn = $(this);

                modal.find('h3').html('<i class="xagio-icon xagio-icon-edit"></i> Edit Shortcode');

                btn.disable();
                $.post(xagio_data.wp_post, 'action=xagio_getShortcode&id=' + id)
                 .done(function (d) {
                     btn.disable();
                     if (d.status == 'success') {

                         var data = d.data;

                         modal.find('.id').val(data.id);
                         modal.find('#shortcode').val(data.shortcode);
                         modal.find('#name').val(data.name);
                         modal.find('#title').val(data.title);
                         modal.find('#url').val(data.url);
                         modal.find('#image').val(data.image);

                         if (data.nofollow == 1) {
                             modal.find('#nofollow').val(data.nofollow);
                             modal.find('#nofollow').next().find('.slider-button').removeClass('on').addClass('on').html('<i class="xagio-icon xagio-icon-check"></i>');
                         } else {
                             modal.find('#nofollow').val(0);
                             modal.find('#nofollow').next().find('.slider-button').removeClass('on').html('<i class="xagio-icon xagio-icon-close"></i>');
                         }
                         if (data.target_blank == 1) {
                             modal.find('#target_blank').val(data.target_blank);
                             modal.find('#target_blank').next().find('.slider-button').removeClass('on').addClass('on').html('<i class="xagio-icon xagio-icon-check"></i>');
                         } else {
                             modal.find('#target_blank').val(0);
                             modal.find('#target_blank').next().find('.slider-button').removeClass('on').html('<i class="xagio-icon xagio-icon-close"></i>');
                         }

                         if (data.mask == 1) {
                             modal.find('#mask').val(data.mask);
                             modal.find('#mask').next().find('.slider-button').removeClass('on').addClass('on').html('<i class="xagio-icon xagio-icon-check"></i>');
                         } else {
                             modal.find('#mask').val(0);
                             modal.find('#mask').next().find('.slider-button').removeClass('on').html('<i class="xagio-icon xagio-icon-close"></i>');
                         }

                         modal.find('#image').trigger('change');

                         // modal.show();
                     } else {
                         xagioNotify(d.status, d.message);
                     }
                 });
            });
        },
        shortcodeDelete             : function () {
            $(document).on('click', '.shortcode-delete', function () {
                var shortcode = $(this).parents('.shortcode');
                var id = shortcode.data('id');
                var btn = $(this);
                btn.disable();
                $.post(xagio_data.wp_post, 'action=xagio_deleteShortcode&id=' + id)
                 .done(function (d) {
                     btn.disable();
                     xagioNotify(d.status, d.message);
                     if (d.status == 'success') {
                         actions.loadShortcodes();
                     }
                 });
            });
        },
        loadShortcodes              : function () {
            var body = $('.shortcode-body');
            body.empty().append('<h4><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Loading ...</h4>');

            var page = $('#page').val();
            var data = $('.filters').serializeArray();
            data.push({
                          name : 'action',
                          value: 'xagio_loadShortcodes'
                      });

            $.post(xagio_data.wp_post, data, function (d) {
                if (d.hasOwnProperty('success')) {
                    var rows = d.data.rows;
                    $('#redirect_mask').val(d.data.mask);
                    var pages = d.data.pages;
                    var total = d.data.total;

                    actions.updatePagination(pages, page);

                    body.empty();
                    if (rows.length < 1) {
                        body.append('<h4><i class="xagio-icon xagio-icon-info"></i> You don\'t have any created shortcodes.</h4>');
                    } else if (rows == false) {
                        body.append('<h4><i class="xagio-icon xagio-icon-info"></i> Can\'t find any shortcodes.</h4>');
                    } else {

                        if (!jQuery.isArray(rows)) {
                            rows = [rows];
                        }
                        for (var i = 0; i < rows.length; i++) {
                            var data = rows[i];
                            var template = $('.shortcode.xagio-hidden').clone();
                            template.removeClass('xagio-hidden');

                            var firstLetterOfGroup = data.group.substring(0, 1);

                            let domain = xagio_data.wp_admin;

                            let mask = data.id;

                            if (data.name != '' && data.name != null) {
                                mask = data.name;
                            }
                            domain = domain.replace('wp-admin/', '');

                            template.attr('data-id', data.id);
                            template.find('.name').html('<span class="shortcode-select">' + data.title + '</span>');

                            template.find('.copy-shortcode-tag').data('content', '[' + data.shortcode + ']');
                            template.find('.copy-masked-tag').data('content', domain + '?' + xagio_linkmanagement.redirect_mask + '=' +
                                                                              mask);

                            template.find('.url').html(data.url).attr('href', data.url);
                            template.find('.title').html(data.title);
                            template.find('.group').html(data.group);

                            template.find('.ctr').html(parseInt(data.ctr) + '%');
                            template.find('.unique_clicks').html(data.unique_clicks);
                            template.find('.impressions').html(data.impressions);

                            template.find('.url_ctr').html(parseInt(data.url_ctr) + '%');
                            template.find('.url_unique_clicks').html(data.url_unique_clicks);
                            template.find('.url_impressions').html(data.url_impressions);

                            if (data.image != '') {
                                template.find('.img').append('<img src="' + data.image + '"/>');
                            }

                            body.append(template)
                        }
                    }
                }
            });
        },
        shortcodePreview            : function () {
            $(document).on('click', '.generatedShortcode', function (e) {
                e.preventDefault()
            });
            var modal = $('#shortcodeModal');
            var preview = modal.find('.shortcode-preview');
            var elements = [
                '#shortcode',
                '#title',
                '#target_blank',
                '#nofollow',
                '#url',
                '#image'
            ];
            for (var i = 0; i < elements.length; i++) {
                var e = elements[i];
                modal.find(e).change(function () {
                    var def = '<span class="empty">Fill in the fields to preview</span>';
                    var title = modal.find(elements[1]).val();
                    var target_blank = modal.find(elements[2]).val();
                    var nofollow = modal.find(elements[3]).val();
                    var url = modal.find(elements[4]).val();
                    var image = modal.find(elements[5]).val();
                    var hasImage = image != '';

                    if (title == '' && image == '') {
                        preview.empty().append(def);
                        return null;
                    }

                    var link = '<a class="generatedShortcode ' + ((hasImage) ? 'hasImage' : '') + '" href="' + url +
                               '" ';
                    if (nofollow == 1) {
                        link += 'rel="nofollow" ';
                    }
                    if (target_blank == 1) {
                        link += 'target="_blank" ';
                    }
                    link += ">";
                    if (image != '') {
                        link += '<img class="responsive" src="' + image + '" title="' + title + '"/>';
                    } else {
                        link += title;
                    }
                    link += "</a>";

                    preview.empty().append(link);
                });
            }
        },
        selectImages                : function () {
            $('.imageSelect').click(function () {
                var target = $(this).data('target');
                $(this).parents('.xagio-modal').append("<div id='TB_window'></div>");
                tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
                window.send_to_editor = function (html) {
                    var img = $(html).attr('src');
                    $('#' + target).val(img).trigger('change');
                    tb_remove();
                }
            });
        },
        saveShortcode               : function () {
            $(document).on('submit', '.shortcodeForm', function (e) {
                e.preventDefault();
                var modal = $('#shortcodeModal');
                $.post(xagio_data.wp_post, $(this).serialize(), function (d) {
                    if (d.status == 'success') {
                        modal.find('.shortcodeForm')[0].reset();
                        modal[0].close();
                        actions.loadShortcodes();
                    }
                    xagioNotify(d.status, d.message);
                });
            });
        },
        createShortcode             : function () {
            $(document).on('click', '.create-shortcode', function (e) {

                var modal = $('#shortcodeModal');
                var url = $(this).data('url');
                var group = $(this).data('group');

                modal.find('h2').html('<i class="xagio-icon xagio-icon-plus"></i> Create Shortcode');
                modal.find('.id').val(0);
                modal.find('.group').val(group);
                modal.find('input#url').val(url).trigger('change');

                // modal.show();
            });
        },
        pagination                  : function () {
            // $(document).on('select.uk.pagination', '.uk-pagination-shortcodes', function (e, pageIndex) {
            //     $('#page').val(pageIndex);
            //     actions.loadShortcodes();
            // });

            $(document).on('change', '.xagio-table-length select', function () {
                $('#total_entries').val($(this).val());
                $('#page').val(0);
                actions.loadShortcodes();
            });

            $(document).on('click', '.xagio-table-paginate span a.paginate_button', function () {
                let btn = $(this);
                $('.xagio-table-paginate span a.paginate_button').removeClass('current');
                btn.addClass('current');
                let page = btn.attr('data-page');
                $('#page').val(page);
                actions.loadShortcodes();
            });

            $(document).on('click', '.paginate_button.previous', function () {
                $('#page').val($(this).attr('data-page'));
                actions.loadShortcodes();
            });

            $(document).on('click', '.paginate_button.next', function () {
                console.log($(this).data('page'));
                console.log($(this).attr('data-page'));
                $('#page').val($(this).attr('data-page'));
                actions.loadShortcodes();
            });
        },
        updatePagination: function (pages, currentPage) {
            pages = parseInt(pages);
            currentPage = parseInt(currentPage);

            let pagination = $('.xagio-table-paginate');

            // Clear existing pagination links
            pagination.find('span').empty();

            // Determine if "Previous" button should be enabled
            let previousButton = pagination.find('.previous');
            if (currentPage > 0) {
                previousButton.removeClass('disabled');
                previousButton.attr('data-page', currentPage - 1);
            } else {
                previousButton.addClass('disabled');
                previousButton.attr('data-page', '0');
            }

            // Generate page links and apply "current" class
            for (let i = 0; i < pages; i++) {
                let pageLink = $('<a>', {
                    class: 'paginate_button',
                    text: i + 1,  // Displayed as 1-based
                    'data-page': i  // Zero-based page index
                });

                if (i === currentPage) {
                    pageLink.addClass('current');  // Apply the "current" class to the active page
                }

                pagination.find('span').append(pageLink);
            }

            // Determine if "Next" button should be enabled
            let nextButton = pagination.find('.next');
            if (currentPage < pages - 1) {
                nextButton.removeClass('disabled');
                nextButton.attr('data-page', currentPage + 1);  // Set the next page
            } else {
                nextButton.addClass('disabled');
                nextButton.attr('data-page', currentPage);  // Keep it on the last page
            }
        }
    };

})(jQuery);
