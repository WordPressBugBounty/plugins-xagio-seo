var queue = [];

(function ($) {
    'use strict';

    const pollInterval = setInterval(() => {

        let ids = queue.map(item => item.id);

        if (ids.length < 1) return;

        $.ajax({
                   url   : xagio_data.wp_post,
                   method: 'POST',
                   data  : {
                       action: 'xagio_get_ai_frontend_output',
                       ids   : ids.join(','),
                   },
               })
         .done(function (response) {
             // response should be an array of { id, status, data }

             response.data.forEach(task => {
                 const {
                           id,
                           input,
                           output
                       } = task;

                 // find the queued object
                 const qItem = queue.find(q => q.id === id);
                 if (!qItem) return;

                 // use its retarget_id to locate the element again
                 const $el = $('.' + qItem.retarget_id);

                 // if the parent is our wrapper, unwrap it
                 const $parent = $el.parent();
                 if ($parent.is('span.xag_ai_loading')) {
                     $el.unwrap();
                 } else {
                     // otherwise just remove the class from the element itself
                     $el.removeClass('xag_ai_loading');
                 }

                 // swap in new data
                 if (input === 'TEXT_CONTENT') {
                     $el.text(output);
                 } else {
                     $el.attr('src', output + '?_=' + Date.now() + '_' + qItem.retarget_id);
                     $el.removeAttr('srcset');
                 }

                 // remove this entry from the queue
                 queue = queue.filter(q => q.id !== id);

                 if (queue.length == 0) {
                     $('#send-to-ai-btn').removeAttr('disabled');
                     // Optional: only clear when user is not typing there
                     if (!$('#ai-additional-prompt').is(':focus')) {
                         $('#ai-additional-prompt').val('');
                     }
                 }
             });

         })
         .fail(function (jqXHR, textStatus) {
             console.error('Polling error:', textStatus);
         });
    }, 2000);

    let prices = null;

    let inspectorEnabled = false;
    let lastHovered = null;
    let selectedElement = null;

    function enableInspector() {
        const $body = $('body');
        const isElementor = $body.hasClass('elementor-page') || $body.hasClass('elementor-editor-active');
        const isKadence   = $body.hasClass('wp-theme-kadence');
        const pageType    = isElementor ? 'elementor' : (isKadence ? 'gutenberg' : 'default');

        // ---------- IGNORE ZONES ----------
        const IGNORE_BASE =
            '#xagio-backups-modal, .dialog-widget, #inspector-toggle, #inspector-info, #wpadminbar';

        const IGNORE_ELEMENTOR =
            '[data-elementor-type="header"], [data-elementor-type="footer"]';

        // Kadence header/footer/off-canvas + sticky/placeholder wrappers
        const IGNORE_KADENCE = [
            'header[class*="wp-block-kadence-header"]',
            'header[role="banner"]',
            '.kb-header-container',
            '.kb-header-sticky-wrapper',
            '.kb-header-placeholder-wrapper',
            '.kadence-header-row-inner',
            '.wp-block-kadence-navigation',
            '.wp-block-site-logo',
            '.wp-block-kadence-off-canvas',
            '.kb-off-canvas-overlay',
            '.kb-off-canvas-inner-wrap',
            '#colophon',
            'footer.site-footer',
            '.site-footer-wrap',
            '.site-top-footer-wrap',
            '.site-middle-footer-wrap',
            '.site-bottom-footer-wrap',
            '.site-footer-row-container',
            '.site-footer-row',
        ].join(', ');

        const IGNORE = [
            IGNORE_BASE,
            isElementor ? IGNORE_ELEMENTOR : null,
            isKadence   ? IGNORE_KADENCE   : null
        ].filter(Boolean).join(', ');

        // Ensure "no hand cursor" over ignored areas while inspector is on
        $body.addClass('xag-inspector-active');
        if (!document.getElementById('xag-inspector-kadence-style')) {
            const css = `
      .xag-inspector-active ${IGNORE},
      .xag-inspector-active ${IGNORE} a,
      .xag-inspector-active ${IGNORE} * { cursor: default !important; }
    `;
            const style = document.createElement('style');
            style.id = 'xag-inspector-kadence-style';
            style.appendChild(document.createTextNode(css));
            document.head.appendChild(style);
        }

        // ---------- HELPERS ----------
        function hasBackgroundImage($el) {
            const bg = $el.css('background-image');
            return bg && bg !== 'none' && /^url\(["']?.+["']?\)$/.test(bg);
        }
        function extractBackgroundImageUrl($el) {
            return $el.css('background-image').replace(/^url\(["']?(.+?)["']?\)$/, '$1');
        }
        function trimTo150(str) { return (!str || str.length <= 150) ? (str || '') : (str.slice(0,150) + '...'); }
        function getOwnText($el) { return ($el.clone().children().remove().end().text() || '').trim(); }

        // Gutenberg/Kadence: allow only true text blocks (incl. Kadence components)
        function isGutenbergTextElement($el) {
            // Tag allowlist
            if ($el.is('p, h1, h2, h3, h4, h5, h6, li, figcaption, blockquote, pre, code')) return true;

            const cls = $el.attr('class') || '';
            // Core text blocks
            if (/\bwp-block-(paragraph|heading|quote|pullquote|list|table|preformatted|verse|code)\b/.test(cls)) return true;
            // Kadence text-oriented blocks
            if (/\bwp-block-kadence-advancedheading\b/.test(cls)) return true;
            if (/\bwp-block-kadence-typography\b/.test(cls)) return true;
            // Kadence icon-list item text
            if (/\bkt-svg-icon-list-text\b/.test(cls)) return true;
            // Kadence button inner text
            if (/\bkt-btn-inner-text\b/.test(cls) || /\bkb-btn-inner-text\b/.test(cls)) return true;
            // Kadence testimonial name
            if (/\bkb-testimonial-name\b/.test(cls) || /\bkt-testimonial-name\b/.test(cls)) return true;
            // Kadence accordion titles & wrappers
            if (/\bkt-blocks-accordion-title\b/.test(cls)) return true;
            if (/\bkt-blocks-accordion-title-wrap\b/.test(cls)) return true;
            if (/\bkt-accordion-header-wrap\b/.test(cls)) return true;
            if (/\bkt-blocks-accordion-header\b/.test(cls)) return true;

            // Avoid nav/link containers
            if (
                /\bwp-block-kadence-(navigation|header|off-canvas)\b/.test(cls) ||
                $el.closest('.wp-block-kadence-navigation, nav.navigation, .menu, .kb-navigation').length
            ) return false;

            return false;
        }

        // If the clicked node is a tiny inner span, climb up/down to the nearest allowed text block
        function findAllowedGutenbergText($start) {
            const ALLOWED_SELECTOR = [
                'p,h1,h2,h3,h4,h5,h6,li,figcaption,blockquote,pre,code',
                '.wp-block-paragraph,.wp-block-heading,.wp-block-quote,.wp-block-pullquote,.wp-block-list,.wp-block-table,.wp-block-preformatted,.wp-block-verse,.wp-block-code',
                '.wp-block-kadence-advancedheading,.wp-block-kadence-typography',
                '.kt-svg-icon-list-text',
                '.kt-btn-inner-text,.kb-btn-inner-text',
                '.kb-testimonial-name,.kt-testimonial-name',
                '.kt-blocks-accordion-title,.kt-blocks-accordion-title-wrap,.kt-blocks-accordion-header,.kt-accordion-header-wrap'
            ].join(',');

            // Prefer the actual title element if present inside containers like h3/button
            const $titleInside = $start.find('.kt-blocks-accordion-title').first();
            if ($titleInside.length && !$titleInside.closest(IGNORE).length) return $titleInside;

            // Otherwise climb to the nearest allowed ancestor
            const $closest = $start.is(ALLOWED_SELECTOR) ? $start : $start.closest(ALLOWED_SELECTOR);
            if ($closest.length && !$closest.closest(IGNORE).length) return $closest.first();

            return $(); // empty jQuery object
        }

        // Ignore the Kadence wrapper only when it is the *actual* target
        function isKadenceWrapperSelfTarget(el) {
            if (!isKadence) return false;
            if (el.id === 'wrapper' && el.classList.contains('wp-site-blocks')) return true;
            if (el.classList.contains('wp-site-blocks') && el.classList.contains('site')) return true;
            return false;
        }

        // ---------- BINDINGS ----------
        $body
            .on('keyup', '#ai-additional-prompt', function (e) {
                if (e.key === 'Enter') {
                    const $btn = $('#send-to-ai-btn');
                    if (!$btn.prop('disabled')) {
                        $btn.trigger('click');
                    }
                    e.preventDefault();
                }
            })
            .on('change', '[name="ai-action-type"]', function () {
                if ($(this).val() == 'generate') {
                    $('.inspector-cost .inspector-cost-value').html(prices.IMAGE_GEN[0].price.toFixed(2));
                } else {
                    $('.inspector-cost .inspector-cost-value').html(prices.IMAGE_EDIT[0].price.toFixed(2));
                }
            })
            .on('mouseover.inspector', '*', function (e) {
                if (isKadenceWrapperSelfTarget(this)) return;
                if ($(this).closest(IGNORE).length) return;
                e.stopPropagation();
                if (lastHovered && lastHovered !== selectedElement) $(lastHovered).removeClass('inspector-highlight');
                lastHovered = this;
                if (this !== selectedElement) $(this).addClass('inspector-highlight');
            })
            .on('mouseout.inspector', '*', function (e) {
                if (isKadenceWrapperSelfTarget(this)) return;
                if ($(this).closest(IGNORE).length) return;
                e.stopPropagation();
                if (this !== selectedElement) $(this).removeClass('inspector-highlight');
            })
            .on('click.inspector', '*', function (e) {
                if (isKadenceWrapperSelfTarget(this)) return;
                if ($(this).closest(IGNORE).length) return;

                e.preventDefault();
                e.stopPropagation();

                let $el = $(this);

                // ---------- ALLOW RULES ----------
                const kadenceOrOther = !isElementor;

                // 1) Always allow real <img>
                let isImage = $el.is('img');

                // 2) Treat any element with background-image as image
                const hasBgImg = hasBackgroundImage($el);
                if (!isImage && hasBgImg) isImage = true;

                // 3) Elementor: keep original restriction
                if (
                    isElementor &&
                    !$el.is('img') &&
                    !$el.parents().is('[data-element_type="widget"]') &&
                    !($el.is('[data-element_type="container"]') && hasBgImg)
                ) {
                    return;
                }

                // 4) Gutenberg/Kadence: allow only true text blocks (with smart bubbling)
                let isAllowedText = false;
                if (kadenceOrOther && !isImage) {
                    if (!isGutenbergTextElement($el)) {
                        const $candidate = findAllowedGutenbergText($el);
                        if ($candidate.length) {
                            $el = $candidate; // bubble to the allowed title/text element
                        }
                    }
                    isAllowedText = isGutenbergTextElement($el);
                }

                // 5) If not Elementor, and not image/bg-image, and not allowed text => ignore
                if (kadenceOrOther && !isImage && !isAllowedText) {
                    return;
                }
                // ---------- END ALLOW RULES ----------

                if (selectedElement) $(selectedElement).removeClass('inspector-highlight');
                selectedElement = $el[0];
                $(selectedElement).addClass('inspector-highlight');

                // Determine content
                let content;
                if (isImage) {
                    content = hasBgImg ? extractBackgroundImageUrl($(this)) : $el.attr('src');
                } else {
                    content = getOwnText($el);
                    if (!content) return; // nothing useful to send
                }

                // Panel
                $('#inspector-info .inspector-body').html(`
        <b class="inspector-selected-element-type">Selected ${isImage ? 'Image' : 'Text'}</b><br>
        <div class="inspector-selected-element-content">
          ${isImage ? `<a href="${content}" target="_blank">${content}</a>` : trimTo150(content)}
        </div>

        <label class="xag-ai-additional-prompt">
          <div class="inspector-text-and-cost">
            <span>Additional Prompt</span>
            <span class="inspector-cost" title="Cost in xR / xB"><span class="inspector-cost-value">0.0000</span> xR</span>
          </div>
          <textarea id="ai-additional-prompt" class="widefat" rows="4" placeholder="Start typing your instructions..."></textarea>

          <div class="inspector-actions">
            ${isElementor ? `<a href="#" id="open-backups-modal" title="View & restore backups"><i class="xagio-icon xagio-icon-refresh"></i> Backups</a>` : ``}
            <button type="button" title="Start processing" id="send-to-ai-btn" class="button button-primary" disabled>
              <svg width="14" height="14" viewBox="0 0 9 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0.396447 7.89645C0.201184 8.09171 0.201184 8.40829 0.396447 8.60355C0.591709 8.79882 0.908291 8.79882 1.10355 8.60355L0.396447 7.89645ZM8.75 0.75C8.75 0.473858 8.52614 0.25 8.25 0.25L3.75 0.25C3.47386 0.25 3.25 0.473858 3.25 0.75C3.25 1.02614 3.47386 1.25 3.75 1.25L7.75 1.25L7.75 5.25C7.75 5.52614 7.97386 5.75 8.25 5.75C8.52614 5.75 8.75 5.52614 8.75 5.25L8.75 0.75ZM0.75 8.25L1.10355 8.60355L8.60355 1.10355L8.25 0.75L7.89645 0.396447L0.396447 7.89645L0.75 8.25Z" fill="white"/>
              </svg>
            </button>
          </div>
        </label>
        <span id="ai-status"></span>
      `);

                if (isImage) {
                    $('.inspector-cost .inspector-cost-value').html(prices.IMAGE_GEN[0].price.toFixed(2));
                } else {
                    $('.inspector-cost .inspector-cost-value').html(prices.TEXT_CONTENT[0].price.toFixed(3));
                }
                $('#inspector-info').show();

                const processAction = isImage ? 'xagio_ai_process_image' : 'xagio_ai_process_text';


                // Extract gutenberg data id and subTarget
                let gutenbergDataId = null;
                let subTarget = null;

                if (pageType === 'gutenberg') {
                    try { gutenbergDataId = getGutenbergDataId($el); } catch (e) { gutenbergDataId = null; }

                    // NEW: detect a precise sub-target class (own class first, then nearest ancestor)
                    const ownClasses = ($el.attr('class') || '').split(/\s+/);
                    const ownHit = SUBTARGET_CLASS_CANDIDATES.find(c => ownClasses.includes(c));
                    if (ownHit) {
                        subTarget = ownHit;
                    } else {
                        for (const c of SUBTARGET_CLASS_CANDIDATES) {
                            if ($el.closest('.' + c).length) { subTarget = c; break; }
                        }
                    }
                }

                if (isImage) {
                    $.post(xagio_data.wp_post, {
                        action   : 'xagio_ai_get_attachment_id',
                        image_url: content
                    }, function (res) {
                        if (res.status === 'success') {
                            checkStatusAndBindButton({
                                type: 'image',
                                id: res.data.id,
                                action: processAction,
                                dataIdGutenberg: gutenbergDataId,
                                subTarget: subTarget
                            });
                        } else {
                            $('#ai-status').text(`❌ ${res.message}`);
                        }
                    });
                } else {

                    checkStatusAndBindButton({
                        type: 'text',
                        text: content,
                        action: processAction,
                        dataIdGutenberg: gutenbergDataId,
                        subTarget: subTarget // <--- NEW
                    });
                }

                function checkStatusAndBindButton({ type, id = null, text = null, action, dataIdGutenberg = null, subTarget = null }) {
                    let elementorDataId = null;

                    // Only Elementor text requires data-id
                    if (type === 'text') {
                        if (pageType === 'elementor') {
                            elementorDataId = $(selectedElement).closest('[data-id]').attr('data-id') || null;
                            if (!elementorDataId) {
                                $('#ai-status').text('⚠️ Cannot send: missing data-id on Elementor text element');
                                return;
                            }
                        } else if (pageType === 'gutenberg') {
                            if (!dataIdGutenberg) {
                                $('#ai-status').text('⚠️ Cannot send: missing block identifier on Gutenberg text element');
                                return;
                            }
                        }
                    }

                    const statusAction = (type === 'image') ? 'xagio_ai_check_status_image' : 'xagio_ai_check_status_text';
                    const statusPayload = (type === 'image')
                        ? { attachment_id: id, page_type: pageType, post_id: xagio_post_id.value }
                        : {
                            post_id  : xagio_post_id.value,
                            content  : text,
                            page_type: pageType,
                            data_id  : (pageType === 'elementor') ? elementorDataId : dataIdGutenberg,
                            ...(pageType === 'gutenberg' && subTarget ? { sub_target: subTarget } : {})
                        };


                    $.post(xagio_data.wp_post, { action: statusAction, ...statusPayload }, function (response) {
                        const btn = $('#send-to-ai-btn');
                        if (response.data === 'queued' || response.data === 'running') {
                            btn.prop('disabled', true);
                            $('#ai-status').text('');
                        } else {
                            btn.prop('disabled', false);
                            $('#ai-status').text('');
                        }

                        $(document).off('click', '#send-to-ai-btn').on('click', '#send-to-ai-btn', function () {
                            // Balance check
                            let total_xags = parseFloat($('.xrenew .value').text()) + parseFloat($('.xbanks .value').text());
                            let total_cost = parseFloat($('.inspector-cost-value').text());
                            const $panel    = $(this).closest('#inspector-info');
                            const $promptEl = $panel.find('#ai-additional-prompt');
                            const additionalPrompt = ($promptEl.val() || '').trim();


                            if (total_cost > total_xags) {
                                alert('You do not have enough XAGs to process this action!');
                                return;
                            }

                            btn.prop('disabled', true);

                            const processPayload = (type === 'image')
                                ? {
                                    attachment_id: id,
                                    page_type: pageType,
                                    post_id: xagio_post_id.value,
                                    data_id  : (pageType === 'gutenberg') ? dataIdGutenberg : null,
                                    ...(pageType === 'gutenberg' && subTarget ? { sub_target: subTarget } : {})
                                }
                                : {
                                    content  : text,
                                    post_id  : xagio_post_id.value,
                                    page_type: pageType,
                                    data_id  : (pageType === 'elementor') ? elementorDataId : dataIdGutenberg,
                                    ...(pageType === 'gutenberg' && subTarget ? { sub_target: subTarget } : {})
                                };

                            if (additionalPrompt) processPayload.additional_prompt = additionalPrompt;

                            // Image action type
                            if (type === 'image') processPayload.action_type = 'generate';

                            // Include nonce if you expose it globally
                            if (typeof window._xagio_nonce !== 'undefined') {
                                processPayload._xagio_nonce = window._xagio_nonce;
                            }

                            // Visual retarget + loading UI
                            const retarget_id = 'rt_' + Date.now().toString(36) + '_' + Math.random().toString(36).substr(2, 8);
                            $(selectedElement).addClass(retarget_id);

                            if ($(selectedElement).is('img')) $(selectedElement).wrap(`<span class="xag_ai_loading" style="${(pageType === 'gutenberg' ? 'position: initial;' : '')}"></span>`);
                            else $(selectedElement).addClass('xag_ai_loading');

                            $.post(xagio_data.wp_post, { action: action, ...processPayload }, function (res) {
                                if (res.status === 'success') {
                                    queue.push({ id: res.data, retarget_id });
                                } else {
                                    $('#ai-status').text('❌ Failed to send');
                                    btn.prop('disabled', false);
                                }
                            });
                        });
                    });
                }
            });
    }

    function disableInspector() {
        $('body').off('.inspector');
        if (lastHovered) $(lastHovered).removeClass('inspector-highlight');
        if (selectedElement) $(selectedElement).removeClass('inspector-highlight');
        $('#inspector-info').hide();
        lastHovered = null;
        selectedElement = null;
    }

    function refreshXags() {
        $.post(xagio_data.wp_post, 'action=xagio_refreshXags', function (d) {
            if (d.status == 'success') {

                $('.xrenew').find('.value').html(parseFloat(d.data.xags_allowance).toFixed(2));

                if (d.data['xags'] > 0) {
                    $('.xbanks').find('.value').html(parseFloat(d.data.xags).toFixed(2));
                }

            }
        });
    }

    function getCosts() {
        $.post(xagio_data.wp_post, `action=xagio_ai_get_average_prices`, function (d) {

            prices = d.data.average_prices;

        });
    }

    // --- Page type detection (Elementor, Kadence/Gutenberg, Default)
    function getPageType() {
        const b = document.body.classList;
        if (b.contains('elementor-page') || b.contains('elementor-editor-active')) return 'elementor';
        if (b.contains('wp-theme-kadence')) return 'gutenberg';
        return 'default';
    }

// --- Shared helpers
    function hasBackgroundImage($element) {
        const bg = $element.css('background-image');
        return bg && bg !== 'none' && /^url\(["']?.+["']?\)$/.test(bg);
    }

    // === NEW: classes we may want to target precisely inside a block ===
    const SUBTARGET_CLASS_CANDIDATES = [
        'kt-blocks-info-box-title',
        'kt-blocks-info-box-text',
        'kt-btn-inner-text',
        'kb-btn-inner-text',
        'kt-svg-icon-list-text',
        'kt-blocks-accordion-title',
        'kt-blocks-accordion-title-wrap',
        'kt-blocks-accordion-header',
        'kt-accordion-header-wrap',
        'kt-testimonial-content',
        'kt-testimonial-name',
        'kt-testimonial-occupation',
        'kb-img',
        'kt-testimonial-image'
    ];

    function extractBackgroundImageUrl($element) {
        const bg = $element.css('background-image');
        return bg.replace(/^url\(["']?(.+?)["']?\)$/, '$1');
    }

    function trimTo150(str) {
        if (!str) return '';
        return str.length <= 150 ? str : (str.slice(0, 150) + '...');
    }

    // Extract a stable identifier for Gutenberg/Kadence blocks
    function getGutenbergDataId($el) {
        // Prefer explicit Kadence marker anywhere up the tree
        const ownKb = $el.attr('data-kb-block');
        if (ownKb) return ownKb;

        const kbClosest = $el.closest('[data-kb-block]');
        if (kbClosest.length) return kbClosest.attr('data-kb-block');

        // Regex for Kadence "uniqueID"-style class tokens baked into classnames
        // Examples: kt-adv-heading10_f2613a-73, kb-btn10_d247ac-a2, kt-pane10_807420-63
        const KADENCE_CLASS_RE = /(?:^|\s)((?:kb|kt|wp-block-kadence)-[a-z0-9-]*_[0-9a-f-]{6,})(?=\s|$)/i;

        function extractKadenceClass($node) {
            const cls = ($node.attr('class') || '');
            const m = cls.match(KADENCE_CLASS_RE);
            return m ? m[1] : null;
        }

        // 1) Try on the element itself
        let best = extractKadenceClass($el);
        if (best) return best;

        // 2) Special cases: bubble to common Kadence parents for inner spans/buttons/titles
        //    - Kadence Single Button wrappers
        const btnParent = $el.closest('.wp-block-kadence-singlebtn, a.kb-button, button.kb-button, .wp-block-kadence-advancedbtn');
        if (btnParent.length) {
            best = extractKadenceClass(btnParent);
            if (best) return best;
        }

        //    - Kadence Accordion: go up to the pane (has kt-pane*_*) or header/button
        const accParent = $el.closest('.kt-accordion-pane, .kt-accordion-header-wrap, .kt-blocks-accordion-header');
        if (accParent.length) {
            best = extractKadenceClass(accParent);
            if (best) return best;
        }

        //    - Kadence InfoBox wrapper
        const infoBoxParent = $el.closest('.wp-block-kadence-infobox, .kt-blocks-info-box');
        if (infoBoxParent.length) {
            best = extractKadenceClass(infoBoxParent);
            if (best) return best;
        }

        //    - Advanced Heading wrapper
        const advHeadingParent = $el.closest('.wp-block-kadence-advancedheading');
        if (advHeadingParent.length) {
            best = extractKadenceClass(advHeadingParent);
            if (best) return best;
        }

        // 3) Generic climb: walk up a reasonable number of ancestors and grab the first Kadence token
        const MAX_DEPTH = 20;
        const $ancestors = $el.parents().slice(0, MAX_DEPTH);
        for (let i = 0; i < $ancestors.length; i++) {
            const $p = $($ancestors[i]);
            best = extractKadenceClass($p);
            if (best) return best;
        }

        // 4) Core Gutenberg anchor/id as a fallback
        const anchorId = $el.attr('id') || $el.closest('[id]').attr('id');
        if (anchorId) return `#${anchorId}`;

        // 5) Last-resort fallback: DOM path + short hash of own visible text
        const ownText = ($el.clone().children().remove().end().text() || '').trim();
        const shortHash = ownText ? btoa(unescape(encodeURIComponent(ownText))).slice(0, 12) : 'no-text';
        const path = getDomPath($el[0]);
        return `path:${path}|h:${shortHash}`;
    }


// Build a deterministic DOM path (tag:nth-child chains). Used only as a fallback.
    function getDomPath(node) {
        const parts = [];
        while (node && node.nodeType === 1 && node !== document.body) {
            const tag = node.tagName.toLowerCase();
            const ix = Array.prototype.indexOf.call(node.parentNode ? node.parentNode.children : [], node) + 1;
            parts.unshift(`${tag}:nth-child(${ix})`);
            node = node.parentElement;
        }
        return parts.join('>');
    }

    //
    // 4) Toggle & panel wiring (moved into #wpadminbar)
    //
    $(document).ready(function () {
        const SELECTORS = {
            elementor: ['elementor-page'],
            kadence:   ['wp-theme-kadence']
        };
        const hasAny = (list) => list.some(cls => document.body.classList.contains(cls));

        const pageType = hasAny(SELECTORS.elementor) ? 'Elementor' : hasAny(SELECTORS.kadence) ? 'Kadence' : 'Default';

        if (pageType === 'Default') return;

        refreshXags();
        getCosts();

        const logoUrl = xagio_data.plugins_url + 'assets/img/logo-xagio-smaller.webp';

        // Admin bar item
        const $toggleLink = $(
            '<li id="wp-admin-bar-xagio-inspector" class="menupop">' +
            `<a href="#" id="inspector-toggle" class="ab-item"><img src="${logoUrl}" alt="Xagio Logo" class="inspector-logo" /> Enable Xagio AI Assistant</a>` +
            '</li>'
        );
        $('#wp-admin-bar-root-default').append($toggleLink);

        // Panel + Backups button
        const $panel = $(`
    <div id="inspector-info">
      <div class="inspector-header">
        <span class="inspector-logo-container">
            <img src="${logoUrl}" alt="Xagio Logo" class="inspector-logo" />
            <span class="inspector-title">Xagio AI Assistant</span>
        </span>
        <span id="inspector-xags">
            <div class="xags-container">
                <div class="xags-item xrenew" id="xags-allowance" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="These are your current XAGS (xRenew)">
                    <img src="${xagio_data.plugins_url}assets/img/logos/xRenew.png" alt="xR" class="xags-icon">
                    <span class="value">0</span>
                </div>
                <div class="xags-item xbanks" id="xags" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="These are your current XAGS (xBank)">
                    <img src="${xagio_data.plugins_url}assets/img/logos/xBanks.png" alt="xB" class="xags-icon">
                    <span class="value">0</span>
                </div>
            </div>
        </span>       
      </div>
      <div class="inspector-body"></div>      
    </div>`);

        $('body').append($panel);

        // Toggle (unchanged)
        $('#inspector-toggle').on('click', function (e) {
            e.preventDefault();
            inspectorEnabled = !inspectorEnabled;
            $(this).html(
                inspectorEnabled
                    ? `<img src="${logoUrl}" alt="Xagio Logo" class="inspector-logo" /> Disable Xagio AI Assistant`
                    : `<img src="${logoUrl}" alt="Xagio Logo" class="inspector-logo" /> Enable Xagio AI Assistant`
            );
            inspectorEnabled ? enableInspector() : disableInspector();
        });

        // ===== Backups modal wiring =====
        $(document).on('click', '#open-backups-modal', function(e){
            e.preventDefault();
            openBackupsModal();
        });

        function openBackupsModal(){
            const $modal = $(`
<div id="xagio-backups-modal" role="dialog" aria-modal="true" aria-label="Elementor Backups">
  <div class="modal-inner">
    <div class="modal-head">
      <strong>Elementor Backups &nbsp; <a href="#" id="delete-all-backups">Delete All</a></strong>
      <button class="close" aria-label="Close">×</button>      
    </div>
    <div class="modal-body"><div class="loading">Loading…</div></div>
  </div>
</div>`);
            $('body').append($modal);
            $modal.fadeIn(120);
            bindModalEvents($modal);
            fetchBackups($modal);
        }

        function bindModalEvents($modal){
            $modal.on('click', '.close', () => closeBackupsModal($modal));
            $modal.on('click', e => { if (e.target.id === 'xagio-backups-modal') closeBackupsModal($modal); });
            $(document).on('keydown.xagioModal', e => { if (e.key === 'Escape') closeBackupsModal($modal); });

            // Restore action
            $modal.on('click', '.restore-backup', function(){
                const index = Number($(this).data('index'));
                if (!Number.isInteger(index)) return;
                if (!confirm('Restore this backup? This will overwrite current Elementor content.')) return;

                $.post(xagio_data.wp_post, {
                    action : 'xagio_restore_elementor_backup',
                    post_id: xagio_post_id.value,
                    index  : index
                }, function(res){
                    if (res.success === true) {
                        // Reload so Elementor/page picks up the restored JSON
                        location.reload();
                    } else {
                        alert(res.message || 'Restore failed.');
                    }
                });
            });

            // Delete action
            $modal.on('click', '.delete-backup', function(){
                const index = Number($(this).data('index'));
                if (!Number.isInteger(index)) return;
                if (!confirm('Delete this backup permanently?')) return;

                const $li = $(this).closest('li');

                $.post(xagio_data.wp_post, {
                    action : 'xagio_delete_elementor_backup',
                    post_id: xagio_post_id.value,
                    index  : index
                }, function(res){
                    if (res.success === true) {
                        $li.remove();
                        const $list = $modal.find('.modal-body .list');
                        if (!$list.find('li').length) {
                            $modal.find('.modal-body').html('<div class="empty">No backups yet.</div>');
                        }
                    } else {
                        alert(res.message || 'Delete failed.');
                    }
                });
            });

            // Delete all backups
            $modal.on('click', '#delete-all-backups', function(e){
                e.preventDefault();
                if (!confirm('Delete ALL backups for this page? This cannot be undone.')) return;
                $.post(xagio_data.wp_post, {
                    action : 'xagio_delete_all_elementor_backups',
                    post_id: xagio_post_id.value
                }, function(res){
                    if (res.success === true) {
                        $modal.find('.modal-body').html('<div class="empty">No backups yet.</div>');
                    } else {
                        alert(res.message || 'Delete-all failed.');
                    }
                });
            });

        }

        function closeBackupsModal($modal){
            $(document).off('keydown.xagioModal');
            $modal.fadeOut(120, () => $modal.remove());
        }

        function fetchBackups($modal){
            $.post(xagio_data.wp_post, {
                action : 'xagio_get_elementor_backups',
                post_id: xagio_post_id.value
            }, function(res){
                const $body = $modal.find('.modal-body');
                if (res.success !== true) {
                    $body.html('<div class="empty">Could not load backups.</div>');
                    return;
                }
                // Accept either a raw array or {backups: []}
                const list = Array.isArray(res.data) ? res.data : (res.data && res.data.backups) ? res.data.backups : [];
                if (!list.length) {
                    $body.html('<div class="empty">No backups yet.</div>');
                    return;
                }

                const items = list.map((b, i) => {
                    const sizeKB = b && b.data ? Math.ceil(b.data.length / 1024) : 0;
                    const by     = (b && (b.by ?? '')).toString();
                    const date   = (b && b.date) ? b.date : '';
                    const type   = (b && b.type) ? b.type : '';

                    return `
<li>
  <div class="meta">
    <div><strong>${escapeHtml(date)}</strong></div>
    <div class="sub">type: ${escapeHtml(type)} · user: ${escapeHtml(by)} · ~${sizeKB} KB</div>
  </div>
  <div class="actions">
    <button class="button button-primary restore-backup" data-index="${i}" type="button">Restore</button>
     <button class="button delete-backup" data-index="${i}" type="button">Delete</button>
  </div>
</li>`;
                }).reverse().join(''); // newest first visually

                $body.html(`<ul class="list">${items}</ul><div class="note" style="margin-top:8px;opacity:.8">Restoring will overwrite current <code>_elementor_data</code>.</div>`);
            });
        }

        function escapeHtml(s){
            return String(s ?? '').replace(/[&<>"']/g, (m)=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[m]));
        }
    });


})(jQuery);
