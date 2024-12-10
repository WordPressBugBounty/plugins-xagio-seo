// Store the widgets
let ps_widgets = [];
if (typeof (ps_widgets) == 'undefined') {
    jQuery(document).ready(function () {
        ps_widgets.push({
                            name: xagio_review_data.unique_id,
                            data: {
                                ps_admin_url       : xagio_review_data.ps_admin_url,
                                ps_thank_you       : xagio_review_data.ps_thank_you,
                                ps_rating_thank_you: xagio_review_data.ps_rating_thank_you,
                                ps_stars_only      : xagio_review_data.ps_stars_only,
                                ps_stars_init      : xagio_review_data.ps_stars_init
                            }
                        });
    });
} else {
    ps_widgets.push({
                        name: xagio_review_data.unique_id,
                        data: {
                            ps_admin_url       : xagio_review_data.ps_admin_url,
                            ps_thank_you       : xagio_review_data.ps_thank_you,
                            ps_rating_thank_you: xagio_review_data.ps_rating_thank_you,
                            ps_stars_only      : xagio_review_data.ps_stars_only,
                            ps_stars_init      : xagio_review_data.ps_stars_init
                        }
                    });
}

// Activate Widget
let ps_activate_widget = function ($, widget) {
    'use strict';
    let widgetContainer = $('.' + widget.name);
    let render          = {
        fields      : {
            name     : {
                type          : "text",
                label         : "Name:",
                placeholder   : "eg. John",
                altplaceholder: "Your Name",
                visible       : true
            },
            review   : {
                type          : "textarea",
                label         : "Review:",
                placeholder   : "eg. This is really a cool website!",
                altplaceholder: "Your Review",
                visible       : true
            },
            rating   : {
                type          : "stars",
                label         : "Rating:",
                placeholder   : "",
                altplaceholder: "",
                visible       : true
            },
            email    : {
                type          : "email",
                label         : "E-Mail Address:",
                placeholder   : "eg. your@email.com",
                altplaceholder: "E-Mail Address",
                visible       : false
            },
            website  : {
                type          : "url",
                label         : "Website:",
                placeholder   : "eg. http://www.website.com",
                altplaceholder: "Your Website",
                visible       : false
            },
            title    : {
                type          : "text",
                label         : "Title:",
                placeholder   : "eg. I like this product",
                altplaceholder: "Your Title",
                visible       : false
            },
            telephone: {
                type          : "text",
                label         : "Telephone:",
                placeholder   : "eg. 1-800-500-6000",
                altplaceholder: "Your Phone Number",
                visible       : false
            },
            location : {
                type          : "text",
                label         : "Location:",
                placeholder   : "eg. Los Angeles",
                altplaceholder: "Your Location",
                visible       : false
            },
            age      : {
                type          : "number",
                label         : "Age:",
                placeholder   : "eg. 35",
                altplaceholder: "Your Age",
                visible       : false
            }
        },
        init        : function () {
            render.firstStart();
            render.doRender();
        },
        firstStart  : function () {
            let json = widgetContainer.find('[name="XAGIO_REVIEW[fields]"]').val();
            if (json === '' || typeof (json) === 'undefined') return;
            json = JSON.parse(atob(json));

            let fields_temp = render.fields;
            render.fields   = $.extend(true, {}, json);

            for (let key in fields_temp) {
                if (!render.fields.hasOwnProperty(key)) {
                    render.fields[key] = fields_temp[key];
                }
            }
        },
        doRender    : function () {
            let cnt     = widgetContainer.find('.review-widget-block-container');
            let i       = 0;
            let allHtml = '';
            for (let name in render.fields) {

                i++;
                let field = render.fields[name];
                if (field.visible == false || (widget.data.ps_stars_only == 1 && name !== 'rating')) {
                    allHtml += '<input type="hidden" name="' + name + '" value=""/>';
                    continue;
                }
                let html = '';
                if (widget.data.ps_stars_only != 1) {
                    html += '<label class="review-widget-label" for="i' + i + '">' + field.label + '</label>';
                }

                let attr_required = '';
                if (field.hasOwnProperty('required')) {
                    if (field.required) {
                        attr_required = 'required';
                    }
                }

                switch (field.type) {
                    case "text":
                        if (field.label === "Name:") {
                            attr_required = 'required';
                        }
                        html += '<input name="' + name + '" type="text" class="review-widget-input" ' + attr_required + ' id="i' + i + '" placeholder="' + field.placeholder + '" data-placeholder="' + field.placeholder + '" data-alt-placeholder="' + field.altplaceholder + '" />';
                        break;
                    case "textarea":
                        html += '<textarea name="' + name + '" rows="5" class="review-widget-input" required id="i' + i + '" placeholder="' + field.placeholder + '" data-placeholder="' + field.placeholder + '" data-alt-placeholder="' + field.altplaceholder + '" ></textarea>';
                        break;
                    case "stars":
                        html += '<input type="hidden" value="5" name="' + name + '" id="ps-rating"/>';
                        html += '<div class="review-widget-stars">';
                        html += '<i data-value="1" class="xagio-icon xagio-icon-star-o"></i>';
                        html += '<i data-value="2" class="xagio-icon xagio-icon-star-o"></i>';
                        html += '<i data-value="3" class="xagio-icon xagio-icon-star-o"></i>';
                        html += '<i data-value="4" class="xagio-icon xagio-icon-star-o"></i>';
                        html += '<i data-value="5" class="xagio-icon xagio-icon-star-o"></i>';
                        html += '</div>';
                        break;
                    case "email":
                        html += '<input name="' + name + '" type="email" class="review-widget-input" ' + attr_required + ' id="i' + i + '" placeholder="' + field.placeholder + '" data-placeholder="' + field.placeholder + '" data-alt-placeholder="' + field.altplaceholder + '" />';
                        break;
                    case "url":
                        html += '<input name="' + name + '" type="url" class="review-widget-input" ' + attr_required + ' id="i' + i + '" placeholder="' + field.placeholder + '" data-placeholder="' + field.placeholder + '" data-alt-placeholder="' + field.altplaceholder + '" />';
                        break;
                    case "number":
                        html += '<input name="' + name + '" type="number" min="18" max="99" class="review-widget-input" ' + attr_required + ' id="i' + i + '" placeholder="' + field.placeholder + '" data-placeholder="' + field.placeholder + '" data-alt-placeholder="' + field.altplaceholder + '" />';
                        break;
                }
                html += '<div class="cx"></div>';
                html = '<div class="review-widget-block">' + html + '</div>';
                allHtml += html;
            }
            cnt.append(allHtml);
        },
        popup       : function () {
            widgetContainer.find('#review-widget-popup-button').click(function (e) {
                e.preventDefault();
                widgetContainer.find('.review-widget-popup-container').fadeIn();
                widgetContainer.find('.review-widget').fadeIn();
            });
            widgetContainer.find('.review-widget-popup-container').click(function () {
                $(this).fadeOut();
                widgetContainer.find('.review-widget').fadeOut();
            });
        },
        submitReview: function () {

            if (widget.data.ps_stars_only != 1) {

                widgetContainer.find('.ps-submit-review').submit(function (e) {
                    e.preventDefault();

                    let btn   = $(this).find('.review-widget-button');
                    let title = widgetContainer.find('.review-widget-title');
                    let text  = widgetContainer.find('.review-widget-text');

                    btn.attr('disabled', 'disabled');
                    btn.html('<i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Processing');

                    $.post(widget.data.ps_admin_url, $(this).serialize(), function (d) {
                        if (d.status == 'success') {
                            title.html('<i class="xagio-icon xagio-icon-check"></i> Review Submitted!');
                            text.html(widget.data.ps_thank_you);
                        } else {
                            title.html('<i class="xagio-icon xagio-icon-close"></i> Oops!');
                            text.html(d.message);
                        }

                        title.prevAll().hide();
                        text.nextAll().hide();

                        widgetContainer.find('.review-widget').addClass('review-widget-message');

                        if ($('.review-widget-popup-container').is(':visible')) {
                            setTimeout(function () {
                                widgetContainer.find('.review-widget-popup-container').fadeOut();
                                widgetContainer.find('.review-widget').fadeOut();
                            }, 3000);
                        }
                    });
                });

            } else {

                let stars = widgetContainer.find('.review-widget-stars > i');
                stars.click(function () {

                    stars.unbind();

                    let data = widgetContainer.find('.ps-submit-review').serialize();
                    let text = widgetContainer.find('.review-widget-stars-ratings-info');

                    $.post(widget.data.ps_admin_url, data, function (d) {

                        if (d.status == 'success') {
                            text.html(widget.data.ps_rating_thank_you);
                        } else {
                            text.html(d.message);
                        }

                        if (widgetContainer.find('.review-widget-popup-container').is(':visible')) {
                            setTimeout(function () {
                                widgetContainer.find('.review-widget-popup-container').fadeOut();
                                widgetContainer.find('.review-widget').fadeOut();
                            }, 3000);
                        }
                    });

                });

            }

        },
        starsHooks  : function () {
            let starc  = widgetContainer.find('.review-widget-stars');
            let stars  = widgetContainer.find('.review-widget-stars > i');
            let rating = widgetContainer.find('#ps-rating');
            stars.click(function () {
                let star = $(this);
                rating.val(star.data('value'));
            });
            stars.mouseenter(function () {
                let star = $(this);
                render.starFull(star);
                star.prevAll().each(function () {
                    render.starFull($(this));
                });
                star.nextAll().each(function () {
                    render.starEmpty($(this));
                });
            });
            starc.mouseleave(function () {
                let star = widgetContainer.find('i[data-value="' + rating.val() + '"]');
                render.starFull(star);
                star.prevAll().each(function () {
                    render.starFull($(this));
                });
                star.nextAll().each(function () {
                    render.starEmpty($(this));
                });
            });

            if (widget.data.ps_stars_init != '') {
                let starIndex = 0;
                if (widget.data.ps_stars_init >= 20 && widget.data.ps_stars_init < 40) {
                    starIndex = 1;
                }
                if (widget.data.ps_stars_init >= 40 && widget.data.ps_stars_init < 60) {
                    starIndex = 2;
                }
                if (widget.data.ps_stars_init >= 60 && widget.data.ps_stars_init < 80) {
                    starIndex = 3;
                }
                if (widget.data.ps_stars_init >= 80 && widget.data.ps_stars_init < 100) {
                    starIndex = 4;
                }
                if (widget.data.ps_stars_init == 100) {
                    starIndex = 4;
                }
                stars.eq(starIndex).trigger('click');
                stars.eq(starIndex).trigger('mouseenter');
                stars.eq(starIndex).trigger('mouseleave');
            }

        },
        starFull    : function (e) {
            if (!e.hasClass('xagio-icon-star')) e.removeClass('xagio-icon-star-o').addClass('xagio-icon-star');
        },
        starEmpty   : function (e) {
            if (!e.hasClass('xagio-icon-star-o')) e.removeClass('xagio-icon-star').addClass('xagio-icon-star-o');
        }
    };

    render.init();
    render.starsHooks();
    render.popup();
    render.submitReview();
};

