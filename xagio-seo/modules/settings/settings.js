var plugins, themes, ajax_timeout, timeout;

(function ($) {

    var matcher;
    matcher = function (params, data) {
        var terms, text;
        if (params.term == null) {
            return data;
        }
        terms = params.term.toUpperCase().split(' ');
        text = data.text.toUpperCase();
        if (terms.every(function (term) {
            if (text.indexOf(term) > -1) {
                return true;
            }
        })) {
            return data;
        } else {
            return null;
        }
    };

    let actions = {
        general            : function () {
            $(document).on('click', '.export_to_file', function (e) {
                e.preventDefault();
                var button = $(this);
                var target = button.data('target');
                window.location = xagio_data.wp_post + '?action=' + target;
            });

            let default_engine = $('#search_engine').attr('data-default');
            default_engine = default_engine.split(",");
            let default_country = $('#search_location').attr('data-default');


            if (default_engine != '') {
                $('#search_engine').val(default_engine).trigger('change');
            }

            if (default_country != '') {
                $('#search_location').val(default_country).trigger('change');
            }

            $('#search_location').select2({
                width: "100%",
                placeholder: "Select a Country"
            });

            $('#search_engine').select2({
                matcher: matcher,
                width: "100%",
                placeholder: "Select a Search Engine"
            });

            $(document).on('change', '#import_options', function (e) {
                e.preventDefault();
                clearTimeout(timeout);
                var form = $(this);

                var file_data = form.find("#import_options_file").prop("files")[0];
                var form_data = new FormData();
                form_data.append("import_options_file", file_data);

                $.ajax({
                           url        : xagio_data.wp_post + '?action=xagio_import_options',
                           dataType   : 'json',
                           cache      : false,
                           contentType: false,
                           processData: false,
                           data       : form_data,
                           type       : 'post',
                           statusCode : {
                               200: function (data) {
                                   xagioNotify(data.status, `${data.message} Refreshing page in 3 sec...`);
                                   timeout = setTimeout(function () {
                                       location.reload();
                                   }, 3000);
                               }
                           }
                       });

            });

        },
        wpEasySetup        : function () {

            /**
             *  Initiate TagsInput
             */
            themes = $('#themes').tagsInput({'interactive': false});
            plugins = $('#plugins').tagsInput({'interactive': false});

            /**
             *  Perform Fresh Start
             */
            $(document).on('click', '.perform-easy-setup', function (e) {
                e.preventDefault();
                let button = $(this);
                let form = $('form.fs');
                button.disable('Loading ...');
                let formDataArray = form.serializeArray();
                let labelsArray = [];

                $(formDataArray).each(function( index, data ) {
                    if (data.value === "1") {
                        let label = $('label[for="' + data.name + '"]').text().replace(/ - /g, "");
                        labelsArray.push(label.trim());
                    }
                    if(data.name === "fs_plugins") {
                        if(data.value !== "") {
                            labelsArray.push("Install plugins: " + data.value.replace(",", ", "));
                        }
                    }
                    if(data.name === "fs_themes") {
                        if(data.value !== "") {
                            labelsArray.push("Install themes: " + data.value.replace(",", ", "));
                        }
                    }
                    if(data.name === "fs_create_categories_list[]" || data.name === "fs_create_blank_pages_list[]" || data.name === "fs_create_blank_posts_list[]") {
                        if(data.value !== "") {
                            labelsArray.push("- " + data.value);
                        }
                    }
                });

                let message = `<div class="modal-message xagio-margin-bottom-medium">This action will do the following:</div>`;

                message += '<div class="modal-items">';
                labelsArray.forEach(function (data) {
                    message += `<p>${data}</p>`;
                });
                message += '</div>';

                if(labelsArray.length > 0) {
                    xagioModal("Are you sure?", message, function (yes) {
                        if (yes) {
                            $.post(xagio_data.wp_post, form.serializeArray(), function (d) {
                                if (d.status === "success") {
                                    $('.easy-setup-backup-notice').removeClass('xagio-hidden');
                                    $('.easy-setup-backup').html(`<a href="${d.backup}" target="_blank">${d.backup}</a>`);
                                    xagioNotify('success', "Operation completed.");
                                    button.disable();
                                }
                            });
                        } else {
                            button.disable();
                        }
                    })
                } else {
                    button.disable();
                    xagioNotify("danger", "Nothing selected!");
                    return false;
                }
            });

            $(document).on('submit', 'form.fs', function (e) {
                e.preventDefault();
            });

            /**
             *  Select result and put it into a tag
             */
            $(document).on('click', '.select-result', function () {
                var name = $(this).data('name');
                var type = $(this).data('type');
                if (!window[type].tagExist(name)) {
                    window[type].addTag(name);
                }
            });

            /**
             *  Key press events for Plugins/Themes search
             */

            $('#search_plugins,#search_themes').on('keypress', function (e) {
                e.stopPropagation();
                var element = this;
                var type = $(element).data('type');
                var results = $('#result_' + type);
                results.empty();
                clearTimeout(ajax_timeout);
                ajax_timeout = setTimeout(function () {
                    $(element).attr('disabled', 'disabled');
                    results.append(
                        '<div class="search-loading">Loading... <i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></div>'
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

                        results.empty();

                        $(element).removeAttr('disabled');
                        var data = d[type];
                        if (data.length < 1) {
                            results.append(
                                '<div class="search-no-results"><i class="xagio-icon xagio-icon-warning"></i> No results for search query <b>"' +
                                $(element).val() + '"</b>.</div>'
                            );
                            return 0;
                        }

                        let author_link = '';
                        for (var i = 0; i < data.length; i++) {
                            var row = data[i];
                            author_link = row.author;
                            if (type === 'themes') {
                                if (row.author.author_url == 'false') {
                                    row.author.author_url = '#';
                                }
                                author_link = `<a href="${row.author.author_url}" target="_blank">${row.author.display_name}</a>`
                            }

                            results.append(
                                '<div class="search-result">' +
                                '<p class="search-result-title">' + row.name + ' <small>by <b>' + author_link +
                                '</b></small></p>' +
                                '<p class="search-result-description">' +
                                (row.hasOwnProperty('short_description') ? row.short_description : row.description) +
                                '</p>' +
                                '<div class="search-result-actions">' +
                                '<button type="button" class="xagio-button xagio-button-primary xagio-button-padding-small select-result" data-type="' +
                                type + '" data-name="' + row.slug + '"><i class="xagio-icon xagio-icon-plus"></i> Add</button>' +
                                '' +
                                '' +
                                '</div>' +
                                '' +
                                '' +
                                '</div>'
                            );
                        }

                    });
                }, 600);
            });

            /**
             *  Create Categories
             */
            $(document).on('change', '#fs_create_categories', function (e) {
                e.preventDefault();
                $('.fs_create_categories_list').toggleClass('xagio-hidden');
            });
            $(document).on('click', '.uk-button-add-category', function (e) {
                e.preventDefault();
                $('<input name="fs_create_categories_list[]" type="text" placeholder="eg. Category Name" class="xagio-input-text-mini"/>').insertBefore($('.uk-button-add-category'));
            });
            $(document).on('click', '.uk-button-remove-category', function (e) {
                e.preventDefault();
                $('.fs_create_categories_list').find('input').last().remove();
            });

            /**
             *  Create Pages
             */
            $(document).on('change', '#fs_create_blank_pages', function (e) {
                e.preventDefault();
                $('.fs_create_blank_pages_list').toggleClass('xagio-hidden');
            });
            $(document).on('click', '.uk-button-add-pages', function (e) {
                e.preventDefault();
                $('<input name="fs_create_blank_pages_list[]" type="text" placeholder="eg. Page Name" class="xagio-input-text-mini"/>').insertBefore($('.uk-button-add-pages'));
            });
            $(document).on('click', '.uk-button-remove-pages', function (e) {
                e.preventDefault();
                $('.fs_create_blank_pages_list').find('input').last().remove();
            });

            /**
             *  Create Posts
             */
            $(document).on('change', '#fs_create_blank_posts', function (e) {
                e.preventDefault();
                $('.fs_create_blank_posts_list').toggleClass('xagio-hidden');
            });
            $(document).on('click', '.uk-button-add-post', function (e) {
                e.preventDefault();
                $('<input name="fs_create_blank_posts_list[]" type="text" placeholder="eg. Post Name" class="xagio-input-text-mini"/>').insertBefore($('.uk-button-add-post'));
            });
            $(document).on('click', '.uk-button-remove-post', function (e) {
                e.preventDefault();
                $('.fs_create_blank_posts_list').find('input').last().remove();
            });

        },
        toggleCaptchaFields: function () {
            $(document).on('change', '#XAGIO_RECAPTCHA', function (e) {
                $('.recaptcha-settings').toggleClass('xagio-hidden');
            });
        },

        locationKeywordSettingsSelect2: function () {
            let languageSelect = $('#xagioSettings-locationKeywordLanguage');
            let countrySelect = $('#xagioSettings-locationKeywordCountry');

            let saved_language = languageSelect.attr('data-default');

            if (saved_language !== '') {
                languageSelect.val(saved_language);
            }

            languageSelect.select2({
                placeholder: "Select Language",
                width: '100%'
            });

            let saved_country = countrySelect.attr('data-default');

            if (saved_country !== '') {
                countrySelect.val(saved_country);
            }

            countrySelect.select2({
                placeholder: "Select Country",
                width: '100%'
            });
        },

        saveKeywordSettingsLanguageOnChange: function () {
            $("#xagioSettings-locationKeywordLanguage").on('change', function() {
                let language = $(this).val();

                if (language !== '') {
                    $.post(xagio_data.wp_post, `action=xagio_set_default_keyword_language&language=${language}`, function (d) {
                        xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                    });
                }
            });
        },

        saveKeywordSettingsCountryOnChange: function () {
            $("#xagioSettings-locationKeywordCountry").on('change', function() {
                let country = $(this).val();

                if (country !== '') {
                    $.post(xagio_data.wp_post, `action=xagio_set_default_keyword_country&country=${country}`, function (d) {
                        xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                    });
                }
            });
        },

        saveRankTrackerCountryOnChange: function () {
            $("#search_location").on('change', function() {
                let country = $(this).val();

                if (country !== '') {
                    $.post(xagio_data.wp_post, `action=xagio_set_default_country&data=${country}`, function (d) {
                        xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                    });
                }
            });
        },

        saveSearchEngineOnChange: function () {
            $('#search_engine').on('change', function() {
                let select = $(this);

                let data = select.select2('data');

                if (data.length >= 1) {
                    let searchEngine = [];
                    for (let i = 0; i < data.length; i++) {
                        let id = data[i].id;
                        let text = data[i].text;
                        let sd = {
                            'id': id,
                            'text': text
                        }
                        searchEngine.push(sd);
                    }

                    let params = new FormData();
                    params.append('action', 'xagio_set_default_search_engine');

                    for (let i = 0; i < searchEngine.length; i++) {
                        const engine = searchEngine[i];
                        params.append(`data[${i}][id]`, engine.id);
                        params.append(`data[${i}][text]`, engine.text);
                    }

                    $.ajax({
                        url        : xagio_data.wp_post,
                        type       : 'POST',
                        data       : params,
                        processData: false, // Necessary for FormData
                        contentType: false, // Necessary for FormData
                        success    : function (d) {
                            xagioNotify(d.status, d.message);
                        }
                    });
                }
            })
        },

        auditSaveDefaultLocation: function () {
            $("#auditWebsite_default-location").on('change', function () {
                let val = $(this).val();
                let locationCode = $(this).find('option:selected').data("lang-code");

                $.post(xagio_data.wp_post, `action=xagio_set_default_audit_location&data=${val},${locationCode}`, function (d) {
                    xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                });
            })
        },

        setDefaultAuditLocation: function () {
            let auditLocationSelect = $("#auditWebsite_default-location");

            let data = auditLocationSelect.data('default');

            if (data) {
                let splitData = data.split(',');

                let value = splitData[0];
                let locationCode = splitData[1];

                $('#auditWebsite_default-location option').removeAttr('selected');
                $(`#auditWebsite_default-location option[value=${value}][data-lang-code=${locationCode}]`).attr('selected', true);
            }

            auditLocationSelect.select2({
                placeholder: "Select Location",
                width: '100%',
            });
        },

        aiWizardSaveDefaultSearchEngine: function () {
            $("#AiWizard_default-search-engine").on('change', function () {
                let val = $(this).val();

                $.post(xagio_data.wp_post, `action=xagio_set_default_ai_wizard_search_engine&value=${val}`, function (d) {
                    xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                });
            })
        },

        setDefaultAiWizardSearchEngine: function () {
            let engineSelect = $("#AiWizard_default-search-engine");
            let value = engineSelect.data('default');

            if (value) {
                $('#AiWizard_default-search-engine option').removeAttr('selected');
                $(`#AiWizard_default-search-engine option[value=${value}]`).attr('selected', true);
            }

            engineSelect.select2({
                matcher: matcher,
                width: "100%",
                placeholder: "Select a Search Engine"
            });
        },

        aiWizardSaveDefaultLocation: function () {
            $("#AiWizard_default-location").on('change', function () {
                let val = $(this).val();

                $.post(xagio_data.wp_post, `action=xagio_set_default_ai_wizard_location&value=${val}`, function (d) {
                    xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                });
            })
        },

        setDefaultAiWizardLocation: function () {
            let locationSelect = $("#AiWizard_default-location");
            let value = locationSelect.data('default');

            if(value) {
                $('#AiWizard_default-location option').removeAttr('selected');
                $(`#AiWizard_default-location option[value=${value}]`).attr('selected', true);
            }

            locationSelect.select2({
                placeholder: "Select Location",
                width: '100%'
            });
        },
    };


    $(document).ready(function () {
        actions.general();
        actions.wpEasySetup();
        actions.toggleCaptchaFields();
        actions.locationKeywordSettingsSelect2();
        actions.saveKeywordSettingsCountryOnChange();
        actions.saveKeywordSettingsLanguageOnChange();
        actions.saveRankTrackerCountryOnChange();
        actions.saveSearchEngineOnChange();
        actions.auditSaveDefaultLocation();
        actions.setDefaultAuditLocation();
        actions.aiWizardSaveDefaultSearchEngine();
        actions.setDefaultAiWizardSearchEngine();
        actions.aiWizardSaveDefaultLocation();
        actions.setDefaultAiWizardLocation();
    });


})(jQuery);
