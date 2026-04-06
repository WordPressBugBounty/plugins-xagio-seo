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

    // === LLMs.txt module ===
    let llms = {
        presets: [
            'GPTBot',
            'ChatGPT-User',
            'Google-Extended',
            'GoogleOther',
            'ClaudeBot',
            'Claude-Web',
            'PerplexityBot',
            'CCBot',
            'Amazonbot',
            'Meta-ExternalAgent',
            'FacebookBot',
            'Bytespider',
            'DataForSeoBot'
        ],

        init: function () {
            // bail if the panel isn't on this screen
            if (!$('#xagio-llms-form').length) return;

            llms.bindEvents();
            llms.refreshPreview();
        },

        bindEvents: function () {
            const $tbody = $('#xagio-llms-rules tbody');

            // Add row
            $(document).on('click', '#xagio-llms-add-row', function (e) {
                e.preventDefault();
                $tbody.append(llms.rowTemplate());
                llms.refreshPreview();
            });

            // Add presets
            $(document).on('click', '#xagio-llms-add-presets', function (e) {
                e.preventDefault();
                const existing = Array.from($tbody.find('input[name="ua[]"]'))
                                      .map(i => i.value.trim().toLowerCase());
                llms.presets.forEach(function (ua) {
                    if (existing.indexOf(ua.toLowerCase()) === -1) {
                        $tbody.append(llms.rowTemplate(ua, '/', ''));
                    }
                });
                llms.refreshPreview();
            });

            // Delete row
            $(document).on('click', '#xagio-llms-rules .link-delete-row', function (e) {
                e.preventDefault();
                $(this).closest('tr').remove();
                llms.refreshPreview();
            });

            // Live preview (debounced)
            let debouncedPreview = llms.debounce(llms.refreshPreview, 250);
            $(document).on('input change paste', '#xagio-llms-form input, #xagio-llms-form textarea', function () {
                debouncedPreview();
            });

            // Update (settings only)
            $(document).on('click', '#xagio-llms-update', function (e) {
                e.preventDefault();
                llms.save('update', $(this));
            });

            // Save to file
            $(document).on('click', '#xagio-llms-save', function (e) {
                e.preventDefault();
                llms.save('save', $(this));
            });
        },

        rowTemplate: function (ua = '', allow = '', disallow = '') {
            return `
<tr>
  <td>
    <input type="text" name="ua[]" class="xagio-input-text-mini" value="${llms.escape(ua)}" list="xagio-llms-ua" />
    <div class="xagio-gray-label"></div>
  </td>
  <td><textarea placeholder="eg. /my-article/" name="allow[]" rows="4" class="xagio-input-textarea">${llms.escape(allow)}</textarea></td>
  <td><textarea placeholder="eg. /wp-admin/" name="disallow[]" rows="4" class="xagio-input-textarea">${llms.escape(disallow)}</textarea></td>
  <td><button type="button" class="link-delete-row" title="Remove">✕</button></td>
</tr>`;
        },

        collectConfig: function () {
            const cfg = {
                rules: [],
                extra: ''
            };
            $('#xagio-llms-rules tbody tr').each(function () {
                const $tr = $(this);
                const ua = ($tr.find('input[name="ua[]"]').val() || '').trim();
                if (!ua) return;
                const allow = ($tr.find('textarea[name="allow[]"]').val() || '')
                    .split('\n').map(v => v.trim()).filter(Boolean);
                const disallow = ($tr.find('textarea[name="disallow[]"]').val() || '')
                    .split('\n').map(v => v.trim()).filter(Boolean);
                cfg.rules.push({
                                   user_agent: ua,
                                   allow     : allow,
                                   disallow  : disallow
                               });
            });
            cfg.extra = ($('#xagio-llms-form textarea[name="extra"]').val() || '').trim();
            return cfg;
        },

        generateText: function (cfg) {
            let out = '';
            (cfg.rules || []).forEach(function (b) {
                if (!b.user_agent) return;
                out += 'User-Agent: ' + b.user_agent + '\n';
                (b.allow || []).forEach(function (p) {
                    out += 'Allow: ' + p + '\n';
                });
                (b.disallow || []).forEach(function (p) {
                    out += 'Disallow: ' + p + '\n';
                });
                out += '\n';
            });
            if (cfg.extra) {
                out += '# Extra rules\n' + cfg.extra + '\n\n';
            }
            return out.replace(/\s+$/, '') + '\n';
        },

        refreshPreview: function () {
            const cfg = llms.collectConfig();
            $('#xagio-llms-preview').val(llms.generateText(cfg));
        },

        save: function (mode, $btn) {
            const form = document.getElementById('xagio-llms-form');
            if (!form) return;

            // Disable both action buttons during save
            const $buttons = $('#xagio-llms-update, #xagio-llms-save');
            $buttons.disable('Saving...');

            let fd = new FormData(form);
            fd.set('action', 'xagio_llms_save');
            fd.set('mode', mode); // 'update' or 'save'
            fd.set('config', JSON.stringify(llms.collectConfig()));

            $.ajax({
                       url        : xagio_data.wp_post,
                       method     : 'POST',
                       data       : fd,
                       processData: false,
                       contentType: false
                   }).done(function (d) {
                setTimeout(function () {
                    $buttons.disable();
                    if (d && d.success) {
                        xagioNotify('success', (d.data && d.data.message) ? d.data.message : 'Saved.');
                    } else {
                        let msg = (d && d.data && d.data.message) ? d.data.message : 'Failed to save.';
                        xagioNotify('danger', msg);
                    }
                }, 300);
            }).fail(function () {
                setTimeout(function () {
                    $buttons.disable();
                    xagioNotify('danger', 'Request failed.');
                }, 300);
            });
        },

        // Utilities
        debounce: function (fn, wait) {
            let t;
            return function () {
                clearTimeout(t);
                let args = arguments, ctx = this;
                t = setTimeout(function () {
                    fn.apply(ctx, args);
                }, wait);
            };
        },
        escape  : function (s) {
            return String(s || '').replace(/[&<>"']/g, function (m) {
                return ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                })[m];
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
        llms.init();

    });


})(jQuery);
