(function ($) {

    let llms = {
        previewTimer: null,
        previewInFlight: false,

        init: function () {
            if (!$('#xagio-llms-form').length) return;
            llms.bindSave();
            llms.bindReset();
            llms.bindLivePreview();
        },

        bindLivePreview: function () {
            const $form = $('#xagio-llms-form');

            $form.on('input change', 'input, textarea', function (e) {
                if (e.target && e.target.id === 'xagio-llms-preview') return;
                llms.schedulePreview();
            });

            $(document).on('click', '#xagio-llms-form .xagio-slider-button', function () {
                setTimeout(llms.schedulePreview, 10);
            });
        },

        schedulePreview: function () {
            if (llms.previewTimer) clearTimeout(llms.previewTimer);
            llms.previewTimer = setTimeout(llms.refreshPreview, 350);
        },

        refreshPreview: function () {
            if (llms.previewInFlight) {
                llms.schedulePreview();
                return;
            }
            llms.previewInFlight = true;

            let data = $('#xagio-llms-form').serialize() + '&mode=preview';

            $.post(xagio_data.wp_post, data, function (response) {
                llms.previewInFlight = false;
                if (response && response.status === 'success' && typeof response.data === 'string') {
                    $('#xagio-llms-preview').val(response.data);
                }
            }).fail(function () {
                llms.previewInFlight = false;
            });
        },

        bindSave: function () {
            $(document).on('click', '.llms-save', function () {
                let btn = $(this);
                btn.disable('Saving...');
                $.post(xagio_data.wp_post, $('#xagio-llms-form').serialize(), function (response) {
                    btn.disable();
                    if (response && response.status === 'success' && typeof response.data === 'string') {
                        $('#xagio-llms-preview').val(response.data);
                    }
                    xagioNotify(response && response.status ? response.status : 'danger', response && response.message ? response.message : 'Failed to save.');
                }).fail(function () {
                    btn.disable();
                    xagioNotify('danger', 'Request failed.');
                });
            });
        },

        bindReset: function () {
            $(document).on('click', '.llms-reset', function () {
                xagioModal('Reset to Default', 'Are you sure you want to reset llms.txt settings to default? All custom changes will be lost.', function (confirmed) {

                    if (!confirmed) return;

                    $.post(xagio_data.wp_post, { action: 'xagio_llms_save', mode: 'reset' }, function (response) {
                        
                        if (response && response.status === 'success' && typeof response.data === 'string') {
                            $('#xagio-llms-preview').val(response.data);
                            $('#XAGIO_LLMS_ENABLED').val(0);
                            $('.xagio-slider-button[data-element="XAGIO_LLMS_ENABLED"]').removeClass('on');
                            $('#XAGIO_LLMS_INCLUDE_SITEMAP').val(1);
                            $('.xagio-slider-button[data-element="XAGIO_LLMS_INCLUDE_SITEMAP"]').addClass('on');
                            $('#XAGIO_LLMS_INTRO').val('');
                            $('#XAGIO_LLMS_MAX_ITEMS').val(100);

                            $('input[name^="XAGIO_LLMS_POST_TYPES["]').each(function () {
                                let $input = $(this);
                                let name = $input.attr('name') || '';
                                let match = name.match(/\[([^\]]+)\]/);
                                let pt = match ? match[1] : '';
                                let on = (pt === 'page' || pt === 'post');
                                $input.val(on ? 1 : 0);
                                let $slider = $('.xagio-slider-button[data-element="' + $input.attr('id') + '"]');
                                if (on) {
                                    $slider.addClass('on');
                                } else {
                                    $slider.removeClass('on');
                                }
                            });
                        }
                        xagioNotify(response && response.status ? response.status : 'danger', response && response.message ? response.message : 'Failed to reset.');
                    }).fail(function () {
                        xagioNotify('danger', 'Request failed.');
                    });
                });
            });
        }
    };

    let okf = {
        init: function () {
            if (!$('#xagio-okf-form').length) return;
            okf.bindSave();
            okf.bindReset();
            okf.bindRebuild();
        },

        updateState: function (data) {
            if (data && typeof data === 'object') {
                if (typeof data.count !== 'undefined') $('#xagio-okf-count').text(data.count);
                if (data.built_human) $('#xagio-okf-built').text(data.built_human);
                okf.updateLint(data);
            }
        },

        updateLint: function (data) {
            // Hard errors (red): block publishing.
            let alert = $('#xagio-okf-lint-alert');
            if (alert.length) {
                // lint_ok is only meaningful when explicitly present in the payload.
                if (typeof data.lint_ok === 'undefined' || data.lint_ok) {
                    alert.hide();
                } else {
                    $('#xagio-okf-lint-headline').text(data.lint_published
                        ? 'The bundle was rebuilt but did not pass validation.'
                        : 'The new bundle was not published — the previous version is still served.');

                    let list = $('#xagio-okf-lint-list').empty();
                    (Array.isArray(data.lint_errors) ? data.lint_errors : []).forEach(function (e) {
                        list.append($('<li></li>').text(String(e)));
                    });
                    alert.show();
                }
            }

            // Content-quality notices (blue): informational, non-blocking.
            let warnings = Array.isArray(data.lint_warnings) ? data.lint_warnings : [];
            let warn = $('#xagio-okf-warn-alert');
            if (warn.length) {
                if (warnings.length) {
                    let wlist = $('#xagio-okf-warn-list').empty();
                    warnings.forEach(function (w) {
                        wlist.append($('<li></li>').text(String(w)));
                    });
                    warn.show();
                } else {
                    warn.hide();
                }
            }

            // All-clear (green): positive confirmation, shown only when validation
            // passed AND there are no content-quality notices AND the bundle has docs.
            let ok = $('#xagio-okf-ok-alert');
            if (ok.length) {
                let lintOk  = (typeof data.lint_ok === 'undefined' || data.lint_ok);
                let hasDocs = (typeof data.count === 'undefined' || data.count > 0);
                ok.toggle(lintOk && warnings.length === 0 && hasDocs);
            }
        },

        bindSave: function () {
            $(document).on('click', '.okf-save', function () {
                let btn = $(this);
                btn.disable('Saving...');
                $.post(xagio_data.wp_post, $('#xagio-okf-form').serialize(), function (response) {
                    btn.disable();
                    if (response && response.status === 'success') {
                        okf.updateState(response.data);
                    }
                    xagioNotify(response && response.status ? response.status : 'danger', response && response.message ? response.message : 'Failed to save.');
                }).fail(function () {
                    btn.disable();
                    xagioNotify('danger', 'Request failed.');
                });
            });
        },

        bindRebuild: function () {
            $(document).on('click', '.okf-rebuild', function () {
                let btn = $(this);
                btn.disable('Rebuilding...');
                $.post(xagio_data.wp_post, { action: 'xagio_okf_save', mode: 'rebuild' }, function (response) {
                    btn.disable();
                    if (response && response.status === 'success') {
                        okf.updateState(response.data);
                    }
                    xagioNotify(response && response.status ? response.status : 'danger', response && response.message ? response.message : 'Failed to rebuild.');
                }).fail(function () {
                    btn.disable();
                    xagioNotify('danger', 'Request failed.');
                });
            });
        },

        bindReset: function () {
            $(document).on('click', '.okf-reset', function () {
                xagioModal('Reset to Default', 'Are you sure you want to reset OKF settings to default? This disables /okf/ and restores the default post types.', function (confirmed) {

                    if (!confirmed) return;

                    $.post(xagio_data.wp_post, { action: 'xagio_okf_save', mode: 'reset' }, function (response) {

                        if (response && response.status === 'success') {
                            okf.updateState(response.data);
                            $('#XAGIO_OKF_ENABLED').val(0);
                            $('.xagio-slider-button[data-element="XAGIO_OKF_ENABLED"]').removeClass('on');

                            $('input[name^="XAGIO_OKF_POST_TYPES["]').each(function () {
                                let $input = $(this);
                                let name = $input.attr('name') || '';
                                let match = name.match(/\[([^\]]+)\]/);
                                let pt = match ? match[1] : '';
                                let on = (pt === 'page' || pt === 'post');
                                $input.val(on ? 1 : 0);
                                let $slider = $('.xagio-slider-button[data-element="' + $input.attr('id') + '"]');
                                if (on) {
                                    $slider.addClass('on');
                                } else {
                                    $slider.removeClass('on');
                                }
                            });
                        }
                        xagioNotify(response && response.status ? response.status : 'danger', response && response.message ? response.message : 'Failed to reset.');
                    }).fail(function () {
                        xagioNotify('danger', 'Request failed.');
                    });
                });
            });
        }
    };

    $(document).ready(function () {
        llms.init();
        okf.init();
    });

})(jQuery);
