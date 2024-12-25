(function ($) {
    'use strict';

    $(document).ready(function () {
        search_preview.init();
    });

    let search_preview = {
        init: function () {
            search_preview.titleDescriptionCalculation();
            search_preview.editorInit();
        },
        parseNumber: function (num) {
            if (num == null || num == "") {
                return '';
            } else {
                return parseInt(num).toLocaleString();
            }
        },
        titleDescriptionCalculation: function () {
            search_preview.calculateProgressTitle();
            search_preview.calculateProgressDescription();
            search_preview.calculateTitleLength();
            search_preview.calculateDescriptionLength();
        },
        calculateTitleLength: function () {
            $(document).on('keydown paste input', '#XAGIO_SEO_TITLE', function () {
                search_preview.calculateProgressTitle();
            });
        },
        calculateDescriptionLength: function () {
            $(document).on('keydown paste input', '#XAGIO_SEO_DESCRIPTION', function () {
                search_preview.calculateProgressDescription();
            });
        },
        calculateProgressTitle: function () {
            let title_check = $('.xagio-title-length');
            let title_check_line = $('.title-check-circle');
            let wordCount = search_preview.getTitle();
            let p = (wordCount / 61) * 100;
            if (wordCount >= 15 && wordCount <= 60) {
                // Green
                title_check_line.css('--xagio-grad-color', '#00BF63');
                title_check.css('--xagio-grad-color', '#00BF63');
            } else {
                title_check_line.css('--xagio-grad-color', '#FFB000');
                title_check.css('--xagio-grad-color', '#FFB000');
                // Warning
            }
            search_preview.animationLengthCheck(title_check.find('.inside-check-circle'));
            if (wordCount < 1) {
                title_check.find('.inside-check-circle').html('0/60');
            } else if (wordCount > 999) {
                title_check.find('.inside-check-circle').html('>1k/60');
            } else {
                title_check.find('.inside-check-circle').html(`${wordCount}/60`);
            }

            if (p <= 100) {
                title_check_line.css('--xagio-grad-fill', p + '%');
            }
        },
        calculateProgressDescription: function () {
            let desc_check = $('.xagio-desc-length');
            let desc_check_line = $('.desc-check-circle');

            let wordCount = search_preview.getDescription();
            let p = (wordCount / 160) * 100;
            if (wordCount >= 40 && wordCount <= 160) {
                // Green
                desc_check_line.css('--xagio-grad-color', '#00BF63');
                desc_check.css('--xagio-grad-color', '#00BF63');
            } else {
                // Warning
                desc_check_line.css('--xagio-grad-color', '#FFB000');
                desc_check.css('--xagio-grad-color', '#FFB000');
            }

            search_preview.animationLengthCheck(desc_check.find('.inside-check-circle'));

            if (wordCount < 1) {
                desc_check.find('.inside-check-circle').html('0/160');
            } else if (wordCount > 999) {
                desc_check.find('.inside-check-circle').html('>1k/160');
            } else {
                desc_check.find('.inside-check-circle').html(`${wordCount}/160`);
            }

            if (p <= 100) {
                desc_check_line.css('--xagio-grad-fill', p + '%');
            }
        },
        animationLengthCheck: function (el) {
            el.css('animation', '120ms circle_update ease-in forwards');
            setTimeout(function () {
                el.css('animation', 'none');
            }, 120);
        },
        calculateAndTrim: function (t) {
            let words_split = [];
            for (let i = 0; i < t.length; i++) {
                words_split.push(t[i].split(' '));
            }
            words_split = [].concat.apply([], words_split);
            let words = [];

            for (let i = 0; i < words_split.length; i++) {
                let check = 0;
                let final = {
                    text: '',
                    weight: 0,
                    html: {
                        'data-xagio-title': 0,
                        'data-xagio-tooltip': ''
                    }
                };
                for (let j = 0; j < words.length; j++) {
                    if (words_split[i] == words[j].text) {
                        check = 1;
                        ++words[j].weight;
                        ++words[j].html['data-xagio-title'];
                    }
                }
                if (check == 0) {
                    final.text = words_split[i];
                    final.weight = 1;
                    final.html['data-xagio-title'] = 1;
                    words.push(final);
                }
                check = 0;
            }

            return words;
        },
        getTitle: () => {
            return $('#XAGIO_SEO_TITLE').html().replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
        },
        getDescription: () => {
            return $('#XAGIO_SEO_DESCRIPTION').html().replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
        },
        editorInit: function () {

            $('body').on('focus', '.xagio-editor[contenteditable="true"]', function () {
                let $this = $(this);
                $this.data('before', $this.html());
                return $this;
            }).on('blur keyup input', '.xagio-editor[contenteditable="true"]', function () {
                let $this = $(this);
                if ($this.data('before') !== $this.html()) {
                    $this.data('before', $this.html());
                    $this.trigger('change');
                }
                return $this;
            }).on('paste', '.xagio-editor[contenteditable="true"]', function (e) {
                e.preventDefault();
                // get text representation of clipboard
                let text = (e.originalEvent || e).clipboardData.getData('text/plain');
                // insert text manually
                $(this).html($(this).html() + text).trigger('change');
            });
            $('.xagio-editor').change(function (e) {
                e.stopPropagation();
                let text = $(this).text();
                let id = $(this).data('target');
                text = text.replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().replace(/<\/?[^>]+(>|$)/g, "").trim();
                $('input#' + id).val(text).trigger('change');
            }).keydown(function (e) {
                e.stopPropagation();
            }).keyup(function (e) {
                e.stopPropagation();
            }).keypress(function (e) {
                e.stopPropagation();
            });

        },
    };

})(jQuery);