
let currentProjectID   = 0;
let currentProjectName = 0;


let cf_templates = {
    Default:   {
        name: "Default",
        data: {
            volume_red:   20,
            volume_green: 100,

            cpc_red:   0.59,
            cpc_green: 1.00,

            intitle_red:   1000,
            intitle_green: 250,

            inurl_red:   1000,
            inurl_green: 250,

            title_ratio_red:   1,
            title_ratio_green: 0.25,

            url_ratio_red:   1,
            url_ratio_green: 0.25,

            tr_goldbar_volume:  1000,
            tr_goldbar_intitle: 20,

            ur_goldbar_volume:  1000,
            ur_goldbar_intitle: 20
        }
    },
    Affiliate: {
        name: "Affiliate",
        data: {
            volume_red:   100,
            volume_green: 1000,

            cpc_red:   1.00,
            cpc_green: 2.00,

            intitle_red:   10000,
            intitle_green: 1000,

            inurl_red:   10000,
            inurl_green: 1000,

            title_ratio_red:   1,
            title_ratio_green: 0.25,

            url_ratio_red:   1,
            url_ratio_green: 0.25,

            tr_goldbar_volume:  1000,
            tr_goldbar_intitle: 20,

            ur_goldbar_volume:  1000,
            ur_goldbar_intitle: 20
        }
    },
    Local:     {
        name: "Local",
        data: {
            volume_red:   10,
            volume_green: 100,

            cpc_red:   2.00,
            cpc_green: 5.00,

            intitle_red:   1000,
            intitle_green: 100,

            inurl_red:   1000,
            inurl_green: 100,

            title_ratio_red:   1,
            title_ratio_green: 0.25,

            url_ratio_red:   1,
            url_ratio_green: 0.25,

            tr_goldbar_volume:  1000,
            tr_goldbar_intitle: 20,

            ur_goldbar_volume:  1000,
            ur_goldbar_intitle: 20
        }
    }
};

let cf_default_template = 'Default';
let cf_template         = cf_templates[cf_default_template].data;

