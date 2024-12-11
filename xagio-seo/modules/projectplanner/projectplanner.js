let currentSiloGroups = [];
let siloInitialized = false;
let currentProjectID = 0;
let currentProjectName = 0;
let nextProjectID = 0;
let nextProjectName = 0;
let modal_block = '';
let moveToProject = false;
let activeChanges = false;
var groupNoticeTimeout = null;
let keywordGroupID = false;
let taxonomiesTable;
let taxonomiesTableCreate;
let postsTable;
let postsTable2;
let selectedPosts;
let selectedTaxonomies;
let pTypes;
let tTypes;
let batchCron;
let volCpcBatchCron;
let alertProjectID;
let pong = false;
let KWS_Origin = 'https://app.keywordsupremacy.com';
let aiStatusTimeout = null
let ai_keywords = [];
let isOriginalOrder = true;
var average_prices = null;

Array.prototype.remove = function (data) {
    const dataIdx = this.indexOf(data)
    if (dataIdx >= 0) {
        this.splice(dataIdx, 1);
    }
    return this.length;
}

window.onbeforeunload = function (e) {
    let message = "Are you sure you want to leave without saving your changes?";
    e = window.event;
    // For IE and Firefox
    if (activeChanges) {
        if (e) {
            e.returnValue = message;
        }

        // For Safari
        return message;
    }
};

let cf_templates = {
    Default  : {
        name: "Default",
        data: {
            volume_red  : 20,
            volume_green: 100,

            cpc_red  : 0.59,
            cpc_green: 1.00,

            intitle_red  : 1000,
            intitle_green: 250,

            inurl_red  : 1000,
            inurl_green: 250,

            title_ratio_red  : 1,
            title_ratio_green: 0.25,

            url_ratio_red  : 1,
            url_ratio_green: 0.25,

            tr_goldbar_volume : 1000,
            tr_goldbar_intitle: 20,

            ur_goldbar_volume : 1000,
            ur_goldbar_intitle: 20
        }
    },
    Affiliate: {
        name: "Affiliate",
        data: {
            volume_red  : 100,
            volume_green: 1000,

            cpc_red  : 1.00,
            cpc_green: 2.00,

            intitle_red  : 10000,
            intitle_green: 1000,

            inurl_red  : 10000,
            inurl_green: 1000,

            title_ratio_red  : 1,
            title_ratio_green: 0.25,

            url_ratio_red  : 1,
            url_ratio_green: 0.25,

            tr_goldbar_volume : 1000,
            tr_goldbar_intitle: 20,

            ur_goldbar_volume : 1000,
            ur_goldbar_intitle: 20
        }
    },
    Local    : {
        name: "Local",
        data: {
            volume_red  : 10,
            volume_green: 100,

            cpc_red  : 2.00,
            cpc_green: 5.00,

            intitle_red  : 1000,
            intitle_green: 100,

            inurl_red  : 1000,
            inurl_green: 100,

            title_ratio_red  : 1,
            title_ratio_green: 0.25,

            url_ratio_red  : 1,
            url_ratio_green: 0.25,

            tr_goldbar_volume : 1000,
            tr_goldbar_intitle: 20,

            ur_goldbar_volume : 1000,
            ur_goldbar_intitle: 20
        }
    }
};

let cf_default_template = 'Default';
let cf_template = cf_templates[cf_default_template].data;

