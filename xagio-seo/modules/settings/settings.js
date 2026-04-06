var plugins, themes, ajax_timeout, timeout, elementorTemplateZip, matcher;
let elementorVersion = 'free';

(function ($) {

    function importKit(session) {
        var payload = {
            id                     : null,
            session                : session,
            include                : [
                'content',
                'settings'
            ],
            overrideConditions     : [],
            selectedCustomPostTypes: []
        };

        if (elementorVersion == 'pro') {
            payload.include.push('templates');
        }

        return $.ajax({
                          url : ajaxurl,
                          type: 'POST',
                          data: {
                              action: 'elementor_import_kit',
                              data  : JSON.stringify(payload),
                              _nonce: nonce
                          }
                      });
    }

    // 3. Import Kit Runner: runs a specific import step based on the runner type.
    function runImportKitRunner(session, runner) {
        var payload = {
            session: session,
            runner : runner
        };

        return $.ajax({
                          url : ajaxurl,
                          type: 'POST',
                          data: {
                              action: 'elementor_import_kit__runner',
                              data  : JSON.stringify(payload),
                              _nonce: nonce
                          }
                      });
    }

    var nonce = xagio_data.elementor_nonce;
    var ajaxurl = xagio_data.wp_get; // If not provided, you might also fall back to a global ajaxurl.

    // 1. Upload Kit: sends the file and nonce to the server.
    function uploadKit(e_import_file, kit_id) {
        var formData = new FormData();
        formData.append('action', 'elementor_upload_kit');
        formData.append('e_import_file', e_import_file);
        formData.append('kit_id', kit_id); // You can pass undefined or a valid kit ID if needed.
        formData.append('_nonce', nonce);

        return $.ajax({
                          url        : ajaxurl,
                          type       : 'POST',
                          data       : formData,
                          processData: false,
                          contentType: false
                      });
    }

    function checkAndInstallElementor() {
        $('#elementor-output').append('<p class="checking-elementor">Checking Elementor <i class="xagio-icon xagio-icon-refresh xagio-icon-spin"></i></p>');

        return $.ajax({
                          url     : xagio_data.wp_post,
                          type    : 'POST',
                          dataType: 'json',
                          data    : {
                              action: 'xagio_ocw_install_elementor'
                          }
                      }).then(function (response) {
            if (response.status === 'success') {
                $('#elementor-output').find('.checking-elementor').html(response.message);
                elementorVersion = response.data.version;

                // Revert kit - ensure this doesn't fail the whole chain
                return $.ajax({
                                  url     : xagio_data.wp_post,
                                  type    : 'POST',
                                  dataType: 'json',
                                  data    : {
                                      action  : 'elementor_revert_kit',
                                      _xagio_nonce: xagio_data._wpnonce
                                  }
                              });

            } else {
                return $.Deferred().reject(response.data.error || 'Unknown error installing Elementor.');
            }
        });
    }

    function startImportProcess() {
        var file = elementorTemplateZip;
        $('#elementor-output').append('<p><i class="xagio-icon xagio-icon-history"></i> Uploading kit file...</p>');

        uploadKit(file, undefined)
            .then(function (uploadResponse) {
                var session = uploadResponse.data.session;
                $('#elementor-output').append('<p><i class="xagio-icon xagio-icon-check"></i> Upload completed.</p>');
                $('#elementor-output').append('<p><i class="xagio-icon xagio-icon-history"></i> Starting kit import...</p>');
                return importKit(session).then(function () {
                    return session;
                });
            })
            .then(function (session) {
                var runners = [
                    "site-settings",
                    "plugins",
                    "templates",
                    "taxonomies",
                    "elementor-content",
                    "wp-content",
                    "elements-default-values",
                    "custom-fonts",
                    "custom-icons",
                    "custom-code",
                ];

                var chain = $.Deferred().resolve().promise();
                $.each(runners, function (index, runner) {
                    chain = chain.then(function () {
                        $('#elementor-output').append('<p><i class="xagio-icon xagio-icon-history"></i> Importing: ' +
                                                      runner + '...</p>');
                        return runImportKitRunner(session, runner)
                            .then(function () {
                                $('#elementor-output').append('<p><i class="xagio-icon xagio-icon-check"></i> Import of "' +
                                                              runner + '" completed.</p>');
                            });
                    });
                });
                return chain;
            })
            .done(function () {
                $('#elementor-output').append('<p><i class="xagio-icon xagio-icon-check"></i>  Elementor kit import process complete.</p>');
                $('.ocw-step-elementor').fadeOut(function () {
                    $('.ocw-step-1').fadeIn();
                });
            })
            .fail(function (error) {
                $('#elementor-output').append('<p style="color:red;">Error: ' + JSON.stringify(error) + '</p>');
            });
    }

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
        checkAndInstallKadence                 : function () {
            $('#elementor-output').append('<p class="checking-elementor">Checking Requirements <i class="xagio-icon xagio-icon-refresh xagio-icon-spin"></i></p>');

            return $.ajax({
                url     : xagio_data.wp_post,
                type    : 'POST',
                dataType: 'json',
                data    : {
                    action: 'xagio_ocw_install_kadence'
                }
            }).then(function (response) {
                if (response.status === 'success') {
                    $('#elementor-output').find('.checking-elementor').html(response.message);
                    // kadenceVersion = response.data.version;

                    // Revert kit - ensure this doesn't fail the whole chain
                    return $.ajax({
                        url: xagio_data.wp_post,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'xagio_kadence_import',
                            // _wpnonce: xagio_data._wpnonce,
                            // If you want to import from a remote ZIP you prepared, pass it here:
                            // import_url: 'https://cdn.example.com/kadence-site-bundle.zip'
                        }
                    });

                } else {
                    return $.Deferred().reject(response.data.error || 'Unknown error installing Elementor.');
                }
            });
        },
        loadTemplates: function () {

            $(document).on('click', '.select-template', function (e) {
                e.preventDefault();
                $('.box-template').removeClass('selected');
                let btn = $(this);
                let box = btn.parents('.box-template');
                box.toggleClass('selected');
            });

            $(document).on('click', '.close-theme-select .xagio-icon', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).closest('.box-template').removeClass('selected');
            });

            $(document).on('click', '.select-theme-editor', function (e) {
                e.preventDefault();

                const $btn   = $(this);
                const plat   = $btn.data('platform');               // 'elementor' | 'gutenberg'
                const $card  = $btn.closest('.box-template');

                // 1) Persist selection on the card
                $card.attr('data-platform', plat);

                // 3) Toggle checked icon on buttons
                const $buttons = $card.find('.theme-picker-buttons .select-theme-editor');

                $buttons.each(function () {
                    const $b = $(this);
                    $b.find('.xagio-icon.xagio-icon-check').remove();
                    $b.removeClass('active');
                });

                if ($btn.find('.xagio-icon.xagio-icon-check').length === 0) {
                    $btn.prepend('<i class="xagio-icon xagio-icon-check"></i> ');
                }
                $btn.addClass('active');

                let template_key = $card.data('key');

                let warningText = (plat === 'gutenberg')
                    ? 'Are you sure you want to install this template? It will overwrite your existing active Gutenberg content!'
                    : 'Are you sure you want to install this template? It will overwrite your existing active Elementor Template!';

                xagioModal('Template Install', warningText, function (result) {

                    if (result) {

                        $('.search-templates').hide();
                        $('#templates').hide();
                        $('#pagination').hide();

                        // Elementor prerequisite only if installing elementor version
                        if (plat === 'elementor') {

                            checkAndInstallElementor().always(function () {

                                $.post(xagio_data.wp_post, `action=xagio_ocw_step&step=keyword_research&templates=1&remove_pages=0&editor_type=${plat}&template_key=${template_key}`);

                                $.post(xagio_data.wp_post, `action=xagio_ocw_get_template&template_key=${template_key}&template_platform=${plat}`, function (d) {
                                    if (d.status === 'success' && d.data) {
                                        fetch(d.data)
                                            .then(response => response.blob())
                                            .then(blob => {
                                                elementorTemplateZip = new File([blob], `${template_key}.zip`, {type: 'application/zip'});
                                                startImportProcess();
                                            })
                                            .catch(error => console.error("Error fetching template:", error));
                                    } else {
                                        console.error("Error retrieving template:", d.message);
                                    }
                                });

                            });

                        } else {

                            let template = $card;
                            let template_key = template.data('key');
                            let template_claimed = (String(template.attr('data-claimed')) === '1'); // IMPORTANT: use attr (data() caches)
                            let template_id = parseInt(template.attr('data-id-elementor') || template.find('.template-action-button').data('id'), 10);
                            let template_platform = 'gutenberg';

                            // Gutenberg
                            actions.checkAndInstallKadence().always(function () {
                                (function injectProgressCssOnce(){
                                    if (document.getElementById('xagio-progress-css')) return;
                                    const style = document.createElement('style');
                                    style.id = 'xagio-progress-css';
                                    document.head.appendChild(style);
                                })();

                                const out = $('#elementor-output');

                                function uiInit() {
                                    out.append(`
      <div class="xagio-stage">Preparing Kadence import…</div>
      <div class="xagio-progress-wrap"><div class="xagio-progress-bar" style="background:#3b82f6;"></div></div>
      <div class="xagio-logs"></div>
    `);
                                }
                                function setStage(text) { out.find('.xagio-stage').text(text); }
                                function setProgress(pct) { out.find('.xagio-progress-bar').css('width', Math.max(0, Math.min(100, pct)) + '%'); }
                                function logLine(html) { out.find('.xagio-logs').append(`<div>${html}</div>`).scrollTop(999999); }
                                function logInfo(msg){ logLine(`<span class="xagio-muted"><i class="xagio-icon xagio-icon-info"></i></span> ${escapeHtml(msg)}`); }
                                function logOk(msg){ logLine(`<span class="xagio-ok"><i class="xagio-icon xagio-icon-check"></i></span> ${escapeHtml(msg)}`); }
                                function logWarn(msg){ logLine(`<span class="xagio-warn"><i class="xagio-icon xagio-icon-warning"></i></span> ${escapeHtml(msg)}`); }
                                function logErr(msg){ logLine(`<span class="xagio-err"><i class="xagio-icon xagio-icon-close"></i></span> ${escapeHtml(msg)}`); }

                                function escapeHtml(s){
                                    if (s == null) return '';
                                    return String(s).replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
                                }

                                $.post(xagio_data.wp_post, `action=xagio_ocw_step&step=keyword_research&templates=1&remove_pages=0&editor_type=${template_platform}&template_key=${template_key}`);


                                // Helper: stringify ajax error
                                function errorToText(err) {
                                    try {
                                        if (err && err.responseJSON && (err.responseJSON.message || err.responseJSON.data || err.responseJSON.error)) {
                                            return err.responseJSON.message || err.responseJSON.data || err.responseJSON.error;
                                        }
                                        if (err && err.responseText) {
                                            const j = JSON.parse(err.responseText);
                                            if (j && (j.message || j.data || j.error)) return j.message || j.data || j.error;
                                        }
                                    } catch (e) {}
                                    return typeof err === 'string' ? err : (err && err.message) || 'Unknown error';
                                }

                                // Import via browser upload (FormData)
                                function importViaUpload(file, onUploadProgress) {
                                    const fd = new FormData();
                                    fd.append('action', 'xagio_kadence_import');
                                    fd.append('_xagio_nonce', xagio_data._wpnonce);
                                    fd.append('xagio_template_file', file);

                                    return $.ajax({
                                        url: xagio_data.wp_post,
                                        type: 'POST',
                                        data: fd,
                                        processData: false,
                                        contentType: false,
                                        dataType: 'json',
                                        xhr: function () {
                                            const xhr = $.ajaxSettings.xhr();
                                            if (xhr.upload && typeof onUploadProgress === 'function') {
                                                xhr.upload.addEventListener('progress', function (e) {
                                                    if (e.lengthComputable) {
                                                        onUploadProgress(e.loaded, e.total);
                                                    } else {
                                                        onUploadProgress(null, null);
                                                    }
                                                });
                                            }
                                            return xhr;
                                        }
                                    });
                                }

                                // Fetch template URL (claimed or claim+get)
                                function getTemplateZipUrl() {
                                    return new Promise(function(resolve, reject){
                                        if (template_claimed) {
                                            $.post(xagio_data.wp_post, `action=xagio_ocw_get_template&template_key=${template_key}&template_platform=${template_platform}`, function (d) {
                                                if (d.status === 'success' && d.data) resolve(d.data);
                                                else reject(d.message || 'Could not retrieve Kadence template URL.');
                                            }).fail(function(err){
                                                reject(errorToText(err));
                                            });
                                        } else {
                                            $.post(xagio_data.wp_post, `action=xagio_ocw_claim_template&template_id=${template_id}`, function (d) {
                                                if (d.status === 'success') {
                                                    template.attr('data-claimed', 1);
                                                    template.data('claimed', true);
                                                    $.post(xagio_data.wp_post, `action=xagio_ocw_get_template&template_key=${template_key}&template_platform=${template_platform}`, function (d2) {
                                                        if (d2.status === 'success' && d2.data) resolve(d2.data);
                                                        else reject(d2.message || 'Could not retrieve Kadence template URL after claim.');
                                                    }).fail(function(err){
                                                        reject(errorToText(err));
                                                    });
                                                } else {
                                                    reject(d.message || 'Template claim failed.');
                                                }
                                            }).fail(function(err){
                                                reject(errorToText(err));
                                            });
                                        }
                                    });
                                }

                                // Fetch with progress (streams). Calls onProgress(bytesRead, totalBytes|null)
                                async function fetchZipWithProgress(url, onProgress) {
                                    const t0 = performance.now();
                                    const resp = await fetch(url, { credentials: 'omit' });
                                    const status = resp.status;
                                    if (!resp.ok) throw new Error('Failed to fetch template ZIP in browser.');

                                    const contentLength = resp.headers.get('content-length');
                                    const total = contentLength ? parseInt(contentLength, 10) : null;

                                    if (!resp.body || !resp.body.getReader) {
                                        // No streaming available; fallback to blob()
                                        const blob = await resp.blob();
                                        onProgress && onProgress(blob.size, blob.size);
                                        logInfo(`Downloaded ${formatBytes(blob.size)} in ${formatMs(performance.now()-t0)}.`);
                                        return blob;
                                    }

                                    const reader = resp.body.getReader();
                                    const chunks = [];
                                    let received = 0;

                                    while (true) {
                                        const { done, value } = await reader.read();
                                        if (done) break;
                                        chunks.push(value);
                                        received += value.byteLength;
                                        onProgress && onProgress(received, total);
                                    }

                                    const blob = new Blob(chunks, { type: 'application/zip' });
                                    onProgress && onProgress(received, total ?? received);
                                    logInfo(`Downloaded ${formatBytes(received)}${total?` / ${formatBytes(total)}`:''} in ${formatMs(performance.now()-t0)}.`);
                                    return blob;
                                }

                                function formatBytes(n){
                                    if (!n && n !== 0) return '';
                                    const u = ['B','KB','MB','GB','TB'];
                                    let i = 0, v = n;
                                    while (v >= 1024 && i < u.length-1) { v /= 1024; i++; }
                                    return `${v.toFixed(v < 10 && i > 0 ? 1 : 0)} ${u[i]}`;
                                }
                                function formatMs(ms){
                                    if (ms < 1000) return `${Math.round(ms)} ms`;
                                    const s = ms/1000;
                                    return `${s.toFixed(s < 10 ? 1 : 0)} s`;
                                }

                                // ----- Run flow with progress UI -----
                                uiInit();
                                const tStart = performance.now();

                                getTemplateZipUrl()
                                    .then(async function(zipUrl){
                                        setStage('Downloading template (browser)…');
                                        setProgress(2);
                                        logInfo('Starting browser download…');

                                        // Download progress maps to 0–50%
                                        const blob = await fetchZipWithProgress(zipUrl, (loaded, total) => {
                                            if (loaded != null) {
                                                if (total) {
                                                    const pct = Math.max(0, Math.min(1, loaded/total));
                                                    setProgress(Math.round(pct * 50));
                                                } else {
                                                    // Indeterminate: gently increase until 45%
                                                    const cur = parseInt(out.find('.xagio-progress-bar').css('width')) || 5;
                                                    setProgress(Math.min(45, cur + 1));
                                                }
                                            }
                                        });

                                        const file = new File([blob], `${template_key}.zip`, { type: 'application/zip' });
                                        logOk(`Download complete (${formatBytes(blob.size)}).`);

                                        setStage('Uploading template to server…');
                                        logInfo('Starting upload…');

                                        // Upload progress maps to 50–90%
                                        const res = await importViaUpload(file, (sent, total) => {
                                            if (sent == null || total == null) {
                                                // unknown length; nudge forward
                                                const cur = parseFloat(out.find('.xagio-progress-bar')[0].style.width) || 50;
                                                setProgress(Math.min(85, cur + 0.5));
                                            } else {
                                                const pct = Math.max(0, Math.min(1, sent/total));
                                                setProgress(50 + Math.round(pct * 40)); // 50->90
                                            }
                                        });

                                        // Server processing 90->100
                                        setStage('Processing import on server…');
                                        setProgress(95);
                                        logInfo('Unzipping & importing content…');

                                        return res;
                                    })
                                    .then(function(res){
                                        if (res && res.status === 'success') {
                                            setProgress(100);
                                            setStage('Completed');
                                            logOk('Kadence import completed successfully.');

                                            out.html(`
                                              <div class="xagio-success-message" style="margin-top:10px;padding:12px;border-radius:8px;background:#ecfdf5;color:#065f46;">
                                                <i class="xagio-icon xagio-icon-check"></i>
                                                Template installed successfully.
                                              </div>
                                            `);

                                            if (res.data && res.data.thumbnails_notice) {
                                                logWarn(res.data.thumbnails_notice);
                                            }

                                        } else {
                                            const msg = (res && (res.message || (res.data && (res.data.message || res.data.error)))) || 'Import failed.';
                                            setStage('Error');
                                            setProgress(100);
                                            logErr(msg);
                                            out.append('<p style="color:red;margin-top:8px;">' + escapeHtml(msg) + '</p>');
                                        }
                                    })
                                    .catch(function(err){
                                        const msg = errorToText(err);
                                        setStage('Error');
                                        setProgress(100);
                                        logErr(msg);
                                        out.append('<p style="color:red;margin-top:8px;">Error: ' + escapeHtml(msg) + '</p>');
                                    })
                                    .finally(function(){
                                        const elapsed = formatMs(performance.now() - tStart);
                                        logInfo(`Total time: ${elapsed}`);
                                    });
                            });
                        }

                    }

                });
            });



            $(document).on('click', '.claim-template', function (e) {
                e.preventDefault();

                let template = $(this).parents('.xagio-column-container.box-template');
                let template_button = template.find('.template-action-button');
                let template_id = template_button.data('id'); // elementor id
                let btn = $(this);

                btn.disable();

                $.post(xagio_data.wp_post, `action=xagio_ocw_claim_template&template_id=${template_id}`, function (d) {

                    btn.disable();

                    if (d.status === 'success') {
                        template.attr('data-claimed', 1);

                        // swap Claim -> Select (open overlay on click)
                        template_button
                            .removeClass('claim-template select-template download-template btn-orange btn-blue')
                            .addClass('btn-blue select-template')
                            .attr('data-claimed', 1)
                            .html('Select');

                        xagioNotify('success', d.message);
                    } else {
                        xagioNotify('error', d.message);
                    }
                });
            });

            $.post(xagio_data.wp_post, 'action=xagio_ocw_get_templates', function (d) {

                let templates = d.data;
                let templates_holder = $('#templates');

                if (templates.length > 0) {
                    let currentPage = 1;
                    const itemsPerPage = 12;
                    // Use a filtered array that will be updated by the search
                    let filteredTemplates = templates;
                    let totalPages = Math.ceil(filteredTemplates.length / itemsPerPage);

                    // Function to render a specific page using the provided data array
                    function renderPage(page, data) {
                        templates_holder.empty();
                        let start = (page - 1) * itemsPerPage;
                        let end = start + itemsPerPage;
                        let pageTemplates = data.slice(start, end);

                        pageTemplates.forEach(function (template) {
                            let box_clone = $('.box-template.template').clone().removeClass('template');

                            const hasElementor = true;
                            const hasGutenberg = (template.has_gutenberg === true || template.has_gutenberg === 1 || template.has_gutenberg === '1');

                            // Build platform badges
                            const types = ['elementor'];
                            if (hasGutenberg) types.push('gutenberg');

                            const platformBadges = types.map(function (t) {
                                const label = t.charAt(0).toUpperCase() + t.slice(1);
                                return `<span class="template-platform ${t}">${label}</span>`;
                            }).join(' ');

                            const $previewProto = box_clone.find('.preview-template').first();

                            // decide selected platform
                            let selectedPlatform = 'elementor';
                            if (!hasElementor && hasGutenberg) {
                                selectedPlatform = 'gutenberg';
                            }

                            // show/hide buttons
                            const $elBtn = box_clone.find('.theme-picker-buttons .xagio-button-elementor.select-theme-editor');
                            const $guBtn = box_clone.find('.theme-picker-buttons .xagio-button-gutenberg.select-theme-editor');

                            hasElementor ? $elBtn.show() : $elBtn.hide();
                            hasGutenberg ? $guBtn.show() : $guBtn.hide();

                            // reset icons/state
                            $elBtn.removeClass('active').find('.xagio-icon.xagio-icon-check').remove();
                            $guBtn.removeClass('active').find('.xagio-icon.xagio-icon-check').remove();

                            // add check icon to the selected platform
                            if (selectedPlatform === 'elementor' && hasElementor) {
                                if ($elBtn.find('.xagio-icon.xagio-icon-check').length === 0) {
                                    $elBtn.prepend('<i class="xagio-icon xagio-icon-check"></i> ');
                                }
                                $elBtn.addClass('active');
                            } else if (selectedPlatform === 'gutenberg' && hasGutenberg) {
                                if ($guBtn.find('.xagio-icon.xagio-icon-check').length === 0) {
                                    $guBtn.prepend('<i class="xagio-icon xagio-icon-check"></i> ');
                                }
                                $guBtn.addClass('active');
                            }

                            // clear any accidental duplicates from the template clone
                            box_clone.find('.preview-template').remove();

                            if (hasElementor) {
                                const $el = $previewProto.clone();
                                $el.addClass('preview-template-elementor').attr('href', `https://templates.xagio.net/${template.key}`).attr('data-xagio-tooltip', '').attr('data-xagio-title', 'Preview Elementor Template').show();
                                box_clone.find('.buttons').prepend($el);
                            }


                            box_clone.attr('data-key', template.key);
                            box_clone.attr('data-claimed', template.claimed);
                            // persist selection on the card
                            box_clone.attr('data-platform', selectedPlatform);
                            box_clone.attr('data-category', template.category);
                            box_clone.find('.screenshot').attr('src', template.image);
                            box_clone.find('.template-name')
                                     .html(template.name)
                                     .attr('data-xagio-tooltip', '')
                                     .attr('data-xagio-title', template.name);
                            box_clone.find('.preview-template').attr('href', `https://templates.xagio.net/${template.key}`);
                            box_clone.find('.template-platform-box').html(platformBadges);
                            if(hasElementor) {
                                box_clone.find('.theme-picker-buttons .xagio-button-elementor').show();
                            }
                            if(hasGutenberg) {
                                box_clone.find('.theme-picker-buttons .xagio-button-gutenberg').show();
                            }

                            box_clone.find('.theme-picker-title').html(template.name);


                            box_clone.attr('data-key', template.key);
                            box_clone.attr('data-claimed', template.claimed ? 1 : 0);
                            box_clone.attr('data-platform', selectedPlatform);
                            box_clone.attr('data-category', template.category);

                            // Store ids on the card
                            box_clone.attr('data-id-elementor', template.id);
                            box_clone.attr('data-id-gutenberg', template.gutenberg_id ? template.gutenberg_id : 0);

                            const $actionBtn = box_clone.find('.template-action-button');

                            $actionBtn.removeClass('claim-template select-template download-template btn-orange btn-blue');

                            $actionBtn
                                .attr('data-template', template.key)
                                .attr('data-id', template.id)
                                .attr('data-claimed', template.claimed ? 1 : 0)
                                .html(template.claimed ? "Select" : "Claim")
                                .addClass(template.claimed ? "btn-blue select-template" : "btn-orange claim-template");

                            templates_holder.append(box_clone);
                        });
                    }

                    // Function to render pagination links based on the provided data array
                    function renderPagination(data) {
                        let paginationContainer = $('#pagination');
                        if (paginationContainer.length === 0) {
                            templates_holder.after('<div id="pagination"></div>');
                            paginationContainer = $('#pagination');
                        }
                        paginationContainer.empty();
                        totalPages = Math.ceil(data.length / itemsPerPage);

                        let prevLink = $('<a href="#" class="page-link prev-link"></a>').text('Prev');
                        if (currentPage > 1) {
                            paginationContainer.append(prevLink);
                        } else {
                            prevLink.addClass('disabled');
                            paginationContainer.append(prevLink);
                        }

                        // Create numbered page links
                        for (let i = 1; i <= totalPages; i++) {
                            let pageLink = $('<a href="#" class="page-link"></a>').text(i).data('page', i);
                            if (i === currentPage) {
                                pageLink.addClass('active');
                            }
                            paginationContainer.append(pageLink);
                        }

                        let nextLink = $('<a href="#" class="page-link next-link"></a>').text('Next');
                        if (currentPage < totalPages) {
                            paginationContainer.append(nextLink);
                        } else {
                            nextLink.addClass('disabled');
                            paginationContainer.append(nextLink);
                        }
                    }

                    // Initial render with all templates
                    renderPage(currentPage, filteredTemplates);
                    renderPagination(filteredTemplates);

                    // Handle click on numbered page links
                    $(document).off('click', '.page-link:not(.prev-link):not(.next-link)')
                               .on('click', '.page-link:not(.prev-link):not(.next-link)', function (e) {
                                   e.preventDefault();
                                   currentPage = $(this).data('page');
                                   renderPage(currentPage, filteredTemplates);
                                   renderPagination(filteredTemplates);
                               });

                    // Handle click on Prev link
                    $(document).off('click', '.prev-link').on('click', '.prev-link', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) {
                            currentPage--;
                            renderPage(currentPage, filteredTemplates);
                            renderPagination(filteredTemplates);
                        }
                    });

                    // Handle click on Next link
                    $(document).off('click', '.next-link').on('click', '.next-link', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) {
                            currentPage++;
                            renderPage(currentPage, filteredTemplates);
                            renderPagination(filteredTemplates);
                        }
                    });

                    // --- MODIFIED SEARCH HANDLER ---
                    // This now filters the full templates array and then re-renders the pagination
                    $(document).on('keyup', '.search', function (e) {
                        e.preventDefault();
                        let search = $(this).val().toLowerCase();
                        filteredTemplates = templates.filter(function (template) {
                            return template.name.toLowerCase().includes(search);
                        });
                        currentPage = 1; // Reset to the first page after a search
                        renderPage(currentPage, filteredTemplates);
                        renderPagination(filteredTemplates);

                        // Show or hide "no templates" message
                        if (filteredTemplates.length === 0) {
                            $("#no-templates").show();
                        } else {
                            $("#no-templates").hide();
                        }
                    });
                    // --- END SEARCH HANDLER ---
                }

            });
        },

        general: function () {
            $(document).on('click', '.export_to_file', function (e) {
                e.preventDefault();
                var button = $(this);
                var target = button.data('target');
                window.location = xagio_data.wp_post + '?action=' + target;
            });

            let default_engine = $('#search_engine').attr('data-default');
            default_engine = default_engine.split(",");

            if (default_engine != '') {
                $('#search_engine').val(default_engine).trigger('change');
            }

            $('#search_country').select2({
                                             width      : "100%",
                                             placeholder: "Select a Country",
                                             allowClear: true
                                         });

            $('#search_location').select2({
                                            width      : "100%",
                                            placeholder: "Select a Location",
                                            allowClear: true,
                                            ajax: {
                                                url: xagio_data.wp_post, 
                                                type: 'POST',
                                                dataType: 'json',
                                                delay: 250,
                                                data: function(params) {
                                                    return {
                                                        action:'xagio_get_cities',
                                                        q: params.term,
                                                        countryCode: $('#search_country').find('option:selected').data('countrycode'),
                                                        page: params.page || 1,
                                                        _xagio_nonce: xagio_data.nonce
                                                    };
                                                },
                                                processResults: function (data, params) {
                                                    params.page = params.page || 1;
                                                    return {
                                                        results: data.data.items,
                                                        pagination: {
                                                            more: data.data.more
                                                        }
                                                    }
                                                },
                                                cache: true
                                            },
                                            minimumInputLength: 3,
                                          });

            $('#search_engine').select2({
                                            matcher    : matcher,
                                            width      : "100%",
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

        wpEasySetup: function () {

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

                $(formDataArray).each(function (index, data) {
                    if (data.value === "1") {
                        let label = $('label[for="' + data.name + '"]').text().replace(/ - /g, "");
                        labelsArray.push(label.trim());
                    }
                    if (data.name === "fs_plugins") {
                        if (data.value !== "") {
                            labelsArray.push("Install plugins: " + data.value.replace(",", ", "));
                        }
                    }
                    if (data.name === "fs_themes") {
                        if (data.value !== "") {
                            labelsArray.push("Install themes: " + data.value.replace(",", ", "));
                        }
                    }
                    if (data.name === "fs_create_categories_list[]" || data.name === "fs_create_blank_pages_list[]" ||
                        data.name === "fs_create_blank_posts_list[]") {
                        if (data.value !== "") {
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

                if (labelsArray.length > 0) {
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
                                type + '" data-name="' + row.slug +
                                '"><i class="xagio-icon xagio-icon-plus"></i> Add</button>' +
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
                                       width      : '100%'
                                   });

            let saved_country = countrySelect.attr('data-default');

            if (saved_country !== '') {
                countrySelect.val(saved_country);
            }

            countrySelect.select2({
                                      placeholder: "Select Country",
                                      width      : '100%',
                                      allowClear: true,
                                  });
        },

        saveKeywordSettingsLanguageOnChange: function () {
            $("#xagioSettings-locationKeywordLanguage").on('change', function () {
                let language = $(this).val();

                if (language !== '') {
                    $.post(xagio_data.wp_post, `action=xagio_set_default_keyword_language&language=${language}`, function (d) {
                        xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                    });
                }
            });
        },

        saveKeywordSettingsCountryOnChange: function () {
            $("#xagioSettings-locationKeywordCountry").on('change', function () {
                let country = $(this).val();

                $.post(xagio_data.wp_post, `action=xagio_set_default_keyword_country&country=${country}`, function (d) {
                    xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                });

            });
        },

        saveRankTrackerCountryOnChange: function () {
            $("#search_country").on('change', function () {
                let country = $(this).val();

                if (country === '') {
                    country = "0"
                }

                $.post(xagio_data.wp_post, `action=xagio_set_default_country&data=${country}`, function (d) {
                    if (d.status == 'success') {
                        xagioNotify('success', d.message);
                        $("#search_location").empty().trigger('change');
                    } else {
                        xagioNotify('danger', d.message);
                    }
                });
            });
        },

        saveRankTrackerLocatioinOnChange: function () {
            $("#search_location").on('change', function () {
                let location = $(this).val();
                $.post(xagio_data.wp_post, {
                    action: 'xagio_set_default_location',
                    data  : location
                }, function (d) {
                    xagioNotify(d.status == 'success' ? 'success' : 'danger', d.message);
                });
            });
        },

        saveSearchEngineOnChange: function () {
            $('#search_engine').on('change', function () {
                let select = $(this);

                let data = select.select2('data');

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

                let sendVal = (val == null) ? '' : val;
                let sendCode = (typeof locationCode === 'undefined') ? '' : locationCode;

                $.post(xagio_data.wp_post, `action=xagio_set_default_audit_location&location=${sendVal}&location_code=${sendCode}`, function (d) {
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
            } else {
                auditLocationSelect.val(null);
            }

            auditLocationSelect.select2({
                                            placeholder: "Select Location",
                                            width      : '100%',
                                            allowClear : true,
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
                                     matcher    : matcher,
                                     width      : "100%",
                                     placeholder: "Select a Search Engine"
                                 });
        },

        aiWizardSaveDefaultLocation: function () {
            $("#AiWizard_default-location").on('change', function () {
                let val = $(this).val();
                let sendVal = (val == null) ? '' : val;

                $.post(xagio_data.wp_post, `action=xagio_set_default_ai_wizard_location&value=${sendVal}`, function (d) {
                    xagioNotify((d.status == 'success') ? d.status : 'danger', d.message);
                });
            })
        },

        setDefaultAiWizardLocation: function () {
            let locationSelect = $("#AiWizard_default-location");
            let value = locationSelect.data('default');

            if (value) {
                $('#AiWizard_default-location option').removeAttr('selected');
                $(`#AiWizard_default-location option[value=${value}]`).attr('selected', true);
            } else {
                locationSelect.val(null);
            }

            locationSelect.select2({
                placeholder: "Select Location",
                width      : '100%',
                allowClear : true,
            });
        },

    };


    $(document).ready(function () {
        actions.loadTemplates();
        actions.general();
        actions.wpEasySetup();
        actions.toggleCaptchaFields();
        actions.locationKeywordSettingsSelect2();
        actions.saveKeywordSettingsCountryOnChange();
        actions.saveKeywordSettingsLanguageOnChange();
        actions.saveRankTrackerCountryOnChange();
        actions.saveRankTrackerLocatioinOnChange();
        actions.saveSearchEngineOnChange();
        actions.auditSaveDefaultLocation();
        actions.setDefaultAuditLocation();
        actions.aiWizardSaveDefaultSearchEngine();
        actions.setDefaultAiWizardSearchEngine();
        actions.aiWizardSaveDefaultLocation();
        actions.setDefaultAiWizardLocation();
    });


})(jQuery);
