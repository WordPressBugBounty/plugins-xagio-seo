let siloLeave   = null;
let siloMenus   = [];
let siloUsedIDs = [];



(function ($) {
    'use strict';

    let extensions = {
        "sFilterInput": "xagio-input-text-mini",
    }
    // Used when bJQueryUI is false
    $.extend($.fn.dataTableExt.oStdClasses, extensions);
    // Used when bJQueryUI is true
    $.extend($.fn.dataTableExt.oJUIClasses, extensions);

    $(document).ready(function () {

        actions.getSiloTabs(actions.initSilo);
        actions.removeSilo();
        actions.saveSilo();
        actions.generateSilo();
        actions.siloAddPostPage();
        actions.trashPagePost();
        actions.trashTagCategory();
        actions.hardReset();
        actions.hideLinks();
        actions.hideAllLinks();
        actions.settingsMenu();
        actions.scrollCatsTags();
        actions.keySave();
        actions.newCategoryTag();
        actions.generateSiloLinks();
        actions.generateSiloLinksByID();
        actions.permalinkSettings();
        actions.saveButtonChangeName();
        actions.selectChildOperators();
        actions.seoCalcEvents();

    });

    let actions = {

        seoCalcEvents: function () {

            let events = {

                get_title: function () {
                    return $('.operator_title').val().replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
                },
                get_desc : function () {
                    return $('.operator_description').val().replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
                },

                xagio_ca_calculate_title_length             : function () {
                    var wordCount = events.get_title();
                    if (wordCount > 70) {
                        wordCount = '<span style="color:red">' + wordCount + '</span>';
                    }
                    $('.count-seo-title').html(wordCount);
                },
                xagio_ca_calculate_title_length_mobile      : function () {
                    var wordCount = events.get_title();
                    if (wordCount > 78) {
                        wordCount = '<span style="color:red">' + wordCount + '</span>';
                    }
                    $('.count-seo-title-mobile').html(wordCount);
                },
                xagio_ca_calculate_description_length       : function () {
                    var wordCount = events.get_desc();
                    if (wordCount > 300) {
                        wordCount = '<span style="color:red">' + wordCount + '</span>';
                    }
                    $('.count-seo-description').html(wordCount);
                },
                xagio_ca_calculate_description_length_mobile: function () {
                    var wordCount = events.get_desc();
                    if (wordCount > 120) {
                        wordCount = '<span style="color:red">' + wordCount + '</span>';
                    }
                    $('.count-seo-description-mobile').html(wordCount);
                },
            };

            events.xagio_ca_calculate_title_length();
            events.xagio_ca_calculate_title_length_mobile();

            events.xagio_ca_calculate_description_length();
            events.xagio_ca_calculate_description_length_mobile();

            setTimeout(function () {

                actions.seoCalcEvents();

            }, 500);
        },

        selectChildOperators: function () {
            $(document).on('dblclick', '.flowchart-operator', function (e) {
                $(this).addClass('selected');

                let operator = $(this);
                let type     = operator.attr('data-type');
                let id       = operator.attr('data-id');
                let links    = null;

                if (type == 'page' || type == 'post') {
                    links = $('.flowchart-link[data-from-id="' + id + '"]');
                } else {
                    links = $('.flowchart-link[data-to-id="' + id + '"]');
                }

                links.each(function () {
                    let attr = null
                    if (type == 'page' || type == 'post') {
                        attr = 'data-to-id';
                    } else {
                        attr = 'data-from-id';
                    }
                    $('.silo:visible').find('[data-id="' + $(this).attr(attr) + '"]').addClass('selected');
                });
            });
        },

        generateSiloLinks: function () {
            $(document).on('click', '.generate-silo-links', function (e) {
                e.preventDefault();

                let area = $(this).parents('.xagio-tab-content').find('.silo-area');
                area.blockUI();

                $.post(xagio_data.wp_post, 'action=xagio_generate_silo_links', function (d) {

                    area.blockUI();

                    $('.silo.links').flowchart('setData', d.data);

                    $('#internal_links_line_width').trigger('change');
                    $('#internal_links_line_type').trigger('change');
                    $('#internal_links_line_color').trigger('change');

                    $('#links_canvas_size').trigger('change');

                    $('#external_links_line_width').trigger('change');
                    $('#external_links_line_type').trigger('change');

                    $('#links_external_color').trigger('change');
                    $('#external_links_line_color').trigger('change');

                    xagioNotify("success", "Successfully generated SILO for internal/external Links from the whole Website!");
                });
            });
        },

        generateSiloLinksByID: function () {
            $(document).on('click', '.silo-add-links', function (e) {
                e.preventDefault();

                let area = $(this).parents('.xagio-tab-content').find('.silo-area');
                area.blockUI();

                $.post(xagio_data.wp_post, 'action=xagio_generate_silo_links_by_id&id=' + $(this).attr('data-id'), function (d) {

                    area.blockUI();

                    $('.silo.links').flowchart('setData', d.data);

                    $('#internal_links_line_width').trigger('change');
                    $('#internal_links_line_type').trigger('change');
                    $('#internal_links_line_color').trigger('change');

                    $('#links_canvas_size').trigger('change');

                    $('#external_links_line_width').trigger('change');
                    $('#external_links_line_type').trigger('change');

                    $('#links_external_color').trigger('change');
                    $('#external_links_line_color').trigger('change');

                    xagioNotify("success", "Successfully generated SILO for internal/external Links from the selected post!");
                });
            });
        },

        saveButtonChangeName: function () {
            let saveButton = $('.silo-save');

            $(document).on('click', '.silo-pages-tab', function () {
                saveButton.html('<i class="xagio-icon xagio-icon-save"></i> Save Pages');
            });
            $(document).on('click', '.silo-posts-tab', function () {
                saveButton.html('<i class="xagio-icon xagio-icon-save"></i> Save Posts');
            });
            $(document).on('click', '.silo-links-tab', function () {
                saveButton.html('<i class="xagio-icon xagio-icon-save"></i> Save Links');
            });
        },

        permalinkSettings: function () {
            let permalink_input = $('.silo-permalink-input');

            $(document).on('click', '.permalink-radio', function (e) {
                let value = $(this).val();
                permalink_input.val(value);
            });

            $(document).on('click', '.permalink-button', function (e) {
                e.preventDefault();
                if (!$(this).hasClass('active')) {
                    $(this).addClass('active');
                    let text = permalink_input.val() + '/' + $(this).text().trim() + '/';
                    permalink_input.val(text.replace('//', '/'));
                } else {
                    $(this).removeClass('active');
                    let text = $(this).text().trim();
                    permalink_input.val(permalink_input.val().replace('/' + text, ''));
                }
            });

            $(document).on('click', '.permalink-postname', function (e) {
                e.preventDefault();
                $(this).toggleClass('active');
                if ($(this).hasClass('active')) {
                    permalink_input.val('/%postname%/');
                }
            });


            $(document).on('click', '.permalink-save-button', function (e) {
                e.preventDefault();
                let form = $('.silo-permalinks-save').serializeArray();
                for (let i = 0; i < form.length; i++) {
                    if (form[i].name == '_wp_http_referer') {
                        form[i].value = '/wp-admin/options-permalink.php';
                        break;
                    }
                }
                form.push({
                    name : 'submit',
                    value: 'Save Changes'
                });
                $.post('/wp-admin/options-permalink.php', form, function (d) {
                    xagioNotify("success", "Successfully saved new permalink settings.");
                });
            });
        },

        keySave       : function () {
            document.addEventListener("keydown", function (e) {
                if ((window.navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey) && e.keyCode == 83) {
                    e.preventDefault();
                    $('.silo-save').trigger('click');
                }
            }, false);
        },
        newCategoryTag: function () {
            $(document).on('click', '.add-category', function (e) {
                e.preventDefault();

                xagioPromptModal("Category Name", "New Category Name:", function (result) {
                    if (result) {
                        let name = result;

                        $.post(xagio_data.wp_post, 'action=xagio_new_category&name=' + name, function (d) {
                            xagioNotify("success", `Successfully created new category: ${name}`);
                            actions.loadTagsCategoriesSilo();
                        });
                    }
                });
            });
            $(document).on('click', '.add-tags', function (e) {
                e.preventDefault();
                xagioPromptModal("Tag Name", "New Tag Name:", function (result) {
                    if (result) {
                        let name = result;

                        $.post(xagio_data.wp_post, 'action=xagio_new_tag&name=' + name, function (d) {
                            xagioNotify("success", `Successfully created new tag: ${name}`);
                            actions.loadTagsCategoriesSilo();
                        });
                    }
                });
            });
        },

        changeLineType: function () {
            $(document).on('change', 'select[name="line_type"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link').find('path').attr('stroke-dasharray', v);
            });
            $(document).on('change', 'select[name="line_category_type"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link[data-to-type="category"]').find('path').attr('stroke-dasharray', v);
            });
            $(document).on('change', 'select[name="line_tag_type"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link[data-to-type="tag"]').find('path').attr('stroke-dasharray', v);
            });
            $(document).on('change', 'select[name="external_line_type"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link[data-to-type="external"]').find('path').attr('stroke-dasharray', v);
            });
            $(document).on('change', 'select[name="internal_line_type"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link[data-to-type="page"]').find('path').attr('stroke-dasharray', v);
                s.find('.flowchart-link[data-to-type="post"]').find('path').attr('stroke-dasharray', v);
            });
        },

        changeLineColors: function () {
            $(document).on('input change', 'input[name="line_color"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link').find('path').attr('stroke', v).attr('color', v);
            });
            $(document).on('input change', 'input[name="line_category_color"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link[data-to-type="category"]').find('path').attr('stroke', v).attr('color', v);
            });
            $(document).on('input change', 'input[name="line_tag_color"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link[data-to-type="tag"]').find('path').attr('stroke', v).attr('color', v);
            });
            $(document).on('input change', 'input[name="external_line_color"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link[data-to-type="external"]').find('path').attr('stroke', v).attr('color', v);
            });
            $(document).on('input change', 'input[name="internal_line_color"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link[data-to-type="page"]').find('path').attr('stroke', v).attr('color', v);
                s.find('.flowchart-link[data-to-type="post"]').find('path').attr('stroke', v).attr('color', v);
            });
        },

        changeBoxColors: function () {
            $(document).on('input change', 'input[name="box_tag_color"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.operator-tag .flowchart-operator-title').css('background', v).attr('color', v);
                $('.silo-tags .draggable_operator').css('background', v).attr('color', v);
            });
            $(document).on('input change', 'input[name="box_category_color"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.operator-category .flowchart-operator-title').css('background', v).attr('color', v);
                $('.silo-categories .draggable_operator').css('background', v).attr('color', v);
            });
            $(document).on('input change', 'input[name="external_color"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-operator.operator-external').css('background', v).attr('color', v);
            });
        },

        changeCanvasSizeGlobal: function () {
            $(document).on('input change', 'input[name="canvas_size"]', function (e) {
                e.preventDefault();

                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();

                s.css('height', v + 'px');
                s.css('width', v + 'px');

                s.panzoom('resetDimensions');
                s.panzoom('reset', {
                    animate: false,
                    contain: true
                });

            });
        },

        changeLineWidthGlobal: function () {
            $(document).on('input change', 'input[name="line_thickness"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link').find('path').attr('stroke-width', v);
            });
            $(document).on('input change', 'input[name="external_line_thickness"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link[data-to-type="external"]').find('path').attr('stroke-width', v);
            });
            $(document).on('input change', 'input[name="internal_line_thickness"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link[data-to-type="post"]').find('path').attr('stroke-width', v);
                s.find('.flowchart-link[data-to-type="page"]').find('path').attr('stroke-width', v);
            });
            $(document).on('input change', 'input[name="line_category_thickness"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link[data-to-type="category"]').find('path').attr('stroke-width', v);
            });
            $(document).on('input change', 'input[name="line_tag_thickness"]', function (e) {
                let s = $(this).parents('.xagio-tab-content').find('.silo');
                let v = $(this).val();
                s.find('.flowchart-link[data-to-type="tag"]').find('path').attr('stroke-width', v);
            });
        },

        scrollCatsTags: function () {
            $('.silo-tags, .silo-categories').on('mousewheel DOMMouseScroll', function (event) {

                var delta = Math.max(-1, Math.min(1, (event.originalEvent.wheelDelta || -event.originalEvent.detail)));

                $(this).scrollLeft($(this).scrollLeft() - (delta * 40));
                event.preventDefault();
                event.stopPropagation();

            });
        },

        settingsMenu: function () {
            $(document).on('settings-menu-created', function (e, appendDivs) {

                let $this = $(e.target);

                let o    = $this.parents('.flowchart-operator');
                let id   = o.attr('data-id');
                let type = o.attr('data-type');

                let data = {
                    action: 'xagio_get_operator_data',
                    id    : id,
                    type  : type
                };

                $.post(xagio_data.wp_post, data, function (d) {
                    $this.find('.operator_h1').val(d.data.h1);
                    $this.find('.operator_title').val(d.data.title);
                    $this.find('.operator_description').val(d.data.desc);
                    $this.find('.operator_slug').val(d.data.slug);
                    $this.find('.operator_slug_before').html(o.find('.flowchart-operator-subtitle').text().replace(d.data.slug, ''));
                });

            });
            $(document).on('click', '.context-menu-save', function (e) {
                e.preventDefault();
                let $this = $(e.target);
                let o     = $this.parents('.flowchart-operator');
                let id    = o.attr('data-id');
                let type  = o.attr('data-type');

                let h1    = o.find('.operator_h1').val();
                let title = o.find('.operator_title').val();
                let desc  = o.find('.operator_description').val();
                let slug  = o.find('.operator_slug').val();

                let data = {
                    action: 'xagio_update_operator_data',
                    id    : id,
                    title : encodeURIComponent(title),
                    desc  : encodeURIComponent(desc),
                    slug  : encodeURIComponent(slug),
                    h1    : encodeURIComponent(h1),
                    type  : type
                };

                $.post(xagio_data.wp_post, data, function (d) {
                    xagioNotify("success", "Successfully saved operator data!");
                    actions.loadSiloPagesPosts();
                    o.find('.flowchart-operator-title-value').text(h1);

                    for (let i = 0; i < siloMenus.length; i++) {
                        siloMenus[i].remove();
                    }
                });
            });

            $(document).on('mouseenter', '.silo-context-menu', function (e) {
                clearTimeout(siloLeave);
            });
            $(document).on('mouseleave', '.silo-context-menu', function (e) {
                e.preventDefault();

                siloLeave = setTimeout(function () {

                    for (let i = 0; i < siloMenus.length; i++) {
                        siloMenus[i].remove();
                    }

                    siloMenus = [];

                }, 5000);
            });
            $(document).on('click', '.flowchart-operator-toggle-options', function (e) {
                e.preventDefault();

                let p = $(this).parents('.flowchart-operator-title');

                let div = $('.silo-context-menu.template').clone();
                div.removeClass('template');

                siloMenus.push(div);
                p.append(div);

                div.trigger("settings-menu-created");
            });
            $(document).on('click', '.context-menu-discard', function (e) {
                e.preventDefault();

                for (let i = 0; i < siloMenus.length; i++) {
                    siloMenus[i].remove();
                }

                siloMenus = [];
            });
        },

        hideLinks: function () {
            $(document).on('click', '.flowchart-operator-toggle-visibility', function (e) {
                e.preventDefault();

                let operator = null;
                if ($(this).parents('.flowchart-operator').length == 0) {
                    operator = $(this).parents('.draggable_operator');
                } else {
                    operator = $(this).parents('.flowchart-operator');
                }
                let type  = operator.attr('data-type');
                let id    = operator.attr('data-id');
                let links = null;

                if (type == 'page' || type == 'post') {
                    links = $('.flowchart-link[data-from-id="' + id + '"]');
                } else {
                    links = $('.flowchart-link[data-to-id="' + id + '"]');
                }

                operator.toggleClass('low-opacity');

                links.each(function () {

                    let link     = $(this);
                    let operator = null;

                    if (type == 'page' || type == 'post') {
                        operator = $('.op-' + type + '-' + link.attr('data-to-id'));
                    } else {
                        operator = $('.op-post-' + link.attr('data-from-id'));
                    }

                    if (link.hasClass('low-opacity')) {
                        operator.removeClass('low-opacity');
                        link.removeClass('low-opacity');
                    } else {
                        operator.addClass('low-opacity');
                        link.addClass('low-opacity');
                    }

                });

            });
        },

        hideAllLinks: function () {
            $(document).on('click', '.hide-all-categories', function (e) {
                e.preventDefault();

                let links = $('.flowchart-link[data-to-type="category"]');
                links.each(function () {

                    let link     = $(this);
                    let operator = $('.op-post-' + link.attr('data-from-id'));
                    if (link.hasClass('low-opacity')) {
                        operator.removeClass('low-opacity');
                        link.removeClass('low-opacity');
                    } else {
                        operator.addClass('low-opacity');
                        link.addClass('low-opacity');
                    }

                });

                $('.operator-category').toggleClass('low-opacity');

            });
            $(document).on('click', '.hide-all-tags', function (e) {
                e.preventDefault();

                let links = $('.flowchart-link[data-to-type="tag"]');
                links.each(function () {

                    let link     = $(this);
                    let operator = $('.op-post-' + link.attr('data-from-id'));
                    if (link.hasClass('low-opacity')) {
                        operator.removeClass('low-opacity');
                        link.removeClass('low-opacity');
                    } else {
                        operator.addClass('low-opacity');
                        link.addClass('low-opacity');
                    }

                });

                $('.operator-tag').toggleClass('low-opacity');
            });
        },

        hardReset: function () {

            $(document).on('click', '.xagio-button-reset-all', function (e) {
                e.preventDefault();

                xagioModal("Are you sure?", "This will completely empty your SILO Builder! Proceed?", function (yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, 'action=xagio_reset_parents_cats_tags', function (d) {

                            xagioNotify("success", "Successfully reverted all pages and posts to default settings. SILO Builder has been cleared as well. Refreshing this page...");

                            actions.getSiloTabs(function () {

                                actions.loadSilo(false, $('.silo.pages'));
                                actions.loadSilo(false, $('.silo.posts'));
                                actions.loadSilo(false, $('.silo.links'));

                                actions.redrawSILOLinks();

                            });
                        });
                    }
                })
            });
        },

        trashPagePost: function () {
            $(document).on('click', '.silo-trash', function (e) {
                e.preventDefault();
                let type = $(this).attr('data-type');
                let text = $(this).attr('data-text');
                let id   = $(this).attr('data-id');
                xagioModal("Are you sure?", "Are you sure that you want to remove " + text + "?", function (yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, 'action=xagio_delete_page&id=' + id, function (d) {
                            actions.loadSiloPagesPosts();
                        });
                    }
                });
            });
        },

        trashTagCategory: function () {
            $(".silo-tags").hover(function () {
                $(this).addClass('hover');
            }, function () {
                $(this).removeClass('hover');
            });
            $(document).on('click', '.flowchart-operator-remove-tag', function (e) {
                e.preventDefault();
                let text = $(this).parents('.draggable_operator').attr('data-text');
                let id   = $(this).parents('.draggable_operator').attr('data-id');

                xagioModal("Are you sure?", "Are you sure that you want to remove <b>" + text + "</b>?", function (yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, 'action=xagio_delete_tag&id=' + id, function (d) {
                            actions.loadTagsCategoriesSilo();
                        });
                    }
                });
            });

            $(document).on('click', '.flowchart-operator-remove-category', function (e) {
                e.preventDefault();
                let text = $(this).parents('.draggable_operator').attr('data-text');
                let id   = $(this).parents('.draggable_operator').attr('data-id');
                xagioModal("Are you sure?", "Are you sure that you want to remove <b>" + text + "</b>?", function (yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, 'action=xagio_delete_category&id=' + id, function (d) {
                            actions.loadTagsCategoriesSilo();
                        });
                    }
                });
            });
        },

        // Function for adding posts/pages from silo builder
        siloAddPostPage: function () {
            $(document).on('click', '.silo-post-page-create', function (e) {
                e.preventDefault();
                let button  = $(this);
                let title   = $('.post-page-title').val();
                let url     = $('.post-page-url').val();
                let type    = $('.post-page-type').val();
                let status  = $('.post-page-status').val();

                if (title == '') {
                    $('.post-page-title').val('New Post');
                }
                if (url == '') {
                    $('.post-page-url').val('');
                }

                button.disable();

                $.post(xagio_data.wp_post, 'action=xagio_add_new_page_post&title=' + title + '&url=' + url + '&type=' + type + '&status=' + status + '', function (d) {
                    $("#silo-add-page-post")[0].close();
                    button.disable();
                    actions.loadSiloPagesPosts();
                });
            });

            $(document).on('click', '.silo-add-page-post', function (e) {
                e.preventDefault();
                $('#silo-add-page-post')[0].showModal();
            });
        },

        generateSilo: function () {
            $(document).on('click', '.xagio-button-generate-silo', function (e) {
                e.preventDefault();

                let silo = actions.siloGetFlowchart();
                let name = actions.siloGetName(silo);
                let type = actions.siloGetType(silo);

                let formData = {
                    action: 'xagio_generate_silo',
                    name  : name,
                    type  : type
                };

                function sendRequest(formData, type) {
                    $.post(xagio_data.wp_post, formData, function (d) {

                        xagioNotify("success", `Successfully generated a SILO from ${type}. Refreshing this page...`);

                        actions.loadSilo();

                        $("#silo-generate-modal")[0].close();

                    });
                }

                xagioModal("Are you sure?", "This will empty your current SILO Builder changes! Proceed?", function (yes) {
                    if (yes) {
                        if (type == 'pages') {

                            $("#silo-generate-modal")[0].showModal();

                            $(document).off('click', '.silo-continue-generate')
                            $(document).on('click', '.silo-continue-generate', function (e) {
                                e.preventDefault();

                                let import_connetions = $('.imp_connections');
                                let import_all        = $('.imp_all');

                                if (import_connetions.is(':checked') == true) {
                                    formData.importAll = 'no';
                                } else if (import_all.is(':checked') == true) {
                                    formData.importAll = 'yes';
                                }

                                sendRequest(formData, type);
                            });

                        } else {
                            sendRequest(formData, type);
                        }
                    }
                });
            });
        },

        getOperatorData: function ($element) {
            let type = $element.data('type');

            let data = {
                properties: {
                    title    : $element.data('text'),
                    attached : $element.data('attached'),
                    permalink: $element.data('permalink'),
                    type     : type,
                    icon     : '',
                    inputs   : {},
                    outputs  : {}
                }
            };

            if (type == 'page') {
                data.properties.icon              = 'xagio-icon-file';
                data.properties.inputs['input_1'] = {};
                data.properties.outputs['outs']   = {
                    multiple: true
                };
            } else if (type == 'post') {
                data.properties.icon           = 'xagio-icon-file';
                data.properties.outputs['ins'] = {
                    multiple: true
                };
            } else if (type == 'tag') {
                data.properties.icon           = 'xagio-icon-tag';
                data.properties.inputs['outs'] = {
                    multiple: true
                };
            } else if (type == 'category') {
                data.properties.icon           = 'xagio-icon-align-justify';
                data.properties.inputs['outs'] = {
                    multiple: true
                };
            }

            let uniqueID           = ' op-' + type + '-' + $element.data('id');
            data.properties.class  = 'operator-' + type + uniqueID;
            data.properties.ID     = uniqueID;
            data.properties.realID = $element.data('id');

            return data;
        },

        createSiloLinks: function () {

            let $flowchart = $('.silo.links');
            let $container = $flowchart.parent();

            let selOpts = {
                zone    : $flowchart,
                elements: $flowchart.find('div.flowchart-operator'),
                key     : false
            };
            new Selectables(selOpts);

            // Panzoom initialization...
            let pan = $flowchart.panzoom();

            // Panzoom zoom handling...
            let possibleZooms = [
                0.1,
                0.2,
                0.3,
                0.4,
                0.5,
                0.6,
                0.7,
                0.8,
                0.9,
                1,
                1.5,
                2
            ];
            let currentZoom   = 9;

            $container.on('mousewheel.focal', function (e) {
                e.preventDefault();
                let delta   = (e.delta || e.originalEvent.wheelDelta) || e.originalEvent.detail;
                let zoomOut = !(delta ? delta < 0 : e.originalEvent.deltaY > 0); // natural scroll direciton

                currentZoom = Math.max(0, Math.min(possibleZooms.length - 1, (currentZoom + (zoomOut * 2 - 1))));

                let f = {
                    clientX: e.clientX,
                    clientY: e.clientY,
                    current: possibleZooms[currentZoom]
                };

                $flowchart.flowchart('setPositionRatio', possibleZooms[currentZoom]);
                $flowchart.panzoom('zoom', possibleZooms[currentZoom], {
                    animate: true,
                    focal  : f
                });

                $flowchart.attr('data-zoom', JSON.stringify(f));

            });

            $flowchart.on('panzoomchange', function () {

                let matrix = $flowchart.panzoom('getMatrix');

                let f = {
                    x: parseFloat(matrix[4]),
                    y: parseFloat(matrix[5])
                };

                $flowchart.attr('data-pan', JSON.stringify(f));

            });

            // Apply the plugin on a standard, empty div...
            $flowchart.flowchart({
                preventOptions    : true,
                verticalConnection: true,
                defaultLinkColor  : '#559acc',
                onOperatorCreate  : function (operatorId, operatorData, fullElement) {

                    let uniqueID  = '.' + operatorData.properties.ID.trim();
                    let flowchart = actions.siloGetFlowchart($flowchart);
                    if (flowchart.find(uniqueID).length > 0) {
                        xagioNotify("warning", "Invalid operation, element is already added to this SILO.");
                        return false;
                    }

                    return true;
                }
            });

        },

        createSilo: function (element) {

            let $flowchart = $(element);
            let $container = $flowchart.parent();

            let selOpts = {
                zone    : $flowchart,
                elements: $flowchart.find('div.flowchart-operator'),
                key     : false
            };
            new Selectables(selOpts);

            // Panzoom initialization...
            let pan = $flowchart.panzoom();

            // Panzoom zoom handling...
            let possibleZooms = [
                0.1,
                0.2,
                0.3,
                0.4,
                0.5,
                0.6,
                0.7,
                0.8,
                0.9,
                1,
                1.5,
                2
            ];
            let currentZoom   = 9;

            $container.on('mousewheel.focal', function (e) {
                e.preventDefault();
                let delta   = (e.delta || e.originalEvent.wheelDelta) || e.originalEvent.detail;
                let zoomOut = !(delta ? delta < 0 : e.originalEvent.deltaY > 0); // natural scroll direciton

                currentZoom = Math.max(0, Math.min(possibleZooms.length - 1, (currentZoom + (zoomOut * 2 - 1))));

                let f = {
                    clientX: e.clientX,
                    clientY: e.clientY,
                    current: possibleZooms[currentZoom],
                };

                $flowchart.flowchart('setPositionRatio', possibleZooms[currentZoom]);
                $flowchart.panzoom('zoom', possibleZooms[currentZoom], {
                    animate: true,
                    focal  : f
                });

                $flowchart.attr('data-zoom', JSON.stringify(f));

            });

            $flowchart.on('panzoomchange', function () {

                let matrix = $flowchart.panzoom('getMatrix');

                let f = {
                    x: parseFloat(matrix[4]),
                    y: parseFloat(matrix[5])
                };

                $flowchart.attr('data-pan', JSON.stringify(f));

            });


            let interval;
            let called = false;

            $container.on('mousedown', '.navigation-arrow', function (e) {
                let type = $(this).attr('data-type');
                if (interval == null) {
                    called   = false;
                    interval = setInterval(function () {
                        moveByMouse(type);
                        called = true;
                    }, 2);
                }

            }).on('mouseup', '.navigation-arrow', function (e) {
                let type = $(this).attr('data-type');
                clearInterval(interval);
                interval = null;
                if (!called) moveByMouse(type);
            });

            window.addEventListener("keydown", moveByMouse, false);

            function moveByMouse(type) {

                let matrix = $flowchart.panzoom('getMatrix');
                let x      = matrix[4];
                let y      = matrix[5];

                x = parseFloat(x);
                y = parseFloat(y);

                switch (type) {

                    // UP
                    case 'up':
                        y += 10;
                        break;

                    // DOWN
                    case 'down':
                        y -= 10;
                        break;

                    // LEFT
                    case 'left':
                        x += 10;
                        break;

                    // RIGHT
                    case 'right':
                        x -= 10;
                        break;
                }

                switch (type.keyCode) {
                    case 37:
                        // left key pressed
                        x += 20;
                        $('body').css('overflow', 'hidden');
                        break;
                    case 38:
                        // up key pressed
                        y += 20;
                        $('body').css('overflow', 'hidden');
                        break;
                    case 39:
                        // right key pressed
                        x -= 20;
                        $('body').css('overflow', 'hidden');
                        break;
                    case 40:
                        // down key pressed
                        y -= 20;
                        $('body').css('overflow', 'hidden');
                        break;
                }

                setTimeout(function () {
                    $('body').css('overflow', 'auto');
                }, 4000)

                x = parseFloat(x);
                y = parseFloat(y);

                $flowchart.panzoom('pan', x, y);
            }

            // Apply the plugin on a standard, empty div...
            $flowchart.flowchart({
                verticalConnection: true,
                defaultLinkColor  : '#559acc',
                onOperatorCreate  : function (operatorId, operatorData, fullElement) {

                    if (!operatorData.properties.hasOwnProperty('ID')) {
                        return false;
                    }

                    let uniqueID  = '.' + operatorData.properties.ID.trim();
                    let flowchart = actions.siloGetFlowchart($flowchart);
                    if (flowchart.find(uniqueID).length > 0) {
                        xagioNotify("warning", "Invalid operation, element is already added to this SILO.");
                        return false;
                    }

                    if (siloUsedIDs.hasOwnProperty(operatorData.properties.type)) {
                        if (siloUsedIDs[operatorData.properties.type].includes(operatorData.properties.realID)) {
                            xagioNotify("warning", `This ${operatorData.properties.type} is already present in another canvass. You cannot add the same ${operatorData.properties.type} to multiple canvases.`);
                            return false;
                        }
                    }


                    return true;
                }
            });

        },

        redrawLinks: function () {
            $('.silo.pages').flowchart('redrawLinksLayer');
            $('.silo.posts').flowchart('redrawLinksLayer');
            $('.silo.links').flowchart('redrawLinksLayer');
        },

        initSilo: function () {
            actions.changeLineWidthGlobal();
            actions.changeCanvasSizeGlobal();
            actions.changeLineColors();
            actions.changeLineType();
            actions.changeBoxColors();

            actions.loadSiloPagesPosts();
            actions.addToSilo();

            actions.loadTagsCategoriesSilo();

            actions.createSilo('.silo.pages');
            actions.createSilo('.silo.posts');
            actions.createSiloLinks();

            actions.loadSilo(false, $('.silo.pages'));
            actions.loadSilo(false, $('.silo.posts'));
            actions.loadSilo(false, $('.silo.links'));

            actions.redrawSILOLinks();
            actions.newSILO();
            actions.siloChange();
            actions.siloRemoveName();
        },

        siloRemoveName: function () {
            $(document).on('click', '.remove-silo-name', function (e) {
                e.preventDefault();
                e.stopPropagation();

                let name = $(this).parents('li').attr('data-name');
                let type = actions.siloGetType($(this));

                xagioModal("Are you sure?", "This will remove the selected SILO!", function (yes) {
                    if (yes) {
                        let formData = {
                            action: 'xagio_silo_remove_name',
                            name  : name,
                            type  : type
                        };

                        $.post(xagio_data.wp_post, formData, function (d) {

                            xagioNotify("success","Successfully removed selected SILO!");

                            actions.getSiloTabs();
                        });
                    }
                });

            });
        },

        siloChange: function () {
            $(document).on('click', '.silo-tabs .xagio-tab > li', function (e) {
                e.preventDefault();
                $(this).parents('.silo-tabs').find('.xagio-tab').find('li.xagio-tab-active').removeClass('xagio-tab-active');
                $(this).addClass('xagio-tab-active');
                actions.loadSilo(false, actions.siloGetFlowchart());
            });
        },

        redrawSILOLinks: function () {
            $(document).on('click', '.xagio-tab.main-nav > li > a', function (e) {
                e.preventDefault();
                setTimeout(function () {
                    $('.silo:visible').flowchart('redrawLinksLayer');
                }, 30);
            });
        },

        newSILO: function () {
            $(document).on('click', '.new-silo', function (e) {
                e.stopPropagation();
                e.preventDefault();

                let type = actions.siloGetType($(this));

                xagioPromptModal("Add a new SILO", "Enter a name for a new SILO:", function (result) {

                    if (result) {
                        let name = result;

                        let data = {
                            action: 'xagio_new_silo',
                            name  : name,
                            type  : type
                        };
                        $.post(xagio_data.wp_post, data, function (d) {

                            if (d.status == 'success') {

                                xagioNotify("success", d.message);
                                actions.getSiloTabs();

                            } else {

                                xagioNotify("danger", d.message);
                            }
                        });
                    }
                });

            });
        },

        getSiloTabs: function (callback) {

            let pages = $('.silo-tabs[data-type="pages"]').find('.xagio-tab');
            let posts = $('.silo-tabs[data-type="posts"]').find('.xagio-tab');
            let links = $('.silo-tabs[data-type="links"]').find('.xagio-tab');

            let newSiloButton = '<li data-xagio-tooltip data-xagio-title="Add a new SILO."><a href="#" class="new-silo"><i class="xagio-icon xagio-icon-plus"></i></a></li>';

            let data = {action: 'xagio_load_silo_names'};
            $.post(xagio_data.wp_post, data, function (d) {

                pages.empty();
                posts.empty();
                links.empty();

                for (let i = 0; i < d.data.pages.length; i++) {
                    let page = d.data.pages[i];
                    pages.append('<li data-name="' + page + '" class="' + ((i == 0) ? 'xagio-tab-active' : '') + '"><a href="#">' + page + ' <i class="xagio-icon xagio-icon-delete remove-silo-name"></i></a></li>');
                }
                pages.append(newSiloButton);

                for (let i = 0; i < d.data.posts.length; i++) {
                    let post = d.data.posts[i];
                    posts.append('<li data-name="' + post + '" class="' + ((i == 0) ? 'xagio-tab-active' : '') + '"><a href="#">' + post + ' <i class="xagio-icon xagio-icon-delete remove-silo-name"></i></a></li>');
                }
                posts.append(newSiloButton);

                for (let i = 0; i < d.data.links.length; i++) {
                    let link = d.data.links[i];
                    links.append('<li data-name="' + link + '" class="' + ((i == 0) ? 'xagio-tab-active' : '') + '"><a href="#">' + link + ' <i class="xagio-icon xagio-icon-delete remove-silo-name"></i></a></li>');
                }
                links.append(newSiloButton);

                if (typeof callback !== 'undefined') {
                    callback();
                }
            });
        },

        loadSilo: function (softLoad, element) {

            let flowchart = actions.siloGetFlowchart(element);
            if (typeof element != "undefined") {
                flowchart = element;
            }
            let name = actions.siloGetName(flowchart);
            let type = actions.siloGetType(flowchart);

            let area = flowchart.parents('.silo-area');

            area.blockUI();

            $.post(xagio_data.wp_post, 'action=xagio_load_silo&name=' + name + '&type=' + type, function (d) {

                area.blockUI();

                let silo = d.data;
                if (silo == null) return;

                let tempSiloIDs = [];

                if (silo.IDS != false) {

                    // Load the SILO Ids
                    for (let type in d.data.IDS) {
                        siloUsedIDs[type] = [];
                        tempSiloIDs[type] = d.data.IDS[type];
                    }

                }

                flowchart.flowchart('setData', silo);
                flowchart.flowchart('setPositionRatio', 1);

                if (silo.IDS != false) {

                    // Load the SILO Ids
                    for (let type in d.data.IDS) {
                        siloUsedIDs[type] = tempSiloIDs[type];
                    }

                }

                if (typeof softLoad == 'undefined' || softLoad == false) {
                    if (type == 'pages') {

                        if (silo.hasOwnProperty('settings')) {
                            $('#pages_line_width').val(silo.settings.line_thickness).trigger('change');
                            $('#pages_line_type').val(silo.settings.line_type).trigger('change');
                            $('#pages_line_color').val(silo.settings.line_color).trigger('change');
                            $('#pages_canvas_size').val(silo.settings.canvas_size).trigger('change');
                        }

                    } else if (type == 'posts') {

                        if (silo.hasOwnProperty('settings')) {

                            $('#posts_line_width1').val(silo.settings.line_category_thickness).trigger('change');
                            $('#posts_line_type1').val(silo.settings.line_category_type).trigger('change');
                            $('#posts_line_color1').val(silo.settings.line_category_color).trigger('change');

                            $('#posts_line_width2').val(silo.settings.line_tag_thickness).trigger('change');
                            $('#posts_line_type2').val(silo.settings.line_tag_type).trigger('change');
                            $('#posts_line_color2').val(silo.settings.line_tag_color).trigger('change');

                            $('#posts_box_color1').val(silo.settings.box_category_color).trigger('change');
                            $('#posts_box_color2').val(silo.settings.box_tag_color).trigger('change');

                            $('#posts_canvas_size').val(silo.settings.canvas_size).trigger('change');
                        }

                    } else if (type == 'links') {

                        if (silo.hasOwnProperty('settings')) {

                            $('#internal_links_line_width').val(silo.settings.internal_line_thickness).trigger('change');
                            $('#internal_links_line_type').val(silo.settings.internal_line_type).trigger('change');
                            $('#internal_links_line_color').val(silo.settings.internal_line_color).trigger('change');

                            $('#links_canvas_size').val(silo.settings.canvas_size).trigger('change');

                            $('#external_links_line_width').val(silo.settings.external_line_thickness).trigger('change');
                            $('#external_links_line_type').val(silo.settings.external_line_type).trigger('change');

                            $('#links_external_color').val(silo.settings.external_color).trigger('change');
                            $('#external_links_line_color').val(silo.settings.external_line_color).trigger('change');

                        }

                    }
                }

                if (silo.hasOwnProperty('settings')) {

                    if (silo.settings.hasOwnProperty('zoom')) {

                        flowchart.flowchart('setPositionRatio', silo.settings.zoom.current);
                        flowchart.panzoom('zoom', silo.settings.zoom.current, {
                            animate: false,
                            focal  : silo.settings.zoom
                        });

                        flowchart.attr('data-zoom', JSON.stringify(silo.settings.zoom));

                    }

                    if (silo.settings.hasOwnProperty('pan')) {

                        flowchart.panzoom('pan', silo.settings.pan.x, silo.settings.pan.y);
                        flowchart.attr('data-pan', JSON.stringify({
                            x: silo.settings.pan.x,
                            y: silo.settings.pan.y
                        }));

                    }

                }
                flowchart.flowchart('redrawLinksLayer');
            });
        },

        loadTagsCategoriesSilo: function () {
            $.post(xagio_data.wp_post, 'action=xagio_get_tags_categories', function (d) {
                let cats = $('.silo-categories');
                let tags = $('.silo-tags');

                cats.empty();
                tags.empty();

                for (let i = 0; i < d.data.tags.length; i++) {
                    let tag = d.data.tags[i];
                    tags.append('<div data-xagio-tooltip data-xagio-title="You can drag this tag directly into the SILO Builder." class="draggable_operator" data-id="' + tag.term_id + '" data-type="tag" data-text="' + tag.name + '">' + tag.name + '<i class="flowchart-operator-toggle-visibility xagio-icon xagio-icon-eye-slash"></i><i class="flowchart-operator-remove-tag xagio-icon xagio-icon-close"></i></div>');
                }

                for (let i = 0; i < d.data.categories.length; i++) {
                    let cat = d.data.categories[i];
                    cats.append('<div data-xagio-tooltip data-xagio-title="You can drag this category directly into the SILO Builder." class="draggable_operator" data-id="' + cat.term_id + '" data-type="category" data-text="' + cat.name + '">' + cat.name + '<i class="flowchart-operator-toggle-visibility xagio-icon xagio-icon-eye-slash"></i><i class="flowchart-operator-remove-category xagio-icon xagio-icon-delete"></i></div>');
                }

                actions.initDrag($('.draggable_operator'));

            });
        },

        addToSilo: function () {
            $(document).on('click', '.silo-add', function (e) {
                e.preventDefault();
                let $element = $(this);
                let data = actions.getOperatorData($element);
                let type = $element.data('type');
                $('.silo.' + type + 's').flowchart('addOperator', data);
            });
        },

        siloGetName: function (element) {
            return element.parents('.xagio-tab-content').find('.silo-tabs').find('.xagio-tab-active').attr('data-name');
        },

        siloGetType: function (element) {
            return element.parents('.xagio-tab-content').find('.silo-tabs').attr('data-type');
        },

        siloGetFlowchart: function (elements) {
            if (typeof elements != 'undefined') {
                if (elements.hasClass('silo')) {
                    return elements;
                } else {
                    return elements.parents('.xagio-tab-content').find('.silo');
                }
            } else {
                let pages = $('.silo.pages');
                let posts = $('.silo.posts');
                let links = $('.silo.links');
                if (pages.is(':visible')) {
                    return pages;
                } else if (posts.is(':visible')) {
                    return posts;
                } else {
                    return links;
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
                    let data  = actions.getOperatorData($this);
                    return $flowchart.flowchart('getOperatorElement', data);
                },
                stop  : function (e, ui) {
                    let $this           = $(this);
                    let elOffset        = ui.offset;
                    let containerOffset = $container.offset();
                    if (elOffset.left > containerOffset.left && elOffset.top > containerOffset.top && elOffset.left < containerOffset.left + $container.width() && elOffset.top < containerOffset.top + $container.height()) {

                        let flowchartOffset = $flowchart.offset();

                        let relativeLeft = elOffset.left - flowchartOffset.left;
                        let relativeTop  = elOffset.top - flowchartOffset.top;

                        let positionRatio = $flowchart.flowchart('getPositionRatio');
                        relativeLeft /= positionRatio;
                        relativeTop /= positionRatio;

                        let data  = actions.getOperatorData($this);
                        data.left = relativeLeft;
                        data.top  = relativeTop;

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
                    infoFiltered     : ""
                },
                "dom"           : '<f>rt<"xagio-table-bottom"<>p>',
                "bDestroy"      : true,
                "sFilterInput"  : "xagio-input-text-mini",
                "searchDelay"   : 350,
                "bPaginate"     : true,
                "bAutoWidth"    : false,
                "bFilter"       : true,
                "bProcessing"   : true,
                "sServerMethod" : "POST",
                "bServerSide"   : true,
                "sAjaxSource"   : xagio_data.wp_post,
                "iDisplayLength": 100,
                "aaSorting"     : [
                    [
                        0,
                        'desc'
                    ]
                ],
                "aoColumns"     : [
                    {
                        "sClass"   : "text-left",
                        "bSortable": true,
                        "mData"    : 'ID',
                        "mRender"  : function (data, type, row) {
                            if (row.post_title == '') row.post_title = '<i>– Unnamed –</i>';

                            let attached = ((row.attached !== false && row.attached != 0) ? '<img title="There is a Project Planner group attached to this page/post." class="v3-image-table" src="' + xagio_data.plugins_url + 'assets/img/logo-menu-xagio.webp"/>' : '');

                            return `<div class="table-row-silo-pages">
                                        <h2>${row.post_title} ${attached}</h2>
                                        <div class="xagio-flex-space-between">
                                            <div class="post-status">${row.post_status.charAt(0).toUpperCase() + row.post_status.slice(1)}ed</div>
                                            <div class="post-date">${new Date(row.post_date).toUTCString().split(' ').splice(0, 4).join(' ')}</div>
                                        </div>
                                        <div class="table-row-actions">
                                            <a href='${row.guid}' target='_blank' class='view'><i class='xagio-icon xagio-icon-search'></i></a>
                                            <a href='${xagio_data.wp_admin}post.php?post=${row.ID}&action=edit' target='_blank' class='edit'><i class='xagio-icon xagio-icon-edit'></i></a>
                                            <a href='#' class='silo-trash' data-id='${row.ID}' data-text='${row.post_title}' data-type='page'><i class='xagio-icon xagio-icon-delete'></i></a>
                                            <a href='#' class='silo-add' data-permalink='${row.permalink}' data-attached='${((row.attached === false || row.attached == 0) ? 0 : row.attached)}' data-id='${row.ID}' data-text='${row.post_title}' data-type='page'><i class='xagio-icon xagio-icon-arrows'></i></a>
                                        </div>
                                    </div>`;
                        }
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
                    $(nRow).addClass('draggable-row').attr('data-type', 'page').attr('data-text', aData.post_title).attr('data-attached', (aData.attached === false || aData.attached == 0) ? 0 : aData.attached).attr('data-permalink', aData.permalink).attr('data-xagio-tooltip', '').attr('data-xagio-title', 'You can drag this element directly into the SILO Builder.').attr('data-id', aData.ID);
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
                    infoFiltered     : ""
                },
                "dom"           : '<f>rt<"xagio-table-bottom"<>p>',
                "bDestroy"      : true,
                "searchDelay"   : 350,
                "bPaginate"     : true,
                "bAutoWidth"    : false,
                "bFilter"       : true,
                "bProcessing"   : true,
                "sServerMethod" : "POST",
                "bServerSide"   : true,
                "sAjaxSource"   : xagio_data.wp_post,
                "iDisplayLength": 100,
                "aoColumns"     : [
                    {
                        "sClass"   : "text-left",
                        "bSortable": true,
                        "mData"    : 'post_title',
                        "mRender"  : function (data, type, row) {
                            if (data == '') data = '<i>– Unnamed –</i>';
                            let attached = ((row.attached !== false && row.attached !== 0) ? '<img title="There is a Project Planner group attached to this page/post." class="v3-image-table" src="' + xagio_data.plugins_url + 'assets/img/logo-menu-xagio.webp"/>' : '');

                            return `<div class="table-row-silo-pages">
                                        <h2>${data} ${attached}</h2>
                                        <div class="xagio-flex-space-between">
                                            <div class="post-status">${row.post_status.charAt(0).toUpperCase() + row.post_status.slice(1)}ed</div>
                                            <div class="post-date">${new Date(row.post_date).toUTCString().split(' ').splice(0, 4).join(' ')}</div>
                                        </div>
                                        <div class="table-row-actions">
                                            <a href='${row.guid}' target='_blank' class='view'><i class='xagio-icon xagio-icon-search'></i></a>
                                            <a href='${xagio_data.wp_admin}post.php?post=${row.ID}&action=edit' target='_blank' class='edit'><i class='xagio-icon xagio-icon-edit'></i></a>
                                            <a href='#' class='silo-trash' data-id='${row.ID}' data-text='${data}' data-type='page'><i class='xagio-icon xagio-icon-delete'></i></a>
                                            <a href='#' class='silo-add' data-permalink='${row.permalink}' data-attached='${((row.attached === false || row.attached == 0) ? 0 : row.attached)}' data-id='${row.ID}' data-text='${data}' data-type='page'><i class='xagio-icon xagio-icon-arrows'></i></a>
                                        </div>
                                    </div>`;

                            return "<b class='post-title'>" + data + "</b>" + ((row.attached !== false && row.attached !== 0) ? '<img title="There is a Project Planner group attached to this page/post." class="v3-image-table" src="' + xagio_data.plugins_url + 'assets/img/logo-menu-xagio.webp"/>' : '')

                                   + '<div class="uk-clearfix uk-margin-small-top"></div>'

                                   + '<b class="uk-float-left uk-display-block post-status">' + row.post_status.charAt(0).toUpperCase() + row.post_status.slice(1) + 'ed</b>' + '<abbr class="uk-float-right uk-display-block" title="' + row.post_date + '">' + new Date(row.post_date).toUTCString().split(' ').splice(0, 4).join(' ') + '</abbr>'

                                   + '<div class="uk-clearfix"></div>'

                                   + "<div class='row-actions'>"

                                   + "<a href='" + row.guid + "' target='_blank' class='view'><i class='xagio-icon xagio-icon-search'></i></a>"

                                   + " <span>|</span> "

                                   + "<a href='" + xagio_data.wp_admin + 'post.php?post=' + row.ID + '&action=edit' + "' target='_blank' class='edit'><i class='xagio-icon xagio-icon-edit'></i></a>"

                                   + " <span>|</span> "

                                   + "<a href='#' class='silo-trash' data-id='" + row.ID + "' data-text='" + data + "' data-type='page'><i class='xagio-icon xagio-icon-delete uk-text-danger'></i></a>"

                                   + " <span>|</span> "

                                   + "<a href='#' class='silo-add' data-attached='" + ((row.attached === false || row.attached == 0) ? 0 : row.attached) + "' data-id='" + row.ID + "' data-text='" + data + "' data-type='post'><i class='xagio-icon xagio-icon-arrows'></i> Add</a>"

                                   + "</div>";
                        }
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
                    $(nRow).addClass('draggable-row').attr('data-type', 'post').attr('data-text', aData.post_title).attr('data-attached', (aData.attached === false || aData.attached == 0) ? 0 : aData.attached).attr('data-permalink', aData.permalink).attr('data-xagio-tooltip', '').attr('data-xagio-title', 'You can drag this element directly into the SILO Builder.').attr('data-id', aData.ID);
                }

            });
            $('.siloPagesTableLinks').dataTable({
                language        : {
                    search           : "_INPUT_",
                    searchPlaceholder: "Search posts/pages...",
                    processing       : "Loading posts/pages...",
                    emptyTable       : "No pages/posts found on this website.",
                    info             : "_START_ to _END_ of _TOTAL_ posts/pages",
                    infoEmpty        : "0 to 0 of 0 posts/pages",
                    infoFiltered     : ""
                },
                "dom"           : '<f>rt<"xagio-table-bottom"<>p>',
                "bDestroy"      : true,
                "searchDelay"   : 350,
                "bPaginate"     : true,
                "bAutoWidth"    : false,
                "bFilter"       : true,
                "bProcessing"   : true,
                "sServerMethod" : "POST",
                "bServerSide"   : true,
                "sAjaxSource"   : xagio_data.wp_post,
                "iDisplayLength": 100,
                "aoColumns"     : [
                    {
                        "sClass"   : "text-left",
                        "bSortable": true,
                        "mData"    : 'post_title',
                        "mRender"  : function (data, type, row) {
                            if (data == '') data = '<i>– Unnamed –</i>';

                            let attached = ((row.attached !== false && row.attached != 0) ? '<img title="There is a Project Planner group attached to this page/post." class="v3-image-table" src="' + xagio_data.plugins_url + 'assets/img/logo-menu-xagio.webp"/>' : '');

                            return `<div class="table-row-silo-pages">
                                        <h2>${data} ${attached}</h2>
                                        <div class="xagio-flex-space-between">
                                            <div class="post-status">${row.post_status.charAt(0).toUpperCase() + row.post_status.slice(1)}ed</div>
                                            <div class="post-date">${new Date(row.post_date).toUTCString().split(' ').splice(0, 4).join(' ')}</div>
                                        </div>
                                        <div class="table-row-actions">
                                            <a href='#' class='silo-add-links' data-permalink='${row.permalink}' data-attached='${((row.attached === false || row.attached == 0) ? 0 : row.attached)}' data-id='${row.ID}' data-text='${data}' data-type='page'><i class='xagio-icon xagio-icon-branch'></i></a>
                                            <a href='${row.guid}' target='_blank' class='view'><i class='xagio-icon xagio-icon-search'></i></a>
                                            <a href='${xagio_data.wp_admin}post.php?post=${row.ID}&action=edit' target='_blank' class='edit'><i class='xagio-icon xagio-icon-edit'></i></a>
                                            <a href='#' class='silo-trash' data-id='${row.ID}' data-text='${data}' data-type='page'><i class='xagio-icon xagio-icon-delete'></i></a>
                                        </div>
                                    </div>`;
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

                }

            });

        },

        // Save Silo functionality
        saveSilo: function () {
            $(document).on('click', '.silo-save', function (e) {
                e.preventDefault();

                let flowchart = actions.siloGetFlowchart();
                let name      = actions.siloGetName(flowchart);
                let type      = actions.siloGetType(flowchart);

                let fwData = flowchart.flowchart('getData');
                $.post(xagio_data.wp_post, {
                    action: 'xagio_save_silo',
                    name  : name,
                    type  : type,
                    silo  : JSON.stringify(fwData)
                }, function (d) {
                    if (d.status == 'success') {
                        xagioNotify("success", "SILO has been successfully saved.")
                        actions.loadSilo();

                        // Load the SILO Ids
                        for (let type in d.data) {

                            siloUsedIDs[type] = d.data[type];

                        }
                    }
                });


            });
        }

    };

})(jQuery);
