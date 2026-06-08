/*
 * RingRobin Gutenberg blocks.
 *
 * Registers two dynamic blocks (server-rendered via PHP):
 *   - xagio-ringrobin/form  — RingRobin Form embed
 *   - xagio-ringrobin/text  — RingRobin Text Widget embed (floating / inline / embedded)
 *
 * Designed to run without a build step — uses wp.element.createElement
 * (aliased to el) and reads widget data from window.xagioRingRobinBlocks
 * which is localized by inc/xagio_gutenberg_ringrobin.php.
 */
(function (wp) {
    if (!wp || !wp.blocks || !wp.element) {
        return;
    }

    var blocks       = wp.blocks;
    var element      = wp.element;
    var blockEditor  = wp.blockEditor || wp.editor;
    var components   = wp.components;
    var i18n         = wp.i18n;

    if (!blockEditor || !components || !i18n) {
        return;
    }

    var el                = element.createElement;
    var Fragment          = element.Fragment;
    var __                = i18n.__;
    var SelectControl     = components.SelectControl;
    var PanelBody         = components.PanelBody;
    var useBlockProps     = blockEditor.useBlockProps;
    var InspectorControls = blockEditor.InspectorControls;

    var data        = window.xagioRingRobinBlocks || {};
    var allWidgets  = Array.isArray(data.widgets) ? data.widgets : [];
    var formWidgets = allWidgets.filter(function (w) { return w && w.type === 'form'; });
    var textWidgets = allWidgets.filter(function (w) { return w && w.type === 'text'; });

    function latestId(list) {
        var latest        = '';
        var latestCreated = '';
        list.forEach(function (w) {
            if (!w || !w.id) { return; }
            var c = w.created_at || '';
            if (!latest || c >= latestCreated) {
                latest        = w.id;
                latestCreated = c;
            }
        });
        return latest;
    }

    function buildOptions(list, emptyLabel) {
        var opts = [{ label: emptyLabel, value: '' }];
        list.forEach(function (w) {
            if (!w || !w.id) { return; }
            opts.push({ label: w.name || w.id, value: w.id });
        });
        return opts;
    }

    function lookupName(list, id) {
        for (var i = 0; i < list.length; i++) {
            if (list[i] && list[i].id === id) {
                return list[i].name || list[i].id;
            }
        }
        return id;
    }

    var latestForm  = latestId(formWidgets);
    var latestText  = latestId(textWidgets);
    var formOptions = buildOptions(formWidgets, __('— Select a form widget —', 'xagio-seo'));
    var textOptions = buildOptions(textWidgets, __('— Select a text widget —', 'xagio-seo'));

    var modeOptions = [
        { label: __('Floating (bottom of page)', 'xagio-seo'), value: 'floating' },
        { label: __('Inline button', 'xagio-seo'),             value: 'inline'   },
        { label: __('Embedded phone', 'xagio-seo'),            value: 'embedded' }
    ];

    function placeholderCard(title, sub, hasSelection) {
        return el('div', {
            style: {
                padding:      '24px',
                background:   hasSelection ? '#eef5ff' : '#f5f7fb',
                border:       hasSelection ? '1px solid #b8d4ff' : '1px dashed #c5c5c5',
                borderRadius: '8px',
                textAlign:    'center',
                color:        hasSelection ? '#1a4674' : '#545454'
            }
        },
            el('strong', null, title),
            sub ? el('div', { style: { marginTop: '6px', fontSize: '13px', color: '#545454' } }, sub) : null,
            hasSelection
                ? el('div', { style: { marginTop: '4px', fontSize: '12px', color: '#9ca3af' } }, __('(rendered on the live page)', 'xagio-seo'))
                : null
        );
    }

    blocks.registerBlockType('xagio-ringrobin/form', {
        apiVersion: 2,
        title:      __('RingRobin Form', 'xagio-seo'),
        description: __('Drop a RingRobin form widget into the page.', 'xagio-seo'),
        icon:       'feedback',
        category:   'xagio',
        keywords:   [__('ringrobin', 'xagio-seo'), __('form', 'xagio-seo'), __('lead', 'xagio-seo'), __('tracking', 'xagio-seo')],
        supports:   { html: false, anchor: false },
        attributes: {
            formId: { type: 'string', default: latestForm }
        },
        edit: function (props) {
            var blockProps = useBlockProps();
            var formId     = props.attributes.formId || '';
            var name       = formId ? lookupName(formWidgets, formId) : '';

            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('RingRobin Form', 'xagio-seo'), initialOpen: true },
                        el(SelectControl, {
                            label:    __('Form widget', 'xagio-seo'),
                            value:    formId,
                            options:  formOptions,
                            onChange: function (v) { props.setAttributes({ formId: v }); },
                            help:     __("Pick a form widget from this site's linked RingRobin campaign. Create new ones from Xagio Settings → Integrations.", 'xagio-seo')
                        })
                    )
                ),
                el('div', blockProps,
                    formId
                        ? placeholderCard(__('RingRobin Form', 'xagio-seo'), name, true)
                        : placeholderCard(__('RingRobin Form', 'xagio-seo'), __('Select a form widget from the sidebar.', 'xagio-seo'), false)
                )
            );
        },
        save: function () { return null; }
    });

    blocks.registerBlockType('xagio-ringrobin/text', {
        apiVersion: 2,
        title:      __('RingRobin Text Widget', 'xagio-seo'),
        description: __('Drop a click-to-text widget into the page.', 'xagio-seo'),
        icon:       'sms',
        category:   'xagio',
        keywords:   [__('ringrobin', 'xagio-seo'), __('text', 'xagio-seo'), __('sms', 'xagio-seo'), __('click-to-text', 'xagio-seo')],
        supports:   { html: false, anchor: false },
        attributes: {
            textId: { type: 'string', default: latestText },
            mode:   { type: 'string', default: 'inline' }
        },
        edit: function (props) {
            var blockProps = useBlockProps();
            var textId     = props.attributes.textId || '';
            var mode       = props.attributes.mode || 'inline';
            var name       = textId ? lookupName(textWidgets, textId) : '';
            var modeLabel  = mode === 'floating' ? __('Floating', 'xagio-seo')
                            : mode === 'embedded' ? __('Embedded phone', 'xagio-seo')
                            : __('Inline button', 'xagio-seo');

            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('RingRobin Text Widget', 'xagio-seo'), initialOpen: true },
                        el(SelectControl, {
                            label:    __('Text widget', 'xagio-seo'),
                            value:    textId,
                            options:  textOptions,
                            onChange: function (v) { props.setAttributes({ textId: v }); }
                        }),
                        el(SelectControl, {
                            label:    __('Display mode', 'xagio-seo'),
                            value:    mode,
                            options:  modeOptions,
                            onChange: function (v) { props.setAttributes({ mode: v }); }
                        })
                    )
                ),
                el('div', blockProps,
                    textId
                        ? placeholderCard(__('RingRobin Text Widget', 'xagio-seo'), name + ' · ' + modeLabel, true)
                        : placeholderCard(__('RingRobin Text Widget', 'xagio-seo'), __('Select a text widget from the sidebar.', 'xagio-seo'), false)
                )
            );
        },
        save: function () { return null; }
    });
})(window.wp);
