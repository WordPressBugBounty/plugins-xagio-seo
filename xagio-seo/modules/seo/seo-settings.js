(function ($) {

    let actions = {
        getUrlParameters: function (sParam) {
            let sPageURL = window.location.search.substring(1);
            let sURLVariables = sPageURL.split('&');
            for (let i = 0; i < sURLVariables.length; i++) {
                let sParameterName = sURLVariables[i].split('=');
                if (sParameterName[0] == sParam) {
                    return sParameterName[1];
                }
            }
        },
        updateSeparator : function () {
            let separator = $('#separator');
            let value = separator.data('value');
            let inputs = separator.find('input');
            let labels = separator.find('label');
            labels.click(function () {
                labels.removeClass('checked');
                $(this).addClass('checked');
                let data = [
                    {
                        name : 'action',
                        value: 'xagio_save_separator'
                    },
                    {
                        name : 'XAGIO_SEO_TITLE_SEPARATOR',
                        value: $(this).prev('input').val()
                    }
                ];
                $.post(xagio_data.wp_post, data, function (d) {
                    xagioNotify("success", "Setting updated.");
                });
            });
            if (value != '') {
                inputs.each(function () {
                    if ($(this).val() == value) {
                        $(this).attr('checked', 'checked');
                        $(this).next().addClass('checked');
                    }
                });
            }
        },
        migrateYoast    : function () {
            $(document).on('click', '.migration-yoast', function (e) {
                e.preventDefault();
                var btn = $(this);
                btn.disable('Working ...');
                $.post(xagio_data.wp_post, 'action=xagio_migrate_yoast', function (d) {
                    btn.disable();
                    xagioNotify("success", "Yoast data successfully migrated.");
                });
            });
        },
        migrateRankMath : function () {
            $(document).on('click', '.migration-rankmath', function (e) {
                e.preventDefault();
                var btn = $(this);
                btn.disable('Working ...');
                $.post(xagio_data.wp_post, 'action=xagio_migrate_rankmath', function (d) {
                    btn.disable();
                    xagioNotify("success", "RankMath SEO data successfully migrated.");
                });
            });
        },
        migrateAIO      : function () {
            $(document).on('click', '.migration-aio', function (e) {
                e.preventDefault();
                var btn = $(this);
                btn.disable('Working ...');
                $.post(xagio_data.wp_post, 'action=xagio_migrate_aio', function (d) {
                    btn.disable();
                    xagioNotify("success", "AIO data successfully migrated.");
                });
            });
        },
        selectImages    : function () {
            $('.xagio-select-image').click(function () {
                let target = $(this).data('target');
                tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');

                $('#TB_iframeContent').on('load', function () {
                    const iframe = this.contentWindow || this.contentDocument;
                    const doc = iframe.document || iframe;

                    const style = doc.createElement('style');
                    style.textContent = `
                        .media-item {
                          display: inline-block;
                        }
                        form#filter {
                          width: unset !important;
                        }
                      `;
                    doc.head.appendChild(style);
                });

                window.send_to_editor = function (html) {
                    var img = $(html).attr('src');
                    $('#' + target).val(img).trigger('change');
                    tb_remove();
                }
            });
            $(document).on('change', `.XAGIO_OG_IMAGE`, function (e) {
                // if $(this).val() is an image url continue
                if ($(this).val().match(/\.(jpeg|jpg|gif|png)$/) != null) {
                    let parent = $(this).parents('.xagio-column');
                    parent.find(`img`).attr('src', $(this).val());
                }
            });
        },
        updatePreviews  : function () {
            $(document).on('change', '.XAGIO_OG_TITLE', function (e) {
                let parent = $(this).parents('.xagio-column');
                parent.find(`.facebook-preview-title`).text($(this).val());
                parent.find(`.twitter-preview-title`).text($(this).val());
            });
            $(document).on('keyup', '.XAGIO_OG_TITLE', function (e) {
                $(this).trigger('change');
            }).trigger('change');
            $(document).on('change', '.XAGIO_OG_DESCRIPTION', function (e) {
                let parent = $(this).parents('.xagio-column');
                parent.find(`.facebook-preview-description`).text($(this).val());
                parent.find(`.twitter-preview-description`).text($(this).val());
            });
            $(document).on('keyup', '.XAGIO_OG_DESCRIPTION', function (e) {
                $(this).trigger('change');
            }).trigger('change');
        },
        loadRobotsTab   : function (tab, post_type) {


            window.history.replaceState({}, document.title, "/wp-admin/admin.php?page=xagio-seo");

            if (tab !== 'undefined') {
                $(`.xagio-tab li:eq(${tab})`).trigger('click');
            }

            if (typeof post_type !== "undefined") {
                if (post_type !== 'homepage') {
                    let homepage = $(`input[name="XAGIO_SEO_DEFAULT_POST_TYPES[homepage][XAGIO_SEO_TITLE]"]`).parents('.xagio-accordion');
                    homepage.removeClass('xagio-accordion-opened');

                    let post_type_el = $(`input[name="XAGIO_SEO_DEFAULT_POST_TYPES[${post_type}][XAGIO_SEO_TITLE]"]`).parents('.xagio-accordion');
                    post_type_el.addClass('xagio-accordion-opened');
                }

                setTimeout(function () {
                    let element = $(`input[name="XAGIO_SEO_DEFAULT_POST_TYPES[${post_type}][XAGIO_SEO_ROBOTS]"]`).parents('.xagio-slider-container');

                    if (element.length > 0) {
                        const y = element[0].getBoundingClientRect().top + window.scrollY - 200;
                        window.scroll({
                                          top     : y,
                                          behavior: 'smooth'
                                      });

                        setTimeout(function () {
                            element.addClass('uk-animation-shake');
                            element.parents('.xagio-save-changes-holder').addClass('xagio-highlight-animation');
                            setTimeout(() => {
                                element.removeClass('uk-animation-shake');
                            }, 500);
                        }, 500)
                    }
                }, 500);

            }
        }
    };

    let profiles = {
        init        : function () {
            profiles.saveProfiles();
            profiles.loadProfiles();
        },
        saveProfiles: function () {
            function debounce(func, wait) {
                let timeout;
                return function (...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            let debouncedSave = debounce(function () {
                let data = [
                    {
                        name : 'action',
                        value: 'xagio_save_profiles'
                    }
                ];

                $('.profiles_input').each(function () {
                    data.push({
                        name : $(this).attr('name'),
                        value: $(this).val()
                    });
                });

                $.post(xagio_data.wp_post, data, function (d) {
                    setTimeout(function () {
                        xagioNotify(d.status, d.message);
                    }, 200);
                });
            }, 500);

            $(document).on('input', '.profiles_input', function (e) {
                debouncedSave($(this));
            });
        },
        loadProfiles: function () {
            $.get(xagio_data.wp_post, `action=xagio_load_profiles`, function (d) {
                if (d.data) {
                    $.each(d.data, function (category, item) {
                        $.each(item, function (key, value) {
                            $(`input[name="XAGIO_SEO_PROFILES[${category}][${key}]"]`).val(value);
                        });
                    });
                }
            });
        }
    };

    let scripts = {
        editorHeader  : null,
        editorBody    : null,
        editorFooter  : null,
        init          : function () {
            scripts.initEditors();
        },
        refreshEditors: function () {
            cm_settings.e1.codemirror.refresh();
            cm_settings.e2.codemirror.refresh();
            cm_settings.e3.codemirror.refresh();
        },
        initEditors   : function () {
            cm_settings.codeEditor.codemirror.lineNumbers = true;
            cm_settings.codeEditor.codemirror.autoRefresh = true;

            cm_settings.e1 = wp.codeEditor.initialize($('[name="XAGIO_SEO_GLOBAL_SCRIPTS_HEAD"]'), cm_settings);
            cm_settings.e2 = wp.codeEditor.initialize($('[name="XAGIO_SEO_GLOBAL_SCRIPTS_BODY"]'), cm_settings);
            cm_settings.e3 = wp.codeEditor.initialize($('[name="XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER"]'), cm_settings);

            $(document).on('click', '.xagio-tab > li', function (e) {
                setTimeout(function () {
                    scripts.refreshEditors();
                }, 100);
            });

            $(document).on('click', '.xagio-save-scripts', function (e) {
                e.preventDefault();

                // disable both buttons
                $(".xagio-save-scripts").disable("Saving");

                let data = [
                    {
                        name : 'action',
                        value: 'xagio_save_scripts'
                    },
                    {
                        name : 'XAGIO_SEO_GLOBAL_SCRIPTS_HEAD',
                        value: btoa(cm_settings.e1.codemirror.getValue())
                    },
                    {
                        name : 'XAGIO_SEO_GLOBAL_SCRIPTS_BODY',
                        value: btoa(cm_settings.e2.codemirror.getValue())
                    },
                    {
                        name : 'XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER',
                        value: btoa(cm_settings.e3.codemirror.getValue())
                    }
                ];

                $('.verification-input').each(function () {
                    data.push({
                                  name : $(this).attr('name'),
                                  value: btoa($(this).val())
                              });
                });

                $.post(xagio_data.wp_post, data, function (d) {
                    setTimeout(function () {
                        $(".xagio-save-scripts").disable();
                        xagioNotify("success", "Setting updated.");
                    }, 1000);
                });

            });

        }
    };

    let defaults = {
        init          : function () {
            defaults.updateDefaults();
        },
        updateDefaults: function () {

            // Debounce function to limit the rate of function calls
            function debounce(func, wait) {
                let timeout;
                return function (...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            // Function to save defaults
            function saveDefaults($input) {
                let parent = $input.parents('.xagio-accordion-panel');
                let data = [
                    {
                        name : 'action',
                        value: 'xagio_save_defaults'
                    }
                ];
                parent.find('.defaults-input').each(function () {
                    data.push({
                                  name : $(this).attr('name'),
                                  value: $(this).val()
                              });
                });

                $.post(xagio_data.wp_post, data, function () {
                    setTimeout(function () {
                        xagioNotify("success", "Setting updated.");
                    }, 200);
                });
            }

            // Debounced version of the saveDefaults function
            let debouncedSaveDefaults = debounce(saveDefaults, 350);

            $(document).on('keydown paste change', '.defaults-input', function (e) {
                debouncedSaveDefaults($(this));
            });

        }
    };

    let robots = {
        previewTimer: null,
        previewInFlight: false,

        init: function () {
            robots.save();
            robots.reset();
            robots.bindCdnCheck();
            robots.bindLivePreview();
        },

        bindLivePreview: function () {
            $(document).on('click', '.robots .xagio-slider-button', function () {
                let $btn = $(this);
                let id = $btn.attr('data-element') || '';
                let isAi = id.indexOf('XAGIO_AI_') === 0;
                let isCs = id.indexOf('XAGIO_CS_') === 0 || id === 'XAGIO_CONTENT_SIGNAL_ENABLED';
                if (!isAi && !isCs) return;
                setTimeout(robots.schedulePreview, 10);
            });
        },

        schedulePreview: function () {
            if (robots.previewTimer) clearTimeout(robots.previewTimer);
            robots.previewTimer = setTimeout(robots.refreshPreview, 250);
        },

        refreshPreview: function () {
            if (robots.previewInFlight) {
                robots.schedulePreview();
                return;
            }
            robots.previewInFlight = true;

            let data = $('.robots').serialize() + '&mode=preview';

            $.post(xagio_data.wp_post, data, function (response) {
                robots.previewInFlight = false;
                if (response && response.status === 'success' && typeof response.data === 'string') {
                    $('#XAGIO_ROBOTS_TXT_CUSTOM').val(response.data);
                }
            }).fail(function () {
                robots.previewInFlight = false;
            });
        },

        save: function () {
            $(document).on('click', '.robots-save', function () {
                let btn    = $(this);
                let content = $('#XAGIO_ROBOTS_TXT_CUSTOM').val();

                if (/^Disallow:\s*\/\s*$/m.test(content)) {
                    xagioModal(
                        'Warning',
                        '<b>Disallow: /</b> blocks search engines from crawling your entire site. Are you sure you want to save this?',
                        function (confirmed) {
                            if (confirmed) {
                                robots.doSave(btn);
                            }
                        }
                    );
                } else {
                    robots.doSave(btn);
                }
            });
        },

        reset: function () {
            $(document).on('click', '.robots-reset', function () {
                xagioModal('Reset to Default', 'Are you sure you want to reset robots.txt to default? All custom changes will be lost.', function (confirmed) {
                    if (!confirmed) return;

                    $.post(xagio_data.wp_post, { action: 'xagio_robots_save', mode: 'reset' }, function (response) {
                        if (response.status === 'success') {
                            $('#XAGIO_ROBOTS_TXT_CUSTOM').val(response.data);
                            $('input[data-ai-crawler="1"]').each(function () {
                                $(this).val(1);
                                let $slider = $('.xagio-slider-button[data-element="' + $(this).attr('id') + '"]');
                                $slider.addClass('on');
                            });
                            // Content Signal back to defaults: disabled, search=yes, ai-input=yes, ai-train=no.
                            let csReset = {
                                'XAGIO_CONTENT_SIGNAL_ENABLED': 0,
                                'XAGIO_CS_SEARCH': 1,
                                'XAGIO_CS_AI_INPUT': 1,
                                'XAGIO_CS_AI_TRAIN': 0
                            };
                            $.each(csReset, function (id, val) {
                                $('#' + id).val(val);
                                let $slider = $('.xagio-slider-button[data-element="' + id + '"]');
                                if (val) { $slider.addClass('on'); } else { $slider.removeClass('on'); }
                            });
                        }
                        xagioNotify(response.status, response.message);
                    });
                });
            });
        },

        bindCdnCheck: function () {
            $(document).on('click', '#xagio-robots-cdn-check', function () {
                let btn = $(this);
                let $out = $('#xagio-robots-cdn-result');
                btn.disable('Checking...');
                $out.empty();

                $.post(xagio_data.wp_post, { action: 'xagio_robots_ai_check' }, function (response) {
                    btn.disable();
                    robots.renderCdnResult($out, response);
                }).fail(function () {
                    btn.disable();
                    $out.html('<div class="xagio-alert xagio-alert-danger"><i class="xagio-icon xagio-icon-warning"></i> Request failed.</div>');
                });
            });
        },

        renderCdnResult: function ($out, response) {
            if (!response) {
                $out.html('<div class="xagio-alert xagio-alert-danger">No response.</div>');
                return;
            }

            let data = response.data || {};
            let mismatch = Array.isArray(data.mismatch) ? data.mismatch : [];
            let url = data.url ? robots.escape(data.url) : '';
            let http = data.http ? parseInt(data.http, 10) : 0;
            let body = typeof data.body === 'string' ? data.body : '';

            let html = '';

            if (response.status !== 'success') {
                html += '<div class="xagio-alert xagio-alert-danger"><i class="xagio-icon xagio-icon-warning"></i> ' + robots.escape(response.message || 'Check failed.') + '</div>';
            } else if (mismatch.length === 0) {
                html += '<div class="xagio-alert xagio-alert-success"><i class="xagio-icon xagio-icon-check"></i> Live <code>/robots.txt</code> matches your Xagio settings.</div>';
            } else {
                html += '<div class="xagio-alert xagio-alert-warning"><i class="xagio-icon xagio-icon-warning"></i> Found ' + mismatch.length + ' mismatch(es) between Xagio and live <code>/robots.txt</code>:</div>';
                html += '<ul class="xagio-margin-top-small">';
                mismatch.forEach(function (m) {
                    html += '<li><strong>' + robots.escape(m.user_agent || '') + '</strong>: ' + robots.escape(m.message || '') + '</li>';
                });
                html += '</ul>';
                html += '<p class="xagio-gray-label">If a CDN (Cloudflare, etc.) is injecting AI bot blocks above WordPress, your Xagio settings cannot override that — you must disable the CDN feature.</p>';
            }

            if (body) {
                html += '<details class="xagio-margin-top-medium"><summary>Live response body (' + body.length + ' bytes from ' + url + ')</summary>';
                html += '<pre class="xagio-input-textarea" style="white-space:pre-wrap;max-height:300px;overflow:auto;">' + robots.escape(body) + '</pre>';
                html += '</details>';
            }

            $out.html(html);
        },

        escape: function (s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) {
                return ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                })[m];
            });
        },

        doSave: function (btn) {
            btn.disable();
            $.post(xagio_data.wp_post, $('.robots').serialize(), function (response) {
                btn.disable();
                if (response && response.status === 'success' && typeof response.data === 'string') {
                    $('#XAGIO_ROBOTS_TXT_CUSTOM').val(response.data);
                }
                xagioNotify(response.status, response.message);
            });
        }
    };

    $(document).ready(function () {

        actions.migrateYoast();
        actions.migrateAIO();
        actions.migrateRankMath();
        actions.updateSeparator();
        actions.selectImages();
        actions.updatePreviews();

        let tab = actions.getUrlParameters('tab');
        let post_type = actions.getUrlParameters('tab_type');
        if (typeof tab !== "undefined") {
            actions.loadRobotsTab(tab, post_type);
        }

        defaults.init();
        scripts.init();
        profiles.init();
        robots.init();

    });


})(jQuery);
