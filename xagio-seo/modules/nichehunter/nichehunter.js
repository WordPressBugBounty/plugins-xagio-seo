var history_chart = null;
let selected_keywords = {};
let table = '';

let loading_keywords_tr = '<tr><td colspan="10" class="loading-niche-keywords">Loading keywords... <i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></td></tr>';

let NICHE_HUNTER_COST = "";
let COMPETITION_COST = "";
let nicheCompetitionBatchCron;

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

    $(document).ready(function () {

        actions.initSliders();
        actions.submitForm();
        actions.getHistory();
        actions.loadHistoryItem();
        actions.refreshXags();

        actions.checkDomain();
        actions.openGoogleLinks();
        actions.openQuora();
        actions.viewGoogleTrends();
        actions.viewSearchVolumeHistory();
        actions.loadCfTemplates();
        actions.openCompetitionModal();
        actions.getCompetition();
        actions.competitionSingleKeyword();
        actions.clearSelectedKeywords();
        actions.selectAllKeywords();
        actions.deleteKeywords();
        actions.deleteHistoryGroup();
        actions.copyToClipboard();
        actions.searchNicheHistory();

        actions.allowances = {
            xags_allowance        : $('#xags-allowance'),
            xags                  : $('#xags')
        };

        let myTLDsSelect = $("#mytld");
        let selectedTLDs = myTLDsSelect.attr('data-tags');
        myTLDsSelect.select2({
            width: 'resolve',
            placeholder: "Select your favorite TLD's.",
            maximumSelectionLength: 5
        });

        if(selectedTLDs.length > 0){
            myTLDsSelect.val(selectedTLDs.split(',')).trigger('change');
        }

        $("#getCompetition_languageCode").select2({
            width: '100%',
            dropdownParent: $("#getHunterCompetitionModal")
        });

        $("#getCompetition_locationCode").select2({
            width: '100%',
            dropdownParent: $("#getHunterCompetitionModal")
        });

        $(document).on('submit', '.niche-tlds-settings', function (e) {
            e.preventDefault();
            let form = $(this);
            let button = form.find('button[type="submit"]');
            let selected_tlds = $('#mytld').val();

            button.disable();

            $.post(xagio_data.wp_post, form.serialize(), function (d) {
                button.disable();
                xagioNotify("success","Setting updated.");
            });
        });


        $(document).on('click', '.copy-keywords-to-project', function (e) {
            let copyToProjectPlannerModal = $('#copyToProjectPlannerModal');


            let input = $('#moveToProjectInput');


            $.post(xagio_data.wp_post, 'action=xagio_get_projects', function (d) {

                input.empty();

                input.append('<option value="">Select a Project / Create a new Project</option>');

                for (let i = 0; i < d.aaData.length; i++) {
                    let o = d.aaData[i];
                    input.append('<option value="' + o.id + '">' + o.project_name + '</option>');
                }

                input.select2({
                    dropdownParent: copyToProjectPlannerModal.find('.xagio-modal-body'),
                    placeholder   : "Select a Project / Create a new Project",
                    width: "100%",
                    tags          : true
                });

                $('#moveToGroupInput').select2({
                    dropdownParent: copyToProjectPlannerModal.find('.xagio-modal-body'),
                    placeholder   : "Select a Group / Create a Group",
                    width: "100%",
                    tags          : true
                });

                copyToProjectPlannerModal[0].showModal();
            });

        });

        $(document).on('click', '.copy-niche-keywords', function (e) {
            let selected_project = $('#moveToProjectInput').val();
            let selected_group   = $('#moveToGroupInput').val();
            let modal = $('#copyToProjectPlannerModal');
            let modal_btn = modal.find('.copy-niche-keywords');

            let form_data = new FormData();

            let count = 0;
            for (const key in selected_keywords) {
                form_data.append(`keywords[${count}][keyword]`, selected_keywords[key].keyword);
                form_data.append(`keywords[${count}][volume]`, selected_keywords[key].volume);
                form_data.append(`keywords[${count}][cpc]`, selected_keywords[key].cpc);
                form_data.append(`keywords[${count}][intitle]`, selected_keywords[key].intitle);
                form_data.append(`keywords[${count}][inurl]`, selected_keywords[key].inurl);
                count++;
            }

            form_data.append('action', 'xagio_submit_niche_keywords');
            form_data.append('project_id', selected_project);
            form_data.append('group_id', selected_group);

            modal_btn.disable();
            $.ajax({
                url        : xagio_data.wp_post,
                type       : 'POST',
                data       : form_data,
                processData: false, // Necessary for FormData
                contentType: false, // Necessary for FormData
                success    : function (d) {
                    modal_btn.disable();
                    xagioNotify("success", d.message, true);

                    modal.find('#moveToProjectInput').val(null).trigger('change');
                    modal.find('#moveToGroupInput').val(null).trigger('change');
                    modal[0].close();
                }
            });

        });

        $(document).on('change', '#moveToProjectInput', function () {
            let select = $(this);
            let project_id = select.val();
            let select_groups = $('#moveToGroupInput');
            let copyToProjectPlannerModal = $('#copyToProjectPlannerModal');

            $.post(xagio_data.wp_post, `action=xagio_get_groups&project_id=${project_id}`, function (d) {

                select_groups.empty();

                select_groups.append('<option value="">Select a Group / Create a Group</option>');

                for (let i = 0; i < d.aaData.length; i++) {
                    let o = d.aaData[i];
                    select_groups.append('<option value="' + o.id + '">' + o.group_name + '</option>');
                }

                select_groups.select2({
                    dropdownParent: copyToProjectPlannerModal.find('.xagio-modal-body'),
                    placeholder   : "Select a Group / Create a Group",
                    tags          : true
                });

            });
        });


        $(document).on('input', '.select-niche-keywords', function () {
            if ($('.select-niche-keywords:checked').length === $('.select-niche-keywords').length) {
                $(".select-all-niche-keywords").prop('checked', true);
            } else {
                $(".select-all-niche-keywords").prop('checked', false);
            }

            let checkbox = $(this);
            let id = checkbox.data('id');
            let keyword = checkbox.data('keyword');
            let volume = checkbox.data('volume');
            let cpc = checkbox.data('cpc');
            let intitle = checkbox.data('intitle');
            let inurl = checkbox.data('inurl');

            if (checkbox.hasClass('selected')) {
                checkbox.removeClass('selected');
            } else {
                checkbox.addClass('selected');
            }

            if(checkbox.prop('checked')) {
                selected_keywords[id] = {
                    "keyword" : keyword,
                    "volume" : volume,
                    "cpc" : cpc,
                    "intitle" : intitle,
                    "inurl" : inurl,
                };
            } else {
                delete selected_keywords[id];
            }

            let size = Object.keys(selected_keywords).length;
            if(size > 0){
                $('.copy-keywords-to-project-container').show();
                $('.niche-selected-keywords').html(size);
                $('.delete-keywords span').text(size);
            } else {
                $('.copy-keywords-to-project-container').hide();
                $('.niche-selected-keywords').html('');
                $('.delete-keywords span').text('0');
            }

        });

        $(document).on('input', '.select-all-niche-keywords', function () {
            let all_checkboxes = $('.select-niche-keywords');

            let allChecked = false;

            if ($('.select-niche-keywords:checked').length === all_checkboxes.length) {
                allChecked = true;
            }

            if (allChecked) {
                all_checkboxes.each(function (i) {
                    let id = $(this).data('id');

                    if($(this).prop('checked')) {
                        delete selected_keywords[id];
                        $(this).prop('checked', false);
                    }
                });
            } else {
                all_checkboxes.each(function (i) {
                    let id = $(this).data('id');
                    let keyword = $(this).data('keyword');
                    let volume = $(this).data('volume');
                    let cpc = $(this).data('cpc');
                    let intitle = $(this).data('intitle');
                    let inurl = $(this).data('inurl');

                    if(!$(this).prop('checked')) {
                        selected_keywords[id] = {
                            "keyword" : keyword,
                            "volume" : volume,
                            "cpc" : cpc,
                            "intitle" : intitle,
                            "inurl" : inurl,
                        };
                        $(this).prop('checked', true);
                    }
                });
            }

            let size = Object.keys(selected_keywords).length;
            if(size > 0){
                $('.copy-keywords-to-project-container').show();
                $('.niche-selected-keywords').html(size);
                $('.delete-keywords span').text(size);
            } else {
                $('.copy-keywords-to-project-container').hide();
                $('.niche-selected-keywords').html('');
                $('.delete-keywords span').text('0');
            }
        });
    });

    let actions = {
        xagsCostOutput: function (cost) {
            let xReview = parseFloat(actions.allowances.xags_allowance.find('.value').html().trim());
            let xBank = parseFloat(actions.allowances.xags.find('.value').html().trim());

            let output = "";
            if (cost <= xReview) {
                output = `<div><img class="xags" src="${siteUrl}/assets/img/logos/xRenew.png" alt="xRenew"/><span>${cost}</span></div>`;
            } else if (xReview == 0) {
                output = `<div><img class="xags" src="${siteUrl}/assets/img/logos/xBanks.png" alt="xBanks"/><span>${cost}</span></div>`;
            } else if (xBank > cost || (xReview + xBank) >= cost) {
                let remaining_cost = parseFloat(cost - xReview).toFixed(2);

                output = `<div><img class="xags" src="${siteUrl}/assets/img/logos/xRenew.png" alt="xRenew"/><span>${xReview}</span></div> and <div><img class="xags" src="${siteUrl}/assets/img/logos/xBanks.png" alt="xBanks"/><span>${remaining_cost}</span></div>`;
            }

            return output;
        },
        loadCfTemplates : function () {
            $.post(xagio_data.wp_post, 'action=xagio_getCfTemplates', function (d) {
                if (d.status == 'success') {
                    cf_templates = $.extend(cf_templates, d.data)
                }

                let template = cf_templates[d.default];

                // Set default template globally
                cf_template = template.data;
                cf_default_template = d.default;
            }, 'json');
        },
        viewSearchVolumeHistory: function() {
            let now = new Date();
            let months = ["Jan", "Feb", "Mar", "Apr", "May","June", "July", "Aug", "Sep", "Oct", "Nov", "Dec"];
            let prev_months = [];
            for(let i=0; i<=11;i++){
                now.setMonth(now.getMonth() - 1);
                prev_months.push(months[now.getMonth()]);
            }
            prev_months = prev_months.reverse();
            $(document).on('click', '.view_history_graph', function () {

                let btn = $(this);
                let search_volume = btn.data('history');
                let keyword = btn.data('keyword');

                if(search_volume !== '') {
                    search_volume = search_volume.split(',');

                    let ctx = $('#history_graph');
                    if(history_chart != null) {
                        history_chart.destroy();
                    }
                    history_chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            datasets: [{
                                data: search_volume,
                                label: keyword,
                                backgroundColor: 'rgba(138, 205, 255, 0.51)'
                            }],
                            labels: prev_months
                        },
                        options: {
                            elements: {
                                line: {
                                    tension: 0
                                }
                            },
                            scales: {
                                y: {
                                    ticks: {
                                        beginAtZero : true
                                    }
                                }
                            }
                        }
                    });
                }
            });

        },
        viewGoogleTrends    : function () {
            $(document).on('click', '.view_google_trends', function () {

                let keyword = $(this).attr('data-keyword');
                let container = $('#googleTrendsContainer');
                container.empty();

                trends.embed.renderExploreWidgetTo(
                    document.getElementById('googleTrendsContainer'),
                    "TIMESERIES",
                    {
                        "comparisonItem": [
                            {
                                "keyword": keyword,
                                "geo"    : "US",
                                "time"   : "today 12-m"
                            }
                        ],
                        "category"      : 0,
                        "property"      : ""
                    },
                    {
                        "exploreQuery": "q=" + encodeURIComponent(keyword) + "&geo=US&date=today 12-m",
                        "guestPath"   : "https://trends.google.com:443/trends/embed/"
                    }
                );

            });
        },
        openQuora           : function () {
            $(document).on('click', '.open_quora', function (e) {
                e.preventDefault();

                let keyword = $(this).data('keyword');
                window.open('https://www.quora.com/search?q=' + keyword);

            });
        },
        openGoogleLinks     : function () {
            $(document).on('click', '.multiple-links', function (e) {
                e.preventDefault();

                let google_co = [
                    'id',
                    'th',
                    'uk',
                    'vi'
                ];
                let google_com = [
                    'ar',
                    'bn',
                    'et',
                    'tw',
                    'sl',
                    'sv',
                    'tr'
                ];
                let google = [
                    "tl",
                    "pl",
                    "ro",
                    "sr",
                    "cn",
                    "sk",
                    "lv",
                    "lt",
                    "ms",
                    "mk",
                    "it",
                    "fi",
                    "hu",
                    "de",
                    "az",
                    "ru",
                    "nl",
                    "bg",
                    "hr",
                    "fr",
                    "pt",
                    "es",
                    "rs"
                ];

                let countries = {
                    "Algeria"             : "google.dz",
                    "Angola"              : "google.co.ao",
                    "Argentina"           : "google.com.ar",
                    "Australia"           : "google.com.au",
                    "Austria"             : "google.at",
                    "Bahrain"             : "google.com.bh",
                    "Bangladesh"          : "google.com.bd",
                    "Armenia"             : "google.am",
                    "Belgium"             : "google.be",
                    "Bolivia"             : "google.com.bo",
                    "Brazil"              : "google.com.br",
                    "Bulgaria"            : "google.bg",
                    "Myanmar"             : "google.com",
                    "Cambodia"            : "google.com",
                    "Canada"              : "google.ca",
                    "Sri Lanka"           : "google.com",
                    "Chile"               : "google.cl",
                    "Taiwan"              : "google.com",
                    "Colombia"            : "google.com.co",
                    "Costa Rica"          : "google.co.cr",
                    "Croatia"             : "google.hr",
                    "Cyprus"              : "google.com.cy",
                    "Czechia"             : "google.cz",
                    "Denmark"             : "google.dk",
                    "Ecuador"             : "google.com.ec",
                    "El Salvador"         : "google.com",
                    "Estonia"             : "google.ee",
                    "Finland"             : "google.fi",
                    "France"              : "google.fr",
                    "Germany"             : "google.de",
                    "Greece"              : "google.gr",
                    "Guatemala"           : "google.com.gt",
                    "Hong Kong"           : "google.com.hk",
                    "Hungary"             : "google.hu",
                    "India"               : "google.com",
                    "Indonesia"           : "google.co.id",
                    "Ireland"             : "google.ie",
                    "Israel"              : "google.com",
                    "Italy"               : "google.it",
                    "Japan"               : "google.co.jp",
                    "Jordan"              : "google.com",
                    "Kenya"               : "google.com",
                    "South Korea"         : "google.com",
                    "Latvia"              : "google.com",
                    "Lithuania"           : "google.com",
                    "Malaysia"            : "google.com",
                    "Malta"               : "google.com",
                    "Mexico"              : "google.com.mx",
                    "Morocco"             : "google.com",
                    "Netherlands"         : "google.com",
                    "New Zealand"         : "google.co.nz",
                    "Nicaragua"           : "google.com",
                    "Nigeria"             : "google.com.ng",
                    "Norway"              : "google.no",
                    "Pakistan"            : "google.com.pk",
                    "Paraguay"            : "google.com",
                    "Peru"                : "google.com.pe",
                    "Philippines"         : "google.com.ph",
                    "Poland"              : "google.pl",
                    "Portugal"            : "google.com",
                    "Romania"             : "google.ro",
                    "Russia"              : "google.ru",
                    "Saudi Arabia"        : "google.com.sa",
                    "Serbia"              : "google.rs",
                    "Singapore"           : "google.com.sg",
                    "Slovakia"            : "google.sk",
                    "Vietnam"             : "google.com.vn",
                    "Slovenia"            : "google.si",
                    "South Africa"        : "google.co.za",
                    "Spain"               : "google.es",
                    "Sweden"              : "google.se",
                    "Switzerland"         : "google.ch",
                    "Thailand"            : "google.co.th",
                    "United Arab Emirates": "google.ae",
                    "Tunisia"             : "google.tn",
                    "Turkey"              : "google.com.tr",
                    "Ukraine"             : "google.com.ua",
                    "Egypt"               : "google.com.eg",
                    "United Kingdom"      : "google.co.uk",
                    "Uruguay"             : "google.com",
                    "Venezuela"           : "google.com"
                }

                let btn = $(this);
                let attr = btn.attr('data-keyword');
                let language = btn.attr('data-language');
                let engine = 'www.google.com';

                if (language === 'zh_tw') language = 'tw';
                if (language === 'zh_cn') language = 'cn';
                if (language === 'sr') language = 'rs';

                if ($.inArray(language, google) !== -1) engine = `www.google.${language}`;
                if ($.inArray(language, google_com) !== -1) engine = `www.google.com.${language}`;
                if ($.inArray(language, google_co) !== -1) engine = `www.google.co.${language}`;

                if (/^[A-Z]/.test(language)) if (countries.hasOwnProperty(language)) engine = `www.${countries[language]}`;

                if (language.indexOf('www.') >= 0) {
                    language = language.replace('http://', '');
                    engine = language;
                }

                xagioModal("Notice", "Please make sure to allow popups in your browser for this domain, Xagio will attempt to open multiple URLs at the same time after you click Continue.", function (ok) {
                    if (ok) {

                        $.post(xagio_data.wp_post, 'action=xagio_niche_hunter_get_windows', function (d) {

                            if(!d.broad && !d.phrase && !d.intitle && !d.inurl) {
                                xagioNotify("danger","All options for new window open are turned off. Go to Settings and switch on Broad/Phrase/inTitle or inURL options.");
                                return false;
                            }

                            if(d.broad) {
                                window.open(`https://${engine}/search?q=${attr}`);
                            }
                            if(d.phrase) {
                                window.open(`https://${engine}/search?q="${attr}"`);
                            }

                            if(d.intitle) {
                                window.open(`https://${engine}/search?q=intitle:"${attr}"`);
                            }
                            if(d.inurl) {
                                window.open(`https://${engine}/search?q=inurl:"${attr}"`);
                            }

                        });

                    }
                })

            });
        },
        checkDomain         : function () {
            $(document).on('click', '.check_domain', function () {

                let $this = $(this);
                let domain = $(this).data('domain');

                $(".checkDomainTable").empty().append('<tr><td colspan="3"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Loading...</td></tr>');

                $this.disable();

                $.post(xagio_data.wp_post, 'action=xagio_niche_hunter_check_domain&domain=' + domain, function (d) {

                    $this.disable();

                    let html = "";

                    if (d.status === 'error') {
                        xagio_notify(d.status, d.message);
                        return false;
                    }

                    for (let i = 0; i < d.data.length; i++) {

                        let nData = d.data[i];
                        let availableDomain = "No";
                        let registerDomain = '<button type="button" class="xagio-button xagio-button-disabled" disabled><i class="xagio-icon xagio-icon-close"></i> Not Available</button>';

                        if (nData.available) {
                            availableDomain = "Yes";
                            registerDomain = `<div class="xagio-flex"><a href="https://shareasale.com/r.cfm?b=518802&u=1233639&m=46483&urllink=https://www.namecheap.com/domains/registration/results.aspx?domain=${nData.domain}&afftrack=kws" target="_blank" class="xagio-button xagio-button-warning"><i class="xagio-icon xagio-icon-check"></i> Buy Now!</a></div>`;
                        }

                        html += `<tr>
                                                <td>${nData.domain}</td>
                                                <td>${registerDomain}</td>
                                            </tr>`;
                    }

                    $(".checkDomainTable").html(html);

                })

            })
        },
        refreshXags      : function () {
            $.post(xagio_data.wp_post, 'action=xagio_refreshXags', function (d) {
                if (d.status == 'error') {

                    actions.allowances.xags.find('.value').html(0);
                    actions.allowances.xags_allowance.find('.value').html(0);

                } else {
                    NICHE_HUNTER_COST = d.data.xags_cost["hunter"];
                    COMPETITION_COST = d.data.xags_cost["comp"];

                    actions.allowances.xags_allowance.find('.value').html(parseFloat(d.data['xags_allowance']).toFixed(2));

                    if(d.data['xags'] > 0) {
                        actions.allowances.xags.find('.value').html(parseFloat(d.data['xags']).toFixed(2));
                    } else {
                        actions.allowances.xags.hide();
                        $('.xags-divider').hide();
                    }

                    actions.allowances.xags_total = d.data['xags_total'];
                }
            });
        },
        loadHistoryItem     : function () {
            $(document).on('click', '.hunter-single-history-item', function (e) {
                e.preventDefault();

                $(".hunter-single-history-item").removeClass("selected");
                $(this).addClass("selected");

                let id = $(this).attr('data-id');
                let lang = $(this).attr('data-language');
                let keyword = $(this).attr('data-keyword');
                let filters = $(this).attr('data-filters');

                $('input[name="filters[keyword]"]').val(keyword);

                filters = JSON.parse(atob(filters));
                for (const filtersKey in filters) {

                    switch (filtersKey) {
                        case "keyword-exclude":
                            $('input[name="filters[keyword_exclude]"]').val(filters[filtersKey]);
                            break;
                        case "keyword_like":
                            $('select[name="keyword_like"]').val(filters[filtersKey]);
                            break;
                        case "gms-min":
                            $(`input[name="${filtersKey}"]`).val(filters[filtersKey]);
                            $(`.${filtersKey}`).val(filters[filtersKey]).trigger('input');
                            $(`.${filtersKey}`)[0].dispatchEvent(new Event('input', {bubbles: true}));
                            break;
                        case "gms-max":
                            $(`input[name="${filtersKey}"]`).val(filters[filtersKey]);
                            $(`.${filtersKey}`).val(filters[filtersKey]).trigger('input');
                            $(`.${filtersKey}`)[0].dispatchEvent(new Event('input', {bubbles: true}));
                            break;
                        case "cpc-min":
                            $(`input[name="${filtersKey}"]`).val(filters[filtersKey]);
                            $(`.${filtersKey}`).val(filters[filtersKey]).trigger('input');
                            $(`.${filtersKey}`)[0].dispatchEvent(new Event('input', {bubbles: true}));
                            break;
                        case "cpc-max":
                            $(`input[name="${filtersKey}"]`).val(filters[filtersKey]);
                            $(`.${filtersKey}`).val(filters[filtersKey]).trigger('input');
                            $(`.${filtersKey}`)[0].dispatchEvent(new Event('input', {bubbles: true}));
                            break;
                        case "cpm-min":
                            $(`input[name="${filtersKey}"]`).val(filters[filtersKey]);
                            $(`.${filtersKey}`).val(filters[filtersKey]).trigger('input');
                            $(`.${filtersKey}`)[0].dispatchEvent(new Event('input', {bubbles: true}));
                            break;
                        case "cpm-max":
                            $(`input[name="${filtersKey}"]`).val(filters[filtersKey]);
                            $(`.${filtersKey}`).val(filters[filtersKey]).trigger('input');
                            $(`.${filtersKey}`)[0].dispatchEvent(new Event('input', {bubbles: true}));
                            break;
                        case "location":
                            $('select[name="filters[location]"]').val(filters[filtersKey]);
                            break;
                    }
                }

                let history_name = $(this).find('.history-name > span');
                history_name.attr('data-text', history_name.text());
                history_name.html('<i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i>');

                $('.results-table').find('tbody').html(loading_keywords_tr);
                $.post(xagio_data.wp_post, 'action=xagio_niche_hunter_keywords&id=' + id, function (d) {
                    history_name.html(history_name.attr('data-text'));
                    actions.loadTable(d, lang);

                });

            });
        },
        getHistory          : function (id = 0) {
            $.post(xagio_data.wp_post, 'action=xagio_niche_hunter_history', function (d) {
                let container = $('.hunter-history-holder');
                container.empty();

                if (d.length > 0) {
                    for (let i = 0; i < d.length; i++) {
                        const data = d[i];
                        let filters = btoa(JSON.stringify(data.filters));

                        let className = 'hunter-single-history-item';
                        if (id == data.id) {
                            className += ' selected'; // add any class you want here
                        }

                        let html = `
                                          <div 
                                            data-filters="${filters}" 
                                            data-language="${data.language}" 
                                            data-keyword="${data.keyword_name}" 
                                            data-id="${data.id}" 
                                            class="${className} xagio-flex-row xagio-flex-align-top">
                                            <div>
                                              <h3 class="history-name">
                                                ${data.keyword_name} <span>(${data.count})</span>
                                              </h3>
                                              <span class="history-date">Date Added: ${data.date_created}</span>
                                            </div>
                                            <button
                                             class="xagio-button xagio-button-danger xagio-button-mini delete-history-group"
                                             data-id="${data.id}">
                                              <i class="xagio-icon xagio-icon-delete"></i>
                                            </button>
                                          </div>
                                        `;
                        container.append(html);
                    }
                    container.addClass("xagio-grid-4-columns");
                } else {
                    container.text("No History Saved for this website, when you Generate keywords, history will automatically show.");
                }
            });
        },
        submitForm          : function () {
            $(document).on('submit', '.filters', function (e) {
                e.preventDefault();

                let data = $(this).serialize();
                let btn = $(this).find('button[type="submit"]');

                let language = $('[name="filters[location]"]').find('option:selected').data('language');

                btn.disable();

                let output = actions.xagsCostOutput(NICHE_HUNTER_COST);

                xagioModal("Are you sure?", `<i class="xagio-icon xagio-icon-info"></i> This action will cost you ${output}. Do you want to continue?`, function (yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, `${data}&filters[language]=${language}`, function (d) {

                            btn.disable();

                            if(d == false) {
                                xagioNotify('danger', 'It seems that account is not connected, please conntect with panel and try again');
                                return false;
                            }

                            if (!d.hasOwnProperty('message')) {
                                $('.results-table').find('tbody').html(loading_keywords_tr);
                                actions.loadTable(d, language);

                                actions.refreshXags();

                            } else {
                                xagioNotify('danger', d.message);
                            }
                        });
                    } else {
                        btn.disable();
                    }
                }, 'niche_hunter_cost_modal')
            });
        },
        loadTable           : function (data, language) {
            data = data || [];
            table = $('.results-table').DataTable({
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search keywords...",
                    processing: "Loading Keywords...",
                    emptyTable: "No keywords found.",
                    info: "_START_ to _END_ of _TOTAL_ keywords",
                    infoEmpty: "0 to 0 of 0 keywords",
                    infoFiltered: ""
                },
                "dom": 'rt<"xagio-table-bottom"lp>',
                'data': data,
                "bDestroy": true,
                "sFilterInput": "xagio-input-text-mini",
                "bPaginate": true,
                "bAutoWidth": false,
                "bFilter": true,
                "bProcessing": true,
                "bServerSide": false,
                "iDisplayLength": 10,
                "aLengthMenu": [[5, 10, 50, 100, -1], [5, 10, 50, 100, 'All']],
                "aaSorting": [
                    [
                        2,
                        'desc'
                    ]
                ],
                "aoColumns": [
                    {
                        "sClass": "xagio-text-center",
                        "bSortable": false,
                        "mRender": function (data, type, row) {
                            let volume = row.search_volume;
                            let cpc = parseFloat(row.cost_per_click).toFixed(2);
                            let inTitle = row.intitle ?? "";
                            let inUrl = row.inurl ?? "";
                            return `<input type="checkbox" data-id="${row.id}" data-keyword="${row.keyword}" data-volume="${volume}" data-cpc="${cpc}" data-intitle="${inTitle}" data-inurl="${inUrl}" class="xagio-input-checkbox select-niche-keywords">`;
                        }
                    },
                    {
                        "sClass": "text-left",
                        "bSortable": true,
                        "mRender": function (data, type, row) {
                            return row.keyword;
                        }
                    },
                    {
                        "sClass": "text-left",
                        "bSortable": true,
                        "mRender": function (data, type, row) {
                            return row.search_volume;
                        }
                    },
                    {
                        "sClass": "text-left",
                        "bSortable": true,
                        "mRender": function (data, type, row) {
                            return '$' + parseFloat(row.cost_per_click).toFixed(2);
                        }
                    },
                    {
                        "sClass": "text-left",
                        "bSortable": true,
                        "mRender": function (data, type, row) {
                            let competition = row.competition * 100;
                            let colorClass = '';
                            let competitionText = '';

                            if (competition <= 33) {
                                colorClass = 'xagio-progress-green'; // Green for low competition
                                competitionText = 'Low';
                            } else if (competition <= 66) {
                                colorClass = 'xagio-progress-orange'; // Yellow for medium competition
                                competitionText = 'Medium';
                            } else {
                                colorClass = 'xagio-progress-red'; // Red for high competition
                                competitionText = 'High';
                            }

                            return `<div class="xagio-progress ${colorClass}">
                                <div class="xagio-progress-bar" style="width: ${competition}%">
                                    <p>${competitionText} (${parseFloat(row.competition).toFixed(2)})</p>
                                </div>
                            </div>`;
                        }
                    },
                    {
                        "sClass": "text-left intitle",
                        "bSortable": true,
                        "mRender": function (data, type, row) {
                            if (row.status === 'completed' && row.intitle != null) {
                                return row.intitle;
                            } else if (row.status === 'queued') {
                                return `<i class="xagio-icon xagio-icon-sync xagio-icon-spin" data-xagio-tooltip data-xagio-title="This value is currently under analysis. Please check back later to see the results."></i>`
                            } else {
                                return '-';
                            }
                        }
                    },
                    {
                        "sClass": "text-left inurl",
                        "bSortable": true,
                        "mRender": function (data, type, row) {
                            if (row.status === 'completed' && row.inurl != null) {
                                return row.inurl;
                            } else if (row.status === 'queued') {
                                return `<i class="xagio-icon xagio-icon-sync xagio-icon-spin" data-xagio-tooltip data-xagio-title="This value is currently under analysis. Please check back later to see the results."></i>`
                            } else {
                                return '-';
                            }
                        }
                    },
                    {
                        data: null,
                        sClass: "text-left tr",
                        bSortable: true,
                        render: {
                            display: function (data, type, row) {
                                let inTitle = row.intitle;
                                let searchVol = row.search_volume;
                                let title_ratio = "";

                                if (row.status === 'completed' && inTitle != null) {
                                    if (inTitle == 0 && inTitle !== "") {
                                        title_ratio = "0";
                                    } else if (searchVol !== "" && inTitle !== "") {
                                        if (searchVol != 0) {
                                            title_ratio = inTitle / searchVol;
                                        }
                                    }

                                    let tr_color = '';
                                    if (title_ratio !== "") {
                                        if (parseFloat(title_ratio) >= parseFloat(cf_template.title_ratio_red)) {
                                            tr_color = 'tr_red';
                                        } else if (
                                            parseFloat(title_ratio) < parseFloat(cf_template.title_ratio_red) &&
                                            parseFloat(title_ratio) > parseFloat(cf_template.title_ratio_green)
                                        ) {
                                            tr_color = 'tr_yellow';
                                        } else if (parseFloat(title_ratio) <= parseFloat(cf_template.title_ratio_green)) {
                                            tr_color = 'tr_green';
                                        }
                                    }

                                    let tr_output = `<p class="${tr_color} margin-none">${parseFloat(title_ratio).toFixed(3)}</p>`;

                                    if (
                                        tr_color === "tr_green" &&
                                        parseFloat(cf_template.tr_goldbar_volume) >= parseFloat(searchVol) &&
                                        parseFloat(cf_template.tr_goldbar_intitle) >= parseFloat(inTitle)
                                    ) {
                                        tr_output = `<div contenteditable="false" data-xagio-tooltip data-xagio-title="Value: ${parseFloat(title_ratio).toFixed(3)}"><img src="${xagio_data.plugins_url}assets/img/gold.webp" alt="Goldbar"></div>`;
                                    }

                                    return tr_output;
                                } else {
                                    return '-';
                                }
                            },
                            sort: function (data, type, row) {
                                let inTitle = row.intitle;
                                let searchVol = row.search_volume;

                                if (
                                    row.status === 'completed' &&
                                    inTitle != null && inTitle !== "" &&
                                    searchVol !== "" && searchVol != 0
                                ) {
                                    const ratio = inTitle / searchVol;
                                    if (!isNaN(ratio)) return ratio;
                                }
                                return Number.MAX_VALUE;
                            }
                        }
                    },
                    {
                        "data": null,
                        "sClass": "text-left ur",
                        "bSortable": true,
                        "render": {
                            display: function (data, type, row) {
                                let inURL = row.inurl;
                                let searchVol = row.search_volume;
                                let url_ratio = "";

                                if (row.status === 'completed' && inURL != null) {
                                    if (inURL == 0 && inURL !== "") {
                                        url_ratio = "0";
                                    } else if (searchVol !== "" && inURL !== "") {
                                        if (searchVol != 0) {
                                            url_ratio = inURL / searchVol;
                                        }
                                    }

                                    let ur_color;
                                    if (url_ratio === "") {
                                        ur_color = '';
                                    } else if (parseFloat(url_ratio) >= parseFloat(cf_template.url_ratio_red)) {
                                        ur_color = 'tr_red';
                                    } else if (parseFloat(url_ratio) < parseFloat(cf_template.url_ratio_red) && parseFloat(url_ratio) > parseFloat(cf_template.url_ratio_green)) {
                                        ur_color = 'tr_yellow';
                                    } else if (parseFloat(url_ratio) <= parseFloat(cf_template.url_ratio_green)) {
                                        ur_color = 'tr_green';
                                    }

                                    let tr_output = `<p class="${ur_color} margin-none">${parseFloat(url_ratio).toFixed(3)}</p>`

                                    if (ur_color == "tr_green" && (parseFloat(cf_template.tr_goldbar_volume) >= parseFloat(searchVol) && parseFloat(cf_template.tr_goldbar_intitle) >= parseFloat(inURL))) {
                                        tr_output = '<div contenteditable="false" data-xagio-tooltip data-xagio-title="Value: ' + parseFloat(url_ratio).toFixed(3) + '"><img src="' +
                                            xagio_data.plugins_url + 'assets/img/gold.webp" alt="Goldbar"></div>';
                                    }

                                    return tr_output;
                                } else {
                                    return '-';
                                }
                            },
                            sort: function (data, type, row) {
                                let inURL = row.inurl;
                                let searchVol = row.search_volume;

                                if (
                                    row.status === 'completed' &&
                                    inURL != null && inURL !== "" &&
                                    searchVol !== "" && searchVol != 0
                                ) {
                                    const ratio = inURL / searchVol;
                                    if (!isNaN(ratio)) return ratio;
                                }

                                return Number.MAX_VALUE;  // Push rows with invalid or empty ratio to the bottom
                            }
                        }
                    },
                    {
                        "sClass": "text-left",
                        "bSortable": false,
                        "sWidth": "200px",
                        "mRender": function (data, type, row) {

                            let history = [];
                            for (let i = 0; i < 12; i++) {
                                history.push(row.history[i]);
                            }
                            if (history.length > 0) {
                                history = history.reverse();
                                history = history.join(',');
                            } else {
                                history = '';
                            }

                            return '<div class="xagio-table-buttons-flex">\n' +
                                '    <button data-domain="' +
                                actions.convertToValidDomain(row.keyword) +
                                '" class="xagio-button xagio-button-primary xagio-button-mini check_domain" data-xagio-tooltip data-xagio-title="Click here to check domain availability" data-xagio-modal="checkDomainModal">' +
                                '        <i class="xagio-icon xagio-icon-travel-explore"></i></button>' +

                                '    <button data-keyword="' + row.keyword +
                                '" class="xagio-button xagio-button-primary xagio-button-mini view_google_trends" data-xagio-tooltip data-xagio-title="View Google trends" data-xagio-modal="googleTrendsModal">' +
                                '        <i class="xagio-icon xagio-icon-school"></i></button>' +

                                '    <button data-language="' + language +
                                '" data-keyword="' + row.keyword +
                                '" class="xagio-button xagio-button-primary xagio-button-mini multiple-links" data-xagio-tooltip data-xagio-title="Search keyword in Google">' +
                                '        <i class="xagio-icon xagio-icon-google"></i></button>' +

                                '    <button data-id="' + row.id +
                                '" class="xagio-button xagio-button-primary xagio-button-mini get-competition-single-keyword" data-xagio-tooltip data-xagio-title="Get competition">' +
                                '        <i class="xagio-icon xagio-icon-key"></i></button>' +

                                '    <button data-keyword="' + row.keyword +
                                '" data-history="' + history +
                                '" class="xagio-button xagio-button-primary xagio-button-mini view_history_graph" data-xagio-tooltip data-xagio-title="View history" data-xagio-modal="historyModal">' +
                                '        <i class="xagio-icon xagio-icon-history"></i></button>' +
                                '</div>';
                        }
                    }
                ],
            });

            $('.niche-keywords').empty().html(table.rows().count() + " Keywords");
            $('#select-all-keywords .count').empty().html(table.rows().count());

            $('#customSearch').on('input', function () {
                table.search(this.value).draw();

                // number of keywords after search
                $('.niche-keywords').empty().html(table.rows({ filter: 'applied' }).count() + " Keywords");
                $('#select-all-keywords .count').empty().text(table.rows({ filter: 'applied' }).count());
            });
        },
        initSliders         : function () {
            const rangeContainers = document.querySelectorAll(".hunter-range-container");
            rangeContainers.forEach(container => {


                const rangevalue = container.querySelectorAll(".hunter-slider-container .price-slider")[0];
                const rangeInputvalue = container.querySelectorAll(".range-input input");
                const priceInputvalue = container.querySelectorAll(`.xagio-slider-input input`);

                let priceGap = rangeInputvalue[0].step;

                for (let i = 0; i < priceInputvalue.length; i++) {
                    let initial_min = rangeInputvalue[0].min;
                    let initial_max = rangeInputvalue[1].max;
                    rangevalue.style.left = `${(initial_min / rangeInputvalue[0].max) * 100}%`;
                    rangevalue.style.right = `${100 - (initial_max / rangeInputvalue[1].max) * 100}%`;

                    priceInputvalue[i].addEventListener("input", e => {

                        // Parse min and max values of the range input
                        let minp = parseFloat(priceInputvalue[0].value);
                        let maxp = parseFloat(priceInputvalue[1].value);
                        let diff = maxp - minp
                        if (minp < 0) {
                            priceInputvalue[0].value = 0;
                            minp = 0;
                        }

                        let validate_max = rangeInputvalue[1].max
                        let validate_min = rangeInputvalue[1].min

                        // Validate the input values
                        if (maxp > validate_max) {
                            priceInputvalue[1].value = validate_max;
                            maxp = validate_max;
                        }

                        if (minp > maxp - priceGap) {
                            priceInputvalue[0].value = maxp - priceGap;
                            minp = maxp - priceGap;

                            if (minp < validate_min) {
                                priceInputvalue[0].value = validate_min;
                                minp = validate_min;
                            }
                        }

                        // Check if the price gap is met
                        // and max price is within the range
                        if (diff >= priceGap && maxp <= rangeInputvalue[1].max) {

                            if (e.target.classList.contains("min-input")) {

                                rangeInputvalue[0].value = minp;
                                let value1 = rangeInputvalue[0].max;
                                rangevalue.style.left = `${(minp / value1) * 100}%`;
                            } else {
                                rangeInputvalue[1].value = maxp;
                                let value2 = rangeInputvalue[1].max;
                                rangevalue.style.right = `${100 - (maxp / value2) * 100}%`;
                            }
                        }
                    });

                    // Add event listeners to range input elements
                    for (let i = 0; i < rangeInputvalue.length; i++) {
                        rangeInputvalue[i].addEventListener("input", e => {
                            let minVal = parseFloat(rangeInputvalue[0].value);
                            let maxVal = parseFloat(rangeInputvalue[1].value);

                            let diff = maxVal - minVal
                            // Check if the price gap is exceeded
                            if (diff < priceGap) {

                                // Check if the input is the min range input
                                if (e.target.classList.contains("min-input")) {
                                    rangeInputvalue[0].value = maxVal - priceGap;
                                    rangevalue.style.left = `${(minVal / rangeInputvalue[0].max) * 100}%`;
                                } else {
                                    rangeInputvalue[1].value = minVal + priceGap;
                                    rangevalue.style.right = `${100 - (maxVal / rangeInputvalue[1].max) * 100}%`;
                                }
                            } else {

                                // Update price inputs and range progress
                                priceInputvalue[0].value = minVal;
                                priceInputvalue[1].value = maxVal;
                                rangevalue.style.left = `${(minVal / rangeInputvalue[0].max) * 100}%`;
                                rangevalue.style.right = `${100 - (maxVal / rangeInputvalue[1].max) * 100}%`;
                            }
                        });
                    }
                }
            });
        },
        convertToValidDomain: function (inputString) {
            // Convert to lowercase
            let domain = inputString.toLowerCase();

            // Remove accents and diacritical marks
            domain = domain.normalize("NFD").replace(/[\u0300-\u036f]/g, "");

            // Replace spaces and invalid characters with hyphens
            domain = domain.replace(/[^a-z0-9]/g, '');

            // Remove multiple consecutive hyphens
            domain = domain.replace(/-+/g, '');

            // Trim hyphens from the start and end of the domain
            domain = domain.replace(/^-+|-+$/g, '');

            // Ensure the domain is not empty and append a generic TLD if valid
            if (domain === "") {
                domain = "defaultdomain";
            }
            return domain;
        },
        openCompetitionModal: function () {
            $(document).on("click", ".get-competition", function (e) {
                e.preventDefault();

                let size = Object.keys(selected_keywords).length;

                if (size > 0) {
                    let cost = COMPETITION_COST * size;
                    let output = actions.xagsCostOutput(cost);
                    let modal = $("#getHunterCompetitionModal");

                    modal.find('#xagsCost').html(`This action will cost you ${output}. Do you want to continue?`);

                    let keys = Object.keys(selected_keywords);
                    modal.find(".ids").val(keys);

                    modal[0].showModal();
                } else {
                    xagioNotify('error', 'Please select some keywords!')
                }
            })
        },
        getCompetition: function () {
            $(document).on("submit", "#getHunterCompetitionForm", function (e) {
                e.preventDefault();

                let modal = $("#getHunterCompetitionModal");
                let data = $(this).serialize();
                let ids = modal.find(".ids").val();
                let idsArray = ids.split(",");
                let btn = $(this).find("button[type='submit']");

                data += "&action=xagio_get_niche_competition";

                btn.disable();

                $.post(xagio_data.wp_post, data, function (d) {
                    if (d.status === 'success') {
                        modal[0].close();

                        actions.refreshXags();

                        idsArray.forEach(function(value, index) {
                            let selector = $(`.select-niche-keywords[data-id="${value}"]`).parents('tr');

                            selector.find('td.intitle').html(`<i class="xagio-icon xagio-icon-sync xagio-icon-spin" data-xagio-tooltip data-xagio-title="This value is currently under analysis. Please check back later to see the results."></i>`);
                            selector.find('td.inurl').html(`<i class="xagio-icon xagio-icon-sync xagio-icon-spin" data-xagio-tooltip data-xagio-title="This value is currently under analysis. Please check back later to see the results."></i>`);
                        });

                        btn.disable();
                        actions.checkIfNicheBatchIsDone();

                        xagioNotify(d.status, d.message);
                    }
                })
            })
        },
        checkIfNicheBatchIsDone: function () {
            clearTimeout(nicheCompetitionBatchCron);
            nicheCompetitionBatchCron = setTimeout(function () {

                $.post(xagio_data.wp_post, 'action=xagio_check_if_niche_batch_is_done', function (d) {
                    if (d.status == 'success') {
                        let id;
                        let lang;
                        let selectedItem = $(".hunter-single-history-item.selected");

                        if (selectedItem.length) {
                            id = selectedItem.data("id");
                            lang = selectedItem.data('language');
                        }

                        $('.results-table').find('tbody').html(loading_keywords_tr);
                        $.post(xagio_data.wp_post, 'action=xagio_niche_hunter_keywords&id=' + id, function (d) {
                            actions.loadTable(d, lang);
                            selected_keywords = {};
                            $('.copy-keywords-to-project-container').hide();
                        });
                    } else {
                        actions.checkIfNicheBatchIsDone();
                    }
                });

            }, 5000);
        },
        competitionSingleKeyword: function () {
            $(document).on('click', '.get-competition-single-keyword', function (e) {
                e.preventDefault();

                let id = $(this).attr('data-id');
                let output = actions.xagsCostOutput(COMPETITION_COST);
                let modal = $("#getHunterCompetitionModal");

                modal.find('#xagsCost').html(`This action will cost you ${output}. Do you want to continue?`);
                modal.find(".ids").val(id);

                modal[0].showModal();
            });
        },
        clearSelectedKeywords: function () {
            $(document).on('click', '#clear-selected-keywords', function () {
                table.rows({ search: 'applied' }).every(function () {
                    let row = this.node();
                    let checkbox = $(row).find('.select-niche-keywords');

                    // Check the checkbox
                    checkbox.prop('checked', false).removeClass('selected');;
                })

                $('.select-all-niche-keywords').prop('checked', false);

                selected_keywords = {};
                $('.copy-keywords-to-project-container').hide();
                $('.delete-keywords span').text("0");
            })
        },
        selectAllKeywords: function () {
            $(document).on('click', '#select-all-keywords', function() {

                table.rows({ search: 'applied' }).every(function () {
                    let row = this.node();
                    let checkbox = $(row).find('.select-niche-keywords');

                    let id = checkbox.data('id');
                    let keyword = checkbox.data('keyword');
                    let volume = checkbox.data('volume');
                    let cpc = checkbox.data('cpc');
                    let intitle = checkbox.data('intitle');
                    let inurl = checkbox.data('inurl');

                    // Check the checkbox
                    checkbox.prop('checked', true);

                    selected_keywords[id] = {
                        "keyword" : keyword,
                        "volume" : volume,
                        "cpc" : cpc,
                        "intitle" : intitle,
                        "inurl" : inurl,
                    };
                });

                $(".select-all-niche-keywords").prop('checked', true);

                let size = Object.keys(selected_keywords).length;
                if(size > 0){
                    $('.copy-keywords-to-project-container').show();
                    $('.niche-selected-keywords').html(size);
                    $('.delete-keywords span').text(size);
                } else {
                    $('.copy-keywords-to-project-container').hide();
                    $('.niche-selected-keywords').html('');
                    $('.delete-keywords span').text('0');
                }
            });
        },
        deleteKeywords: function () {
            $(document).on('click', '.delete-keywords', function (e) {
                let button = $(this);
                let ids = Object.keys(selected_keywords);
                let size = ids.length;
                if (size < 1) {
                    xagioNotify("warning", "Please select at least one keyword.");
                    return false;
                }

                xagioModal('Confirm', 'Are you sure you want to delete selected keywords?', function(yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, `action=xagio_delete_keywords&ids=${ids}`, function (d) {
                            if (d.status == 'success') {
                                let id;
                                let lang;
                                let selectedItem = $(".hunter-single-history-item.selected");

                                if (selectedItem.length) {
                                    id = selectedItem.data("id");
                                    lang = selectedItem.data('language');
                                }

                                actions.getHistory(id);

                                $('.results-table').find('tbody').html(loading_keywords_tr);
                                $.post(xagio_data.wp_post, 'action=xagio_niche_hunter_keywords&id=' + id, function (d) {
                                    actions.loadTable(d, lang);
                                    selected_keywords = {};
                                    $('.copy-keywords-to-project-container').hide();
                                    button.find('span').text("0");
                                });

                                xagioNotify(d.status, d.message);
                            }
                        });
                    }
                });
            })
        },
        deleteHistoryGroup: function () {
            $(document).on('click', '.delete-history-group', function (e) {
                e.stopPropagation();
                let button = $(this);
                let groupId = button.attr('data-id');

                xagioModal('Confirm', 'Are you sure you want to delete this group?', function(yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, `action=xagio_delete_history_group&group_id=${groupId}`, function (d) {
                            if (d.status == 'success') {
                                let id;
                                let lang;
                                let selectedItem = $(".hunter-single-history-item.selected");

                                if (selectedItem.length) {
                                    id = selectedItem.data("id");
                                    lang = selectedItem.data('language');
                                }

                                actions.getHistory(id);

                                $('.results-table').find('tbody').html(loading_keywords_tr);
                                $.post(xagio_data.wp_post, 'action=xagio_niche_hunter_keywords&id=0' + id, function (d) {
                                    actions.loadTable(d, lang);
                                });

                                xagioNotify(d.status, d.message);
                            }
                        });
                    }
                });
            })
        },
        copyToClipboard: function () {
            $(document).on('click', '.copy-to-clipboard', function (e) {
                e.preventDefault();

                let size = Object.keys(selected_keywords).length;
                if (size < 1) {
                    xagioNotify("error", "Please select at least one keyword.");
                    return false;
                }

                let ids = Object.keys(selected_keywords);

                $.post(xagio_data.wp_post, `action=xagio_copy_to_clipboard&ids=${ids}`, function (d) {
                    if (d.status == 'success') {
                        navigator.clipboard.writeText(d.data).then(() => {
                            xagioNotify("success", "Keywords copied to clipboard.");
                        }).catch(err => {
                            xagioNotify("danger", `Failed to copy keywords: ${err}.`);
                        });
                    }
                });

            });
        },
        debounce: function(func, delay) {
            let timeoutId;
            return function (...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            };
        },
        searchNicheHistory: function () {
            const debouncedSearch = actions.debounce(function (input) {
                let value = input.val().toLowerCase();

                $('.hunter-single-history-item').each(function() {
                    let keyword = $(this).data('keyword').toLowerCase();

                    if (keyword.includes(value)) {
                        $(this).show();  // show matching
                    } else {
                        $(this).hide();  // hide non-matching
                    }
                });

                $("#no-hiche-history-found").toggle($(".hunter-single-history-item:visible").length === 0);
            }, 500);

            $(document).on('keyup', '.search-niche-history', function (e) {
                debouncedSearch($(this));
            });
        }

    }


})(jQuery);