(function ($) {
    'use strict';

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

    $(window).scroll(function () {
        if ($(this).scrollTop()) {
            $('#move-to-top').css('display', 'grid');
        } else {
            $('#move-to-top').hide();
        }
    });

    window.addEventListener("message", (event) => {
        if (event.origin == KWS_Origin) {

            if (event.data == 'pong') {

                pong = true;

            } else {

                try {
                    let data = btoa(encodeURI(event.data));
                    if (currentProjectID == 0) {
                        xagioNotify("danger", "Please load a project first and try again to import data from KeywordSupremacy!", 10);
                        return;
                    }
                    $.post(xagio_data.wp_post, 'action=xagio_import_kws&project_id=' + currentProjectID + '&data=' +
                                               data, function (d) {
                        actions.loadProjectManually();
                        xagioNotify("success", "Data from KeywordSupremacy has been successfully imported!");
                    });
                } catch (error) {

                }

            }
        }
    }, false);

    let $grid;

    $(document).ready(function () {

        // init Masonry
        $grid = $('.data').masonry({
            itemSelector: '.xagio-group',
            horizontalOrder: true,
            percentPosition: true,
            // fitWidth: true,
            gutter: 40
        });

        actions.allowances = {
            xags_allowance        : $('#xags-allowance'),
            xags                  : $('#xags'),
            cost                  : []
        };

        $(document).on('mouseenter', '[data-xagio-option-show]', function () {
            let hover = $(this).data('xagio-option-show');

            $('.csv-option-hover').hide();
            $(`#${hover}`).show();

        });

        actions.loadCfTemplates();
        actions.changeCfTemplate();
        actions.saveCfTemplate();
        actions.addCfTemplate();
        actions.applyCfTemplate();
        actions.deleteCfTemplate();
        actions.cfValidation();
        actions.newKeyword();
        actions.deleteKeywords();
        actions.deleteDuplicate();
        actions.createPagePost();
        actions.deleteGroup();
        actions.deleteGroups();
        actions.createPagePostMulti();
        actions.updateGroup();
        actions.newGroup();
        actions.modalEvents();
        actions.newProject();
        actions.removeProject();
        actions.renameProject();
        actions.loadProjects();
        actions.loadProject();
        actions.duplicateProject();
        actions.removeAlertProjectID();
        actions.backToProjects();
        actions.editGroupSettings();
        actions.initSliders();
        actions.selectAllKeywords();
        actions.retrieveVolumeAndCPC();
        actions.getAi();
        actions.retrieveKeywordData();
        actions.copyKeywords();
        actions.refreshXags();
        actions.exportProject();
        actions.importProject();
        actions.importKWS();
        actions.trackRankings();
        actions.submitKeywordsForRanking();
        actions.submitKeywordsForGetVolAndCPC();
        actions.refreshVolAndCpcValues();
        actions.refreshCompetitionValues();
        actions.selectKeyword();
        actions.minimizeGroup();
        actions.loadRedirects();
        actions.addNewRedirect();
        actions.deleteRedirect();
        actions.onURLEdit();
        actions.goToPagePost();

        actions.attachToPagePost();
        actions.dettachPagePost();
        actions.loadPostTypes();
        actions.changePostTypes();

        actions.attachToTaxonomy();
        actions.loadTaxonomyTypes();
        actions.goToTaxonomy();
        actions.changeTaxonomyTypes();

        actions.auditWebsite();
        actions.phraseMatch();
        actions.previewCluster();
        actions.seedKeyword();
        actions.moveToProject();
        actions.moveSelectedGroups();
        actions.selectAllGroups();

        actions.consolidateKeywords();

        actions.addGroupFromExisting();
        actions.addGroupFromExistingTaxonomy();

        actions.keywordInputKeypress();
        actions.selectAllPagePosts();
        actions.filterByPostType();
        actions.expandCollapseFunctions();
        actions.formatSEO();

        actions.wordCountCloud();
        actions.switchToSilo();
        actions.removeSilo();
        actions.saveSilo();

        actions.getSavedKeywordSettingsLanguageAndCountry();
        actions.volumeAndCpcOnChangeLanguage();
        actions.volumeAndCpcOnChangeCountry();
        actions.competitionChangeLanguage();
        actions.competitionChangeCountry();
        actions.setDefaultAuditLocation();
        actions.setDefaultAiWizardSearchEngine();
        actions.setDefaultAiWizardLocation();

        actions.openNotes();
        actions.saveGroupClick();
        actions.saveProject();
        actions.showShortcodes();


        actions.exportGroups();
        actions.exportKeywords();
        actions.exportAllProjects();
        actions.loadProjectIdFromURL();

        actions.aiwizard();
        actions.shareProject();

        actions.getXagioLinks();

        $.tablesorter.addParser({
                                    id    : "fancyNumber",
                                    is    : function (s) {
                                        // return false so this parser is not auto detected
                                        return false;
                                    },
                                    format: function (s) {
                                        return $.tablesorter.formatFloat(s.replace(/,/g, ''));
                                    },
                                    type  : "numeric"
                                });

        /*Get default settings*/
        $.post(xagio_data.wp_post, 'action=xagio_get_default_search_engine', function (d) {
            if (d.status == 'success') {
                for (let i = 0; i < d.data.length; i++) {
                    let id = d.data[i].id;
                    if ($('#search_engine').find("option[value='" + id + "']").length) {
                        $("#search_engine").find("option[value=" + id + "]").attr('selected', true);
                    }
                }
            }
        });
        $.post(xagio_data.wp_post, 'action=xagio_get_default_country', function (d) {
            if (d.status == 'success') {

                let id = d.data;
                if ($('#search_location').find("option[value='" + id + "']").length) {
                    $("#search_location").find("option[value=" + id + "]").attr('selected', true);
                }

            }
        });

        /* Get Alert Project ID*/
        $.post(xagio_data.wp_post, 'action=xagio_get_alert_project_id', function (d) {
            if (d.status == 'success') {
                alertProjectID = d.project_id;
            }
        });


        /*  AI Functions */
        actions.ai.init();

        $("#move-to-top").click(function () {
            $("html").animate({scrollTop: 0});
        });

        $(document).mouseup(function (e) {
            var container = $(".xagio-button-dropdown");
            if (!container.is(e.target) && container.has(e.target).length === 0 &&
                !$('.group-connect-group > button').is(e.target)) {
                container.prev('button.xagio-on').removeClass('xagio-on');
                container.hide();
            }
        });


        $(document).on('click', '.group-connect-group > button', function (e) {
            let btn = $(this);
            let dropdown = btn.next('.xagio-button-dropdown');

            if (btn.hasClass('xagio-on')) {
                btn.removeClass('xagio-on')
                dropdown.hide();
            } else {
                btn.addClass('xagio-on');
                dropdown.show();
            }
        });

    });


    let actions = {
        ai                  : {
            init                      : function () {
                actions.ai.helper.modalAccordion();
                actions.ai.helper.disableDefaultOnLableClick();
                actions.ai.openAiModal();
                actions.ai.openAiWizardModal();
                actions.ai.useSelectedSuggestionEvent();
                actions.ai.modifyAiSuggestion();
                actions.ai.viewSEOSuggestions();
            },
            openAiWizardModal         : function () {
                $(document).on('click', '.aiWizardBtn', function () {
                    let btn = $(this);
                    btn.disable();

                    if (!xagio_data.connected) {
                        xagioConnectModal();
                        return;
                    }

                    $('#aiwizard')[0].showModal();
                    btn.disable();

                });
            },
            openAiModal               : function () {
                $(document).on('click', '.optimize-ai', function (e) {
                    e.preventDefault();

                    if (!xagio_data.connected) {
                        xagioConnectModal();
                        return;
                    }

                    ai_keywords = [];

                    let btn = $(this);
                    let regenerate = btn.attr('data-regenerate');
                    let current_group = btn.parents('.xagio-group');
                    let group_id = current_group.find('input[name="group_id"]').val();


                    btn.parents('.xag-ai-tools-button').find('.xag-ai-tools i.xagio-icon.xagio-icon-robot').removeClass().addClass('xagio-icon xagio-icon-sync xagio-icon-spin');

                    let group_tr = current_group.find('.updateKeywords').find('.keywords').find('.keywords-data tr');

                    let keywords = [];

                    let all_competition_present = true;
                    group_tr.each(function () {


                        if ($(this).find('div.keywordInput[data-target="intitle"]').html() !== "" ||
                            $(this).find('div.keywordInput[data-target="inurl"]').html() !== "") {
                            ai_keywords.push([
                                                 $(this).find('div.keywordInput[data-target="keyword"]').text(),
                                                 parseFloat(actions.cleanComma($(this).find('div.keywordInput[data-target="volume"]').html())),
                                                 $(this).find('div.keywordInput[data-target="intitle"]').html() ??
                                                 parseFloat(actions.cleanComma($(this).find('div.keywordInput[data-target="intitle"]').html())),
                                                 $(this).find('div.keywordInput[data-target="inurl"]').html() ??
                                                 parseFloat(actions.cleanComma($(this).find('div.keywordInput[data-target="inurl"]').html()))
                                             ]);
                        } else {
                            all_competition_present = false;
                        }

                        let tmp = {
                            'keyword': {
                                'value': $(this).find('div.keywordInput[data-target="keyword"]').text(),
                            },
                            'volume' : {
                                'class': $(this).find('div.keywordInput[data-target="volume"]').parents('td').attr('class'),
                                'value': parseFloat(actions.cleanComma($(this).find('div.keywordInput[data-target="volume"]').html())),
                            },
                            'intitle': {
                                'class': $(this).find('div.keywordInput[data-target="intitle"]').parents('td').attr('class'),
                                'value': $(this).find('div.keywordInput[data-target="intitle"]').html() ??
                                         parseFloat(actions.cleanComma($(this).find('div.keywordInput[data-target="intitle"]').html())),
                            },
                            'inurl'  : {
                                'class': $(this).find('div.keywordInput[data-target="inurl"]').parents('td').attr('class'),
                                'value': $(this).find('div.keywordInput[data-target="inurl"]').html() ??
                                         parseFloat(actions.cleanComma($(this).find('div.keywordInput[data-target="inurl"]').html()))
                            }
                        };
                        if (typeof $(this).find('div.keywordInput[data-target="keyword"]').html() !== 'undefined') {
                            keywords.push(tmp);
                        }
                    });

                    if (keywords.length < 1) {
                        xagioNotify("danger", "Please make sure that group has at least one keyword.");
                        return false;
                    }

                    if (!all_competition_present) ai_keywords = [];

                    let aiSuggestionOptions = $('#aiSuggestionOptions');

                    let tr = '';

                    for (let i = 0; i < keywords.length; i++) {
                        let k = keywords[i];
                        let input = '';
                        if (ai_keywords.length > 0) {
                            input = `<input type="radio" id="keyword_${i}" name="suggestion_keyword" /> `;
                        }

                        tr += `<tr>`;
                        tr += `<td>${input}<label for="keyword_${i}">${k.keyword.value}</label></td>`;
                        tr += `<td class="text-center ${k.volume.class}">${k.volume.value}</td>`;
                        if (k.intitle.value !== '') {
                            tr += `<td class="text-center ${k.intitle.class}">${k.intitle.value}</td>`;
                        } else {
                            tr += `<td class="text-center"><i class="xagio-icon xagio-icon-warning uk-text-warning icon-warning" data-toggle="tooltip" title="Please fetch competition data for better results"></i></td>`;
                        }

                        if (k.inurl.value !== '') {
                            tr += `<td class="text-center ${k.inurl.class}">${k.inurl.value}</td>`;
                        } else {
                            tr += `<td class="text-center"><i class="xagio-icon xagio-icon-warning uk-text-warning icon-warning" data-toggle="tooltip" title="Please fetch competition data for better results"></i></td>`;
                        }


                        tr += `</tr>`;
                    }

                    aiSuggestionOptions.find('.ai-suggestion-keywords-table tbody').html(tr);

                    if (ai_keywords.length < 1) {
                        aiSuggestionOptions.find('.ai-optimization-alert').show();
                        aiSuggestionOptions.find('.ai-optimization-alert-message').html(`You are about to optimize without competition metrics in consideration.<br>This will provide a lower quality result.  Please fetch metrics first if you wish for optimal optimizations.`);
                    } else {
                        aiSuggestionOptions.find('.ai-optimization-alert').hide();

                    }


                    aiSuggestionOptions.find('.ai-suggestion-keywords-table').DataTable(
                        {
                            "dom"       : 't<"xagio-table-bottom"lp><"clear">',
                            "responsive": true,
                            "bDestroy"  : true,
                            "bAutoWidth": false,
                            "aaSorting" : [
                                [
                                    1,
                                    'desc'
                                ]
                            ],
                        });
                    aiSuggestionOptions.find('.aiSuggestionNext').attr('data-group', group_id).attr('data-regenerate', regenerate);

                    setTimeout(function () {
                        aiSuggestionOptions[0].showModal();
                    }, 500);

                });

                $(document).on('change', '#prompt_id', function (e) {
                    e.preventDefault();

                    let selected_value = $(this).val();
                    let selected_prompt = null;

                    let input = $('#aiPrice').attr('data-target');

                    for (let i = 0; i < average_prices[input].length; i++) {
                        const pagecontentElement = average_prices[input][i];
                        if (pagecontentElement.id == selected_value) {
                            selected_prompt = pagecontentElement;
                            break;
                        }
                    }

                    $('#aiPrice').find('.average-price').html(parseFloat(selected_prompt.price.toFixed(3)));
                });

                $(document).on('click', '.aiSuggestionNext ', function () {
                    let btn = $(this);
                    btn.disable();

                    $.post(xagio_data.wp_post, `action=xagio_ai_get_average_prices`, function (d) {
                        btn.disable();

                        if (d.status == 'error') {
                            xagioNotify('danger', 'There was a problem establishing connection, please contact Support.');
                            return;
                        }

                        let defaultPrompt = null;
                        let aiPriceModal = $('#aiPrice');
                        let prompt_id = aiPriceModal.find('#prompt_id');
                        prompt_id.empty();

                        if (average_prices == null) {
                            average_prices = d.data.average_prices;
                        }

                        let input = 'SEO_SUGGESTIONS';
                        if (ai_keywords.length > 0) {
                            input = 'SEO_SUGGESTIONS_MAIN_KW';
                        }

                        for (let i = 0; i < average_prices[input].length; i++) {
                            const pagecontentElement = average_prices[input][i];
                            prompt_id.append(`<option value="${pagecontentElement.id}">${pagecontentElement.title}</option>`)
                            if (pagecontentElement.default) {
                                defaultPrompt = pagecontentElement;
                                prompt_id.val(pagecontentElement.id);
                            }
                        }

                        let settings_modal = btn.parents('#aiSuggestionOptions');
                        let main_keyword = settings_modal.find('#ai-suggestion-main-keyword').val();

                        let regenerate = btn.attr('data-regenerate');
                        let group_id = btn.attr('data-group');

                        aiPriceModal.find('.input-name').html('AI SEO Optimization');
                        aiPriceModal.find('#suggestion-main-keyword').val($.trim(main_keyword));

                        aiPriceModal.find('.average-price').html(parseFloat(defaultPrompt.price.toFixed(3)));
                        aiPriceModal.find('.ai-credits').html(parseFloat(d.data.credits.toFixed(3)));

                        aiPriceModal.find('.makeAiRequest').attr('data-group', group_id).attr('data-regenerate', regenerate);

                        aiPriceModal.attr('data-target', input);

                        settings_modal[0].close();
                        aiPriceModal[0].showModal();
                    });

                });


                $(document).on('change', 'input[name="suggestion_keyword"]', function () {
                    $('#ai-suggestion-main-keyword').val($(this).next('label').html());
                    $('.ai-main-keyword-holder').slideDown();
                });


                $('#aiSuggestionOptions')[0].addEventListener("close", (event) => {
                    let modal = $(event.target);
                    modal.find('.ai-suggestion-keywords-table tbody').empty();
                    modal.find('#ai-suggestion-main-keyword').val('');
                    modal.find('.ai-main-keyword-holder').hide();
                    modal.find('.ai-suggestion-keywords-table').DataTable().destroy();
                });

                $('#ai-suggest-modal').on('close', function () {
                    let labels = $('.ai-block .ai-content ul li label');
                    $('.mini-table').empty();

                    labels.each(function (index) {
                        let current_label = $(this);
                        current_label.unhighlight();
                    });

                });

                $(document).on('click', '.table_hightligh_also', function (e) {
                    let labels = $('.ai-block .ai-content ul li label');
                    let word_el = $(this).find('.ai-cluster-word');
                    let word = word_el.text();


                    if (word_el.hasClass('highlightCloud')) {
                        word_el.removeClass('highlightCloud');

                        labels.each(function (index) {
                            let current_label = $(this);
                            let label_matches = current_label.html().match(new RegExp(`\\b(${word})\\b`, 'gi'));
                            if (label_matches !== null) {
                                for (let j = 0; j < label_matches.length; j++) {
                                    const labelMatch = label_matches[j];
                                    const labelReg = new RegExp(`<b class="highlightCloud">(${labelMatch})</b>`, "g");
                                    let label_replace = current_label.html().replace(labelReg, labelMatch);
                                    current_label.html(label_replace);
                                }
                            }
                            current_label.html(current_label.html().replace(new RegExp(`<b class="highlightCloud">(${word})<\\/b>`, 'gi'), word));
                        });

                    } else {
                        word_el.addClass('highlightCloud');

                        labels.each(function (index) {
                            let current_label = $(this);
                            let label_matches = current_label.html().match(new RegExp(`\\b(${word})\\b`, 'gi'));
                            if (label_matches !== null) {
                                for (let j = 0; j < label_matches.length; j++) {
                                    const labelMatch = label_matches[j];
                                    const labelReg = new RegExp(`\\b(${labelMatch})\\b`, "g");
                                    let label_replace = current_label.html().replace(labelReg, '<b class="highlightCloud">' +
                                                                                               labelMatch + '</b>');
                                    current_label.html(label_replace);
                                }
                            }
                        });
                    }
                });

                $(document).on('click', '.word-highlight', function (e) {
                    let labels = $('.ai-block .ai-content ul li label');
                    let word_el = $(this).find('.ai-cluster-word');
                    let word = word_el.text();


                    if (word_el.hasClass('highlightCloud')) {
                        word_el.removeClass('highlightCloud');

                        labels.each(function (index) {
                            let current_label = $(this);
                            let label_matches = current_label.html().match(new RegExp(`\\b(${word})\\b`, 'gi'));
                            if (label_matches !== null) {
                                for (let j = 0; j < label_matches.length; j++) {
                                    const labelMatch = label_matches[j];
                                    const labelReg = new RegExp(`<b class="highlightCloud">(${labelMatch})</b>`, "g");
                                    let label_replace = current_label.html().replace(labelReg, labelMatch);
                                    current_label.html(label_replace);
                                }
                            }
                            current_label.html(current_label.html().replace(new RegExp(`<b class="highlightCloud">(${word})<\\/b>`, 'gi'), word));
                        });

                    } else {
                        word_el.addClass('highlightCloud');

                        labels.each(function (index) {
                            let current_label = $(this);
                            let label_matches = current_label.html().match(new RegExp(`\\b(${word})\\b`, 'gi'));
                            if (label_matches !== null) {
                                for (let j = 0; j < label_matches.length; j++) {
                                    const labelMatch = label_matches[j];
                                    const labelReg = new RegExp(`\\b(${labelMatch})\\b`, "g");
                                    let label_replace = current_label.html().replace(labelReg, '<b class="highlightCloud">' +
                                                                                               labelMatch + '</b>');
                                    current_label.html(label_replace);
                                }
                            }
                        });
                    }

                });


                $(document).on('click', '.makeAiRequest', function (e) {
                    let btn = $(this);
                    let group_id = btn.attr('data-group');
                    let regenerate = btn.attr('data-regenerate');
                    let current_group = $(`input[name="group_id"][value="${group_id}"]`).parents('.xagio-group');
                    let modal = btn.parents('#aiPrice');
                    let main_keyword = modal.find('#suggestion-main-keyword').val();
                    let group_tr = current_group.find('.updateKeywords').find('.keywords').find('.keywords-data tr');

                    btn.disable();
                    let tbody_keywords = current_group.find('.updateKeywords').find('.keywords').find('.keywords-data tr').find('div.keywordInput[data-target="keyword"]');

                    let table_keywords = [];
                    group_tr.each(function () {
                        let tmp = {
                            'keyword': {
                                'value': $(this).find('div.keywordInput[data-target="keyword"]').text(),
                            },
                            'volume' : {
                                'class': $(this).find('div.keywordInput[data-target="volume"]').parents('td').attr('class'),
                                'value': parseFloat(actions.cleanComma($(this).find('div.keywordInput[data-target="volume"]').html())),
                            },
                            'intitle': {
                                'class': $(this).find('div.keywordInput[data-target="intitle"]').parents('td').attr('class'),
                                'value': $(this).find('div.keywordInput[data-target="intitle"]').html() ??
                                         parseFloat(actions.cleanComma($(this).find('div.keywordInput[data-target="intitle"]').html())),
                            },
                            'inurl'  : {
                                'class': $(this).find('div.keywordInput[data-target="inurl"]').parents('td').attr('class'),
                                'value': $(this).find('div.keywordInput[data-target="inurl"]').html() ??
                                         parseFloat(actions.cleanComma($(this).find('div.keywordInput[data-target="inurl"]').html()))
                            }
                        };
                        if (typeof $(this).find('div.keywordInput[data-target="keyword"]').html() !== 'undefined') {
                            table_keywords.push(tmp);
                        }
                    });

                    let keywords = [];
                    tbody_keywords.each(function () {
                        keywords.push($(this).text());
                    });

                    //send only top 50 keywords
                    keywords.splice(50);

                    let words_table = actions.ai.helper.calculateWordWeight(keywords);

                    let mini_table = '<div class="xagio-alert xagio-alert-primary xagio-margin-top-medium xagio-margin-bottom-medium"><i class="xagio-icon xagio-icon-info"></i> ' +
                                     'In table below you can click on any keyword to highlight entire keyword used by AI suggestions ' +
                                     'to help you visually see optimization results</div>';
                    mini_table += '<table class="uk-table ai-keyword-cloud-table">';
                    mini_table += '<thead>';
                    mini_table += '<tr>';
                    mini_table += '<td width="55%">Keyword</td>' +
                                  '<td class="text-center" width="15%">Volume</td>' +
                                  '<td class="text-center" width="15%">inTitle</td>' +
                                  '<td class="text-center" width="15%">inURL</td>';
                    mini_table += '</tr>';
                    mini_table += '</thead>';
                    mini_table += '<tbody>';
                    for (let i = 0; i < table_keywords.length; i++) {
                        let k = table_keywords[i];
                        mini_table += '<tr>';
                        mini_table += `<td class="table_hightligh_also"><span class="ai-cluster-word">${k.keyword.value}</span></td>`;
                        mini_table += `<td class="text-center ${k.volume.class}">${k.volume.value}</td>`;
                        mini_table += `<td class="text-center ${k.intitle.class}">${k.intitle.value}</td>`;
                        mini_table += `<td class="text-center ${k.inurl.class}">${k.inurl.value}</td>`;
                        mini_table += '</tr>';
                    }
                    mini_table += '</tbody>';
                    mini_table += '</table>';

                    mini_table += '<div class="xagio-alert xagio-alert-primary xagio-margin-top-medium xagio-margin-bottom-medium"><i class="xagio-icon xagio-icon-info"></i> Below you can see separated keywords by words and their weights (<b>word (weight)</b>). You can click on any word to highlight words used by AI suggestions ' +
                                  'to help you visually see optimization results</div>';

                    mini_table += '<div class="ai-keyword-cloud">';
                    for (let i = 0; i < words_table.length; i++) {
                        let word = words_table[i];
                        mini_table += '<div class="word-highlight">';
                        mini_table += `<span class="ai-cluster-word">${word.text}</span> <span>(${word.weight})</span>`;
                        mini_table += '</div>';
                    }
                    mini_table += '</div>';
                    let aiModal = $('#ai-suggest-modal');

                    let input = 'SEO_SUGGESTIONS';
                    let target_id = group_id;
                    let prompt_id = $("#prompt_id").val();

                    if (ai_keywords.length > 0) {
                        input = 'SEO_SUGGESTIONS_MAIN_KW';
                        words_table = actions.ai.helper.generateAiKeywordCluster(ai_keywords);
                    } else {
                        words_table = JSON.stringify(words_table);
                    }

                    aiModal.find('.mini-table').html(mini_table);
                    aiModal.find('.ai-keyword-cloud-table').DataTable(
                        {
                            "dom"       : 't<"xagio-table-bottom"lp><"clear">',
                            "responsive": true,
                            "bDestroy"  : true,
                            "bAutoWidth": false,
                            "aaSorting" : [
                                [
                                    1,
                                    'desc'
                                ]
                            ],
                        });
                    aiModal.find('.use-ai-suggested').attr('data-group-id', group_id);
                    aiModal.find('.use-ai-suggested').attr('data-ai-input', input);
                    aiModal.find('.ai-block').addClass('grad');


                    modal[0].close();
                    aiModal[0].showModal();


                    btn.disable();
                    let r = "";
                    if (regenerate === 'yes') {
                        r = "&regenerate=yes";
                        $.post(xagio_data.wp_post, `action=xagio_ai_suggest&prompt_id=${prompt_id}&keyword_group=${words_table}&group_id=${target_id}&main_keyword=${main_keyword}&input=${input}${r}`, (d) => {

                            if (d.status == 'upgrade') {
                                // show aiUpgrade modal
                                $('#aiUpgrade')[0].showModal();
                                return;
                            }

                            if (d.status === 'success') {
                                actions.ai.checkAiStatus(input, target_id, aiModal, words_table);
                            }
                            xagioNotify(d.status, d.message);
                        });
                        return false;
                    }

                    $.post(xagio_data.wp_post, `action=xagio_ai_output&input=${input}&target_id=${target_id}`, (d) => {
                        let status = d.status;
                        if (status === 'running') {
                            setTimeout(function () {
                                aiModal.find('.ai-alert-info').slideDown();
                            }, 12000);
                            actions.ai.checkAiStatus(input, target_id, aiModal, words_table);
                        } else if (status === 'completed') {
                            clearTimeout(aiStatusTimeout);
                            let suggestions = d.data;
                            actions.ai.helper.displaySeoSuggestionsInModal(aiModal, suggestions, d.id);
                        } else {
                            // If status is none, send request for AI
                            $.post(xagio_data.wp_post, `action=xagio_ai_suggest&prompt_id=${prompt_id}&keyword_group=${JSON.stringify(words_table)}&group_id=${target_id}&main_keyword=${main_keyword}&input=${input}`, (d) => {
                                if (d.status === 'success') {
                                    actions.ai.checkAiStatus(input, target_id, aiModal, words_table);
                                }
                                xagioNotify(d.status, d.message);
                            });
                        }
                    });
                });
            },
            viewSEOSuggestions        : function () {
                $(document).on('click', '.view-ai-suggestions', function (e) {
                    e.preventDefault();
                    let btn = $(this);
                    let current_group = btn.parents('.xagio-group');
                    let group_id = current_group.find('input[name="group_id"]').val();
                    let ai_input = btn.data('ai-input');
                    let group_tr = current_group.find('.updateKeywords').find('.keywords').find('.keywords-data tr');

                    let tbody_keywords = current_group.find('.updateKeywords').find('.keywords').find('.keywords-data tr').find('div.keywordInput[data-target="keyword"]');

                    let keywords = [];
                    tbody_keywords.each(function () {
                        keywords.push($(this).text());
                    });

                    let table_keywords = [];
                    group_tr.each(function () {
                        let tmp = {
                            'keyword': {
                                'value': $(this).find('div.keywordInput[data-target="keyword"]').text(),
                            },
                            'volume' : {
                                'class': $(this).find('div.keywordInput[data-target="volume"]').parents('td').attr('class'),
                                'value': parseFloat(actions.cleanComma($(this).find('div.keywordInput[data-target="volume"]').html())),
                            },
                            'intitle': {
                                'class': $(this).find('div.keywordInput[data-target="intitle"]').parents('td').attr('class'),
                                'value': $(this).find('div.keywordInput[data-target="intitle"]').html() ??
                                         parseFloat(actions.cleanComma($(this).find('div.keywordInput[data-target="intitle"]').html())),
                            },
                            'inurl'  : {
                                'class': $(this).find('div.keywordInput[data-target="inurl"]').parents('td').attr('class'),
                                'value': $(this).find('div.keywordInput[data-target="inurl"]').html() ??
                                         parseFloat(actions.cleanComma($(this).find('div.keywordInput[data-target="inurl"]').html()))
                            }
                        };
                        if (typeof $(this).find('div.keywordInput[data-target="keyword"]').html() !== 'undefined') {
                            table_keywords.push(tmp);
                        }
                    });

                    let words_table = actions.ai.helper.calculateWordWeight(keywords);

                    let mini_table = '<div class="xagio-alert xagio-alert-primary xagio-margin-top-medium xagio-margin-bottom-medium"><i class="xagio-icon xagio-icon-info"></i> ' +
                                     'In table below you can click on any keyword to highlight entire keyword used by AI suggestions ' +
                                     'to help you visually see optimization results</div>';
                    mini_table += '<table class="uk-table ai-keyword-cloud-table">';
                    mini_table += '<thead>';
                    mini_table += '<tr>';
                    mini_table += '<td width="55%">Keyword</td>' +
                                  '<td class="text-center" width="15%">Volume</td>' +
                                  '<td class="text-center" width="15%">inTitle</td>' +
                                  '<td class="text-center" width="15%">inURL</td>';
                    mini_table += '</tr>';
                    mini_table += '</thead>';
                    mini_table += '<tbody>';
                    for (let i = 0; i < table_keywords.length; i++) {
                        let k = table_keywords[i];
                        mini_table += '<tr>';
                        mini_table += `<td class="table_hightligh_also"><span class="ai-cluster-word">${k.keyword.value}</span></td>`;
                        mini_table += `<td class="text-center ${k.volume.class}">${k.volume.value}</td>`;
                        mini_table += `<td class="text-center ${k.intitle.class}">${k.intitle.value}</td>`;
                        mini_table += `<td class="text-center ${k.inurl.class}">${k.inurl.value}</td>`;
                        mini_table += '</tr>';
                    }
                    mini_table += '</tbody>';
                    mini_table += '</table>';

                    mini_table += '<div class="xagio-alert xagio-alert-primary xagio-margin-top-medium xagio-margin-bottom-medium"><i class="xagio-icon xagio-icon-info"></i> Below you can see separated keywords by words and their weights (<b>word (weight)</b>). You can click on any word to highlight words used by AI suggestions ' +
                                  'to help you visually see optimization results</div>';

                    mini_table += '<div class="ai-keyword-cloud">'
                    for (let i = 0; i < words_table.length; i++) {
                        let word = words_table[i];
                        mini_table += '<div class="word-highlight">';
                        mini_table += `<span class="ai-cluster-word">${word.text}</span> <span>(${word.weight})</span>`
                        mini_table += '</div>';
                    }

                    mini_table += '</div>';
                    let aiModal = $('#ai-suggest-modal');
                    aiModal.find('.mini-table').html(mini_table);
                    aiModal.find('.ai-keyword-cloud-table').DataTable(
                        {
                            "dom"       : 't<"xagio-table-bottom"lp><"clear">',
                            "responsive": true,
                            "bDestroy"  : true,
                            "bAutoWidth": false,
                            "aaSorting" : [
                                [
                                    1,
                                    'desc'
                                ]
                            ],
                        });
                    aiModal.find('.use-ai-suggested').attr('data-group-id', group_id);
                    aiModal.find('.use-ai-suggested').attr('data-ai-input', ai_input);
                    aiModal.find('.ai-block').addClass('grad');
                    aiModal[0].showModal();

                    $.post(xagio_data.wp_post, `action=xagio_ai_output&input=${ai_input}&target_id=${group_id}`, (d) => {
                        let status = d.status;
                        if (status === 'completed') {
                            clearTimeout(aiStatusTimeout);
                            let suggestions = d.data;
                            actions.ai.helper.displaySeoSuggestionsInModal(aiModal, suggestions, d.id);
                        }
                    });
                });
            },
            checkAiStatus             : function (input, target_id, aiModal, words_table) {
                let template = $(`input[name="group_id"][value="${target_id}"]`).parents('.xagio-group');

                aiStatusTimeout = setTimeout(function () {
                    $.post(xagio_data.wp_post, `action=xagio_ai_output&input=${input}&target_id=${target_id}`, (d) => {
                        let status = d.status;
                        if (status === 'running') {
                            actions.ai.checkAiStatus(input, target_id, aiModal, words_table);
                            template.find('.xag-ai-tools-button').attr('title', 'Getting AI Suggestions');
                            template.find('.xag-ai-tools i.xagio-icon.xagio-icon-robot').removeClass().addClass('xagio-icon xagio-icon-sync xagio-icon-spin');
                            template.find('.optimize-ai i').removeClass().addClass('xagio-icon xagio-icon-sync xagio-icon-spin');
                        } else if (status === 'completed') {
                            clearTimeout(aiStatusTimeout);
                            let suggestions = d.data;
                            actions.ai.helper.displaySeoSuggestionsInModal(aiModal, suggestions, d.id);

                            template.find('.xag-ai-tools-button').attr('title', 'AI Suggestions Ready');
                            template.find('.xag-ai-tools').addClass('xag-ai-complete').html(`<i class="xagio-icon xagio-icon-ai"></i> <i class="xagio-icon xagio-icon-check"></i>`);
                            template.find('.optimize-ai').attr('data-regenerate', 'yes').html(`<i class="xagio-icon xagio-icon-brain"></i> Regenerate AI Suggestions`);
                            template.find('.createPostPageAi').show();
                            template.find('.view-ai-suggestions').attr('data-ai-input', input);
                            template.find('.view-ai-li').show();

                        } else {
                            $.post(xagio_data.wp_post, `action=xagio_ai_suggest&keyword_group=${JSON.stringify(words_table)}&group_id=${target_id}`, (d) => {
                                actions.ai.checkAiStatus(input, target_id, aiModal, words_table);
                                xagioNotify(d.status, d.message);
                            });
                        }
                    });
                }, 4000);
            },
            useSelectedSuggestionEvent: function () {
                $(document).on('click', '.use-ai-suggested', function () {
                    let btn = $(this);
                    let aiModal = $('#ai-suggest-modal');
                    let group_id = btn.attr('data-group-id');
                    let group = $(`input[name='group_id'][value='${group_id}']`).parents('.xagio-group');

                    let header = btn.parents('#ai-suggest-modal').find('.ai-block.ai-headers .select-suggestion:checked').next().text().trim();
                    let title = btn.parents('#ai-suggest-modal').find('.ai-block.ai-titles .select-suggestion:checked').next().text().trim();
                    let desc = btn.parents('#ai-suggest-modal').find('.ai-block.ai-desc .select-suggestion:checked').next().text().trim();

                    if (aiModal.find('#include_h1').val() === '1') {
                        group.find('.updateGroup input[name="h1"]').val(header);
                        group.find('.updateGroup div.prs-h1tag').html(header);
                    }

                    if (aiModal.find('#include_titles').val() === '1') {
                        group.find('.updateGroup input[name="title"]').val(title);
                        group.find('.updateGroup .prs-editor.prs-title').html(title).trigger('input');
                    }

                    if (aiModal.find('#include_desc').val() === '1') {
                        group.find('.updateGroup input[name="description"]').val(desc);
                        group.find('.updateGroup .prs-editor.prs-description').html(desc).trigger('input');
                    }

                    aiModal[0].close();

                    group.addClass('uk-animation-shake');
                    setTimeout(() => {
                        group.removeClass('uk-animation-shake');
                    }, 500);

                    group.find('.updateGroup').submit();

                });
            },
            modifyAiSuggestion        : function () {
                $(document).on('click', '.modify-suggestion', function () {
                    let btn = $(this);
                    let modal = btn.parents('#ai-suggest-modal');
                    let group_id = modal.find('.use-ai-suggested').attr('data-group-id');
                    let ai_input = modal.find('.use-ai-suggested').attr('data-ai-input');
                    let newText = btn.prev().text();
                    let type = btn.data('index');
                    let data_id = btn.data('id');
                    btn.disable();

                    $.post(xagio_data.wp_post, `action=xagio_modify_suggestion&group_id=${group_id}&ai_input=${ai_input}&type=${type}&row_id=${data_id}&text=${encodeURIComponent(newText)}`, function (d) {
                        btn.disable();
                        xagioNotify(d.status, d.message);
                    });

                });
            },
            helper                    : {
                disableDefaultOnLableClick   : function () {
                    $(document).on('click', '.ai-block label', function (e) {
                        e.preventDefault();
                        $(this).prev().prop('checked', true);
                    });
                },
                displaySeoSuggestionsInModal : function (aiModal, suggestions, id) {
                    let headers = [];
                    let titles = [];
                    let descriptions = [];

                    if (suggestions == null) {
                        aiModal.find('.ai-headers .ai-content').html(`<div class="failed-suggestions"><i class="xagio-icon xagio-icon-info"></i> Failed to retrieve H1 Suggestions, please try again</div>`);
                        aiModal.find('.ai-titles .ai-content').html(`<div class="failed-suggestions"><i class="xagio-icon xagio-icon-info"></i> Failed to retrieve Title Suggestions, please try again</div>`);
                        aiModal.find('.ai-desc .ai-content').html(`<div class="failed-suggestions"><i class="xagio-icon xagio-icon-info"></i> Failed to retrieve Description Suggestions, please try again</div>`);

                    } else {
                        for (let i = 0; i < suggestions.length; i++) {
                            let item = suggestions[i];
                            headers.push(item['h1']);
                            titles.push(item['title']);
                            descriptions.push(item['description']);
                        }

                        aiModal.find('.ai-headers .ai-content').html(actions.ai.helper.generateAISuggestionULElement(headers, 'header', id));
                        aiModal.find('.ai-titles .ai-content').html(actions.ai.helper.generateAISuggestionULElement(titles, 'title', id));
                        aiModal.find('.ai-desc .ai-content').html(actions.ai.helper.generateAISuggestionULElement(descriptions, 'desc', id));
                    }


                    aiModal.find('.ai-block').removeClass('grad');
                },
                generateAISuggestionULElement: function (data, type, id) {
                    // type = header, title, desc
                    let ul = '<ul>';
                    for (let i = 0; i < data.length; i++) {
                        let item = data[i].trim();
                        item = item.replace(/^[0-9]\.\s?/, '');
                        item = item.replace(/^"|"$/g, '');
                        let checked = '';
                        if (i === 0) checked = 'checked';
                        ul += `<li>
                                <input type="radio" class="select-suggestion" id="${type}${i}" name="${type}" ${checked}>
                                <label for="${type}${i}" contenteditable="true">${item}</label>
                                <button class="xagio-button xagio-button-primary xagio-button-mini modify-suggestion" data-index="${type}-${i}" data-id="${id}"><i class="xagio-icon xagio-icon-save"></i></button>
                               </li>`;
                    }
                    ul += '</ul>';
                    return ul;
                },
                generateAiKeywordCluster     : function (keywords) {
                    let table = '';

                    for (let i = 0; i < keywords.length; i++) {
                        let row = keywords[i];

                        table += `${row[0]}, ${row[1]}, ${row[2]}, ${row[3]} \n`;
                    }

                    return table;
                },
                calculateWordWeight          : function (keywords) {
                    let words_split = [];
                    for (let i = 0; i < keywords.length; i++) {
                        words_split.push(keywords[i].split(' '));
                    }
                    words_split = [].concat.apply([], words_split);
                    let words = [];

                    for (let i = 0; i < words_split.length; i++) {
                        if (words_split[i].length < 2) continue;
                        if (words_split[i] === "&amp;") continue;
                        let check = 0;
                        let final = {
                            text  : '',
                            weight: 0
                        };
                        for (let j = 0; j < words.length; j++) {
                            if (words_split[i] == words[j].text) {
                                check = 1;
                                ++words[j].weight;
                            }
                        }
                        if (check == 0) {
                            final.text = words_split[i];
                            final.weight = 1;
                            words.push(final);
                        }
                        check = 0;
                    }

                    words.sort(function (a, b) {
                        let a1 = a.weight,
                            b1 = b.weight;
                        if (a1 == b1) return 0;
                        return a1 < b1 ? 1 : -1;
                    });

                    return words;
                },
                modalAccordion               : function () {
                    $(document).on('click', '.ai-accordion-title', function (e) {
                        if ($(this).hasClass('open')) {
                            $(this).removeClass('open');
                            $(this).find('i').removeClass().addClass('xagio-icon xagio-icon-arrow-up');
                            $('.mini-table').slideUp();
                        } else {
                            $(this).addClass('open');
                            $(this).find('i').removeClass().addClass('xagio-icon xagio-icon-arrow-down');
                            $('.mini-table').slideDown();
                        }
                    });
                }
            }
        },
        allowances          : null,
        getXagioLinks       : function () {
            $.post(xagio_data.wp_post, 'action=xagio_get_links', function (d) {
                if(d !== false) {
                    let projectplanner_btn = $('.xagio-button-dashboard-link');
                    projectplanner_btn.html(`<i class="xagio-icon xagio-icon-store"></i> ${d.projectplanner.text}`);
                    projectplanner_btn.attr('href', d.projectplanner.url);
                }
            });
        },
        shareProject        : function () {
            $('#confirmShareModal')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                let project_id = modal.find('#shareProjectId').val();
                let sliderStatus = modal.find('#sliderStatus').val();
                let checkbox = $('.share_btn_cell').find(`input[data-id="${project_id}"]`);
                checkbox.prop("checked", !parseInt(sliderStatus));

                modal.find('#shareProjectId').val(0);
                modal.find('#sliderStatus').val(0);
                modal.find('.link-share-message').val('');
            });

            $(document).on('click', '.add-share-link', function () {
                let btn = $(this);
                let modal_show = $('#shared_project_link');
                let modal = btn.parents('.xagio-modal');
                let project_id = modal.find('#shareProjectId').val();
                let isActive = modal.find('#sliderStatus').val();
                let checkbox = $('.share_btn_cell').find(`input[data-id="${project_id}"]`);

                modal[0].close();


                if (project_id == 0) {
                    xagioNotify("danger", "Please open any project to share");
                    return false;
                }

                btn.disable();
                isActive = parseInt(isActive);

                $.post(xagio_data.wp_post, `action=xagio_share_project&project_id=${project_id}&share=${isActive}`, function (d) {
                    let shared_url = d.data;
                    btn.disable();
                    if (isActive) {
                        checkbox.prop("checked", true);

                        modal_show.find('.share-modal-link a').attr('href', shared_url).html(shared_url);
                        modal_show.find('.view-shared-url').attr('href', shared_url);

                        modal_show[0].showModal();

                    } else {
                        checkbox.prop("checked", false);
                    }

                    actions.loadProjects();

                    xagioNotify("success", d.message);
                });

            });

            $(document).on('click', '.copy-shared-url', function () {
                let btn = $(this);
                let modal = btn.parents('.xagio-modal');
                let link = modal.find('.share-modal-link a').attr('href');
                copyTextToClipboard(link);

                modal[0].close();
                xagioNotify("success", "Link Copied");
            });

            $(document).on('click', '.on-off-share', function (e) {
                let checkbox = $(this);
                let modal = $('#confirmShareModal');
                let project_id = checkbox.data('id');
                let checked = checkbox.prop('checked');

                let isActive;
                let msg = '';
                if (checked === true) {
                    isActive = 1;
                    msg = "This action will generate unique sharable link for this project";
                } else {
                    isActive = 0;
                    msg = "This action will remove sharing for this project. You can always share it again later";
                }

                modal.find('#shareProjectId').val(project_id);
                modal.find('#sliderStatus').val(isActive);
                modal.find('.link-share-message').html(msg);
                modal[0].showModal();

            });

            $(document).on('click', '.shared_project_link', function (e) {
                let btn = $(this);
                let shared_url = btn.attr('data-shared-url');

                let modal = $('#shared_project_link');

                modal.find('.share-modal-link a').attr('href', shared_url).html(shared_url);
                modal.find('.view-shared-url').attr('href', shared_url);

                modal[0].showModal();
            });
        },
        loadProjectIdFromURL: function () {
            if (actions.getUrlParameters('pid')) {
                currentProjectID = actions.getUrlParameters('pid');
                if (currentProjectID != 0)
                    actions.loadProjectManually();
            }
        },
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
        parseUrl            : function (url) {
            let a = $('<a>', {
                href: url
            });
            return a;
        },
        aiwizard            : function () {

            $(document).on('change', '#top-ten-language-select', function (e) {
                e.preventDefault();
                $('#top-ten-language').val($(this).find('option:selected').attr('data-lang-code'));
            });

            let project_ids = [];
            let domains_length = 0;

            function auditWebsite(type, domains) {
                let generating_el = $('.generating-project-loading');
                let next_btn = generating_el.parents('#step-2').find('.next-step');
                let back_btn = generating_el.parents('#step-2').find('.prev-step');
                let step_4 = $('#step-4');
                project_ids = [];

                back_btn.hide();
                next_btn.disable('Working...');
                step_4.find('a.finish').hide();
                step_4.find('.ai-wizard-cost-label').hide();
                step_4.find('a.prev-step').disable();
                $('.top-ten-options').hide();

                let top_ten_options = step_4.find('.top-ten-options');
                let lang_code = top_ten_options.find('#top-ten-language').val();
                let lang = top_ten_options.find('#top-ten-language-select option:selected').val();
                let keyword_contain = top_ten_options.find('#keyword_contain').val();
                let keyword_contain_text = top_ten_options.find('.main_keyword_contain').val();
                let is_relative = top_ten_options.find('#is_relative').val();

                let requestsRemaining = domains.length; // Counter for remaining requests
                domains_length = domains.length;

                generating_el.html(`Finding & Clustering Your Keywords...`);

                domains.forEach(function (domain) {

                    $.ajaxq('ProjectQueue', {
                        type    : 'POST',
                        url     : xagio_data.wp_post,
                        data    : `action=xagio_generate_audit&type=${type}&website=${domain}&lang_code=${lang_code}&lang=${lang}&keyword_contain=${keyword_contain}&keyword_contain_text=${keyword_contain_text}&is_relative=${is_relative}`,
                        success : function (d) {

                            console.log('finish ' + domain);

                            if (d.status === 'credits') {
                                $.ajaxq.clear('ProjectQueue');
                                generating_el.html(d.message);
                                xagioNotify("warning", d.message, 15);
                                setTimeout(function(){
                                    document.location.reload();
                                }, 5000);
                            } else {
                                if (d.hasOwnProperty('project_id')) {
                                    generating_el.html(`Processing... ${domain}...`);
                                    processProject(d.project_id);
                                }
                            }
                        },
                        complete: function () {
                            requestsRemaining--; // Decrement the counter on each completion
                            if (requestsRemaining === 0) {
                                console.log('finished all');
                                // If all requests are completed, redirect
                                setTimeout(function () {
                                    finalProcessing();
                                }, 15000);
                            }
                        }
                    });
                });
            }

            function processProject(project_id) {
                $.ajaxq('ProjectQueue', {
                    type   : 'POST',
                    url    : xagio_data.wp_post,
                    data   : `action=xagio_generate_seed&project_id=${project_id}`,
                    success: function (d) {
                        if (d.status !== 'error') {
                            $.ajaxq('ProjectQueue', {
                                type   : 'POST',
                                url    : xagio_data.wp_post,
                                data   : `action=xagio_generate_phrasematch&project_id=${d.project_id}`,
                                success: function (dd) {
                                    project_id = dd.project_id;
                                    project_ids.push(project_id);
                                }
                            });
                        }
                    }
                });
            }

            function finalProcessing() {
                if (domains_length > 1) {
                    if (project_ids == 0) {
                        let generating_el = $('.generating-project-loading');
                        generating_el.html(`No keywords found for any of selected websites.`);
                        return;
                    }
                    $.ajaxq('ProjectQueue', {
                        type   : 'POST',
                        url    : xagio_data.wp_post,
                        data   : `action=xagio_combine_projects&project_ids=${project_ids.join(',')}`,
                        success: function (d) {
                            if (d.status !== 'error') {
                                let final_project_id = d.project_id;
                                $.ajaxq('ProjectQueue', {
                                    type   : 'POST',
                                    url    : xagio_data.wp_post,
                                    data   : `action=xagio_generate_seed&project_id=${final_project_id}`,
                                    success: function (d) {
                                        if (d.status !== 'error') {
                                            $.ajaxq('ProjectQueue', {
                                                type   : 'POST',
                                                url    : xagio_data.wp_post,
                                                data   : `action=xagio_generate_phrasematch&project_id=${final_project_id}`,
                                                success: function (dd) {
                                                    redirectToProject(dd.project_id);
                                                }
                                            });
                                        }
                                    }
                                });
                            } else {
                                xagioNotify("warning", d.message, 15);
                            }
                        }
                    });
                } else {
                    redirectToProject(project_ids[project_ids.length - 1]);
                }
            }

            function redirectToProject(project_id) {
                setTimeout(function () {
                    window.location = xagio_data.wp_admin +
                                      'admin.php?page=xagio-projectplanner&project_id=' +
                                      project_id;
                }, 2000); // Delay for UI effect or final processing
            }

            $(document).ready(function () {
                $(document).on('click', '.sort-groups-asc', function (e) {
                    $(this).hide();
                    $('.sort-groups-desc').show();
                });

                $(document).on('click', '.sort-groups-desc', function (e) {
                    $(this).hide();
                    $('.sort-groups-asc').show();
                });

                $(document).on('click', '.select-all-recommended-websites', function () {
                    let current_page_view = $('.show-page.active').data('page');

                    if($(`.top-ten-result.recommended.page-${current_page_view}`).length > 0) {
                        $(`.top-ten-result.recommended.page-${current_page_view}`).each(function() {
                            $(this).find('.select-website').prop('checked', true).trigger('change');
                        });
                    }

                });

                $(document).on('change', '.select-website', function () {

                    let count_checked = 0;
                    $('.top-ten-results .select-website').each(function(){
                        let checkbox = $(this);
                        if(checkbox.prop('checked')){
                            count_checked++;
                        }
                    });

                    $('.ai-wizard-cost-value').html(count_checked * actions.allowances.cost.wizards);

                    let parsed = actions.parseUrl($(this).val());
                    let path = parsed.prop('pathname');

                    if (path === '/') {
                        $('#is_relative').val(0);
                        $('span[data-element="is_relative"]').removeClass('on');
                    } else {
                        $('#is_relative').val(1);
                        $('span[data-element="is_relative"]').addClass('on');
                    }
                });

                let aiwizard = $('#aiwizard');

                $('#top-ten-language-select').select2({
                    dropdownParent    : aiwizard,
                    matcher: matcher,
                    placeholder: "Select a Search Engine"
                });

                $('#top_ten_search_engine').select2({
                    dropdownParent    : aiwizard,
                    matcher: matcher,
                    placeholder: "Select a Search Engine"
                });

                $('#top_ten_search_location').select2({
                    dropdownParent    : aiwizard,
                    matcher: matcher,
                    placeholder: "Select a Search Location"
                });

                $(document).on('select2:open', () => {
                    let el = $('.select2-search__field:visible');
                    if (el.hasOwnProperty(0)) {
                        el[0].focus();
                    }
                });

                $(document).on('click', '.show-page', function () {
                    let btn = $(this);
                    let show_page = btn.data('page');
                    let active_page = $('.show-page.active').data('page');

                    $('.show-page').removeClass('active');
                    btn.addClass('active');

                    $(`.page-${active_page}`).fadeOut();
                    $(`.page-${show_page}`).fadeIn();

                });

                $(document).on('keyup paste', '#top-ten-location-text, #top-ten-keyword', function () {

                    let location = $('#top-ten-location-text').val().trim();
                    let keyword = $('#top-ten-keyword').val().trim();

                    // if (keyword.indexOf("$") >= 0) {
                    //     keyword = keyword.replace("$", location);
                    // } else {
                    //     keyword = keyword + " " + location;
                    // }

                    if (isOriginalOrder) {
                        keyword = keyword + " " + location;
                    } else {
                        keyword = location + " " + keyword;
                    }

                    $('.main-keyword').val(keyword.trim());
                    $('.keyword-example').html(keyword.trim());
                });

                $(document).on('click', '#swap-words', function () {

                    let location = $('#top-ten-location-text').val().trim();
                    let keyword = $('#top-ten-keyword').val().trim();
                    let mainKeyword;

                    if (isOriginalOrder) {
                        mainKeyword = location + " " + keyword;
                    } else {
                        mainKeyword = keyword + " " + location;
                    }

                    $('.keyword-example').html(mainKeyword);
                    $('.main-keyword').val(mainKeyword.trim());

                    isOriginalOrder = !isOriginalOrder;
                });

                $(document).on('click', '#step-4 .finish', function (e) {
                    e.preventDefault();

                    // Get all checked checkboxes that belong to the class 'select-website'
                    let selected_websites = $('.top-ten-results input[name="select-website"]:checked');

                    // Check if at least one website is selected
                    if (selected_websites.length < 1) {
                        xagioNotify("warning", "Please select at least one website in table above");
                        return;
                    }

                    let balance = parseInt(actions.allowances.xags_allowance.find('.value').html()) + parseInt(actions.allowances.xags.find('.value').html());

                    if (balance < selected_websites.length) {
                        xagioNotify("warning", "You do not have enough XAGS to perform this operation!");
                        return;
                    }

                    // Collect all selected domains
                    let domains = selected_websites.map(function () {
                        return $(this).val();
                    }).get(); // Assuming you want to send the domains as a comma-separated string

                    // Hide elements during processing
                    $(".top-ten-pagination-container").hide();
                    $(".ai-wizard-buttons").hide();
                    $('.top-ten-results-info').slideUp();

                    // Show a loading message
                    $('.top-ten-results').html(`
        <div class="lds-facebook"><div></div><div></div><div></div></div>
        <p class="xagio-text-center generating-project-loading">Finding & Clustering Your Keywords for you... (Please do not close, refresh or leave this page) <br> Once completed you will be redirected to your project.</p>
    `);

                    // Call the auditWebsite function with the collected domains
                    auditWebsite('Wizard', domains);
                });

                $(document).on('click', '.search-top-ten', function (e) {
                    e.preventDefault();
                    let websites_holder = $('.top-ten-results');
                    let btn = $(this);
                    let main_keyword = $('.main-keyword').val();
                    let keyword = $('.top-websites-keyword').val();
                    let location = $('#top-ten-location-text').val();
                    let search_engine = $('#top_ten_search_engine').val();
                    let search_engine_text = $('#top_ten_search_engine option:selected').text();
                    let search_location = $('#top_ten_search_location').val();
                    let search_location_text = $('#top_ten_search_location option:selected').text();
                    let top_ten_results_info = $('.top-ten-results-info');

                    let step_4 = $('#step-4');
                    step_4.find('a.finish').hide();
                    step_4.find('.ai-wizard-cost-label').hide();
                    step_4.find('a.prev-step').hide();
                    top_ten_results_info.hide();

                    if (main_keyword.length < 1) {
                        xagioNotify("warning", "Please enter any keyword that best describes your business");
                        return false;
                    }

                    if (main_keyword.length > 80) {
                        xagioNotify("warning", "Keyword phrase must be lower then 80 characters long");
                        return false;
                    }

                    websites_holder.html(`
                                    <div class="lds-facebook"><div></div><div></div><div></div></div>
                                    <p class="xagio-text-center xag-loading-plugins">Loading... (Please do not close, refresh or leave this page)</p>
                             `);

                    $('.main_keyword_contain').val(location);

                    $('#top-ten-language-select option').each(function() {
                        $(this).attr('selected', false);

                        if ($(this).text().includes(search_location_text)) {
                            $(this).attr('selected', true);
                            $('#top-ten-language-select').trigger('change');
                        }
                    });

                    btn.disable();

                    $.post(xagio_data.wp_post, `action=xagio_get_top_ten&main-keyword=${main_keyword}&location=${location}&keyword=${keyword}&search_engine=${search_engine}&search_location=${search_location}&search_engine_text=${search_engine_text}&search_location_text=${search_location_text}`, function (d) {

                        top_ten_results_info.slideDown();
                        btn.disable();
                        step_4.find('a.finish').show();
                        step_4.find('.ai-wizard-cost-label').show();
                        step_4.find('a.prev-step').show();

                        if (d.status === 'error') {
                            xagioNotify("danger", d.message);
                            return;
                        }

                        let html = '';
                        let page = 1;
                        let pages = [];

                        for (let i = 0; i < d.data.length; i++) {
                            let website_row = $('.top-ten-result-template.template').clone().removeClass('template');
                            let for_id = `select-website${i + 1}`;

                            let website = d.data[i];

                            website_row.find('.website-position').html(`#${website['position']}`);
                            website_row.find('.select-website').attr('id', for_id).val(website['url']);
                            website_row.find('.g-url').html(website['url']).attr('href', website['url']);
                            website_row.find('.g-title').html(website['title']).attr('for', for_id);
                            website_row.find('.g-desc').html(website['snippet']);

                            if (website['recommended'] === true) {
                                website_row.find('.top-ten-result').addClass('recommended');
                            }

                            if (website['listing'] === true) {
                                website_row.find('.top-ten-result').addClass('not-recommended');
                                //website_row.find('.select-website').remove();
                            }


                            if (i % 10 === 0) {
                                pages.push((i / 10) + 1);
                                page = (i / 10) + 1;
                            }

                            website_row.find('.top-ten-result').addClass(`page-${page}`);

                            html += website_row.html();
                        }

                        let pagination = '<div class="top-ten-pagination">';
                        for (let i = 0; i < pages.length; i++) {
                            pagination += `<span class="show-page ${i ===
                                                                    0 ? 'active' : ''}" data-page="${pages[i]}">${pages[i]}</span>`;
                        }

                        pagination += '</div>';

                        $('.top-ten-pagination-container').html(pagination);

                        websites_holder.html(html);
                        $('.top-ten-options').slideDown();
                    });
                });

                $(document).on('click', '.mistake', function (e) {
                    e.preventDefault();
                    $('.aiwizard').hide();
                    $('.aiwizard-start').fadeIn();
                    window.history.replaceState({}, document.title, document.location.href.replace(/#.+/, ""));
                });

                $(document).on('click', '.stop-aiwizard', function (e) {
                    e.preventDefault();

                    let modal = $("#aiwizard");
                    window.history.replaceState({}, document.title, document.location.href.replace(/#.+/, ""));


                    modal[0].close();

                });

                $('#aiwizard')[0].addEventListener("close", (event) => {

                    $('.aiwizard-wizard').smartWizard("reset");
                    $('.top-ten-options').hide();
                    $('.aiwizard-start').show();
                    $('.aiwizard').hide();
                });

                $(document).on('click', '.option-picker', function () {
                    let option = $(this).attr('data-type');

                    $('#aiwizard-type').val(option);

                    let step1 = $('#step-1');
                    let step2 = $('#step-2');


                    if (option === 'affiliate') {
                        $('.step-1-header').html('Main Niche');
                        $('.step-2-header').html('Site Type');
                        if (step1.find('.step-input #top-ten-location-text').length > 0) {

                            step1.find('.step-text').html(`What is the main <b>niche</b> of your website`);
                            step1.find('.help').html(``);

                            step2.find('.step-text').html(`What type of site are you building`);

                            let input1 = step1.find('.step-input #top-ten-location-text').clone().remove();
                            let input2 = step2.find('.step-input #top-ten-keyword').clone().remove();
                            input1.attr('placeholder', 'e.g. review, bonus...');
                            input2.attr('placeholder', 'e.g. weight loss, dedicated hosting...');
                            step1.find('.step-input').html(input2);
                            step2.find('.step-input').html(input1);

                            $('#keyword_contain').val(1);
                            $('span[data-element="keyword_contain"]').addClass('on');
                        }
                    } else {
                        $('.step-1-header').html('Location');
                        $('.step-2-header').html('Services');
                        if (step1.find('.step-input #top-ten-keyword').length > 0) {

                            step1.find('.step-text').html(`In what <b class="with-underscore">City</b> is your Business located at?`);
                            step1.find('.help').html(`You can leave this empty, however, it is always recommended to include City for your Businesses.`);

                            step2.find('.step-text').html(`Enter a <b class="with-underscore">Keyword</b> that best describes your Business`);

                            let input1 = step1.find('.step-input #top-ten-keyword').clone().remove();
                            let input2 = step2.find('.step-input #top-ten-location-text').clone().remove();
                            input1.attr('placeholder', 'e.g. pool cleaning');
                            input2.attr('placeholder', 'e.g. austin');
                            step1.find('.step-input').html(input2);
                            step2.find('.step-input').html(input1);
                            $('#keyword_contain').val(0);
                            $('span[data-element="keyword_contain"]').removeClass('on');
                        }
                    }

                    $('.aiwizard-start').fadeOut(function () {
                        $('.aiwizard').fadeIn();
                    });

                });

                $(document).on('click', '.select-type', function () {
                    $('.aiwizard').fadeOut(function () {
                        $('.aiwizard-start').fadeIn();
                    });
                    $('.aiwizard-wizard').smartWizard("reset");
                });

                $('.aiwizard-wizard').smartWizard({
                                                      theme           : 'arrows',
                                                      toolbar         : {
                                                          position          : 'none',
                                                          showNextButton    : false,
                                                          showPreviousButton: false,
                                                      },
                                                      autoAdjustHeight: false
                                                  });

                $(document).on('keydown', '#top-ten-keyword, #top-ten-location-text', function (e) {
                    e.stopPropagation();

                    let input = $(this);
                    let keyword = input.val();
                    if (e.keyCode === 13) {
                        e.preventDefault();
                        if (keyword.length < 1) {

                            let message = 'Please enter main niche of your website!';
                            if (input.attr('id') === 'top-ten-location-text') {
                                message = 'Please enter location of your business!';
                            }

                            xagioNotify("warning", message);

                            return false;
                        }

                        if ($('#step-2').is(':visible')) {
                            $('.search-top-ten').trigger('click');
                        }

                        let w = $('.aiwizard-wizard');
                        w.smartWizard("next");

                    }
                });

                $(document).on('click', '.next-step', function (e) {
                    e.preventDefault();

                    let parent = $(this).parents('.tab-pane');
                    let input = parent.find('#top-ten-location-text');
                    if (input.length < 1) {
                        input = parent.find('#top-ten-keyword');
                    }

                    let keyword = input.val();

                    if (keyword.length < 1) {

                        let message = 'Please enter main niche of your website!';
                        if (input.attr('id') === 'top-ten-location-text') {
                            message = 'Please enter location of your business!';
                        }

                        xagioNotify("warning", message);
                        return false;
                    }

                    let w = $('.aiwizard-wizard');
                    w.smartWizard("next");
                });

                $(document).on('click', '.prev-step', function (e) {
                    e.preventDefault();

                    let w = $('.aiwizard-wizard');
                    w.smartWizard("prev");
                });

            });

        },

        showShortcodes: function () {
            $(document).on('click', '.groupInput[name="h1"]', function (e) {
                e.preventDefault();
                if (typeof $(this).attr('value-shortcoded') == 'undefined') return;

                if ($(this).val() !== $(this).attr('value-original')) {
                    $(this).val($(this).attr('value-original'));
                }
            });
            $(document).on('mouseenter', '.groupInput[name="h1"]', function (e) {
                e.preventDefault();
                if (typeof $(this).attr('value-shortcoded') == 'undefined') return;

                $(this).val($(this).attr('value-shortcoded'));
            });
            $(document).on('mouseleave', '.groupInput[name="h1"]', function (e) {
                e.preventDefault();
                if (typeof $(this).attr('value-shortcoded') == 'undefined') return;

                $(this).val($(this).attr('value-original'));
            });
            $(document).on('keyup', '.groupInput[name="h1"]', function (e) {
                e.preventDefault();
                if (typeof $(this).attr('value-shortcoded') == 'undefined') return;

                $(this).attr('value-original', $(this).val());
            });

            $(document).on('keyup', '.prs-h1tag', function (e) {

                e.preventDefault();
                $(this).prev().val($(this).html());

            });

            $(document).on('keyup', '.prs-editor', function (e) {
                e.preventDefault();
                if (typeof $(this).prev().attr('value-shortcoded') == 'undefined') return;

                $(this).prev().attr('value-original', $(this).html());
            });
            $(document).on('click', '.prs-editor', function (e) {
                e.preventDefault();
                if (typeof $(this).prev().attr('value-shortcoded') == 'undefined') return;

                if ($(this).html() !== $(this).prev().attr('value-original')) {
                    $(this).html($(this).prev().attr('value-original'));
                }
            });
            $(document).on('mouseenter', '.prs-editor', function (e) {
                e.preventDefault();
                if (typeof $(this).prev().attr('value-shortcoded') == 'undefined') return;

                $(this).html($(this).prev().attr('value-shortcoded'));
            });
            $(document).on('mouseleave', '.prs-editor', function (e) {
                e.preventDefault();
                if (typeof $(this).prev().attr('value-shortcoded') == 'undefined') return;

                $(this).html($(this).prev().attr('value-original'));
            });

        },

        runBatchCron: function () {
            clearTimeout(batchCron);
            batchCron = setTimeout(function () {

                $.post(xagio_data.wp_post, 'action=xagio_checkBatchCron', function (d) {

                    if (d.status == 'change' || d.status == 'done') {
                        actions.loadProjectManually();
                    }

                    if (d.status != 'done') {
                        actions.runBatchCron();
                    }
                });

            }, 5000);
        },

        runVolCPCBatchCron: function () {
            clearTimeout(volCpcBatchCron);
            volCpcBatchCron = setTimeout(function () {

                $.post(xagio_data.wp_post, 'action=xagio_checkVolCPCBatchCron', function (d) {

                    if (d.status == 'change' || d.status == 'done') {
                        actions.loadProjectManually();
                    }

                    if (d.status != 'done') {
                        actions.runVolCPCBatchCron();
                    }
                });

            }, 5000);
        },

        wordCountCloud         : function () {

            $(document).on('click', '.wordCloud', function () {

                let cloudBoxTemplate = $('.cloud.template.hide').clone();
                cloudBoxTemplate.removeClass('hide').show().addClass('seen');

                let btn = $(this);
                let cloudKeyword = btn.parents('.xagio-group').find('.xagio-keyword-cloud');

                if (btn.hasClass('open')) {
                    if(cloudKeyword.hasClass('generated')) {
                        btn.removeClass('open');
                        btn.attr('data-xagio-title', 'Open Word Cloud');
                        btn.find('i').removeClass().addClass('xagio-icon xagio-icon-cloud');
                        $('.xagio-tooltip').remove();

                        for (let m = 0; m < 15; m++) {
                            // Remove b tag from title, desciption, url, H1
                            btn.parents('.xagio-group').find('.prs-title').html(btn.parents('.xagio-group').find('.prs-title').html().replace(/<b class="highlightCloud">(.+)<\/b>/gi, "$1"));
                            btn.parents('.xagio-group').find('.prs-description').html(btn.parents('.xagio-group').find('.prs-description').html().replace(/<b class="highlightCloud">(.+)<\/b>/gi, "$1"));
                            btn.parents('.xagio-group').find('.url-edit').html(btn.parents('.xagio-group').find('.url-edit').html().replace(/<b class="highlightCloud">(.+)<\/b>/gi, "$1"));
                            btn.parents('.xagio-group').find('.prs-h1tag').html(btn.parents('.xagio-group').find('.prs-h1tag').html().replace(/<b class="highlightCloud">(.+)<\/b>/gi, "$1"));
                            // Remove b tag from keywords
                            btn.parents('.xagio-group').find('.updateKeywords').find('.keywordInput[data-target="keyword"]').each(function () {
                                $(this).html($(this).html().replace(/<b class="highlightCloud">(.+)<\/b>/gi, "$1"));
                            });
                        }
                        cloudKeyword.toggle();
                        $(".jqcloud").css("display", "block").resize();
                    }

                    // let thisCont = btn.parents('.xagio-group').find('.cloud.template.seen.jqcloud');
                    // if (thisCont.length > 0) {
                    //     thisCont.jQCloud('destroy');
                    //     thisCont.slideUp("normal", function () {
                    //         $(this).remove();
                    //         actions.updateGrid();
                    //     });
                    // }
                } else {
                    if (!cloudKeyword.hasClass("generated")) {
                        let tbody_keywords = btn.parents('.xagio-group').find('.updateKeywords').find('.keywords').find('.keywords-data tr').find('div.keywordInput[data-target="keyword"]');
                        let keywords = [];
                        tbody_keywords.each(function () {
                            keywords.push($(this).text());
                        });

                        if (keywords.length > 0) {
                            btn.parents('.xagio-group').find('.xagio-keyword-cloud').html(cloudBoxTemplate);
                            cloudBoxTemplate.jQCloud(actions.calculateAndTrim(keywords), {
                                colors    : [
                                    "#ffffff",
                                    "#FAF9F6",
                                    "#F1F0ED",
                                    "#E5E4E2",
                                    "#D9D8D6"
                                ],
                                autoResize: true,
                                height    : 350,
                                fontSize  : {
                                    from: 0.07,
                                    to  : 0.02
                                }
                            });

                            $(".jqcloud").css("display", "block").resize();

                            cloudKeyword.addClass('generated');
                            btn.addClass('open');
                            btn.attr('data-xagio-title', 'Close Word Cloud');
                            btn.find('i').removeClass().addClass('xagio-icon xagio-icon-cloud-o');
                            $('.xagio-tooltip').remove();
                        } else {
                            btn.removeClass('open');
                            xagioNotify("warning", "No keywords for this group");
                        }
                    } else {
                        btn.addClass('open');
                        btn.attr('data-xagio-title', 'Close Word Cloud');
                        btn.find('i').removeClass().addClass('xagio-icon xagio-icon-cloud-o');
                        $('.xagio-tooltip').remove();
                        cloudKeyword.toggle();
                        $(".jqcloud").css("display", "block").resize();
                    }
                }
                actions.updateGrid();
            });
        },
        expandCollapseFunctions: function () {
            $(document).on('click', '.collapseAllGroups', function (e) {
                e.preventDefault();
                actions.collapseKeywordGroups();
                actions.collapseSettingsBody();
                actions.updateGrid();
                // $('.data').trigger('display.uk.check');
            });

            $(document).on('click', '.expandAllGroups', function (e) {
                e.preventDefault();
                $(this).disable();
                actions.expandKeywordGroups();
                actions.expandGroupNotes();
                actions.expandGroupWordCount();
                actions.expandSettingsBody();

                actions.updateGrid();
                // $('.data').trigger('display.uk.check');
                $(this).disable();
            });

            $(document).on('click', '.expandKeywordGroups', function (e) {
                e.preventDefault();
                actions.expandKeywordGroups();

                $('.minimizeGroup').each(function () {
                    let btn = $(this);
                    btn.removeClass('kw-opened');
                    btn.attr('data-xagio-title', 'Hide Keywords');
                    btn.find('i').removeClass('xagio-icon-eye').addClass('xagio-icon-eye-o');
                    $('.xagio-tooltip').remove();
                });

                actions.updateGrid();
                // $('.data').trigger('display.uk.check');
            });

            $(document).on('click', '.collapseKeywordGroups', function (e) {
                e.preventDefault();
                actions.collapseKeywordGroups();

                $('.minimizeGroup').each(function () {
                    let btn = $(this);
                    btn.addClass('kw-opened');
                    btn.attr('data-xagio-title', 'Show Keywords');
                    btn.find('i').removeClass('xagio-icon-eye-o').addClass('xagio-icon-eye');
                    $('.xagio-tooltip').remove();
                });


                // $('.data').trigger('display.uk.check');
            });
        },
        expandKeywordGroups    : function () {
            $('.updateKeywords').each(function () {
                $(this).removeClass('hidden');
            });
            $('.minimizeGroup').each(function () {
                $(this).attr('data-xagio-title', 'Hide Keywords').find('i').removeClass('xagio-icon-eye').addClass('xagio-icon-eye-o');
            });
        },
        expandGroupNotes       : function () {
            $('.notes-row').each(function () {
                $(this).show();
            });
            $('.openNotes').each(function () {
                $(this).addClass('notesOpened');
                $(this).attr('data-xagio-title', 'Close Notes');
                $(this).find('i').removeClass().addClass('xagio-icon xagio-icon-note-o');
            });
        },
        expandGroupWordCount   : function () {

            $('.wordCloud').each(function () {
                let btn = $(this);

                if (btn.hasClass('open')) return;

                let cloudBoxTemplate = $('.cloud.template.hide').clone();
                cloudBoxTemplate.removeClass('hide').show().addClass('seen');

                let cloudKeyword = btn.parents('.xagio-group').find('.xagio-keyword-cloud');

                if(!cloudKeyword.hasClass("generated")) {
                    cloudKeyword.addClass('generated');

                    btn.addClass('open');
                    btn.attr('data-xagio-title', 'Close Word Cloud');
                    btn.find('i').removeClass().addClass('xagio-icon xagio-icon-cloud-o');

                    let tbody_keywords = btn.parents('.xagio-group').find('.updateKeywords').find('.keywords').find('.keywords-data tr').find('div.keywordInput[data-target="keyword"]');

                    let keywords = [];
                    tbody_keywords.each(function () {
                        keywords.push($(this).text());
                    });

                    if (keywords.length > 0) {
                        btn.find('i').removeClass().addClass('xagio-icon xagio-icon-cloud-o');
                        btn.parents('.xagio-group').find('.xagio-keyword-cloud').html(cloudBoxTemplate);
                        cloudBoxTemplate.jQCloud(actions.calculateAndTrim(keywords), {
                            delay     : 50,
                            colors    : [
                                "#ffffff",
                                "#FAF9F6",
                                "#F1F0ED",
                                "#E5E4E2",
                                "#D9D8D6"
                            ],
                            autoResize: true,
                            height    : 350,
                            fontSize  : {
                                from: 0.1,
                                to  : 0.03
                            }
                        });

                        actions.updateGrid();
                        $(".jqcloud").css("display", "block").resize();
                    }
                } else {
                    btn.addClass('open');
                    btn.attr('data-xagio-title', 'Close Word Cloud');
                    btn.find('i').removeClass().addClass('xagio-icon xagio-icon-cloud-o');
                    $(".jqcloud").css("display", "block").resize();
                    cloudKeyword.show();
                }
            });
        },
        collapseKeywordGroups  : function () {
            $('.updateKeywords').each(function () {
                $(this).removeClass('hidden').addClass('hidden');
                $(this).parents('.xagio-group').find('.minimizeGroup').attr('data-xagio-title', 'Hide Keywords').find('i').removeClass('xagio-icon-eye-o').addClass('xagio-icon-eye');
            });

            $('.minimizeGroup').each(function () {
                $(this).attr('data-xagio-title', 'Hide Keywords').find('i').removeClass('xagio-icon-eye-o').addClass('xagio-icon-eye');
            });

            actions.updateGrid();
        },
        expandSettingsBody     : function () {
            $('.groupSettingsTbody').each(function () {
                $(this).css('display', 'table-row-group');
            });
        },
        collapseSettingsBody   : function () {
            $('.xagio-group').each(function () {
                $(this).find('.notes-row').hide();
                let notes_btn = $(this).find('.openNotes');

                notes_btn.removeClass('notesOpened');
                notes_btn.attr('data-xagio-title', 'Open Notes');
                notes_btn.find('i').removeClass().addClass('xagio-icon xagio-icon-note');

                let cloudBtn = $(this).find('.wordCloud');
                cloudBtn.attr('data-xagio-title', 'Open Word Cloud');
                cloudBtn.removeClass('open');
                cloudBtn.find('i').removeClass().addClass('xagio-icon xagio-icon-cloud');
                cloudBtn.parents('.xagio-group').find('.updateKeywords').find('.keywordInput[data-target="keyword"]').unhighlight();

                cloudBtn.parents('.xagio-group').find('.prs-title').unhighlight();
                cloudBtn.parents('.xagio-group').find('.prs-description').unhighlight();

                cloudBtn.parents('.xagio-group').find('.xagio-keyword-cloud').hide();
                //
                // let thisCont = cloudBtn.parents('.xagio-group').find('.cloud.template.seen.jqcloud');
                //
                // if (thisCont.length > 0) {
                //     thisCont.jQCloud('destroy');
                //     thisCont.slideUp("normal", function () {
                //         $(this).remove();
                //         actions.updateGrid();
                //     });
                // }

            });
        },
        keywordInputKeypress   : function () {
            $(document).on('keypress', '.keywordInput', function () {
                activeChanges = true;
            });
            $(document).on('keypress', '[contenteditable="true"]', function () {
                activeChanges = true;
            });
        },

        addGroupFromExistingTaxonomy : function () {

            $(document).on('click', '.addGroupFromExistingTaxonomy', function (e) {
                e.preventDefault();

                selectedTaxonomies = [];
                $('.selected-taxonomies').html('');

                let addGroupModal = $('#addGroupFromExistingTaxonomyModal');
                addGroupModal[0].showModal();
            });

            $(document).on('change', '.select-taxonomy', function () {

                let checked = $(this).is(':checked');
                let val = $(this).val();
                if (checked) {
                    selectedTaxonomies.push(val);
                } else {
                    selectedTaxonomies.splice($.inArray(val, selectedTaxonomies), 1);
                }

                if (selectedTaxonomies.length < 1) {
                    $('.selected-taxonomies').html('');
                } else {
                    $('.selected-taxonomies').html(`(${selectedTaxonomies.length})`);
                }


            });

            $(document).on('change', '.select-taxonomies-all', function () {

                let checked = $(this).is(':checked');

                $('.taxonomiesTableCreate').find('.select-taxonomy').each(function () {
                    $(this).prop('checked', checked);
                    $(this).trigger('change');
                });

            });

            $(document).on('click', '.add-group-from-existing-taxonomy', function (e) {
                e.preventDefault();

                let btn = $(this);
                btn.disable();

                if (selectedTaxonomies.length < 1) {
                    xagioNotify("danger", "You must first select some taxonomies first!");
                    return;
                }

                $.post(xagio_data.wp_post, 'action=xagio_make_groups_from_taxonomies&ids=' +
                                           selectedTaxonomies.join(',') + '&project_id=' +
                                           currentProjectID, function (d) {

                    $('#addGroupFromExistingTaxonomyModal')[0].close();
                    btn.disable();
                    xagioNotify("success", d.message);
                    actions.loadProjectManually();

                });

            });

        },
        addGroupFromExisting         : function () {

            $(document).on('click', '.addGroupFromExisting', function (e) {
                e.preventDefault();

                selectedPosts = [];
                $('.selected-posts').html('');

                let addGroupModal = $('#addGroupFromExistingModal');
                addGroupModal[0].showModal();
            });

            $(document).on('change', '.select-post', function () {

                let checked = $(this).is(':checked');
                let val = $(this).val();
                if (checked) {
                    selectedPosts.push(val);
                } else {
                    selectedPosts.splice($.inArray(val, selectedPosts), 1);
                }

                if (selectedPosts.length < 1) {
                    $('.selected-posts').html('');
                } else {
                    $('.selected-posts').html(`(${selectedPosts.length})`);
                }

            });

            $(document).on('change', '.select-posts-all', function () {

                let checked = $(this).is(':checked');

                $('.postsTable2').find('.select-post').each(function () {
                    $(this).prop('checked', checked);
                    $(this).trigger('change');
                });

            });

            $(document).on('click', '.add-group-from-existing', function (e) {
                e.preventDefault();

                let btn = $(this);
                btn.disable();

                if (selectedPosts.length < 1) {
                    xagioNotify("danger", "You must select some posts first!");
                    return;
                }

                $.post(xagio_data.wp_post, 'action=xagio_make_groups&ids=' + selectedPosts.join(',') + '&project_id=' +
                                           currentProjectID, function (d) {

                    $('#addGroupFromExistingModal')[0].close();
                    btn.disable();
                    xagioNotify("success", d.message);
                    actions.loadProjectManually();

                });

            });

        },
        selectAllPagePosts           : function () {
            $(document).on('click', '.select-all-page-posts', function () {

                let btn = $(this);

                if (btn.hasClass('selected')) {
                    $("#posts_pages > option").removeAttr("selected").trigger("change");
                    btn.removeClass('uk-button-danger selected').addClass('uk-button-success');
                    btn.html('<i class="xagio-icon xagio-icon-plus"></i> Select All');
                } else {
                    $('#posts_pages > option').prop("selected", "selected").trigger("change");
                    btn.removeClass('uk-button-success').addClass('uk-button-danger selected');
                    btn.html('<i class="xagio-icon xagio-icon-minus"></i> Deselect All');
                }

            });
        },
        deleteRedirect               : function () {
            $(document).on('click', '.delete-redirect', function (e) {
                e.preventDefault();
                let button = $(this);
                let id = $(this).data('id');
                button.disable();

                xagioModal("Are you sure?", "Are you sure that you want to delete this redirect?", function (yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, 'action=xagio_delete_redirect&id=' + id, function (d) {
                            button.disable();
                            actions.loadRedirects();
                        });
                    } else {
                        button.disable();
                    }
                });
            });
        },
        addNewRedirect               : function () {
            $(document).on('click', '.add-new-redirect', function (e) {
                e.preventDefault();

                let button = $(this);

                xagioPromptModal("Confirm", "Old URL (use the /oldurl/ format):", function (result) {

                    if (result) {
                        let old_url = result;
                        xagioPromptModal("Confirm", "Redirect to URL (use the /newurl/ format) (DANGER: Creating invalid redirects may result in breaking of your website):", function (result) {
                            if (result) {
                                button.disable('Saving...');
                                let new_url = result;
                                $.post(xagio_data.wp_post, 'action=xagio_add_redirect&oldURL=' + old_url + '&newURL=' +
                                                           new_url, function (d) {
                                    button.disable();
                                    actions.loadRedirects();
                                });
                            }
                        });
                    }
                });

            });
        },
        loadRedirects                : function () {
            let messages = {
                empty  : '<tr><td colspan="4">Can\'t find any active redirects.</td></tr>',
                loading: '<tr><td colspan="4"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Loading ...</td></tr>'
            };
            let table = $('.table-redirects');
            let tbody = table.find('tbody');

            tbody.empty().append(messages.loading);

            $.post(xagio_data.wp_post, 'action=xagio_get_redirects', function (d) {
                if (d.status == 'success') {

                    if (d.data.length == 0) {
                        tbody.empty().append(messages.empty);
                    } else {
                        tbody.empty();

                        for (let i = 0; i < d.data.length; i++) {
                            let data = d.data[i];
                            let html = '<tr>' + '<td><a target="_blank" href="/' + data.old + '">/' + data.old +
                                       '</a></td>' + '<td><a target="_blank" href="/' + data.new + '">/' + data.new +
                                       '</a></td>' +
                                       '<td><button type="button" class="xagio-button xagio-button-danger xagio-button-mini delete-redirect" data-id="' +
                                       data.id +
                                       '" title="Delete this redirect"><i class="xagio-icon xagio-icon-delete"></i></button></td>' +
                                       '</tr>';
                            tbody.append(html);
                        }

                    }

                } else {
                    xagioNotify("danger", "An unknown error has occurred.");
                }
            });

        },
        minimizeGroup                : function () {
            $(document).on('click', '.minimizeGroup', function () {
                let i = $(this).find('i');
                let kw = $(this).parents('.xagio-group').find('.updateKeywords');
                let btn = $(this);
                if (btn.hasClass('kw-opened')) {
                    btn.removeClass('kw-opened');
                    btn.attr('data-xagio-title', 'Hide Keywords');
                    $('.xagio-tooltip').remove();
                } else {
                    btn.addClass('kw-opened');
                    btn.attr('data-xagio-title', 'Show Keywords');
                    $('.xagio-tooltip').remove();
                }
                i.toggleClass('xagio-icon-eye xagio-icon-eye-o');
                kw.toggleClass('hidden');

                actions.updateGrid();
                // btn.parents('.xagio-group').trigger('display.uk.check');
            });
        },
        selectKeyword                : function () {
            $(document).on('change', '.keyword-selection', function () {
                let tr = $(this).parents('tr');
                if (!tr.hasClass('selected')) {
                    tr.addClass('selected');
                }
            });
        },
        submitKeywordsForRanking     : function () {
            $(document).on('submit', '#rankTrackingForm', function (e) {
                e.preventDefault();

                let btn = $(this).find('.submitKeywords');
                btn.attr('disabled', true);


                let ranking_modal = $('#rankTrackingModal');

                let form_data = $(this).serialize();

                $.post(xagio_data.wp_post, 'action=xagio_track_keywords_add&' + form_data, function (d) {
                    btn.attr('disabled', false);
                    ranking_modal[0].close();
                    actions.loadProjectManually();
                    actions.refreshXags();

                    if (d.status == 'error') {
                        xagioNotify("danger", d.message);
                    } else {
                        xagioNotify("success", d.message);
                    }
                });

            });
        },
        trackRankings                : function () {
            $(document).on('click', '.track_rankings', function (e) {
                e.preventDefault();

                let group = $(this).parents('.xagio-group');
                let ids = [];
                let keywords = [];
                let btn = $(this);
                let type = btn.data('type');

                if (type === "all") {
                    $('.keyword-selection').each(function () {
                        let tr = $(this).parents('tr');
                        $(this).removeAttr('checked');
                        let kw = tr.find('.keywordInput[data-target="keyword"]').text().trim();
                        let id = tr.data('id');

                        if (kw != '') {
                            ids.push(id);
                            keywords.push(kw);
                        }
                    });
                } else {
                    group.find('.keyword-selection').each(function () {
                        let tr = $(this).parents('tr');
                        if ($(this).is(':checked')) {
                            $(this).removeAttr('checked');
                            let kw = tr.find('.keywordInput[data-target="keyword"]').text().trim();
                            let id = tr.data('id');
                            if (kw != '') {
                                ids.push(id);
                                keywords.push(kw);
                            }
                        }
                    });
                }

                if (keywords.length < 1) {
                    xagioNotify("danger", "Please select some keywords!");
                    return false;
                }

                let ranking_modal = $('#rankTrackingModal');

                ranking_modal[0].showModal();

                // ranking_modal.find('input[name="keywords"]').tagsInput({
                //     'interactive': false
                // }).importTags(keywords.join(','));

                // vol_cpc_modal.find('input[name="keywords"]').tagsInput({
                //     'interactive': false
                // }).importTags(keywords.join(','));

                ranking_modal.find('input[name="keywords"]').val(keywords.join(','));


                ranking_modal.find('#search_engine').select2({
                    matcher           : matcher,
                    dropdownParent    : ranking_modal,
                    placeholder       : "Select a Search Engine"
                });

                ranking_modal.find('#search_location').select2({
                    dropdownParent    : ranking_modal,
                    placeholder       : "Select a Country"
                })

            });

            $(document).on('click', '.ranking-kw-select', function () {
                let checkbox = $(this);
                let keyword_id = checkbox.data('value');
                let keyword = checkbox.data('keyword');
                let modal = checkbox.parents('#rankTrackingModal');

                let keyword_names_input = modal.find('#keywords')
                let keyword_names = keyword_names_input.val();
                keyword_names = keyword_names.split(',');

                if (jQuery.inArray(keyword.toString(), keyword_names) !== -1) {
                    keyword_names.splice($.inArray(keyword.toString(), keyword_names), 1);
                } else {
                    keyword_names.push(keyword);
                }

                keyword_names_input.val(keyword_names.join(','));

            });

            $('#rankTrackingModal').on({
                                           'hide.uk.modal': function () {
                                               let ranking_modal = $('#rankTrackingModal');
                                               ranking_modal.find('.tagsinput').remove();
                                           }
                                       });

            $(document).on('submit', '#rankTrackingDefaultCountryForm', function (e) {
                e.preventDefault();
                let form = $(this);
                let btn = form.find('.submitDefaultCountry')
                let modal = form.parents('.xagio-modal');

                btn.disable("Saving...");
                let country = form.find('#search_country_data').val();

                let params = new FormData();
                params.append('action', 'xagio_set_default_country');
                params.append('data', country);

                $.ajax({
                           url        : xagio_data.wp_post,
                           type       : 'POST',
                           data       : params,
                           processData: false, // Necessary for FormData
                           contentType: false, // Necessary for FormData
                           success    : function (d) {
                               btn.disable(); // Properly disable the button
                               if (modal.length > 0) { // Assuming 'modal' is correctly initialized
                                   modal[0].close(); // Close the modal if it's open
                               }
                               form.find('#search_country_data').val('').trigger('change'); // Clear Select2

                               xagioNotify(d.status, d.message);
                           }
                       });
            });

            $(document).on('submit', '#rankTrackingDefaultForm', function (e) {
                e.preventDefault();
                let form = $(this);
                let btn = form.find('.submitDefaultEngine')
                let modal = form.parents('.xagio-modal');

                btn.disable("Saving...");
                let engines = form.find('#search_engine_data').val();
                try {
                    engines = JSON.parse(engines);
                } catch (error) {
                    engines = [];
                }

                let params = new FormData();
                params.append('action', 'xagio_set_default_search_engine');

                for (let i = 0; i < engines.length; i++) {
                    const engine = engines[i];
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
                               btn.disable();
                               // btn.prop('disabled', true); // Properly disable the button
                               if (modal.length > 0) { // Assuming 'modal' is correctly initialized
                                   modal[0].close(); // Close the modal if it's open
                               }
                               form.find('#search_engine_data').val('').trigger('change'); // Clear Select2

                               xagioNotify(d.status, d.message);
                           }
                       });
            });

            $(document).on('change', '#search_location', function (e) {
                let select = $(this);
                let changeDefaultCountryModal = $('#rankTrackingDefaultCountry');
                let data = select.select2('data');

                if (data.length > 0) {
                    data = data[0].id;
                } else {
                    data = 0;
                }

                if (data.length >= 1) {
                    changeDefaultCountryModal.find('#search_country_data').val(data);
                    changeDefaultCountryModal[0].showModal();
                }
            });

            $(document).on('change', '#search_engine', function () {
                let select = $(this);

                let data = select.select2('data');
                let se = [];
                let local = true;
                let changeDefaultEngineModal = $('#rankTrackingDefault');

                if (data.length >= 1) {
                    let searchEngine = [];
                    for (let i = 0; i < data.length; i++) {
                        let id = data[i].id;
                        let text = data[i].text;
                        let sd = {
                            'id'  : id,
                            'text': text
                        }
                        searchEngine.push(sd);
                    }

                    changeDefaultEngineModal.find('#search_engine_data').val(JSON.stringify(searchEngine));
                    changeDefaultEngineModal[0].showModal();
                }

                for (let i = 0; i < data.length; i++) {
                    let selected = data[i].text;
                    se.push(selected);
                }

                for (let k = 0; k < se.length; k++) {
                    if (se[k].indexOf('Google') == -1) {
                        local = false;
                    }
                }

                if (data.length < 1) {
                    local = false;
                }

                if (local) {
                    $('#local_track').attr('disabled', false);
                    $('#local_fieldset').attr('disabled', false);
                } else {
                    $('#local_track').attr('disabled', true).attr('checked', false);
                    $('#local_track').removeClass('on').addClass('off');
                    $('.local_fieldset').attr('disabled', true).fadeOut();
                }

            });

            $(document).on('click', '#local_track', function () {

                let checkbox = $(this);
                let local_fields = $('.local_fieldset');

                if (checkbox.attr('disabled') == 'disabled') {
                    return false;
                }

                if ($(this).hasClass('on')) {
                    $(this).removeClass('on').addClass('off');
                    local_fields.attr('disabled', true).fadeOut();
                } else {
                    $(this).removeClass('off').addClass('on');
                    local_fields.attr('disabled', false).fadeIn();
                }

            });
        },
        selectAllGroups              : function () {
            $(document).on('click', '.selectAllGroups', function (e) {
                e.preventDefault();
                $('.project-groups .groupSelect').prop('checked', !$('.project-groups .groupSelect').prop('checked'));
            });
        },
        moveSelectedGroups           : function () {
            $(document).on('click', '.moveSelectedGroups', function (e) {
                e.preventDefault();
                let input = $('#moveToProjectInput');

                let ids = [];
                $('.groupSelect:checked').each(function () {
                    let group = $(this).parents('.xagio-group');
                    ids.push(group.find('[name="group_id"]').val());
                });

                if (ids.length > 0) input.data('group-id', ids.join(','));

                moveToProject = $('#moveToProjectGroup');

                $.post(xagio_data.wp_post, 'action=xagio_get_projects', function (d) {

                    input.empty();

                    input.append('<option value="">Select a Project / Create a new Project</option>');

                    for (let i = 0; i < d.aaData.length; i++) {
                        let o = d.aaData[i];
                        input.append('<option value="' + o.id + '">' + o.project_name + '</option>');
                    }

                    input.select2({
                                      dropdownParent: moveToProject,
                                      placeholder   : "Select a Project / Create a new Project",
                                      tags          : true
                                  });

                    moveToProject[0].showModal();
                });

            });
        },
        moveToProject                : function () {
            $(document).on('click', '.moveToProject', function (e) {
                e.preventDefault();
                let input = $('#moveToProjectInput');
                let group_id = $(this).parents('.xagio-group').find('.updateGroup').find('input[name="group_id"]').val();
                input.data('group-id', group_id);
                moveToProject = $('#moveToProjectGroup');

                $.post(xagio_data.wp_post, 'action=xagio_get_projects', function (d) {

                    input.empty();

                    input.append('<option value="">Select a Project / Create a new Project</option>');

                    for (let i = 0; i < d.aaData.length; i++) {
                        let o = d.aaData[i];
                        input.append('<option value="' + o.id + '">' + o.project_name + '</option>');
                    }

                    input.select2({
                                      dropdownParent: moveToProject,
                                      placeholder   : "Select a Project / Create a new Project",
                                      tags          : true
                                  });

                    moveToProject[0].showModal();
                });

                //
            });
            $(document).on('submit', '#moveToProjectForm', function (e) {
                e.preventDefault();
                let form = $(this);
                let btn = form.find('button[type="submit"]');
                let group_id = $('#moveToProjectInput').data('group-id');

                btn.disable();
                if (typeof group_id == "undefined" || group_id === "") {
                    btn.disable();
                    xagioNotify("danger", "Please select at least one group");
                    moveToProject[0].close();
                    return false;
                }

                $.post(xagio_data.wp_post, 'action=xagio_moveToProject&' + form.serialize() + '&group_id=' +
                                           group_id, function (d) {

                    btn.disable();

                    if (d.status == 'success') {
                        moveToProject[0].close();
                        actions.loadProjects();
                        actions.loadProjectManually();
                    }
                    xagioNotify(d.status, d.message);

                });


            });
            $(document).on('click', '.groupToProject', function (e) {

                let group_ids = [];
                $('.groupSelect:checked').each(function () {
                    let group = $(this).parents('.xagio-group');
                    group_ids.push(group.find('[name="group_id"]').val());
                });

                if (group_ids.length == 0) {
                    let group_id = $('#moveToProjectInput').data('group-id');

                    if (group_id !== undefined) {
                        group_ids.push(group_id);
                    }
                }

                if (group_ids.length == 0) {
                    xagioNotify("danger", "Please select at least one group");
                    return false;
                }

                let modal = $('#newProject');

                modal.find('.moveGroupsIds').val(group_ids);
                modal[0].showModal();
            });
        },
        consolidateKeywords          : function () {
            $('#phraseMatchModal')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('#group_name_phr').val('');
            });

            $(document).on('click', '.consolidateKeywords', function (e) {
                e.preventDefault();
                let consolidateModal = $('#consolidateModal');
                consolidateModal[0].showModal();
            });

            $(document).on('submit', '#consolidateForm', function (e) {
                e.preventDefault();
                let form = $(this);
                let btn = $(this).find('.consolidateBtn');
                let consolidateModal = $('#consolidateModal');
                btn.disable();

                $.post(xagio_data.wp_post, 'action=xagio_consolidateKeywords&' + form.serialize() + '&project_id=' + currentProjectID, function (d) {
                    btn.disable();
                    consolidateModal[0].close();

                    if (d.status === 'error') {
                        xagio_notify("danger", d.message);
                    } else {
                        xagioNotify("success", d.message);

                        if ($('#XAGIO_REMOVE_EMPTY_GROUPS').val() == 1) {
                            $.post(xagio_data.wp_post, 'action=xagio_deleteEmptyGroups&project_id=' + currentProjectID, function (d) {
                                xagioNotify("success", "Successfully deleted Empty groups.");
                                actions.loadProjectManually();
                            });
                        } else {
                            actions.loadProjectManually();
                        }
                    }
                });
            })

        },
        seedKeyword                  : function () {
            let phrase_match_labels = [
                `Phrase Match ( <span class="phrase-match-underline">cat</span>, <span class="phrase-match-underline">cat</span>s, <span class="phrase-match-underline">cat</span>apult, wild<span class="phrase-match-underline">cat</span> )`,
                `Phrase Match ( <span class="phrase-match-underline">cat</span> )`
            ];

            $(document).on('change', '.seed-word-match', function () {
                let checkbox = $(this);

                if (checkbox.prop('checked')) {
                    checkbox.next('.word_match_label').html(phrase_match_labels[1]);
                } else {
                    checkbox.next('.word_match_label').html(phrase_match_labels[0]);
                }
            });

            $(document).on('click', '.seedKeyword', function (e) {
                e.preventDefault();
                let group_id = $(this).data('group-id');
                let seedKeywordModal = $('#seedKeywordsModal');

                seedKeywordModal.find('input[name="group_id"]').val(group_id);
                seedKeywordModal[0].showModal();
            });

            $(document).on('submit', '#seedKeywordsForm', function (e) {
                e.preventDefault();

                let seedKeywordModal = $('#seedKeywordsModal');
                let form = $(this);
                let btn = form.find('.autoGenerateGroupsBtn');
                btn.disable();

                $.post(xagio_data.wp_post, 'action=xagio_seedKeywords&' + form.serialize() + '&project_id=' +
                                           currentProjectID, function (d) {

                    btn.disable();

                    if (d.status === 'error') {
                        xagioNotify("danger", d.message);
                    } else {
                        seedKeywordModal[0].close();
                        xagioNotify("success", d.message);
                        actions.loadProjectManually();
                    }

                });
            });
        },
        previewCluster               : function () {
            $(document).on('click', '.previewCluster', function (e) {
                e.preventDefault();

                let form = $(this).parents('#phraseMatchForm');
                let btn = $(this);
                let preview_panel = $('.cluster-preview');


                preview_panel.addClass('loading-cluster').html('Loading cluster preview <i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i>');

                btn.disable();

                $.post(xagio_data.wp_post, 'action=xagio_preview_phrasematch&' + form.serialize(), function (d) {
                    btn.disable();
                    let groups = d.data;
                    let groups_html = '';
                    for (const group_name in groups) {
                        let template_groups = $('.cluster_preview_template.template.hide').clone().removeClass('template').removeClass('hide');

                        template_groups.find('.cluster_group_name').html(group_name);
                        let keywords = '';
                        for (let i = 0; i < groups[group_name].length; i++) {
                            let keyword = groups[group_name][i];
                            keywords += `<div>${keyword}</div>`;
                        }
                        template_groups.find('.cluster_group_keywords').html(keywords);
                        groups_html += $.trim(template_groups.html());
                    }

                    preview_panel.removeClass('loading-cluster').html(groups_html);

                });

            });
        },
        phraseMatch                  : function () {
            $(document).on('change', '#cluster_in_new_project', function () {
                if ($(this).prop('checked')) {
                    $('.pm-project-name').slideDown();
                } else {
                    $('.pm-project-name').slideUp();
                }
            });

            $('#phraseMatchModal')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('.cluster-preview').empty();
                modal.find('#cluster_in_new_project').prop('checked', false);
                modal.find('.pm-project-name').hide();
            });

            $(document).on('click', '.phraseMatchSelectAll', function (e) {
                let el = $('.phrase_keyword').find('input');
                el.prop('checked', !el.prop('checked'));
            });
            $(document).on('click', '.phraseMatch', function (e) {
                e.preventDefault();
                let btn = $(this);
                let group_id = btn.data('group-id');

                let keywordContainer = $('.phraseMatchingKeywords'),
                    kwGroup1         = keywordContainer.find('.kw-group-1'),
                    kwGroup2         = keywordContainer.find('.kw-group-2');

                kwGroup1.empty();
                kwGroup2.empty();


                let allKeywords = $('.keywordInput[data-target="keyword"]');
                let allGroups = $('.project-groups .updateGroup input[name="group_id"]');
                let group_ids = [];
                if (group_id != "0") {
                    allKeywords = btn.parents('.xagio-group').find('.keywordInput[data-target="keyword"]');
                } else {
                    allGroups.each(function () {
                        group_ids.push($(this).val());
                    });

                    group_id = group_ids.join(',');
                }

                let keywords = [];


                allKeywords.each(function () {
                    let value = $(this).text().trim();
                    if (value != '') {
                        keywords.push(value);
                    }
                });

                // Get top 3 keywords based on weight
                let a = actions.calculateKeywordWeight(keywords);
                let sortedArr = a.sort(function (a, b) {
                    return b.weight - a.weight;
                });
                let top3 = sortedArr.slice(0, 3);
                let exclude_suggestion = '';

                for (let i = 0; i < top3.length; i++) {
                    if (top3[i].weight > 2) {
                        exclude_suggestion += top3[i].text + ',';
                    }
                }
                exclude_suggestion = exclude_suggestion.slice(0, -1);

                if (keywords.length == 0) {
                    xagioNotify("danger", "Please add some keywords first before trying to Cluster!");
                    return;
                }

                keywords.sort();

                let groupSplit = Math.ceil(keywords.length / 2);

                for (let i = 0; i < keywords.length; i++) {
                    let keyword = keywords[i];
                    if (i >= groupSplit) {
                        kwGroup2.append('<label class="phrase_keyword"><input checked type="checkbox" class="xagio-input-checkbox xagio-input-checkbox-mini" name="keywords[]" value="' +
                                        keyword + '"/> ' + keyword + '</label>');
                    } else {
                        kwGroup1.append('<label class="phrase_keyword"><input checked type="checkbox" class="xagio-input-checkbox xagio-input-checkbox-mini" name="keywords[]" value="' +
                                        keyword + '"/> ' + keyword + '</label>');
                    }
                }


                let phraseMatch = $('#phraseMatchModal');
                phraseMatch.find('#excluded_words').val(exclude_suggestion);
                phraseMatch.find('input[name="group_id"]').val(group_id);

                phraseMatch[0].showModal();

                let form = phraseMatch.find('#phraseMatchForm');
                let cluster_btn = form.find('.previewCluster');
                let preview_panel = $('.cluster-preview');


                preview_panel.addClass('loading-cluster').html('Loading cluster preview <i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i>');

                cluster_btn.disable();

                $.post(xagio_data.wp_post, 'action=xagio_preview_phrasematch&' + form.serialize(), function (d) {
                    cluster_btn.disable();
                    let groups = d.data;
                    let groups_html = '';
                    for (const group_name in groups) {
                        let template_groups = $('.cluster_preview_template.template.hide').clone().removeClass('template').removeClass('hide');

                        template_groups.find('.cluster_group_name').html(group_name);
                        let keywords = '';
                        for (let i = 0; i < groups[group_name].length; i++) {
                            let keyword = groups[group_name][i];
                            keywords += `<div>${keyword}</div>`;
                        }
                        template_groups.find('.cluster_group_keywords').html(keywords);
                        groups_html += $.trim(template_groups.html());
                    }

                    preview_panel.removeClass('loading-cluster').html(groups_html);

                });

            });

            $(document).on('click', '.cluster-accordion-title', function (e) {
                if ($(this).hasClass('open')) {
                    $(this).removeClass('open');
                    $(this).find('i').removeClass().addClass('xagio-icon xagio-icon-arrow-up');
                    $('.clustering-keywords').slideUp();
                } else {
                    $(this).addClass('open');
                    $(this).find('i').removeClass().addClass('xagio-icon xagio-icon-arrow-down');
                    $('.clustering-keywords').slideDown();
                }
            });

            $(document).on('submit', '#phraseMatchForm', function (e) {
                e.preventDefault();

                let phraseMatch = $('#phraseMatchModal');
                let form = $(this);
                let btn = form.find('.autoGenerateGroupsBtn');
                btn.disable();

                $.post(xagio_data.wp_post, 'action=xagio_phraseMatch&' + form.serialize() + '&project_id=' +
                                           currentProjectID, function (d) {

                    phraseMatch[0].close();

                    if (d.status == 'error') {
                        xagioNotify("danger", d.message);
                    } else {

                        nextProjectName = d.data.name;
                        nextProjectID = d.data.id;
                        currentProjectID = nextProjectID;
                        currentProjectName = nextProjectName;
                        actions.loadProjectManually();
                        actions.loadProjects();

                        xagioNotify("success", d.message);

                        if (activeChanges) {
                            $('.saveProject').trigger('click');
                        }
                    }

                    btn.disable();

                });

            });
        },
        auditWebsite                 : function () {
            $(document).on('change keyup paste', '#auditWebsite_domain', function () {
                let domain = $(this);
                let currentDomain = domain.data('host');

                if (currentDomain.indexOf(domain.val()) < 0) {
                    $('#auditWebsite_ignoreLocal').attr('checked', true);
                } else {
                    $('#auditWebsite_ignoreLocal').attr('checked', false);
                }
            });

            $(document).on('click', '#auditWebsite_trackKeywords', function (e) {

                let selectHolder = $('.auditWebsite_rankHolder');

                if (selectHolder.hasClass('open')) {
                    selectHolder.removeClass('open').hide();
                } else {
                    selectHolder.addClass('open').show();
                }

            });

            $('#auditWebsite_searchEngine').select2({
                matcher           : matcher,
                placeholder       : "Select a Search Engine"
            });

            /**
             *  Migrate Yoast
             */
            $(document).on('click', '.migration-yoast', function (e) {
                e.preventDefault();
                var btn = $(this);
                btn.disable('Working ...');
                $.post(xagio_data.wp_post, 'action=xagio_migrate_yoast', function (d) {
                    btn.disable();
                    xagioNotify("success", "Yoast data successfully migrated.");
                });
            });

            /**
             *  Migrate RankMath
             */
            $(document).on('click', '.migration-rankmath', function (e) {
                e.preventDefault();
                var btn = $(this);
                btn.disable('Working ...');
                $.post(xagio_data.wp_post, 'action=xagio_migrate_rankmath', function (d) {
                    btn.disable();
                    xagioNotify("success", "RankMath SEO data successfully migrated.");
                });
            });

            /**
             *  Migrate AIO
             */
            $(document).on('click', '.migration-aio', function (e) {
                e.preventDefault();
                var btn = $(this);
                btn.disable('Working ...');
                $.post(xagio_data.wp_post, 'action=xagio_migrate_aio', function (d) {
                    btn.disable();
                    xagioNotify("success", "AIO data successfully migrated.");
                });
            });

            let auditModal = $('#auditWebsiteModal');
            let auditModalInternal = $('#auditWebsiteModalInternal');

            $('#auditWebsite_limit').select2({
                dropdownParent: auditModal,
                width: '100%',
                placeholder   : "Select Limit"
            });

            $('#auditWebsite_limit-internal').select2({
                dropdownParent: auditModalInternal,
                width: '100%',
                placeholder   : "Select Limit"
            });

            $(document).on('click', '.auditWebsiteMigration', function (e) {
                e.preventDefault();

                if (!xagio_data.connected) {
                    xagioConnectModal();
                    return;
                }


                let btn = $(this);
                let audit_type = btn.data('modal');
                let audit_button = $('.auditWebsite');

                audit_button.data('target', audit_type);

                if ($('.migration-visible').length < 1) {
                    let auditWebsite = document.getElementById('auditWebsiteModal');


                    if (audit_type === 'internal') {
                        auditWebsite = document.getElementById('auditWebsiteModalInternal');
                    }

                    auditWebsite.showModal();

                } else {
                    let migrationModal = document.getElementById('migrationModal');
                    migrationModal.showModal();
                }

            });

            $(document).on('click', '.auditWebsite', function (e) {
                e.preventDefault();

                if (!xagio_data.connected) {
                    xagioConnectModal();
                    return;
                }

                // close this modal
                let migrationModal = $('#migrationModal');
                migrationModal[0].close();

                let auditWebsite = $('#auditWebsiteModal');

                if ($(this).data('target') === 'internal') {
                    auditWebsite = $('#auditWebsiteModalInternal');
                    $(this).data('target', 'external');
                }

                auditWebsite[0].showModal();
            });

            $(document).on('change', '#auditWebsite_lang', function (e) {
                e.preventDefault();
                $('#auditWebsite_langCode').val($(this).find('option:selected').attr('data-lang-code'));
            });

            $(document).on('change', '#auditWebsite_lang_internal', function (e) {
                e.preventDefault();
                $('#auditWebsite_langCode_internal').val($(this).find('option:selected').attr('data-lang-code'));
            });

            $(document).on('submit', '#auditWebsiteForm', function (e) {
                e.preventDefault();

                let auditWebsite = $('#auditWebsiteModal');
                let form = $(this);
                let btn = $(this).find('.auditWebsiteBtn');
                btn.disable();

                let balance = parseInt(actions.allowances.xags_allowance.find('.value').html()) + parseInt(actions.allowances.xags.find('.value').html());

                if (balance < 1) {

                    xagioNotify("danger", "You do not have enough XAGS to continue this operation. Get more by vising Shop on Xagio.net");

                    btn.disable();
                    return;
                }

                $.post(xagio_data.wp_post, 'action=xagio_auditWebsite&type=Audit&' + form.serialize(), function (d) {

                    btn.disable();
                    auditWebsite[0].close();

                    if (d.status == 'success') {

                        currentProjectID = d.data;
                        actions.loadProjectManually();

                        actions.refreshXags();
                        // balance.html(parseInt(balance.html()) - 1);
                        actions.loadProjects();
                    }

                    xagioNotify(d.status, d.message);

                });

            });

            $(document).on('submit', '#auditWebsiteInternalForm', function (e) {
                e.preventDefault();

                let auditWebsite = $('#auditWebsiteModalInternal');
                let form = $(this);
                let btn = $(this).find('.auditWebsiteBtn');
                btn.disable();

                let balance = parseInt(actions.allowances.xags_allowance.find('.value').html()) + parseInt(actions.allowances.xags.find('.value').html());

                if (balance < 1) {

                    xagioNotify("danger", "You do not have enough XAGS to continue this operation. Get more by vising Shop on Xagio.net");

                    btn.disable();
                    return;
                }

                $.post(xagio_data.wp_post, 'action=xagio_auditWebsite&type=Audit&' + form.serialize() +
                                           `&project_id=${currentProjectID}`, function (d) {

                    btn.disable();

                    auditWebsite[0].close();

                    if (d.status == 'success') {

                        currentProjectID = d.data;
                        actions.loadProjectManually();

                        balance.html(parseInt(balance.html()) - 1);
                        actions.loadProjects();
                    }

                    xagioNotify(d.status, d.message);

                });

            });

        },
        refreshXags                  : function () {
            $.post(xagio_data.wp_post, 'action=xagio_refreshXags', function (d) {
                if (d.status == 'error') {

                    actions.allowances.xags.find('.value').html(0);
                    actions.allowances.xags_allowance.find('.value').html(0);

                } else {

                    actions.allowances.xags_allowance.find('.value').html(parseFloat(d.data.xags_allowance).toFixed(2));

                    if(d.data['xags'] > 0) {
                        actions.allowances.xags.find('.value').html(parseFloat(d.data.xags).toFixed(2));
                    } else {
                        actions.allowances.xags.find('.value').html(0);
                        actions.allowances.xags.hide();
                    }
                    actions.allowances.cost = d.data.xags_cost;
                    actions.allowances.xags_total = d.data.xags_total;

                    $("#auditWebsiteInternalForm").find("#auditCostImport").text(actions.allowances.cost.audits);
                    $("#auditWebsiteForm").find("#auditCost").text(actions.allowances.cost.audits);
                }
            });
        },
        getAi                        : function () {
            $(document).on('click', '.get-ai', function (e) {
                e.preventDefault();
                // open a new tab to https://xagio.net/ai
                window.open('https://xagio.net/ai', '_blank');
            });
        },
        retrieveVolumeAndCPC         : function () {

            $(document).on('click', '.getVolumeAndCPC', function (e) {
                e.preventDefault();

                if (!xagio_data.connected) {
                    xagioConnectModal();
                    return;
                }

                let group = $(this).parents('.xagio-group');
                let ids = [];
                let keywords = [];
                let btn = $(this);
                let type = btn.data('type');
                let icon = '<i class="xagio-icon xagio-icon-sync xagio-icon-spin" title="This value is currently under analysis. Please wait until results are gathered."></i>';


                if (type === 'all') {
                    if ($('.xagio-refresh-vol-cpc-values').hasClass('hide')) {
                        $('.xagio-refresh-vol-cpc-values').removeClass('hide');
                    }
                    if ($('#XAGIO_REFRESH_VOL_CPC_VALUES').val() === '0') {
                        $('.keyword-selection').each(function () {
                            let tr = $(this).parents('tr');
                            $(this).removeAttr('checked');
                            let kw = tr.find('.keywordInput[data-target="keyword"]').text().trim();
                            let volume = tr.find('.keywordInput[data-target="volume"]').text().trim();
                            let cpc = tr.find('.keywordInput[data-target="cpc"]').text().trim();
                            let id = tr.data('id');

                            if (kw != '') {
                                if(volume == "" || cpc == "") {
                                    ids.push(id);
                                    keywords.push(kw);
                                }
                            }
                        });
                    } else  {
                        $('.keyword-selection').each(function () {
                            let tr = $(this).parents('tr');
                            $(this).removeAttr('checked');
                            let kw = tr.find('.keywordInput[data-target="keyword"]').text().trim();
                            let id = tr.data('id');

                            if (kw != '') {
                                ids.push(id);
                                keywords.push(kw);
                            }
                        });
                    }
                } else {
                    $('.xagio-refresh-vol-cpc-values').addClass('hide');
                    group.find('.keyword-selection').each(function () {
                        let tr = $(this).parents('tr');
                        if ($(this).is(':checked')) {
                            $(this).removeAttr('checked');
                            let kw = tr.find('.keywordInput[data-target="keyword"]').text().trim();
                            let id = tr.data('id');
                            if (kw != '') {
                                ids.push(id);
                                keywords.push(kw);
                            }
                        }
                    });
                    if (keywords.length < 1) {
                        xagioNotify("danger", "Please select some keywords!");
                        return false;
                    }
                }

                actions.volAndCpcProgressBar(keywords);

                let vol_cpc_modal = $('#VolumeAndCPCModal');
                vol_cpc_modal.find('input[name="ids"]').val(ids.join(','));
                vol_cpc_modal.find('input[name="keywords"]').val(keywords.join(','));

                $('#getVolAndCpc_languageCode').select2({
                    matcher       : matcher,
                    dropdownParent: vol_cpc_modal,
                    placeholder   : "Select Language"
                });

                $('#getVolAndCpc_locationCode').select2({
                    matcher       : matcher,
                    dropdownParent: vol_cpc_modal,
                    placeholder   : "Select Country"
                });

            });

            $(document).on('click', '.vol-cpc-kw-select', function () {
                let checkbox = $(this);
                let keyword_id = checkbox.data('value');
                let keyword = checkbox.data('keyword');
                let modal = checkbox.parents('#VolumeAndCPCModal');

                let keyword_ids_input = modal.find('#ids')
                let keyword_names_input = modal.find('#keywords')
                let keyword_ids = keyword_ids_input.val();
                let keyword_names = keyword_names_input.val();
                keyword_ids = keyword_ids.split(',');
                keyword_names = keyword_names.split(',');

                if (jQuery.inArray(keyword_id.toString(), keyword_ids) !== -1) {
                    keyword_ids.splice($.inArray(keyword_id.toString(), keyword_ids), 1);
                } else {
                    keyword_ids.push(keyword_id);
                }

                if (jQuery.inArray(keyword.toString(), keyword_names) !== -1) {
                    keyword_names.splice($.inArray(keyword.toString(), keyword_names), 1);
                } else {
                    keyword_names.push(keyword);
                }

                modal.find('.keyword_volume_cost').html(keyword_ids.length);
                modal.find('.progress-keywords-volume span:last-child').html(`-${keyword_ids.length}`);

                keyword_ids_input.val(keyword_ids.join(','));
                keyword_names_input.val(keyword_names.join(','));

            });


            $('#VolumeAndCPCModal').on({
                'hide.uk.modal': function () {
                    let vol_cpc_modal = $('#VolumeAndCPCModal');
                    vol_cpc_modal.find('.tagsinput').remove();
                }
            });

        },

        refreshVolAndCpcValues: function () {
            $(document).on('change', "#XAGIO_REFRESH_VOL_CPC_VALUES", function() {
                let ids = [];
                let keywords = [];
                let btn = $('.getVolumeAndCPC');
                let type = btn.data('type');

                if (type === 'all') {
                    if ($('#XAGIO_REFRESH_VOL_CPC_VALUES').val() === '0') {
                        $('.keyword-selection').each(function () {
                            let tr = $(this).parents('tr');
                            $(this).removeAttr('checked');
                            let kw = tr.find('.keywordInput[data-target="keyword"]').text().trim();
                            let volume = tr.find('.keywordInput[data-target="volume"]').text().trim();
                            let cpc = tr.find('.keywordInput[data-target="cpc"]').text().trim();
                            let id = tr.data('id');

                            if (kw != '') {
                                if(volume == "" || cpc == "") {
                                    ids.push(id);
                                    keywords.push(kw);
                                }
                            }
                        });
                    } else  {
                        $('.keyword-selection').each(function () {
                            let tr = $(this).parents('tr');
                            $(this).removeAttr('checked');
                            let kw = tr.find('.keywordInput[data-target="keyword"]').text().trim();
                            let id = tr.data('id');

                            if (kw != '') {
                                ids.push(id);
                                keywords.push(kw);
                            }
                        });
                    }
                }

                actions.volAndCpcProgressBar(keywords);

                let vol_cpc_modal = $('#VolumeAndCPCModal');
                vol_cpc_modal.find('input[name="ids"]').val(ids.join(','));
                vol_cpc_modal.find('input[name="keywords"]').val(keywords.join(','));
            })
        },

        volAndCpcProgressBar: function (keywords) {
            let vol_cpc_modal = $('#VolumeAndCPCModal');
            let vol_cpc_cost = keywords.length * actions.allowances.cost.vol_cpc;
            vol_cpc_cost = vol_cpc_cost.toFixed(2);
            vol_cpc_modal.find('.keyword_competition_cost_text').hide();
            vol_cpc_modal.find('.keyword_volume_cost').html(vol_cpc_cost);

            let current_volume_credits = parseFloat(actions.allowances.xags_allowance.find('.value').html()) + parseFloat(actions.allowances.xags.find('.value').html());

            let credits_left = (current_volume_credits - vol_cpc_cost).toFixed(2);
            let total_percent = current_volume_credits * 100 / current_volume_credits;
            let deduct_percent = (actions.allowances.xags_total - (current_volume_credits - vol_cpc_cost)) * 100 / actions.allowances.xags_total;
            let diff = (total_percent - deduct_percent) * 100 / total_percent;

            if (total_percent < 23) total_percent = 23;

            let max = 74;
            if (vol_cpc_cost < 10) {
                max = 88;
            } else if (vol_cpc_cost < 100) {
                max = 85;
            }

            if (diff < 22) diff = 22;
            if (diff > max) diff = max;

            if (credits_left <= 0) {
                credits_left = '< 0';
                diff = 0;
            }

            let vol_left_per = credits_left * 100 / current_volume_credits;
            let progress_color = '#1dbf6a';
            if (vol_left_per < 50) progress_color = '#fcb963';
            if (vol_left_per < 25) progress_color = '#fb1f36';

            vol_cpc_modal.find('.progress-keywords-volume').css('width', total_percent + '%').html(`<span>${credits_left}</span><span>-${vol_cpc_cost}</span>`);
            vol_cpc_modal.find('.progress-keywords-volume').css('background', `linear-gradient(to right, ${progress_color} ${diff}%, #fb1f36 0%)`);
            vol_cpc_modal.find('.progress-keywords-volume').next().find('span').html(current_volume_credits);

            vol_cpc_modal[0].showModal();
        },

        submitKeywordsForGetVolAndCPC: function () {
            $(document).on('submit', '#VolumeAndCPCForm', function (e) {
                e.preventDefault();

                let btn = $(this).find('.submitKeywords');
                btn.disable();

                let vol_cpc_modal = $('#VolumeAndCPCModal');

                let dataArray = $(this).serializeArray();

                let len = dataArray.length;
                let dataObj = {};

                for (let i = 0; i < len; i++) {
                    dataObj[dataArray[i].name] = dataArray[i].value;
                }

                if (dataObj['language'] == '0000') {
                    xagioNotify("danger", "Please select a language!");
                    e.stopImmediatePropagation();
                    btn.disable();
                    return false;
                }
                if (dataObj['keywords'] == '') {
                    xagioNotify("danger", "All keywords have Volume and CPC. Click on refresh button to refresh values.");
                    e.stopImmediatePropagation();

                    btn.disable();
                    return false;
                }
                if (dataObj['ids'] == '') {
                    xagioNotify("danger", "Something went wrong please select keywords again!");
                    e.stopImmediatePropagation();
                    btn.disable();
                    return false;
                }

                let data = [
                    {
                        name : 'action',
                        value: 'xagio_getVolumeAndCPC'
                    },
                    {
                        name : 'ids',
                        value: dataObj['ids']
                    },
                    {
                        name : 'keywords',
                        value: dataObj['keywords']
                    },
                    {
                        name : 'language',
                        value: dataObj['language']
                    },
                    {
                        name : 'location',
                        value: dataObj['location']
                    },
                    {
                        name : 'disable_cache',
                        value: dataObj['disable_cache']
                    }
                ];

                // Send them for analysis
                $.postq('keywordApi', xagio_data.wp_post, data, function (d) {
                    btn.disable();
                    vol_cpc_modal[0].close()
                    actions.refreshXags();
                    actions.runVolCPCBatchCron();

                    let ids = dataObj['ids'].split(",");

                    if (d.status == 'success') {
                        for (let i = 0; i < ids.length; i++) {
                            let k = ids[i];
                            let tr = $('tr[data-id="' + k + '"]');

                            tr.find('.keywordInput[data-target="volume"]').attr('title', 'This value is currently under analysis. Please check back later to see the results.').html('<i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i>');
                            tr.find('.keywordInput[data-target="cpc"]').attr('title', 'This value is currently under analysis. Please check back later to see the results.').html('<i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i>');
                        }

                        xagio_notify("success","You have successfully queued selected keywords for analysis. You will receive an e-mail when the analysis is completed, or you can simply just check back later for results.");

                    } else if (d.status == 'results') {

                        for (let i = 0; i < d.data.length; i++) {
                            let k = d.data[i];
                            let tr = $('tr[data-id="' + k.id + '"]');

                            let volume_color = '';

                            if (k.search_volume == 0) {
                                volume_color = 'tr_green';
                            } else if (parseFloat(cf_template.volume_red) >= parseInt(k.search_volume)) {
                                volume_color = 'tr_red';
                            } else if (parseFloat(cf_template.volume_red) < parseInt(k.search_volume) &&
                                       parseInt(cf_template.volume_green) > parseInt(k.search_volume)) {
                                volume_color = 'tr_yellow';
                            } else if (parseFloat(cf_template.volume_green) <= parseInt(k.search_volume)) {
                                volume_color = 'tr_green';
                            }

                            let cpc_color = '';
                            if (k.cost_per_click == 0) {
                                cpc_color = 'tr_green';
                            } else if (parseFloat(cf_template.cpc_red) >= parseFloat(k.cost_per_click)) {
                                cpc_color = 'tr_red';
                            } else if (parseFloat(cf_template.cpc_red) < parseFloat(k.cost_per_click) &&
                                       parseFloat(cf_template.cpc_green) > parseFloat(k.cost_per_click)) {
                                cpc_color = 'tr_yellow';
                            } else if (parseFloat(cf_template.cpc_green) <= parseFloat(k.cost_per_click)) {
                                cpc_color = 'tr_green';
                            }

                            tr.find('.keywordInput[data-target="volume"]').html(parseInt(k.search_volume).toLocaleString()).removeClass('xagio-text-center').parents('td').addClass(volume_color);
                            tr.find('.keywordInput[data-target="cpc"]').html(k.cost_per_click).removeClass('xagio-text-center').parents('td').addClass(cpc_color);
                        }

                        xagioNotify("success", d.message);

                    } else if (d.status == 'error') {
                        xagioNotify("danger", d.message);
                    }
                });
            });
        },
        copyKeywords                 : function () {

            $(document).on('click', '.copyKeywordsButton', function (e) {
                e.preventDefault();

                copyTextToClipboard($('#copiedKeywords').val());

                xagioNotify("success", "Keywords are successfully copied to your clipboard.");

                $("#copyKeywords")[0].close();
            });
            $(document).on('click', '.copyKeywords', function (e) {
                e.preventDefault();

                let group = $(this).parents('.xagio-group');
                let keywords = [];

                if (group.find('.keyword-selection:checked').length < 1) {

                    group.find('.keywordInput[data-target="keyword"]').each(function () {
                        keywords.push($(this).text().trim());
                    });

                } else {

                    group.find('.keyword-selection:checked').each(function () {
                        let tr = $(this).parents('tr');
                        let kw = tr.find('.keywordInput[data-target="keyword"]').html().trim();
                        keywords.push(kw);
                    });

                }

                keywords = keywords.join("\r\n");

                $('#copiedKeywords').val(keywords);

                $("#copyKeywords")[0].showModal();
            });
        },
        retrieveKeywordData          : function () {
            $(document).on('click', '.getKeywordData', function (e) {
                e.preventDefault();

                let group = $(this).parents('.xagio-group');
                let ids = [];
                let keywords = [];
                let btn = $(this);
                let type = btn.data('type');
                let competition_modal = $('#getCompetitionModal');

                if (!xagio_data.connected) {
                    xagioConnectModal();
                    return;
                }

                if (type === 'all') {
                    if ($('.xagio-refresh-competition-values').hasClass('hide')) {
                        $('.xagio-refresh-competition-values').removeClass('hide');
                    }

                    if ($('#XAGIO_REFRESH_COMPETITION_VALUES').val() === '0') {
                        $('.keyword-selection').each(function () {
                            let tr = $(this).parents('tr');
                            if (tr.data('queued') != 1) {
                                $(this).removeAttr('checked');
                                let kw = tr.find('.keywordInput[data-target="keyword"]').html().trim();
                                let inTitle = tr.find('.keywordInput[data-target="intitle"]').html().trim();
                                let inUrl = tr.find('.keywordInput[data-target="inurl"]').html().trim();
                                let id = tr.data('id');
                                if (kw != '') {
                                    if(inTitle == "" || inUrl == "") {
                                        ids.push(id);
                                        keywords.push(kw);
                                    }
                                }
                            }
                        });
                    } else  {
                        $('.keyword-selection').each(function () {
                            let tr = $(this).parents('tr');
                            if (tr.data('queued') != 1) {
                                $(this).removeAttr('checked');
                                let kw = tr.find('.keywordInput[data-target="keyword"]').html().trim();
                                let id = tr.data('id');
                                if (kw != '') {
                                    ids.push(id);
                                    keywords.push(kw);
                                }
                            }
                        });
                    }
                } else {
                    $('.xagio-refresh-competition-values').addClass('hide');

                    group.find('.keyword-selection').each(function () {
                        let tr = $(this).parents('tr');
                        if ($(this).is(':checked') && tr.data('queued') != 1) {
                            $(this).removeAttr('checked');
                            let kw = tr.find('.keywordInput[data-target="keyword"]').html().trim();
                            let id = tr.data('id');
                            if (kw != '') {
                                ids.push(id);
                                keywords.push(kw);
                            }
                        }
                    });

                    if (keywords.length < 1) {
                        xagioNotify("danger", "Please select some keywords");
                        return false;
                    }
                }

                actions.competitionProgressBar(keywords);

                competition_modal.find('#keywords_cmp').val(keywords.join(','));
                competition_modal.find('#ids_cmp').val(ids.join(','));

                $('#getCompetition_languageCode').select2({
                    matcher       : matcher,
                    dropdownParent: competition_modal,
                    placeholder   : "Select Language"
                });

                $('#getCompetition_locationCode').select2({
                    matcher       : matcher,
                    dropdownParent: competition_modal,
                    placeholder   : "Select Country"
                });

            });

            $(document).on('submit', '#getCompetitionForm', function (e) {
                e.preventDefault();

                let kwallow = parseInt(actions.allowances.xags_allowance.find('.value').html().trim()) + parseInt(actions.allowances.xags.find('.value').html().trim());
                let form = $(this);
                let btn = form.find('.submitCompetitionKeywords');
                btn.disable();
                let modal = form.parents('.xagio-modal');
                let ids = form.find('#ids_cmp').val();
                let keywords = form.find('#keywords_cmp').val();

                if (keywords == "") {
                    xagioNotify("danger", "All keywords have Competition value. Please click on refresh keywords to refresh values.");
                    btn.disable();
                    return;
                }

                ids = ids.split(',');
                keywords = keywords.split(',');

                let cost = parseInt(actions.allowances.cost.comp) * keywords.length;

                if (cost > kwallow) {
                    xagioNotify("danger", "You do not have enough XAGS.");
                    btn.disable();
                    return;
                }

                let data = [
                    {
                        name : 'action',
                        value: 'xagio_getKeywordData'
                    },
                    {
                        name : 'ids',
                        value: ids
                    },
                    {
                        name : 'keywords',
                        value: keywords
                    },
                    {
                        name : 'language',
                        value: $('#getCompetition_languageCode').val()
                    },
                    {
                        name : 'location',
                        value: $('#getCompetition_locationCode').val()
                    }
                ];

                // Send them for analysis
                $.post(xagio_data.wp_post, data, function (d) {
                    xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                    if (d.status == 'success') {
                        actions.refreshXags();
                        actions.runBatchCron();
                        for (let i = 0; i < ids.length; i++) {
                            let id = ids[i];
                            let el = $('.keyword-selection[value="' + id + '"]');
                            let tr = el.parents('tr');
                            tr.attr('data-queued', 1);
                            let tm = '<i class="xagio-icon xagio-icon-sync xagio-icon-spin" title="This value is currently under analysis. Please check back later to see the results."></i>';
                            let values = [
                                '.keywordInput[data-target="intitle"]',
                                '.keywordInput[data-target="inurl"]'
                            ];
                            for (let z = 0; z < values.length; z++) {
                                let td = tr.find(values[z]).parent();
                                td.removeClass();
                                td.addClass('xagio-text-center');
                                td.html(tm);
                            }
                        }
                    }

                    btn.disable();
                    modal[0].close();
                });

            });

            $('#getCompetitionModal')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('#ids_cmp').val('');
                modal.find('#keywords_cmp').val('');
            });

        },

        refreshCompetitionValues: function () {
            $(document).on('change', "#XAGIO_REFRESH_COMPETITION_VALUES", function() {
                let ids = [];
                let keywords = [];
                let btn = $('.getKeywordData');
                let type = btn.data('type');

                if (type === 'all') {
                    if ($('#XAGIO_REFRESH_COMPETITION_VALUES').val() === '0') {
                        $('.keyword-selection').each(function () {
                            let tr = $(this).parents('tr');
                            if (tr.data('queued') != 1) {
                                $(this).removeAttr('checked');
                                let kw = tr.find('.keywordInput[data-target="keyword"]').html().trim();
                                let inTitle = tr.find('.keywordInput[data-target="intitle"]').html().trim();
                                let inUrl = tr.find('.keywordInput[data-target="inurl"]').html().trim();
                                let id = tr.data('id');
                                if (kw != '') {
                                    if(inTitle == "" || inUrl == "") {
                                        ids.push(id);
                                        keywords.push(kw);
                                    }
                                }
                            }
                        });
                    } else  {
                        $('.keyword-selection').each(function () {
                            let tr = $(this).parents('tr');
                            if (tr.data('queued') != 1) {
                                $(this).removeAttr('checked');
                                let kw = tr.find('.keywordInput[data-target="keyword"]').html().trim();
                                let id = tr.data('id');
                                if (kw != '') {
                                    ids.push(id);
                                    keywords.push(kw);
                                }
                            }
                        });
                    }
                }

                actions.competitionProgressBar(keywords);

                let competition_modal = $('#getCompetitionModal');
                competition_modal.find('#keywords_cmp').val(keywords.join(','));
                competition_modal.find('#ids_cmp').val(ids.join(','));
            })
        },

        competitionProgressBar: function (keywords) {
            let competition_modal = $('#getCompetitionModal');

            let competition_cost = keywords.length * actions.allowances.cost.comp;
            competition_cost = competition_cost.toFixed(2);

            let current_scraping_credits = parseFloat(actions.allowances.xags_allowance.find('.value').html()) + parseFloat(actions.allowances.xags.find('.value').html());
            current_scraping_credits = current_scraping_credits.toFixed(2);
            let credits_left = current_scraping_credits - competition_cost;
            credits_left = credits_left.toFixed(2);
            let total_percent = current_scraping_credits * 100 / current_scraping_credits;
            let deduct_percent = (actions.allowances.xags_total - (current_scraping_credits - competition_cost)) * 100 / actions.allowances.xags_total;
            let diff = (total_percent - deduct_percent) * 100 / total_percent;

            if (total_percent < 15) total_percent = 15;

            let max = 92;
            if (competition_cost < 10) {
                max = 95;
            } else if (competition_cost < 100) {
                max = 94;
            }

            if (diff < 6) diff = 6;
            if (diff > max) diff = max;

            if (credits_left <= 0) {
                credits_left = '< 0';
                diff = 0;
            }

            let vol_left_per = credits_left * 100 / actions.allowances.xags_total;
            let progress_color = '#1acb87';
            if (vol_left_per < 50) progress_color = '#f28e36';
            if (vol_left_per < 25) progress_color = '#fd1f36';

            competition_modal.find('.keyword_competition_cost').html(competition_cost);
            competition_modal.find('.progress-keywords-scrape').css('width', total_percent + '%').html(`<span>${credits_left}</span><span>-${competition_cost}</span>`);
            competition_modal.find('.progress-keywords-scrape').css('background', `linear-gradient(to right, ${progress_color} ${diff}%, #fd1f36 0%)`);
            competition_modal.find('.progress-keywords-scrape').next().find('span').html(current_scraping_credits);

            competition_modal[0].showModal();
        },

        /*CF Templates*/
        loadCfTemplates              : function () {
            $.post(xagio_data.wp_post, 'action=xagio_getCfTemplates', function (d) {
                if (d.status == 'success') {
                    cf_templates = $.extend(cf_templates, d.data)
                }

                let template = cf_templates[d.default];

                // Set default template globally
                cf_template = template.data;
                cf_default_template = d.default;

                let template_names = '';
                for (let key in cf_templates) {
                    if (key == d.default) {
                        // Star if it's default template
                        template_names += '<option value="' + key + '">' + key + ' *</option>';
                    } else {
                        template_names += '<option value="' + key + '">' + key + '</option>';
                    }
                }
                $('#cf-templates').html(template_names);

                $('#cf-templates').val(template.name);
                for (let key in template.data) {
                    $('#' + key).val(template.data[key]);
                    $('.' + key).val(template.data[key]);
                }
            }, 'json');

        },
        changeCfTemplate             : function () {
            $("#cf-templates").change(function () {
                let templateName = $(this).val();

                for (let key in cf_templates[templateName].data) {
                    $('#' + key).val(cf_templates[templateName].data[key]);
                    $('.' + key).val(cf_templates[templateName].data[key]);
                }

            });
        },
        saveCfTemplate               : function () {
            $(document).on('click', '#saveCfTemplate', function (e) {
                // Disable button to prevent multiple sending
                let btn = $(this);
                btn.disable();
                e.preventDefault();

                let data = $('#conditional-formatting-local-form').serialize();

                let selected_template = $('#cf-templates').val();

                $.post(xagio_data.wp_post, 'action=xagio_saveCfTemplate&' + data + '&name=' +
                                           selected_template, function (d) {
                    xagioNotify(d.status, d.message);
                    // Update CF Templates data
                    cf_templates = d.data;
                    cf_template = cf_templates[cf_default_template].data;
                    // When saving is done, enable button again
                    btn.disable();
                }, 'json');

            });
        },
        addCfTemplate: function() {
            $(document).on('click', '#addCfTemplate', function (e) {
                let btn = $(this);
                btn.attr('disabled', true);
                e.preventDefault();

                let data = $('#conditional-formatting-local-form').serialize();

                xagioPromptModal("Confirm", `<span style="font-size: 20px;"><i class="xagio-icon xagio-icon-save"></i> Please enter name for new template:</span>`, function (result) {

                    if (result) {
                        let new_name = result;

                        if (new_name.length < 1) {
                            xagioNotify("danger", "Please enter a name for new template!");
                            btn.attr('disabled', false);
                            return false;
                        }

                        $.post(xagio_data.wp_post, 'action=xagio_createCfTemplate&' + data + '&name=' +
                            new_name, function (d) {

                            btn.attr('disabled', false);
                            $('#applyCfTemplate').attr('disabled', false);
                            if (d.status == 'error') {
                                xagioNotify("danger", d.message);
                                return false;
                            } else {
                                xagioNotify(d.status, d.message);
                            }
                            // Update CF Templates data
                            actions.loadCfTemplates();

                        }, 'json');
                    } else {
                        btn.attr('disabled', false);
                    }
                });
            })
        },
        applyCfTemplate              : function () {
            $(document).on('click', '#applyCfTemplate', function (e) {

                e.preventDefault();
                let btn = $(this);
                // btn.attr('disabled', true);
                btn.disable();

                $.post(xagio_data.wp_post, 'action=xagio_applyCfTemplate&templateName=' +
                                           $('#cf-templates').val(), function (d) {
                    xagioNotify(d.status, d.message);
                    // When saving is done, enable button again
                    // btn.attr('disabled', false);
                    btn.disable();
                    actions.loadCfTemplates();
                    actions.loadProjectManually();
                }, 'json');
            })
        },
        deleteCfTemplate             : function () {
            $(document).on('click', '#deleteCfTemplate', function (e) {
                e.preventDefault();
                let btn = $(this);
                let template_name = $('#cf-templates').val();

                if (template_name === "Default" || template_name === "Affiliate" || template_name === "Local") {
                    xagioNotify("danger", "You cannot delete XAGIO default conditional formatting templates");
                    return;
                }

                btn.disable();

                xagioModal("Are you sure?", "Are you sure that you want to delete selected template?", function (yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, 'action=xagio_deleteCfTemplate&templateName=' +
                            template_name, function (d) {
                            xagioNotify(d.status, d.message);
                            // When saving is done, enable button again
                            btn.disable();
                            actions.loadCfTemplates();
                        }, 'json');
                    } else {
                        btn.disable();
                    }
                })
            })

        },
        cfValidation                 : function () {
            let inputs = [
                'volume',
                'cpc'
            ];
            let inputs2 = [
                'intitle',
                'inurl',
                'title_ratio',
                'url_ratio'
            ];

            $.each(inputs, function (index, value) {
                let input_type = value;

                $('#' + input_type + '_red').change(function () {
                    let value1 = $(this).val();
                    let value2 = $('#' + input_type + '_green').val();
                    value1 = parseFloat(value1);
                    value2 = parseFloat(value2);
                    if (value1 >= value2) {
                        xagioNotify("warning", "Please input correct condition!");
                        $(this).val('');
                        $(this).focus();
                        return false;
                    }

                    $('.' + input_type + '_yellow_1').val(value1);
                    $('.' + input_type + '_yellow_2').val(value2);
                });

                $('#' + input_type + '_green').change(function () {
                    let value1 = $(this).val();
                    let value2 = $('#' + input_type + '_red').val();
                    value1 = parseFloat(value1);
                    value2 = parseFloat(value2);
                    if (value1 <= value2) {
                        xagioNotify("warning", "Please input correct condition!");
                        $(this).val('');
                        $(this).focus();
                        return false;
                    }

                    $('.' + input_type + '_yellow_1').val(value2);
                    $('.' + input_type + '_yellow_2').val(value1);
                });
            });

            $.each(inputs2, function (index, value) {
                let input_type = value;

                $('#' + input_type + '_red').change(function () {
                    let value1 = $(this).val();
                    let value2 = $('#' + input_type + '_green').val();
                    value1 = parseFloat(value1);
                    value2 = parseFloat(value2);
                    if (value1 <= value2) {
                        xagioNotify("warning", "Please input correct condition!");
                        $(this).val('');
                        $(this).focus();
                        return false;
                    }

                    $('.' + input_type + '_yellow_1').val(value1);
                    $('.' + input_type + '_yellow_2').val(value2);
                });

                $('#' + input_type + '_green').change(function () {
                    let value1 = $(this).val();
                    let value2 = $('#' + input_type + '_red').val();
                    value1 = parseFloat(value1);
                    value2 = parseFloat(value2);
                    if (value1 >= value2) {
                        xagioNotify("warning", "Please input correct condition!");
                        $(this).val('');
                        $(this).focus();
                        return false;
                    }

                    $('.' + input_type + '_yellow_1').val(value2);
                    $('.' + input_type + '_yellow_2').val(value1);
                });
            });
        },

        /*Munja Menu*/
        newKeyword       : function () {
            $(document).on('click', '.add-keywords', function (e) {
                e.preventDefault();

                let keywords = $('#keywords-input').val();

                if (keywords == '') {
                    xagioNotify("danger", "You must insert some keywords first.");
                    return;
                }

                $.post(xagio_data.wp_post, 'action=xagio_addKeyword&group_id=' + keywordGroupID + '&keywords=' + encodeURIComponent(keywords), function (d) {

                    $("#addKeywords")[0].close();
                    xagioNotify("success", "Successfully added keywords.");
                    actions.loadProjectManually();

                });
            });
            $(document).on('click', '.addKeyword', function (e) {
                e.preventDefault();
                let group = $(this).parents('.xagio-group');
                keywordGroupID = group.find('[name="group_id"]').val();
                let modal = $("#addKeywords")[0];
                modal.showModal();
            });
        },
        deleteKeywords   : function () {
            $(document).on('click', '.deleteKeywords', function (e) {
                e.preventDefault();
                let keyword_ids = $(this).parents('.xagio-group').find('.updateKeywords').serialize();
                let keywords_length = $(this).parents('.xagio-group').find('.updateKeywords').serializeArray().length;

                if (keywords_length < 1) {
                    xagioNotify("danger", "Please select some keywords!");
                    return false;
                }

                let modal = $('#deleteKeywords');

                modal.find('.delete-keywords-number').html(keywords_length);
                modal.find('#keywordIds').val(keyword_ids);

                modal[0].showModal();
            });

            $(document).on('click', '.delete-keywords', function () {
                let btn = $(this);

                let modal = btn.parents('.xagio-modal');
                let deleteRanks = $('.xagio-modal #deleteRanks').is(':checked');
                let keyword_ids = modal.find('#keywordIds').val();


                $.post(xagio_data.wp_post, 'action=xagio_deleteKeywords&' + keyword_ids + '&deleteRanks=' +
                                           deleteRanks, function (d) {
                    xagioNotify("success", "Keywords successfully deleted.");
                    modal[0].close();
                    actions.loadProjectManually();
                })
            });

            $('#deleteKeywords')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('#keywordIds').val(0);
                modal.find('#deleteRanks').val(0).prop('checked', false);
                modal.find('.delete-keywords-number').text('-');
            });
        },
        deleteDuplicate  : function () {
            /*Delete Duplicate keywords from current project*/
            $(document).on('click', '.deleteDuplicate', function (e) {
                e.preventDefault();
                let updateGroup = $('.updateGroup');
                let modal = $('#removeDuplicateKeywords');
                let project_id = updateGroup.find('[name="project_id"]').val();
                modal.find('#projectId').val(project_id);
                modal[0].showModal();
            });

            $(document).on('click', '.remove-duplicate-keywords', function () {
                let btn = $(this);
                let modal = btn.parents('.xagio-modal');
                let project_id = modal.find('#projectId').val();

                btn.disable();
                $.post(xagio_data.wp_post, 'action=xagio_deleteDuplicate&project_id=' + project_id, function (d) {
                    btn.disable();
                    xagioNotify(d.status, d.message);
                    modal[0].close();
                    actions.loadProjectManually();
                });
            });

            $('#removeDuplicateKeywords')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('#projectId').val(0);
            });
        },
        createPagePost   : function () {
            /*Create New Page or Post*/
            $(document).on('click', '.createNewPagePost', function (e) {
                e.preventDefault();
                let btn = $(this);
                let btn_type = btn.attr('data-type');
                let form = btn.parents('.xagio-group').find('form.updateGroup');
                let form_post = form.serialize().replace('action=xagio_updateGroup&', '');
                let group_name = btn.parents('.xagio-group').find('input[name="group_name"]').val();

                let block_modal = $('#creating_block');

                block_modal[0].showModal();

                $.post(xagio_data.wp_post, `action=xagio_create_page_post&type=${btn_type}&${form_post}&group_name=${group_name}`, function (d) {

                    block_modal[0].close();
                    if (d.status == 'error') {
                        xagioNotify("danger", d.message);
                        return false;
                    }

                    let modal_template = $('#resultsPagePost');

                    if (d.status == 'warning') {
                        modal_template.find('.pagePostResultsMessage').html('<i class="xagio-icon xagio-icon-warning"></i> Page is already created, you can access it below!')
                    }

                    if (d.data.post_type == 'page' && d.status == 'success') {
                        $.post(xagio_data.wp_post, 'action=xagio_get_page_post_parent', function (pdata) {
                            let pageOption = [];
                            pageOption.push("<option dataid='" + d.data.page_id + "' value='0'>( No Parent )</option>");
                            for (let i = 0; i < pdata.length; i++) {
                                let id = pdata[i].id;
                                let title = pdata[i].title;
                                pageOption.push("<option dataid='" + d.data.page_id + "' value='" + id + "'>" + title +
                                                "</option>");
                            }
                            let pageOptions = pageOption.join('');

                            modal_template.find('.update_parent_page #parentPage').append(pageOptions);
                        })
                    } else {
                        modal_template.find('.update_parent_page').html('');
                    }

                    if (d.status == 'success') {
                        $.post(xagio_data.wp_post, 'action=xagio_get_page_post_status', function (sData) {
                            modal_template.find('.update_page_post_status #pagePostStatus').empty();
                            let statusOption = [];
                            for (let i = 0; i < sData.length; i++) {
                                let value = sData[i].value;
                                let title = sData[i].title;
                                statusOption.push("<option dataid='" + d.data.page_id + "' value='" + value + "'>" +
                                                  title + "</option>");
                            }
                            let statusOptions = statusOption.join('');

                            modal_template.find('.update_page_post_status #pagePostStatus').append(statusOption);
                        })
                    } else {
                        modal_template.find('.update_page_post_status').html('');
                    }

                    actions.loadProjectManually();
                    modal_template.find('.edit_page_post_link').html('<a href="' + d.data.url + '" target="_blank">' +
                                                                     d.data.url + '</a>');

                    modal_template[0].showModal();

                })
            });

            $(document).on('change', '.update_page_post_status #pagePostStatus', function (e) {
                e.preventDefault();

                let value = this.value;
                let pageID = $('option:selected', this).attr('dataid');

                if (value != '' && pageID != '') {
                    $.post(xagio_data.wp_post, 'action=xagio_update_page_post_status&page_id=' + pageID + '&value=' +
                                               value, function (d) {
                        xagioNotify("success", "Status successfully updated.");
                    })
                }
            });

            $(document).on('change', '.update_parent_page #parentPage', function (e) {
                e.preventDefault();

                let value = this.value;
                let pageID = $('option:selected', this).attr('dataid');

                if (value != '' && pageID != '') {
                    $.post(xagio_data.wp_post, 'action=xagio_update_page_parent&page_id=' + pageID + '&value=' +
                                               value, function (d) {
                        xagioNotify("success", "Parent successfully updated.");
                    })
                }
            });
        },
        deleteGroup      : function () {
            $(document).on('click', '.deleteGroup', function (e) {
                e.preventDefault();
                let group = $(this).parents('.xagio-group');
                let group_id = group.find('[name="group_id"]').val();
                let modal = $('#deleteGroup');

                modal.find('#groupId').val(group_id);
                modal[0].showModal();
            });
            $(document).on('click', '.delete-group', function () {
                let btn = $(this);
                let modal = btn.parents('.xagio-modal');
                let group_id = modal.find('#groupId').val();
                let delete_ranks = modal.find('#deleteGroupRanks').is(':checked');


                $.post(xagio_data.wp_post, 'action=xagio_deleteGroup&group_id=' + group_id + '&deleteRanks=' +
                                           delete_ranks, function (d) {
                    modal[0].close();
                    actions.loadProjects();
                    actions.loadProjectManually();
                    xagioNotify("success", "Group successfully deleted.");
                })
            });

            $('#deleteGroup')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('#groupId').val(0);
                modal.find('#deleteGroupRanks').val(0).prop('checked', false);
            });
        },
        deleteGroups     : function () {
            $(document).on('click', '.deleteGroups', function (e) {
                e.preventDefault();
                let modal = $('#deleteSelectedGroups');
                let group_names = [];
                let ids = [];

                $('.project-groups .groupSelect:checked').each(function () {
                    let group = $(this).parents('.xagio-group');
                    group_names.push('<li>' + group.data('name') + '</li>');
                    ids.push(group.find('[name="group_id"]').val());
                });

                if (ids.length < 1) {
                    xagioNotify("warning", "Please select at least one group to delete");
                    return false;
                }

                modal.find('.delete-selected-groups-ul').html(group_names.join(''));

                modal[0].showModal();
            });

            $(document).on('click', '.delete-selected-groups', function () {
                let btn = $(this);
                let modal = btn.parents('.xagio-modal');
                let delete_ranks = modal.find('#deleteSelectedGroupRanks').is(':checked');

                btn.disable();
                let ids = [];
                $('.project-groups .groupSelect:checked').each(function () {
                    let group = $(this).parents('.xagio-group');
                    ids.push(group.find('[name="group_id"]').val());
                });

                $.post(xagio_data.wp_post, 'action=xagio_deleteGroups&group_ids=' + ids.join(',') + '&deleteRanks=' +
                                           delete_ranks, function (d) {
                    btn.disable();
                    modal[0].close();
                    actions.loadProjectManually();
                    actions.loadProjects();
                    xagioNotify("success", "Groups successfully deleted.");
                });
            });

            $('#deleteSelectedGroups')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('.delete-selected-groups-ul').html('');
                modal.find('#deleteSelectedGroupRanks').prop('checked', false);
            });


            $(document).on('click', '.deleteEmptyGroups', function (e) {
                e.preventDefault();
                let modal = $('#deleteEmptyGroups');
                modal[0].showModal();
            });

            $(document).on('click', '.delete-empty-groups', function () {
                let btn = $(this);
                let modal = btn.parents('.xagio-modal');
                let skip_groups = modal.find('#skipGroups').is(':checked');

                btn.disable();
                $.post(xagio_data.wp_post, 'action=xagio_deleteEmptyGroups&project_id=' + currentProjectID +
                                           '&skipGroups=' + skip_groups, function (d) {
                    btn.disable();
                    modal[0].close();
                    actions.loadProjectManually();
                    actions.loadProjects();
                    xagioNotify("success", "Successfully deleted Empty groups.");
                });
            });

            $('#deleteEmptyGroups')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('#skipGroups').prop('checked', false);
            });
        },
        selectAllKeywords: function () {
            $(document).on('click', '.select-all', function () {
                let table = $(this).parents('table.keywords');
                table.find('.keyword-selection').each(function () {
                    $(this).prop("checked", !$(this).prop("checked"));
                });
            });
        },
        newGroup         : function () {
            $(document).on('click', '.addGroup', function (e) {
                e.preventDefault();

                let modal = $('#newGroup');
                modal[0].showModal();
            });

            $(document).on('click', '.newGroupsButton', function () {
                let btn = $(this);
                let modal = btn.parents('.xagio-modal');
                let group_name = modal.find('#newGroupInput').val();


                btn.disable();
                if (group_name == '') {
                    btn.disable();
                    xagioNotify("danger", "Group Name cannot be empty!");
                } else {
                    $.post(xagio_data.wp_post, 'action=xagio_newGroup&project_id=' + currentProjectID + '&group_name=' +
                                               group_name, function (d) {
                        xagioNotify("success", `Group ${group_name} has been created.`);
                        btn.disable();
                        modal[0].close();
                        actions.loadProjectManually();
                    });
                }

            });

            $('#newGroup')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('#newGroupInput').val('');
            });

            $(document).on('click', '.add-empty-group', function (e) {
                e.preventDefault();
                $.post(xagio_data.wp_post, 'action=xagio_newGroup&project_id=' + currentProjectID +
                                           '&group_name=xagio-empty', function (d) {
                    xagioNotify("success", "Empty group has been created.");
                    actions.loadProjectManually();
                });
            });
        },
        openNotes        : function () {
            $(document).on('click', '.openNotes', function (e) {
                e.preventDefault();
                let btn = $(this);
                let notes_row = btn.parents('.xagio-group').find('.notes-row');

                if (btn.hasClass('notesOpened')) {
                    notes_row.hide();
                    btn.removeClass('notesOpened');
                    btn.attr('data-xagio-title', 'Open Notes');
                    btn.find('i').removeClass().addClass('xagio-icon xagio-icon-note');
                } else {
                    notes_row.show();
                    btn.addClass('notesOpened');
                    btn.attr('data-xagio-title', 'Close Notes');
                    btn.find('i').removeClass().addClass('xagio-icon xagio-icon-note-o');
                }
                $('.xagio-tooltip').remove();

                actions.updateGrid();
                // btn.parents('.xagio-group').trigger('display.uk.check');
            })
        },
        saveProject      : function () {
            $(document).on('click', '.saveProject', function (e) {
                e.preventDefault();
                let btn = $(this);
                btn.disable();
                $('.project-groups').find('.updateGroup').each(function () {
                    $(this).trigger('submit');
                });
                var checker = setInterval(function () {
                    if (false !== $.ajaxq.isRunning('groupUpdate')) {
                        btn.disable();
                        clearInterval(checker);
                        if (nextProjectID !== 0) {
                            currentProjectID = nextProjectID;
                            currentProjectName = nextProjectName;
                            nextProjectID = 0;
                            nextProjectName = 0;
                            actions.loadProjectManually();
                        }
                    }
                }, 500);
            });
        },
        saveGroupClick   : function () {
            $(document).on('click', '.saveGroup', function (e) {
                e.preventDefault();
                let btn = $(this);
                let form = btn.parents('.xagio-group').find('.updateGroup');
                form.submit();
            })
        },
        updateGroup      : function () {
            $(document).on('submit', '.updateGroup', function (e) {
                e.preventDefault();
                e.stopPropagation();

                let button = $(this).prev().find('.saveGroup');
                let group_id = $(this).find('[name="group_id"]').val();
                let data = $(this).serialize();
                let kw_data = $(this).parents('.xagio-group').find('.keywords-data');
                let group_name = $(this).parents('.xagio-group').find('input[name="group_name"]').val();
                data = data + '&group_name=' + group_name;

                button.disable();

                // First update the group settings
                $.postq('groupUpdate', xagio_data.wp_post, data, function (d) {

                    button.disable();

                    // Now update all keywords
                    let keywords = [];
                    let position = 1;
                    kw_data.find('tr').each(function () {
                        let keyword = {};
                        keyword['id'] = $(this).data('id');
                        keyword['position'] = position;
                        position++;
                        let allNull = true;
                        $(this).find('td div.keywordInput').each(function () {
                            let value = $(this).text();
                            if (value != '') {
                                keyword[$(this).data('target')] = value;
                                allNull = false;
                            }
                        });
                        if (!allNull) keywords.push(keyword);
                    });

                    if(keywords.length > 1) {
                        let data = [
                            {
                                name : 'action',
                                value: 'xagio_updateKeywords'
                            },
                            {
                                name : 'group_id',
                                value: group_id
                            }
                        ];

                        keywords.forEach((keyword, index) => {
                            Object.keys(keyword).forEach(key => {
                                data.push({
                                    name : `keywords[${index}][${key}]`,
                                    value: keyword[key]
                                });
                            });
                        });

                        $.postq('groupUpdate', xagio_data.wp_post, data, function (d) {
                            activeChanges = false;
                            clearTimeout(groupNoticeTimeout);
                            groupNoticeTimeout = setTimeout(function () {
                                xagioNotify("success", "Changes saved successfully.");
                            }, 300);
                        });
                    } else {
                        activeChanges = false;
                        clearTimeout(groupNoticeTimeout);
                        groupNoticeTimeout = setTimeout(function () {
                            xagioNotify("success", "Changes saved successfully.");
                        }, 300);
                    }

                });


            });
        },
        editGroupSettings: function () {
            $(document).on('click', '.editGroupSettings', function (e) {
                e.preventDefault();

                let groupSettings = $(this).parents('.groupSettings');
                let tbody = groupSettings.find('tbody.groupSettingsTbody');

                tbody.toggle();

                actions.updateGrid();
            });
        },
        renderSliders    : function () {
            // Enable sliders
            $('.prs-slider-frame .slider-button').toggle(function () {
                $(this).addClass('on');
            }, function () {
                $(this).removeClass('on');
            });
        },
        initSliders: function () {
            const rangeContainers = document.querySelectorAll(".hunter-range-container");
            rangeContainers.forEach(container => {

                const rangevalue = container.querySelector(".hunter-slider-container .price-slider");
                const rangeInputvalue = container.querySelectorAll(".range-input input");
                const priceInputvalue = container.querySelectorAll(`.xagio-slider-input input`);

                let priceGap = parseFloat(rangeInputvalue[0].step);

                // Debounce function to delay execution until user stops typing
                function debounce(func, delay) {
                    let timeout;
                    return function (...args) {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => func.apply(this, args), delay);
                    };
                }

                // Function to update the display of the price slider color and range fill
                function updateSliderDisplay(minVal, maxVal) {
                    rangevalue.style.left = `${(minVal / rangeInputvalue[0].max) * 100}%`;
                    rangevalue.style.right = `${100 - (maxVal / rangeInputvalue[1].max) * 100}%`;
                }

                // Main function to handle input validation and updates
                function handleInput(e) {
                    let minp = parseFloat(priceInputvalue[0].value);
                    let maxp = parseFloat(priceInputvalue[1].value);

                    const validate_max = rangeInputvalue[1].max;
                    const validate_min = rangeInputvalue[0].min;

                    // Separate logic for min and max inputs
                    if (e.target.classList.contains("min-input")) {
                        if (minp < validate_min) minp = validate_min;
                        else if (minp > maxp - priceGap) minp = maxp - priceGap;
                        rangeInputvalue[0].value = minp;
                    } else {
                        if (maxp > validate_max) maxp = validate_max;
                        else if (maxp < minp + priceGap) maxp = minp + priceGap;
                        rangeInputvalue[1].value = maxp;
                    }

                    // Update the display of the range slider based on minp and maxp
                    updateSliderDisplay(minp, maxp);

                    // Reflect changes in price input values
                    priceInputvalue[0].value = minp;
                    priceInputvalue[1].value = maxp;
                }

                // Debounced version of handleInput
                const debouncedHandleInput = debounce(handleInput, 500); // 500ms delay

                // Attach debounced function to input events for min and max input elements
                priceInputvalue.forEach(input => input.addEventListener("input", debouncedHandleInput));

                // Immediate event listener for range slider inputs
                rangeInputvalue.forEach(input => {
                    input.addEventListener("input", e => {
                        let minVal = parseFloat(rangeInputvalue[0].value);
                        let maxVal = parseFloat(rangeInputvalue[1].value);
                        let diff = maxVal - minVal;

                        if (diff < priceGap) {
                            if (e.target.classList.contains("min-input")) {
                                rangeInputvalue[0].value = maxVal - priceGap;
                                minVal = maxVal - priceGap;
                            } else {
                                rangeInputvalue[1].value = minVal + priceGap;
                                maxVal = minVal + priceGap;
                            }
                        }

                        // Update price inputs and range slider display
                        priceInputvalue[0].value = minVal;
                        priceInputvalue[1].value = maxVal;
                        updateSliderDisplay(minVal, maxVal);
                    });
                });
            });
        },
        renameProject    : function () {
            $(document).on('click', '.rename_project', function (e) {
                e.preventDefault();

                let project_id = $(this).data('id');
                let project_name = $(this).data('name');
                let modal = $('#newProject')[0];

                $(modal).find('input').val(project_name);
                $(modal).find('.xagio-modal-title').text('Edit Your Project Name');
                $(modal).find('.editProjectName').val(project_id);

                modal.showModal();
            });
        },
        modalEvents      : function () {
            $('#newProject')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('input').val('');
                modal.find('.xagio-modal-title').text('Name Your New Project');
                modal.find('.editProjectName').val(0);
            });
        },
        newProject       : function () {
            $(document).on('click', '.new-project', function (e) {
                e.preventDefault();
                let modal = $('#newProject')[0];
                modal.showModal();
            });

            $(document).on('submit', '#newProjectForm', function (e) {
                e.preventDefault();

                let project_name = $(this).find('#newProjectInput').val();
                let project_id = $(this).find('.editProjectName').val();
                let group_ids = $(this).find('.moveGroupsIds').val();
                let btn = $(this).find('.newProjectButton');
                let modal = $(this).parents('.xagio-modal')[0];

                btn.disable();

                if (parseInt(project_id) === 0) {
                    if (project_name == '') {
                        xagioNotify("danger", "Project Name cannot be empty!");
                        btn.disable();
                    } else {
                        // NEW PROJECT
                        if (group_ids === '') {
                            $.post(xagio_data.wp_post, 'action=xagio_new_project&project_name=' +
                                                       project_name, function (d) {

                                btn.disable();
                                modal.close();
                                actions.loadProjects();
                                xagioNotify("success", `Project ${project_name} has been created.`);
                            });
                        } else {
                            // Move Groups to a new project
                            $.post(xagio_data.wp_post, 'action=xagio_groupToProject&group_id=' + group_ids +
                                                       '&projectName=' + project_name, function (d) {
                                if (d.status == 'success') {
                                    btn.disable();
                                    modal.close();
                                    moveToProject[0].close();
                                    actions.loadProjects();
                                }
                                xagioNotify(d.status, d.message);
                            });
                        }
                    }
                } else {
                    // RENAME PROJECT
                    if (project_name == '') {
                        xagioNotify("danger", "Project Name cannot be empty!");
                        btn.disable();
                    } else {
                        $.post(xagio_data.wp_post, 'action=xagio_rename_project&project_id=' + project_id +
                                                   '&project_name=' + project_name, function (d) {
                            btn.disable();
                            modal.close();
                            actions.loadProjects();
                            xagioNotify("success", `Project ${project_name} has been renamed`);
                        });
                    }
                }


            });
        },
        updateGrid       : function () {
            $grid.masonry('reloadItems');
            $grid.masonry('layout');
        },
        updateElements   : function () {
            let table_sort_config = {
                headers: {
                    0: {
                        sorter: false
                    },
                    2: {
                        sorter: 'fancyNumber'
                    },
                    3: {
                        sorter: 'fancyNumber'
                    },
                    4: {
                        sorter: 'fancyNumber'
                    },
                    5: {
                        sorter: 'fancyNumber'
                    },
                    6: {
                        sorter: 'fancyNumber'
                    },
                    7: {
                        sorter: 'fancyNumber'
                    },
                    8: {
                        sorter: 'fancyNumber'
                    }
                }
            };
            // Table sorting
            $(".keywords").tablesorter(table_sort_config);

            let kw_data = $('.keywords-data');

            $(document).on('keyup', function (event) {
                if (event.key === "Escape") {
                    $('.keywords-data tr').removeClass('selected multiselectable-previous');
                }
            });

            kw_data.multisortable({
                items: "tr",
                selectedClass: "selected",
                stop: function (e) {
                    if ($(e.target).find('tr').length < 1) {
                        $(e.target).html('<tr><td colspan="11"><div class="empty-keywords"><i class="xagio-icon xagio-icon-warning"></i> No added keywords yet... <button type="button" class="xagio-button xagio-button-primary addKeyword"><i class="xagio-icon xagio-icon-plus"></i>Add Keyword(s)</button></div></td></tr>');
                    }

                    $('.xagio-group .jqcloud').each(function (index) {
                        let jscloud = $(this);

                        let current_cloud_keywords = jscloud.parents('.xagio-group').find('.keywords-data tr').find('div.keywordInput[data-target="keyword"]');

                        let keywords = [];
                        current_cloud_keywords.each(function () {
                            keywords.push($(this).text());
                        });
                        jscloud.jQCloud('update', actions.calculateAndTrim(keywords));
                        jscloud.css("display", "block").resize();


                    });
                }
            });

            // Drag and Drop
            kw_data.sortable({
                connectWith: ".uk-sortable",
                cancel     : "input,textarea,button,select,option,[contenteditable]",
                placeholder: "drop-placeholder",
                cursorAt   : {left: 20},
                opacity    : 0.8,
            }).on("sortreceive", function (event, ui) {

                let target = $(this);
                let original_group = $(ui.sender).parents('.xagio-group').find('[name="group_id"]').val();
                let target_group = target.parents('.xagio-group').find('[name="group_id"]').val();

                let original_table = $(`input[name="group_id"][value="${original_group}"]`).parents('.xagio-group').find('table.keywords');
                let target_table = $(`input[name="group_id"][value="${target_group}"]`).parents('.xagio-group').find('table.keywords');

                $('.keywordInput[data-target="keyword"]').unhighlight();

                if (target_table.find('.empty-keywords').length > 0) {
                    target_table.find('.keywords-data').find('.empty-keywords').parents('tr').remove();
                }

                original_table.trigger("update");
                target_table.trigger("update");

                let original_table_keywords = original_table.find('.keywords-data tr').find('div.keywordInput[data-target="keyword"]');
                let target_table_keywords = target_table.find('.keywords-data tr').find('div.keywordInput[data-target="keyword"]');

                let original_keywords = [];
                let target_keywords = [];
                original_table_keywords.each(function () {
                    original_keywords.push($(this).text());
                });

                target_table_keywords.each(function () {
                    target_keywords.push($(this).text());
                });

                let original_table_cloud = original_table.parents('.xagio-group').find('.jqcloud');
                let target_table_cloud = target_table.parents('.xagio-group').find('.jqcloud');

                if (original_table_cloud.length > 0) {
                    original_table_cloud.jQCloud('update', actions.calculateAndTrim(original_keywords));
                    original_table_cloud.css("display", "block").resize();
                }

                if (target_table_cloud.length > 0) {
                    target_table_cloud.jQCloud('update', actions.calculateAndTrim(target_keywords));
                    target_table_cloud.css("display", "block").resize();
                }

                if ($(`input[name="group_id"][value="${original_group}"]`).parents('.xagio-group').find('table.keywords').find('.keywords-data tr').length <
                    1) {
                    original_table.find('.keywords-data').html('<tr><td colspan="11"><div class="empty-keywords"><i class="xagio-icon xagio-icon-warning"></i> No added keywords yet... <button type="button" class="xagio-button xagio-button-primary addKeyword"><i class="xagio-icon xagio-icon-plus"></i>Add Keyword(s)</button></div></td></tr>');
                }


                setTimeout(function () {
                    let keyword_ids = [];
                    target.find('tr.selected').each(function () {
                        let id = $(this).data('id');
                        keyword_ids.push(id);
                    });

                    $.post(xagio_data.wp_post, 'action=xagio_keywordChangeGroup&keyword_ids=' + keyword_ids.join(',') +
                                               '&original_group_id=' + original_group + '&target_group_id=' +
                                               target_group, function (d) {
                        actions.updateGrid();

                        xagioNotify("success", "Group change successful.");
                    });
                }, 250);
            });
        },
        prepareURL       : function (url) {
            if (url == null || url == '') {
                return {
                    pre : '/',
                    name: ''
                };
            }
            let hasSlash = 2;
            if (url.substr(-1) != '/') {
                hasSlash = 1;
            }

            url = url.split('/');
            let name = url[url.length - hasSlash];
            let cat = url.slice(0, -hasSlash).join('/') + '/';
            return {
                pre : cat,
                name: name
            };
        },

        changeTaxonomyTypes: function () {
            $(document).on('change', '#TaxonomyType', function (e) {

                taxonomiesTable.fnDraw();

            });
            $(document).on('change', '#TaxonomyType2', function (e) {

                taxonomiesTableCreate.fnDraw();

            });
        },
        changePostTypes    : function () {
            $(document).on('change', '#PostsType', function (e) {

                postsTable.fnDraw();

            });
            $(document).on('change', '#PostsType2', function (e) {

                postsTable2.fnDraw();

            });
        },
        filterByPostType   : function () {
            $(document).on('change', '#filterPostTypes', function () {

                let value = $(this).val() != '' ? ' (<b>' + $(this).val().charAt(0).toUpperCase() +
                                                  $(this).val().slice(1) + 's' + ')</b>' : '';

                $(this).prev().html('<i class="xagio-icon xagio-icon-filter"></i> ' + value);

                actions.loadProjectManually();
            });
        },

        loadPostTypes   : function () {

            $.post(xagio_data.wp_post, 'action=xagio_get_post_types', function (d) {

                if (d.status == 'success') {

                    pTypes = d.data;

                    let postTypes = [];
                    for (let i = 0; i < pTypes.length; i++) {
                        let type = pTypes[i];
                        postTypes.push("<option value='" + type + "'>" + type.charAt(0).toUpperCase() + type.slice(1) +
                                       "s</option>");
                    }
                    pTypes = postTypes.join('');
                    // Insert into filters
                    $('#filterPostTypes').append(pTypes);
                    // $('#filterPostTypes').trigger('change');

                    // Load the Datatable for posts
                    actions.loadPostsPages();
                }

            });
        },
        loadPostsPages  : function () {

            postsTable = $('.postsTable').dataTable({
                                                        language        : {
                                                            search           : "_INPUT_",
                                                            searchPlaceholder: "Search posts...",
                                                            processing       : "Loading Posts...",
                                                            emptyTable       : "No posts found on this website.",
                                                            info             : "_START_ to _END_ of _TOTAL_ results",
                                                            infoEmpty        : "0 to 0 of 0 results",
                                                            infoFiltered     : "(from _MAX_ total results)"
                                                        },
                                                        "dom"           : '<"posts-actions"f>rt<"xagio-table-bottom"lp><"clear">',
                                                        "bDestroy"      : true,
                                                        "searchDelay"   : 350,
                                                        "bPaginate"     : true,
                                                        "bAutoWidth"    : false,
                                                        "bFilter"       : true,
                                                        "bProcessing"   : true,
                                                        "sServerMethod" : "POST",
                                                        "bServerSide"   : true,
                                                        "sAjaxSource"   : xagio_data.wp_post,
                                                        "iDisplayLength": 5,
                                                        "aLengthMenu"   : [
                                                            [
                                                                5,
                                                                10,
                                                                50,
                                                                100
                                                            ],
                                                            [
                                                                5,
                                                                10,
                                                                50,
                                                                100
                                                            ]
                                                        ],
                                                        "aaSorting"     : [
                                                            [
                                                                1,
                                                                'desc'
                                                            ]
                                                        ],
                                                        "aoColumns"     : [
                                                            {
                                                                "sClass"   : "text-left",
                                                                "bSortable": false,
                                                                "mData"    : 'ID',
                                                                "mRender"  : function (data, type, row) {
                                                                    return '<span class="post-id">' + data + '</span>';
                                                                }
                                                            },
                                                            {
                                                                "sClass"   : "text-left",
                                                                "bSortable": true,
                                                                "mData"    : 'post_title',
                                                                "mRender"  : function (data, type, row) {
                                                                    return "<b class='post-title'>" + data + "</b>" +
                                                                           "<a href='" + row.permalink +
                                                                           "' target='_blank'>" + row.permalink +
                                                                           "</a>" +
                                                                           "<div class='row-actions'>" +
                                                                           "<a href='#' data-id='" + row.ID +
                                                                           "' class='attach-to-page-post'>Attach</a>"

                                                                           + " <span>|</span> "

                                                                           + "<a href='" + xagio_data.wp_admin +
                                                                           'post.php?post=' + row.ID + '&action=edit' +
                                                                           "' target='_blank' class='edit'>Edit</a>"

                                                                           + " <span>|</span> "

                                                                           + "<a href='" + row.page_url +
                                                                           "' target='_blank' class='view'>View</a>" +
                                                                           "</div>";
                                                                },
                                                                "asSorting": [
                                                                    "desc",
                                                                    "asc"
                                                                ]
                                                            },
                                                            {
                                                                "bSortable": true,
                                                                "mData"    : 'post_date',
                                                                "mRender"  : function (data, type, row) {
                                                                    return '<b>' +
                                                                           row.post_status.charAt(0).toUpperCase() +
                                                                           row.post_status.slice(1) + 'ed</b>' +
                                                                           '<br>' + '<abbr title="' + data + '">' +
                                                                           new Date(data).toUTCString().split(' ').splice(0, 4).join(' ') +
                                                                           '</abbr>';
                                                                },
                                                                "asSorting": [
                                                                    "desc",
                                                                    "asc"
                                                                ]
                                                            }
                                                        ],
                                                        "fnServerParams": function (aoData) {

                                                            aoData.push({
                                                                            name : 'action',
                                                                            value: 'xagio_get_posts'
                                                                        });

                                                            if ($('#PostsType').length > 0) {

                                                                aoData.push({
                                                                                name : 'PostsType',
                                                                                value: $('#PostsType').val()
                                                                            });

                                                            }
                                                        },
                                                        "fnCreatedRow"  : function (row, data, index) {
                                                            let modal = $("#attachToPagePost");
                                                            let value = modal.find('[name="post_id"]').val();

                                                            if (data.ID == value) {
                                                                $(row).addClass('attached-pt');
                                                                $(row).attr('data-xagio-tooltip', '').attr('data-xagio-title', 'Attached');
                                                            }
                                                        },

                                                        fnInitComplete: function () {
                                                            $('.posts-actions').find('input[type="search"]').before('<div class="modal-label">Search</div>');
                                                            $('.posts-actions').find('input[type="search"]').addClass('xagio-input-text-mini');

                                                            $('.posts-actions').prepend(
                                                                '<div class="xagio-flex xagio-flex-gap-medium">' +
                                                                '<div><div class="modal-label">Filter Type</div><select class="xagio-input-select xagio-input-select-gray" id="PostsType">' +
                                                                '<option value="">– Filter Type –</option>' + pTypes +
                                                                '</select></div>'
                                                                +
                                                                '<div><div class="modal-label">Filter Type</div><select class="xagio-input-select xagio-input-select-gray" id="AttachType">' +
                                                                '<option value="" selected>Import data from ...</option>' +
                                                                '<option value="page">Page fields (WordPress)</option>' +
                                                                '<option value="group">Group fields (Xagio Project Planner)</option>'
                                                                + '</select></div></div>');
                                                        }

                                                    });
            postsTable2 = $('.postsTable2').dataTable({
                                                          language: {
                                                              search           : "_INPUT_",
                                                              searchPlaceholder: "Search posts...",
                                                              processing       : "Loading Posts...",
                                                              emptyTable       : "No posts found on this website.",
                                                              info             : "_START_ to _END_ of _TOTAL_ results",
                                                              infoEmpty        : "0 to 0 of 0 results",
                                                              infoFiltered     : "(from _MAX_ total results)"
                                                          },

                                                          "dom"           : '<"posts-actions2"f>rt<"xagio-table-bottom"lp><"clear">',
                                                          "bDestroy"      : true,
                                                          "searchDelay"   : 350,
                                                          "bPaginate"     : true,
                                                          "bAutoWidth"    : false,
                                                          "bFilter"       : true,
                                                          "bProcessing"   : true,
                                                          "sServerMethod" : "POST",
                                                          "bServerSide"   : true,
                                                          "sAjaxSource"   : xagio_data.wp_post,
                                                          "iDisplayLength": 5,
                                                          "aLengthMenu"   : [
                                                              [
                                                                  5,
                                                                  10,
                                                                  50,
                                                                  100
                                                              ],
                                                              [
                                                                  5,
                                                                  10,
                                                                  50,
                                                                  100
                                                              ]
                                                          ],
                                                          "aaSorting"     : [
                                                              [
                                                                  1,
                                                                  'desc'
                                                              ]
                                                          ],
                                                          "aoColumns"     : [
                                                              {
                                                                  "sClass"     : "text-left",
                                                                  "bSortable"  : false,
                                                                  "bSearchable": false,
                                                                  "mRender"    : function (data, type, row) {
                                                                      let checked = '';

                                                                      if ($.inArray(row.ID, selectedPosts) != -1) {
                                                                          checked = 'checked';
                                                                      }

                                                                      return '<input ' + checked +
                                                                             ' class="xagio-input-checkbox xagio-input-checkbox-mini select-post" type="checkbox" data-value="' +
                                                                             row.ID + '"  value="' + row.ID + '">';
                                                                  }
                                                              },
                                                              {
                                                                  "sClass"   : "text-left",
                                                                  "bSortable": false,
                                                                  "mData"    : 'ID',
                                                                  "mRender"  : function (data, type, row) {
                                                                      return '<span class="post-id">' + data +
                                                                             '</span>';
                                                                  }
                                                              },
                                                              {
                                                                  "sClass"   : "text-left",
                                                                  "bSortable": true,
                                                                  "mData"    : 'post_title',
                                                                  "mRender"  : function (data, type, row) {
                                                                      return "<b class='post-title'>" + data + "</b>" +
                                                                             "<div class='row-actions'>"

                                                                             + "<a href='" + xagio_data.wp_admin +
                                                                             'post.php?post=' + row.ID +
                                                                             '&action=edit' +
                                                                             "' target='_blank' class='edit'>Edit</a>"

                                                                             + " <span>|</span> "

                                                                             + "<a href='" + row.page_url +
                                                                             "' target='_blank' class='view'>View</a>" +
                                                                             "</div>";
                                                                  },
                                                                  "asSorting": [
                                                                      "desc",
                                                                      "asc"
                                                                  ]
                                                              },
                                                              {
                                                                  "bSortable": true,
                                                                  "mData"    : 'post_date',
                                                                  "mRender"  : function (data, type, row) {
                                                                      return '<b>' +
                                                                             row.post_status.charAt(0).toUpperCase() +
                                                                             row.post_status.slice(1) + 'ed</b>' +
                                                                             '<br>' + '<abbr title="' + data + '">' +
                                                                             new Date(data).toUTCString().split(' ').splice(0, 4).join(' ') +
                                                                             '</abbr>';
                                                                  },
                                                                  "asSorting": [
                                                                      "desc",
                                                                      "asc"
                                                                  ]
                                                              }
                                                          ],
                                                          "fnServerParams": function (aoData) {

                                                              aoData.push({
                                                                              name : 'action',
                                                                              value: 'xagio_get_posts'
                                                                          });

                                                              if ($('#PostsType2').length > 0) {

                                                                  aoData.push({
                                                                                  name : 'PostsType',
                                                                                  value: $('#PostsType2').val()
                                                                              });

                                                              }
                                                          },

                                                          fnInitComplete: function () {
                                                              $('.posts-actions2').find('input[type="search"]').before('<div class="modal-label">Search</div>');
                                                              $('.posts-actions2').find('input[type="search"]').addClass('xagio-input-text-mini');
                                                              $('.posts-actions2').prepend('<div class="modal-label">Filter Type</div><select class=" xagio-input-select xagio-input-select-gray" id="PostsType2">' +
                                                                                           '<option value="">Post Type</option>' +
                                                                                           pTypes + '</select>');
                                                          }

                                                      });

        },
        dettachPagePost : function () {
            $(document).on('click', '.detachPagePost', function (e) {
                e.preventDefault();

                let btn = $(this);

                let form = btn.parents('.xagio-group').find('.updateGroup');
                let group_id = form.find('[name="group_id"]').val();

                xagioModal("Are you sure?", "You are about to detach this group from the connected Page/Post. Continue?", function (yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, `action=xagio_detach_from_group&group_id=${group_id}`, function (d) {
                            actions.loadProjectManually();
                        });
                    }
                })

            });
        },
        attachToPagePost: function () {

            $(document).on('click', '.attach-to-page-post', function (e) {
                e.preventDefault();
                let button = $(this);
                let modal = $("#attachToPagePost");
                let post_id = button.data('id');
                let attach_t = $('#AttachType').val();
                let group_id = modal.find('[name="group_id"]').val();

                if (attach_t == "") {
                    xagioNotify("danger", "Please select first where to import the data from (Title / Description / H1)! You can select your Group's SEO Title and Description, or your Page/Post's SEO Title, Descriptions and H1.");
                    return;
                }

                button.disable('Attaching ...');
                $.post(xagio_data.wp_post, 'action=xagio_attach_to_page_post&group_id=' + group_id + '&post_id=' +
                                           post_id + '&attach_type=' + attach_t, function (d) {
                    button.disable();
                    if (d.status == 'success') {
                        xagioNotify(d.status, d.message, true);
                        actions.loadProjectManually();
                        modal[0].close();
                    } else {
                        xagioNotify("danger", d.message);
                    }
                });

            });

            $(document).on('click', '.attachToPagePost', function (e) {
                e.preventDefault();
                let group_id = $(this).parents('.xagio-group').find('input[name="group_id"]').val();
                let post_id = $(this).data('post-id');
                let modal = $("#attachToPagePost");

                modal.find('[name="group_id"]').val(group_id);
                modal.find('[name="post_id"]').val(post_id);

                postsTable.fnDraw();

                modal[0].showModal();
            });
        },
        goToPagePost    : function () {
            $(document).on('click', '.goToPagePost', function (e) {
                if ($(this).attr('href') == '#') {
                    e.preventDefault();
                    xagioNotify("warning", "You must first attach a page in order to use Go to Page/Post.");
                }
            });
        },

        loadTaxonomyTypes: function () {

            $.post(xagio_data.wp_post, 'action=xagio_get_taxonomy_types', function (d) {

                if (d.status == 'success') {

                    tTypes = d.data;

                    let taxTypes = [];
                    for (let i = 0; i < tTypes.length; i++) {
                        let type = tTypes[i];
                        taxTypes.push("<option value='" + type + "'>" + type.charAt(0).toUpperCase() + type.slice(1) +
                                      "</option>");
                    }
                    tTypes = taxTypes.join('');

                    // Load the Datatable for posts
                    actions.loadTaxonomies();
                }

            });
        },
        loadTaxonomies   : function () {

            taxonomiesTable = $('.taxonomiesTable').dataTable({
                                                                  language        : {
                                                                      search           : "_INPUT_",
                                                                      searchPlaceholder: "Search taxonomies...",
                                                                      processing       : "Loading taxonomies...",
                                                                      emptyTable       : "No taxonomies found on this website.",
                                                                      info             : "_START_ to _END_ of _TOTAL_ results",
                                                                      infoEmpty        : "0 to 0 of 0 results",
                                                                      infoFiltered     : "(from _MAX_ total results)"
                                                                  },
                                                                  "dom"           : '<"taxonomies-actions"f>rt<"xagio-table-bottom"lp><"clear">',
                                                                  "bDestroy"      : true,
                                                                  "searchDelay"   : 350,
                                                                  "bPaginate"     : true,
                                                                  "bAutoWidth"    : false,
                                                                  "bFilter"       : true,
                                                                  "bProcessing"   : true,
                                                                  "sServerMethod" : "POST",
                                                                  "bServerSide"   : true,
                                                                  "sAjaxSource"   : xagio_data.wp_post,
                                                                  "iDisplayLength": 5,
                                                                  "aLengthMenu"   : [
                                                                      [
                                                                          5,
                                                                          10,
                                                                          50,
                                                                          100
                                                                      ],
                                                                      [
                                                                          5,
                                                                          10,
                                                                          50,
                                                                          100
                                                                      ]
                                                                  ],
                                                                  "aaSorting"     : [
                                                                      [
                                                                          1,
                                                                          'desc'
                                                                      ]
                                                                  ],
                                                                  "aoColumns"     : [
                                                                      {
                                                                          "sClass"   : "xagio-text-center",
                                                                          "bSortable": false,
                                                                          "mData"    : 'term_id',
                                                                          "mRender"  : function (data, type, row) {
                                                                              return '<span class="taxonomy-id">' +
                                                                                     data + '</span>';
                                                                          }
                                                                      },
                                                                      {
                                                                          "sClass"   : "",
                                                                          "bSortable": true,
                                                                          "mData"    : 'name',
                                                                          "mRender"  : function (data, type, row) {
                                                                              return "<b class='taxonomy-name'>" +
                                                                                     data + "</b>" +
                                                                                     "<div class='row-actions'>" +
                                                                                     "<a href='#' data-id='" +
                                                                                     row.term_id +
                                                                                     "' class='attach-to-taxonomy'>Attach</a>"

                                                                                     + " <span>|</span> "

                                                                                     + "<a href='" +
                                                                                     xagio_data.wp_admin +
                                                                                     'term.php?taxonomy=' +
                                                                                     row.taxonomy + '&tag_ID=' +
                                                                                     row.term_id +
                                                                                     "' target='_blank' class='edit'>Edit</a>"
                                                                          },
                                                                          "asSorting": [
                                                                              "desc",
                                                                              "asc"
                                                                          ]
                                                                      },
                                                                      {
                                                                          "bSortable": true,
                                                                          "mData"    : 'taxonomy',
                                                                          "mRender"  : function (data, type, row) {
                                                                              return data;
                                                                          },
                                                                          "asSorting": [
                                                                              "desc",
                                                                              "asc"
                                                                          ]
                                                                      }
                                                                  ],
                                                                  "fnServerParams": function (aoData) {

                                                                      aoData.push({
                                                                                      name : 'action',
                                                                                      value: 'xagio_get_taxonomies'
                                                                                  });

                                                                      if ($('#TaxonomyType').length > 0) {

                                                                          aoData.push({
                                                                                          name : 'taxonomy',
                                                                                          value: $('#TaxonomyType').val()
                                                                                      });

                                                                      }
                                                                  },
                                                                  "fnCreatedRow"  : function (row, data, index) {
                                                                      let modal = $("#attachToTaxonomy");
                                                                      let value = modal.find('[name="taxonomy_id"]').val();

                                                                      if (data.term_id == value) {
                                                                          $(row).addClass('attached-pt');
                                                                          $(row).attr('data-xagio-tooltip', '').attr('data-xagio-title', 'Attached')
                                                                      }
                                                                  },

                                                                  fnInitComplete: function () {
                                                                      $('.taxonomies-actions').find('input[type="search"]').before('<div class="modal-label">Search</div>');
                                                                      $('.taxonomies-actions').find('input[type="search"]').addClass('xagio-input-text-mini');
                                                                      $('.taxonomies-actions').append('<div class="xagio-flex xagio-flex-gap-medium">' +
                                                                                                      '<div><div class="modal-label">Filter Type</div><select class=" xagio-input-select xagio-input-select-gray" id="TaxonomyType">' +
                                                                                                      '<option value="">– Filter Type –</option>' +
                                                                                                      tTypes +
                                                                                                      '</select></div>'
                                                                                                      +
                                                                                                      '<div><div class="modal-label">Import Data From</div><select class=" xagio-input-select xagio-input-select-gray" id="AttachTypeTax">' +
                                                                                                      '<option value="" selected>Import data from ...</option>' +
                                                                                                      '<option value="taxonomy">Taxonomy fields (WordPress)</option>' +
                                                                                                      '<option value="group">Group fields (Xagio Project Planner)</option>' +
                                                                                                      '</select></div></div>');
                                                                  }

                                                              });
            taxonomiesTableCreate = $('.taxonomiesTableCreate').dataTable({
                                                                              language        : {
                                                                                  search           : "_INPUT_",
                                                                                  searchPlaceholder: "Search taxonomies...",
                                                                                  processing       : "Loading taxonomies...",
                                                                                  emptyTable       : "No taxonomies found on this website.",
                                                                                  info             : "_START_ to _END_ of _TOTAL_ results",
                                                                                  infoEmpty        : "0 to 0 of 0 results",
                                                                                  infoFiltered     : "(from _MAX_ total results)"
                                                                              },
                                                                              "dom"           : '<"taxonomies-actions2"f>rt<"xagio-table-bottom"lp><"clear">',
                                                                              "bDestroy"      : true,
                                                                              "searchDelay"   : 350,
                                                                              "bPaginate"     : true,
                                                                              "bAutoWidth"    : false,
                                                                              "bFilter"       : true,
                                                                              "bProcessing"   : true,
                                                                              "sServerMethod" : "POST",
                                                                              "bServerSide"   : true,
                                                                              "sAjaxSource"   : xagio_data.wp_post,
                                                                              "iDisplayLength": 5,
                                                                              "aLengthMenu"   : [
                                                                                  [
                                                                                      5,
                                                                                      10,
                                                                                      50,
                                                                                      100
                                                                                  ],
                                                                                  [
                                                                                      5,
                                                                                      10,
                                                                                      50,
                                                                                      100
                                                                                  ]
                                                                              ],
                                                                              "aaSorting"     : [
                                                                                  [
                                                                                      1,
                                                                                      'desc'
                                                                                  ]
                                                                              ],
                                                                              "aoColumns"     : [
                                                                                  {
                                                                                      "sClass"     : "xagio-text-center",
                                                                                      "bSortable"  : false,
                                                                                      "bSearchable": false,
                                                                                      "mRender"    : function (data, type, row) {
                                                                                          let checked = '';

                                                                                          if ($.inArray(row.term_id, selectedTaxonomies) !=
                                                                                              -1) {
                                                                                              checked = 'checked';
                                                                                          }

                                                                                          return '<input ' + checked +
                                                                                                 ' class="xagio-input-checkbox xagio-input-checkbox-mini select-taxonomy" type="checkbox" data-value="' +
                                                                                                 row.term_id +
                                                                                                 '" value="' +
                                                                                                 row.term_id + '">';
                                                                                      }
                                                                                  },
                                                                                  {
                                                                                      "sClass"   : "xagio-text-center",
                                                                                      "bSortable": false,
                                                                                      "mData"    : 'term_id',
                                                                                      "mRender"  : function (data, type, row) {
                                                                                          return '<span class="taxonomy-id">' +
                                                                                                 data + '</span>';
                                                                                      }
                                                                                  },
                                                                                  {
                                                                                      "sClass"   : "",
                                                                                      "bSortable": true,
                                                                                      "mData"    : 'name',
                                                                                      "mRender"  : function (data, type, row) {
                                                                                          return "<b class='taxonomy-name'>" +
                                                                                                 data + "</b>" +
                                                                                                 "<div class='row-actions'>"

                                                                                                 + "<a href='" +
                                                                                                 xagio_data.wp_admin +
                                                                                                 'term.php?taxonomy=' +
                                                                                                 row.taxonomy +
                                                                                                 '&tag_ID=' +
                                                                                                 row.term_id +
                                                                                                 "' target='_blank' class='edit'>Edit</a>"
                                                                                      },
                                                                                      "asSorting": [
                                                                                          "desc",
                                                                                          "asc"
                                                                                      ]
                                                                                  },
                                                                                  {
                                                                                      "bSortable": true,
                                                                                      "mData"    : 'taxonomy',
                                                                                      "mRender"  : function (data, type, row) {
                                                                                          return data;
                                                                                      },
                                                                                      "asSorting": [
                                                                                          "desc",
                                                                                          "asc"
                                                                                      ]
                                                                                  }
                                                                              ],
                                                                              "fnServerParams": function (aoData) {

                                                                                  aoData.push({
                                                                                                  name : 'action',
                                                                                                  value: 'xagio_get_taxonomies'
                                                                                              });

                                                                                  if ($('#TaxonomyType2').length > 0) {

                                                                                      aoData.push({
                                                                                                      name : 'taxonomy',
                                                                                                      value: $('#TaxonomyType2').val()
                                                                                                  });

                                                                                  }
                                                                              },

                                                                              fnInitComplete: function () {
                                                                                  $('.taxonomies-actions2').find('input[type="search"]').addClass('xagio-input-text-mini');
                                                                                  $('.taxonomies-actions2').prepend('<select class=" xagio-input-select xagio-input-select-gray" id="TaxonomyType2">' +
                                                                                                                    '<option value="">– Filter Type –</option>' +
                                                                                                                    tTypes +
                                                                                                                    '</select>');
                                                                              }

                                                                          });
        },
        attachToTaxonomy : function () {

            $(document).on('click', '.attach-to-taxonomy', function (e) {
                e.preventDefault();
                let button = $(this);
                let modal = $("#attachToTaxonomy");
                let taxonomy_id = button.data('id');
                let attach_t = $('#AttachTypeTax').val();
                let group_id = modal.find('[name="group_id"]').val();

                if (attach_t == "") {
                    xagioNotify("danger", "Please select first where to import the data from (Title / Description / H1)! You can select your Group's SEO Title and Description, or your Taxonomy's SEO Title, Descriptions and H1.");
                    return;
                }

                button.disable('Attaching ...');
                $.post(xagio_data.wp_post, 'action=xagio_attach_to_taxonomy&group_id=' + group_id + '&taxonomy_id=' +
                                           taxonomy_id + '&attach_type=' + attach_t, function (d) {
                    button.disable();
                    if (d.status == 'success') {
                        xagioNotify(d.status, d.message);
                        actions.loadProjectManually();
                        modal[0].close();
                    } else {
                        xagioNotify("danger", d.message);
                    }
                });

            });

            $(document).on('click', '.attachToTaxonomy', function (e) {
                e.preventDefault();
                let group_id = $(this).parents('.xagio-group').find('input[name="group_id"]').val();
                let taxonomy_id = $(this).data('taxonomy-id');
                let modal = $("#attachToTaxonomy");

                modal.find('[name="group_id"]').val(group_id);
                modal.find('[name="taxonomy_id"]').val(taxonomy_id);

                taxonomiesTable.fnDraw();

                modal[0].showModal();
            });
        },
        goToTaxonomy     : function () {
            $(document).on('click', '.goToTaxonomy', function (e) {
                if ($(this).attr('href') == '#') {
                    e.preventDefault();
                    xagioNotify("warning", "You must first attach a page in order to use Go to Page/Post.");
                }
            });
        },

        onURLEdit           : function () {
            $(document).on('focus', '[contenteditable="true"]', function () {
                let $this = $(this);
                $this.data('before', $this.html());
                return $this;
            }).on('blur keyup input', '[contenteditable="true"]', function (e) {
                let $this = $(this);
                if ($this.data('before') != $this.html()) {
                    $this.data('before', $this.html());
                }
                return $this;
            });

            $(document).on('paste', '[contenteditable="true"]', function (e) {
                e.preventDefault();
                let $this = $(this);
                let input = $('<input>');
                let text = e.originalEvent.clipboardData.getData("text/plain");
                input.val(text);
                let pasted_text = input.val();
                pasted_text = pasted_text.trim();

                if ($this.data('before') != pasted_text) {
                    $this.data('before', pasted_text);
                }
                document.execCommand("insertHTML", false, pasted_text);
            });

            $(document).on('input', '.url-edit', function (e) {
                let cont = $(this).parents('.url-container');

                let pre = $(this).prev('.pre-url').html();
                let name = $(this).html().replace(/\//g, '');
                let post = $(this).next('.post-url').html();

                cont.find('[name="url"]').val(pre + name + post);
            });
            $(document).on('click', '.pre-url', function (e) {
                e.preventDefault();
                $(this).next().focus().select();
            });
            $(document).on('click', '.post-url', function (e) {
                e.preventDefault();
                $(this).prev().focus().select();
            });
        },
        parseNumber         : function (num) {
            if (num === null || num === "") {
                return '';
            } else {
                if (typeof num === 'string') {
                    num = num.replaceAll(',', '');
                }
                return parseInt(num).toLocaleString();
            }
        },
        cleanComma          : function (num) {
            if (typeof num === 'string') {
                num = num.replaceAll(',', '');
            }

            return num;
        },
        decodeHtml          : function (html) {
            var txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        },
        loadProjectManually : function (button) {
            if (currentProjectID == 0) return;
            $('.xagio-header-actions-in-project').show();
            $('.xagio-header-actions').hide();

            let project_dashboard = $('.project-dashboard');

            $.post(xagio_data.wp_post, 'action=xagio_get_project_info&project_id=' + currentProjectID, function (d) {
                project_dashboard.find('.project-name').html("<i class='xagio-icon xagio-icon-file'></i> #" + d.data.id +
                                                             ": " + d.data.name);
            });

            $.post(xagio_data.wp_post, 'action=xagio_getGroups&project_id=' + currentProjectID + '&post_type=' +
                                       $('#filterPostTypes').val(), function (d) {

                if (typeof button === 'object') {
                    button.disable();
                }

                d.sort((a, b) => {
                    if (a.group_name == null) a.group_name = '';
                    if (b.group_name == null) b.group_name = '';
                    let aa = a.group_name.toLowerCase(),
                        bb = b.group_name.toLowerCase();

                    let matchA = aa.match(/^(\d+)\.\s*(.+)/);
                    let matchB = bb.match(/^(\d+)\.\s*(.+)/);

                    if (matchA && matchB) {
                        let numA = parseInt(matchA[1], 10);
                        let numB = parseInt(matchB[1], 10);

                        if (numA === numB) {
                            let alphaA = matchA[2];
                            let alphaB = matchB[2];
                            return alphaA.localeCompare(alphaB);
                        }
                        return numA - numB;
                    }

                    return aa.localeCompare(bb);
                });


                let projects_table = $('.projects-table');
                let project_groups = $('.project-groups');
                let project_empty = $('.project-empty');


                if (d.length > 0) {
                    project_empty.hide();
                    project_groups.show();

                    let data = project_groups.find('.data');
                    let groups = [];

                    // Remove old loaded groups
                    data.empty();

                    // Render new groups
                    for (let i = 0; i < d.length; i++) {

                        let row = d[i];
                        let template = $('.xagio-group.template').clone();
                        template.removeClass('template');

                        //html entity decode
                        row.title = actions.decodeHtml(row.title);
                        row.group_name = actions.decodeHtml(row.group_name);
                        row.h1 = actions.decodeHtml(row.h1);
                        row.description = actions.decodeHtml(row.description);

                        let magicPage = row.id_taxonomy != null && row.id_taxonomy != '' && row.id_taxonomy != 0 &&
                                        row.id_taxonomy_term != null && row.id_taxonomy_term.taxonomy == 'location';

                        // Set the Post Type
                        if (row.post_type != false) {
                            if (row.post_type !== null) {
                                template.addClass('hasAttachedPost');
                                if (row.id_page_post != null && row.id_page_post != '' && row.id_page_post != 0) {
                                    template.find('.group-seo').addClass('page-attached');
                                    template.find('.attachToPagePost').parents('li').addClass('li-attached');
                                    template.find('.attachToTaxonomy').parents('li').addClass('li-attached');
                                    template.find('.attached').show().html(`<a href="${xagio_data.wp_admin}post.php?post=${row.id_page_post}&action=edit" target="_blank">edit ${row.post_type.replace("_", " ")}</a>`);
                                }
                                if (row.id_taxonomy != null && row.id_taxonomy != '' && row.id_taxonomy != 0 &&
                                    row.id_taxonomy_term != null) {
                                    template.find('.group-seo').addClass('page-attached');
                                    template.find('.attachToPagePost').parents('li').addClass('li-attached');
                                    template.find('.attachToTaxonomy').parents('li').addClass('li-attached');
                                    template.find('.attached').show().html(`<a href="${xagio_data.wp_admin}term.php?taxonomy=${row.id_taxonomy_term.taxonomy}&tag_ID=${row.id_taxonomy}" target="_blank">edit ${row.post_type.replace("_", " ")}</a>`);
                                }
                            }
                        }

                        // Append the Group ID
                        template.find('[name="group_id"]').val(row.id);
                        template.find('.seedKeyword').attr('data-group-id', row.id);
                        template.find('.phraseMatch').attr('data-group-id', row.id);
                        template.find('[name="project_id"]').val(currentProjectID);

                        // Change the Group Name
                        template.find('[name="group_name"]').val(row.group_name);
                        template.attr('data-name', row.group_name);

                        let ai_status = row.ai_status;
                        let ai_input = row.ai_input;

                        if (ai_status == 'running') {
                            template.find('.xag-ai-tools-button').attr('title', 'Getting AI Suggestions');
                            template.find('.xag-ai-tools i.xagio-icon.xagio-icon-robot').removeClass().addClass('xagio-icon xagio-icon-sync xagio-icon-spin');
                            template.find('.optimize-ai i').removeClass().addClass('xagio-icon xagio-icon-sync xagio-icon-spin');
                        } else if (ai_status == 'failed') {
                            template.find('.xag-ai-tools-button').attr('title', 'AI Suggestions Failed');
                            template.find('.xag-ai-tools').addClass('xag-ai-failed').html(`<i class="xagio-icon xagio-icon-ai"></i> <i class="xagio-icon xagio-icon-close"></i>`);
                            template.find('.optimize-ai').attr('data-regenerate', 'yes').html(`<i class="xagio-icon xagio-icon-brain"></i> Regenerate AI Suggestions`);
                            template.find('.createPostPageAi').show();
                        } else if (ai_status == 'completed') {
                            template.find('.xag-ai-tools-button').attr('title', 'AI Suggestions Ready');
                            template.find('.xag-ai-tools').addClass('xag-ai-complete').html(`<i class="xagio-icon xagio-icon-ai"></i> <i class="xagio-icon xagio-icon-check"></i>`);
                            template.find('.optimize-ai').attr('data-regenerate', 'yes').html(`<i class="xagio-icon xagio-icon-brain"></i> Regenerate AI Suggestions`);
                            template.find('.view-ai-suggestions').attr('data-ai-input', ai_input);
                            template.find('.createPostPageAi').show();
                            template.find('.view-ai-li').show();
                        }


                        // Prepare the URL
                        let pURL = actions.prepareURL(row.url);

                        template.find('.attachToPagePost').attr('data-post-id', row.id_page_post);

                        // Go to Page/Post
                        if (row.id_page_post != null && row.id_page_post != '' && row.id_page_post != 0 &&
                            row.post_type !== null) {
                            template.find('.goToPagePost').attr('href', xagio_data.wp_admin + "post.php?post=" +
                                                                        row.id_page_post + "&action=edit");
                            template.find('.attachToPagePost').html('Attach to Page/Post &nbsp;&nbsp; (<i title="Attached to an existing Page/Post already." class="uk-text-success xagio-icon xagio-icon-check"></i>)');
                            template.find('.attachToPagePost').attr('data-group-id', row.id);
                        } else {
                            template.find('.goToPagePost').addClass('hidden');
                            template.find('.detachPagePost').addClass('hidden');
                        }

                        template.find('.attachToTaxonomy').attr('data-taxonomy-id', row.id_taxonomy);

                        // Go to Taxonomy
                        if (row.id_taxonomy != null && row.id_taxonomy != '' && row.id_taxonomy != 0 &&
                            row.id_taxonomy_term != null) {
                            template.find('.goToTaxonomy').attr('href', xagio_data.wp_admin + "term.php?taxonomy=" +
                                                                        row.id_taxonomy_term.taxonomy + "&tag_ID=" +
                                                                        row.id_taxonomy);
                            template.find('.attachToTaxonomy').html('<i class="xagio-icon xagio-icon-target"></i> Attach to Taxonomy &nbsp;&nbsp; (<i title="Attached to an existing Taxonomy already." class="uk-text-success xagio-icon xagio-icon-check"></i>)');
                            template.find('.attachToTaxonomy').attr('data-group-id', row.id);
                        } else {
                            template.find('.goToTaxonomy').addClass('hidden');
                        }

                        // Change the rest of the Group Settings
                        template.find('[name="h1"]').val(row.h1 != null ? row.h1 : '');

                        // Set to read only if location
                        if (magicPage) {
                            template.find('[name="h1"]').attr('disabled', 'disabled');
                            // template.find('.prs-title').attr('contenteditable', 'false');
                            // template.find('.prs-description').attr('contenteditable', 'false');
                            template.find('.url-edit').attr('contenteditable', 'false');
                        }

                        template.find('[name="title"]').val(row.title != null ? row.title : '');
                        template.find('[name="description"]').val(row.description != null ? row.description : '');

                        if (row.h1_sh != row.h1) {
                            template.find('[name="h1"]').attr('value-shortcoded', row.h1_sh);
                            template.find('[name="h1"]').attr('value-original', row.h1);
                        }

                        if (row.title_sh != row.title) {
                            template.find('[name="title"]').attr('value-shortcoded', row.title_sh);
                            template.find('[name="title"]').attr('value-original', row.title);
                        }

                        if (row.description_sh != row.description) {
                            template.find('[name="description"]').attr('value-shortcoded', row.description_sh);
                            template.find('[name="description"]').attr('value-original', row.description);
                        }

                        template.find('[name="notes"]').val(row.notes != null ? row.notes : '');
                        template.find('[name="url"]').val(row.url != null ? row.url : '');

                        if (row.external_domain != null) {
                            if (row.external_domain != '') {
                                template.find('.host-url').html(`http://${row.external_domain}`);
                            }
                        }
                        template.find('.pre-url').html(pURL.pre);
                        template.find('.url-edit').html(pURL.name);
                        template.find('.post-url').html('/');
                        template.find('[name="oriUrl"]').val(row.url != null ? row.url : '');

                        template.find('[data-target="title"]').text(row.title != null ? row.title : '');
                        template.find('[data-target="description"]').text(row.description !=
                                                                          null ? row.description : '');
                        template.find('[data-target="h1tag"]').text(row.h1 != null ? row.h1 : '');

                        // Calculate Counting
                        let count_seo_title, count_seo_title_mobile, count_seo_description, count_seo_description_mobile = 0;

                        if(row.title != null) {
                            count_seo_title = row.title.length;
                            count_seo_title_mobile = row.title.length;
                        }

                        if(count_seo_title > 70) {
                            count_seo_title = `<span class="xagio-seo-count-danger">${count_seo_title}</span>`;
                        }
                        if(count_seo_title_mobile > 78) {
                            count_seo_title_mobile = `<span class="xagio-seo-count-danger">${count_seo_title_mobile}</span>`;
                        }

                        if(row.description != null) {
                            count_seo_description = row.description.length;
                            count_seo_description_mobile = row.description.length;
                        }

                        if(count_seo_description > 300) {
                            count_seo_description = `<span class="xagio-seo-count-danger">${count_seo_description}</span>`;
                        }
                        if(count_seo_description_mobile > 120) {
                            count_seo_description_mobile = `<span class="xagio-seo-count-danger">${count_seo_description_mobile}</span>`;
                        }

                        template.find('.count-seo-title').html(count_seo_title);
                        template.find('.count-seo-title-mobile').html(count_seo_title_mobile);
                        template.find('.count-seo-description').html(count_seo_description);
                        template.find('.count-seo-description-mobile').html(count_seo_description_mobile);

                        // Go through keywords
                        if (row.keywords.length > 0) {

                            let kwData = template.find('.keywords-data');
                            kwData.empty();

                            let groupKeywords = [];

                            for (let k = 0; k < row.keywords.length; k++) {
                                let keyword = row.keywords[k];

                                // remove null values
                                for (let key in keyword) {
                                    if (keyword.hasOwnProperty(key)) {
                                        if (keyword[key] == null) {
                                            keyword[key] = '';
                                        }
                                    }
                                }

                                // Is queued
                                let alsoQueued = false;
                                if (keyword.inurl == -1 && keyword.intitle == -1) {
                                    alsoQueued = true;
                                    keyword.inurl = null;
                                    keyword.intitle = null;
                                }

                                /**
                                 *
                                 *     CONDITIONAL FORMATTING
                                 *
                                 */

                                let volume_color,
                                    cpc_color,
                                    intitle_color,
                                    inurl_color,
                                    tr_color,
                                    ur_color;

                                keyword.volume = actions.cleanComma(keyword.volume);
                                keyword.cpc = actions.cleanComma(keyword.cpc);
                                keyword.intitle = actions.cleanComma(keyword.intitle);
                                keyword.inurl = actions.cleanComma(keyword.inurl);

                                let title_ratio = "";
                                if (keyword.volume != "" && keyword.intitle != "") {
                                    if (keyword.volume != 0) {
                                        title_ratio = keyword.intitle / keyword.volume;
                                    }
                                }

                                let url_ratio = "";
                                if (keyword.volume !== "" && keyword.inurl !== "") {
                                    if (keyword.volume != 0) {
                                        url_ratio = keyword.inurl / keyword.volume;
                                    }
                                }

                                if (keyword.volume === "") {
                                    volume_color = '';
                                } else if (parseFloat(cf_template.volume_red) >= parseFloat(keyword.volume)) {
                                    volume_color = 'tr_red';
                                } else if (parseFloat(cf_template.volume_red) < parseFloat(keyword.volume) &&
                                           parseFloat(cf_template.volume_green) > parseFloat(keyword.volume)) {
                                    volume_color = 'tr_yellow';
                                } else if (parseFloat(cf_template.volume_green) <= parseFloat(keyword.volume)) {
                                    volume_color = 'tr_green';
                                }

                                if (keyword.cpc === "") {
                                    cpc_color = '';
                                } else if (parseFloat(cf_template.cpc_red) >= parseFloat(keyword.cpc)) {
                                    cpc_color = 'tr_red';
                                } else if (parseFloat(cf_template.cpc_red) < parseFloat(keyword.cpc) &&
                                           parseFloat(cf_template.cpc_green) > parseFloat(keyword.cpc)) {
                                    cpc_color = 'tr_yellow';
                                } else if (parseFloat(cf_template.cpc_green) <= parseFloat(keyword.cpc)) {
                                    cpc_color = 'tr_green';
                                }

                                if (keyword.intitle === "") {
                                    intitle_color = '';
                                } else if (parseFloat(cf_template.intitle_red) <= parseFloat(keyword.intitle)) {
                                    intitle_color = 'tr_red';
                                } else if (parseFloat(cf_template.intitle_red) > parseFloat(keyword.intitle) &&
                                           parseFloat(cf_template.intitle_green) < parseFloat(keyword.intitle)) {
                                    intitle_color = 'tr_yellow';
                                } else if (parseFloat(cf_template.intitle_green) >= parseFloat(keyword.intitle)) {
                                    intitle_color = 'tr_green';
                                }

                                if (keyword.inurl === "") {
                                    inurl_color = '';
                                } else if (parseFloat(cf_template.inurl_red) <= parseFloat(keyword.inurl)) {
                                    inurl_color = 'tr_red';
                                } else if (parseFloat(cf_template.inurl_red) > parseFloat(keyword.inurl) &&
                                           parseFloat(cf_template.inurl_green) < parseFloat(keyword.inurl)) {
                                    inurl_color = 'tr_yellow';
                                } else if (parseFloat(cf_template.inurl_green) >= parseFloat(keyword.inurl)) {
                                    inurl_color = 'tr_green';
                                }

                                if (title_ratio === "") {
                                    tr_color = '';
                                } else if (parseFloat(title_ratio) >= parseFloat(cf_template.title_ratio_red)) {
                                    tr_color = 'tr_red';
                                } else if (parseFloat(title_ratio) < parseFloat(cf_template.title_ratio_red) &&
                                           parseFloat(title_ratio) > parseFloat(cf_template.title_ratio_green)) {
                                    tr_color = 'tr_yellow';
                                } else if (parseFloat(title_ratio) <= parseFloat(cf_template.title_ratio_green)) {
                                    tr_color = 'tr_green';
                                }

                                if (url_ratio === "") {
                                    ur_color = '';
                                } else if (parseFloat(url_ratio) >= parseFloat(cf_template.url_ratio_red)) {
                                    ur_color = 'tr_red';
                                } else if (parseFloat(url_ratio) < parseFloat(cf_template.url_ratio_red) &&
                                           parseFloat(url_ratio) > parseFloat(cf_template.url_ratio_green)) {
                                    ur_color = 'tr_yellow';
                                } else if (parseFloat(url_ratio) <= parseFloat(cf_template.url_ratio_green)) {
                                    ur_color = 'tr_green';
                                }

                                /**
                                 *
                                 *     CONDITIONAL FORMATTING
                                 *
                                 */


                                let tr = $('<tr data-queued="' + keyword.queued + '" data-id="' + keyword.id +
                                           '"></tr>');
                                tr.append('<td class="xagio-text-center"><div class="drag-cursor"></div> <input type="checkbox" class="keyword-selection" value="' +
                                          keyword.id + '" name="keywords[]" /></td>');
                                tr.append('<td><div contenteditable="true" class="keywordInput" data-target="keyword">' +
                                          keyword.keyword + '</div></td>');

                                if (keyword.queued == 2) {
                                    tr.append('<td data-target="volume" title="This value is currently under analysis. Please check back later to see the results."><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></td>');
                                    tr.append('<td data-target="cpc" title="This value is currently under analysis. Please check back later to see the results."><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></td>');
                                } else {
                                    tr.append('<td class="' + volume_color +
                                              '"><div contenteditable="true" class="keywordInput" data-target="volume">' +
                                              actions.parseNumber(keyword.volume) + '</div></td>');
                                    tr.append('<td class="' + cpc_color +
                                              '"><div contenteditable="true" class="keywordInput" data-target="cpc">' +
                                              keyword.cpc + '</div></td>');
                                }

                                if (keyword.queued == 1 || alsoQueued == true) {

                                    actions.runBatchCron();

                                    tr.append('<td data-target="intitle" title="This value is currently under analysis. Please check back later to see the results."><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></td>');
                                    tr.append('<td data-target="inurl" title="This value is currently under analysis. Please check back later to see the results."><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></td>');
                                } else {

                                    tr.append('<td data-target="intitle" class="' + intitle_color +
                                              '"><div contenteditable="true" class="keywordInput" data-target="intitle">' +
                                              actions.parseNumber(keyword.intitle) + '</div></td>');
                                    tr.append('<td data-target="inurl" class="' + inurl_color +
                                              '"><div contenteditable="true" class="keywordInput" data-target="inurl">' +
                                              actions.parseNumber(keyword.inurl) + '</div></td>');
                                }

                                if (title_ratio != "") {
                                    if (tr_color == "tr_green" &&
                                        (parseFloat(cf_template.tr_goldbar_volume) >= parseFloat(keyword.volume) &&
                                        parseFloat(cf_template.tr_goldbar_intitle) >= parseFloat(keyword.intitle))) {
                                        tr.append('<td class="xagio-text-center ' + tr_color +
                                                  '" data-target="tr"><div contenteditable="false" class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-title="Value: ' +
                                                  parseFloat(title_ratio).toFixed(3) + '"><img src="' +
                                                  xagio_data.plugins_url + 'assets/img/gold.webp"></div></td>');
                                    } else {
                                        tr.append('<td class="xagio-text-center ' + tr_color +
                                                  '" data-target="tr"><div contenteditable="true" class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-title="Value: ' +
                                                  parseFloat(title_ratio).toFixed(3) + '">' +
                                                  parseFloat(title_ratio).toFixed(3) + '</div></td>');
                                    }
                                } else {
                                    tr.append('<td class="xagio-text-center ' + tr_color +
                                              '" data-target="tr"><div contenteditable="true" class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-title="Search Volume and InTitle metrics must be retrieved first to see the Title Ratio."><i class="xagio-icon xagio-icon-minus"></i></div></td>');
                                }

                                if (url_ratio != "") {
                                    if (ur_color == "tr_green" &&
                                        (parseFloat(cf_template.ur_goldbar_volume) >= parseFloat(keyword.volume) &&
                                        parseFloat(cf_template.ur_goldbar_intitle) >= parseFloat(keyword.inurl))) {
                                        tr.append('<td class="xagio-text-center ' + ur_color +
                                                  '" data-target="ur"><div contenteditable="false" class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-title="Value: ' +
                                                  parseFloat(url_ratio).toFixed(3) + '"><img src="' +
                                                  xagio_data.plugins_url + 'assets/img/gold.webp"></div></td>');
                                    } else {
                                        tr.append('<td class="xagio-text-center ' + ur_color +
                                                  '" data-target="ur"><div contenteditable="true" class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-title="Value: ' +
                                                  parseFloat(url_ratio).toFixed(3) + '">' +
                                                  parseFloat(url_ratio).toFixed(3) + '</div></td>');
                                    }
                                } else {
                                    tr.append('<td class="xagio-text-center ' + ur_color +
                                              '" data-target="ur"><div contenteditable="true" class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-title="Search Volume and InURL metrics must be retrieved first to see the URL Ratio."><i class="xagio-icon xagio-icon-minus"></i></div></td>');
                                }

                                let rank = keyword.rank.isJSON();
                                let rank_cell = '';

                                if (rank == 0) {
                                    rank_cell = '<span data-xagio-tooltip data-xagio-title="Not Added"><i class="xagio-icon xagio-icon-minus"></i><span style="display: none;">99999</span></span>';
                                } else if (rank == 501) {
                                    rank_cell = '<span data-xagio-tooltip data-xagio-title="Analysing..."><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i><span style="display: none;">99998</span></span>';
                                } else {

                                    let max = 501;
                                    let rank_title = '';

                                    if ($.isNumeric(rank)) max = rank;

                                    for (let j = 0; j < rank.length; j++) {
                                        let obj = rank[j];

                                        if (obj.rank != 'NTH' || obj.rank == null) {
                                            if (max > obj.rank) {
                                                max = obj.rank;
                                            }
                                            if (typeof obj.rank == 'undefined') {
                                                obj.rank = "<i class='xagio-icon xagio-icon-ban'></i>";
                                            }
                                            rank_title += obj.engine + ' : ' + obj.rank + '<br>';
                                        } else {
                                            rank_title += obj.engine + ' : <i class=\'xagio-icon xagio-icon-ban\'></i><br>';
                                        }

                                    }

                                    if (max == 501) {
                                        rank_cell = '<a href="https://app.xagio.net/rank_tracker?domain=' + xagio_data.domain +
                                                    '&keyword=' + encodeURIComponent(keyword.keyword) +
                                                    '" target="_blank" data-xagio-tooltip data-xagio-title="' +
                                                    rank_title +
                                                    '"><i class=\'xagio-icon xagio-icon-ban\'></i><span style="display: none;">99997</span></a>';
                                    } else {
                                        if ($.isNumeric(rank)) {
                                            rank_cell = max;
                                        } else {
                                            rank_cell = '<a href="https://app.xagio.net/rank_tracker?domain=' + xagio_data.domain +
                                                        '&keyword=' + encodeURIComponent(keyword.keyword) +
                                                        '" target="_blank" data-xagio-tooltip data-xagio-title="' +
                                                        rank_title + '">' + max + '</a>';
                                        }
                                    }

                                }

                                tr.append('<td class="text-center">' + rank_cell + '</td>');

                                groupKeywords.push(tr);
                            }

                            kwData.append(groupKeywords);
                        }

                        groups.push(template);
                    }

                    data.append(groups);

                } else {
                    project_empty.show();
                    project_groups.hide();
                }

                projects_table.slideUp("fast", function () {
                    actions.updateElements();
                    actions.updateGrid();
                });
                project_dashboard.slideDown("fast", function () {
                    setTimeout(function () {
                        if (actions.getUrlParameters('gid')) {
                            let view_group_id = actions.getUrlParameters('gid');
                            var target = $(`input[name="group_id"][value="${view_group_id}"]`).parents('.xagio-group');
                            if (target.length)
                            {
                                var top = target.offset().top - 120;
                                $('html,body').animate({scrollTop: top}, 1000);
                            }
                        }
                    }, 500);
                });

            });
        },
        loadProject         : function () {
            $(document).on('click', '.load_project', function (e) {
                e.preventDefault();
                currentProjectID = $(this).data('id');
                currentProjectName = $(this).data('name');

                let button = $(this);
                button.disable();

                $('.sort-groups-asc').removeClass('uk-active').hide();
                $('.uk-active').removeClass('uk-active').addClass('uk-active').show();

                $('.logo-paragraph.uk-block-xagio').slideUp();
                actions.importKeywordPlanner();
                actions.loadProjectManually(button);
            });
        },
        duplicateProject    : function () {
            $(document).on('click', '.duplicate_project', function () {
                let button = $(this);
                let project_id = button.data('id');

                button.disable();
                $.post(xagio_data.wp_post, 'action=xagio_duplicate_project&project_id=' + project_id, function (d) {
                    button.disable();
                    actions.loadProjects();

                    xagioNotify(d.status, d.message);
                });
            });
        },
        removeAlertProjectID: function () {
            $.post(xagio_data.wp_post, 'action=xagio_remove_alert_project_id', function (d) {
                // actions.removeAlertProjectID();
            });
        },
        backToProjects      : function () {
            $(document).on('click', '.closeProject', function (e) {

                let currentUrl = window.location.href;
                let newUrl = currentUrl.replace(/(\?|&)(pid=\d+|gid=\d+)(&|$)/g, function(match, p1, p2, p3) {
                    if (p1 === '?' && p3 === '&') {
                        return '?';
                    } else if (p1 === '&' && p3 === '&') {
                        return '&';
                    } else {
                        return '';
                    }
                });

                newUrl = newUrl.replace(/(\?|&)$/, '');
                window.history.pushState({}, '', newUrl);

                function runBack() {
                    $('.xagio-header-actions-in-project').hide();
                    $('.xagio-header-actions').show();

                    let project_dashboard = $('.project-dashboard');
                    let projects_table = $('.projects-table');

                    $('.logo-paragraph.uk-block-xagio').slideDown();
                    projects_table.slideDown();
                    project_dashboard.slideUp();
                    currentProjectID = 0;
                }

                if (activeChanges) {
                    xagioModal("Unsaved Changes", "You have unsaved changes in your Project! Continue?", function (result) {
                        if (result) runBack();
                    });
                } else {
                    runBack();
                }


            });
        },
        removeProject       : function () {
            $(document).on('click', '.remove_project', function (e) {
                e.preventDefault();
                let id = $(this).data('id');
                let modal = $('#deleteProject');

                modal.find('#projectId').val(id);
                modal[0].showModal();

            });

            $(document).on('click', '.delete-project', function () {
                let btn = $(this);

                let modal = btn.parents('.xagio-modal');
                let deleteRanks = modal.find('#deleteProjectRanks').is(':checked');
                let project_id = modal.find('#projectId').val();
                btn.disable();

                $.post(xagio_data.wp_post, 'action=xagio_remove_project&project_id=' + project_id + '&deleteRanks=' +
                                           deleteRanks, function (d) {
                    modal[0].close();
                    btn.disable();
                    actions.loadProjects();
                    xagioNotify("success", "Project has been removed.");
                });
            });

            $('#deleteProject')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('#deleteProjectRanks').val(0).prop('checked', false);
                modal.find('#projectId').val('');
            });
        },
        loadProjects        : function () {
            let project_table = $('.pTable').dataTable({
                                                           "dom"           : '<"clear">rt<"xagio-table-bottom"lp><"clear">',
                                                           "bDestroy"      : true,
                                                           "bPaginate"     : true,
                                                           "bAutoWidth"    : false,
                                                           "bFilter"       : true,
                                                           "sServerMethod" : "POST",
                                                           "sAjaxSource"   : xagio_data.wp_post,
                                                           "iDisplayLength": 10,
                                                           "language"      : {
                                                               "emptyTable": "<div class='xagio-buttons-flex xagio-flex-align-center'><a href='#' class='xagio-button xagio-button-primary new-project'><i class='xagio-icon xagio-icon-plus'></i> Create My First Project</a>" +
                                                                             "<a href='#importProject' data-uk-modal class='xagio-button xagio-button-primary'><i class='xagio-icon xagio-icon-download'></i> Import Existing Project</a></div>"
                                                           },
                                                           "aLengthMenu"   : [
                                                               [
                                                                   5,
                                                                   10,
                                                                   50,
                                                                   100,
                                                                   -1
                                                               ],
                                                               [
                                                                   5,
                                                                   10,
                                                                   50,
                                                                   100,
                                                                   "All"
                                                               ]
                                                           ],
                                                           "aaSorting"     : [
                                                               [
                                                                   0,
                                                                   'desc'
                                                               ]
                                                           ],
                                                           "aoColumns"     : [
                                                               {
                                                                   "sClass"   : "",
                                                                   "bSortable": true,
                                                                   "mData"    : "id",
                                                                   "mRender"  : function (data, type, row) {
                                                                       return data;
                                                                   }
                                                               },
                                                               {
                                                                   "sClass"   : "",
                                                                   "bSortable": true,
                                                                   "mData"    : "project_name",
                                                                   "mRender"  : function (data, type, row) {
                                                                       return `<b>${data.replace(/\\/g, '')}</b> <i title="Rename Project" class="xagio-icon xagio-icon-edit rename_project" data-id="${row.id}" data-name="${data.replace(/\\/g, '')}"></i>`;
                                                                   }
                                                               },
                                                               {
                                                                   "sClass"   : "",
                                                                   "bSortable": true,
                                                                   "mData"    : "date_created",
                                                                   "mRender"  : function (data, type, row) {
                                                                       return new Date(data).toDateString();
                                                                   }
                                                               },
                                                               {
                                                                   "sClass"   : "xagio-text-center",
                                                                   "bSortable": true,
                                                                   "mData"    : "groups",
                                                                   "mRender"  : function (data, type, row) {
                                                                       if (data ===
                                                                           "0") return `<i class="xagio-icon xagio-icon-minus"></i>`;
                                                                       return `<b>${data}</b>`;
                                                                   }
                                                               },
                                                               {
                                                                   "sClass"   : "xagio-text-center",
                                                                   "bSortable": true,
                                                                   "mData"    : "keywords",
                                                                   "mRender"  : function (data, type, row) {
                                                                       if (data ===
                                                                           "0") return `<i class="xagio-icon xagio-icon-minus"></i>`;
                                                                       return `<b>${data}</b>`;
                                                                   }
                                                               },
                                                               {
                                                                   "sClass"   : "xagio-text-center",
                                                                   "bSortable": true,
                                                                   "mData"    : "shared",
                                                                   "mRender"  : function (data, type, row) {
                                                                       let buttons = '';
                                                                       let share_button = '';
                                                                       let share_enabled = 0;
                                                                       let share_checked = '';

                                                                       if (data != null) {
                                                                           let share_url = xagio_data.wp_admin.replace('wp-admin/', '') +
                                                                                           'shared-seo-report?hash=' +
                                                                                           data;
                                                                           share_enabled = 1;
                                                                           share_checked = 'checked="checked"';
                                                                           share_button += `<button data-shared-url="${share_url}" title="Shared link"  data-toggle="tooltip" data-placement="top" class="xagio-button xagio-button-primary xagio-button-mini shared_project_link"><i class="xagio-icon xagio-icon-external-link"></i> </button> `;
                                                                       }

                                                                       buttons += `<label class="switch" title="Enable/Disable"><input type="checkbox" data-id="${row.id}" value="${share_enabled}" class="on-off-share" ${share_checked}><div class="slider round"></div></label>`;
                                                                       buttons += share_button;

                                                                       buttons = `<div class="share_btn_cell">${buttons}</div>`

                                                                       return buttons;
                                                                   }
                                                               },
                                                               {
                                                                   "sClass"   : "xagio-text-center",
                                                                   "bSortable": false,
                                                                   "mRender"  : function (data, type, row) {
                                                                       if (row.status != 'queued') {
                                                                           let buttons = '';

                                                                           buttons += '<div class="xagio-cell-actions-row xagio-flex-align-center">';
                                                                           if (alertProjectID === row.id) {
                                                                               buttons += '<div data-name="' +
                                                                                          row.project_name +
                                                                                          '" data-id="' + row.id +
                                                                                          '" class="project-alert" style="display:inline;" ><img src="' +
                                                                                          xagio_data.plugins_url +
                                                                                          'assets/img/logo-nag-xagio.webp" alt="Alert logo" width="30" height="30"></div> ';
                                                                           } else {
                                                                               buttons += '<div data-name="' +
                                                                                          row.project_name +
                                                                                          '" data-id="' + row.id +
                                                                                          '" class="project-alert" style="display:none;" ><img src="' +
                                                                                          xagio_data.plugins_url +
                                                                                          'assets/img/logo-nag-xagio.webp" alt="Alert logo" width="30" height="30"></div> ';
                                                                           }
                                                                           buttons += '<button data-name="' +
                                                                                      row.project_name +
                                                                                      '" data-id="' + row.id +
                                                                                      '" data-xagio-tooltip data-xagio-title="Load this project" type="button" class="xagio-button xagio-button-primary xagio-button-mini load_project"><i class="xagio-icon xagio-icon-folder-open"></i></button> ';
                                                                           buttons += `<button data-name="${row.project_name}" data-id="${row.id}" data-xagio-tooltip data-xagio-title="Duplicate this project" type="button" class="xagio-button xagio-button-primary xagio-button-mini duplicate_project"><i class="xagio-icon xagio-icon-copy"></i></button> `;
                                                                           buttons += '<button data-id="' + row.id +
                                                                                      '" data-xagio-tooltip data-xagio-title="Export this project" type="button" class="xagio-button xagio-button-primary xagio-button-mini export_project"><i class="xagio-icon xagio-icon-download"></i></button> ';

                                                                           buttons += '<button data-id="' + row.id +
                                                                                      '" data-xagio-tooltip data-xagio-title="Remove this project permanently" type="button" class="xagio-button xagio-button-danger xagio-button-mini remove_project"><i class="xagio-icon xagio-icon-delete"></i></button> ';

                                                                           buttons += '</div>';
                                                                           return buttons;
                                                                       } else {
                                                                           return '<i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Automatically generating groups... ' +
                                                                                  '<button data-id="' + row.id +
                                                                                  '" data-xagio-tooltip data-xagio-title="Cancel this operation" type="button" class="xagio-button xagio-button-danger xagio-button-mini remove_project"><i class="xagio-icon xagio-icon-delete"></i></button> '
                                                                       }
                                                                   }
                                                               }
                                                           ],
                                                           "fnServerParams": function (aoData) {
                                                               aoData.push({
                                                                               name : 'action',
                                                                               value: 'xagio_get_projects'
                                                                           });
                                                           },
                                                       });

            $(document).on('keyup', '.search-projects', function () {
                $('.pTable').dataTable().fnFilter($(this).val());
            });
        },
        /*Export Selected Groups*/
        exportAllProjects: function () {
            $(document).on('click', '.export-all-projects', function (e) {
                e.preventDefault();
                window.location = xagio_data.wp_post + '?action=xagio_export_projects' + '&_xagio_nonce=' + xagio_data.nonce;
            })
        },
        /*Export Selected Groups*/
        exportGroups: function () {
            $(document).on('click', '.exportGroups', function () {
                let ids = [];
                $('.project-groups .xagio-group .groupSelect:checked').each(function () {
                    let group = $(this).parents('.xagio-group');
                    ids.push(group.find('[name="group_id"]').val());
                });

                if (ids.length < 1) {
                    xagioNotify("warning", "Please select at least one group");
                    return false;
                }

                ids = ids.join(",");

                window.location = xagio_data.wp_post + '?action=xagio_export_groups&group_ids=' + ids + '&_xagio_nonce=' + xagio_data.nonce;
            })
        },
        /*Export Selected Keywords*/
        exportKeywords: function () {
            $(document).on('click', '.exportKeywords', function () {
                let keywordIds = [];
                $('.xagio-group:not(.template) .keyword-selection:checked').each(function () {
                    keywordIds.push($(this).val());
                });

                if (keywordIds.length < 1) {
                    xagioNotify("warning", "Please select at least one keyword");
                    return false;
                }

                keywordIds = keywordIds.join(",");

                window.location = xagio_data.wp_post + '?action=xagio_export_keywords&keyword_ids=' + keywordIds + '&_xagio_nonce=' + xagio_data.nonce;
            })
        },
        /*Export Import Projects*/
        exportProject         : function () {
            $(document).on('click', '.export_project', function () {
                let project_id = $(this).attr('data-id');
                window.location = xagio_data.wp_post + '?action=xagio_export_project&project_id=' + project_id + '&_xagio_nonce=' + xagio_data.nonce;
            })
        },
        importProject         : function () {
            $('#importProject').xagio_uploader('xagio_import_project', actions.loadProjects);
        },
        importKWS             : function () {
            $(document).on('click', '.importKWS', function () {
                pong = false;
                let popup = window.open(KWS_Origin + "?redirect=/results_area");
                let interval = setInterval(function () {
                    if (popup && !popup.closed && pong == false) {
                        popup.postMessage("ping-" + document.location.origin, KWS_Origin);
                    } else {
                        clearInterval(interval);
                    }
                }, 500);
            });
        },
        importKeywordPlanner  : function () {
            $('#importKeywordPlanner').xagio_uploader('xagio_import_keyword_planner&project=' + currentProjectID, actions.loadProjectManually);
        },
        createPagePostMulti   : function () {
            $(document).on('click', '.createPagesPosts', function (e) {
                e.preventDefault();

                let table = $('.pagePostAllTableTemplate.xagio-hidden').clone().removeClass('xagio-hidden');
                let tr = table.find('.tr_template');
                let body = table.find('.body_template').html('');

                table.find('.body_template').html('');
                let counter = 0;
                $('.project-groups .xagio-group').each(function () {

                    let group_name = $(this).find('input[name="group_name"]').val();
                    let group_id = $(this).find('input[name="group_id"]').val();
                    tr.find('.group_name').html(group_name).attr('data-id', group_id);
                    tr.find('.xagio-radio-btn-holder input[type="radio"]').attr('name', `post_or_page_${counter}`);
                    tr.find('.page_selection input[type="radio"]').attr('id', `select_page_${counter}`);
                    tr.find('.page_selection label').attr('for', `select_page_${counter}`);

                    tr.find('.post_selection input[type="radio"]').attr('id', `select_post_${counter}`);
                    tr.find('.post_selection label').attr('for', `select_post_${counter}`);
                    body.append('<tr>' + tr.html() + '</tr>');

                    counter++;
                });

                let mod = $('#pagePostMulti');
                mod.find('.table_holder_all').html(table);
                mod[0].showModal();
            });

            $(document).on('click', '.pagePostMultiBtn', function (e) {
                e.preventDefault();

                let modal = $(this).parents('#pagePostMulti');
                let table = modal.find('.pagePostAllTableTemplate');
                let tr = table.find('.body_template tr');

                tr.each(function () {
                    let current_tr = $(this);

                    let group_id = current_tr.find('.group_name').attr('data-id');
                    let type = current_tr.find('.createMultiResults input[type="radio"]:checked').val();
                    current_tr.find('.createMultiResults').html('<i class="xagio-icon xagio-icon-gear xagio-icon-spin"></i>');

                    let data = {
                        action      : 'xagio_create_page_post',
                        group_id    : group_id,
                        type        : type,
                        request_type: 'multi'
                    };

                    $.ajaxq("pagePostMulti", {
                        url    : xagio_data.wp_post,
                        type   : 'post',
                        data   : data,
                        cache  : false,
                        success: function (d) {

                            // let group_id = d.group_id;

                            let icon = '';
                            let info_class = '';
                            if (d.status == 'error') {
                                icon = '<i class="xagio-icon xagio-icon-warning uk-text-warning"></i>';
                                info_class = 'tr_danger';
                            }

                            let url = '';
                            if (d.status == 'success') {
                                icon = '<i class="xagio-icon xagio-icon-check uk-text-success"></i>';
                                url = '<br><a href="' + d.data.url + '" target="_blank">' + d.data.url + '</a>';
                                info_class = 'tr_check';
                            }
                            if (d.status == 'warning') {
                                icon = '<i class="xagio-icon xagio-icon-warning uk-text-warning"></i>';
                                url = '<br><a href="' + d.data.url + '" target="_blank">' + d.data.url + '</a>';
                                info_class = 'tr_danger';
                            }

                            $('td[data-id="' + group_id +
                              '"]').parents('tr').addClass(info_class).find('.createMultiResults').html(icon + ' ' +
                                                                                                        d.message +
                                                                                                        url);
                        }
                    });
                });
            });

            $(document).on('click', 'div[data-uk-button-radio] button[aria-checked]', function (e) {
                e.preventDefault();
            });
        },
        calculateAndTrim      : function (t) {
            let words_split = [];
            for (let i = 0; i < t.length; i++) {
                words_split.push(t[i].split(' '));
            }
            words_split = [].concat.apply([], words_split);
            let words = [];

            for (let i = 0; i < words_split.length; i++) {
                let check = 0;
                let final = {
                    text    : '',
                    weight  : 0,
                    html    : {
                        'data-xagio-title'  : 0,
                        'data-xagio-tooltip': ''
                    },
                    handlers: {

                        click: function (e) {

                            // Vars
                            let p = $(this).parents('.xagio-group');
                            let title = p.find('.prs-title');
                            let desc = p.find('.prs-description');
                            let url = p.find('.url-edit');
                            let h1Tag = p.find('.prs-h1tag');

                            if ($(e.currentTarget).hasClass('highlightWordInCloud')) {
                                $(e.currentTarget).removeClass('highlightWordInCloud');
                            } else {
                                $(e.currentTarget).addClass('highlightWordInCloud');
                            }

                            // Remove b tag from title, desciption, url, H1
                            p.find('.updateGroup b').each(function () {
                                title.html(title.html().replace(/<b class="highlightCloud">(.+)<\/b>/gi, "$1"));
                                desc.html(desc.html().replace(/<b class="highlightCloud">(.+)<\/b>/gi, "$1"));
                                url.html(url.html().replace(/<b class="highlightCloud">(.+)<\/b>/gi, "$1"));
                                h1Tag.html(h1Tag.html().replace(/<b class="highlightCloud">(.+)<\/b>/gi, "$1"));
                            });

                            // Remove b tag from keywords
                            for (let m = 0; m < 15; m++) {
                                p.find('.keywordInput[data-target="keyword"]').each(function () {
                                    $(this).html($(this).html().replace(/<b class="highlightCloud">(.+)<\/b>/gi, "$1"));
                                });
                            }

                            // Add b tag in title, desciption, url, keywords, H1
                            p.find('.cloud.template.seen.jqcloud').find('.highlightWordInCloud').each(function () {
                                let t = $(this).text();
                                let title_matches = title.html().match(new RegExp($(this).text(), 'gi'));
                                let desc_matches = desc.html().match(new RegExp($(this).text(), 'gi'));
                                let url_matches = url.html().match(new RegExp($(this).text(), 'gi'));
                                let h1Tag_matches = h1Tag.html().match(new RegExp($(this).text(), 'gi'));

                                if (title_matches !== null) {
                                    for (let j = 0; j < title_matches.length; j++) {
                                        const titleMatch = title_matches[j];
                                        const titleReg = new RegExp(`\\b(${titleMatch})\\b`, "g");
                                        title.html(title.html().replace(titleReg, '<b class="highlightCloud">' +
                                                                                  titleMatch + '</b>'));
                                    }
                                }

                                if (desc_matches !== null) {
                                    for (let j = 0; j < desc_matches.length; j++) {
                                        const descMatch = desc_matches[j];
                                        const descReg = new RegExp(`\\b(${descMatch})\\b`, "g");
                                        desc.html(desc.html().replace(descReg, '<b class="highlightCloud">' +
                                                                               descMatch + '</b>'));

                                    }
                                }

                                if (url_matches !== null) {
                                    for (let j = 0; j < url_matches.length; j++) {
                                        const urlMatch = url_matches[j];
                                        const urlReg = new RegExp(`\\b(${urlMatch})\\b`, "g");
                                        url.html(url.html().replace(urlReg, '<b class="highlightCloud">' + urlMatch +
                                                                            '</b>'));

                                    }
                                }

                                if (h1Tag_matches !== null) {
                                    for (let j = 0; j < h1Tag_matches.length; j++) {
                                        const h1TagMatch = h1Tag_matches[j];
                                        const h1TagReg = new RegExp(`\\b(${h1TagMatch})\\b`, "g");
                                        h1Tag.html(h1Tag.html().replace(h1TagReg, '<b class="highlightCloud">' +
                                                                                  h1TagMatch + '</b>'));

                                    }
                                }

                                p.find('.keywordInput[data-target="keyword"]').each(function () {
                                    let keyword_matches = $(this).html().match(new RegExp(t, 'gi'));
                                    if (keyword_matches != null) {
                                        for (let j = 0; j < keyword_matches.length; j++) {
                                            const keywordMatch = keyword_matches[j];
                                            const keywordReg = new RegExp(`\\b(${keywordMatch})\\b`, "g");
                                            $(this).html($(this).html().replace(keywordReg, '<b class="highlightCloud">' +
                                                                                            keywordMatch + '</b>'));
                                        }
                                    }
                                });
                            });
                        }
                    }
                };
                for (let j = 0; j < words.length; j++) {
                    if (words_split[i] == words[j].text && words_split[i].length >= 2) {
                        check = 1;
                        ++words[j].weight;
                        ++words[j].html['data-xagio-title'];
                    }
                }
                if (check == 0 && words_split[i].length >= 2) {
                    final.text = words_split[i];
                    final.weight = 1;
                    final.html["data-xagio-title"] = 1;
                    words.push(final);
                }
                check = 0;
            }

            return words;
        },
        calculateKeywordWeight: function (t) {
            let words_split = [];
            for (let i = 0; i < t.length; i++) {
                words_split.push(t[i].split(' '));
            }
            words_split = [].concat.apply([], words_split);
            let words = [];

            for (let i = 0; i < words_split.length; i++) {
                let check = 0;
                let final = {
                    text  : '',
                    weight: 0
                };
                for (let j = 0; j < words.length; j++) {
                    if (words_split[i] == words[j].text && words_split[i].length >= 2) {
                        check = 1;
                        ++words[j].weight;
                    }
                }
                if (check == 0 && words_split[i].length >= 2) {
                    final.text = words_split[i];
                    final.weight = 1;
                    words.push(final);
                }
                check = 0;
            }

            return words;
        },
        formatSEO             : function (t) {
            $(document).on('change, paste, keyup, input', '.prs-title', function (e) {
                $(this).prev('input').val($(this).text());


                let wordCount = $(this).html().replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
                if (wordCount > 70) {
                    $(this).parents('.group-seo').find('.count-seo-title').html('<span class="xagio-seo-count-danger">' + wordCount +
                                                                                '</span>');
                } else {
                    $(this).parents('.group-seo').find('.count-seo-title').html(wordCount);
                }

                if (wordCount > 78) {
                    $(this).parents('.group-seo').find('.count-seo-title-mobile').html('<span class="xagio-seo-count-danger">' +
                                                                                       wordCount + '</span>');
                } else {
                    $(this).parents('.group-seo').find('.count-seo-title-mobile').html(wordCount);
                }

            });

            $(document).on('change, paste, keyup, input', '.prs-description', function (e) {
                $(this).prev('input').val($(this).text());

                let wordCount = $(this).html().replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;

                if (wordCount > 300) {
                    $(this).parents('.group-seo').find('.count-seo-description').html('<span class="xagio-seo-count-danger">' +
                                                                                      wordCount + '</span>');
                } else {
                    $(this).parents('.group-seo').find('.count-seo-description').html(wordCount);
                }

                if (wordCount > 120) {
                    $(this).parents('.group-seo').find('.count-seo-description-mobile').html('<span class="xagio-seo-count-danger">' +
                                                                                             wordCount + '</span>');
                } else {
                    $(this).parents('.group-seo').find('.count-seo-description-mobile').html(wordCount);
                }
            });
        },

        switchToSilo: function () {
            $(document).on('click', '.switch-to-silo', function (e) {
                e.preventDefault();

                if ($('.project-silo').hasClass('xagio-hidden')) {

                    $(this).html('<i class="xagio-icon xagio-icon-link-off"></i> Project Planner');
                    $('.project-dashboard').addClass('xagio-hidden');
                    $('.projects-table').addClass('xagio-hidden');
                    $('.project-silo').removeClass('xagio-hidden');
                    actions.initSilo();

                } else {

                    $(this).html('<i class="xagio-icon xagio-icon-link"></i> Silo Builder');
                    $('.project-dashboard').removeClass('xagio-hidden');
                    $('.projects-table').removeClass('xagio-hidden');
                    $('.project-silo').addClass('xagio-hidden');

                }

            });
        },

        getOperatorData: function ($element) {

            let data = {
                properties: {
                    title  : $element.data('text'),
                    inputs : {},
                    outputs: {}
                }
            };

            let type = $element.data('type');

            if (type == 'page') {
                data.properties.inputs['ins'] = {
                    label   : 'Child',
                    multiple: true
                };
                data.properties.outputs['output_1'] = {
                    label: 'Parent'
                };
            } else if (type == 'post') {
                data.properties.outputs['outs'] = {
                    label   : 'Parent',
                    multiple: true
                };
            } else if (type == 'tag') {
                data.properties.inputs['ins'] = {
                    label   : 'Post',
                    multiple: true
                };
            } else if (type == 'category') {
                data.properties.inputs['ins'] = {
                    label   : 'Post',
                    multiple: true
                };
            }

            let uniqueID = ' op-' + type + '-' + $element.data('id');
            data.properties.class = 'operator-' + type + uniqueID;
            data.properties.ID = uniqueID;
            data.properties.realID = $element.data('id');

            return data;
        },

        createSilo: function (element) {

            let $flowchart = $(element);
            let $container = $flowchart.parent();

            // Panzoom initialization...
            $flowchart.panzoom();

            // Panzoom zoom handling...
            let possibleZooms = [
                0,
                0.5,
                1
            ];
            let currentZoom = 1;

            $container.on('mousewheel.focal', function (e) {
                e.preventDefault();
                let delta = (e.delta || e.originalEvent.wheelDelta) || e.originalEvent.detail;
                let zoomOut = !(delta ? delta < 0 : e.originalEvent.deltaY > 0); // natural scroll direciton
                currentZoom = Math.max(0, Math.min(possibleZooms.length - 1, (currentZoom + (zoomOut * 2 - 1))));
                $flowchart.flowchart('setPositionRatio', possibleZooms[currentZoom]);
                $flowchart.panzoom('zoom', possibleZooms[currentZoom], {
                    animate: false,
                    focal  : e
                });

            });

            // Apply the plugin on a standard, empty div...
            $flowchart.flowchart({
                                     defaultLinkColor: '#559acc',
                                     onOperatorCreate: function (operatorId, operatorData, fullElement) {

                                         let uniqueID = '.' + operatorData.properties.ID.trim();
                                         let flowchart = actions.siloGetFlowchart();
                                         if (flowchart.find(uniqueID).length > 0) {
                                             xagioNotify("warning", "Invalid operation, element is already added to the Silo.");
                                             return false;
                                         }

                                         return true;
                                     }
                                 });

        },

        redrawLinks: function () {
            $('.silo.pages').flowchart('redrawLinksLayer');
            $('.silo.posts').flowchart('redrawLinksLayer');
        },

        initSilo: function () {
            if (siloInitialized) return;
            siloInitialized = true;

            actions.loadSiloPagesPosts();
            actions.addToSilo();

            actions.loadTagsCategoriesSilo();

            actions.createSilo('.silo.pages');
            actions.createSilo('.silo.posts')

            actions.loadSilo();

            $(document).on('click', '.uk-tab > li > a', function (e) {
                e.preventDefault();
                actions.redrawLinks();
            });
        },

        loadSilo: function () {
            $.post(xagio_data.wp_post, 'action=xagio_load_silo', function (d) {
                $('.silo.pages').flowchart('setData', JSON.parse(d.data.pages));
                $('.silo.posts').flowchart('setData', JSON.parse(d.data.posts));
            });
        },

        loadTagsCategoriesSilo: function () {
            $.post(xagio_data.wp_post, 'action=xagio_get_tags_categories', function (d) {
                let cats = $('.silo-category');
                let tags = $('.silo-tag');

                cats.empty();
                tags.empty();

                for (let i = 0; i < d.data.tags.length; i++) {
                    let tag = d.data.tags[i];
                    tags.append('<div class="draggable_operator" data-id="' + tag.name +
                                '" data-type="tag" data-text="' + tag.name + '">' + tag.name + '</div>');
                }

                for (let i = 0; i < d.data.categories.length; i++) {
                    let cat = d.data.categories[i];
                    cats.append('<div class="draggable_operator" data-id="' + cat.term_id +
                                '" data-type="category" data-text="' + cat.name + '">' + cat.name + '</div>');
                }

                actions.initDrag($('.draggable_operator'));

            });
        },

        addToSilo: function () {
            $(document).on('click', '.silo-add', function (e) {
                e.preventDefault();

                let $element = $(this);

                let data = {
                    properties: {
                        title  : $element.data('text'),
                        inputs : {},
                        outputs: {}
                    }
                };

                let type = $element.data('type');

                if (type == 'page') {
                    data.properties.inputs['ins'] = {
                        label   : 'Child',
                        multiple: true
                    };
                    data.properties.outputs['output_1'] = {
                        label: 'Parent'
                    };
                } else if (type == 'post') {
                    data.properties.outputs['outs'] = {
                        label   : 'Parent',
                        multiple: true
                    };
                }

                let uniqueID = ' op-' + type + '-' + $element.data('id');
                data.properties.class = 'operator-' + type + uniqueID;
                data.properties.ID = uniqueID;
                data.properties.realID = $element.data('id');


                $('.silo.' + type + 's').flowchart('addOperator', data);
            });
        },

        siloGetFlowchart: function (elements) {
            if (typeof elements != 'undefined') {
                return elements.parents('.tab').find('.silo');
            } else {
                let pages = $('.silo.pages');
                let posts = $('.silo.posts');
                if (pages.is(':visible')) {
                    return pages;
                } else {
                    return posts;
                }
            }
        },

        removeSilo: function () {
            $(document).on('click', '.silo-remove', function (e) {
                e.preventDefault();

                let $flowchart = actions.siloGetFlowchart();
                $flowchart.flowchart('deleteSelected');

            });

            document.addEventListener('keydown', function (event) {
                const key = event.key; // const {key} = event; ES6+
                if (key === "Delete") {
                    let $flowchart = actions.siloGetFlowchart();
                    $flowchart.flowchart('deleteSelected');
                }
            });
        },

        initDrag: function (elements) {

            let $flowchart = actions.siloGetFlowchart(elements);
            let $container = $flowchart.parent();

            elements.draggable({
                                   cursor : "move",
                                   opacity: 0.7,

                                   appendTo: 'body',
                                   zIndex  : 1000,

                                   helper: function (e) {
                                       let $this = $(this);
                                       let data = actions.getOperatorData($this);
                                       return $flowchart.flowchart('getOperatorElement', data);
                                   },
                                   stop  : function (e, ui) {
                                       let $this = $(this);
                                       let elOffset = ui.offset;
                                       let containerOffset = $container.offset();
                                       if (elOffset.left > containerOffset.left && elOffset.top > containerOffset.top &&
                                           elOffset.left < containerOffset.left + $container.width() && elOffset.top <
                                           containerOffset.top + $container.height()) {

                                           let flowchartOffset = $flowchart.offset();

                                           let relativeLeft = elOffset.left - flowchartOffset.left;
                                           let relativeTop = elOffset.top - flowchartOffset.top;

                                           let positionRatio = $flowchart.flowchart('getPositionRatio');
                                           relativeLeft /= positionRatio;
                                           relativeTop /= positionRatio;

                                           let data = actions.getOperatorData($this);
                                           data.left = relativeLeft;
                                           data.top = relativeTop;

                                           $flowchart.flowchart('addOperator', data);
                                       }
                                   }
                               });

        },

        loadSiloPagesPosts: function () {

            $('.siloPagesTable').dataTable({
                                               language        : {
                                                   search           : "_INPUT_",
                                                   searchPlaceholder: "Search pages...",
                                                   processing       : "Loading Pages...",
                                                   emptyTable       : "No pages found on this website.",
                                                   info             : "_START_ to _END_ of _TOTAL_ pages",
                                                   infoEmpty        : "0 to 0 of 0 pages",
                                                   infoFiltered     : "(from _MAX_ total pages)"
                                               },
                                               "dom"           : '<fl>rt<ip>',
                                               "bDestroy"      : true,
                                               "searchDelay"   : 350,
                                               "bPaginate"     : true,
                                               "bAutoWidth"    : false,
                                               "bFilter"       : true,
                                               "bProcessing"   : true,
                                               "sServerMethod" : "POST",
                                               "bServerSide"   : true,
                                               "sAjaxSource"   : xagio_data.wp_post,
                                               "iDisplayLength": 5,
                                               "aLengthMenu"   : [
                                                   [
                                                       5,
                                                       10,
                                                       50,
                                                       100
                                                   ],
                                                   [
                                                       5,
                                                       10,
                                                       50,
                                                       100
                                                   ]
                                               ],
                                               "aaSorting"     : [
                                                   [
                                                       1,
                                                       'desc'
                                                   ]
                                               ],
                                               "aoColumns"     : [
                                                   {
                                                       "sClass"   : "text-left",
                                                       "bSortable": true,
                                                       "mData"    : 'post_title',
                                                       "mRender"  : function (data, type, row) {
                                                           return "<b class='post-title'>" + data + "</b>" +
                                                                  "<div class='row-actions'>"

                                                                  + "<a href='" + row.guid +
                                                                  "' target='_blank' class='view'><i class='xagio-icon xagio-icon-search'></i></a>"

                                                                  + " <span>|</span> "

                                                                  + "<a href='" + xagio_data.wp_admin +
                                                                  'post.php?post=' + row.ID + '&action=edit' +
                                                                  "' target='_blank' class='edit'><i class='xagio-icon xagio-icon-edit'></i></a>"

                                                                  + " <span>|</span> "

                                                                  + "<a href='#' class='silo-add' data-id='" + row.ID +
                                                                  "' data-text='" + data +
                                                                  "' data-type='page'><i class='xagio-icon xagio-icon-arrows'></i> Add</a>"

                                                                  + "</div>";
                                                       },
                                                       "asSorting": [
                                                           "desc",
                                                           "asc"
                                                       ]
                                                   },
                                                   {
                                                       "bSortable": true,
                                                       "mData"    : 'post_date',
                                                       "mRender"  : function (data, type, row) {
                                                           return '<b>' + row.post_status.charAt(0).toUpperCase() +
                                                                  row.post_status.slice(1) + 'ed</b>' + '<br>' +
                                                                  '<abbr title="' + data + '">' +
                                                                  new Date(data).toUTCString().split(' ').splice(0, 4).join(' ') +
                                                                  '</abbr>';
                                                       },
                                                       "asSorting": [
                                                           "desc",
                                                           "asc"
                                                       ]
                                                   }
                                               ],
                                               "fnServerParams": function (aoData) {

                                                   aoData.push({
                                                                   name : 'action',
                                                                   value: 'xagio_get_posts'
                                                               });

                                                   aoData.push({
                                                                   name : 'PostsType',
                                                                   value: 'page'
                                                               });
                                               },

                                               "fnDrawCallback": function (oSettings) {
                                                   actions.initDrag($(this).find('tr.draggable-row'));
                                               },

                                               "fnRowCallback": function (nRow, aData, iDisplayIndex, iDisplayIndexFull) {
                                                   $(nRow).addClass('draggable-row').attr('data-type', 'page').attr('data-text', aData.post_title).attr('data-id', aData.ID);
                                               }


                                           });
            $('.siloPostsTable').dataTable({
                                               language        : {
                                                   search           : "_INPUT_",
                                                   searchPlaceholder: "Search posts...",
                                                   processing       : "Loading Posts...",
                                                   emptyTable       : "No posts found on this website.",
                                                   info             : "_START_ to _END_ of _TOTAL_ posts",
                                                   infoEmpty        : "0 to 0 of 0 posts",
                                                   infoFiltered     : "(from _MAX_ total posts)"
                                               },
                                               "dom"           : '<fl>rt<ip>',
                                               "bDestroy"      : true,
                                               "searchDelay"   : 350,
                                               "bPaginate"     : true,
                                               "bAutoWidth"    : false,
                                               "bFilter"       : true,
                                               "bProcessing"   : true,
                                               "sServerMethod" : "POST",
                                               "bServerSide"   : true,
                                               "sAjaxSource"   : xagio_data.wp_post,
                                               "iDisplayLength": 5,
                                               "aLengthMenu"   : [
                                                   [
                                                       5,
                                                       10,
                                                       50,
                                                       100
                                                   ],
                                                   [
                                                       5,
                                                       10,
                                                       50,
                                                       100
                                                   ]
                                               ],
                                               "aaSorting"     : [
                                                   [
                                                       1,
                                                       'desc'
                                                   ]
                                               ],
                                               "aoColumns"     : [
                                                   {
                                                       "sClass"   : "text-left",
                                                       "bSortable": true,
                                                       "mData"    : 'post_title',
                                                       "mRender"  : function (data, type, row) {
                                                           return "<b class='post-title'>" + data + "</b>" +
                                                                  "<div class='row-actions'>"

                                                                  + "<a href='" + row.guid +
                                                                  "' target='_blank' class='view'><i class='xagio-icon xagio-icon-search'></i></a>"

                                                                  + " <span>|</span> "

                                                                  + "<a href='" + xagio_data.wp_admin +
                                                                  'post.php?post=' + row.ID + '&action=edit' +
                                                                  "' target='_blank' class='edit'><i class='xagio-icon xagio-icon-edit'></i></a>"

                                                                  + " <span>|</span> "

                                                                  + "<a href='#' class='silo-add' data-id='" + row.ID +
                                                                  "' data-text='" + data +
                                                                  "' data-type='post'><i class='xagio-icon xagio-icon-arrows'></i> Add</a>"

                                                                  + "</div>";
                                                       },
                                                       "asSorting": [
                                                           "desc",
                                                           "asc"
                                                       ]
                                                   },
                                                   {
                                                       "bSortable": true,
                                                       "mData"    : 'post_date',
                                                       "mRender"  : function (data, type, row) {
                                                           return '<b>' + row.post_status.charAt(0).toUpperCase() +
                                                                  row.post_status.slice(1) + 'ed</b>' + '<br>' +
                                                                  '<abbr title="' + data + '">' +
                                                                  new Date(data).toUTCString().split(' ').splice(0, 4).join(' ') +
                                                                  '</abbr>';
                                                       },
                                                       "asSorting": [
                                                           "desc",
                                                           "asc"
                                                       ]
                                                   }
                                               ],
                                               "fnServerParams": function (aoData) {

                                                   aoData.push({
                                                                   name : 'action',
                                                                   value: 'xagio_get_posts'
                                                               });


                                                   aoData.push({
                                                                   name : 'PostsType',
                                                                   value: 'post'
                                                               });

                                               },

                                               "fnDrawCallback": function (oSettings) {
                                                   actions.initDrag($(this).find('tr.draggable-row'));
                                               },

                                               "fnRowCallback": function (nRow, aData, iDisplayIndex, iDisplayIndexFull) {
                                                   $(nRow).addClass('draggable-row').attr('data-type', 'post').attr('data-text', aData.post_title).attr('data-id', aData.ID);
                                               }

                                           });

        },

        // Save Silo functionality
        saveSilo: function () {
            $(document).on('click', '.silo-save', function (e) {
                e.preventDefault();

                let silo_pages = $('.silo.pages').flowchart('getData');
                let silo_posts = $('.silo.posts').flowchart('getData');

                $.post(xagio_data.wp_post, {
                    action: 'xagio_save_silo',
                    pages : JSON.stringify(silo_pages),
                    posts : JSON.stringify(silo_posts)
                }, function (d) {
                    if (d.status == 'success') {
                        xagioNotify("success", "Silo Builder has been successfully saved.");
                    }
                });


            });
        },

        getSavedKeywordSettingsLanguageAndCountry: function () {
            let saved_language = $('#getCompetition_languageCode').attr('data-default');
            let saved_country = $('#getCompetition_locationCode').attr('data-default');

            if (saved_language != '') {
                $('#getVolAndCpc_languageCode').val(saved_language).trigger('change');
                $('#getCompetition_languageCode').val(saved_language).trigger('change');
            }
            if (saved_country != '') {
                $('#getVolAndCpc_locationCode').val(saved_country).trigger('change');
                $('#getCompetition_locationCode').val(saved_country).trigger('change');
            }
        },

        volumeAndCpcOnChangeLanguage: function () {
            $("#getVolAndCpc_languageCode").on('change', function () {
                let language = $(this).val();

                xagioModal("Save Default Language", "Do you want to make this as a default language?", function (yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, `action=xagio_set_default_keyword_language&language=${language}`, function (d) {
                            xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                        });
                    }
                })
            })
        },

        volumeAndCpcOnChangeCountry: function () {
            $("#getVolAndCpc_locationCode").on('change', function () {
                let country = $(this).val();

                xagioModal("Save Default Country", "Do you want to make this as a default country?", function (yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, `action=xagio_set_default_keyword_country&country=${country}`, function (d) {
                            xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                        });
                    }
                })
            })
        },

        competitionChangeLanguage: function () {
            $("#getCompetition_languageCode").on('change', function () {
                let language = $(this).val();

                xagioModal("Save Default Language", "Do you want to make this as a default language?", function (yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, `action=xagio_set_default_keyword_language&language=${language}`, function (d) {
                            xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                        });
                    }
                })
            })
        },

        competitionChangeCountry: function () {
            $("#getCompetition_locationCode").on('change', function () {
                let country = $(this).val();

                xagioModal("Save Default Country", "Do you want to make this as a default country?", function (yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, `action=xagio_set_default_keyword_country&country=${country}`, function (d) {
                            xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                        });
                    }
                })
            })
        },

        setDefaultAuditLocation: function () {
            let auditLanguageSelect = $("#auditWebsite_lang");

            let data = auditLanguageSelect.data('default');
            if (data) {
                let splitData = data.split(',');

                let value = splitData[0];
                let locationCode = splitData[1];

                // set also hidden input field value
                $("#auditWebsite_langCode").val(locationCode);

                $('#auditWebsite_lang option').removeAttr('selected');
                $(`#auditWebsite_lang option[value=${value}][data-lang-code=${locationCode}]`).attr('selected', true);

                let auditModal = $('#auditWebsiteModal');

                $('#auditWebsite_lang').select2({
                    matcher       : matcher,
                    dropdownParent: auditModal,
                    width: '100%',
                    placeholder   : "Select Location"
                });

                // set also hidden input field value
                $("#auditWebsite_langCode_internal").val(locationCode);

                $('#auditWebsite_lang_internal option').removeAttr('selected');
                $(`#auditWebsite_lang_internal option[value=${value}][data-lang-code=${locationCode}]`).attr('selected', true);

                let auditModalInternal = $('#auditWebsiteModalInternal');

                $('#auditWebsite_lang_internal').select2({
                    matcher       : matcher,
                    dropdownParent: auditModalInternal,
                    width: '100%',
                    placeholder   : "Select Location"
                });
            }
        },

        setDefaultAiWizardSearchEngine: function () {
            let engineSelect = $("#top_ten_search_engine");
            let value = engineSelect.data('default');

            if (value) {
                $('#top_ten_search_engine option').removeAttr('selected');
                $(`#top_ten_search_engine option[value=${value}]`).attr('selected', true);
            }
        },

        setDefaultAiWizardLocation: function () {
            let engineSelect = $("#top_ten_search_location");
            let value = engineSelect.data('default');

            if (value) {
                $('#top_ten_search_location option').removeAttr('selected');
                $(`#top_ten_search_location option[value=${value}]`).attr('selected', true);
            }
        }

    };

})(jQuery);


