(function ($) {

    let actions = {
        getUrlParameters    : function (sParam) {
            let sPageURL = window.location.search.substring(1);
            let sURLVariables = sPageURL.split('&');
            for (let i = 0; i < sURLVariables.length; i++) {
                let sParameterName = sURLVariables[i].split('=');
                if (sParameterName[0] == sParam) {
                    return sParameterName[1];
                }
            }
        },
        updateSeparator: function () {
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
        migrateYoast   : function () {
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
        migrateRankMath: function () {
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
        migrateAIO     : function () {
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
        selectImages   : function () {
            $('.xagio-select-image').click(function () {
                let target = $(this).data('target');
                tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
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
        updatePreviews : function () {
            $(document).on('change', '.XAGIO_OG_TITLE', function(e){
                let parent = $(this).parents('.xagio-column');
                parent.find(`.facebook-preview-title`).text($(this).val());
                parent.find(`.twitter-preview-title`).text($(this).val());
            });
            $(document).on('keyup', '.XAGIO_OG_TITLE', function(e){
                $(this).trigger('change');
            }).trigger('change');
            $(document).on('change', '.XAGIO_OG_DESCRIPTION', function(e){
                let parent = $(this).parents('.xagio-column');
                parent.find(`.facebook-preview-description`).text($(this).val());
                parent.find(`.twitter-preview-description`).text($(this).val());
            });
            $(document).on('keyup', '.XAGIO_OG_DESCRIPTION', function(e){
                $(this).trigger('change');
            }).trigger('change');
        },
        loadRobotsTab: function (tab, post_type) {


            window.history.replaceState({}, document.title, "/wp-admin/admin.php?page=xagio-seo");

            if(tab !== 'undefined') {
                $(`.xagio-tab li:eq(${tab})`).trigger('click');
            }

            if(typeof post_type !== "undefined") {
                if(post_type !== 'homepage') {
                    let homepage = $(`input[name="XAGIO_SEO_DEFAULT_POST_TYPES[homepage][XAGIO_SEO_TITLE]"]`).parents('.xagio-accordion');
                    homepage.removeClass('xagio-accordion-opened');

                    let post_type_el = $(`input[name="XAGIO_SEO_DEFAULT_POST_TYPES[${post_type}][XAGIO_SEO_TITLE]"]`).parents('.xagio-accordion');
                    post_type_el.addClass('xagio-accordion-opened');
                }

                setTimeout(function () {
                    let element = $(`input[name="XAGIO_SEO_DEFAULT_POST_TYPES[${post_type}][XAGIO_SEO_ROBOTS]"]`).parents('.xagio-slider-container');

                    if(element.length > 0) {
                        const y = element[0].getBoundingClientRect().top + window.scrollY - 200;
                        window.scroll({
                            top: y,
                            behavior: 'smooth'
                        });

                        setTimeout(function (){
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

    let scripts = {
        editorHeader  : null,
        editorBody    : null,
        editorFooter  : null,
        init          : function () {
            scripts.initEditors();
            scripts.updateVerifications();
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

                let $this = $(this);
                $this.disable("Saving");

                let data = [
                    {
                        name : 'action',
                        value: 'xagio_save_editors'
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
                $.post(xagio_data.wp_post, data, function (d) {
                    setTimeout(function () {
                        $this.disable();
                        xagioNotify("success", "Setting updated.");
                    }, 1000);
                });

            });

        },

        updateVerifications: function () {
            $(document).on('click', '.xagio-save-webmaster', function (e) {
                e.preventDefault();

                let $this = $(this);
                let parent = $this.parents('.xagio-panel');
                $this.disable("Saving");

                let data = [
                    {
                        name : 'action',
                        value: 'xagio_save_verifications'
                    }
                ];

                parent.find('.verification-input').each(function () {

                    data.push({
                                  name : $(this).attr('name'),
                                  value: btoa($(this).val())
                              });

                });

                $.post(xagio_data.wp_post, data, function (d) {
                    setTimeout(function () {
                        $this.disable();
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
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            // Function to save defaults
            function saveDefaults($input) {
                let parent = $input.parents('.xagio-accordion-panel');
                let data = [{
                    name: 'action',
                    value: 'xagio_save_defaults'
                }];
                parent.find('.defaults-input').each(function() {
                    data.push({
                                  name: $(this).attr('name'),
                                  value: $(this).val()
                              });
                });

                $.post(xagio_data.wp_post, data, function() {
                    setTimeout(function() {
                        xagioNotify("success", "Setting updated.");
                    }, 200);
                });
            }

            // Debounced version of the saveDefaults function
            let debouncedSaveDefaults = debounce(saveDefaults, 350);

            $(document).on('keydown paste change', '.defaults-input', function(e) {
                debouncedSaveDefaults($(this));
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
        if(typeof tab !== "undefined") {
            actions.loadRobotsTab(tab, post_type);
        }

        defaults.init();
        scripts.init();

    });


})(jQuery);
