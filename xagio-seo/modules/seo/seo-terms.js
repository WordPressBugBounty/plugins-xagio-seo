(function ($) {
    'use strict';

    $(document).ready(function () {

        search_preview.init();

    });

    let search_preview = {
        cf_templates                   : {
            Default     : {
                name: "Default", data: {
                    volume_red: 20, volume_green: 100,

                    cpc_red: 0.59, cpc_green: 1.00,

                    intitle_red: 1000, intitle_green: 250,

                    inurl_red: 1000, inurl_green: 250,

                    title_ratio_red: 1, title_ratio_green: 0.25,

                    url_ratio_red: 1, url_ratio_green: 0.25,

                    tr_goldbar_volume: 1000, tr_goldbar_intitle: 20,

                    ur_goldbar_volume: 1000, ur_goldbar_intitle: 20
                }
            }, Affiliate: {
                name: "Affiliate", data: {
                    volume_red: 100, volume_green: 1000,

                    cpc_red: 1.00, cpc_green: 2.00,

                    intitle_red: 10000, intitle_green: 1000,

                    inurl_red: 10000, inurl_green: 1000,

                    title_ratio_red: 1, title_ratio_green: 0.25,

                    url_ratio_red: 1, url_ratio_green: 0.25,

                    tr_goldbar_volume: 1000, tr_goldbar_intitle: 20,

                    ur_goldbar_volume: 1000, ur_goldbar_intitle: 20
                }
            }, Local    : {
                name: "Local", data: {
                    volume_red: 10, volume_green: 100,

                    cpc_red: 2.00, cpc_green: 5.00,

                    intitle_red: 1000, intitle_green: 100,

                    inurl_red: 1000, inurl_green: 100,

                    title_ratio_red: 1, title_ratio_green: 0.25,

                    url_ratio_red: 1, url_ratio_green: 0.25,

                    tr_goldbar_volume: 1000, tr_goldbar_intitle: 20,

                    ur_goldbar_volume: 1000, ur_goldbar_intitle: 20
                }
            }
        }, cf_default_template         : 'Default', cf_template: null, init: function () {
            search_preview.cf_template = search_preview.cf_templates[search_preview.cf_default_template].data

            search_preview.titleDescriptionCalculation();
            search_preview.editorInit();
            search_preview.attachGroupEvents();
            search_preview.editGroupEvents();
            search_preview.saveGroupEvents();
            search_preview.generateKeywordsTable();
            search_preview.wordCloudOnClick();
            search_preview.toTargetKeyword();
            search_preview.toKeywordGroup();
            search_preview.clearTargetKeyword();

            search_preview.calculations.ranking.init();
            search_preview.calculations.optimization.init();
            search_preview.calculations.suggestions.init();
        }, calculations                : {
            ranking            : {
                init                   : function () {
                    search_preview.calculations.ranking.targetKeywordChanged();
                    search_preview.calculations.shared_functions.countFoundIssues('ranking');
                    search_preview.calculations.shared_functions.getContentLoop();
                    search_preview.calculations.ranking.triggers.initAll();
                    target_keyword = $('#XAGIO_SEO_TARGET_KEYWORD').val();
                }, triggers            : {
                    enabled             : false, initAll: function () {
                        for (let tFK in search_preview.calculations.ranking.triggers) {
                            if (tFK.indexOf('tFK') !== -1) {
                                search_preview.calculations.ranking.triggers[tFK].init();
                            }
                        }
                    }, triggerAll       : function () {
                        for (let tFK in search_preview.calculations.ranking.triggers) {
                            if (tFK.indexOf('tFK') !== -1) {
                                search_preview.calculations.ranking.triggers[tFK].trigger();
                            }
                        }
                    }, tFK_SeoTitle     : {
                        trigger: function () {
                            if (!search_preview.calculations.ranking.triggers.enabled) return;

                            let element = $('.tFK_SeoTitle');
                            let seoTitle = search_preview.calculations.shared_functions.getTitle();

                            element.removeClass('analysis-error analysis-warning analysis-ok');

                            if (seoTitle != '') {

                                // if seo title contains target_keyword
                                if (seoTitle.toLowerCase().indexOf(target_keyword.toLowerCase()) > -1) {
                                    element.addClass('analysis-ok');
                                    element.find('span').html('Target Keyword found in SEO Title');
                                } else {
                                    element.addClass('analysis-error');
                                    element.find('span').html('Add Target Keyword to the SEO Title.');
                                }

                            } else {
                                seoTitle = $('[name="post_title"]').val();

                                // if seo title contains target_keyword
                                if (seoTitle.toLowerCase().indexOf(target_keyword.toLowerCase()) > -1) {
                                    element.addClass('analysis-warning');
                                    element.find('span').html('SEO Title is empty, but Focus keyword was found in Post H1, which is used as a fallback.');
                                } else {
                                    element.addClass('analysis-error');
                                    element.find('span').html('Add Target Keyword to the SEO Title.');
                                }

                            }

                            search_preview.calculations.shared_functions.countFoundIssues('ranking');
                        }, init: function () {
                            $(document).on('change', '[name="XAGIO_SEO_TITLE"]', function () {
                                search_preview.calculations.ranking.triggers.tFK_SeoTitle.trigger();
                            });
                        }
                    }, tFK_SeoDesc      : {
                        trigger: function () {
                            if (!search_preview.calculations.ranking.triggers.enabled) return;

                            let element = $('.tFK_SeoDesc');
                            let seoDesc = search_preview.calculations.shared_functions.getDescription();

                            element.removeClass('analysis-error analysis-warning analysis-ok');

                            if (seoDesc != '') {

                                // if seo title contains target_keyword
                                if (seoDesc.toLowerCase().indexOf(target_keyword.toLowerCase()) > -1) {
                                    element.addClass('analysis-ok');
                                    element.find('span').html('Target Keyword found in SEO Description.');
                                } else {
                                    element.addClass('analysis-error');
                                    element.find('span').html('Add Target Keyword to your SEO Description.');
                                }

                            } else {
                                element.addClass('analysis-error');
                                element.find('span').html('Add Target Keyword to your SEO Description.');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('ranking');
                        }, init: function () {
                            $(document).on('change', '[name="XAGIO_SEO_DESCRIPTION"]', function () {
                                search_preview.calculations.ranking.triggers.tFK_SeoDesc.trigger();
                            });
                        }
                    }, tFK_SeoUrl       : {
                        trigger: function () {
                            if (!search_preview.calculations.ranking.triggers.enabled) return;

                            // replace all spaces with dashes and make lowercase in target_keyword
                            let target_keyword_url = target_keyword.replace(/ /g, '-').toLowerCase();

                            let element = $('.tFK_SeoUrl');
                            let seoUrl = search_preview.calculations.shared_functions.getUrl();

                            element.removeClass('analysis-error analysis-warning analysis-ok');

                            if (seoUrl != '') {

                                // if seo title contains target_keyword
                                if (seoUrl.toLowerCase().indexOf(target_keyword_url) > -1) {
                                    element.addClass('analysis-ok');
                                    element.find('span').html('Target Keyword found in URL.');
                                } else {
                                    element.addClass('analysis-error');
                                    element.find('span').html('Use Target Keyword in the URL.');
                                }

                            } else {

                                seoUrl = $('#sample-permalink > a').attr('href');

                                // if seo title contains target_keyword
                                if (seoUrl.toLowerCase().indexOf(target_keyword_url) > -1) {
                                    element.addClass('analysis-ok');
                                    element.find('span').html('Focus keyword found in URL.');
                                } else {
                                    element.addClass('analysis-error');
                                    element.find('span').html('Use Target Keyword in the URL.');
                                }
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('ranking');
                        }, init: function () {
                            $(document).on('change', '[name="XAGIO_SEO_URL"]', function () {
                                search_preview.calculations.ranking.triggers.tFK_SeoUrl.trigger();
                            });
                            $(document).on('keyup', '[name="XAGIO_SEO_URL"]', function () {
                                search_preview.calculations.ranking.triggers.tFK_SeoUrl.trigger();
                            });

                            let origOpen = XMLHttpRequest.prototype.open;
                            XMLHttpRequest.prototype.open = function () {
                                this.addEventListener('load', function () {
                                    // if this.responseText contains 'sample-permalink'
                                    if (this.responseText.indexOf('sample-permalink') > -1) {
                                        setTimeout(function () {
                                            search_preview.calculations.ranking.triggers.tFK_SeoUrl.trigger();
                                        }, 200);
                                    }
                                });
                                origOpen.apply(this, arguments);
                            };
                        }
                    }, tFK_Content      : {
                        trigger: function () {
                            if (!search_preview.calculations.ranking.triggers.enabled) return;

                            let element = $('.tFK_Content');
                            let content = search_preview.calculations.shared_functions.getContentText;

                            if (content != '') {

                                // if seo title contains target_keyword
                                if (content.toLowerCase().indexOf(target_keyword.toLowerCase()) > -1) {
                                    if (!element.hasClass('analysis-ok')) {
                                        element.removeClass('analysis-error analysis-warning');
                                        element.addClass('analysis-ok');
                                        element.find('span').html('Target Keyword found in the content.');
                                    }
                                } else {
                                    if (!element.hasClass('analysis-error')) {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html('Add Target Keyword in the content.');
                                    }
                                }

                            } else {
                                element.addClass('analysis-error');
                                element.find('span').html('Add Target Keyword in the content.');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('ranking');
                        }, init: function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.ranking.triggers.tFK_Content.trigger();
                                search_preview.calculations.ranking.triggers.tFK_Content.init();
                            }, 500);
                        }
                    }, tFK_SubHead      : {
                        trigger: function () {
                            if (!search_preview.calculations.ranking.triggers.enabled) return;

                            let element = $('.tFK_SubHead');
                            let content = search_preview.calculations.shared_functions.getContentRaw;
                            content = content.replace(/src=".+?"/gi, 'src="data:,"')

                            if (content != '') {

                                let $content = $('<div/>').html(content);

                                let found = false;
                                $content.find('h2,h3,h4,h5,h6').each(function () {

                                    if ($(this).text().toLowerCase().indexOf(target_keyword.toLowerCase()) > -1) {
                                        found = true;
                                    }

                                });
                                if (found) {
                                    if (!element.hasClass('analysis-ok')) {
                                        element.removeClass('analysis-error analysis-warning');
                                        element.addClass('analysis-ok');
                                        element.find('span').html('Target Keyword found in the subheading(s) like H2, H3, H4, etc..');
                                    }
                                } else {
                                    if (!element.hasClass('analysis-error')) {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html('Target Keyword not present in subheading(s) like H2, H3, H4, etc..');
                                    }
                                }

                            } else {
                                element.addClass('analysis-warning');
                                element.find('span').html('Use Target Keyword in subheading(s) like H2, H3, H4, etc..');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('ranking');
                        }, init: function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.ranking.triggers.tFK_SubHead.trigger();
                                search_preview.calculations.ranking.triggers.tFK_SubHead.init();
                            }, 500);
                        }
                    }, tFK_ImageAlt     : {
                        trigger: function () {
                            if (!search_preview.calculations.ranking.triggers.enabled) return;

                            let element = $('.tFK_ImageAlt');
                            let content = search_preview.calculations.shared_functions.getContentRaw;
                            content = content.replace(/src=".+?"/gi, 'src="data:,"')

                            if (content != '') {

                                let $content = $('<div/>').html(content);

                                let found = false;

                                $content.find('img').each(function () {

                                    let attr = $(this).attr('alt');
                                    if (typeof attr !== 'undefined' && attr !== false) {

                                        if (attr.toLowerCase().indexOf(target_keyword.toLowerCase()) > -1) {
                                            found = true;
                                        }
                                    }

                                });
                                if (found) {
                                    if (!element.hasClass('analysis-ok')) {
                                        element.removeClass('analysis-error analysis-warning');
                                        element.addClass('analysis-ok');
                                        element.find('span').html('Image with Target Keyword  in alt text found.');
                                    }
                                } else {
                                    if (!element.hasClass('analysis-error')) {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html('Add an image with your Target Keyword as alt text.');
                                    }
                                }

                            } else {
                                element.addClass('analysis-warning');
                                element.find('span').html('Add an image with your Target Keyword as alt text.');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('ranking');
                        }, init: function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.ranking.triggers.tFK_ImageAlt.trigger();
                                search_preview.calculations.ranking.triggers.tFK_ImageAlt.init();
                            }, 500);
                        }
                    }, tFK_BeginSeoTitle: {
                        trigger: function () {
                            if (!search_preview.calculations.ranking.triggers.enabled) return;

                            let element = $('.tFK_BeginSeoTitle');
                            let seoTitle = search_preview.calculations.shared_functions.getTitle();

                            element.removeClass('analysis-error analysis-warning analysis-ok');

                            if (seoTitle != '') {

                                if (seoTitle.toLowerCase().indexOf(target_keyword.toLowerCase()) < 12 && seoTitle.toLowerCase().indexOf(target_keyword.toLowerCase()) > -1) {
                                    element.addClass('analysis-ok');
                                    element.find('span').html('Target Keyword found near the beginning of the SEO Title.');
                                } else {
                                    element.addClass('analysis-error');
                                    element.find('span').html('Use the Target Keyword near the beginning of the SEO Title.');
                                }

                            } else {
                                seoTitle = $('[name="post_title"]').val();

                                if (seoTitle.toLowerCase().indexOf(target_keyword.toLowerCase()) < 12 && seoTitle.toLowerCase().indexOf(target_keyword.toLowerCase()) > -1) {
                                    element.addClass('analysis-warning');
                                    element.find('span').html('SEO Title is empty, but Focus keyword was found near the beginning of Post H1, which is used as a fallback.');
                                } else {
                                    element.addClass('analysis-error');
                                    element.find('span').html('Use the Target Keyword near the beginning of the SEO Title.');
                                }

                            }

                            search_preview.calculations.shared_functions.countFoundIssues('ranking');
                        }, init: function () {
                            $(document).on('change', '[name="XAGIO_SEO_TITLE"]', function () {
                                search_preview.calculations.ranking.triggers.tFK_BeginSeoTitle.trigger();
                            });
                        }
                    }, tFK_KwDensity    : {
                        trigger: function () {
                            if (!search_preview.calculations.ranking.triggers.enabled) return;

                            let element = $('.tFK_KwDensity');
                            let content = search_preview.calculations.shared_functions.getContentText;
                            let target_keyword_density = target_keyword.toLowerCase();
                            let pattern = new RegExp(target_keyword_density, "g");

                            if (content != '') {

                                let occurrences = (content.match(pattern) || []).length;
                                let words_count = search_preview.calculations.shared_functions.getWordCount(content);

                                let density = ((occurrences / words_count) * 100).toFixed(2);

                                if (density == 0) {
                                    if (element.find('b.current').text() != '0%') {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html('Target Keyword Density is <b class="current">0%</b>. Aim for around <b class="target">1%</b> Keyword Density.');
                                    }
                                } else if (density > 0 && density < 0.6) {
                                    if (element.find('b.current').text() != density + '%') {
                                        element.removeClass('analysis-error analysis-ok');
                                        element.addClass('analysis-warning');
                                        element.find('span').html(`Target Keyword Density is <b class="current">${density}%</b>. Just a little more to reach at least <b class="target">0.6%</b> Keyword Density.`);
                                    }
                                } else if (density > 0.6 && density < 1) {
                                    if (element.find('b.current').text() != density + '%') {
                                        element.removeClass('analysis-error analysis-warning');
                                        element.addClass('analysis-warning');
                                        element.find('span').html(`Target Keyword Density is <b class="current">${density}%</b>. Perfect!`);
                                    }
                                } else {
                                    if (element.find('b.current').text() != density + '%') {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html(`Target Keyword Density is <b class="current">${density}%</b>. That's too much! Aim for around <b class="target">1%</b> Keyword Density.`);
                                    }
                                }

                            } else {
                                element.addClass('analysis-warning');
                                element.find('span').html('Target Keyword Density is <b>0%</b>. Aim for around <b>1%</b> Keyword Density.');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('ranking');
                        }, init: function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.ranking.triggers.tFK_KwDensity.trigger();
                                search_preview.calculations.ranking.triggers.tFK_KwDensity.init();
                            }, 500);
                        }
                    },
                }, targetKeywordChanged: function () {
                    $(document).on('change', '#XAGIO_SEO_TARGET_KEYWORD', function () {

                        let current_value = $(this).val();
                        let analysis_ranking = $(".analysis-ranking");

                        if (current_value == '') {

                            if (!analysis_ranking.find('.analysis-object').hasClass('uk-hidden')) {

                                analysis_ranking.find('.analysis-object').addClass('uk-hidden');
                                analysis_ranking.append('<span class="analysis-info analysis-warning"><i class="far fa-exclamation-circle"></i> <span>Please enter a <b>Target Keyword</b> to get the SEO ranking calculation.</span></span>');
                                search_preview.calculations.ranking.triggers.enabled = false;

                            }

                        } else {

                            analysis_ranking.find('.analysis-object').removeClass('uk-hidden');
                            analysis_ranking.find('.analysis-info').remove();

                            target_keyword = current_value;

                            search_preview.calculations.ranking.triggers.enabled = true;
                            search_preview.calculations.ranking.triggers.triggerAll();

                        }

                        search_preview.calculations.shared_functions.countFoundIssues('ranking');
                    });
                    $(document).on('keyup', '#XAGIO_SEO_TARGET_KEYWORD', function () {
                        $('#XAGIO_SEO_TARGET_KEYWORD').trigger('change');
                    });

                    // necessary for the first load
                    $('#XAGIO_SEO_TARGET_KEYWORD').trigger('change');
                },
            }, optimization    : {
                init       : function () {
                    search_preview.calculations.shared_functions.countFoundIssues('optimization');
                    search_preview.calculations.optimization.triggers.initAll();
                }, triggers: {
                    initAll             : function () {
                        for (let tOP in search_preview.calculations.optimization.triggers) {
                            if (tOP.indexOf('tOP') !== -1) {
                                search_preview.calculations.optimization.triggers[tOP].init();
                            }
                        }
                    }, tOP_ContentLength: {
                        trigger: function () {

                            let element = $('.tOP_ContentLength');
                            let word_count = search_preview.calculations.shared_functions.getWordCount(search_preview.calculations.shared_functions.getContentText);

                            if (word_count != 0) {

                                if (word_count < 500) {
                                    if (element.find('b.current').text() != word_count) {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html(`Content is <b class="current">${word_count}</b> words long. Consider using at least <b class="target">500</b> words.`);
                                    }
                                } else if (word_count >= 500 && word_count <= 2100) {
                                    if (element.find('b.current').text() != word_count) {
                                        element.removeClass('analysis-ok analysis-error');
                                        element.addClass('analysis-warning');
                                        element.find('span').html(`Content is <b class="current">${word_count}</b> words long, but to optimize even better and get the ideal word count, consider using at least <b class="target">2100</b> words.`);
                                    }
                                } else {
                                    if (element.find('b.current').text() != word_count) {
                                        element.removeClass('analysis-error analysis-warning');
                                        element.addClass('analysis-ok');
                                        element.find('span').html(`Content is <b class="current">${word_count}</b> words long, which is great!`);
                                    }
                                }

                            } else {
                                element.addClass('analysis-error');
                                element.find('span').html('Content is empty. Consider using at least 500 words.');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('optimization');
                        }, init: function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.optimization.triggers.tOP_ContentLength.trigger();
                                search_preview.calculations.optimization.triggers.tOP_ContentLength.init();
                            }, 500);
                        }
                    }, tOP_TitleLength  : {
                        trigger: function () {

                            let element = $('.tOP_TitleLength');
                            let title_count = search_preview.calculations.shared_functions.getTitle().length;

                            if (title_count != 0) {

                                if (title_count < 20) {
                                    if (element.find('b.current').text() != title_count) {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html(`SEO Title is <b class="current">${title_count}</b> characters long. Consider using at least <b class="target">20</b> characters.`);
                                    }
                                } else if (title_count >= 20 && title_count <= 60) {
                                    if (element.find('b.current').text() != title_count) {
                                        element.removeClass('analysis-warning analysis-error');
                                        element.addClass('analysis-ok');
                                        element.find('span').html(`SEO Title is <b class="current">${title_count}</b> characters long. Perfect!`);
                                    }
                                } else if (title_count > 60) {
                                    if (element.find('b.current').text() != title_count) {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html(`SEO Title is <b class="current">${title_count}</b> characters long. Consider using less than <b class="target">60</b> characters.`);
                                    }
                                }

                            } else {
                                element.addClass('analysis-error');
                                element.find('span').html('SEO Title is empty. Consider using at least <b>20</b> characters.');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('optimization');
                        }, init: function () {
                            $(document).on('change', '[name="XAGIO_SEO_TITLE"]', function () {
                                search_preview.calculations.optimization.triggers.tOP_TitleLength.trigger();
                            });
                            $('[name="XAGIO_SEO_TITLE"]').trigger('change');
                        }
                    }, tOP_DescLength   : {
                        trigger: function () {

                            let element = $('.tOP_DescLength');
                            let desc_count = search_preview.calculations.shared_functions.getDescription().length;

                            if (desc_count != 0) {

                                if (desc_count < 50) {
                                    if (element.find('b.current').text() != desc_count) {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html(`SEO Description is <b class="current">${desc_count}</b> characters long. Consider using at least <b class="target">50</b> characters.`);
                                    }
                                } else if (desc_count >= 50 && desc_count <= 160) {
                                    if (element.find('b.current').text() != desc_count) {
                                        element.removeClass('analysis-warning analysis-error');
                                        element.addClass('analysis-ok');
                                        element.find('span').html(`SEO Description is <b class="current">${desc_count}</b> characters long. Perfect!`);
                                    }
                                } else if (desc_count > 160) {
                                    if (element.find('b.current').text() != desc_count) {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html(`SEO Description is <b class="current">${desc_count}</b> characters long. Consider using less than <b class="target">160</b> characters.`);
                                    }
                                }

                            } else {
                                element.addClass('analysis-error');
                                element.find('span').html('SEO Description is empty. Consider using at least <b>50</b> characters.');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('optimization');
                        }, init: function () {
                            $(document).on('change', '[name="XAGIO_SEO_DESCRIPTION"]', function () {
                                search_preview.calculations.optimization.triggers.tOP_DescLength.trigger();
                            });
                            $('[name="XAGIO_SEO_DESCRIPTION"]').trigger('change');
                        }
                    }, tOP_UrlLength    : {
                        trigger: function () {

                            let element = $('.tOP_UrlLength');
                            let url_count = search_preview.calculations.shared_functions.getUrl();
                            if (typeof url_count == 'undefined') {
                                element.removeClass('analysis-warning analysis-error');
                                element.addClass('analysis-ok');
                                element.find('span').html(`This URL is a homepage, length calculation not necessary.`);

                                search_preview.calculations.shared_functions.countFoundIssues('optimization');
                            } else {
                                url_count = url_count.length;
                            }

                            if (url_count != 0) {

                                if (url_count < 10) {
                                    if (element.find('b.current').text() != url_count) {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html(`URL is <b class="current">${url_count}</b> characters long. Consider using at least <b class="target">10</b> characters.`);
                                    }
                                } else if (url_count >= 10 && url_count <= 60) {
                                    if (element.find('b.current').text() != url_count) {
                                        element.removeClass('analysis-warning analysis-error');
                                        element.addClass('analysis-ok');
                                        element.find('span').html(`URL is <b class="current">${url_count}</b> characters long. Perfect!`);
                                    }
                                } else if (url_count > 60) {
                                    if (element.find('b.current').text() != url_count) {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html(`URL is <b class="current">${url_count}</b> characters long. Consider using less than <b class="target">60</b> characters.`);
                                    }
                                }

                            } else {
                                element.addClass('analysis-error');
                                element.find('span').html('URL is empty. Consider using at least <b>10</b> characters.');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('optimization');
                        }, init: function () {
                            $(document).on('change', '[name="XAGIO_SEO_URL"]', function () {
                                search_preview.calculations.optimization.triggers.tOP_UrlLength.trigger();
                            });
                            $(document).on('keyup', '[name="XAGIO_SEO_URL"]', function () {
                                search_preview.calculations.optimization.triggers.tOP_UrlLength.trigger();
                            });
                            $('[name="XAGIO_SEO_URL"]').trigger('change');

                            if ($('[name="XAGIO_SEO_URL"]').length < 1) {
                                search_preview.calculations.optimization.triggers.tOP_UrlLength.trigger();
                            }

                            let origOpen = XMLHttpRequest.prototype.open;
                            XMLHttpRequest.prototype.open = function () {
                                this.addEventListener('load', function () {
                                    // if this.responseText contains 'sample-permalink'
                                    if (this.responseText.indexOf('sample-permalink') > -1) {
                                        setTimeout(function () {
                                            search_preview.calculations.optimization.triggers.tOP_UrlLength.trigger();
                                        }, 200);
                                    }
                                });
                                origOpen.apply(this, arguments);
                            };
                        }
                    }, tOP_NumberTitle  : {
                        trigger: function () {

                            let element = $('.tOP_NumberTitle');
                            let title = search_preview.calculations.shared_functions.getTitle();

                            if (title != '') {

                                // if title contains number
                                if (/\d/.test(title)) {
                                    element.removeClass('analysis-warning analysis-error');
                                    element.addClass('analysis-ok');
                                    element.find('span').html('You are using a number in your SEO Title. Perfect!');
                                } else {
                                    element.removeClass('analysis-ok analysis-warning');
                                    element.addClass('analysis-error');
                                    element.find('span').html('You are <b>not</b> using a number in your SEO Title.');
                                }

                            } else {
                                element.removeClass('analysis-ok analysis-error');
                                element.addClass('analysis-warning');
                                element.find('span').html('You are <b>not</b> using a number in your SEO Title.');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('optimization');
                        }, init: function () {
                            $(document).on('change', '[name="XAGIO_SEO_TITLE"]', function () {
                                search_preview.calculations.optimization.triggers.tOP_NumberTitle.trigger();
                            });
                            $('[name="XAGIO_SEO_TITLE"]').trigger('change');
                        }
                    }, tOP_AddMedia     : {
                        trigger: function () {

                            let element = $('.tOP_AddMedia');
                            let content = search_preview.calculations.shared_functions.getContentRaw;

                            if (content != '') {

                                // check if content contains image or video
                                if (content.indexOf('<img') > -1 || content.indexOf('<video') > -1) {
                                    if (!element.hasClass('analysis-ok')) {
                                        element.removeClass('analysis-warning analysis-error');
                                        element.addClass('analysis-ok');
                                        element.find('span').html('You are using images or videos in your content. Perfect!');
                                    }
                                } else {
                                    if (!element.hasClass('analysis-error')) {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html('You are <b>not</b> using images or videos in your content. Consider adding some.');
                                    }
                                }

                            } else {
                                element.removeClass('analysis-error analysis-ok');
                                element.addClass('analysis-warning');
                                element.find('span').html('Content is empty. Consider adding images or videos.');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('optimization');
                        }, init: function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.optimization.triggers.tOP_AddMedia.trigger();
                                search_preview.calculations.optimization.triggers.tOP_AddMedia.init();
                            }, 500);
                        }
                    }, tOP_IntLinks     : {
                        trigger: function () {

                            let element = $('.tOP_IntLinks');
                            let content = search_preview.calculations.shared_functions.getContentRaw;

                            if (content != '') {


                                if (content.indexOf('href="/') > -1) {
                                    if (!element.hasClass('analysis-ok')) {
                                        element.removeClass('analysis-warning analysis-error');
                                        element.addClass('analysis-ok');
                                        element.find('span').html('Internal Links found in content. Perfect!');
                                    }
                                } else {
                                    if (!element.hasClass('analysis-error')) {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html('Internal Links <b>not</b> found in content. Consider linking to other pages on your site.');
                                    }
                                }

                            } else {
                                element.removeClass('analysis-error analysis-ok');
                                element.addClass('analysis-warning');
                                element.find('span').html('Content is empty. Consider adding Internal Links.');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('optimization');
                        }, init: function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.optimization.triggers.tOP_IntLinks.trigger();
                                search_preview.calculations.optimization.triggers.tOP_IntLinks.init();
                            }, 500);
                        }
                    }, tOP_ExtLinks     : {
                        trigger: function () {

                            let element = $('.tOP_ExtLinks');
                            let content = search_preview.calculations.shared_functions.getContentRaw;

                            if (content != '') {

                                if (content.indexOf('href="http') > -1) {
                                    if (!element.hasClass('analysis-ok')) {
                                        element.removeClass('analysis-warning analysis-error');
                                        element.addClass('analysis-ok');
                                        element.find('span').html('External Links found in content. Perfect!');
                                    }
                                } else {
                                    if (!element.hasClass('analysis-error')) {
                                        element.removeClass('analysis-ok analysis-warning');
                                        element.addClass('analysis-error');
                                        element.find('span').html('External Links <b>not</b> found in content. Consider linking to external references.');
                                    }
                                }

                            } else {
                                element.removeClass('analysis-error analysis-ok');
                                element.addClass('analysis-warning');
                                element.find('span').html('Content is empty. Consider adding Internal Links.');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('optimization');
                        }, init: function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.optimization.triggers.tOP_ExtLinks.trigger();
                                search_preview.calculations.optimization.triggers.tOP_ExtLinks.init();
                            }, 500);
                        }
                    }, tOP_ReadScore    : {
                        trigger: function () {

                            let element = $('.tOP_ReadScore');
                            let content = search_preview.calculations.shared_functions.getContentText;

                            if (content != '') {

                                let rating = parseInt(rate(content));

                                if (rating < 30) {
                                    if (element.find('b.current').text() != rating) {
                                        element.removeClass('analysis-ok analysis-error');
                                        element.addClass('analysis-warning');
                                        element.find('span').html(`Content is <b>pretty difficult</b> to read. Readability score is <b class="current">${rating}</b>. Consider simplifying content to reach <b class="target">70</b> Readability score.`);
                                    }
                                } else if (rating >= 30 && rating <= 60) {
                                    if (element.find('b.current').text() != rating) {
                                        element.removeClass('analysis-ok analysis-error');
                                        element.addClass('analysis-warning');
                                        element.find('span').html(`Content is <b>difficult</b> to read. Readability score is <b class="current">${rating}</b>. Consider simplifying content to reach <b class="target">70</b> Readability score.`);
                                    }
                                } else if (rating > 60) {
                                    if (element.find('b.current').text() != rating) {
                                        element.removeClass('analysis-error analysis-warning');
                                        element.addClass('analysis-ok');
                                        element.find('span').html(`Content is <b>easy</b> to read. Readability score is <b class="current">${rating}</b>. Pefect!`);
                                    }
                                }

                            } else {
                                element.removeClass('analysis-warning analysis-ok');
                                element.addClass('analysis-error');
                                element.find('span').html('Content is empty. Readability score will not be calculated.');
                            }

                            search_preview.calculations.shared_functions.countFoundIssues('optimization');
                        }, init: function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.optimization.triggers.tOP_ReadScore.trigger();
                                search_preview.calculations.optimization.triggers.tOP_ReadScore.init();
                            }, 500);
                        }
                    },
                }
            }, suggestions     : {
                init                     : function () {
                    search_preview.calculations.suggestions.targetKeywordChanged();
                }, timeout               : null, targetKeywordChanged: function () {
                    $(document).on('change', '[name="XAGIO_SEO_TARGET_KEYWORD"]', function () {

                        if ($('#lock-suggestions').is(':checked')) return;

                        let current_value = $(this).val();
                        let suggestion_keywords = $(".suggestion-keywords");

                        if (current_value == '') {

                            suggestion_keywords.empty();
                            suggestion_keywords.append('<td colspan="9" class="uk-text-center"><i class="far fa-exclamation-circle"></i> Please enter a <b>Target Keyword</b> to get the Keyword Suggestions.</td>');

                        } else {

                            target_keyword = current_value;

                            clearTimeout(search_preview.calculations.suggestions.timeout);
                            search_preview.calculations.suggestions.timeout = setTimeout(function () {

                                suggestion_keywords.empty();
                                suggestion_keywords.append('<td colspan="9" class="uk-text-center"><i class="fa-light fa-sync fa-spin"></i> Loading Suggested Keywords... </td>');
                                search_preview.calculations.suggestions.getSuggestions();
                            }, 1500);

                        }

                    });
                    $('[name="XAGIO_SEO_TARGET_KEYWORD"]').trigger('change');
                    $('#lock-suggestions').prop('checked', true);
                }, getSuggestions        : function () {
                    $.post(xagio_data.wp_post, `action=xagio_keyword_suggestions&post_id=${xagio_post_id}&keyword=` + search_preview.calculations.shared_functions.getKeyword(), function (d) {

                        let suggestion_keywords = $(".suggestion-keywords");
                        suggestion_keywords.empty();

                        if (!d.hasOwnProperty('data')) {
                            suggestion_keywords.append('<td colspan="9" class="uk-text-center"><i class="far fa-exclamation-circle"></i> An error occurred, please try again later.</td>');
                            return;
                        }

                        if (d.data.length > 0) {
                            let kwData = suggestion_keywords;

                            let groupKeywords = [];

                            for (let i = 0; i < d.data.length; i++) {
                                let keyword = d.data[i];

                                // remove null values
                                for (let key in keyword) {
                                    if (keyword.hasOwnProperty(key)) {
                                        if (keyword[key] == null) {
                                            keyword[key] = '';
                                        }
                                    }
                                }

                                /**
                                 *
                                 *     CONDITIONAL FORMATTING
                                 *
                                 */

                                let volume_color, cpc_color;

                                if (keyword.volume == "") {
                                    volume_color = '';
                                } else if (parseFloat(search_preview.cf_template.volume_red) >= parseFloat(keyword.volume)) {
                                    volume_color = 'tr_red';
                                } else if (parseFloat(search_preview.cf_template.volume_red) < parseFloat(keyword.volume) && parseFloat(search_preview.cf_template.volume_green) > parseFloat(keyword.volume)) {
                                    volume_color = 'tr_yellow';
                                } else if (parseFloat(search_preview.cf_template.volume_green) <= parseFloat(keyword.volume)) {
                                    volume_color = 'tr_green';
                                }

                                if (keyword.cpc == "") {
                                    cpc_color = '';
                                } else if (parseFloat(search_preview.cf_template.cpc_red) >= parseFloat(keyword.cpc)) {
                                    cpc_color = 'tr_red';
                                } else if (parseFloat(search_preview.cf_template.cpc_red) < parseFloat(keyword.cpc) && parseFloat(search_preview.cf_template.cpc_green) > parseFloat(keyword.cpc)) {
                                    cpc_color = 'tr_yellow';
                                } else if (parseFloat(search_preview.cf_template.cpc_green) <= parseFloat(keyword.cpc)) {
                                    cpc_color = 'tr_green';
                                }

                                /**
                                 *
                                 *     CONDITIONAL FORMATTING
                                 *
                                 */


                                let tr = $('<tr data-id="0"></tr>');
                                tr.append('<td><button data-xagio-tooltip data-xagio-title="Set as Target Keyword." class="to-target-keyword" type="button"><i class="far fa-caret-up"></i></button><button data-xagio-tooltip data-xagio-title="Add this keyword to your Group above." class="to-keyword-group" type="button"><i class="far fa-plus"></i></button></td>');
                                tr.append('<td><div contenteditable="true" class="keywordInput" data-target="keyword">' + keyword.keyword + '</div></td>');

                                tr.append('<td class="' + volume_color + '"><div contenteditable="true" class="keywordInput" data-target="volume">' + search_preview.parseNumber(keyword.volume) + '</div></td>');
                                tr.append('<td class="' + cpc_color + '"><div contenteditable="true" class="keywordInput" data-target="cpc">' + keyword.cpc + '</div></td>');

                                tr.append('<td data-target="intitle"><div contenteditable="true" class="keywordInput" data-target="intitle">' + search_preview.parseNumber(keyword.intitle) + '</div></td>');
                                tr.append('<td data-target="inurl"><div contenteditable="true" class="keywordInput" data-target="inurl">' + search_preview.parseNumber(keyword.inurl) + '</div></td>');

                                tr.append('<td class="uk-text-center" data-target="tr"><div contenteditable="true" class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Search Volume and InTitle metrics must be retrieved first to see the Title Ratio."></div></td>');
                                tr.append('<td class="uk-text-center" data-target="ur"><div contenteditable="true" class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Search Volume and InURL metrics must be retrieved first to see the URL Ratio."></div></td>');

                                tr.append('<td class="uk-text-center"><span data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Not Added"><span style="display: none;">99999</span></span></td>');

                                groupKeywords.push(tr);
                            }

                            kwData.append(groupKeywords);

                            search_preview.initSorters();

                        } else {

                            suggestion_keywords.append('<td colspan="9" class="uk-text-center" style="padding: 10px 20px;"><i class="far fa-exclamation-circle"></i> No Keyword Suggestions are available for this <b>Target Keyword</b>.</td>');

                        }

                        search_preview.calculations.suggestions.calculateFoundKeywords();
                    });
                }, calculateFoundKeywords: function () {
                    let counter = $('.analysis-suggestions').prev('.xagio-accordion-title').find('.uk-badge');
                    let count = $('.suggestion-keywords').find('tr').length;

                    let text = count + ' Keywords';

                    if (counter.find('span').text() !== text) {
                        counter.html('🗝️ <span>' + text + '</span>');
                    }
                }
            }, shared_functions: {
                countFoundIssues : function (type) {
                    if (!$('.analysis-' + type).is(':visible')) {
                        return;
                    }

                    let counter = $('.analysis-' + type).prev('.xagio-accordion-title').find('.uk-badge');

                    let cText = counter.find('span').text();
                    let nHTML = $('<span class="uk-badge uk-badge-a"></span>');

                    let errors = $('.analysis-' + type).find('span.analysis-error:visible').length;
                    let warnings = $('.analysis-' + type).find('span.analysis-warning:visible').length;
                    let ok = $('.analysis-' + type).find('span.analysis-ok:visible').length;

                    if (errors > 0) {

                        nHTML.addClass('uk-badge-e');
                        nHTML.html('❌ <span>' + errors + ' Issue' + (errors > 1 ? 's' : '') + '</span>');

                    } else if (warnings > 0) {

                        nHTML.addClass('uk-badge-w');
                        nHTML.html('⚠️ <span>' + warnings + ' Warning' + (warnings > 1 ? 's' : '') + '</span>');

                    } else if (ok > 0) {

                        nHTML.addClass('uk-badge-o');
                        nHTML.html('✅ <span>All Good' + '</span>');

                    }

                    if (nHTML.find('span').text() != cText && nHTML.find('span').text() != '') {
                        counter.replaceWith(nHTML);
                    }
                }, getContentRaw : '', getContentText: '', getContentLoop: function () {

                    search_preview.calculations.shared_functions.getContentRaw = search_preview.calculations.shared_functions.getContent('raw');
                    search_preview.calculations.shared_functions.getContentText = search_preview.calculations.shared_functions.getContent('text');

                    setTimeout(function () {

                        search_preview.calculations.shared_functions.getContentLoop();

                    }, 500);
                }, getContent    : function (format) {
                    let html = '';
                    let rex = /(<([^>]+)>)/ig;
                    if (typeof format == 'undefined') {
                        format = 'raw';
                    }
                    try {
                        html = tinyMCE.get('content').getContent({format: format});
                    } catch (error) {
                        var wpeditor = jQuery('#content-textarea-clone');
                        if (wpeditor.length > 0) {
                            if (format == 'html') {
                                html = wpeditor.text().replace(/\[.*?\]/g, "");
                            } else {
                                var content = wpeditor.text();
                                html = content.replace(rex, "").replace(/\[.*?\]/g, "");
                            }
                        } else {
                            html = '';
                        }
                    }
                    if (html == '') {
                        if ($('#cke_content').length > 0) {
                            CKEDITOR.disableAutoInline = true;
                            html = CKEDITOR.instances.content.getData();
                            if (format == 'html') {
                                html = html.replace(/\[.*?\]/g, "");
                            } else {
                                html = html.replace(rex, "").replace(/\[.*?\]/g, "");
                            }
                            return html;
                        }
                    }
                    if (html == '') {
                        if (typeof thriveBody != 'undefined') {
                            if (thriveBody != '') {
                                html = thriveBody;
                                if (format == 'html') {
                                    html = html.replace(/\[.*?\]/g, "");
                                } else {
                                    html = html.replace(rex, "").replace(/\[.*?\]/g, "");
                                }
                            }
                        }
                    }
                    if (html == '') {
                        if (typeof DiviBody != 'undefined') {
                            if (DiviBody != '') {
                                html = DiviBody;
                                if (format == 'html') {
                                    html = html.replace(/\[.*?\]/g, "");
                                } else {
                                    html = html.replace(rex, "").replace(/\[.*?\]/g, "");
                                }
                            }
                        }
                    }
                    if (html == '') {
                        if ($('.mce-content-body').length != 0) {
                            html = $('.mce-content-body').html();
                            if (format == 'html') {
                                html = html.replace(/\[.*?\]/g, "");
                            } else {
                                html = html.replace(rex, "").replace(/\[.*?\]/g, "");
                            }
                        }
                    }
                    if (html == '') {
                        try {
                            if (typeof wp.data != 'undefined' && typeof wp.data.select('core/editor') != 'undefined') {
                                if (wp.data.select("core/editor").getCurrentPost().content.length != 0) {
                                    html = wp.data.select("core/editor").getCurrentPost().content;
                                    if (format == 'html') {
                                        html = html.replace(/\[.*?\]/g, "");
                                    } else {
                                        html = html.replace(rex, "").replace(/\[.*?\]/g, "");
                                    }
                                }
                            }
                        } catch (error) {

                        }
                    }
                    return html;
                }, getTitle      : function () {
                    return $('[name="XAGIO_SEO_TITLE"]').val()
                }, getDescription: function () {
                    return $('[name="XAGIO_SEO_DESCRIPTION"]').val();
                }, getUrl        : function () {
                    return $('[name="XAGIO_SEO_URL"]').val();
                }, getKeyword    : function () {
                    return $('[name="XAGIO_SEO_TARGET_KEYWORD"]').val();
                }, getWordCount  : function (s, b) {
                    s = s.replace(/(^\s*)|(\s*$)/gi, "");//exclude  start and end white-space
                    s = s.replace(/[ ]{2,}/gi, " ");//2 or more space to 1
                    s = s.replace(/\n /, "\n"); // exclude newline with a start spacing
                    if (typeof b == 'undefined') {
                        return s.split(' ').length;
                    } else {
                        return s.split(' ');
                    }
                }
            }
        }, clearTargetKeyword          : function () {
            $(document).on('click', '.clear-target-keyword', function (e) {
                e.preventDefault();
                $('#XAGIO_SEO_TARGET_KEYWORD').val('').trigger('change');
            });
        }, toKeywordGroup              : function () {
            $(document).on('click', '.to-keyword-group', function (e) {
                e.preventDefault();

                if ($('.xagio-detach-group.uk-hidden').length > 0) {
                    notify('error', 'Please attach a group before adding keywords.');
                    return;
                }

                let tr = $(this).parents('tr');
                let trc = tr.clone();
                trc.removeClass();

                tr.remove();
                $('.group-keywords').append(trc);
            });
        }, toTargetKeyword             : function () {
            $(document).on('click', '.to-target-keyword', function (e) {
                e.preventDefault();
                let keyword = $(this).parents('tr').find('td').eq(1).find('div[contenteditable="true"]').text();
                $('#XAGIO_SEO_TARGET_KEYWORD').val(keyword).trigger('change');
                $('html, body').animate({
                                            scrollTop: $('#XAGIO_SEO_TARGET_KEYWORD').offset().top - 100 // Adjust 80 to your header height
                                        }, 1000);
            });
            $(document).on('click', '.analysis-keyword', function (e) {
                $('#XAGIO_SEO_TARGET_KEYWORD').val($(this).text()).trigger('change');
                $('html, body').animate({
                                            scrollTop: $('#XAGIO_SEO_TARGET_KEYWORD').offset().top - 100 // Adjust 80 to your header height
                                        }, 1000);
            });
        }, processChangedTargetKeyword : function () {
            $('#XAGIO_SEO_TARGET_KEYWORD').val(target_keyword).trigger('change');
        }, wordCloudOnClick            : function () {
            let highlighted = [];
            $(document).on('click', '.xagio-word-cloud .jqcloud-word', function () {

                // Vars
                let title = $('#XAGIO_SEO_TITLE');
                let desc = $('#XAGIO_SEO_DESCRIPTION');

                // Query
                let word = $(this).text();

                if ($(this).hasClass('highlightWordInCloud')) {
                    // Un-highlight
                    $(this).removeClass('highlightWordInCloud');

                    // remove from highlighted array
                    highlighted = highlighted.filter(function (item) {
                        return item !== word;
                    });

                    let regex = new RegExp(`<span\\b[^>]*class="highlightCloud">(${word})<\/span>`, 'gi');

                    $('.keywordInput').each(function () {

                        $(this).html($(this).html().replace(regex, '$1'));

                    });

                    title.html(title.html().replace(regex, '$1'));
                    desc.html(desc.html().replace(regex, '$1'));

                } else {
                    // Highlight
                    $(this).addClass('highlightWordInCloud');

                    // add to highlighted array if not already there
                    if (highlighted.indexOf(word) === -1) {
                        highlighted.push(word);
                    }

                    // Highlight keywords
                    $('.keywordInput').each(function () {

                        let matches = $(this).html().match(new RegExp('\\b(' + word + ')\\b', 'gi'));
                        if (matches != null) {
                            for (let j = 0; j < matches.length; j++) {
                                const match = matches[j];
                                $(this).html($(this).html().replace(new RegExp('\\b(' + match + ')\\b', 'g'), "<span class=\"highlightCloud\">" + match + "</span>"));
                            }
                        }

                    });

                    let title_matches = title.html().match(new RegExp('\\b(' + $(this).text() + ')\\b', 'gi'));
                    let desc_matches = desc.html().match(new RegExp('\\b(' + $(this).text() + ')\\b', 'gi'));

                    if (title_matches !== null) {
                        if (title_matches.hasOwnProperty(0)) {
                            title.html(title.html().replace(title_matches[0], '<span class="highlightCloud">' + title_matches[0] + '</span>'));
                        }
                    }

                    if (desc_matches !== null) {
                        if (desc_matches.hasOwnProperty(0)) {
                            desc.html(desc.html().replace(new RegExp('\\b(' + desc_matches[0] + ')\\b', 'g'), '<span class="highlightCloud">' + desc_matches[0] + '</span>'));
                        }
                    }

                }

                if (highlighted.length === 0) {
                    target_keyword = '';
                } else {
                    target_keyword = highlighted.join(' ');
                }
                search_preview.processChangedTargetKeyword();

            });
        }, attachGroupEvents           : function () {

            $(document).on('click', '.xagio-detach-group', function (e) {
                let btn = $(this);
                let parent = btn.parents('.xagio-group-container');
                let group_id = parent.attr('data-group-id');

                btn.disable();

                $.post(xagio_data.wp_post, `action=xagio_detach_from_group&group_id=${group_id}`, function (d) {
                    btn.disable();
                    parent.attr('data-group-id', 0).addClass('uk-hidden');
                    search_preview.generateKeywordsTable();
                    $('.xagio-search-group-input').removeClass('uk-hidden');
                    $('.xagio-word-cloud').empty().removeClass('jqcloud').attr('style', '');
                });

            });

            $(document).on('click', 'li.xagio-group', function (e) {
                e.preventDefault();

                let selection = $(this);

                let group_id = selection.data('group-id');
                let post_id = $('#xagio_post_id').val();
                let h1 = $('input[name="post_title"]').val();
                let url = $('input[name="XAGIO_SEO_URL"]').val();
                let title = $('input[name="XAGIO_SEO_TITLE"]').val();
                let desc = $('input[name="XAGIO_SEO_DESCRIPTION"]').val();

                let relative_url_part = $('input[name="XAGIO_RELATIVE_URL_PART"]').val();

                if (relative_url_part !== "/") {
                    url = relative_url_part + url + "/";
                } else {
                    if (url !== undefined) {
                        url = "/" + url + "/";
                    } else {
                        url = "/";
                    }
                }

                let data = [{
                    name: "action", value: "xagio_attach_to_project_group"
                }, {
                    name: "group_id", value: group_id
                }, {
                    name: "post_id", value: post_id
                }, {
                    name: "h1", value: h1
                }, {
                    name: "url", value: url
                }, {
                    name: "title", value: title
                }, {
                    name: "desc", value: desc
                }];

                $.post(xagio_data.wp_post, data, function (d) {
                    $('.xagio-group-container').attr('data-group-id', group_id).removeClass('uk-hidden');
                    $('.xagio-search-group-input').addClass('uk-hidden');
                    search_preview.generateKeywordsTable();
                });

            });

            let typingTimer = null;
            $(document).on('keyup', '.searchProjectGroups', function (e) {
                let search = $(this).val();
                if (search.length >= 3) {

                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(function () {
                        // Display loading
                        let results_holder = $('.xagio-group-search-results');
                        results_holder.html('<div class="xagio-search-info">Searching for groups <i class="fa-light fa-sync fa-spin"></i></div>');

                        // Call ajax for search results
                        $.post(xagio_data.wp_post, `action=xagio_searchGroups&search=${search}`, function (d) {
                            let data = d.data;

                            if (data.length < 1) {
                                // No groups found
                                results_holder.html('<div class="xagio-search-info">Group not found <i class="xagio-icon xagio-icon-info"></i></div>');
                            } else {
                                let list = '<ul>';

                                let results = data.reduce(function (r, a) {
                                    r[a.project_name] = r[a.project_name] || [];
                                    r[a.project_name].push(a);
                                    return r;
                                }, Object.create(null));

                                for (const project in results) {
                                    list += `<li class="xagio-project">${project}</li>`;

                                    for (let i = 0; i < results[project].length; i++) {
                                        let row = results[project][i];
                                        list += `<li class="xagio-group" data-project-id="${row['project_id']}" data-group-id="${row['id']}"><i class="far fa-clock"></i> ${row['group_name']}</li>`;
                                    }
                                }

                                list += '</ul>'

                                results_holder.html(list);
                            }
                        });

                    }, 500);

                }
            });

            $(document).on('click', '.searchProjectGroups, .xagio-group-search-results', function (e) {
                e.stopPropagation();

                $('.xagio-group-search-results').removeClass('uk-hidden');
                $('.xagio-search-group-input').addClass('xagio-opened');
            });

            $(window).click(function () {
                $('.xagio-group-search-results').addClass('uk-hidden');
                $('.xagio-search-group-input').removeClass('xagio-opened');
            });
        }, editGroupEvents             : function () {
            $(document).on('click', '.xagio-edit-group', function (e) {
                e.preventDefault();

                let group_id = $(this).parents('.xagio-group-container').attr('data-group-id');

                window.open('/wp-admin/admin.php?page=xagio-projectplanner&group_id=' + group_id, '_blank');
            });
        }, saveGroupEvents             : function () {
            $(document).on('click', '.xagio-save-keywords', function (e) {
                e.preventDefault();

                let btn = $(this);
                let group_id = $(this).parents('.xagio-group-container').attr('data-group-id');

                let keywords = [];
                let position = 1;
                $('.group-keywords').find('tr').each(function () {
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

                let data = [{
                    name: 'action', value: 'xagio_updateKeywords'
                }, {
                    name: 'group_id', value: group_id
                }, {
                    name: 'keywords', value: encodeURIComponent(JSON.stringify(keywords))
                }];

                btn.disable();

                $.post(xagio_data.wp_post, data, function (d) {
                    // Give user a sense of success
                    setTimeout(function () {
                        btn.disable();
                        notify('success', 'Changes saved successfully.');
                    }, 1500);
                });

            });
        }, generateKeywordsTable       : function () {
            let kwData = $('#xagio_seo .group-keywords');
            let wdCloud = $('#xagio_seo .xagio-word-cloud');
            let group_id = $('.xagio-group-container').attr('data-group-id');
            let wordcloud_keywords = [];

            if (group_id != 0) {

                $.post(xagio_data.wp_post, `action=xagio_getAttachedGroup&group_id=${group_id}`, function (raw_keywords) {

                    if (raw_keywords.length < 1) {
                        kwData.html(`<td colspan="9" class="uk-text-center"><i class="xagio-icon xagio-icon-info"></i> Attached group does not have any keywords.</td>`);
                        wdCloud.addClass('no-keywords');
                    } else {
                        kwData.empty();
                        wdCloud.removeClass('no-keywords');

                        let groupKeywords = [];

                        for (let k = 0; k < raw_keywords.length; k++) {
                            let keyword = raw_keywords[k];

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

                            // add to wordcloud
                            wordcloud_keywords.push(keyword.keyword);

                            /**
                             *
                             *     CONDITIONAL FORMATTING
                             *
                             */

                            let volume_color, cpc_color, intitle_color, inurl_color, tr_color, ur_color;

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

                            if (keyword.volume == "") {
                                volume_color = '';
                            } else if (parseFloat(search_preview.cf_template.volume_red) >= parseFloat(keyword.volume)) {
                                volume_color = 'tr_red';
                            } else if (parseFloat(search_preview.cf_template.volume_red) < parseFloat(keyword.volume) && parseFloat(search_preview.cf_template.volume_green) > parseFloat(keyword.volume)) {
                                volume_color = 'tr_yellow';
                            } else if (parseFloat(search_preview.cf_template.volume_green) <= parseFloat(keyword.volume)) {
                                volume_color = 'tr_green';
                            }

                            if (keyword.cpc == "") {
                                cpc_color = '';
                            } else if (parseFloat(search_preview.cf_template.cpc_red) >= parseFloat(keyword.cpc)) {
                                cpc_color = 'tr_red';
                            } else if (parseFloat(search_preview.cf_template.cpc_red) < parseFloat(keyword.cpc) && parseFloat(search_preview.cf_template.cpc_green) > parseFloat(keyword.cpc)) {
                                cpc_color = 'tr_yellow';
                            } else if (parseFloat(search_preview.cf_template.cpc_green) <= parseFloat(keyword.cpc)) {
                                cpc_color = 'tr_green';
                            }

                            if (keyword.intitle == "") {
                                intitle_color = '';
                            } else if (parseFloat(search_preview.cf_template.intitle_red) <= parseFloat(keyword.intitle)) {
                                intitle_color = 'tr_red';
                            } else if (parseFloat(search_preview.cf_template.intitle_red) > parseFloat(keyword.intitle) && parseFloat(search_preview.cf_template.intitle_green) < parseFloat(keyword.intitle)) {
                                intitle_color = 'tr_yellow';
                            } else if (parseFloat(search_preview.cf_template.intitle_green) >= parseFloat(keyword.intitle)) {
                                intitle_color = 'tr_green';
                            }

                            if (keyword.inurl == "") {
                                inurl_color = '';
                            } else if (parseFloat(search_preview.cf_template.inurl_red) <= parseFloat(keyword.inurl)) {
                                inurl_color = 'tr_red';
                            } else if (parseFloat(search_preview.cf_template.inurl_red) > parseFloat(keyword.inurl) && parseFloat(search_preview.cf_template.inurl_green) < parseFloat(keyword.inurl)) {
                                inurl_color = 'tr_yellow';
                            } else if (parseFloat(search_preview.cf_template.inurl_green) >= parseFloat(keyword.inurl)) {
                                inurl_color = 'tr_green';
                            }

                            if (title_ratio == "") {
                                tr_color = '';
                            } else if (parseFloat(title_ratio) >= parseFloat(search_preview.cf_template.title_ratio_red)) {
                                tr_color = 'tr_red';
                            } else if (parseFloat(title_ratio) < parseFloat(search_preview.cf_template.title_ratio_red) && parseFloat(title_ratio) > parseFloat(search_preview.cf_template.title_ratio_green)) {
                                tr_color = 'tr_yellow';
                            } else if (parseFloat(title_ratio) <= parseFloat(search_preview.cf_template.title_ratio_green)) {
                                tr_color = 'tr_green';
                            }

                            if (url_ratio == "") {
                                ur_color = '';
                            } else if (parseFloat(url_ratio) >= parseFloat(search_preview.cf_template.url_ratio_red)) {
                                ur_color = 'tr_red';
                            } else if (parseFloat(url_ratio) < parseFloat(search_preview.cf_template.url_ratio_red) && parseFloat(url_ratio) > parseFloat(search_preview.cf_template.url_ratio_green)) {
                                ur_color = 'tr_yellow';
                            } else if (parseFloat(url_ratio) <= parseFloat(search_preview.cf_template.url_ratio_green)) {
                                ur_color = 'tr_green';
                            }

                            /**
                             *
                             *     CONDITIONAL FORMATTING
                             *
                             */


                            let tr = $('<tr data-id="' + keyword.id + '"></tr>');
                            tr.append('<td><button data-xagio-tooltip data-xagio-title="Set as Target Keyword." class="to-target-keyword" type="button"><i class="far fa-caret-up"></i></button></td>');
                            tr.append('<td><div contenteditable="true" class="keywordInput" data-target="keyword">' + keyword.keyword + '</div></td>');

                            if (keyword.queued == 2) {
                                tr.append('<td data-target="volume" class="uk-text-center" title="This value is currently under analysis. Please check back later to see the results."><i class="fal fa-sync fa-spin"></i></td>');
                                tr.append('<td data-target="cpc" class="uk-text-center" title="This value is currently under analysis. Please check back later to see the results."><i class="fal fa-sync fa-spin"></i></td>');
                            } else {
                                tr.append('<td class="' + volume_color + '"><div contenteditable="true" class="keywordInput" data-target="volume">' + search_preview.parseNumber(keyword.volume) + '</div></td>');
                                tr.append('<td class="' + cpc_color + '"><div contenteditable="true" class="keywordInput" data-target="cpc">' + keyword.cpc + '</div></td>');
                            }

                            if (keyword.queued == 1 || alsoQueued == true) {

                                actions.runBatchCron();

                                tr.append('<td data-target="intitle" class="uk-text-center" title="This value is currently under analysis. Please check back later to see the results."><i class="fal fa-sync fa-spin"></i></td>');
                                tr.append('<td data-target="inurl" class="uk-text-center" title="This value is currently under analysis. Please check back later to see the results."><i class="fal fa-sync fa-spin"></i></td>');
                            } else {

                                tr.append('<td data-target="intitle" class="' + intitle_color + '"><div contenteditable="true" class="keywordInput" data-target="intitle">' + search_preview.parseNumber(keyword.intitle) + '</div></td>');
                                tr.append('<td data-target="inurl" class="' + inurl_color + '"><div contenteditable="true" class="keywordInput" data-target="inurl">' + search_preview.parseNumber(keyword.inurl) + '</div></td>');
                            }

                            if (title_ratio != "") {
                                if (tr_color == "tr_green" && (parseFloat(search_preview.cf_template.tr_goldbar_volume) >= parseFloat(keyword.volume) && parseFloat(search_preview.cf_template.tr_goldbar_intitle) >= parseFloat(keyword.intitle))) {
                                    tr.append('<td class="uk-text-center ' + tr_color + '" data-target="tr"><div contenteditable="false" class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Value: ' + parseFloat(title_ratio).toFixed(3) + '"><img src="' + xagio_data.plugins_url + 'assets/img/gold.webp"></div></td>');
                                } else {
                                    tr.append('<td class="uk-text-center ' + tr_color + '" data-target="tr"><div contenteditable="true" class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Value: ' + parseFloat(title_ratio).toFixed(3) + '">' + parseFloat(title_ratio).toFixed(3) + '</div></td>');
                                }
                            } else {
                                tr.append('<td class="uk-text-center ' + tr_color + '" data-target="tr"><div contenteditable="true" class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Search Volume and InTitle metrics must be retrieved first to see the Title Ratio."></div></td>');
                            }

                            if (url_ratio != "") {
                                if (ur_color == "tr_green" && (parseFloat(search_preview.cf_template.ur_goldbar_volume) >= parseFloat(keyword.volume) && parseFloat(search_preview.cf_template.ur_goldbar_intitle) >= parseFloat(keyword.inurl))) {
                                    tr.append('<td class="uk-text-center ' + ur_color + '" data-target="ur"><div contenteditable="false" class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Value: ' + parseFloat(url_ratio).toFixed(3) + '"><img src="' + xagio_data.plugins_url + 'assets/img/gold.webp"></div></td>');
                                } else {
                                    tr.append('<td class="uk-text-center ' + ur_color + '" data-target="ur"><div contenteditable="true" class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Value: ' + parseFloat(url_ratio).toFixed(3) + '">' + parseFloat(url_ratio).toFixed(3) + '</div></td>');
                                }
                            } else {
                                tr.append('<td class="uk-text-center ' + ur_color + '" data-target="ur"><div contenteditable="true" class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Search Volume and InURL metrics must be retrieved first to see the URL Ratio."></div></td>');
                            }

                            let rank = keyword.rank.isJSON();
                            let rank_cell = '';

                            if (rank == 0) {
                                rank_cell = '<span data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Not Added"><span style="display: none;">99999</span></span>';
                            } else if (rank == 501) {
                                rank_cell = '<span data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Analysing..."><i class="fal fa-sync fa-spin"></i><span style="display: none;">99998</span></span>';
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
                                            obj.rank = "<i class='fal fa-ban'></i>";
                                        }
                                        rank_title += obj.engine + ' : ' + obj.rank + '<br>';
                                    } else {
                                        rank_title += obj.engine + ' : <i class=\'fal fa-ban\'></i><br>';
                                    }

                                }

                                if (max == 501) {
                                    rank_cell = '<a href="https://app.xagio.net/rank_tracker?domain=' + domain + '&keyword=' + encodeURIComponent(keyword.keyword) + '" target="_blank" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="' + rank_title + '"><i class=\'fal fa-ban\'></i><span style="display: none;">99997</span></a>';
                                } else {
                                    if ($.isNumeric(rank)) {
                                        rank_cell = max;
                                    } else {
                                        rank_cell = '<a href="https://app.xagio.net/rank_tracker?domain=' + domain + '&keyword=' + encodeURIComponent(keyword.keyword) + '" target="_blank" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="' + rank_title + '">' + max + '</a>';
                                    }
                                }

                            }

                            tr.append('<td class="uk-text-center">' + rank_cell + '</td>');

                            groupKeywords.push(tr);
                        }

                        kwData.append(groupKeywords);

                        search_preview.initSorters();

                        search_preview.generateWordCloud(wordcloud_keywords);
                    }
                });

            } else {

                $('.group-keywords .keywordInput').unhighlight();
                $('.suggestion-keywords .keywordInput').unhighlight();
                $('#XAGIO_SEO_TITLE').unhighlight();
                $('#XAGIO_SEO_DESCRIPTION').unhighlight();

                kwData.html('<td colspan="9" class="uk-text-center"><i class="xagio-icon xagio-icon-info"></i> No group has been attached to this page.</td>');
                $('.searchProjectGroups').val('');
            }
        }, updateTableSorters          : function () {
            // Table sorting
            $(".keywords").tablesorter({
                                           headers: {
                                               0   : {
                                                   sorter: false
                                               }, 2: {
                                                   sorter: 'fancyNumber'
                                               }, 3: {
                                                   sorter: 'fancyNumber'
                                               }, 4: {
                                                   sorter: 'fancyNumber'
                                               }, 5: {
                                                   sorter: 'fancyNumber'
                                               }, 6: {
                                                   sorter: 'fancyNumber'
                                               }, 7: {
                                                   sorter: 'fancyNumber'
                                               }, 8: {
                                                   sorter: 'fancyNumber'
                                               }
                                           }
                                       }).trigger('updateAll');
        }, initSorters                 : function () {

            search_preview.updateTableSorters();

            let sugge_kws = $('.suggestion-keywords');
            let group_kws = $('.group-keywords');

            sugge_kws.multisortable({
                                        items: "tr", selectedClass: "selected"
                                    });

            group_kws.multisortable({
                                        items: "tr", selectedClass: "selected"
                                    });

            sugge_kws.sortable({
                                   connectWith: ".uk-sortable",
                                   cancel     : "input,textarea,button,select,option,[contenteditable]"
                               })

            group_kws.sortable({
                                   cancel: "input,textarea,button,select,option,[contenteditable]"
                               }).on("sortreceive", function (event, ui) {

                if ($('.xagio-detach-group.uk-hidden').length > 0) {
                    notify('error', 'Please attach a group before adding keywords.');
                    ui.sender.sortable("cancel");
                } else {
                    search_preview.updateTableSorters();
                }

            });
        }, generateWordCloud           : function (keywords) {
            let cloudBoxTemplate = $('.xagio-word-cloud');
            cloudBoxTemplate.jQCloud(search_preview.calculateAndTrim(keywords), {
                colors    : ["#13bfff", "#26c5ff", "#3acaff", "#4ecfff", "#61d4ff", "#75daff", "#89dfff", "#9ce4ff", "#b0eaff", "#c3efff", "#d7f4ff", "#ebfaff", "#feffff"],
                autoResize: true,
                height    : '250',
                fontSize  : {
                    from: 0.08, to: 0.02
                },
            });
        }, parseNumber                 : function (num) {
            if (num == null || num == "") {
                return '';
            } else {
                return parseInt(num).toLocaleString();
            }
        },
        titleDescriptionCalculation : function () {
            search_preview.calculateProgressTitle();
            search_preview.calculateProgressDescription();
            search_preview.calculateTitleLength();
            search_preview.calculateDescriptionLength();
        },
        calculateTitleLength        : function () {
            $(document).on('keydown paste input', '#XAGIO_SEO_TITLE', function () {
                search_preview.calculateProgressTitle();
            });
        },
        calculateDescriptionLength  : function () {
            $(document).on('keydown paste input', '#XAGIO_SEO_DESCRIPTION', function () {
                search_preview.calculateProgressDescription();
            });
        },
        calculateProgressTitle      : function () {
            let title_check = $('.xagio-title-length');
            let title_check_line = $('.title-check-circle');
            let wordCount = search_preview.getTitle();
            let p = (wordCount / 61) * 100;
            if (wordCount >= 15 && wordCount <= 60) {
                // Green
                title_check_line.css('--xagio-grad-color', '#00BF63');
                title_check.css('--xagio-grad-color', '#00BF63');
            } else {
                title_check_line.css('--xagio-grad-color', '#FFB000');
                title_check.css('--xagio-grad-color', '#FFB000');
                // Warning
            }
            search_preview.animationLengthCheck(title_check.find('.inside-check-circle'));
            if (wordCount < 1) {
                title_check.find('.inside-check-circle').html('0/60');
            } else if (wordCount > 999) {
                title_check.find('.inside-check-circle').html('>1k/60');
            } else {
                title_check.find('.inside-check-circle').html(`${wordCount}/60`);
            }

            if (p <= 100) {
                title_check_line.css('--xagio-grad-fill', p + '%');
            }
        },
        calculateProgressDescription: function () {
            let desc_check = $('.xagio-desc-length');
            let desc_check_line = $('.desc-check-circle');

            let wordCount = search_preview.getDescription();
            let p = (wordCount / 160) * 100;
            if (wordCount >= 40 && wordCount <= 160) {
                // Green
                desc_check_line.css('--xagio-grad-color', '#00BF63');
                desc_check.css('--xagio-grad-color', '#00BF63');
            } else {
                // Warning
                desc_check_line.css('--xagio-grad-color', '#FFB000');
                desc_check.css('--xagio-grad-color', '#FFB000');
            }

            search_preview.animationLengthCheck(desc_check.find('.inside-check-circle'));

            if (wordCount < 1) {
                desc_check.find('.inside-check-circle').html('0/160');
            } else if (wordCount > 999) {
                desc_check.find('.inside-check-circle').html('>1k/160');
            } else {
                desc_check.find('.inside-check-circle').html(`${wordCount}/160`);
            }

            if (p <= 100) {
                desc_check_line.css('--xagio-grad-fill', p + '%');
            }
        },
        animationLengthCheck        : function (el) {
            el.css('animation', '120ms circle_update ease-in forwards');
            setTimeout(function () {
                el.css('animation', 'none');
            }, 120);
        },
        calculateAndTrim            : function (t) {
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
                    weight: 0,
                    html  : {
                        'data-xagio-title'  : 0,
                        'data-xagio-tooltip': ''
                    }
                };
                for (let j = 0; j < words.length; j++) {
                    if (words_split[i] == words[j].text) {
                        check = 1;
                        ++words[j].weight;
                        ++words[j].html['data-xagio-title'];
                    }
                }
                if (check == 0) {
                    final.text = words_split[i];
                    final.weight = 1;
                    final.html['data-xagio-title'] = 1;
                    words.push(final);
                }
                check = 0;
            }

            return words;
        },
        isGuteberg                  : function () {
            return typeof wp !== 'undefined' && typeof wp.blocks !== 'undefined' &&
                   $('.edit-post-visual-editor').length > 0;
        },
        getH1                       : function () {
            let title = '';
            if ($('.editor-post-title').length > 0) {
                title = $('.editor-post-title').text().trim();
            } else if ($('#title').length > 0) {
                title = $('#title').val().trim();
            } else if ($('#post-title-0').length > 0) {
                title = $('#post-title-0').val().trim();
            } else {
                title = $('#prs-title').val().trim();
            }
            return title.toLowerCase();
        },
        getTitle                    : () => {
            return $('#XAGIO_SEO_TITLE').html().replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
        },
        getDescription              : () => {
            return $('#XAGIO_SEO_DESCRIPTION').html().replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
        }, editorInit                  : function () {

            $('body').on('focus', '.xagio-editor[contenteditable="true"]', function () {
                let $this = $(this);
                $this.data('before', $this.html());
                return $this;
            }).on('blur keyup input', '.xagio-editor[contenteditable="true"]', function () {
                let $this = $(this);
                if ($this.data('before') !== $this.html()) {
                    $this.data('before', $this.html());
                    $this.trigger('change');
                }
                return $this;
            }).on('paste', '.xagio-editor[contenteditable="true"]', function (e) {
                e.preventDefault();
                // get text representation of clipboard
                let text = (e.originalEvent || e).clipboardData.getData('text/plain');
                // insert text manually
                $(this).html($(this).html() + text).trigger('change');
            });
            $('.xagio-editor').change(function (e) {
                e.stopPropagation();
                let text = $(this).text();
                let id = $(this).data('target');
                text = text.replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().replace(/<\/?[^>]+(>|$)/g, "").trim();
                $('input#' + id).val(text).trigger('change');
            }).keydown(function (e) {
                e.stopPropagation();
            }).keyup(function (e) {
                e.stopPropagation();
            }).keypress(function (e) {
                e.stopPropagation();
            });

        },
    };

    let actions = {
        init                   : function () {
            actions.removeDragHandler();
            actions.switchTabOptions();
            actions.initSliders();
            actions.accordion();
            actions.initYoutubeSearch();
            actions.initPixabaySearch();
        }, accordion           : function () {
            $('.xagio-accordion-title').on('click', function (e) {
                // if event target is not the title, return
                if ($(e.target).is('button') || $(e.target).parents('button').length) {
                    return;
                }
                $(this).find('.far').toggleClass('fa-caret-down fa-caret-up');
                $(this).next().fadeToggle(function () {
                    scripts.refreshEditors();
                });
            });
        }, allowedToRun        : function () {
            return $('#xagio_seo').length > 0;
        }, initSliders         : function () {

            $('.prs-slider-frame .slider-button').click(function () {

                let parent = $(this).parents('.slider-container');
                let target = $(this).attr('data-element');

                let element = parent.find('#' + target);
                if (element.length < 1) {
                    element = parent.find('.' + target);
                }

                if ($(this).hasClass('on')) {
                    $(this).removeClass('on').html('Off');
                    element.val(0);
                } else {
                    $(this).addClass('on').html('On');
                    element.val(1);
                }

                $(`.${target}`).removeClass('on').removeClass('off').addClass(parseInt(element.val()) ? 'on' : 'off');
            });
        }, switchTabOptions    : function () {
            $(document).on('click', '.xagio-g-tabs li', function (e) {
                e.preventDefault();
                $('.additional-options').hide();

                let thisTab = $(this).text().trim();
                // remove all characters except letters spaces and then convert spaces to dashes
                let thisTabId = thisTab.replace(/[^a-zA-Z ]/g, "").replace(/\s+/g, '-').toLowerCase();
                $('.additional-options.' + thisTabId).show();
            });
            $(document).on('animationend', '#seo-sections > div', function (e) {
                scripts.refreshEditors();
            });
        }, initPixabaySearch   : function () {
            let time = null;
            $(document).on('keydown', '#xagio_pixabay_query', function (e) {
                clearTimeout(time);
                time = setTimeout(function () {
                    $('#xagio_pixabay_search').trigger('click');
                }, 500);
            });
            $(document).on('click', '.xagio_pixabay_insert', function () {
                let image = $('.xagio_pixabay_image_selected').attr('src');
                let title = $('#xagio_pixabay_image_title').val();
                let alt = $('#xagio_pixabay_image_alt').val();

                let lat = $('#xagio_pixabay_exif_latitude').val();
                let lon = $('#xagio_pixabay_exif_longtitude').val();
                let desc = $('#xagio_pixabay_exif_description').val();

                if (title == '' || alt == '') {
                    xagioNotify("danger", "Please set up Image Title and Alt before proceeding.");
                    return false;
                }

                if (lat != '' || lon != '' || desc != '') {
                    if (lat == '' || lon == '' || desc == '') {
                        xagioNotify("danger", "When using EXIF for images, you must set all the appropriate fields in order to make it work.");
                        return false;
                    }
                }

                let button = $(this);
                button.disable('Downloading...');

                let data = [{
                    name: 'action', value: 'xagio_pixabay_download'
                }, {
                    name: 'img', value: image
                }, {
                    name: 'title', value: title
                }, {
                    name: 'alt', value: alt
                }, {
                    name: 'lat', value: lat
                }, {
                    name: 'lon', value: lon
                }, {
                    name: 'desc', value: desc
                }];

                $.post(xagio_data.wp_post, data, function (d) {

                    button.disable();

                    if (d.status == 'success') {

                        let image_path = xagio_data.uploads_dir.baseurl + '/' + d.data.file;
                        let image = '<img class="alignnone size-medium wp-image-' + d.id + '" title="' + title + '" alt="' + alt + '" src="' + image_path + '"/>';

                        tinyMCE.activeEditor.execCommand('mceInsertContent', false, image);

                        modal.hide();

                    } else {
                        xagioNotify("danger", d.message);
                    }

                });

            });
            $(document).on('click', '.exif-info-pixa', function () {

                setTimeout(function () {

                    mapboxgl.accessToken = 'pk.eyJ1IjoieGFnaW8iLCJhIjoiY2t4cHhteWJyMGlkNjMycDR4am83aTA5byJ9.IDQgx4L8IhKfqQHAli3nHg';

                    let center = [-100.72265625, 40.04443758460856];
                    let zoom = 3;

                    let map = new mapboxgl.Map({
                                                   container: 'xagio_pixabay_map',
                                                   style    : 'mapbox://styles/mapbox/streets-v11',
                                                   center   : center,
                                                   zoom     : zoom
                                               });

                    map.on('click', (e) => {
                        $('#xagio_pixabay_exif_latitude').val(e.lngLat.lat);
                        $('#xagio_pixabay_exif_longtitude').val(e.lngLat.lng);
                    });

                    let geo = new MapboxGeocoder({
                                                     accessToken: mapboxgl.accessToken, mapboxgl: mapboxgl
                                                 });

                    geo.on('result', (e) => {
                        $('#xagio_pixabay_exif_latitude').val(e.result.center[1]);
                        $('#xagio_pixabay_exif_longtitude').val(e.result.center[0]);
                    });

                    map.addControl(geo);

                    $('#xagio_pixabay_map').css('height', '200px');

                }, 501);

            });
            $(document).on('click', '.xagio_pixabay_back', function () {
                $('.xagio_pixabay_search_area').show();
                $('.xagio_pixabay_image_area').hide();
                $('.xagio_pixabay_insert').hide();
                $(this).parents('.uk-modal-dialog').addClass('uk-modal-dialog-large');
            });
            $(document).on('click', '.pixabay-image', function () {

                $('.xagio_pixabay_search_area').hide();
                $('.xagio_pixabay_insert').show();
                $('.xagio_pixabay_image_area').show();
                $(this).parents('.uk-modal-dialog').removeClass('uk-modal-dialog-large');

                let url = $(this).data('url');
                $('.xagio_pixabay_image_selected').attr('src', url);
            });
            $(document).on('click', '#xagio_pixabay_search', function () {
                let messages = {
                    emptyQuery: '<span class="xagio_pixabay_results_msg"><i class="fal fa-exclamation-triangle"></i> No images found for your search query.</span>',
                    noResults : '<span class="xagio_pixabay_results_msg"><i class="xagio-icon xagio-icon-info"></i> No images found for your search query.</span>'
                };
                let results = $('.xagio_pixabay_results');
                let query = $('#xagio_pixabay_query').val();

                if (query == '') {
                    results.empty().append(messages.emptyQuery);
                    results.removeClass('xagio_pixabay_results_columns')
                    return;
                }

                $.ajax({

                           url     : 'https://pixabay.com/api/?key=25026237-b49b785012e885e4aabca4dba&q=' + query + '&image_type=photo&pretty=true&per_page=200',
                           dataType: 'jsonp',
                           success : function (d) {

                               if (d.hits.length == 0) {
                                   results.empty().append(messages.noResults);
                                   results.removeClass('xagio_pixabay_results_columns')
                               } else {
                                   results.addClass('xagio_pixabay_results_columns');
                                   results.empty();
                                   for (let i = 0; i < d.hits.length; i++) {
                                       let img = d.hits[i];
                                       let html = '<div class="pixabay-image" data-url="' + img.webformatURL + '"><img src="' + img.previewURL + '"/><div class="pixabay-size">' + img.webformatWidth + 'x' + img.webformatHeight + '</div></div>';
                                       results.append(html);
                                   }
                                   results.append('<div class="pixabar-clear"></div>');
                               }

                           },
                           error   : function () {
                               results.empty().append(messages.noResults);
                           }
                       });

            });
        }, initYoutubeSearch   : function () {
            let time = null;
            $(document).on('keydown', '#xagio_youtube_query', function (e) {
                clearTimeout(time);
                time = setTimeout(function () {
                    $('#xagio_youtube_search').trigger('click');
                }, 500);
            });
            $(document).on('click', '.xagio_youtube_insert', function () {
                let id = $('#xagio_youtube_id').val();
                let autoplay = $('#xagio_youtube_autoplay').val();
                let strip = $('#xagio_youtube_autoplay').val();

                let width = $('#xagio_youtube_width').val();
                let height = $('#xagio_youtube_height').val();

                let args = (autoplay == 1 || strip == 1) ? '?' : '';
                if (autoplay == 1) {
                    args += 'autoplay=1';
                }
                if (strip == 1) {
                    if (args == '') {
                        args += 'showinfo=0&controls=0';
                    } else {
                        args += '&showinfo=0&controls=0';
                    }
                }
                let iframe = '<iframe width="' + width + '" height="' + height + '" src="https://www.youtube.com/embed/' + id + args + '" frameborder="0" allowfullscreen></iframe>';

                tinyMCE.activeEditor.execCommand('mceInsertContent', false, iframe);

                $('.xagio_youtube_search').show();
                $('.xagio_youtube_video').hide();
                $('.xagio_youtube_insert').hide();

                modal.hide();
            });
            $(document).on('click', '.xagio_youtube_back', function () {
                $('.xagio_youtube_search').show();
                $('.xagio_youtube_video').hide();
                $('.xagio_youtube_insert').hide();
            });
            $(document).on('click', '.yt-video-container h3, .yt-video-container img', function () {
                $('.xagio_youtube_search').hide();
                $('.xagio_youtube_video').show();
                $('.xagio_youtube_insert').show();

                let parent = $(this).parents('.yt-video-container');
                let id = parent.data('id');
                let title = parent.find('h3').text().trim();

                $('.xagio_youtube_preview').empty().append('<iframe width="100%" height="350" src="https://www.youtube.com/embed/' + id + '" frameborder="0" allowfullscreen></iframe>');
                $('#xagio_youtube_title').val(title);
                $('#xagio_youtube_url').val('https://www.youtube.com/embed/' + id);
                $('#xagio_youtube_id').val(id);

            });

            $(document).on('click', '.xagio_youtube_next', function (e) {
                e.preventDefault();
                let value = $('#xagio_youtube_next_page').val();
                if (value == '') return false;
                $('#xagio_youtube_curr_page').val(value);
                actions.performYoutubeSearch();
            });

            $(document).on('click', '.xagio_youtube_prev', function (e) {
                e.preventDefault();
                let value = $('#xagio_youtube_prev_page').val();
                if (value == '') return false;
                $('#xagio_youtube_curr_page').val(value);
                actions.performYoutubeSearch();
            });

            $(document).on('click', '#xagio_youtube_search', function (e) {
                e.preventDefault();
                $('#xagio_youtube_curr_page').val('');
                $('#xagio_youtube_next_page').val('');
                $('#xagio_youtube_prev_page').val('');
                actions.performYoutubeSearch();
            });
        }, performYoutubeSearch: function () {
            let messages = {
                emptyQuery: '<span class="xagio_youtube_results_msg"><i class="fal fa-exclamation-triangle"></i> No videos found for your search query.</span>',
                noResults : '<span class="xagio_youtube_results_msg"><i class="xagio-icon xagio-icon-info"></i> No videos found for your search query.</span>'
            };
            let results = $('.xagio_youtube_results');
            let query = $('#xagio_youtube_query').val();
            let page = $('#xagio_youtube_curr_page').val();

            if (query == '') {
                results.empty().append(messages.emptyQuery);
                return;
            }

            $.ajax({

                       url     : 'https://www.googleapis.com/youtube/v3/search?part=snippet&q=' + query + '&pageToken=' + page + '&maxResults=5&order=viewCount&type=video&key=AIzaSyCDkcVzELYEXJ8utxCnHyyx8r5LTadbbdg',
                       dataType: 'jsonp',
                       success : function (d) {

                           results.empty();

                           $('#xagio_youtube_next_page').val(d.nextPageToken);
                           if (d.hasOwnProperty('prevPageToken')) {
                               $('#xagio_youtube_prev_page').val(d.prevPageToken);
                           } else {
                               $('#xagio_youtube_prev_page').val('');
                           }

                           for (let i = 0; i < d.items.length; i++) {
                               let video = '';
                               let data = d.items[i];

                               let image = data.snippet.thumbnails.medium.url;
                               let title = data.snippet.title;
                               if (title.length > 47) {
                                   title = title.substring(0, 47) + '...';
                               }
                               let from = data.snippet.channelTitle;
                               let desc = data.snippet.description;
                               if (desc.length > 128) {
                                   desc = desc.substring(0, 128) + '...';
                               }
                               let id = data.id.videoId;

                               video += '<div data-id="' + id + '" class="yt-video-container">' + '<div class="yt-image">' + '<img src="' + image + '"/>' + '</div>' + '<div class="yt-meta">' + '<h3>' + title + '</h3>' + '<span>' + from + '</span>' + '<p>' + desc + '</p>' + '</div>' + '</div>';

                               results.append(video);
                           }

                           if (d.items.length == 0) {
                               results.append(messages.noResults);
                           } else {
                               $('.xagio_youtube_pagination').show();
                           }

                       },
                       error   : function () {
                           results.empty().append(messages.noResults);
                       }
                   });
        }, removeDragHandler   : function () {
            $('#xagio_ai .hndle').removeClass();
        }
    }

})(jQuery);