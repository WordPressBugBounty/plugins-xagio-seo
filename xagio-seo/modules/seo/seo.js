let target_keyword = '';
let aiStatusTimeout = null;
let aiSchemaStatusTimeout = null;
let isBlockEditor = false;


(function ($) {
    'use strict';

    $(document).ready(function () {
        if ($('body.block-editor-page').length > 0) isBlockEditor = true;

        if (actions.allowedToRun()) {
            helpers();
            search_preview.init();
            ai_content.init();
            scripts.init();
            social.init();
            actions.init();
            schemas.init();
            blocks.init();
        } else {
            global.init();
        }
    });

    let notify = function (status, msg) {
        xagioNotify(status == 'success' ? 'success' : 'danger', msg);
    };

    let global = {
        init: function () {
            actions.initSliders();
            actions.addCustomBulkAction();
        }
    };

    let helpers = function () {

        $.tablesorter.addParser({
                                    id    : "fancyNumber",
                                    is    : function (s) {
                                        return false;
                                    },
                                    format: function (s) {
                                        return $.tablesorter.formatFloat(s.replace(/,/g, ''));
                                    },
                                    type  : "numeric"
                                });

        String.prototype.isJSON = function () {
            var json;
            try {
                json = JSON.parse(this);
            } catch (e) {
                return this;
            }
            return json;
        };

        $.fn.unhighlight = function (options) {
            var settings = {
                className: 'highlightCloud',
                element  : 'span'
            };
            jQuery.extend(settings, options);

            return this.find(settings.element + "." + settings.className).each(function () {
                var parent = this.parentNode;
                parent.replaceChild(this.firstChild, this);
                parent.normalize();
            }).end();
        };

        $.fn.disable = function (message) {
            return this.each(function () {
                var i = $(this).find('i');
                if (typeof $(this).attr('disabled') == 'undefined') {
                    if (i.length > 0) {
                        i.attr('class-backup', i.attr('class'));
                        i.attr('class', 'xagio-icon xagio-icon-sync xagio-icon-spin');
                    }
                    if (typeof message != 'undefined') {
                        $(this).attr('text-backup', $(this).text());
                        $(this).text(' ' + message);
                        $(this).prepend(i);
                    }
                    $(this).attr('disabled', 'disabled');
                } else {
                    $(this).removeAttr('disabled');
                    if (i.length > 0) i.attr('class', i.attr('class-backup'));
                    if (typeof $(this).attr('text-backup') != 'undefined') {
                        $(this).text(' ' + $(this).attr('text-backup'));
                        $(this).prepend(i);
                    }
                }
            });
        };
    };

    var average_prices = null;
    let ai_content = {
        init               : function () {
            ai_content.loadContentHistory();
            ai_content.loadSchemaStatus();
            ai_content.generateSchema();
            ai_content.generateContent();
            ai_content.changeContent();
            ai_content.insertContent();
            ai_content.makeRequest();
            ai_content.changeAiPrompt();
        },
        makeRequest        : function () {
            $(document).on('click', '.makeAiRequest', function (e) {
                e.preventDefault();
                let btn = $(this);
                let target = btn.attr('data-target');
                $('body').append(`<button class="${target}" type="button">...</b`);
                $('.' + target).trigger('click').remove();
                $('#aiPrice')[0].close();
            });
        },
        loadSchemaStatus   : function () {
            let post_id = $('#xagio_post_id').val();
            let btn = $('.confirmGenerateAiSchema');
            $.post(xagio_data.wp_post, `action=xagio_get_ai_schema_history&post_id=${post_id}`, function (d) {
                let history = d.data;

                // If at least one request is running, check history every few seconds
                let checkHistory = false;

                for (let i = 0; i < history.length; i++) {
                    let row = history[i];

                    if (row['status'] === 'running') {
                        checkHistory = true;
                    }

                }

                if (checkHistory) {
                    // disable button and add spinner
                    btn.attr('disabled', 'disabled');
                    btn.html('<i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Generating Schema...');
                    ai_content.checkAiSchemaStatus("SCHEMA", post_id);
                }

            });
        },
        loadContentHistory : function () {
            $.post(xagio_data.wp_post, `action=xagio_get_ai_history&post_id=${$('#xagio_post_id').val()}`, function (d) {
                let history = d.data;

                // If at least one request is running, check history every few seconds
                let checkHistory = false;

                let ul = '<ul class="aiHistory">';

                for (let i = 0; i < history.length; i++) {
                    let row = history[i];
                    let disabled = "";
                    let running = "xagio-icon-clock";
                    let small = row['small'];

                    if (row['status'] === 'running') {
                        disabled = "disabled";
                        running = "xagio-icon-sync xagio-icon-spin";
                        small = "Generating AI Content...";
                        checkHistory = true;
                    }

                    if (i === 0 && row['status'] !== 'running') {
                        let output = row['output'];
                        output = output.replace(/\n/ig, "");
                        output = output.replace(/\r/ig, "");

                        $('.insertAiContent').show();
                        setTimeout(function () {
                            tinymce.editors['aiContentEditor'].setContent(output);
                        }, 1000);
                    }

                    let num = history.length - i;

                    ul += `<li class="aiHistoryItem ${i === 0 ? 'active' : ''}" data-id="${row['id']}" ${disabled}>
<div class="aiHistoryHead xagio-margin-bottom-medium">
<span><i class="xagio-icon ${running}"></i> #${num}</span>
<span class="aiHistoryDate">${row['date_created']}</span>
</div>
<span class="aiHistoryText">${small}</span>
</li>`;

                }

                ul += '</ul>';

                $('.aiHistoryHolder').html(ul);

                if (checkHistory) {
                    ai_content.checkAiStatus("PAGE_CONTENT", $('#xagio_post_id').val());
                }

            });
        },
        insertContent      : function () {
            $(document).on('click', '.insertAiContent', function (e) {
                e.preventDefault();
                let output = tinymce.editors['aiContentEditor'].getContent();


                // if guteberg
                if (search_preview.isGuteberg()) {
                    wp.data.dispatch('core/editor').resetBlocks(wp.blocks.parse(output));
                } else {
                    tinymce.editors['content'].execCommand('mceInsertContent', false, output);
                }

                $([
                      document.documentElement,
                      document.body
                  ]).animate({
                                 scrollTop: $(".wrap").offset().top + 200
                             }, 500);
            });
        },
        changeContent      : function () {
            $(document).on('click', '.aiHistoryItem:not([disabled])', function (e) {
                e.preventDefault();
                let li = $(this);
                let id = $(this).data('id');

                li.find('i').removeClass('xagio-icon-clock').addClass('xagio-icon-sync xagio-icon-spin');
                $('.aiHistoryItem').removeClass('active');

                li.addClass('active');
                $.post(xagio_data.wp_post, `action=xagio_get_ai_history&post_id=${$('#xagio_post_id').val()}&row_id=${id}`, function (d) {
                    let history = d.data;

                    if (history.hasOwnProperty(0)) {
                        let output = history[0]['output'];
                        output = output.replace(/\n/ig, "");
                        output = output.replace(/\r/ig, "");
                        tinymce.editors['aiContentEditor'].setContent(output);
                    }

                    li.find('i').removeClass('xagio-icon-sync xagio-icon-spin').addClass('xagio-icon-clock');
                });

            });
        },
        checkAiStatus      : function (input, target_id) {
            clearTimeout(aiStatusTimeout);
            aiStatusTimeout = setTimeout(function () {
                $.post(xagio_data.wp_post, `action=xagio_ai_output&input=${input}&target_id=${target_id}`, (d) => {
                    let status = d.status;
                    if (status === 'running') {
                        ai_content.checkAiStatus(input, target_id);
                    } else {
                        // Load History
                        let btn = $('.confirmGenerateAiContent');
                        btn.attr('disabled', false);
                        btn.html('<i class="xagio-icon xagio-icon-save"></i> Generate');
                        $('.insertAiContent').show();
                        ai_content.loadContentHistory();
                    }
                });
            }, 4000);
        },
        checkAiSchemaStatus: function (input, target_id) {
            aiSchemaStatusTimeout = setTimeout(function () {
                $.post(xagio_data.wp_post, `action=xagio_ai_output&input=${input}&target_id=${target_id}`, (d) => {
                    let status = d.status;
                    if (status === 'running') {
                        ai_content.checkAiSchemaStatus(input, target_id);

                    } else {
                        clearTimeout(aiSchemaStatusTimeout);
                        // Load History
                        let btn = $('.confirmGenerateAiSchema');

                        btn.attr('disabled', false);
                        btn.html('<i class="xagio-icon xagio-icon-save"></i>  Generate AI Schema');
                        schemas.loadPerPageSchema();
                    }
                });
            }, 4000);
        },
        generateSchema     : function () {
            $(document).on('click', '.confirmGenerateAiSchema', function (e) {
                e.preventDefault();
                let btn = $(this);

                ai_content.openAveragePrices(btn, "AI Generated JSON Schema", "SCHEMA", "generateAiSchema");

            });
            $(document).on('click', '.generateAiSchema', function (e) {
                let btn = $('.confirmGenerateAiSchema');

                let h1 = search_preview.getH1();
                let title = search_preview.calculations.shared_functions.getTitle();
                let description = search_preview.calculations.shared_functions.getDescription();
                let schema = 'creative';

                let data = [
                    {
                        'name' : 'h1',
                        'value': h1
                    },
                    {
                        'name' : 'title',
                        'value': title
                    },
                    {
                        'name' : 'description',
                        'value': description
                    },
                    {
                        'name' : 'schema',
                        'value': schema
                    },
                    {
                        'name' : 'action',
                        'value': 'xagio_ai_schema'
                    },
                    {
                        'name' : 'post_id',
                        'value': $('#xagio_post_id').val()
                    },
                    {
                        'name' : 'prompt_id',
                        'value': $('#prompt_id').val()
                    }
                ];

                // if title/desc/h1 is empty, don't generate content, show error
                if (title == '' || description == '') {
                    xagioNotify("danger", "Xagio SEO Title & Xagio SEO Description is necessary to be filled in order to generate AI Content.", true);
                    // scroll to element
                    $('html, body').animate({
                                                scrollTop: $('#prs-title').offset().top
                                            }, 500);
                    return false;
                }

                if (h1 == '') {
                    xagioNotify("danger", "Post Title (aka H1) is necessary to be filled in order to generate AI Content.", true);
                    // scroll to top
                    $('html, body').animate({
                                                scrollTop: 0
                                            }, 500);
                    return false;
                }

                // disable button and add spinner
                btn.disable();

                $.post(xagio_data.wp_post, data, function (d) {

                    if (d.status == 'upgrade') {
                        // show aiUpgrade modal
                        $('#aiUpgrade')[0].showModal();
                        return;
                    }

                    xagioNotify(d.status, d.message, true);

                    if (d.status == 'error') {
                        btn.disable();
                        return;
                    }


                    ai_content.checkAiSchemaStatus("SCHEMA", $('#xagio_post_id').val());
                });

            });
        },
        changeAiPrompt     : function () {
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
        },
        openAveragePrices  : function (btn, title, input, target) {
            btn.disable();

            $.post(xagio_data.wp_post, `action=xagio_ai_get_average_prices`, function (d) {
                btn.disable();

                if (d.status == 'error') {
                    xagioNotify('danger', 'There was a problem establishing connection, please contact Support.');
                    return;
                }

                let defaultPrompt = null;
                let prompt_id = $('#aiPrice').find('#prompt_id');
                prompt_id.empty();

                if (average_prices == null) {
                    average_prices = d.data.average_prices;
                }

                for (let i = 0; i < average_prices[input].length; i++) {
                    const pagecontentElement = average_prices[input][i];
                    prompt_id.append(`<option value="${pagecontentElement.id}">${pagecontentElement.title}</option>`)
                    if (pagecontentElement.default) {
                        defaultPrompt = pagecontentElement;
                        prompt_id.val(pagecontentElement.id);
                    }
                }

                $('#aiPrice').find('.input-name').html(title);
                $('#aiPrice').find('.average-price').html(parseFloat(defaultPrompt.price.toFixed(3)));
                $('#aiPrice').find('.ai-credits').html(parseFloat(d.data.credits.toFixed(3)));
                $('#aiPrice').find('.makeAiRequest').attr('data-target', target);
                $('#aiPrice').attr('data-target', input);

                $('#aiPrice')[0].showModal();

                actions.refreshXags();
            });
        },
        generateContent    : function () {
            $(document).on('click', '.confirmGenerateAiContent', function (e) {
                e.preventDefault();
                let btn = $(this);

                ai_content.openAveragePrices(btn, 'AI Generated Content', "PAGE_CONTENT", "generateAiContent");

            });
            $(document).on('click', '.generateAiContent', function (e) {
                let btn = $('.confirmGenerateAiContent');

                let price = $('#aiPrice').find('.average-price').html();
                let credits = $('#aiPrice').find('.ai-credits').html();

                if (credits < price) {
                    xagioNotify("danger", "You do not have enough AI Credits, please top up and try again!");
                    return;
                }

                let h1 = search_preview.getH1();
                let title = search_preview.calculations.shared_functions.getTitle();
                let description = search_preview.calculations.shared_functions.getDescription();

                let style = $('#ai-writing-style option:selected').val();
                let tone = $('#ai-writing-tone option:selected').val();

                let data = [
                    {
                        'name' : 'action',
                        'value': 'xagio_ai_content'
                    },
                    {
                        'name' : 'h1',
                        'value': h1
                    },
                    {
                        'name' : 'title',
                        'value': title
                    },
                    {
                        'name' : 'description',
                        'value': description
                    },
                    {
                        'name' : 'content_style',
                        'value': style
                    },
                    {
                        'name' : 'content_tone',
                        'value': tone
                    },
                    {
                        'name' : 'post_id',
                        'value': $('#xagio_post_id').val()
                    },
                    {
                        'name' : 'prompt_id',
                        'value': $('#prompt_id').val()
                    }
                ];

                // if title/desc/h1 is empty, don't generate content, show error
                if (title == '' || description == '') {
                    xagioNotify("danger", "Xagio SEO Title & Xagio SEO Description is necessary to be filled in order to generate AI Content.", true);
                    // scroll to element
                    $('html, body').animate({
                                                scrollTop: $('#prs-title').offset().top
                                            }, 500);
                    return false;
                }

                if (h1 == '') {
                    xagioNotify("danger", "Post Title (aka H1) is necessary to be filled in order to generate AI Content.", true);
                    // scroll to top
                    $('html, body').animate({
                                                scrollTop: 0
                                            }, 500);
                    return false;
                }

                // disable button and add spinner
                btn.disable();

                $.post(xagio_data.wp_post, data, function (d) {

                    if (d.status == 'upgrade') {
                        // show aiUpgrade modal
                        $('#aiUpgrade')[0].showModal();
                        return;
                    }

                    xagioNotify(d.status, d.message, true);

                    if (d.status == 'error') {
                        btn.disable();
                        return;
                    }

                    ai_content.loadContentHistory();
                    ai_content.checkAiStatus("PAGE_CONTENT", $('#xagio_post_id').val());
                });

            });
        }
    };

    let scripts = {
        editorHeader  : null,
        editorBody    : null,
        editorFooter  : null,
        init          : function () {
            scripts.initEditors();
            scripts.saveEditors();
        },
        refreshEditors: function () {
            cm_settings.e1.codemirror.refresh();
            cm_settings.e2.codemirror.refresh();
            cm_settings.e3.codemirror.refresh();
        },
        initEditors   : function () {
            cm_settings.codeEditor.codemirror.autoRefresh = true;

            cm_settings.e1 = wp.codeEditor.initialize($('[name="XAGIO_SEO_SCRIPTS_HEADER"]'), cm_settings);
            cm_settings.e2 = wp.codeEditor.initialize($('[name="XAGIO_SEO_SCRIPTS_BODY"]'), cm_settings);
            cm_settings.e3 = wp.codeEditor.initialize($('[name="XAGIO_SEO_SCRIPTS_FOOTER"]'), cm_settings);

            $(document).on('click', '.xagio-tab > li', function (e) {
                setTimeout(function () {
                    scripts.refreshEditors();
                }, 100);
            });
        },
        saveEditors   : function () {
            if (isBlockEditor) {
                wp.data.subscribe(function () {
                    cm_settings.e1.codemirror.save();
                    cm_settings.e2.codemirror.save();
                    cm_settings.e3.codemirror.save();
                });
            }
        }
    };

    let blocks = {
        init    : function () {
            blocks.input();
            blocks.dropdown();
        },
        dropdown: function () {
            $(document).on('click', '.xagio-blocks-button', function (e) {
                e.preventDefault();
                $(this).find('.xagio-blocks').toggle();
                $(this).find('.button').find('i').toggleClass('xagio-icon-arrow-down xagio-icon-arrow-left')
            });

            $(document).on('click', '.xagio-blocks-search', function (e) {
                e.stopPropagation();
            });

            $(document).on('click', '.xagio-blocks-data li .icon', function (e) {
                let shortcode = $(this).parents('li').attr('data-shortcode');

                // get the parent .xagio-blocks-button
                let button = $(this).parents('.xagio-blocks-button');

                // append text to the editor
                let editor = button.next().next();
                editor.append(' ' + shortcode);
                editor.trigger('input');
            });

            let searchTimeout = null;
            $(document).on('keydown', '.xagio-blocks-search', function (e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    let search = $('.xagio-blocks-search').val().toLowerCase();
                    $('.xagio-blocks-data').find('li').each(function () {
                        let name = $(this).find('.name').text().toLowerCase();
                        if (name.includes(search)) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }, 300);
            });

            $('.xagio-blocks-data').each(function () {

                for (let block in xagio_replaces) {
                    let item = $('<li></li>');
                    item.attr('data-shortcode', '{' + block + '}');
                    item.append('<div class="icon"><i class="xagio-icon xagio-icon-arrow-left"></i></div>');
                    item.append('<div class="text"><span class="name">' + xagio_replaces[block]['name'] +
                                '</span><span class="desc">' + xagio_replaces[block]['desc'] + '</span></div>');
                    $(this).append(item);
                }

            });

        },
        input   : function () {
            let allowedShortcodes = [];

            // xagio_replaces is a key value pair of the shortcode and the replacement
            if (typeof xagio_replaces !== 'undefined') {
                allowedShortcodes = Object.keys(xagio_replaces);
            }

            $('div.xagio-editor[contenteditable="true"]').on('input', function () {

                const regexPattern = '\\{(' + allowedShortcodes.join('|') + ')\\}';
                const regex = new RegExp(regexPattern, 'g');

                let content = $(this).html();

                const newContent = content.replace(regex, function (match, shortcode) {

                    // generate random unique id
                    let xagio_render_blocks = "ID-" + Math.random().toString(36).substring(2, 15) +
                                              Math.random().toString(36).substring(2, 15);

                    $.post(xagio_data.wp_post, {
                        action: "xagio_render_blocks",
                        html  : match,
                        page  : $('#xagio_post_id').val()
                    })
                     .done(function (response) {
                         if (response.status == 'success') {
                             $("div.xagio-block#" +
                               xagio_render_blocks).attr('data-render', response.data).trigger('input');
                         }
                     });

                    return '<div contenteditable="false" id="' + xagio_render_blocks +
                           '" class="xagio-block"  data-render="{ ' + shortcode + ' }" data-shortcode="' + shortcode +
                           '" data-display="' + xagio_replaces[shortcode].name + '"></div>';

                });

                if (content !== newContent) {
                    $(this).html(newContent);
                    this.focus();

                    if (typeof window.getSelection != "undefined" && typeof document.createRange != "undefined") {
                        var range = document.createRange();
                        range.selectNodeContents(this);
                        range.collapse(false);
                        var sel = window.getSelection();
                        sel.removeAllRanges();
                        sel.addRange(range);
                    } else if (typeof document.body.createTextRange != "undefined") {
                        var textRange = document.body.createTextRange();
                        textRange.moveToElementText(this);
                        textRange.collapse(false);
                        textRange.select();
                    }
                }
            }).on('keydown', function (e) {
                // Check if the backspace key is pressed
                if (e.key === 'Backspace') {
                    var selection = window.getSelection();
                    // Check if the cursor is at the start of the contenteditable element or if the range is collapsed
                    if (selection.rangeCount > 0) {
                        var range = selection.getRangeAt(0);
                        var precedingNode = range.startContainer;

                        // If the selection is collapsed and at the start, check the preceding node
                        if (range.collapsed && range.startOffset === 0) {
                            // If the preceding node is a text node, get the previous sibling element
                            if (precedingNode.nodeType === 3) {
                                precedingNode = precedingNode.previousSibling;
                            } else if (precedingNode.nodeType === 1 && range.startOffset === 0) {
                                // If the cursor is within an element, step out to check the previous sibling element
                                precedingNode = precedingNode.parentNode.previousSibling;
                            }

                            // Check if the preceding node is a .xagio-block element
                            if ($(precedingNode).hasClass('xagio-block')) {
                                e.preventDefault(); // Prevent default backspace behavior
                                $(precedingNode).remove(); // Remove the .xagio-block element
                            }
                        }
                    }
                }
            }).trigger('input');

        }
    };

    let schemas = {
        init                          : function () {
            schemas.validateSchema();
            schemas.renderSchema();
            schemas.loadPerPageSchema();
            schemas.assignSchema();
            schemas.searchSchemas();
            schemas.loadSchemaGroups();
            schemas.toggleSchemaTypeContainerAll();
            schemas.toggleSchemaTypeContainer();
            schemas.schemaWizard.init();
        },
        searchSchemas                 : function () {

            let schemaType = $(".manage-schema-types");
            let schemaGroup = $(".manage-schema-groups");
            let schemaSearch = $(".manage-schema-search");

            schemaSearch.keyup(function () {

                let element = $(this);
                let container = $('.localSchemas');
                let schemaSearch = element.val().trim();
                container.find('.schema-tag').each(function () {

                    let current_schema_name = $(this).find('.schema-name').text().trim();
                    let current_schema_type = $(this).attr('data-type');
                    let current_schema_group = $(this).attr('data-group');

                    if (schemaSearch == '') {
                        if (schemaType.val() != '') {
                            if (schemaType.val() == current_schema_type) {
                                if (schemaGroup.val() != '') {
                                    if (schemaGroup.val() == current_schema_group) {
                                        $(this).show();
                                    } else {
                                        $(this).hide();
                                    }
                                } else {
                                    $(this).show();
                                }
                            } else {
                                $(this).hide();
                            }
                        } else {
                            if (schemaGroup.val() != '') {
                                if (schemaGroup.val() == current_schema_group) {
                                    $(this).show();
                                } else {
                                    $(this).hide();
                                }
                            } else {
                                $(this).show();
                            }
                        }
                    } else {
                        if (current_schema_name.toLowerCase().includes(schemaSearch.toLowerCase())) {
                            if (schemaType.val() != '') {
                                if (schemaType.val() == current_schema_type) {
                                    if (schemaGroup.val() != '') {
                                        if (schemaGroup.val() == current_schema_group) {
                                            $(this).show();
                                        } else {
                                            $(this).hide();
                                        }
                                    } else {
                                        $(this).show();
                                    }
                                } else {
                                    $(this).hide();
                                }
                            } else {
                                if (schemaGroup.val() != '') {
                                    if (schemaGroup.val() == current_schema_group) {
                                        $(this).show();
                                    } else {
                                        $(this).hide();
                                    }
                                } else {
                                    $(this).show();
                                }
                            }
                        } else {
                            $(this).hide();
                        }
                    }
                });
                container.find('.no-schema').remove();

                let count = 0;

                let all_hidden = true;
                container.find('.schema-type-container').each(function () {

                    let all_hidden_inside = true;
                    $(this).find('.schema-tag').each(function () {

                        if (!$(this).eq(0)[0].hasAttribute('style')) {
                            count++;
                            all_hidden_inside = false;
                        } else {
                            if ($(this).css('display') == 'flex') {
                                count++;
                                all_hidden_inside = false;
                            }
                        }
                    });

                    if (all_hidden_inside === true) {
                        $(this).hide();
                    } else {
                        $(this).show();
                        all_hidden = false;
                    }

                });

                $('.schema-count').html(count);

                if (all_hidden === true) {

                    // No Schemas
                    let template = $('.schema-loading.template').clone();
                    template.removeClass('template');
                    template.addClass('no-schema');
                    template.find('i').removeClass('xagio-icon-sync').removeClass('xagio-icon-spin').addClass('xagio-icon-warning');
                    template.find('p').html('No results were found for the requested search query.');
                    container.append(template);

                }
            });

            schemaGroup.change(function () {
                $(".manage-schema-search").trigger('keyup');
            });

            schemaType.change(function () {
                $(".manage-schema-search").trigger('keyup');
            });

        },
        generateAssignedSchemaTemplate: function (schema) {
            let template = '';
            template += '<tr><td class="schemaName">' + '<input type="hidden" class="schemaID" value="' + schema.id +
                        '"/> ' + schema.name + '</td>';
            template += '<td class="schemaType">' + schema.type + '</td>';
            template += '<td class="schemaButton">' +
                        '<button type="button" class="uk-button uk-button-mini uk-button-primary selectSchema"><i class="xagio-icon xagio-icon-check"></i> Assign</button>' +
                        '</td>';
            template += '</tr>';
            return template;

        },
        loadSchemaGroups              : function () {
            let schemaGroups = $('.manage-schema-groups');
            let defaultGroup = schemaGroups.data('default-group');
            $.post(xagio_data.wp_post, 'action=xagio_get_remote_schema_groups', function (d) {
                if (d.hasOwnProperty('data')) {
                    if (d.data !== null) {
                        for (let i = 0; i < d.data.length; i++) {
                            let group = d.data[i];
                            if (group.name === defaultGroup) {
                                schemaGroups.append('<option selected value="' + group.id + '">' + group.name +
                                                    '</option>');
                            } else {
                                schemaGroups.append('<option value="' + group.id + '">' + group.name + '</option>');
                            }
                        }
                    } else {
                        xagioNotify(d.status, d.message);
                    }
                }

                schemas.loadRemoteSchemas();
            });

        },
        loadRemoteSchemas             : function () {

            let container = $('.localSchemas');
            let types = {};
            let output = [];

            let template = $('.schema-loading.template').clone();
            template.removeClass('template');
            container.empty().append(template);

            $.post(xagio_data.wp_post, 'action=xagio_get_remote_schema', function (d) {

                d = d.data;

                for (let group_id in d) {

                    if (d.hasOwnProperty(group_id)) {

                        for (let type in d[group_id]) {

                            // Insert the new type if it doesn't exist
                            if (!types.hasOwnProperty(type)) {
                                types[type] = [];
                                $('.manage-schema-types').append('<option value="' + type + '">' + type + '</option>');
                            }

                            if (d[group_id].hasOwnProperty(type)) {

                                for (let id in d[group_id][type]) {

                                    if (d[group_id][type].hasOwnProperty(id)) {

                                        let name = d[group_id][type][id].name;

                                        let group = $('.manage-schema-groups').find('option[value="' + group_id +
                                                                                    '"]').text();

                                        let template = $('.schema-tag.template').clone();
                                        template.removeClass('template');
                                        template.attr('data-id', id);
                                        template.attr('data-type', type);
                                        template.attr('data-group', group_id);
                                        template.find('.schema-edit').attr('href', `https://app.xagio.net/schema?id=${id}&type=${type}&name=${name}&group=${group}`)
                                        template.find('.schema-name').html(name);

                                        // If added already
                                        if ($('.schemaTag[data-id="' + id + '"]').length > 0) {
                                            template.addClass('added');
                                        }

                                        types[type].push(template.clone());

                                    }

                                }
                            }

                        }

                    }

                }

                if (Object.keys(types).length > 0) {
                    for (let type in types) {
                        let template = $('.schema-type-container.template').clone();
                        template.removeClass('template');
                        template.attr('data-type', type);
                        template.find('.schema-type-container-name').html(type);
                        template.find('.schema-type-container-schemas').append(types[type]);

                        output.push(template);
                    }

                    container.empty().append(output);

                    $('.manage-schema-groups').trigger('change');

                } else {

                    // No Schemas
                    let template = $('.schema-loading.template').clone();
                    template.removeClass('template');
                    template.addClass('no-schema');
                    template.find('i').removeClass('xagio-icon-sync').removeClass('xagio-icon-spin').addClass('xagio-icon-info');
                    template.find('p').html('You do not have any created schemas. ');
                    container.empty().append(template);

                }

            });

        },
        loadPerPageSchema             : function () {
            let selected_input = $('#selectedSchemas');
            // let schema_holder  = $('.schemasForPage');
            let schema_holder = $('.assigned-schemas');
            schema_holder.empty().html(`<div class="schema-spinner"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></div>`);
            $.post(xagio_data.wp_post, `action=xagio_get_schemas&post_id=${$('#xagio_post_id').val()}`, function (d) {
                let schemas = d.data;

                if (schemas.length > 0) {
                    let schema_html = '<div class="assigned-schema-panel">';
                    let selected = '';
                    for (let i = 0; i < schemas.length; i++) {
                        let schema = schemas[i];
                        selected += schema['id'] + ',';
                        schema_html += `<div class="schemaTag" data-name="${schema['name']}" data-id="${schema['id']}">
                                            <div class="schema-name">
                                                <a title='Edit this Schema' href='https://app.xagio.net/schema?id=${schema['id']}&type=${schema['type']}&name=${schema['name']}&group=' target='_blank'>${schema['name']}</a>
                                            </div>
                                            <div class="schema-type">
                                                ${schema['type']}
                                            </div>
                                            <div class="removeSchemaTag" title="Unassign this schema from the current page.">
                                                <i class="xagio-icon xagio-icon-delete"></i>
                                            </div>
                                        </div>`;

                    }

                    schema_html += '</div>';
                    selected = selected.slice(0, -1);
                    selected_input.val(selected);
                    schema_holder.html(schema_html);
                } else {
                    schema_holder.html(`<p class="xagio-text-info noSchemas">You have no schema assigned to this page.</p>`);
                }
            });
        },
        assignSchema                  : function () {
            $(document).on('click', '#assignSchema', function () {
                let modal = $("#remoteSchemas");
                modal[0].showModal();
            });
            $(document).on('click', '.removeSchemaTag', function (e) {
                e.preventDefault();
                let tag = $(this).parents('.schemaTag');
                let id = tag.data('id');
                $('.schema-tag[data-id="' + id + '"]').removeClass('added');

                $(this).remove();
                tag.remove();

                let schemaIDs = [];
                $('.schemaTag').each(function () {
                    schemaIDs.push($(this).data('id'));
                });

                if (schemaIDs.length < 1) {
                    let schemasContainer = $('.assigned-schemas');
                    schemasContainer.append('<p class="xagio-text-info noSchemas">You have no schema assigned to this page.</p>');
                }

                let sElement = $('#selectedSchemas');
                sElement.val(schemaIDs.join(','));
            });

            $(document).on('click', '.schema-close', function (e) {
                e.preventDefault();
                let tag = $(this).parents('.schema-tag');
                let id = tag.data('id');
                tag.removeClass('added');

                let parent = $('.schemaTag[data-id="' + id + '"]');
                parent.remove();

                let schemaIDs = [];
                $('.schemaTag').each(function () {
                    schemaIDs.push($(this).data('id'));
                });

                if (schemaIDs.length < 1) {
                    let schemasContainer = $('.assigned-schemas');
                    schemasContainer.append('<p class="xagio-text-info noSchemas">You have no schema assigned to this page.</p>');
                }

                let sElement = $('#selectedSchemas');
                sElement.val(schemaIDs.join(','));
            });

            $(document).on('click', '.schema-add', function () {

                let tag = $(this).parents('.schema-tag');
                let schemaID = tag.data('id');
                let schemaName = tag.find('.schema-name').text().trim();
                let schemaType = tag.data('type');

                let schemasContainer = $('.assigned-schemas');
                if (schemasContainer.find('.noSchemas').length == 1) {
                    schemasContainer.empty();
                    schemasContainer.append('<div class="assigned-schema-panel"></div>');
                    schemasContainer = schemasContainer.find('.assigned-schema-panel');
                } else {
                    schemasContainer = schemasContainer.find('.assigned-schema-panel');
                }
                let alreadyAssigned = false;
                $('.schemaTag').each(function () {
                    if ($(this).data('id') == schemaID) {
                        alreadyAssigned = true;
                    }
                });
                if (alreadyAssigned) {
                    xagioNotify("danger", "This Schema has been already assigned for this page!");
                } else {
                    tag.addClass('added');
                    let sElement = $('#selectedSchemas');
                    let selectedSchemas = sElement.val();
                    if (selectedSchemas.length == 0) {
                        selectedSchemas = [];
                    } else {
                        selectedSchemas = selectedSchemas.split(',');
                    }
                    selectedSchemas.push(schemaID);
                    sElement.val(selectedSchemas.join(','));
                    let schemaNameContainer = `
                    <div class="schema-name">
                        <a title='Edit this Schema' href='https://app.xagio.net/schema?id=${schemaID}&type=${schemaType}&name=${schemaName}&group=' target='_blank'>${schemaName}</a>
                    </div>`;
                    schemasContainer.append(`
                                                <div class="schemaTag" data-name="${schemaName}" data-id="${schemaID}">
                                                    ${schemaNameContainer}
                                                    <div class="schema-type">
                                                        ${schemaType}
                                                    </div>
                                                    <div class="removeSchemaTag" title="Unassign this schema from the current page.">
                                                        <i class="xagio-icon xagio-icon-delete"></i>
                                                    </div>
                                                </div>
                                            `);

                    $('.editor-post-publish-button').trigger('click');
                }
            });
        },
        generateSchemaError           : function (error) {
            let type, template, message;
            let field = error.args.join(', ');
            if (error.errorType == 'MISSING_RECOMMENDED_FIELD') {
                message = ' is optional but should be in Schema.';
                type = 'warning';
            } else if (error.errorType == 'MISSING_FIELD_WITHOUT_TYPE') {
                message = ' is required and it needs to be set in Schema.';
                type = 'error';
            } else if (error.errorType == 'INVALID_OBJECT') {
                message = ' is required and it needs to be set in Schema.';
                type = 'error';
            } else if (error.errorType == 'EMPTY_FIELD_BODY') {
                message = ' cannot be empty in Schema.';
                type = 'error';
            } else if (error.errorType == 'ONE_OF_TWO_REQUIRED') {
                message = ' need to be together in Schema.';
                type = 'error';
            } else if (error.errorType == 'MULTIPLE_REVIEW_WITHOUT_AGGREGATE') {
                message = ' Multiple reviews should be accompanied by an aggregate rating.';
                type = 'error';
            } else if (error.errorType == 'MISSING_NAME_OF_REVIEWED_ITEM') {
                message = ' The review has no reviewed item specified.';
                type = 'error';
            } else if (error.errorType == 'JSON_PARSE_ERROR') {
                message = 'The structure data have Duplicate key found.';
                type = 'error';
            }

            if (type == 'error') {
                template = '<div class="schemaError problem"><i class="xagio-icon xagio-icon-warning"></i> <b>' +
                           field + '</b> ' + message + '</div>';
            } else {
                template = '<div class="schemaError warning"><i class="xagio-icon xagio-icon-info"></i> <b>' + field +
                           '</b> ' + message + '</div>';
            }
            return template;
        },
        generateSchemaMessage         : function (type, message, schemas) {
            let icon;
            if (type == 'error') {
                icon = 'warning';
            } else {
                icon = 'check';
            }
            let template = '<div class="schemaMessage schemaMessage-' + type + '"><i class="xagio-icon xagio-icon-' +
                           icon + '"></i> ' + message + '</div>';
            if (typeof schemas != 'undefined') {
                template += '<ul>';
                for (let i = 0; i < schemas.length; i++) {
                    template += '<li>' + schemas[i] + '</li>';
                }
                template += '</ul>';
            }
            return template;
        },
        renderSchema                  : function () {
            $('#renderSchema').click(function () {
                let button = $(this);
                let id = $(this).data('id');
                button.disable('Rendering Schema(s)...');
                $.post(xagio_data.wp_post, 'action=xagio_render_schema&id=' + id).done(function (d) {
                    button.disable();
                    if (d.status == 'error') {
                        xagioNotify("danger", d.message);
                    } else {
                        let renderedSchema = $('#renderedSchema').find('code');
                        renderedSchema.html('&lt;script type="application/ld+json"&gt;' + "\n" +
                                            JSON.stringify(d.data, null, 2) + "\n" + '&lt;/script&gt;');

                        let modal = $("#renderSchemasModal");
                        modal[0].showModal();
                    }
                });
            });
        },
        validateSchema                : function () {
            $('#validateSchema').click(function () {
                let button = $(this);
                let url = button.data('url');
                button.disable('Validating Schema(s)...');
                $.post(xagio_data.wp_post, 'action=xagio_validate_schema&url=' + url).done(function (d) {
                    button.disable();
                    if (d.status == 'error') {
                        xagioNotify("danger", d.message);
                    } else {
                        let schemaOutput = $('.schemaValidationOutput');
                        schemaOutput.empty();
                        if (d.data.numObjects > 0) {
                            if (d.data.errors.length > 0) {
                                let errors = d.data.errors;
                                for (let i = 0; i < errors.length; i++) {
                                    schemaOutput.append(schemas.generateSchemaError(errors[i]));
                                }
                            } else {
                                let schemasArray = [];
                                for (let i = 0; i < d.data.tripleGroups.length; i++) {
                                    let type = d.data.tripleGroups[i].type;
                                    if (type !== 'hentry') schemasArray.push(type);
                                }
                                if (schemasArray.length > 0) {
                                    schemaOutput.append(schemas.generateSchemaMessage('success', 'Valid Schema(s) detected!', schemasArray));
                                } else {
                                    schemaOutput.append(schemas.generateSchemaMessage('error', 'No Schema(s) detected!'));
                                }
                            }
                        } else {
                            schemaOutput.append(schemas.generateSchemaMessage('error', 'No Schema(s) detected!'));
                        }
                    }
                });
            });
        },
        toggleSchemaTypeContainerAll  : function () {
            $(document).on('click', '.schema-toggle-collapse', function (e) {
                e.preventDefault();
                let parent = $(this).parents('.schema-container-title');
                let container = parent.next('.schema-container');
                let state = $(this).attr('data-value');

                if (state == 'expanded') {
                    $(this).attr('data-value', 'collapsed');
                } else {
                    $(this).attr('data-value', 'expanded');
                }

                container.find('.schema-type-container').each(function () {

                    let iconToggle = $(this).find('.schema-type-container-toggle');
                    let schemasContainer = $(this).find('.schema-type-container-schemas');

                    if (state == 'expanded') {
                        iconToggle.removeClass('xagio-icon-arrow-up').addClass('xagio-icon-arrow-down');
                        schemasContainer.slideUp();
                    } else {
                        iconToggle.removeClass('xagio-icon-arrow-down').addClass('xagio-icon-arrow-up');
                        schemasContainer.slideDown();
                    }

                });

            });
        },
        toggleSchemaTypeContainer     : function () {
            $(document).on('click', '.schema-type-container-toggle', function (e) {
                e.preventDefault();
                let container = $(this).parents('.schema-type-container');
                let schemasContainer = container.find('.schema-type-container-schemas');
                let state = $(this).hasClass('xagio-icon-arrow-up') ? true : false;

                if (state) {
                    $(this).removeClass('xagio-icon-arrow-up').addClass('xagio-icon-arrow-down');
                    schemasContainer.slideUp();
                } else {
                    $(this).removeClass('xagio-icon-arrow-down').addClass('xagio-icon-arrow-up');
                    schemasContainer.slideDown();
                }

            });
        },
        schemaWizard                  : {
            properties      : {
                name           : {
                    type : "text",
                    label: "The name of the item.",
                    value: $('#title').length != 0 ? $('#title').val() : $('#post-title-0').val()
                },
                description    : {
                    type : "textarea",
                    label: "A description of the item.",
                    value: ""
                },
                image          : {
                    type : "url",
                    label: "An image of the item.",
                    value: $('#set-post-thumbnail').find('img').attr('src')
                },
                url            : {
                    type : "url",
                    label: "URL of the item.",
                    value: $('#sample-permalink').find('a').attr('href')
                },
                sameAs         : {
                    type : "array",
                    label: "URL(s) of a reference Web page that unambiguously indicates the item's identity.",
                    value: ""
                },
                aggregateRating: {
                    type  : "fields",
                    label : "The overall rating, based on a collection of reviews or ratings, of the item.",
                    stype : "AggregateRating",
                    fields: {
                        bestRating : {
                            type : "url",
                            label: "The highest value allowed in this rating system. If bestRating is omitted, 5 is assumed.",
                            value: 5
                        },
                        worstRating: {
                            type : "url",
                            label: "The lowest value allowed in this rating system. If worstRating is omitted, 1 is assumed.",
                            value: 1
                        },
                        ratingCount: {
                            type : "url",
                            label: "The count of total number of ratings.",
                            value: 10
                        },
                        ratingValue: {
                            type : "url",
                            label: "The rating for the content.",
                            value: 5
                        },
                    }
                }
            },
            schemas         : {
                Article      : {
                    icon  : "book",
                    label : "An article, such as a news article or piece of investigative report. Newspapers and magazines have articles of many different types and this is intended to cover them all.",
                    fields: {
                        author          : {
                            type : "text",
                            label: "The author of this content or rating. ",
                            value: $('#post_author_override').find('option:selected').text()
                        },
                        publisher       : {
                            type  : "fields",
                            label : "The publisher of the creative work.",
                            stype : "Organization",
                            fields: {
                                name: {
                                    type : "text",
                                    label: "Name of the organization.",
                                    value: xagio_data.sitename
                                },
                                logo: {
                                    type  : "fields",
                                    label : "An associated logo.",
                                    stype : "ImageObject",
                                    fields: {
                                        url: {
                                            type : "url",
                                            label: "URL of the logo.",
                                            value: $('#set-post-thumbnail').find('img').attr('src')
                                        }
                                    }
                                }
                            }
                        },
                        headline        : {
                            type : "text",
                            label: "Headline of the article.",
                            value: $('#title').length != 0 ? $('#title').val() : $('#post-title-0').val()
                        },
                        articleBody     : {
                            type : "textarea",
                            label: "The actual body of the article.",
                            value: ""
                        },
                        articleSection  : {
                            type : "text",
                            label: "Articles may belong to one or more 'sections' in a magazine or newspaper, such as Sports, Lifestyle, etc.",
                            value: $('.categorychecklist').find('input:checked').parent('label').eq(0).text().trim()
                        },
                        pageEnd         : {
                            type : "text",
                            label: "The page on which the work ends; for example \"138\" or \"xvi\".",
                            value: ""
                        },
                        pageStart       : {
                            type : "text",
                            label: "The page on which the work starts; for example \"135\" or \"xiii\".",
                            value: ""
                        },
                        pagination      : {
                            type : "text",
                            label: "Any description of pages that is not separated into pageStart and pageEnd; for example, \"1-6, 9, 55\" or \"10-12, 46-49\".",
                            value: ""
                        },
                        wordCount       : {
                            type : "text",
                            label: "The number of words in the text of the Article.",
                            value: $('.word-count').text().trim()
                        },
                        mainEntityOfPage: {
                            type : "url",
                            label: "Indicates a page for which this thing is the main entity being described.",
                            value: $('#sample-permalink').find('a').attr('href')
                        },
                        dateModified    : {
                            type : "date",
                            label: "The date on which the CreativeWork was most recently modified or when the item's entry was modified within a DataFeed.",
                            value: ""
                        },
                        datePublished   : {
                            type : "date",
                            label: "Date of first broadcast/publication.",
                            value: ""
                        },
                    }
                },
                Product      : {
                    icon  : "shopping-cart",
                    label : "Any offered product or service. For example: a pair of shoes; a concert ticket; the rental of a car; a haircut; or an episode of a TV show streamed online.",
                    fields: {
                        brand    : {
                            type  : "fields",
                            label : "The brand(s) associated with a product or service, or the brand(s) maintained by an organization or business person.",
                            stype : "Organization",
                            fields: {
                                name: {
                                    type : "text",
                                    label: "Name of the organization.",
                                    value: xagio_data.sitename
                                },
                                logo: {
                                    type  : "fields",
                                    label : "An associated logo.",
                                    stype : "ImageObject",
                                    fields: {
                                        url: {
                                            type : "url",
                                            label: "URL of the logo.",
                                            value: $('#set-post-thumbnail').find('img').attr('src')
                                        }
                                    }
                                }
                            }
                        },
                        productID: {
                            type : "text",
                            label: "The product identifier, such as ISBN.",
                            value: ""
                        },
                        sku      : {
                            type : "text",
                            label: "The Stock Keeping Unit (SKU), i.e. a merchant-specific identifier for a product or service, or the product to which the offer refers.",
                            value: ""
                        },
                        color    : {
                            type : "text",
                            label: "The color of the product.",
                            value: ""
                        },
                        model    : {
                            type : "text",
                            label: "The model of the product.",
                            value: ""
                        },
                        material : {
                            type : "text",
                            label: "A material that something is made from, e.g. leather, wool, cotton, paper.",
                            value: ""
                        },
                        logo     : {
                            type : "url",
                            label: "An associated logo.",
                            value: $('#set-post-thumbnail').find('img').attr('src')
                        },
                        category : {
                            type : "text",
                            label: "A category for the item. Greater signs or slashes can be used to informally indicate a category hierarchy.",
                            value: $('.categorychecklist').find('input:checked').parent('label').eq(0).text().trim()
                        },
                        award    : {
                            type : "text",
                            label: "An award won by or for this item. Supersedes awards.",
                            value: ""
                        },
                    }
                },
                Service      : {
                    icon  : "graduation-cap",
                    label : "A service provided by an organization, e.g. delivery service, print services, etc.",
                    fields: {
                        brand           : {
                            type  : "fields",
                            label : "The brand(s) associated with a product or service, or the brand(s) maintained by an organization or business person.",
                            stype : "Organization",
                            fields: {
                                name: {
                                    type : "text",
                                    label: "Name of the organization.",
                                    value: xagio_data.sitename
                                },
                                logo: {
                                    type  : "fields",
                                    label : "An associated logo.",
                                    stype : "ImageObject",
                                    fields: {
                                        url: {
                                            type : "url",
                                            label: "URL of the logo.",
                                            value: $('#set-post-thumbnail').find('img').attr('src')
                                        }
                                    }
                                }
                            }
                        },
                        provider        : {
                            type  : "fields",
                            label : "The service provider, service operator, or service performer; the goods producer.",
                            stype : "Organization",
                            fields: {
                                name: {
                                    type : "text",
                                    label: "Name of the organization.",
                                    value: xagio_data.sitename
                                },
                                logo: {
                                    type  : "fields",
                                    label : "An associated logo.",
                                    stype : "ImageObject",
                                    fields: {
                                        url: {
                                            type : "url",
                                            label: "URL of the logo.",
                                            value: $('#set-post-thumbnail').find('img').attr('src')
                                        }
                                    }
                                }
                            }
                        },
                        providerMobility: {
                            type : "text",
                            label: "Indicates the mobility of a provided service (e.g. 'static', 'dynamic').",
                            value: ""
                        },
                        serviceType     : {
                            type : "text",
                            label: "The type of service being offered, e.g. veterans' benefits, emergency relief, etc.",
                            value: ""
                        },
                        areaServed      : {
                            type : "text",
                            label: "The geographic area where a service or offered item is provided.",
                            value: ""
                        },
                        logo            : {
                            type : "url",
                            label: "An associated logo.",
                            value: $('#set-post-thumbnail').find('img').attr('src')
                        },
                        category        : {
                            type : "text",
                            label: "A category for the item. Greater signs or slashes can be used to informally indicate a category hierarchy.",
                            value: $('.categorychecklist').find('input:checked').parent('label').eq(0).text().trim()
                        },
                        award           : {
                            type : "text",
                            label: "An award won by or for this item. Supersedes awards.",
                            value: ""
                        },
                        termsOfService  : {
                            type : "url",
                            label: "Human-readable terms of service documentation.",
                            value: ""
                        },
                    }
                },
                LocalBusiness: {
                    icon  : "shopping-bag",
                    label : "A particular physical business or branch of an organization. Examples of LocalBusiness include a restaurant, a particular branch of a restaurant chain, a branch of a bank, a medical practice, a club, a bowling alley, etc.",
                    fields: {
                        brand             : {
                            type  : "fields",
                            label : "The brand(s) associated with a product or service, or the brand(s) maintained by an organization or business person.",
                            stype : "Organization",
                            fields: {
                                name: {
                                    type : "text",
                                    label: "Name of the organization.",
                                    value: xagio_data.sitename
                                },
                                logo: {
                                    type  : "fields",
                                    label : "An associated logo.",
                                    stype : "ImageObject",
                                    fields: {
                                        url: {
                                            type : "url",
                                            label: "URL of the logo.",
                                            value: $('#set-post-thumbnail').find('img').attr('src')
                                        }
                                    }
                                }
                            }
                        },
                        currenciesAccepted: {
                            type : "text",
                            label: "The currency accepted (in ISO 4217 currency format).",
                            value: ""
                        },
                        openingHours      : {
                            type : "text",
                            label: "The general opening hours for a business.",
                            value: ""
                        },
                        paymentAccepted   : {
                            type : "text",
                            label: "Cash, credit card, etc.",
                            value: ""
                        },
                        priceRange        : {
                            type : "text",
                            label: "The price range of the business, for example $$$.",
                            value: ""
                        },
                        logo              : {
                            type : "url",
                            label: "An associated logo.",
                            value: $('#set-post-thumbnail').find('img').attr('src')
                        },
                        geo               : {
                            type  : "fields",
                            label : "The geo coordinates of the place.",
                            stype : "GeoCoordinates",
                            fields: {
                                latitude : {
                                    type : "text",
                                    label: "The latitude of a location. For example 37.42242",
                                    value: ""
                                },
                                longitude: {
                                    type : "text",
                                    label: "The longitude of a location. For example -122.08585",
                                    value: ""
                                },
                            }
                        },
                        address           : {
                            type  : "fields",
                            label : "Physical address of the item.",
                            stype : "PostalAddress",
                            fields: {
                                addressCountry     : {
                                    type : "text",
                                    label: "The country. For example, USA. You can also provide the two-letter ISO 3166-1 alpha-2 country code.",
                                    value: ""
                                },
                                addressLocality    : {
                                    type : "text",
                                    label: "The locality. For example, Mountain View.",
                                    value: ""
                                },
                                addressRegion      : {
                                    type : "text",
                                    label: "The region. For example, CA.",
                                    value: ""
                                },
                                postOfficeBoxNumber: {
                                    type : "text",
                                    label: "The post office box number for PO box addresses.",
                                    value: ""
                                },
                                postalCode         : {
                                    type : "text",
                                    label: "The postal code. For example, 94043.",
                                    value: ""
                                },
                                streetAddress      : {
                                    type : "text",
                                    label: "The street address. For example, 1600 Amphitheatre Pkwy.",
                                    value: ""
                                },
                            }
                        },
                    }
                },
                Organization : {
                    icon  : "building",
                    label : "An organization such as a school, NGO, corporation, club, etc.",
                    fields: {
                        brand  : {
                            type  : "fields",
                            label : "The brand(s) associated with a product or service, or the brand(s) maintained by an organization or business person.",
                            stype : "Organization",
                            fields: {
                                name: {
                                    type : "text",
                                    label: "Name of the organization.",
                                    value: xagio_data.sitename
                                },
                                logo: {
                                    type  : "fields",
                                    label : "An associated logo.",
                                    stype : "ImageObject",
                                    fields: {
                                        url: {
                                            type : "url",
                                            label: "URL of the logo.",
                                            value: $('#set-post-thumbnail').find('img').attr('src')
                                        }
                                    }
                                }
                            }
                        },
                        logo   : {
                            type : "url",
                            label: "An associated logo.",
                            value: $('#set-post-thumbnail').find('img').attr('src')
                        },
                        geo    : {
                            type  : "fields",
                            label : "The geo coordinates of the place.",
                            stype : "GeoCoordinates",
                            fields: {
                                latitude : {
                                    type : "text",
                                    label: "The latitude of a location. For example 37.42242",
                                    value: ""
                                },
                                longitude: {
                                    type : "text",
                                    label: "The longitude of a location. For example -122.08585",
                                    value: ""
                                },
                            }
                        },
                        address: {
                            type  : "fields",
                            label : "Physical address of the item.",
                            stype : "PostalAddress",
                            fields: {
                                addressCountry     : {
                                    type : "text",
                                    label: "The country. For example, USA. You can also provide the two-letter ISO 3166-1 alpha-2 country code.",
                                    value: ""
                                },
                                addressLocality    : {
                                    type : "text",
                                    label: "The locality. For example, Mountain View.",
                                    value: ""
                                },
                                addressRegion      : {
                                    type : "text",
                                    label: "The region. For example, CA.",
                                    value: ""
                                },
                                postOfficeBoxNumber: {
                                    type : "text",
                                    label: "The post office box number for PO box addresses.",
                                    value: ""
                                },
                                postalCode         : {
                                    type : "text",
                                    label: "The postal code. For example, 94043.",
                                    value: ""
                                },
                                streetAddress      : {
                                    type : "text",
                                    label: "The street address. For example, 1600 Amphitheatre Pkwy.",
                                    value: ""
                                },
                            }
                        },
                    }
                }
            },
            revertSteps     : function () {
                let steps = [
                    '.swStep1',
                    '.swStep2',
                    '.swStep3'
                ];
                let prev = $('.swPreviousStep');
                let next = $('.swNextStep');
                let finish = $('.swFinish');
                for (let i = 1; i < steps.length; i++) {
                    $(steps[i]).hide();
                }

                $(steps[0]).show();
                prev.hide();
                next.hide();
                finish.hide();
            },
            nextStep        : function () {
                let steps = [
                    '.swStep1',
                    '.swStep2',
                    '.swStep3'
                ];
                let prev = $('.swPreviousStep');
                let next = $('.swNextStep');
                let finish = $('.swFinish');
                for (let i = 0; i < steps.length; i++) {

                    if (!$(steps[i]).is(':visible')) {
                        continue;
                    }

                    let currentStep = $(steps[i]);
                    let nextStep = $(steps[i + 1]);

                    if (i === 0) {
                        // First Step
                        prev.show();
                        next.show();
                        currentStep.hide();
                        nextStep.show();
                        return;
                    } else if (i === steps.length - 1) {
                        // Last Step
                        return;
                    } else {
                        currentStep.hide();
                        nextStep.show();
                        // Find out if its the last step
                        if ((i + 1) === (steps.length - 1)) {
                            next.hide();
                            finish.show();
                        }
                        return;
                    }
                }
            },
            previousStep    : function () {
                let steps = [
                    '.swStep1',
                    '.swStep2',
                    '.swStep3'
                ];
                let prev = $('.swPreviousStep');
                let next = $('.swNextStep');
                let finish = $('.swFinish');
                for (let i = 0; i < steps.length; i++) {

                    if (!$(steps[i]).is(':visible')) {
                        continue;
                    }

                    let currentStep = $(steps[i]);
                    let previousStep = $(steps[i - 1]);

                    if (i === 0) {
                        // First Step
                        return;
                    } else if (i === (steps.length - 1)) {
                        // Last Step
                        next.show();
                        finish.hide();

                        currentStep.hide();
                        previousStep.show();
                        return;
                    } else {
                        // Find out if its the first step

                        currentStep.hide();
                        previousStep.show();

                        if ((i - 1) === 0) {
                            next.hide();
                            prev.hide();
                        }
                        return;
                    }
                }
            },
            generateProperty: function (fields, parentProperty) {
                let html = '';
                for (let property in fields) {
                    let obj = fields[property];
                    html += '<div class="swProperty" data-property="' + property + '" data-type="' + obj.type + '">';

                    html += "<label for='" + property + "'>" + property +
                            " <i class='xagio-icon xagio-icon-info' data-xagio-tooltip data-xagio-title='" + obj.label +
                            "'></i></label>";
                    // html += "<span>" + obj.label + "</span>";

                    let fieldName = "swFields[" + property + "]";

                    if (parentProperty !== false) {
                        fieldName = parentProperty.generatedField + "[" + property + "]";
                    }

                    if (obj.type === 'fields') {
                        html += "<div class='swSubSchema'>";
                    }

                    if (typeof obj.value === 'undefined') obj.value = '';

                    switch (obj.type) {
                        case "date":
                            html += "<input type='date' name='" + fieldName + "' id='" + property + "' value='" +
                                    obj.value + "' required/>";
                            break;
                        case "text":
                            html += "<input class='xagio-input-text-mini' name='" + fieldName + "' id='" + property +
                                    "' value='" + obj.value + "' required/>";
                            break;
                        case "textarea":
                            html += "<textarea class='xagio-input-textarea' name='" + fieldName + "' id='" + property +
                                    "' required>" + obj.value + "</textarea>";
                            break;
                        case "url":
                            html += "<input class='xagio-input-text-mini' name='" + fieldName + "' type='url' id='" +
                                    property + "' value='" + obj.value + "' required/>";
                            break;
                        case "array":
                            html += "<input class='xagio-input-text-mini' name='" + fieldName + "' id='" + property +
                                    "' required/>";
                            break;
                        case "fields":
                            html += "<input type='hidden' name='" + fieldName + "[@type]' value='" + obj.stype + "'/>";
                            html += schemas.schemaWizard.generateProperty(obj.fields, {
                                generatedField: fieldName
                            });
                            break;
                    }

                    if (obj.type === 'fields') {
                        html += "</div>";
                    }

                    html += '</div>';
                }
                return html;
            },
            init            : function () {
                // let swTypes = $('.swTypes');
                let swTypes = $('#swTypes');
                swTypes.append('<option class="" data-type="" selected>-- Select Below --</option>');
                for (let type in schemas.schemaWizard.schemas) {
                    let schema = schemas.schemaWizard.schemas[type];
                    swTypes.append('<option class="swType" data-type="' + type + '"><span>' + type +
                                   '</span></option>');
                }

                $('#wizardSchema').click(function () {
                    let id = $(this).data('id');
                    $('#wizardSchema_post_id').val(id);
                    schemas.schemaWizard.revertSteps();
                    let modal = $("#wizardSchemaModal");
                    modal[0].showModal();
                });

                $(document).on('click', '.swPreviousStep', function (e) {
                    e.preventDefault();
                    schemas.schemaWizard.previousStep();
                });

                $(document).on('click', '.swNextStep', function (e) {
                    e.preventDefault();
                    schemas.schemaWizard.nextStep();
                });

                $(document).on('change', '#swTypes', function (e) {
                    let option = $(this).find(':selected');
                    let type = option.data('type');
                    let swFields = $('.swFields');

                    $('#sw_schema_type').val(type);
                    $('.swSelectedType').text(type);

                    // Set the final name
                    $('#swName').val($('#title').length != 0 ? $('#title').val() : $('#post-title-0').val());

                    // Render the fields
                    swFields.empty();

                    let form = $("<form class='swForm'></form>");
                    form.append(schemas.schemaWizard.generateProperty(schemas.schemaWizard.properties, false));
                    form.append(schemas.schemaWizard.generateProperty(schemas.schemaWizard.schemas[type].fields, false));

                    swFields.append(form);

                    schemas.schemaWizard.nextStep();
                });

                // $(document).on('click', '.swType', function (e) {
                //     e.preventDefault();
                //
                //     let type = $(this).data('type');
                //     let swFields = $('.swFields');
                //
                //     $('#sw_schema_type').val(type);
                //     $('.swSelectedType').text(type);
                //
                //     // Set the final name
                //     $('#swName').val($('#title').length != 0 ? $('#title').val() : $('#post-title-0').val());
                //
                //     // Render the fields
                //     swFields.empty();
                //
                //     let form = $("<form class='swForm'></form>");
                //     form.append(schemas.schemaWizard.generateProperty(schemas.schemaWizard.properties, false));
                //     form.append(schemas.schemaWizard.generateProperty(schemas.schemaWizard.schemas[type].fields, false));
                //
                //     swFields.append(form);
                //
                //     schemas.schemaWizard.nextStep();
                // });

                $(document).on('click', '.swFinish', function (e) {
                    e.preventDefault();

                    $('.swFinish').disable();
                    $('#swName').disable();

                    let data = $('.swForm').serialize();

                    $.post(xagio_data.wp_post, 'action=xagio_schema_wizard&post_id=' + $('#xagio_post_id').val() +
                                               '&type=' + $('#sw_schema_type').val() + "&name=" + $('#swName').val() +
                                               "&" + data).done(function (d) {
                        if (d.status === 'success') {
                            xagioNotify('success', d.message);
                            document.location.reload();
                        } else {
                            xagioNotify('danger', "Failed to generate Schema. Please contact support!");
                        }
                    });
                });

            }
        }
    };

    let social = {
        init              : function () {
            social.handleTitles('facebook');
            social.handleDescriptions('facebook');
            social.handleImage('facebook');
            social.seoTitles('facebook');
            social.seoDescription('facebook');

            social.handleTitles('twitter');
            social.handleDescriptions('twitter');
            social.handleImage('twitter');
            social.seoTitles('twitter');
            social.seoDescription('twitter');

            social.selectImages();
            social.initRadios();
            social.changeMetaRobots();
            social.initChecks();
        },
        initChecks        : function () {
            if ($('.XAGIO_SEO_SOCIAL input[type="checkbox"]').is(':checked')) {
                $('.XAGIO_SEO_SOCIAL input[type="checkbox"]').trigger('change');
            }
        },
        changeMetaRobots  : function () {
            $(document).on('change', 'select[name="XAGIO_SEO_META_ROBOTS_INDEX"]', function () {
                social.updateMetaRobots();
            });

            $(document).on('change', 'select[name="XAGIO_SEO_META_ROBOTS_FOLLOW"]', function () {
                social.updateMetaRobots();
            });

            $(document).on('change', 'fieldset.xagio-robots-optional input[name="XAGIO_SEO_META_ROBOTS_ADVANCED[]"]', function () {
                social.updateMetaRobots();
            });

        },
        updateMetaRobots  : function () {
            let robots_el = $('.xagio-preview-meta-robots');
            let robots_global = robots_el.data('global');

            if (robots_global.length > 0) {
                robots_global = robots_global.split(",");
            }

            let robots = [];
            let index_b = $('select[name="XAGIO_SEO_META_ROBOTS_INDEX"]').val();
            let follow_b = $('select[name="XAGIO_SEO_META_ROBOTS_FOLLOW"]').val();

            for (let glob of robots_global) {
                robots.push(glob);
            }

            $('fieldset.xagio-robots-optional input[name="XAGIO_SEO_META_ROBOTS_ADVANCED[]"]').each(function () {
                let no_robots_val = $(this).val();
                console.log(no_robots_val);
                if (no_robots_val.length > 0) {
                    robots.push(no_robots_val);
                }
            });

            if (index_b !== 'default') {
                robots = robots.filter(function (e) {
                    return e !== 'noindex'
                });
                robots.push(index_b);
            }

            if (follow_b !== 'default') {
                robots = robots.filter(function (e) {
                    return e !== 'follow'
                });
                robots.push(follow_b);
            }

            if (index_b !== 'default' && follow_b !== 'default') {
                $('.xagio-global-robots-info').hide();
            } else {
                $('.xagio-global-robots-info').show();
            }


            if (robots.length < 1) {
                robots_el.html('&lt;meta name="robots" content="" /&gt;');
            } else {
                robots = robots.join(',');
                robots_el.html(`&lt;meta name="robots" content="${robots}" /&gt;`);
            }

        },
        initRadios        : function () {
            $('select[name="XAGIO_SEO_META_ROBOTS_INDEX"], select[name="XAGIO_SEO_META_ROBOTS_FOLLOW"]').each(function () {
                let value = $(this).data('value').toString();
                // if value contains | then it's a multiple value
                $(this).val(value);
            });

            $('fieldset[data-value]').each(function () {
                let value = $(this).data('value').toString();
                // if value contains | then it's a multiple value
                if (value.indexOf('|') !== -1) {
                    let values = value.split('|');
                    for (let i = 0; i < values.length; i++) {
                        let current_slider = $(this).find('input[name="' + values[i] +
                                                          '"]').parents('.xagio-slider-container');
                        $(this).find('input[name="' + values[i] + '"]').val(0);
                        current_slider.find('input[name="XAGIO_SEO_META_ROBOTS_ADVANCED[]"]').prop("disabled", false).val(values[i]);
                        current_slider.find('.xagio-slider-button').removeClass('on');
                    }
                } else {
                    if (value != '') {
                        $(this).find('input[name="' + value + '"]').val(0);
                        $(this).find('input[name="' + value +
                                     '"]').parents('.xagio-slider-container').find('input[name="XAGIO_SEO_META_ROBOTS_ADVANCED[]"]').prop("disabled", false).val(value);
                        $(this).find('input[name="' + value +
                                     '"]').parents('.xagio-slider-container').find('.xagio-slider-button').removeClass('on');
                    }
                }
            });
        },
        selectImages      : function () {
            $('.imageSelect').click(function () {
                let target = $(this).data('target');
                tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
                window.send_to_editor = function (html) {
                    var img = $(html).attr('src');
                    $('#' + target).val(img).trigger('change');
                    tb_remove();
                }
            });
        },
        seoTitles         : function (what) {
            let swhat = what.toLowerCase();
            let bwhat = what.toUpperCase();

            $(document).on('change', '#XAGIO_SEO_TITLE_INPUT', function () {
                if ($(`#${swhat}_use_title_from_seo`).is(':checked')) {
                    $(`#XAGIO_SEO_${bwhat}_TITLE`).val($('#XAGIO_SEO_TITLE').text()).trigger('change');
                }
            });
        },
        seoDescription    : function (what) {
            let swhat = what.toLowerCase();
            let bwhat = what.toUpperCase();

            $(document).on('change', '#XAGIO_SEO_DESCRIPTION_INPUT', function () {
                if ($(`#${swhat}_use_description_from_seo`).is(':checked')) {
                    $(`#XAGIO_SEO_${bwhat}_DESCRIPTION`).val($('#XAGIO_SEO_DESCRIPTION').text()).trigger('change');
                }
            });
        },
        handleTitles      : function (what) {
            let swhat = what.toLowerCase();
            let bwhat = what.toUpperCase();
            $(document).on('change', `#${swhat}_use_title_from_seo`, function (e) {
                e.preventDefault();
                // if its checked
                if ($(this).is(':checked')) {
                    $(`#XAGIO_SEO_${bwhat}_TITLE`).attr('data-value-old', $(`#XAGIO_SEO_${bwhat}_TITLE`).val());
                    $(`#XAGIO_SEO_${bwhat}_TITLE`).val($('#XAGIO_SEO_TITLE').text()).trigger('change');
                    // disable
                    $(`#XAGIO_SEO_${bwhat}_TITLE`).attr('disabled', true);
                } else {
                    // enable
                    $(`#XAGIO_SEO_${bwhat}_TITLE`).attr('disabled', false);

                    if ($(`#XAGIO_SEO_${bwhat}_TITLE`).attr('data-value-old') !== undefined) {
                        $(`#XAGIO_SEO_${bwhat}_TITLE`).val($(`#XAGIO_SEO_${bwhat}_TITLE`).attr('data-value-old')).trigger('change');
                    }
                }
            });
            $(document).on('change', '#xagio_title', function (e) {
                if ($(`#${swhat}_use_title_from_seo`).is(':checked')) {
                    $(`#XAGIO_SEO_${bwhat}_TITLE`).val($('#XAGIO_SEO_TITLE').text());
                }
            });
            $(document).on('keyup', `#XAGIO_SEO_${bwhat}_TITLE`, function (e) {
                $(this).trigger('change');
            });
            $(document).on('change', `#XAGIO_SEO_${bwhat}_TITLE`, function (e) {
                $(`.${swhat}-preview-title`).text($(this).val());
            });
        },
        handleDescriptions: function (what) {
            let swhat = what.toLowerCase();
            let bwhat = what.toUpperCase();
            $(document).on('change', `#${swhat}_use_description_from_seo`, function (e) {
                e.preventDefault();
                // if its checked
                if ($(this).is(':checked')) {
                    $(`#XAGIO_SEO_${bwhat}_DESCRIPTION`).attr('data-value-old', $(`#XAGIO_SEO_${bwhat}_DESCRIPTION`).val());
                    $(`#XAGIO_SEO_${bwhat}_DESCRIPTION`).val($('#XAGIO_SEO_DESCRIPTION').text()).trigger('change');
                    // disable
                    $(`#XAGIO_SEO_${bwhat}_DESCRIPTION`).attr('disabled', true);
                } else {
                    //enable
                    $(`#XAGIO_SEO_${bwhat}_DESCRIPTION`).attr('disabled', false);

                    if ($(`#XAGIO_SEO_${bwhat}_DESCRIPTION`).attr('data-value-old') !== undefined) {
                        $(`#XAGIO_SEO_${bwhat}_DESCRIPTION`).val($(`#XAGIO_SEO_${bwhat}_DESCRIPTION`).attr('data-value-old')).trigger('change');
                    }
                }
            });
            $(document).on('change', '#xagio_description', function (e) {
                if ($(`#${swhat}_use_description_from_seo`).is(':checked')) {
                    $(`#XAGIO_SEO_${bwhat}_DESCRIPTION`).val($('#XAGIO_SEO_DESCRIPTION').text());
                }
            });
            $(document).on('keyup', `#XAGIO_SEO_${bwhat}_DESCRIPTION`, function (e) {
                $(this).trigger('change');
            });
            $(document).on('change', `#XAGIO_SEO_${bwhat}_DESCRIPTION`, function (e) {
                $(`.${swhat}-preview-description`).text($(this).val());
            });
        },
        handleImage       : function (what) {

            let featuredImage = false;

            if ($(`#postimagediv .inside img`).length > 0) {
                featuredImage = $(`#postimagediv .inside img`).attr('src');
            } else if ($('#xagio_post_thumbnail').val() !== '') {
                featuredImage = $('#xagio_post_thumbnail').val();
            } else if ($('.editor-post-featured-image__container img').length > 0) {
                featuredImage = $(`.editor-post-featured-image__container img`).attr('src');
            }
            let swhat = what.toLowerCase();
            let bwhat = what.toUpperCase();


            $(document).on('change', `#XAGIO_SEO_${bwhat}_IMAGE`, function (e) {
                // if $(this).val() is an image url continue
                console.log("CHANGE");
                console.log($(this).val());
                if ($(this).val().match(/\.(jpeg|jpg|gif|png)$/) != null) {
                    $(`.${swhat}-image-preview`).attr('src', $(this).val());
                }
            });


            if (featuredImage !== false) {
                // put the old value in input attribute data-value-old
                $(`#XAGIO_SEO_${bwhat}_IMAGE`).attr('data-value-old', $(`#XAGIO_SEO_${bwhat}_IMAGE`).val());
                $(`#XAGIO_SEO_${bwhat}_IMAGE`).val(featuredImage).trigger('change');

                // disable
                $(`#XAGIO_SEO_${bwhat}_IMAGE`).attr('disabled', true);
                $(`.xagio-social-${swhat}-image-warning`).hide();
                $(`#${what}_use_featured_image`).prop('checked', true);
            } else {
                $(`.xagio-social-${swhat}-image-warning`).show();
                // If all fails, set checked to false
                $(`#${what}_use_featured_image`).prop('checked', false);
            }


            $(document).on('input', `#${swhat}_use_featured_image`, function (e) {
                e.preventDefault();
                // if its checked
                if ($(this).is(':checked')) {

                    let featuredImage = false;

                    if ($(`#postimagediv .inside img`).length > 0) {
                        featuredImage = $(`#postimagediv .inside img`).attr('src');
                    } else if ($('#xagio_post_thumbnail').val() !== '') {
                        featuredImage = $('#xagio_post_thumbnail').val();
                    } else if ($('.editor-post-featured-image__container img').length > 0) {
                        featuredImage = $(`.editor-post-featured-image__container img`).attr('src');
                    }

                    if (featuredImage !== false) {
                        // put the old value in input attribute data-value-old
                        $(`#XAGIO_SEO_${bwhat}_IMAGE`).attr('data-value-old', $(`#XAGIO_SEO_${bwhat}_IMAGE`).val());
                        $(`#XAGIO_SEO_${bwhat}_IMAGE`).val(featuredImage).trigger('change');
                        // disable
                        $(`#XAGIO_SEO_${bwhat}_IMAGE`).attr('disabled', true);
                        $(`.xagio-social-${swhat}-image-warning`).hide();
                    } else {
                        xagioNotify("danger", "Featured image is not set.");
                        $(`.xagio-social-${swhat}-image-warning`).show();
                        // If all fails, set checked to false
                        $(this).prop('checked', false);
                    }

                } else {
                    //enable
                    $(`#XAGIO_SEO_${bwhat}_IMAGE`).attr('disabled', false);
                    $(`.${swhat}-image-preview`).attr('src', $(`.${swhat}-image-preview`).attr('data-no-image'));

                    if ($(`#XAGIO_SEO_${bwhat}_IMAGE`).attr('data-value-old') !== undefined) {
                        $(`#XAGIO_SEO_${bwhat}_IMAGE`).val($(`#XAGIO_SEO_${bwhat}_IMAGE`).attr('data-value-old')).trigger('change');
                    }
                }
            });

            let imageChangeTimeout = null;
            $(document).on('DOMSubtreeModified', '#postimagediv', function () {
                clearTimeout(imageChangeTimeout);
                imageChangeTimeout = setTimeout(function () {
                    if ($(`#${swhat}_use_featured_image`).is(':checked')) {
                        if ($(`#postimagediv .inside img`).length > 0) {
                            let featuredImage = $(`#postimagediv .inside img`).attr('src');
                            $(`#XAGIO_SEO_${bwhat}_IMAGE`).val(featuredImage).trigger('change');
                        } else {
                            $(`#${swhat}_use_featured_image`).prop('checked', false).trigger('input');
                        }
                    }
                }, 100);
            });
        }
    };

    let search_preview = {
        cf_templates                : {
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
        },
        cf_default_template         : 'Default',
        cf_template                 : null,
        init                        : function () {
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
        },
        calculations                : {
            ranking         : {
                init                : function () {
                    search_preview.calculations.ranking.targetKeywordChanged();
                    search_preview.calculations.shared_functions.countFoundIssues('ranking');
                    search_preview.calculations.shared_functions.getContentLoop();
                    search_preview.calculations.ranking.triggers.initAll();
                    target_keyword = $('#XAGIO_SEO_TARGET_KEYWORD').val();
                },
                triggers            : {
                    enabled          : false,
                    initAll          : function () {
                        for (let tFK in search_preview.calculations.ranking.triggers) {
                            if (tFK.indexOf('tFK') !== -1) {
                                search_preview.calculations.ranking.triggers[tFK].init();
                            }
                        }
                    },
                    triggerAll       : function () {
                        for (let tFK in search_preview.calculations.ranking.triggers) {
                            if (tFK.indexOf('tFK') !== -1) {
                                search_preview.calculations.ranking.triggers[tFK].trigger();
                            }
                        }
                    },
                    tFK_SeoTitle     : {
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
                                if (isBlockEditor) {
                                    seoTitle = $('.wp-block-post-title').text();
                                } else {
                                    seoTitle = $('[name="post_title"]').val();
                                }

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
                        },
                        init   : function () {
                            $(document).on('change', '[name="XAGIO_SEO_TITLE"]', function () {
                                search_preview.calculations.ranking.triggers.tFK_SeoTitle.trigger();
                            });
                        }
                    },
                    tFK_SeoDesc      : {
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
                        },
                        init   : function () {
                            $(document).on('change', '[name="XAGIO_SEO_DESCRIPTION"]', function () {
                                search_preview.calculations.ranking.triggers.tFK_SeoDesc.trigger();
                            });
                        }
                    },
                    tFK_SeoUrl       : {
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
                        },
                        init   : function () {
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
                    },
                    tFK_Content      : {
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
                        },
                        init   : function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.ranking.triggers.tFK_Content.trigger();
                                search_preview.calculations.ranking.triggers.tFK_Content.init();
                            }, 500);
                        }
                    },
                    tFK_SubHead      : {
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
                        },
                        init   : function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.ranking.triggers.tFK_SubHead.trigger();
                                search_preview.calculations.ranking.triggers.tFK_SubHead.init();
                            }, 500);
                        }
                    },
                    tFK_ImageAlt     : {
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
                        },
                        init   : function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.ranking.triggers.tFK_ImageAlt.trigger();
                                search_preview.calculations.ranking.triggers.tFK_ImageAlt.init();
                            }, 500);
                        }
                    },
                    tFK_BeginSeoTitle: {
                        trigger: function () {
                            if (!search_preview.calculations.ranking.triggers.enabled) return;

                            let element = $('.tFK_BeginSeoTitle');
                            let seoTitle = search_preview.calculations.shared_functions.getTitle();

                            element.removeClass('analysis-error analysis-warning analysis-ok');

                            if (seoTitle != '') {

                                if (seoTitle.toLowerCase().indexOf(target_keyword.toLowerCase()) < 12 &&
                                    seoTitle.toLowerCase().indexOf(target_keyword.toLowerCase()) > -1) {
                                    element.addClass('analysis-ok');
                                    element.find('span').html('Target Keyword found near the beginning of the SEO Title.');
                                } else {
                                    element.addClass('analysis-error');
                                    element.find('span').html('Use the Target Keyword near the beginning of the SEO Title.');
                                }

                            } else {
                                if (isBlockEditor) {
                                    seoTitle = $('.wp-block-post-title').text();
                                } else {
                                    seoTitle = $('[name="post_title"]').val();
                                }

                                if (seoTitle.toLowerCase().indexOf(target_keyword.toLowerCase()) < 12 &&
                                    seoTitle.toLowerCase().indexOf(target_keyword.toLowerCase()) > -1) {
                                    element.addClass('analysis-warning');
                                    element.find('span').html('SEO Title is empty, but Focus keyword was found near the beginning of Post H1, which is used as a fallback.');
                                } else {
                                    element.addClass('analysis-error');
                                    element.find('span').html('Use the Target Keyword near the beginning of the SEO Title.');
                                }

                            }

                            search_preview.calculations.shared_functions.countFoundIssues('ranking');
                        },
                        init   : function () {
                            $(document).on('change', '[name="XAGIO_SEO_TITLE"]', function () {
                                search_preview.calculations.ranking.triggers.tFK_BeginSeoTitle.trigger();
                            });
                        }
                    },
                    tFK_KwDensity    : {
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
                        },
                        init   : function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.ranking.triggers.tFK_KwDensity.trigger();
                                search_preview.calculations.ranking.triggers.tFK_KwDensity.init();
                            }, 500);
                        }
                    },
                },
                targetKeywordChanged: function () {
                    $(document).on('change', '#XAGIO_SEO_TARGET_KEYWORD', function () {

                        let current_value = $(this).val();
                        let analysis_ranking = $(".analysis-ranking");

                        if (current_value == '') {

                            if (!analysis_ranking.find('.analysis-object').hasClass('xagio-hidden')) {

                                analysis_ranking.find('.analysis-object').addClass('xagio-hidden');
                                analysis_ranking.append('<span class="analysis-info analysis-warning"><i class="xagio-icon xagio-icon-warning"></i> <span>Please enter a <b>Target Keyword</b> to get the SEO ranking calculation.</span></span>');
                                search_preview.calculations.ranking.triggers.enabled = false;

                            }

                        } else {

                            analysis_ranking.find('.analysis-object').removeClass('xagio-hidden');
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
            },
            optimization    : {
                init    : function () {
                    search_preview.calculations.shared_functions.countFoundIssues('optimization');
                    search_preview.calculations.optimization.triggers.initAll();
                },
                triggers: {
                    initAll          : function () {
                        for (let tOP in search_preview.calculations.optimization.triggers) {
                            if (tOP.indexOf('tOP') !== -1) {
                                search_preview.calculations.optimization.triggers[tOP].init();
                            }
                        }
                    },
                    tOP_ContentLength: {
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
                        },
                        init   : function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.optimization.triggers.tOP_ContentLength.trigger();
                                search_preview.calculations.optimization.triggers.tOP_ContentLength.init();
                            }, 500);
                        }
                    },
                    tOP_TitleLength  : {
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
                        },
                        init   : function () {
                            $(document).on('change', '[name="XAGIO_SEO_TITLE"]', function () {
                                search_preview.calculations.optimization.triggers.tOP_TitleLength.trigger();
                            });
                            $('[name="XAGIO_SEO_TITLE"]').trigger('change');
                        }
                    },
                    tOP_DescLength   : {
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
                        },
                        init   : function () {
                            $(document).on('change', '[name="XAGIO_SEO_DESCRIPTION"]', function () {
                                search_preview.calculations.optimization.triggers.tOP_DescLength.trigger();
                            });
                            $('[name="XAGIO_SEO_DESCRIPTION"]').trigger('change');
                        }
                    },
                    tOP_UrlLength    : {
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
                        },
                        init   : function () {
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
                    },
                    tOP_NumberTitle  : {
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
                        },
                        init   : function () {
                            $(document).on('change', '[name="XAGIO_SEO_TITLE"]', function () {
                                search_preview.calculations.optimization.triggers.tOP_NumberTitle.trigger();
                            });
                            $('[name="XAGIO_SEO_TITLE"]').trigger('change');
                        }
                    },
                    tOP_AddMedia     : {
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
                        },
                        init   : function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.optimization.triggers.tOP_AddMedia.trigger();
                                search_preview.calculations.optimization.triggers.tOP_AddMedia.init();
                            }, 500);
                        }
                    },
                    tOP_IntLinks     : {
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
                        },
                        init   : function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.optimization.triggers.tOP_IntLinks.trigger();
                                search_preview.calculations.optimization.triggers.tOP_IntLinks.init();
                            }, 500);
                        }
                    },
                    tOP_ExtLinks     : {
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
                        },
                        init   : function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.optimization.triggers.tOP_ExtLinks.trigger();
                                search_preview.calculations.optimization.triggers.tOP_ExtLinks.init();
                            }, 500);
                        }
                    },
                    tOP_ReadScore    : {
                        trigger: function () {

                            let element = $('.tOP_ReadScore');
                            let content = search_preview.calculations.shared_functions.getContentText;
                            content = content.trim();
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
                        },
                        init   : function () {
                            // no good & easy way to trigger this
                            setTimeout(function () {
                                search_preview.calculations.optimization.triggers.tOP_ReadScore.trigger();
                                search_preview.calculations.optimization.triggers.tOP_ReadScore.init();
                            }, 1000);
                        }
                    },
                }
            },
            suggestions     : {
                init                  : function () {
                    search_preview.calculations.suggestions.targetKeywordChanged();
                },
                timeout               : null,
                targetKeywordChanged  : function () {
                    $(document).on('change', '[name="XAGIO_SEO_TARGET_KEYWORD"]', function () {

                        if ($('#lock-suggestions').val() == '1') return;

                        let current_value = $(this).val();
                        let suggestion_keywords = $(".suggestion-keywords");

                        if (current_value == '') {

                            suggestion_keywords.empty();
                            suggestion_keywords.append('<td colspan="9" class="xagio-text-center"><i class="xagio-icon xagio-icon-warning"></i> Please enter a <b>Target Keyword</b> to get the Keyword Suggestions.</td>');

                        } else {

                            target_keyword = current_value;

                            clearTimeout(search_preview.calculations.suggestions.timeout);
                            search_preview.calculations.suggestions.timeout = setTimeout(function () {

                                suggestion_keywords.empty();
                                suggestion_keywords.append('<td colspan="9" class="xagio-text-center" style="padding: 10px 20px;"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Loading Suggested Keywords... </td>');
                                search_preview.calculations.suggestions.getSuggestions();
                            }, 1500);

                        }

                    });
                    $('[name="XAGIO_SEO_TARGET_KEYWORD"]').trigger('change');
                    $('#lock-suggestions').val(1);
                    $('#lock-suggestions').next('.xagio-slider-frame').find('.xagio-slider-button').addClass('on');
                },
                getSuggestions        : function () {
                    $.post(xagio_data.wp_post, `action=xagio_keyword_suggestions&post_id=${xagio_post_id.value}&keyword=` +
                                               search_preview.calculations.shared_functions.getKeyword(), function (d) {

                        let suggestion_keywords = $(".suggestion-keywords");
                        suggestion_keywords.empty();

                        if (!d.hasOwnProperty('data')) {
                            suggestion_keywords.append('<td colspan="9" class="xagio-text-center"><i class="xagio-icon xagio-icon-warning"></i> An error occurred, please try again later.</td>');
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
                                    volume_color = 'tr_red';
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
                                } else if (parseFloat(search_preview.cf_template.cpc_red) < parseFloat(keyword.cpc) &&
                                           parseFloat(search_preview.cf_template.cpc_green) > parseFloat(keyword.cpc)) {
                                    cpc_color = 'tr_yellow';
                                } else if (parseFloat(search_preview.cf_template.cpc_green) <=
                                           parseFloat(keyword.cpc)) {
                                    cpc_color = 'tr_green';
                                }

                                /**
                                 *
                                 *     CONDITIONAL FORMATTING
                                 *
                                 */


                                let tr = $('<tr data-id="0"></tr>');
                                tr.append('<td class="xagio-text-center"><button data-xagio-tooltip data-xagio-title="Set as Target Keyword." class="to-target-keyword" type="button"><i class="xagio-icon xagio-icon-arrow-up"></i></button><button data-xagio-tooltip data-xagio-title="Add this keyword to your Group above." class="to-keyword-group" type="button"><i class="xagio-icon xagio-icon-plus"></i></button></td>');
                                tr.append('<td><div contenteditable="true" class="keywordInput" data-target="keyword">' +
                                          keyword.keyword + '</div></td>');

                                tr.append('<td class="xagio-text-center ' + volume_color +
                                          '"><div contenteditable="true" class="keywordInput" data-target="volume">' +
                                          search_preview.parseNumber(keyword.volume) + '</div></td>');
                                tr.append('<td class="xagio-text-center ' + cpc_color +
                                          '"><div contenteditable="true" class="keywordInput" data-target="cpc">' +
                                          keyword.cpc + '</div></td>');

                                tr.append('<td data-target="intitle" class="xagio-text-center"><div contenteditable="true" class="keywordInput" data-target="intitle">' +
                                          search_preview.parseNumber(keyword.intitle) + '</div></td>');
                                tr.append('<td data-target="inurl" class="xagio-text-center"><div contenteditable="true" class="keywordInput" data-target="inurl">' +
                                          search_preview.parseNumber(keyword.inurl) + '</div></td>');

                                tr.append('<td class="xagio-text-center" data-target="tr"><div contenteditable="true" class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Search Volume and InTitle metrics must be retrieved first to see the Title Ratio."></div></td>');
                                tr.append('<td class="xagio-text-center" data-target="ur"><div contenteditable="true" class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Search Volume and InURL metrics must be retrieved first to see the URL Ratio."></div></td>');

                                tr.append('<td class="xagio-text-center"><span data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Not Added"><span style="display: none;">99999</span></span></td>');

                                groupKeywords.push(tr);
                            }

                            kwData.append(groupKeywords);

                            search_preview.initSorters();

                        } else {

                            suggestion_keywords.append('<td colspan="9" class="xagio-text-center" style="padding: 10px 20px;"><i class="xagio-icon xagio-icon-warning"></i> No Keyword Suggestions are available for this <b>Target Keyword</b>.</td>');

                        }

                        search_preview.calculations.suggestions.calculateFoundKeywords();
                    });
                },
                calculateFoundKeywords: function () {
                    let counter = $('.analysis-suggestions').find('.uk-badge');
                    let count = $('.suggestion-keywords').find('tr').length;

                    let text = count;

                    if (counter.find('span').text() !== text) {
                        counter.html('<span>' + text + '</span>');
                    }
                }
            },
            shared_functions: {
                countFoundIssues: function (type) {
                    if (!$('.analysis-' + type).is(':visible')) {
                        return;
                    }

                    let counter = $('.analysis-' +
                                    type).parents('.xagio-accordion').find('.xagio-accordion-title').find('.uk-badge');

                    let cText = counter.find('span').text();
                    let nHTML = $('<span class="uk-badge uk-badge-a"></span>');

                    let errors = $('.analysis-' + type).find('span.analysis-error:visible').length;
                    let warnings = $('.analysis-' + type).find('span.analysis-warning:visible').length;
                    let ok = $('.analysis-' + type).find('span.analysis-ok:visible').length;

                    if (errors > 0) {

                        nHTML.addClass('uk-badge-e');
                        nHTML.html('<span>' + errors + ' Issue' + (errors > 1 ? 's' : '') + '</span>');

                    } else if (warnings > 0) {

                        nHTML.addClass('uk-badge-w');
                        nHTML.html('<span>' + warnings + ' Warning' + (warnings > 1 ? 's' : '') + '</span>');

                    } else if (ok > 0) {

                        nHTML.addClass('uk-badge-o');
                        nHTML.html('<span>Perfect</span>');

                    }

                    if (nHTML.find('span').text() != cText && nHTML.find('span').text() != '') {
                        counter.replaceWith(nHTML);
                    }
                },
                getContentRaw   : '',
                getContentText  : '',
                getContentLoop  : function () {

                    search_preview.calculations.shared_functions.getContentRaw = search_preview.calculations.shared_functions.getContent('raw');
                    search_preview.calculations.shared_functions.getContentText = search_preview.calculations.shared_functions.getContent('text');

                    setTimeout(function () {

                        search_preview.calculations.shared_functions.getContentLoop();

                    }, 500);
                },
                getContent      : function (format) {
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
                },
                getTitle        : function () {
                    return $('[name="XAGIO_SEO_TITLE"]').val()
                },
                getDescription  : function () {
                    return $('[name="XAGIO_SEO_DESCRIPTION"]').val();
                },
                getUrl          : function () {
                    return $('[name="XAGIO_SEO_URL"]').length >
                           0 ? $('[name="XAGIO_SEO_URL"]').val() : $('.xagio-g-domain').text().trim();
                },
                getKeyword      : function () {
                    return $('[name="XAGIO_SEO_TARGET_KEYWORD"]').val();
                },
                getWordCount    : function (s, b) {
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
        },
        clearTargetKeyword          : function () {
            $(document).on('click', '.clear-target-keyword', function (e) {
                e.preventDefault();
                $('#XAGIO_SEO_TARGET_KEYWORD').val('').trigger('change');
            });
        },
        toKeywordGroup              : function () {
            $(document).on('click', '.to-keyword-group', function (e) {
                e.preventDefault();

                if ($('.xagio-detach-group.xagio-hidden').length > 0) {
                    notify('error', 'Please attach a group before adding keywords.');
                    return;
                }

                let tr = $(this).parents('tr');
                let trc = tr.clone();
                trc.removeClass();

                tr.remove();
                $('.group-keywords').append(trc);
            });
        },
        toTargetKeyword             : function () {
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
        },
        processChangedTargetKeyword : function () {
            $('#XAGIO_SEO_TARGET_KEYWORD').val(target_keyword).trigger('change');
        },
        wordCloudOnClick            : function () {
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
                                $(this).html($(this).html().replace(new RegExp('\\b(' + match +
                                                                               ')\\b', 'g'), "<span class=\"highlightCloud\">" +
                                                                                             match + "</span>"));
                            }
                        }

                    });

                    let title_matches = title.html().match(new RegExp('\\b(' + $(this).text() + ')\\b', 'gi'));
                    let desc_matches = desc.html().match(new RegExp('\\b(' + $(this).text() + ')\\b', 'gi'));

                    if (title_matches !== null) {
                        if (title_matches.hasOwnProperty(0)) {
                            title.html(title.html().replace(title_matches[0], '<span class="highlightCloud">' +
                                                                              title_matches[0] + '</span>'));
                        }
                    }

                    if (desc_matches !== null) {
                        if (desc_matches.hasOwnProperty(0)) {
                            desc.html(desc.html().replace(new RegExp('\\b(' + desc_matches[0] +
                                                                     ')\\b', 'g'), '<span class="highlightCloud">' +
                                                                                   desc_matches[0] + '</span>'));
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
        },
        attachGroupEvents           : function () {

            $(document).on('click', '.xagio-detach-group', function (e) {
                let btn = $(this);
                let parent = btn.parents('.xagio-group-container');
                let group_id = parent.attr('data-group-id');

                btn.disable();

                $.post(xagio_data.wp_post, `action=xagio_detach_from_group&group_id=${group_id}`, function (d) {
                    btn.disable();
                    parent.attr('data-group-id', 0).addClass('xagio-hidden');
                    search_preview.generateKeywordsTable();
                    $('.xagio-g-tabs-extended').removeClass('xagio-hidden');
                    $('table.keywords').addClass('xagio-hidden');
                    $('.xagio-word-cloud').empty().removeClass('jqcloud').attr('style', '');
                });

            });

            $(document).on('click', 'li.xagio-group', function (e) {
                e.preventDefault();

                let selection = $(this);

                let group_id = selection.data('group-id');
                let post_id = $('#xagio_post_id').val();
                let h1 = $('input[name="post_title"]').val();
                if (isBlockEditor) {
                    h1 = $('.wp-block-post-title').text();
                }

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

                let data = [
                    {
                        name : "action",
                        value: "xagio_attach_to_project_group"
                    },
                    {
                        name : "group_id",
                        value: group_id
                    },
                    {
                        name : "post_id",
                        value: post_id
                    },
                    {
                        name : "h1",
                        value: h1
                    },
                    {
                        name : "url",
                        value: url
                    },
                    {
                        name : "title",
                        value: title
                    },
                    {
                        name : "desc",
                        value: desc
                    }
                ];

                $.post(xagio_data.wp_post, data, function (d) {
                    $('.xagio-group-container').attr('data-group-id', group_id).removeClass('xagio-hidden');
                    $('.xagio-g-tabs-extended').addClass('xagio-hidden');
                    $('table.keywords').removeClass('xagio-hidden');
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
                        results_holder.html('<div class="xagio-search-info">Searching for groups <i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></div>');

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
                                        list += `<li class="xagio-group" data-project-id="${row['project_id']}" data-group-id="${row['id']}"><i class="xagio-icon xagio-icon-clock"></i> ${row['group_name']}</li>`;
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

                $('.xagio-group-search-results').removeClass('xagio-hidden');
                $('.xagio-search-group-input').addClass('xagio-opened');
            });

            $(window).click(function () {
                $('.xagio-group-search-results').addClass('xagio-hidden');
                $('.xagio-search-group-input').removeClass('xagio-opened');
            });
        },
        editGroupEvents             : function () {
            $(document).on('click', '.xagio-edit-group', function (e) {
                e.preventDefault();

                let group_id = $(this).parents('.xagio-group-container').attr('data-group-id');
                let project_id = $(this).parents('.xagio-group-container').attr('data-project-id');

                window.open(`/wp-admin/admin.php?page=xagio-projectplanner&pid=${project_id}&gid=${group_id}`, '_blank');
            });
        },
        saveGroupEvents             : function () {
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

                // Convert keywords array into individual form fields
                keywords.forEach((keyword, index) => {
                    Object.keys(keyword).forEach(key => {
                        data.push({
                                      name : `keywords[${index}][${key}]`,
                                      value: keyword[key]
                                  });
                    });
                });

                btn.disable();

                $.post(xagio_data.wp_post, data, function (d) {
                    // Give user a sense of success
                    setTimeout(function () {
                        btn.disable();
                        notify('success', 'Changes saved successfully.');
                    }, 1500);
                });

            });
        },
        generateKeywordsTable       : function () {
            let kwData = $('#xagio_seo .group-keywords');
            let wdCloud = $('#xagio_seo .xagio-word-cloud');
            let group_id = $('.xagio-group-container').attr('data-group-id');
            let wordcloud_keywords = [];

            if (group_id != 0) {

                $.post(xagio_data.wp_post, `action=xagio_getAttachedGroup&group_id=${group_id}`, function (raw_keywords) {

                    if (raw_keywords.length < 1) {
                        kwData.html(`<td colspan="9" class="xagio-text-center"><i class="xagio-icon xagio-icon-info"></i> Attached group does not have any keywords.</td>`);
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
                            } else if (parseFloat(search_preview.cf_template.volume_red) >=
                                       parseFloat(keyword.volume)) {
                                volume_color = 'tr_red';
                            } else if (parseFloat(search_preview.cf_template.volume_red) < parseFloat(keyword.volume) &&
                                       parseFloat(search_preview.cf_template.volume_green) >
                                       parseFloat(keyword.volume)) {
                                volume_color = 'tr_yellow';
                            } else if (parseFloat(search_preview.cf_template.volume_green) <=
                                       parseFloat(keyword.volume)) {
                                volume_color = 'tr_green';
                            }

                            if (keyword.cpc == "") {
                                cpc_color = '';
                            } else if (parseFloat(search_preview.cf_template.cpc_red) >= parseFloat(keyword.cpc)) {
                                cpc_color = 'tr_red';
                            } else if (parseFloat(search_preview.cf_template.cpc_red) < parseFloat(keyword.cpc) &&
                                       parseFloat(search_preview.cf_template.cpc_green) > parseFloat(keyword.cpc)) {
                                cpc_color = 'tr_yellow';
                            } else if (parseFloat(search_preview.cf_template.cpc_green) <= parseFloat(keyword.cpc)) {
                                cpc_color = 'tr_green';
                            }

                            if (keyword.intitle == "") {
                                intitle_color = '';
                            } else if (parseFloat(search_preview.cf_template.intitle_red) <=
                                       parseFloat(keyword.intitle)) {
                                intitle_color = 'tr_red';
                            } else if (parseFloat(search_preview.cf_template.intitle_red) >
                                       parseFloat(keyword.intitle) &&
                                       parseFloat(search_preview.cf_template.intitle_green) <
                                       parseFloat(keyword.intitle)) {
                                intitle_color = 'tr_yellow';
                            } else if (parseFloat(search_preview.cf_template.intitle_green) >=
                                       parseFloat(keyword.intitle)) {
                                intitle_color = 'tr_green';
                            }

                            if (keyword.inurl == "") {
                                inurl_color = '';
                            } else if (parseFloat(search_preview.cf_template.inurl_red) <= parseFloat(keyword.inurl)) {
                                inurl_color = 'tr_red';
                            } else if (parseFloat(search_preview.cf_template.inurl_red) > parseFloat(keyword.inurl) &&
                                       parseFloat(search_preview.cf_template.inurl_green) < parseFloat(keyword.inurl)) {
                                inurl_color = 'tr_yellow';
                            } else if (parseFloat(search_preview.cf_template.inurl_green) >=
                                       parseFloat(keyword.inurl)) {
                                inurl_color = 'tr_green';
                            }

                            if (title_ratio == "") {
                                tr_color = '';
                            } else if (parseFloat(title_ratio) >=
                                       parseFloat(search_preview.cf_template.title_ratio_red)) {
                                tr_color = 'tr_red';
                            } else if (parseFloat(title_ratio) <
                                       parseFloat(search_preview.cf_template.title_ratio_red) &&
                                       parseFloat(title_ratio) >
                                       parseFloat(search_preview.cf_template.title_ratio_green)) {
                                tr_color = 'tr_yellow';
                            } else if (parseFloat(title_ratio) <=
                                       parseFloat(search_preview.cf_template.title_ratio_green)) {
                                tr_color = 'tr_green';
                            }

                            if (url_ratio == "") {
                                ur_color = '';
                            } else if (parseFloat(url_ratio) >= parseFloat(search_preview.cf_template.url_ratio_red)) {
                                ur_color = 'tr_red';
                            } else if (parseFloat(url_ratio) < parseFloat(search_preview.cf_template.url_ratio_red) &&
                                       parseFloat(url_ratio) > parseFloat(search_preview.cf_template.url_ratio_green)) {
                                ur_color = 'tr_yellow';
                            } else if (parseFloat(url_ratio) <=
                                       parseFloat(search_preview.cf_template.url_ratio_green)) {
                                ur_color = 'tr_green';
                            }

                            /**
                             *
                             *     CONDITIONAL FORMATTING
                             *
                             */


                            let tr = $('<tr data-id="' + keyword.id + '"></tr>');
                            tr.append('<td class="xagio-text-center"><button data-xagio-tooltip data-xagio-title="Set as Target Keyword." class="to-target-keyword" type="button"><i class="xagio-icon xagio-icon-arrow-up"></i></button></td>');
                            tr.append('<td><div contenteditable="true" class="keywordInput" data-target="keyword">' +
                                      keyword.keyword + '</div></td>');

                            if (keyword.queued == 2) {
                                tr.append('<td data-target="volume"  class="xagio-text-center" title="This value is currently under analysis. Please check back later to see the results."><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></td>');
                                tr.append('<td data-target="cpc"  class="xagio-text-center" title="This value is currently under analysis. Please check back later to see the results."><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></td>');
                            } else {
                                tr.append('<td class="xagio-text-center ' + volume_color +
                                          '"><div contenteditable="true" class="keywordInput" data-target="volume">' +
                                          search_preview.parseNumber(keyword.volume) + '</div></td>');
                                tr.append('<td class="xagio-text-center ' + cpc_color +
                                          '"><div contenteditable="true" class="keywordInput" data-target="cpc">' +
                                          keyword.cpc + '</div></td>');
                            }

                            if (keyword.queued == 1 || alsoQueued == true) {

                                actions.runBatchCron();

                                tr.append('<td data-target="intitle" class="xagio-text-center" title="This value is currently under analysis. Please check back later to see the results."><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></td>');
                                tr.append('<td data-target="inurl" class="xagio-text-center" title="This value is currently under analysis. Please check back later to see the results."><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></td>');
                            } else {

                                tr.append('<td data-target="intitle" class="xagio-text-center ' + intitle_color +
                                          '"><div contenteditable="true" class="keywordInput" data-target="intitle">' +
                                          search_preview.parseNumber(keyword.intitle) + '</div></td>');
                                tr.append('<td data-target="inurl" class="xagio-text-center ' + inurl_color +
                                          '"><div contenteditable="true" class="keywordInput" data-target="inurl">' +
                                          search_preview.parseNumber(keyword.inurl) + '</div></td>');
                            }

                            if (title_ratio != "") {
                                if (tr_color == "tr_green" &&
                                    (parseFloat(search_preview.cf_template.tr_goldbar_volume) >=
                                    parseFloat(keyword.volume) &&
                                    parseFloat(search_preview.cf_template.tr_goldbar_intitle) >=
                                    parseFloat(keyword.intitle))) {
                                    tr.append('<td class="xagio-text-center ' + tr_color +
                                              '" data-target="tr"><div contenteditable="false" class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Value: ' +
                                              parseFloat(title_ratio).toFixed(3) + '"><img src="' +
                                              xagio_data.plugins_url + 'assets/img/gold.webp"></div></td>');
                                } else {
                                    tr.append('<td class="xagio-text-center ' + tr_color +
                                              '" data-target="tr"><div contenteditable="true" class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Value: ' +
                                              parseFloat(title_ratio).toFixed(3) + '">' +
                                              parseFloat(title_ratio).toFixed(3) + '</div></td>');
                                }
                            } else {
                                tr.append('<td class="xagio-text-center ' + tr_color +
                                          '" data-target="tr"><div contenteditable="true" class="keywordInput" data-target="tr" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Search Volume and InTitle metrics must be retrieved first to see the Title Ratio."></div></td>');
                            }

                            if (url_ratio != "") {
                                if (ur_color == "tr_green" &&
                                    (parseFloat(search_preview.cf_template.ur_goldbar_volume) >=
                                    parseFloat(keyword.volume) &&
                                    parseFloat(search_preview.cf_template.ur_goldbar_intitle) >=
                                    parseFloat(keyword.inurl))) {
                                    tr.append('<td class="xagio-text-center ' + ur_color +
                                              '" data-target="ur"><div contenteditable="false" class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Value: ' +
                                              parseFloat(url_ratio).toFixed(3) + '"><img src="' +
                                              xagio_data.plugins_url + 'assets/img/gold.webp"></div></td>');
                                } else {
                                    tr.append('<td class="xagio-text-center ' + ur_color +
                                              '" data-target="ur"><div contenteditable="true" class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Value: ' +
                                              parseFloat(url_ratio).toFixed(3) + '">' +
                                              parseFloat(url_ratio).toFixed(3) + '</div></td>');
                                }
                            } else {
                                tr.append('<td class="xagio-text-center ' + ur_color +
                                          '" data-target="ur"><div contenteditable="true" class="keywordInput" data-target="ur" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Search Volume and InURL metrics must be retrieved first to see the URL Ratio."></div></td>');
                            }

                            let rank = keyword.rank.isJSON();
                            let rank_cell = '';

                            if (rank == 0) {
                                rank_cell = '<span data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Not Added"><span style="display: none;">99999</span></span>';
                            } else if (rank == 501) {
                                rank_cell = '<span data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Analysing..."><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i><span style="display: none;">99998</span></span>';
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
                                    rank_cell = '<a href="https://app.xagio.net/rank_tracker?domain=' +
                                                xagio_data.domain +
                                                '&keyword=' + encodeURIComponent(keyword.keyword) +
                                                '" target="_blank" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="' +
                                                rank_title +
                                                '"><i class=\'xagio-icon xagio-icon-ban\'></i><span style="display: none;">99997</span></a>';
                                } else {
                                    if ($.isNumeric(rank)) {
                                        rank_cell = max;
                                    } else {
                                        rank_cell = '<a href="https://app.xagio.net/rank_tracker?domain=' +
                                                    xagio_data.domain +
                                                    '&keyword=' + encodeURIComponent(keyword.keyword) +
                                                    '" target="_blank" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="' +
                                                    rank_title + '">' + max + '</a>';
                                    }
                                }

                            }

                            tr.append('<td class="xagio-text-center">' + rank_cell + '</td>');

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

                kwData.html('<td colspan="9" class="xagio-text-center"><i class="xagio-icon xagio-icon-info"></i> No group has been attached to this page.</td>');
                $('.searchProjectGroups').val('');
            }
        },
        updateTableSorters          : function () {
            // Table sorting
            $(".keywords").tablesorter({
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
                                       }).trigger('updateAll');
        },
        initSorters                 : function () {

            search_preview.updateTableSorters();

            let sugge_kws = $('.suggestion-keywords');
            let group_kws = $('.group-keywords');

            sugge_kws.multisortable({
                                        items        : "tr",
                                        selectedClass: "selected"
                                    });

            group_kws.multisortable({
                                        items        : "tr",
                                        selectedClass: "selected"
                                    });

            sugge_kws.sortable({
                                   connectWith: ".uk-sortable",
                                   cancel     : "input,textarea,button,select,option,[contenteditable]"
                               })

            group_kws.sortable({
                                   cancel: "input,textarea,button,select,option,[contenteditable]"
                               }).on("sortreceive", function (event, ui) {

                if ($('.xagio-detach-group.xagio-hidden').length > 0) {
                    notify('error', 'Please attach a group before adding keywords.');
                    ui.sender.sortable("cancel");
                } else {
                    search_preview.updateTableSorters();
                }

            });
        },
        generateWordCloud           : function (keywords) {
            let cloudBoxTemplate = $('.xagio-word-cloud');
            cloudBoxTemplate.jQCloud(search_preview.calculateAndTrim(keywords), {
                colors    : [
                    "#ffffff",
                    "#FAF9F6",
                    "#F1F0ED",
                    "#E5E4E2",
                    "#D9D8D6"
                ],
                autoResize: true,
                height    : '250',
                fontSize  : {
                    from: 0.09,
                    to  : 0.05
                },
            });
        },
        parseNumber                 : function (num) {
            if (num == null || num == "") {
                return 0;
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
            let wordCount = search_preview.getTitleLength();
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

            let wordCount = search_preview.getDescriptionLength();
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

            if (p >= 100) {
                p = 100;
            }
            desc_check_line.css('--xagio-grad-fill', p + '%');
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
            } else if ($('.editor-document-bar__post-title').length > 0) {
                title = $('.editor-document-bar__post-title').html().trim();
            } else {
                title = $('#prs-title').val().trim();
            }
            return title.toLowerCase();
        },
        getTitleLength              : () => {
            let title = $('#XAGIO_SEO_TITLE').html();
            const shortcodeReplacementRegex = /<div[^>]*data-render="([^"]*)"[^>]*>(?:.*?)<\/div>/g;
            title = title.replace(shortcodeReplacementRegex, (match, p1) => p1);
            title = title.replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
            return title;
        },
        getDescriptionLength        : () => {
            let desc = $('#XAGIO_SEO_DESCRIPTION').html();
            const shortcodeReplacementRegex = /<div[^>]*data-render="([^"]*)"[^>]*>(?:.*?)<\/div>/g;
            desc = desc.replace(shortcodeReplacementRegex, (match, p1) => p1);
            desc = desc.replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().length;
            return desc;

        },
        editorInit                  : function () {

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
                let text = $(this).html();
                let id = $(this).data('target');

                const shortcodeReplacementRegex = /<div[^>]*data-shortcode="([^"]*)"[^>]*>(?:.*?)<\/div>/g;
                text = text.replace(shortcodeReplacementRegex, (match, p1) => '{' + p1 + '}');
                text = text.replace(/\&nbsp\;/g, ' ').replace(/\s+/g, ' ').trim().replace(/<\/?[^>]+(>|$)/g, "").trim();
                // remove spaces between {{ }} tags
                text = text.replace(/{\s+/g, '{').replace(/\s+}/g, '}');
                $('#' + id).val(text).trigger('change');
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
        allowances          : [],
        refreshXags         : function () {
            $.post(xagio_data.wp_post, 'action=xagio_refreshXags', function (d) {
                if (d.status == 'error') {
                    actions.allowances.xags_bank = 0;
                    actions.allowances.xags_renew = 0;
                } else {
                    actions.allowances.xags_bank = d.data.xags;
                    actions.allowances.xags_renew = d.data.xags_allowance;
                    actions.allowances.cost = d.data.xags_cost;
                    actions.allowances.xags_total = d.data.xags_total;
                }
            });
        },
        init                : function () {
            actions.refreshXags();
            actions.removeDragHandler();
            actions.switchTabOptions();
            actions.initSliders();
            actions.initYoutubeSearch();
            actions.initPixabaySearch();
        },
        accordion           : function () {
            $('.xagio-accordion-title').on('click', function (e) {
                // if event target is not the title, return
                if ($(e.target).is('button') || $(e.target).parents('button').length) {
                    return;
                }
                $(this).find('.far').toggleClass('xagio-icon-arrow-down xagio-icon-arrow-up');
                $(this).next().fadeToggle(function () {
                    scripts.refreshEditors();
                });
            });
        },
        allowedToRun        : function () {
            return $('#xagio_seo').length > 0;
        },
        initSliders         : function () {

            $(document).on('click', '.xagio-slider-container .xagio-slider-label', function () {
                $(this).prev('.xagio-slider-frame').find('.xagio-slider-button').trigger('click');
            });
            $(document).on('click', '.xagio-slider-container .page-seo-section-text', function () {
                $(this).parents('.xagio-slider-container').find('.xagio-slider-button').trigger('click');
            });

            $(document).on('click', '.xagio-slider-frame .xagio-slider-button', function () {
                let btn = $(this);
                let parent = $(this).parents('.xagio-slider-container');
                let target = $(this).attr('data-element');

                let element = parent.find('#' + target);

                if (element.length < 1) {
                    element = parent.find('.' + target);
                }

                if (element.next().attr('name') == 'post_id') {
                    let post_id = element.next().val();
                    let value = element.val();
                    $.post(xagio_data.wp_post, `action=xagio_change_seo_status&post_id=${post_id}&status=${value}`);
                }

                if (btn.has('[data-with-text]')) {
                    parent.find('.page-seo-section-text').text(parseInt(element.val()) ? 'Enabled' : 'Disabled');
                }

                if (parent.parents('.xagio-robots-optional').length > 0) {
                    element.prev('input[name="XAGIO_SEO_META_ROBOTS_ADVANCED[]"]').prop("disabled", !!parseInt(element.val())).val(parseInt(element.val()) ? '' : element.attr('name')).trigger('change');

                }


                $(`.${target}`).removeClass('on').removeClass('off').addClass(parseInt(element.val()) ? 'on' : 'off');
            });

        },
        switchTabOptions    : function () {
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
        },
        initPixabaySearch   : function () {
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

                if (title == '' || alt == '') {
                    xagioNotify("danger", "Please set up Image Title and Alt before proceeding.");
                    return false;
                }

                let button = $(this);
                button.disable('Downloading...');

                let data = [
                    {
                        name : 'action',
                        value: 'xagio_pixabay_download'
                    },
                    {
                        name : 'img',
                        value: image
                    },
                    {
                        name : 'title',
                        value: title
                    },
                    {
                        name : 'alt',
                        value: alt
                    }
                ];

                $.post(xagio_data.wp_post, data, function (d) {

                    button.disable();

                    if (d.status == 'success') {

                        let image_path = xagio_data.uploads_dir.baseurl + '/' + d.data.file;
                        let image = '<img class="alignnone size-medium wp-image-' + d.id + '" title="' + title +
                                    '" alt="' + alt + '" src="' + image_path + '"/>';

                        tinyMCE.activeEditor.execCommand('mceInsertContent', false, image);

                        modal.close();

                    } else {
                        xagioNotify("danger", d.message);
                    }

                });

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
                    emptyQuery: '<span class="xagio_pixabay_results_msg"><i class="xagio-icon xagio-icon-warning"></i> No images found for your search query.</span>',
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

                           url     : 'https://pixabay.com/api/?key=25026237-b49b785012e885e4aabca4dba&q=' + query +
                                     '&image_type=photo&pretty=true&per_page=200',
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
                                       let html = '<div class="pixabay-image" data-url="' + img.webformatURL +
                                                  '"><img src="' + img.previewURL + '"/><div class="pixabay-size">' +
                                                  img.webformatWidth + 'x' + img.webformatHeight + '</div></div>';
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
        },
        initYoutubeSearch   : function () {
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
                let iframe = '<iframe width="' + width + '" height="' + height +
                             '" src="https://www.youtube.com/embed/' + id + args +
                             '" frameborder="0" allowfullscreen></iframe>';

                tinyMCE.activeEditor.execCommand('mceInsertContent', false, iframe);

                $('.xagio_youtube_search').show();
                $('.xagio_youtube_video').hide();
                $('.xagio_youtube_insert').hide();

                modal.close();
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

                $('.xagio_youtube_preview').empty().append('<iframe width="100%" height="350" src="https://www.youtube.com/embed/' +
                                                           id + '" frameborder="0" allowfullscreen></iframe>');
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
        },
        performYoutubeSearch: function () {
            let messages = {
                emptyQuery: '<span class="xagio_youtube_results_msg"><i class="xagio-icon xagio-icon-warning"></i> No videos found for your search query.</span>',
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

                       url     : 'https://www.googleapis.com/youtube/v3/search?part=snippet&q=' + query +
                                 '&pageToken=' + page +
                                 '&maxResults=5&order=viewCount&type=video&key=AIzaSyCDkcVzELYEXJ8utxCnHyyx8r5LTadbbdg',
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

                               video += '<div data-id="' + id + '" class="yt-video-container">' +
                                        '<div class="yt-image">' + '<img src="' + image + '"/>' + '</div>' +
                                        '<div class="yt-meta">' + '<h3>' + title + '</h3>' + '<span>' + from +
                                        '</span>' + '<p>' + desc + '</p>' + '</div>' + '</div>';

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
        },
        removeDragHandler   : function () {
            $('#xagio_ai .hndle').removeClass();
        },
        addCustomBulkAction : function () {
            $("#bulk-action-selector-top").append(
                '<optgroup label="SEO Settings">' +
                '<option value="xagio_seo_enable">Xagio SEO — Turn On</option>' +
                '<option value="xagio_seo_disable">Xagio SEO — Turn Off</option>' +
                '</optgroup>'
            );
            $("#bulk-action-selector-bottom").append(
                '<optgroup label="SEO Settings">' +
                '<option value="xagio_seo_enable">Xagio SEO — Turn On</option>' +
                '<option value="xagio_seo_disable">Xagio SEO — Turn Off</option>' +
                '</optgroup>'
            );
        }

    }

})(jQuery);