(function ($) {

    let render_display = {
        showMoreLess: function () {
            $(document).on('click', '.prs-show-reviews', function (e) {
                e.preventDefault();
                let btn = $(this);
                if (btn.find('span').text() === 'Show more') {
                    btn.find('i').removeClass('xagio-icon-arrow-down').addClass('xagio-icon-arrow-up');
                    btn.find('span').text('Show less');
                    $('.review-hidden').addClass('review-hidden-open').removeClass('review-hidden');
                } else if (btn.find('span').text() === 'Show less') {
                    btn.find('i').removeClass('xagio-icon-arrow-up').addClass('xagio-icon-arrow-down');
                    btn.find('span').text('Show more');
                    $('.review-hidden-open').addClass('review-hidden').removeClass('review-hidden-open');
                }
            });
        }
    };

    let exit_popup = {
        init     : function () {
            $(document).mouseleave(function () {
                if (exit_popup.getCookie('exit-popup') !== '1') {
                    if ($('.exit-popup-window').eq(0).length != 0) {
                        exit_popup.setCookie('exit-popup', '1');
                        $('.exit-popup-window').eq(0).trigger('click');
                    }
                }
            });
        },
        setCookie: function (key, value) {
            var expires = new Date();
            expires.setTime(expires.getTime() + (1 * 24 * 60 * 60 * 1000));
            document.cookie = key + '=' + value + ';expires=' + expires.toUTCString() + ';path=/';
        },
        getCookie: function (key) {
            var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
            return keyValue ? keyValue[2] : null;
        }
    };

    $(document).ready(function () {
        if (typeof (ps_widgets) == 'undefined') ps_widgets = [];

        // Check if there are any widgets to be initialized
        if (ps_widgets.length > 0) {
            for (let i = 0; i < ps_widgets.length; i++) {
                let widget = ps_widgets[i];
                ps_activate_widget($, widget);
            }
        }

        // Display reviews on click
        render_display.showMoreLess();

        // Init the exit popup stuff
        exit_popup.init();
    });

})(jQuery);