(function ($) {
    'use strict';



    $(document).ready(function () {
        xagio_shared_data.groups = JSON.parse(atob(xagio_shared_data.groups));

        if(xagio_shared_data.user_details.hasOwnProperty('company_logo')) {
            $('.logo-xagio-image').attr('src', `https://cdn.xagio.net/company_logo/${xagio_shared_data.user_details.company_logo}`)
        }

        if(xagio_shared_data.user_details.hasOwnProperty('first_name')) {
            $('.logo-first-name').html(`${xagio_shared_data.user_details.first_name}&nbsp${xagio_shared_data.user_details.last_name}`);
        }

        if(xagio_shared_data.user_details.hasOwnProperty('user_email')) {
            $('.logo-email').html(xagio_shared_data.user_details.user_email);
        }

        if(xagio_shared_data.user_details.hasOwnProperty('address')) {
            $('.logo-address').html(xagio_shared_data.user_details.address);
        }

        if(xagio_shared_data.user_details.hasOwnProperty('phone_number')) {
            $('.logo-phone-number').html(xagio_shared_data.user_details.phone_number);
        }

        $(document).on('mouseenter', '[data-xagio-tooltip]', function (e) {
            e.preventDefault();
            let el = $(this);
            let title = el.attr('data-xagio-title');

            if(title.length < 1) return;

            let position = $(this).data('xagio-tooltip-position') || 'top';
            let tooltip = $(`<div class="xagio-tooltip ${position}"><div class="xagio-tooltip-body">${title}</div><div class="xagio-tooltip-arrow"></div></div>`);

            let dialogContainer = el.closest('.xagio-modal');

            let tooltipTop, tooltipLeft;

            if (dialogContainer.length) {
                tooltip.appendTo(dialogContainer);

                let elementPosition = el.offset();
                let dialogPosition = dialogContainer.offset(); // Get dialog's position relative to the document

                tooltipLeft = elementPosition.left - dialogPosition.left + el.outerWidth() / 2 - tooltip.outerWidth() / 2;

                if (position === 'bottom') {
                    tooltipTop = elementPosition.top - dialogPosition.top + el.outerHeight() + 10; // Position below the element
                } else {
                    tooltipTop = elementPosition.top - dialogPosition.top - tooltip.outerHeight() - 10; // Default to position above the element
                }

                let dialogWidth = dialogContainer.outerWidth(); // Get dialog's outer width

                if (tooltipLeft < 0) {
                    tooltipLeft = 0;
                } else if (tooltipLeft + tooltip.outerWidth() > dialogWidth) {
                    tooltipLeft = dialogWidth - tooltip.outerWidth();
                }

                if (tooltipTop < 0) {
                    tooltipTop = 0;
                }

                let arrowLeft = elementPosition.left - dialogPosition.left + el.outerWidth() / 2 - tooltipLeft;
                tooltip.find('.xagio-tooltip-arrow').css('left', arrowLeft);
            } else {
                tooltip.appendTo('body');

                let elementPosition = el.offset();
                tooltipLeft = elementPosition.left + el.outerWidth() / 2 - tooltip.outerWidth() / 2;

                if (position === 'bottom') {
                    tooltipTop = elementPosition.top + el.outerHeight() + 10; // Position below the element
                } else {
                    tooltipTop = elementPosition.top - tooltip.outerHeight() - 10; // Default to position above the element
                }

                let windowWidth = $(window).width();

                if (tooltipLeft < 0) {
                    tooltipLeft = 0;
                } else if (tooltipLeft + tooltip.outerWidth() > windowWidth) {
                    tooltipLeft = windowWidth - tooltip.outerWidth();
                }

                if (tooltipTop < 0) {
                    tooltipTop = 0;
                }

                let arrowLeft = elementPosition.left + el.outerWidth() / 2 - tooltipLeft;
                tooltip.find('.xagio-tooltip-arrow').css('left', arrowLeft);
            }

            tooltip.css({
                left: tooltipLeft,
                top: tooltipTop
            });
        });

        $(document).on('mouseleave', '[data-xagio-tooltip]', function () {
            $('.xagio-tooltip').remove();
        });


        actions.init();




    });


    let actions = {
        init: function () {
            $(document).on('click', '.sort-groups-asc', function (e) {
                $(this).hide();
                $('.sort-groups-desc').show();
            });

            $(document).on('click', '.sort-groups-desc', function (e) {
                $(this).hide();
                $('.sort-groups-asc').show();
            });

            $.tablesorter.addParser({
                id:     "fancyNumber",
                is:     function (s) {
                    return false;
                },
                format: function (s) {
                    return $.tablesorter.formatFloat(s.replace(/,/g, ''));
                },
                type:   "numeric"
            });

            setTimeout(function () {
                actions.loadProjectManually();
            }, 1500);

            actions.formatSEO();
            // actions.loadCfTemplates();
        },
        formatSEO:              function (t) {
            $(document).on('change', '.prs-title', function (e) {
                $(this).prev('input').val($(this).text());

                let wordCount = $(this).html().replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;

                if (wordCount > 70) {
                    $(this).parents('td').find('.count-seo-title').html('<span style="color:red">' + wordCount + '</span>');
                } else {
                    $(this).parents('td').find('.count-seo-title').html(wordCount);
                }

                if (wordCount > 78) {
                    $(this).parents('td').find('.count-seo-title-mobile').html('<span style="color:red">' + wordCount + '</span>');
                } else {
                    $(this).parents('td').find('.count-seo-title-mobile').html(wordCount);
                }

            });

            $(document).on('change', '.prs-description', function (e) {
                $(this).prev('input').val($(this).text());

                let wordCount = $(this).html().replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;

                if (wordCount > 300) {
                    $(this).parents('td').find('.count-seo-description').html('<span style="color:red">' + wordCount + '</span>');
                } else {
                    $(this).parents('td').find('.count-seo-description').html(wordCount);
                }

                if (wordCount > 120) {
                    $(this).parents('td').find('.count-seo-description-mobile').html('<span style="color:red">' + wordCount + '</span>');
                } else {
                    $(this).parents('td').find('.count-seo-description-mobile').html(wordCount);
                }
            });
        },
        loadProjectManually:  function () {
            $('.project_loaded').show();
            $('.new-folder').hide();
            $('.website-actions-single').hide();

            let project_dashboard = $('.project-dashboard');
            let project_groups    = $('.project-groups');

            $('.project-name').html(`<i class='xagio-icon xagio-icon-file'></i> Project: ${xagio_shared_data.project_name}`);

            let d = xagio_shared_data.groups;

            project_dashboard.show();

            d.sort((a, b) => {
                let aa = a.group_name.toString().toLowerCase(),
                    bb = b.group_name.toString().toLowerCase();

                if (aa < bb) {
                    return -1;
                }
                if (aa > bb) {
                    return 1;
                }
                return 0;
            });





            if (d.length > 0) {
                project_groups.show();

                let data   = project_groups.find('.data');
                let groups = [];

                // Remove old loaded groups
                data.empty();

                // Render new groups
                for (let i = 0; i < d.length; i++) {

                    let row      = d[i];
                    let template = $('.xagio-group.template').clone();
                    template.removeClass('template');

                    //html entity decode
                    row.title       = utils.decodeHtml(row.title);
                    row.group_name  = utils.decodeHtml(row.group_name);
                    row.h1          = utils.decodeHtml(row.h1);
                    row.description = utils.decodeHtml(row.description);

                    // Append the Group ID
                    template.find('[name="group_id"]').val(row.id);
                    template.find('.seedKeyword').attr('data-group-id', row.id);
                    template.find('.phraseMatch').attr('data-group-id', row.id);

                    // Change the Group Name
                    template.find('[name="group_name"]').val(row.group_name);
                    template.find('[name="group_name"]').attr('title', row.group_name);

                    // Prepare the URL
                    let pURL = utils.prepareURL(row.url);

                    template.find('.attachToPagePost').attr('data-post-id', row.id_page_post);

                    // Go to Page/Post
                    if (row.id_page_post != null && row.id_page_post != '' && row.id_page_post != 0) {
                        template.find('.goToPagePost').attr('href', "post.php?post=" + row.id_page_post + "&action=edit");
                        template.find('.attachToPagePost').html('Attach to Page/Post &nbsp;&nbsp; (<i title="Attached to an existing Page/Post already." class="uk-text-success xagio-icon xagio-icon-check"></i>)');
                        template.find('.attachToPagePost').attr('data-group-id', row.id);
                    } else {
                        template.find('.goToPagePost').addClass('hidden');
                    }

                    // Change the rest of the Group Settings
                    template.find('[name="h1"]').val(row.h1 != null ? row.h1 : '');

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
                    template.find('[data-target="description"]').text(row.description != null ? row.description : '');
                    template.find('[data-target="h1tag"]').text(row.h1 != null ? row.h1 : '');

                    // Calculate Counting
                    template.find('.count-seo-title').text(row.title != null ? row.title.length : 0);
                    template.find('.count-seo-title-mobile').text(row.title != null ? row.title.length : 0);

                    template.find('.count-seo-description').text(row.description != null ? row.description.length : 0);
                    template.find('.count-seo-description-mobile').text(row.description != null ? row.description.length : 0);

                    // Go through keywords
                    if (row.keywords.length > 0) {

                        let kwData = template.find('.keywords-data');
                        kwData.empty();

                        let groupKeywords = '';

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
                                alsoQueued      = true;
                                keyword.inurl   = null;
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

                            keyword.volume  = utils.cleanComma(keyword.volume);
                            keyword.cpc     = utils.cleanComma(keyword.cpc);
                            keyword.intitle = utils.cleanComma(keyword.intitle);
                            keyword.inurl   = utils.cleanComma(keyword.inurl);


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
                            } else if (parseFloat(cf_template.volume_red) < parseFloat(keyword.volume) && parseFloat(cf_template.volume_green) > parseFloat(keyword.volume)) {
                                volume_color = 'tr_yellow';
                            } else if (parseFloat(cf_template.volume_green) <= parseFloat(keyword.volume)) {
                                volume_color = 'tr_green';
                            }

                            if (keyword.cpc === "") {
                                cpc_color = '';
                            } else if (parseFloat(cf_template.cpc_red) >= parseFloat(keyword.cpc)) {
                                cpc_color = 'tr_red';
                            } else if (parseFloat(cf_template.cpc_red) < parseFloat(keyword.cpc) && parseFloat(cf_template.cpc_green) > parseFloat(keyword.cpc)) {
                                cpc_color = 'tr_yellow';
                            } else if (parseFloat(cf_template.cpc_green) <= parseFloat(keyword.cpc)) {
                                cpc_color = 'tr_green';
                            }

                            if (keyword.intitle === "") {
                                intitle_color = '';
                            } else if (parseFloat(cf_template.intitle_red) <= parseFloat(keyword.intitle)) {
                                intitle_color = 'tr_red';
                            } else if (parseFloat(cf_template.intitle_red) > parseFloat(keyword.intitle) && parseFloat(cf_template.intitle_green) < parseFloat(keyword.intitle)) {
                                intitle_color = 'tr_yellow';
                            } else if (parseFloat(cf_template.intitle_green) >= parseFloat(keyword.intitle)) {
                                intitle_color = 'tr_green';
                            }

                            if (keyword.inurl === "") {
                                inurl_color = '';
                            } else if (parseFloat(cf_template.inurl_red) <= parseFloat(keyword.inurl)) {
                                inurl_color = 'tr_red';
                            } else if (parseFloat(cf_template.inurl_red) > parseFloat(keyword.inurl) && parseFloat(cf_template.inurl_green) < parseFloat(keyword.inurl)) {
                                inurl_color = 'tr_yellow';
                            } else if (parseFloat(cf_template.inurl_green) >= parseFloat(keyword.inurl)) {
                                inurl_color = 'tr_green';
                            }

                            if (title_ratio === "") {
                                tr_color = '';
                            } else if (parseFloat(title_ratio) >= parseFloat(cf_template.title_ratio_red)) {
                                tr_color = 'tr_red';
                            } else if (parseFloat(title_ratio) < parseFloat(cf_template.title_ratio_red) && parseFloat(title_ratio) > parseFloat(cf_template.title_ratio_green)) {
                                tr_color = 'tr_yellow';
                            } else if (parseFloat(title_ratio) <= parseFloat(cf_template.title_ratio_green)) {
                                tr_color = 'tr_green';
                            }

                            if (url_ratio === "") {
                                ur_color = '';
                            } else if (parseFloat(url_ratio) >= parseFloat(cf_template.url_ratio_red)) {
                                ur_color = 'tr_red';
                            } else if (parseFloat(url_ratio) < parseFloat(cf_template.url_ratio_red) && parseFloat(url_ratio) > parseFloat(cf_template.url_ratio_green)) {
                                ur_color = 'tr_yellow';
                            } else if (parseFloat(url_ratio) <= parseFloat(cf_template.url_ratio_green)) {
                                ur_color = 'tr_green';
                            }

                            /**
                             *
                             *     CONDITIONAL FORMATTING
                             *
                             */

                            let newTR = '';
                            newTR += '<tr data-queued="' + keyword.queued + '" data-id="' + keyword.id + '">';
                            newTR += '<td><div  class="keywordInput" data-target="keyword">' + keyword.keyword + '</div></td>';

                            newTR += '<td class="' + volume_color + '"><div  class="keywordInput" data-target="volume">' + utils.parseNumber(keyword.volume) + '</div></td>';
                            newTR += '<td class="' + cpc_color + '"><div  class="keywordInput" data-target="cpc">' + keyword.cpc + '</div></td>';

                            keyword.intitle = keyword.intitle ?? utils.parseNumber(keyword.intitle);
                            keyword.inurl = keyword.inurl ?? utils.parseNumber(keyword.inurl);
                            newTR += '<td data-target="intitle" class="' + intitle_color + '"><div  class="keywordInput" data-target="intitle">' + keyword.intitle + '</div></td>';
                            newTR += '<td data-target="inurl" class="' + inurl_color + '"><div  class="keywordInput" data-target="inurl">' + keyword.inurl + '</div></td>';

                            if (title_ratio != "") {
                                if (tr_color == "tr_green" && (parseFloat(cf_template.tr_goldbar_volume) >= parseFloat(keyword.volume) && parseFloat(cf_template.tr_goldbar_intitle) >= parseFloat(keyword.intitle))) {
                                    newTR += '<td class="text-center ' + tr_color + '" data-target="tr"><div contenteditable="false" class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-title="Value: ' + parseFloat(title_ratio).toFixed(3) + '">' +
                                        '<img src="'+ xagio_shared_data.plugins_url +'assets/img/gold.webp" />' +
                                        '</div></td>';
                                } else {
                                    newTR += '<td class="text-center ' + tr_color + '" data-target="tr"><div  class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-title="Value: ' + parseFloat(title_ratio).toFixed(3) + '">' + parseFloat(title_ratio).toFixed(3) + '</div></td>';
                                }
                            } else {
                                newTR += '<td class="text-center ' + tr_color + '" data-target="tr"><div  class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-title="Search Volume and InTitle metrics must be retrieved first to see the Title Ratio."><i class="xagio-icon xagio-icon-minus"></i></div></td>';
                            }

                            if (url_ratio != "") {
                                if (ur_color == "tr_green" && (parseFloat(cf_template.ur_goldbar_volume) >= parseFloat(keyword.volume) && parseFloat(cf_template.ur_goldbar_intitle) >= parseFloat(keyword.inurl))) {
                                    newTR += '<td class="text-center ' + ur_color + '" data-target="ur"><div contenteditable="false" class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-title="Value: ' + parseFloat(url_ratio).toFixed(3) + '">' +
                                        '<img src="' + xagio_shared_data.plugins_url + 'assets/img/gold.webp" />' +
                                        '</div></td>';
                                } else {
                                    newTR += '<td class="text-center ' + ur_color + '" data-target="ur"><div  class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-title="Value: ' + parseFloat(url_ratio).toFixed(3) + '">' + parseFloat(url_ratio).toFixed(3) + '</div></td>';
                                }
                            } else {
                                newTR += '<td class="text-center ' + ur_color + '" data-target="ur"><div  class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-title="Search Volume and InURL metrics must be retrieved first to see the URL Ratio."><i class="xagio-icon xagio-icon-minus"></i></div></td>';
                            }

                            let rank      = utils.isJSON(keyword.rank);
                            let rank_cell = '';

                            if (rank == 0) {
                                rank_cell = '<span data-xagio-tooltip data-xagio-title="Not Added"><i class="xagio-icon xagio-icon-minus"></i><span style="display: none;">99999</span></span>';
                            } else if (rank == 501) {
                                rank_cell = '<span data-xagio-tooltip data-xagio-title="Analysing..."><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i><span style="display: none;">99998</span></span>';
                            } else {

                                let max        = 501;
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
                                        rank_title += `${obj.engine} : ${obj.rank} <br>`;
                                    } else {
                                        rank_title += `${obj.engine} : <i class='xagio-icon xagio-icon-ban'></i><br>`;
                                    }

                                }

                                if (max == 501) {
                                    rank_cell = `<a href="#" data-xagio-tooltip data-xagio-title="${rank_title}"><i class='xagio-icon xagio-icon-ban'></i><span style="display: none;">99997</span></a>`;
                                } else {
                                    if ($.isNumeric(rank)) {
                                        rank_cell = max;
                                    } else {
                                        rank_cell = `<a href="#" data-xagio-tooltip data-xagio-title="${rank_title}">${max}</a>`;
                                    }
                                }

                            }

                            newTR += '<td class="text-center">' + rank_cell + '</td>';
                            newTR += '</tr>';

                            groupKeywords += newTR;
                        }
                        kwData.append(groupKeywords);
                    }

                    groups.push(template);
                }

                data.append(groups);

            } else {
                project_groups.hide();
            }


            actions.updateElements();
        },
        updateElements:    function () {
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

        },
        loadCfTemplates:               function () {
            let shared_template = JSON.parse(atob(templates));
            if (shared_template.status == 'success' && shared_template.data != 'null') {

                cf_templates = $.extend(cf_templates, shared_template.data);
            }
            $('#conditional-formatting .uk-modal-footer button').attr('disabled', false);

            let template = cf_templates[shared_template.default];

            // Set default template globally
            cf_template         = template.data;
            cf_default_template = shared_template.default;
        }
    };

    let utils = {
        decodeHtml:           function (html) {
            var txt       = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        },
        prepareURL:        function (url) {
            if (url == null || url == '') {
                return {
                    pre:  '/',
                    name: ''
                };
            }
            let hasSlash = 2;
            if (url.substr(-1) != '/') {
                hasSlash = 1;
            }

            url      = url.split('/');
            let name = url[url.length - hasSlash];
            let cat  = url.slice(0, -hasSlash).join('/') + '/';
            return {
                pre:  cat,
                name: name
            };
        },
        parseNumber:          function (num) {
            if (num === null || num === "") {
                return '';
            } else {
                if(typeof num === 'string') {
                    num = num.replaceAll(',', '');
                }
                return parseInt(num).toLocaleString();
            }
        },
        cleanComma:         function (num) {
            if(typeof num === 'string') {
                num = num.replaceAll(',', '');
            }

            return num;
        },
        isJSON : function (json) {
            // var json;
            try {
                json = JSON.parse(json);
            } catch (e) {
                return 0;
            }
            return json;
        },
        parseUrl:             function (url) {
            let a = $('<a>', {
                href: url
            });
            return a;
        }
    }

})(jQuery);