function tst() {

    var splitRegex = /\s|[:\?\!\.,'"\$]+\s?/;
    var splittedKeywords = this.keywords.split(/\n/g);

    if (void 0 !==
        this.stemmerLanguages[this.selectedLang]) var c = t(95).newStemmer(this.stemmerLanguages[this.selectedLang]);

    for (var holderObject = {}, index = 0; index < splittedKeywords.length; index++) {

        var words = splittedKeywords[index].split(splitRegex);

        if (void 0 !== this.seed) {

            var d = this.seed.split(splitRegex);
            this.keywordTokens = d

        } else {

            d = [];
            var v = m[this.selectedLang] || [];

            if (words = i.tokens.removeWords(words, b(v)), words = i.tokens.removeWords(words, b(d)), void 0 !==
                                                                                                      this.stemmerLanguages[this.selectedLang]) {
                var p = [];
                for (const n in words) p.push(c.stem(words[n]));
                this.stemmedTokens.push(p)
            } else p = words;
            for (const n in p) this.stems[p[n]] = words[n];
            for (var s = l(2)(words).concat(l(3)(p)), g = 0; g < s.length; g++) {
                var f = s[g].join(" ");
                void 0 === holderObject[f] && (holderObject[f] = []), holderObject[f].push(splittedKeywords[index])
            }

        }
    }
    var u = u = Object.keys(holderObject);
    u.sort((function (n, a) {
        return holderObject[a].length - holderObject[n].length
    }));
    for (var h = [], x = 0; x < u.length; x++) {
        var w = u[x].split(splitRegex),
            k = [];
        for (var y in w) k.push(this.stems[w[y]] || w[y]);
        var z = k.join(" ");
        holderObject[u[x]].length < 50 && holderObject[u[x]].length > 2 && h.push({
                                                                                      title   : z,
                                                                                      keywords: holderObject[u[x]]
                                                                                  })
    }
    return this.keywordgroups = holderObject, this.cachedgroups = h, h

}
