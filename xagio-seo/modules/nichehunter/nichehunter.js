var history_chart = null;
let selected_keywords = {};

let loading_keywords_tr = '<tr><td colspan="6" class="loading-niche-keywords">Loading keywords... <i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></td></tr>';

let NICHE_HUNTER_COST = "";

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
                console.log(selected_keywords[key]);
                form_data.append(`keywords[${count}][keyword]`, selected_keywords[key].keyword);
                form_data.append(`keywords[${count}][volume]`, selected_keywords[key].volume);
                form_data.append(`keywords[${count}][cpc]`, selected_keywords[key].cpc);
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

            console.log(select.val());
        });


        $(document).on('input', '.select-niche-keywords', function () {
            let checkbox = $(this);

            let id = checkbox.data('id');
            let keyword = checkbox.data('keyword');
            let volume = checkbox.data('volume');
            let cpc = checkbox.data('cpc');


            if(checkbox.prop('checked')) {

                selected_keywords[id] = {
                    "keyword" : keyword,
                    "volume" : volume,
                    "cpc" : cpc,
                };
            } else {
                delete selected_keywords[id];
            }

            let size = Object.keys(selected_keywords).length;
            if(size > 0){
                $('.copy-keywords-to-project').show();
                $('.niche-selected-keywords').html(size);
            } else {
                $('.copy-keywords-to-project').hide();
                $('.niche-selected-keywords').html('');
            }

        });

        $(document).on('input', '.select-all-niche-keywords', function () {
            let checkbox = $(this);
            let all_checkboxes = $('.select-niche-keywords');

            all_checkboxes.each(function (i) {

                let id = $(this).data('id');
                let keyword = $(this).data('keyword');
                let volume = $(this).data('volume');
                let cpc = $(this).data('cpc');

                if($(this).prop('checked')) {
                    delete selected_keywords[id];
                    $(this).prop('checked', false);
                } else {
                    selected_keywords[id] = {
                        "keyword" : keyword,
                        "volume" : volume,
                        "cpc" : cpc,
                    };
                    $(this).prop('checked', true);
                }
            });

            let size = Object.keys(selected_keywords).length;
            if(size > 0){
                $('.copy-keywords-to-project').show();
                $('.niche-selected-keywords').html(size);
            } else {
                $('.copy-keywords-to-project').hide();
                $('.niche-selected-keywords').html('');
            }
        });
    });

    let actions = {
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

                    actions.allowances.xags_allowance.find('.value').html(parseFloat(d.data['xags_allowance']).toFixed(2));

                    if(d.data['xags'] > 0) {
                        actions.allowances.xags.find('.value').html(parseFloat(d.data['xags']).toFixed(2));
                    } else {
                        actions.allowances.xags.hide();
                    }

                    actions.allowances.xags_total = d.data['xags_total'];
                }
            });
        },
        loadHistoryItem     : function () {
            $(document).on('click', '.hunter-single-history-item', function (e) {
                e.preventDefault();

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
        getHistory          : function () {
            $.post(xagio_data.wp_post, 'action=xagio_niche_hunter_history', function (d) {
                let container = $('.hunter-history-holder');
                container.empty();

                if(d.length < 1) {
                    container.html('No History Saved for this website, when you Generate keywords, history will automatically show.');
                }

                for (let i = 0; i < d.length; i++) {
                    const data = d[i];
                    let filters = btoa(JSON.stringify(data.filters));

                    let html = '<div data-filters="'+filters+'" data-language="' + data.language + '" data-keyword="'+data.keyword_name+'" data-id="' + data.id +
                               '" class="hunter-single-history-item">' +
                               '          <h3 class="history-name">' + data.keyword_name + ' <span>(' + data.count +
                               ')</span></h3>' +
                               '          <span class="history-date">Date Added: ' + data.date_created + '</span>' +
                               '       </div>';
                    container.append(html);

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

                xagioModal("Are you sure?", `This action will cost you ${NICHE_HUNTER_COST} XAGS`, function (yes) {
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
                })
            });
        },
        loadTable           : function (data, language) {
            $('.results-table').dataTable({
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search keywords...",
                    processing: "Loading Keywords...",
                    emptyTable: "No keywords found.",
                    info: "_START_ to _END_ of _TOTAL_ keywords",
                    infoEmpty: "0 to 0 of 0 keywords",
                    infoFiltered: ""
                },
                "dom": '<f>rt<"xagio-table-bottom"<>p>',
                'data': data,
                "bDestroy": true,
                "sFilterInput": "xagio-input-text-mini",
                "bPaginate": true,
                "bAutoWidth": false,
                "bFilter": true,
                "bProcessing": true,
                "bServerSide": false,
                "iDisplayLength": 100,
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
                            return `<input type="checkbox" data-id="${row.id}" data-keyword="${row.keyword}" data-volume="${volume}" data-cpc="${cpc}" class="xagio-input-checkbox select-niche-keywords">`;
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

                            return '<div class="xagio-progress ' + colorClass + '">' +
                                '    <div class="xagio-progress-bar" style="width: ' +
                                competition + '%">' + competitionText + '</div>' +
                                '</div>';
                        }
                    },{
                        "sClass": "text-left",
                        "bSortable": false,
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

                                '    <button data-keyword="' + row.keyword +
                                '" class="xagio-button xagio-button-primary xagio-button-mini open_quora" data-xagio-tooltip data-xagio-title="Search keyword in Quora">' +
                                '        <i class="xagio-icon xagio-icon-quora"></i></button>' +

                                '    <button data-keyword="' + row.keyword +
                                '" data-history="' + history +
                                '" class="xagio-button xagio-button-primary xagio-button-mini view_history_graph" data-xagio-tooltip data-xagio-title="View history" data-xagio-modal="historyModal">' +
                                '        <i class="xagio-icon xagio-icon-history"></i></button>' +
                                '</div>';
                        }
                    }
                ],
                fnInitComplete: function () {
                    $('.dataTables_filter input[type="search"]').addClass('xagio-input-text-mini');
                }

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
        }
    }


})(jQuery);


