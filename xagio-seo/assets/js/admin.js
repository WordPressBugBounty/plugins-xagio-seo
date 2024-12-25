(function ($) {
    'use strict';

    $(document).ready(function () {

        // Check if permitted to run
        if (!actions.allowedToRun()) {
            return;
        }

    });

    let actions = {

        allowedToRun: function () {
            return $('#xagio_seo').length > 0;
        },

        detectEditPage: function () {
            if ($('#prs-title').length == 0) {
                return false;
            }
            if ($('#soliloquy-header').length > 0) {
                return false;
            }
            if ($('[name="post_title"]').length == 0 && $('.block-editor__container').length == 0) {
                return false;
            } else {
                return true;
            }
        },
        detectTermPage: function () {
            if ($('#XAGIO_SEO_TITLE').length == 0) {
                return false;
            }
            if ($('[name="meta[taxonomy]"]').length == 0) {
                return false;
            } else {
                return true;
            }
        },


    };

    let xagio_ca = {
        init: function () {


            if (actions.detectEditPage()) {
                xagio_ca.xagio_ca_calculate_content_length();
                xagio_ca.xagio_ca_calculate_title_length();
                xagio_ca.xagio_ca_calculate_title_length_mobile();
                xagio_ca.xagio_ca_calculate_description_length();
                xagio_ca.xagio_ca_calculate_description_length_mobile();

                xagio_ca.xagio_ca_keyword_title();
                xagio_ca.xagio_ca_keyword_desc();
                xagio_ca.xagio_ca_keyword_body();
                xagio_ca.xagio_ca_keyword_url();

                xagio_ca.xagio_ca_h1_keyword();
                xagio_ca.xagio_ca_h1_keyword_content();
                xagio_ca.xagio_ca_h2_keyword();
                xagio_ca.xagio_ca_h3_keyword();

                xagio_ca.xagio_ca_keyword_density();

                setTimeout(function () {
                    xagio_ca.init()
                }, 1500);
            }
        },
        xagio_ca_h1_keyword: function () {
            let title   = ($('#title').length != 0 ? $('#title').val() : $('#post-title-0').val()).toLowerCase();
            let keyword = $('#seo_keyword').val().toLowerCase();
            if (keyword == '') {
                xagio_ca.generate_li('xagio_ca_h1_keyword', 'yellow', 'Your Target Keyword is not set.')
            } else if (title.contains(keyword)) {
                xagio_ca.generate_li('xagio_ca_h1_keyword', 'green', 'Your Target Keyword is in your Page H1.')
            } else {
                xagio_ca.generate_li('xagio_ca_h1_keyword', 'red', 'Your Target Keyword is <b>NOT</b> in your Page H1.')
            }
        },
        xagio_ca_h1_keyword_content: function () {
            let content = xagio_ca.get_content();
            if (content == '') content = '<div></div>';
            let tempDom = $('<div>').append($.parseHTML(content));

            let h1s = tempDom.find('h1');

            let contains = false;
            let keyword  = $('#seo_keyword').val().toLowerCase();

            if (h1s.length > 0) {
                h1s.each(function () {
                    let text = $(this).html().toLowerCase();
                    if (text.contains(keyword)) {
                        contains = true;
                    }
                });
            }

            if (keyword == '') {
                xagio_ca.generate_li('xagio_ca_h1_keyword', 'yellow', 'Your Target Keyword is not set.')
            } else if (h1s.length < 1) {

            } else if (contains == true) {
                xagio_ca.generate_li('xagio_ca_h1_keyword', 'green', 'Your Target Keyword is in your Page H1.')
            } else {
                xagio_ca.generate_li('xagio_ca_h1_keyword', 'red', 'Your Target Keyword is <b>NOT</b> in your Page H1.')
            }
        },
        xagio_ca_h2_keyword: function () {
            let content = xagio_ca.get_content();
            if (content == '') content = '<div></div>';
            let tempDom = $('<div>').append($.parseHTML(content));

            let h2s = tempDom.find('h2');

            let contains = false;
            let keyword  = $('#seo_keyword').val().toLowerCase();

            if (h2s.length > 0) {
                h2s.each(function () {
                    let text = $(this).html().toLowerCase();
                    if (text.contains(keyword)) {
                        contains = true;
                    }
                });
            }

            if (keyword == '') {
                xagio_ca.generate_li('xagio_ca_h2_keyword', 'yellow', 'Your Target Keyword is not set.')
            } else if (h2s.length < 1) {
                xagio_ca.generate_li('xagio_ca_h2_keyword', 'yellow', 'H2 Tags are not found in your Page.')
            } else if (contains == true) {
                xagio_ca.generate_li('xagio_ca_h2_keyword', 'green', 'Your Target Keyword is in your Page H2.')
            } else {
                xagio_ca.generate_li('xagio_ca_h2_keyword', 'red', 'Your Target Keyword is <b>NOT</b> in your Page H2.')
            }
        },
        xagio_ca_h3_keyword: function () {
            let content = xagio_ca.get_content();
            if (content == '') content = '<div></div>';
            let tempDom = $('<div>').append($.parseHTML(content));

            let h2s = tempDom.find('h3');

            let contains = false;
            let keyword  = $('#seo_keyword').val().toLowerCase();

            if (h2s.length > 0) {
                h2s.each(function () {
                    let text = $(this).html().toLowerCase();
                    if (text.contains(keyword)) {
                        contains = true;
                    }
                });
            }

            if (keyword == '') {
                xagio_ca.generate_li('xagio_ca_h3_keyword', 'yellow', 'Your Target Keyword is not set.')
            } else if (h2s.length < 1) {
                xagio_ca.generate_li('xagio_ca_h3_keyword', 'yellow', 'H3 Tags are not found in your Page.')
            } else if (contains == true) {
                xagio_ca.generate_li('xagio_ca_h3_keyword', 'green', 'Your Target Keyword is in your Page H3.')
            } else {
                xagio_ca.generate_li('xagio_ca_h3_keyword', 'red', 'Your Target Keyword is <b>NOT</b> in your Page H3.')
            }
        },
        xagio_ca_keyword_density: function () {
            let keyword = $('#seo_keyword').val().toLowerCase();
            if (keyword == '') {
                $('.count-seo-density').html('0.0%');
                return false;
            }

            let content     = xagio_ca.get_content('text').replace(/\!/g, ' ').replace(/\?/g, ' ').toLowerCase();
            let reg         = new RegExp(keyword, "g");
            let occurrences = (content.match(reg) || []).length;

            let words = xagio_ca.get_words(content, true);

            let totalWords = words.length;
            let a          = occurrences;
            let b          = totalWords;
            let c          = a / b;
            let wordCount  = c * 100;
            $('.count-seo-density').html(wordCount.toFixed(2) + '%');
        },
        xagio_ca_keyword_title: function () {
            let title   = xagio_ca.get_title_value().toLowerCase();
            let keyword = $('#seo_keyword').val().toLowerCase();
            if (keyword == '') {
                xagio_ca.generate_li('xagio_ca_keyword_title', 'yellow', 'Your Target Keyword is not set.')
            } else if (title.contains(keyword)) {
                xagio_ca.generate_li('xagio_ca_keyword_title', 'green', 'Your Target Keyword is in your Page Title.')
            } else {
                xagio_ca.generate_li('xagio_ca_keyword_title', 'red', 'Your Target Keyword is <b>NOT</b> in your Page Title.')
            }
        },
        xagio_ca_keyword_desc: function () {
            let title   = xagio_ca.get_desc_value().toLowerCase();
            let keyword = $('#seo_keyword').val().toLowerCase();
            if (keyword == '') {
                xagio_ca.generate_li('xagio_ca_keyword_desc', 'yellow', 'Your Target Keyword is not set.')
            } else if (title.contains(keyword)) {
                xagio_ca.generate_li('xagio_ca_keyword_desc', 'green', 'Your Target Keyword is in your Page Description.')
            } else {
                xagio_ca.generate_li('xagio_ca_keyword_desc', 'red', 'Your Target Keyword is <b>NOT</b> in your Page Description.')
            }
        },
        xagio_ca_keyword_body: function () {
            let body    = xagio_ca.get_content().toLowerCase();
            let keyword = $('#seo_keyword').val().toLowerCase();
            if (keyword == '') {
                xagio_ca.generate_li('xagio_ca_keyword_body', 'yellow', 'Your Target Keyword is not set.')
            } else if (body.contains(keyword)) {
                xagio_ca.generate_li('xagio_ca_keyword_body', 'green', 'Your Target Keyword is in your Page Body.')
            } else {
                xagio_ca.generate_li('xagio_ca_keyword_body', 'red', 'Your Target Keyword is <b>NOT</b> in your Page Body.')
            }
        },
        xagio_ca_keyword_url: function () {
            let url          = $('#prs-url').html().toLowerCase();
            url              = url.trim();
            let keyword      = $('#seo_keyword').val().toLowerCase().replace(/\ /g, '');
            let keyword_crte = $('#seo_keyword').val().toLowerCase().replace(/\ /g, '-');
            keyword          = keyword.trim();
            keyword_crte     = keyword_crte.trim();
            if (keyword == '') {
                xagio_ca.generate_li('xagio_ca_keyword_url', 'yellow', 'Your Target Keyword is not set.')
            } else if (url.contains(keyword) || url.contains(keyword_crte)) {
                xagio_ca.generate_li('xagio_ca_keyword_url', 'green', 'Your Target Keyword is in your Page URL.')
            } else {
                xagio_ca.generate_li('xagio_ca_keyword_url', 'red', 'Your Target Keyword is <b>NOT</b> in your Page URL.')
            }
        },
        xagio_ca_calculate_content_length: function () {
            let wordCount = xagio_ca.get_words(xagio_ca.get_content('text'));
            $('.count-seo-words').html(wordCount);
        },
        xagio_ca_calculate_title_length: function () {
            let wordCount = xagio_ca.get_title();
            if (wordCount == 0) {
                wordCount = $('#prs-title').attr('placeholder').replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
            }
            if (wordCount > 70) {
                wordCount = '<span style="color:red">' + wordCount + '</span>';
            }
            $('.count-seo-title').html(wordCount);
        },
        xagio_ca_calculate_title_length_mobile: function () {
            let wordCount = xagio_ca.get_title();
            if (wordCount == 0) {
                wordCount = $('#prs-title').attr('placeholder').replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
            }
            if (wordCount > 78) {
                wordCount = '<span style="color:red">' + wordCount + '</span>';
            }
            $('.count-seo-title-mobile').html(wordCount);
        },
        xagio_ca_calculate_description_length: function () {
            let wordCount = xagio_ca.get_desc();
            if (wordCount == 0) {
                wordCount = $('#prs-description').attr('placeholder').replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
            }
            if (wordCount > 300) {
                wordCount = '<span style="color:red">' + wordCount + '</span>';
            }
            $('.count-seo-description').html(wordCount);
        },
        xagio_ca_calculate_description_length_mobile: function () {
            let wordCount = xagio_ca.get_desc();
            if (wordCount == 0) {
                wordCount = $('#prs-description').attr('placeholder').replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
            }
            if (wordCount > 120) {
                wordCount = '<span style="color:red">' + wordCount + '</span>';
            }
            $('.count-seo-description-mobile').html(wordCount);
        },


        /** Utils **/
        generate_li: function (id, color, text) {
            let icon = '';
            if (color == 'green') icon = 'xagio-icon-check';
            if (color == 'yellow') icon = 'xagio-icon-warning';
            if (color == 'red') icon = 'xagio-icon-close';
            $('#' + id).html('<i class="fal ' + icon + ' ' + color + '"></i> ' + text);
        },
        get_title: function () {
            return $('#prs-title').html().replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
        },
        get_title_value: function () {
            let title = $('#prs-title').html();
            if (title == '') {
                title = $('#prs-title').attr('placeholder');
            }
            return title;
        },
        get_desc: function () {
            return $('#prs-description').html().replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
        },
        get_desc_value: function () {
            return $('#prs-description').html();
        },
        get_content: function (format) {
            let html = '';
            if (typeof format == 'undefined') {
                format = 'html';
            }
            if ($('#cke_content').length > 0) {
                CKEDITOR.disableAutoInline = true;
                html                       = CKEDITOR.instances.content.getData();
                if (format == 'html') {
                    html = html.replace(/\[.*?\]/g, "");
                } else {
                    let rex = /(<([^>]+)>)/ig;
                    html    = html.replace(rex, "").replace(/\[.*?\]/g, "");
                }
                return html;
            }
            try {
                html = tinyMCE.get('content').getContent({format: format});
            } catch (error) {
                let wpeditor = jQuery('#content-textarea-clone');
                if (wpeditor.length > 0) {
                    if (format == 'html') {
                        html = wpeditor.text().replace(/\[.*?\]/g, "");
                    } else {
                        let content = wpeditor.text();
                        let rex     = /(<([^>]+)>)/ig;
                        html        = content.replace(rex, "").replace(/\[.*?\]/g, "");
                    }
                } else {
                    html = '';
                }
            }
            if (html == '') {
                if (typeof thriveBody != 'undefined') {
                    if (thriveBody != '') {
                        html = thriveBody;
                        if (format == 'html') {
                            html = html.replace(/\[.*?\]/g, "");
                        } else {
                            let rex = /(<([^>]+)>)/ig;
                            html    = html.replace(rex, "").replace(/\[.*?\]/g, "");
                        }
                    }
                }
            }
            if (html == '') {
                if (typeof DiviBody != 'undefined') {
                    if (DiviBody != '') {
                        html = DiviBody;
                        if (format == 'html') {
                            html = html.replace(/\[.*?\]/g, "");
                        } else {
                            let rex = /(<([^>]+)>)/ig;
                            html    = html.replace(rex, "").replace(/\[.*?\]/g, "");
                        }
                    }
                }
            }
            if (html == '') {
                if ($('.mce-content-body').length != 0) {
                    html = $('.mce-content-body').html();
                    if (format == 'html') {
                        html = html.replace(/\[.*?\]/g, "");
                    } else {
                        let rex = /(<([^>]+)>)/ig;
                        html    = html.replace(rex, "").replace(/\[.*?\]/g, "");
                    }
                }
            }
            if (html == '') {
                try {
                    if (typeof wp.data != 'undefined' && typeof wp.data.select('core/editor') != 'undefined') {
                        if (wp.data.select("core/editor").getCurrentPost().content.length != 0) {
                            html = wp.data.select("core/editor").getCurrentPost().content;
                            if (format == 'html') {
                                html = html.replace(/\[.*?\]/g, "");
                            } else {
                                let rex = /(<([^>]+)>)/ig;
                                html    = html.replace(rex, "").replace(/\[.*?\]/g, "");
                            }
                        }
                    }
                } catch (error) {
                    console.log(error);
                }
            }
            // Remove 404 Images
            html = html.replace(/<img[^>]*>/g, "");
            return html;
        },
        get_words: function (s, b) {
            s = s.replace(/(^\s*)|(\s*$)/gi, "");//exclude  start and end white-space
            s = s.replace(/[ ]{2,}/gi, " ");//2 or more space to 1
            s = s.replace(/\n /, "\n"); // exclude newline with a start spacing
            if (typeof b == 'undefined') {
                return s.split(' ').length;
            } else {
                return s.split(' ');
            }
        }
    };

})(jQuery);

