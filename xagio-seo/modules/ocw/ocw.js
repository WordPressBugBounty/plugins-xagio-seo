(function ($) {

    let GLOBAL_PRICING_DATA = {
        // from xags response
        competitionCost: null,
        // from average prices response
        optimizeAiCost       : null,
        generateAiContentCost: null,
        generateAiSchemaCost : null,
        imagesEditAI         : null,
        imagesGenerateAI     : null
    };

    var average_prices = null;
    let requestsRemaining = 0;
    var selectedGroups = [];
    let elementorVersion = 'free';
    let lastSeedGroupId = null;
    let selected_seed_keywords = [];
    let $grid = null;
    let currentProjectID = 14;
    let isOriginalOrder = true;
    let project_ids = [];
    let domains_length = 0;
    let templates = [];
    let elementorTemplateZip;
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
    let nonce = xagio_data.elementor_nonce;
    let ajaxurl = xagio_data.wp_get;
    let homepage_group = 0;
    let global_steps = [];

    let matcher = function (params, data) {
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
        allowances           : {
            xags_allowance: $('.xrenew'),
            xags          : $('.xbanks'),
            cost          : []
        },
        checkAIClusterStatus: function () {

            $.post( xagio_data.wp_post, {
                action       : 'xagio_ai_check_status_cluster',
                project_id   : currentProjectID
            }, function ( resp ) {

                // resp example: {status:"success", message:"Image Status retrieved!", data:"completed"}
                if ( resp.status === 'success' ) {

                    const status = resp.data; // "running" | "queued" | "completed" | false

                    if ( status === 'running' ) {
                        $('.start-wizard, .reset-wizard, .ai-clustering')
                            .each(function () {
                                // works with your custom .enable() plugin if present,
                                // otherwise fall back to a plain prop().
                                if ( $.fn.disable ) {
                                    $(this).disable();
                                } else {
                                    $(this).prop('disabled', false);
                                }
                            });

                        $('.loading').removeClass('hidden');

                        actions.pollClusterStatus(currentProjectID);
                    }

                }
            }, 'json' );
        },
        remoteCheckStatuses  : function () {
            $.post(xagio_data.wp_post, `action=xagio_ocw_check_statuses`);
        },
        trackRankings        : function () {

            $('#search_country').select2({
                                             width      : "100%",
                                             placeholder: "Select a Country",
                                         });

            $('#search_location').select2({
                                              width             : "100%",
                                              placeholder       : "Select a Location",
                                              allowClear        : true,
                                              ajax              : {
                                                  url           : xagio_data.wp_post,
                                                  type          : 'POST',
                                                  dataType      : 'json',
                                                  delay         : 250,
                                                  data          : function (params) {
                                                      return {
                                                          action      : 'xagio_get_cities',
                                                          q           : params.term,
                                                          countryCode : $('#search_country').find('option:selected').data('countrycode'),
                                                          page        : params.page || 1,
                                                          _xagio_nonce: xagio_data.nonce
                                                      };
                                                  },
                                                  processResults: function (data, params) {
                                                      params.page = params.page || 1;
                                                      return {
                                                          results   : data.data.items,
                                                          pagination: {
                                                              more: data.data.more
                                                          }
                                                      }
                                                  },
                                                  cache         : true
                                              },
                                              minimumInputLength: 3,
                                          });

            $('#search_engine').select2({
                                            matcher    : matcher,
                                            width      : "100%",
                                            placeholder: "Select a Search Engine"
                                        });
        },
        wizardEvents         : function () {
            $(document).on('click', '.reload-wizard', function (e) {
                e.preventDefault();
                document.location.reload();
            });
            $(document).on('click', '.next-templates', function (e) {
                e.preventDefault();

                let template = $('.xagio-column-container.box-template.selected');
                let use_xagio_template = $('#XAGIO_USE_TEMPLATE').val();
                let remove_pages = $('#XAGIO_REMOVE_PAGES').val();

                if (template.length > 0 && use_xagio_template == 1) {

                    let template_button = template.find('.template-action-button');
                    let template_id = template_button.data('id');
                    let template_claimed = template.data('claimed');
                    let template_key = template.data('key');
                    let template_platform = template.data('platform');


                    let step_5 = $('.ocw-step-templates');
                    let step_6 = $('.ocw-step-elementor');
                    step_5.fadeOut(function () {
                        step_6.fadeIn(function () {
                        });
                    });


                    // Project already loaded from Project Planner, skip keyword_research
                    let update_step = 'keyword_research';
                    if(global_steps.step === 'project_created') {
                        update_step = `project_created&project_id=${global_steps.data.project_id}`;
                    }


                    if(template_platform === 'elementor') {
                        // First, ensure Elementor is installed (or install it)
                        actions.checkAndInstallElementor().always(function () {

                            $.post(xagio_data.wp_post, `action=xagio_ocw_step&step=${update_step}&templates=${use_xagio_template}&remove_pages=${remove_pages}&editor_type=${template_platform}&template_key=${template_key}`);

                            // Now that Elementor is present, continue with the template workflow
                            if (template_claimed) {
                                $.post(xagio_data.wp_post, `action=xagio_ocw_get_template&template_key=${template_key}&template_platform=${template_platform}`, function (d) {
                                    if (d.status === 'success' && d.data) {
                                        // Fetch the template file
                                        fetch(d.data)
                                            .then(response => response.blob())
                                            .then(blob => {
                                                elementorTemplateZip = new File([blob], `${template_key}.zip`, {type: 'application/zip'});
                                                actions.startImportProcess();
                                            })
                                            .catch(error => console.error("Error fetching template:", error));
                                    } else {
                                        console.error("Error retrieving template:", d.message);
                                    }
                                });
                            } else {
                                // Claim template first then get template
                                $.post(xagio_data.wp_post, `action=xagio_ocw_claim_template&template_id=${template_id}`, function (d) {
                                    if (d.status === 'success') {
                                        template.data('claimed', true);
                                        $.post(xagio_data.wp_post, `action=xagio_ocw_get_template&template_key=${template_key}&template_platform=${template_platform}`, function (d) {
                                            if (d.status === 'success' && d.data) {
                                                // Fetch the template file
                                                fetch(d.data)
                                                    .then(response => response.blob())
                                                    .then(blob => {
                                                        elementorTemplateZip = new File([blob], `${template_key}.zip`, {type: 'application/zip'});
                                                        actions.startImportProcess();
                                                    })
                                                    .catch(error => console.error("Error fetching template:", error));
                                            } else {
                                                console.error("Error retrieving template:", d.message);
                                            }
                                        });
                                    } else {
                                        xagioNotify('error', d.message);
                                    }
                                });
                            }
                        });
                    } else {
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

                            // Mark OCW step (align label and payload)
                            $.post(xagio_data.wp_post, `action=xagio_ocw_step&step=${update_step}&templates=${use_xagio_template}&remove_pages=${remove_pages}&editor_type=gutenberg&template_key=${template_key}`);

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
                                        if (res.data && res.data.thumbnails_notice) {
                                            logWarn(res.data.thumbnails_notice);
                                        }

                                        // Transition & clear UI
                                        $('.ocw-step-elementor').fadeOut(function () {
                                            $('.ocw-step-profiles').fadeIn();
                                        });

                                        setTimeout(() => { out.empty(); }, 1500);
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
                } else {

                    let step_templates = $('.ocw-step-templates');
                    let step_profiles = $('.ocw-step-profiles');
                    step_templates.fadeOut(function () {
                        step_profiles.fadeIn();
                    });

                }
            });

            $(document).on('click', '.start-wizard', function (e) {
                e.preventDefault();

                let selectedGroups = $('.data .groupSelect:checked').length;
                if (selectedGroups < 1) {
                    xagioNotify('error', 'Please set select some groups first!');
                    return;
                }

                let isHomeSet = $('.setHome.xagio-group-button-orange').length > 0;
                let homepageName = '';
                if (isHomeSet) {
                    let getHomepageName = $('.setHome.xagio-group-button-orange').parents('.group-action-buttons').find('.groupInput').val();
                    homepageName = `, with ${getHomepageName} set as the home page`;
                }

                let costXags = $('.calculated-wizard-cost').text().trim();

                xagioModal('Run Agent X', `You are about to run Agent X and build ${selectedGroups} pages${homepageName}. This will consume ${costXags} xBanks/xRenew. Are you sure?`, function (yes) {
                    if (yes) {

                        if (!isHomeSet) {
                            xagioModal('Homepage Group is not set!', 'You did not set a Homepage based of a group. This is optional but recommended if you are using Xagio Templates. Click on the button <button type="button" class="xagio-group-button"><i class="xagio-icon xagio-icon-home"></i></button> next to a group to set up a Homepage.', function (yes) {
                                if (yes) {
                                    processWizard();
                                }
                            });
                        } else {
                            processWizard();
                        }

                    }
                });

            });

            function processWizard() {

                let language = $('#getCompetition_languageCode').val()
                let location = $('#getCompetition_locationCode').val()

                if (language == '' || location == '') {
                    xagioNotify('error', 'Please set Language and Location before continuing...');
                    return;
                }

                let balance = actions.allowances.xags_sum;

                if (balance < parseFloat($('.calculated-wizard-cost').text())) {
                    xagioNotify("warning", "You do not have enough XAGS to perform this operation!");
                    return;
                }

                if (!actions.validateVolumeAndCPC()) {
                    xagioNotify('error', 'Some keywords are missing Volume or CPC metrics. Please collect these metrics for all keywords before continuing. You can do this by going to Project Planner → Get Volume & CPC.');
                    return;
                }


                let step_3 = $('.ocw-step-3');
                let step_4 = $('.ocw-step-4');

                step_3.fadeOut(function () {
                    step_4.fadeIn();
                });

                let ids = [];
                $('.project-groups .groupSelect:not(:checked)').each(function () {
                    let group = $(this).parents('.xagio-group');
                    ids.push(group.find('[name="group_id"]').val());
                });

                ids = ids.join(',');

                // Rank Tracker
                let search_engine_param = $('input[name="search_engine[]"]')
                    .map(function () {
                        return `rank_tracker_search_engine[]=${encodeURIComponent($(this).val())}`;
                    })
                    .get()
                    .join('&');

                let search_country = $('#search_country').val();
                let search_location = $('#search_location').val();
                let loc_name = $('#search_location').val() ==
                               '' ? $('#search_country option:selected').text() : $('#search_location option:selected').text();

                $.post(xagio_data.wp_post, `action=xagio_ocw_step&step=running_wizard&progress=1&delete_groups=${ids}&language=${language}&location=${location}&rank_tracker_search_country=${search_country}&rank_tracker_search_location=${search_location}&locname=${loc_name}&${search_engine_param}`, function () {

                    actions.loadSteps();

                });

            }
        },
        validateVolumeAndCPC : function () {
            let allValid = true;

            $('.groupSelect:checked').closest('.xagio-group')
                                     .find('.keywords-data div[data-target="volume"], .keywords-data div[data-target="cpc"]')
                                     .each(function () {
                                         if ($(this).text().trim() === '') {
                                             allValid = false;
                                             return false; // break loop
                                         }
                                     });

            return allValid;
        },
        xagsCostOutput       : function (cost) {
            let xReview = parseFloat(actions.allowances.xags_allowance.find('.value').html().trim());
            let xBank = parseFloat(actions.allowances.xags.find('.value').html().trim());

            let output = "";
            if (cost <= xReview) {
                output = `<div><img class="xags" src="${siteUrl}/assets/img/logos/xRenew.png" alt="xRenew"/><span>${cost}</span></div>`;
            } else if (xReview == 0) {
                output = `<div><img class="xags" src="${siteUrl}/assets/img/logos/xBanks.png" alt="xBanks"/><span>${cost}</span></div>`;
            } else if (xBank > cost) {
                let remaining_cost = parseFloat(cost - xReview).toFixed(2);

                output = `<div><img class="xags" src="${siteUrl}/assets/img/logos/xRenew.png" alt="xRenew"/><span>${xReview}</span></div> and <div><img class="xags" src="${siteUrl}/assets/img/logos/xBanks.png" alt="xBanks"/><span>${remaining_cost}</span></div>`;
            }

            return output;
        },
        loadProjectEvents    : function () {
            // init Masonry
            $grid = $('.data').masonry({
                                           itemSelector   : '.xagio-group',
                                           horizontalOrder: true,
                                           percentPosition: true,
                                           // fitWidth: true,
                                           gutter: 40
                                       });

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

            $(document).on('click', '.setHome', function (e) {
                e.preventDefault();

                let group = $(this).parents('.xagio-group');
                let group_id = group.find('[name="group_id"]').val();

                $.post(xagio_data.wp_post, 'action=xagio_ocw_set_homepage&group_id=' + group_id, function (d) {

                    $('.setHome').removeClass('xagio-group-button-orange');

                    if (d.status == 'success') {
                        let home_button = $('[name="group_id"][value="' + d.data +
                                            '"]').parents('.xagio-group').find('.setHome');
                        home_button.addClass('xagio-group-button-orange');
                        homepage_group = group_id;
                    }

                });
            });

            $(document).on('change', '#top-ten-language-select', function (e) {
                e.preventDefault();
                $('#top-ten-language').val($(this).find('option:selected').attr('data-lang-code'));
            });

            $(document).on('click', '.sort-groups-asc', function (e) {
                $(this).hide();
                $('.sort-groups-desc').show();

                let groups = $('.project-groups .xagio-group');

                let sortedGroups = groups.toArray().sort(function (a, b) {
                    let valueA = $(a).find('input[name="group_name"]').val().toLowerCase().trim();
                    let valueB = $(b).find('input[name="group_name"]').val().toLowerCase().trim();

                    return valueA.localeCompare(valueB);
                });

                $('.project-groups .data').empty().append(sortedGroups);

                actions.updateGrid();
                actions.updateElements();
            });

            $(document).on('click', '.sort-groups-desc', function (e) {
                $(this).hide();
                $('.sort-groups-asc').show();

                let groups = $('.project-groups .xagio-group');

                let sortedGroups = groups.toArray().sort(function (a, b) {
                    let valueA = $(a).find('input[name="group_name"]').val().toLowerCase().trim();
                    let valueB = $(b).find('input[name="group_name"]').val().toLowerCase().trim();
                    return valueB.localeCompare(valueA);
                });

                $('.project-groups .data').empty().append(sortedGroups);

                actions.updateGrid();
                actions.updateElements();
            });

            $(document).on('click', '.select-all-recommended-websites', function () {
                let current_page_view = $('.show-page.active').data('page');

                if ($(`.top-ten-result.recommended.page-${current_page_view}`).length > 0) {
                    $(`.top-ten-result.recommended.page-${current_page_view}`).each(function () {
                        $(this).find('.select-website').prop('checked', true).trigger('change');
                    });
                }

            });

            $(document).on('change', '.select-website', function () {

                let count_checked = 0;
                $('.top-ten-results .select-website').each(function () {
                    let checkbox = $(this);
                    if (checkbox.prop('checked')) {
                        count_checked++;
                    }
                });

                let aiWizardCost = count_checked * actions.allowances.cost.wizards;

                let output = actions.xagsCostOutput(aiWizardCost);
                $('.ai-wizard-cost-label').find('#xagsCost').html(`This action will cost you ${output}`);

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

            let ocw = $('.ocw');

            $('#top-ten-language-select').select2({
                                                      dropdownParent: ocw,
                                                      matcher       : actions.matcher,
                                                      placeholder   : "Select a Search Engine"
                                                  });

            $('#top_ten_search_engine').select2({
                                                    dropdownParent: ocw,
                                                    matcher       : actions.matcher,
                                                    placeholder   : "Select a Search Engine"
                                                });

            $('#top_ten_search_location').select2({
                                                      dropdownParent: ocw,
                                                      matcher       : actions.matcher,
                                                      placeholder   : "Select a Search Location"
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

                $(`.page-${active_page}`).fadeOut('fast', function () {
                    $(`.page-${show_page}`).fadeIn();
                });


            });

            $(document).on('keyup paste', '#top-ten-location-text, #top-ten-keyword', function () {

                let location = $('#top-ten-location-text').val().toLowerCase().trim();
                let keyword = $('#top-ten-keyword').val().toLowerCase().trim();

                if (isOriginalOrder) {
                    keyword = keyword + " " + location;
                } else {
                    keyword = location + " " + keyword;
                }

                $('.main-keyword').val(keyword.toLowerCase().trim());
                $('.keyword-example').html(keyword.toLowerCase().trim());
            });

            $(document).on('click', '#swap-words', function () {

                let location = $('#top-ten-location-text').val().toLowerCase().trim();
                let keyword = $('#top-ten-keyword').val().toLowerCase().trim();
                let mainKeyword;

                if (isOriginalOrder) {
                    mainKeyword = location + " " + keyword;
                } else {
                    mainKeyword = keyword + " " + location;
                }

                $('.keyword-example').html(mainKeyword);
                $('.main-keyword').val(mainKeyword.toLowerCase().trim());

                isOriginalOrder = !isOriginalOrder;
            });

            $(document).on('click', '.create-project', function (e) {
                e.preventDefault();

                // Get all checked checkboxes that belong to the class 'select-website'
                let selected_websites = $('.top-ten-results input[name="select-website"]:checked');

                // Check if at least one website is selected
                if (selected_websites.length < 1) {
                    xagioNotify("warning", "Please select at least one website in table above");
                    return;
                }

                let balance = actions.allowances.xags_sum;

                if (balance < selected_websites.length) {
                    xagioNotify("warning", "You do not have enough XAGS to perform this operation!");
                    return;
                }

                if (selected_websites.length > 1 && $('#keyword_contain').val() == 0 && $('#aiwizard-type').val() ===
                    'local') {

                    xagioModal('Warning', 'Selecting more than one website while having "Filter results to only contain keyword below" checked off may produce a lot of non relevant keywords and groups to your selected keyword. Do you want to continue?', function (yes) {

                        if (yes) {

                            // Collect all selected domains
                            let domains = selected_websites.map(function () {
                                return $(this).val();
                            }).get(); // Assuming you want to send the domains as a comma-separated string

                            // Hide elements during processing
                            $(".top-ten-pagination-container").hide();
                            $('.top-ten-results-info').slideUp();

                            // Show a loading message
                            $('.top-ten-results').html(`
        <div class="lds-facebook"><div></div><div></div><div></div></div>
        <p class="xagio-text-center generating-project-loading">Finding & Clustering Your Keywords for you... (Please do not close, refresh or leave this page) <br> Once completed you will be redirected to your project.</p>
    `);


                            $('.xagio-agent-type').hide();
                            // Call the auditWebsite function with the collected domains
                            actions.auditWebsite('Wizard', domains);

                        }

                    });


                } else {

                    // Collect all selected domains
                    let domains = selected_websites.map(function () {
                        return $(this).val();
                    }).get(); // Assuming you want to send the domains as a comma-separated string

                    // Hide elements during processing
                    $(".top-ten-pagination-container").hide();
                    $('.top-ten-results-info').slideUp();

                    // Show a loading message
                    $('.top-ten-results').html(`
        <div class="lds-facebook"><div></div><div></div><div></div></div>
        <p class="xagio-text-center generating-project-loading">Finding & Clustering Your Keywords for you... (Please do not close, refresh or leave this page) <br> Once completed you will be redirected to your project.</p>
    `);

                    $('.xagio-agent-type').hide();
                    // Call the auditWebsite function with the collected domains
                    actions.auditWebsite('Wizard', domains);
                }
            });

            $(document).on('click', '.search-top-ten', function (e) {
                e.preventDefault();

                let btn = $(this);

                let websites_holder = $('.top-ten-results');
                let main_keyword = $('.main-keyword').val().toLowerCase();
                let keyword = $('.top-websites-keyword').val().toLowerCase();
                let location = $('#top-ten-location-text').val().toLowerCase();
                let search_engine = $('#top_ten_search_engine').val();
                let search_engine_text = $('#top_ten_search_engine option:selected').text();
                let search_location = $('#top_ten_search_location').val();
                let search_location_text = $('#top_ten_search_location option:selected').text();
                let top_ten_results_info = $('.top-ten-results-info');

                let step_1 = $('.ocw-step-1');
                let step_2 = $('.ocw-step-2');
                step_2.find('a.create-project').hide();
                step_2.find('.ai-wizard-cost-label').hide();
                step_2.find('a.prev-step-2').hide();
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

                $('#top-ten-language-select option').each(function () {
                    $(this).attr('selected', false);

                    if ($(this).text().includes(search_location_text)) {
                        $(this).prop('selected', true);
                        $('#top-ten-language-select').trigger('change');
                    }
                });

                btn.disable();

                step_1.fadeOut(function () {
                    step_2.fadeIn();
                });

                $.post(xagio_data.wp_post, `action=xagio_get_top_ten&main-keyword=${main_keyword}&location=${location}&keyword=${keyword}&search_engine=${search_engine}&search_location=${search_location}&search_engine_text=${search_engine_text}&search_location_text=${search_location_text}`, function (d) {

                    top_ten_results_info.slideDown();
                    btn.disable();
                    step_2.find('a.create-project').show();
                    step_2.find('.ai-wizard-cost-label').show();
                    step_2.find('a.prev-step-2').show();

                    if (d.status === 'error') {
                        xagioNotify("danger", d.message);
                        return;
                    }

                    let html = '';
                    let page = 1;
                    let pages = [];
                    let count_checked = 0;

                    for (let i = 0; i < d.data.length; i++) {
                        let website_row = $('.top-ten-result-template.template').clone().removeClass('template');
                        let for_id = `select-website${i + 1}`;

                        let website = d.data[i];

                        website_row.find('.website-position').html(`#${website['position']}`);
                        website_row.find('.select-website').attr('id', for_id).val(website['url']);
                        website_row.find('.g-url').html(website['url']).attr('href', website['url']);
                        website_row.find('.g-title').html(website['title']).attr('for', for_id);
                        website_row.find('.g-desc').html(website['snippet']);


                        if (i % 10 === 0) {
                            pages.push((i / 10) + 1);
                            page = (i / 10) + 1;
                        }


                        if (website['recommended'] === true) {
                            if (page === 1) {
                                count_checked++;
                            }
                            website_row.find('.top-ten-result').addClass('recommended');
                        }

                        if (website['listing'] === true) {
                            website_row.find('.top-ten-result').addClass('not-recommended');
                        }

                        website_row.find('.top-ten-result').addClass(`page-${page}`);

                        html += website_row.html();
                    }

                    let aiWizardCost = count_checked * actions.allowances.cost.wizards;

                    let output = actions.xagsCostOutput(aiWizardCost);
                    $('.ai-wizard-cost-label').find('#xagsCost').html(`This action will cost you ${output}`);

                    let pagination = '<div class="top-ten-pagination">';
                    for (let i = 0; i < pages.length; i++) {
                        pagination += `<span class="show-page ${i ===
                                                                0 ? 'active' : ''}" data-page="${pages[i]}">${pages[i]}</span>`;
                    }

                    pagination += '</div>';

                    $('.top-ten-pagination-container').html(pagination);

                    websites_holder.html(html);
                    $('.top-ten-options').slideDown();
                    $('.recommended.page-1').find('.select-website').prop('checked', true);


                    if ($('#aiwizard-type').val() === 'global') {
                        let words = main_keyword.trim().split(/\s+/); // handles multiple spaces
                        if (words[0]) {
                            $('.main_keyword_contain').val(words[0]);
                        }

                        $('.xagio-agent-type').show();
                    } else {
                        $('.xagio-agent-type').hide();
                    }
                });
            });

            $(document).on('click', '.option-picker:not(.disabled)', function () {
                let option = $(this).attr('data-type');
                $('#aiwizard-type').val(option);

                let keyword_step = $('.ocw-step-1');
                let top_ten_step = $('.ocw-step-2');

                // In future, determine type and change stuff accordingly if necessary

                if (option === 'global') {
                    keyword_step.find('.keyword-research-local').hide();
                    keyword_step.find('#top-ten-keyword').attr('placeholder', 'e.g. weight loss, dedicated hosting...');
                } else {
                    keyword_step.find('.keyword-research-local').show();
                    keyword_step.find('#top-ten-keyword').attr('placeholder', 'e.g. pool cleaning');
                }

                $('.ocw-start').fadeOut(function () {
                    $('.ocw-step-templates').fadeIn();
                });

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

                    if ($('.ocw-step-1').is(':visible')) {
                        $('.search-top-ten').trigger('click');
                    }

                }
            });

            $(document).on('click', '.prev-step-search', function (e) {
                e.preventDefault();

                $('.ocw-step-1').fadeOut(function () {
                    $('.ocw-step-profiles').fadeIn();
                });
            });

            $(document).on('click', '.prev-step-templates', function (e) {
                e.preventDefault();

                $('.ocw-step-templates').fadeOut(function () {
                    $('.ocw-start').fadeIn();
                });
            });

            $(document).on('click', '.next-profiles', function (e) {
                $('.ocw-step-profiles').fadeOut(function () {

                    if(global_steps.step === 'project_created') {

                        currentProjectID = global_steps.data.project_id;
                        let step_3 = $('.ocw-step-3');

                        actions.checkAIClusterStatus();
                        actions.loadProjectManually();
                        actions.getSavedKeywordSettingsLanguageAndCountry();
                        actions.setDefaultAiWizardSearchEngine();
                        actions.setDefaultAiWizardLocation();

                        step_3.fadeIn(function () {
                            actions.updateGrid();
                        });


                    } else {
                        $('.ocw-step-1').fadeIn();
                    }
                });
            });

            $(document).on('click', '.prev-step-profiles', function (e) {
                $('.ocw-step-profiles').fadeOut(function () {
                    $('.ocw-step-templates').fadeIn();
                });
            });

            $(document).on('click', '.prev-step-1', function (e) {
                e.preventDefault();

                $('.ocw-step-1').fadeOut(function () {
                    $('.ocw-step-templates').fadeIn();
                });
            });

            $(document).on('click', '.prev-step-2', function (e) {
                e.preventDefault();

                $('.ocw-step-2').fadeOut(function () {
                    $('.ocw-step-1').fadeIn();
                });
            });

            $(document).on('click', '.reset-wizard', function (e) {
                e.preventDefault();

                xagioModal('Reset Agent X', 'This will reset Agent X and let you go back to the first step? Are you sure you want to do this?', function (yes) {

                    if (yes) {

                        $.post(xagio_data.wp_post, 'action=xagio_ocw_reset_wizard', function () {
                            document.location.reload();
                        });

                    }

                });
            });
        },
        escapeRegExp         : function (string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        },
        matcher              : function (params, data) {
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
        },
        saveLabelOnInput     : function () {
            $(document).on('keyup', 'input[name="group_name"]', actions.debounce(actions.saveData, 700));
        },
        debounce             : function (func, delay) {
            let timeout;
            return function (...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            }
        },
        saveData             : function () {
            let group_input = $(this);
            let group = group_input.parents('.xagio-group');
            let group_id = group.find('input[name="group_id"]').val();
            let group_name = group_input.val();
            group_name = encodeURIComponent(group_name);

            $.post(xagio_data.wp_post, `action=xagio_ocw_update_group_name&project_id=${currentProjectID}&group_id=${group_id}&group_name=${group_name}`, function (response) {
                xagioNotify(response.status, response.message);
            });
        },
        previewCluster       : function () {
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
        phraseMatch          : function () {
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

                        xagioNotify("success", d.message);
                    }

                    btn.disable();

                });

            });
        },
        deselectAllGroups    : function () {
            $(document).on('click', '.deselectAllGroups', function (e) {
                e.preventDefault();
                $('.project-groups .groupSelect').prop('checked', false);

                actions.calculateCosts();
            });
        },
        selectAllGroups      : function () {
            $(document).on('click', '.selectAllGroups', function (e) {
                e.preventDefault();
                $('.project-groups .groupSelect').prop('checked', !$('.project-groups .groupSelect').prop('checked'));

                actions.calculateCosts();
            });

            $(document).on('change', '.groupSelect', function () {
                let group = $(this).parents('.xagio-group');
                let group_id = group.find('[name="group_id"]').val();

                if ($(this).is(':checked')) {
                    // Add the group_id if it's not already in the array
                    if (!selectedGroups.includes(group_id)) {
                        selectedGroups.push(group_id);
                    }
                } else {
                    // Remove the group_id from the array
                    selectedGroups = selectedGroups.filter(id => id !== group_id);
                }

                // Debug output (optional)
                console.log('Selected Groups:', selectedGroups);

                actions.calculateCosts();
            });

        },
        consolidateKeywords  : function () {
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

                $.post(xagio_data.wp_post, 'action=xagio_consolidateKeywords&' + form.serialize() + '&project_id=' +
                                           currentProjectID, function (d) {
                    btn.disable();
                    consolidateModal[0].close();

                    if (d.status === 'error') {
                        xagio_notify("danger", d.message);
                    } else {
                        xagioNotify("success", d.message);

                        if ($('#XAGIO_REMOVE_EMPTY_GROUPS').val() == 1) {
                            $.post(xagio_data.wp_post, 'action=xagio_deleteEmptyGroups&project_id=' +
                                                       currentProjectID, function (d) {
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
        deleteGroups         : function () {
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
                    xagioNotify("success", "Groups successfully deleted.");

                    actions.calculateCosts();
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
                    xagioNotify("success", "Successfully deleted Empty groups.");
                    actions.calculateCosts();
                });
            });

            $('#deleteEmptyGroups')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('#skipGroups').prop('checked', false);
            });
        },
        seedKeyword          : function () {
            let phrase_match_labels = [
                `Broad Match ( <span class="phrase-match-underline">cat</span>, <span class="phrase-match-underline">cat</span>s, <span class="phrase-match-underline">cat</span>apult, wild<span class="phrase-match-underline">cat</span> )`,
                `Phrase Match ( <span class="phrase-match-underline">cat</span> )`
            ];

            $(document).on('change', '.seed-word-match', function () {
                let input = $(this);

                if (input.val() == "1") {
                    input.parent().find('.word_match_label').html(phrase_match_labels[1]);
                } else {
                    input.parent().find('.word_match_label').html(phrase_match_labels[0]);
                }
            });

            $(document).on('click', '.seed-keywords-panel-global', function () {
                let btn = $(this);
                let form = $('#seedPanelForm');

                btn.disable();

                $.post(xagio_data.wp_post, 'action=xagio_seedKeywords&' + form.serialize() + '&project_id=' +
                                           currentProjectID +
                                           '&delete_empty_groups=true&word_match=0&group_id=0', function (d) {

                    btn.disable();

                    if (d.status === 'error') {
                        xagioNotify("danger", d.message);
                    } else {

                        actions.clearSeedKeywordModal();

                        xagioNotify("success", d.message);
                        actions.loadProjectManually();
                    }

                });

            });

            $(document).on('click', '.seedKeyword', function (e) {
                e.preventDefault();
                let group_id = $(this).data('group-id');
                let seedKeywordModal = $('#seedKeywordsModal');
                seedKeywordModal.find('input[name="group_id"]').val(group_id);
                if (group_id != lastSeedGroupId) {
                    lastSeedGroupId = group_id;
                    seedKeywordModal.find(".seed_group_container_template").remove();
                    seedKeywordModal.find("input[type='text']").val("");
                }

                seedKeywordModal[0].showModal();
            });


            $(document).on("click", "#add_multiple_groups", function () {
                let template = $(".seed_group_container_template.xagio-hidden").clone().removeClass('xagio-hidden');
                $("#seed_group_container").append(template);
            });

            $(document).on('click', '.delete_seed_row', function () {
                $(this).parents(".seed_group_container_template").remove();
            });

            $(document).on('submit', '#seedKeywordsForm', function (e) {
                e.preventDefault();

                let seedKeywordModal = $('#seedKeywordsModal');
                let form = $(this);
                let btn = form.find('.autoGenerateGroupsBtn');
                btn.disable();

                $.post(xagio_data.wp_post, 'action=xagio_seedKeywords&' + form.serialize() + '&project_id=' +
                                           currentProjectID + '&delete_empty_groups=true', function (d) {

                    btn.disable();

                    if (d.status === 'error') {
                        xagioNotify("danger", d.message);
                    } else {

                        actions.clearSeedKeywordModal();

                        seedKeywordModal[0].close();
                        xagioNotify("success", d.message);
                        actions.loadProjectManually();
                    }

                });
            });
        },
        clearSeedKeywordModal: function () {
            let modal = $('#seedKeywordsModal');

            modal.find('.seed-word-match').val(0);
            modal.find('.seed-word-match').parents('.xagio-slider-container').find('.xagio-slider-button').removeClass('on');
            modal.find('.seed-word-match').parents('.xagio-slider-container').find('.word_match_label').html(`Broad Match ( <span class="phrase-match-underline">cat</span>, <span class="phrase-match-underline">cat</span>s, <span class="phrase-match-underline">cat</span>apult, wild<span class="phrase-match-underline">cat</span> )`);
            modal.find(".seed_group_container_template").remove();
            modal.find("input[type='text']").val("");
            $('.seedKeyword').html('Seed Keywords');
            $('.keywords-action-button').html('Keywords <i class="xagio-icon xagio-icon-arrow-down"></i>');

            $('.jqcloud-word').removeClass('highlightWordInCloud');

            $('.seed-keywords-inputs').empty();
            $('.seed-keywords-panel-select').hide();
            $('.seed-keywords-panel-start').show();

            $('.xagio-keyword-cloud-global').hide();
            $('.seed-keywords-global').hide();
            let global_could_btn = $('.global-wordCloud');

            global_could_btn.removeClass('open');
            global_could_btn.attr('data-xagio-title', 'Open Global Word Cloud');
            global_could_btn.find('i').removeClass().addClass('xagio-icon xagio-icon-cloud');
            $('.xagio-tooltip').remove();

            selected_seed_keywords = [];

        },
        copyKeywords         : function () {

            $(document).on('click', '.copyKeywordsButton', function (e) {
                e.preventDefault();

                actions.copyTextToClipboard($('#copiedKeywords').val());

                xagioNotify("success", "Keywords are successfully copied to your clipboard.", true);

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
        deleteKeywords       : function () {
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
                    actions.calculateCosts();
                })
            });

            $('#deleteKeywords')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('#keywordIds').val(0);
                modal.find('#deleteRanks').val(0).prop('checked', false);
                modal.find('.delete-keywords-number').text('-');
            });
        },
        calculateAndTrim     : function (t) {
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
                            e.preventDefault();
                            const $clicked = $(e.currentTarget);
                            const word = $clicked.text().trim();
                            const isGlobal = $clicked.closest('.xagio-keyword-cloud-global').length > 0;
                            let seed_panel = $('.seed-keywords-inputs');

                            // Toggle highlight class on the clicked element
                            if ($clicked.hasClass('highlightWordInCloud')) {
                                if (isGlobal) {
                                    selected_seed_keywords = jQuery.grep(selected_seed_keywords, function (value) {
                                        return value != word;
                                    });
                                }
                                $clicked.removeClass('highlightWordInCloud');
                            } else {
                                if (isGlobal) {
                                    selected_seed_keywords.push(word);
                                }
                                $clicked.addClass('highlightWordInCloud');
                            }

                            seed_panel.empty();
                            for (let j = 0; j < selected_seed_keywords.length; j++) {
                                let kw = selected_seed_keywords[j];
                                let template_panel = $(".seed_panel_container_template.xagio-hidden").clone().removeClass('xagio-hidden');
                                seed_panel.append(template_panel);
                                seed_panel.find('[name="seed_group_name[]"]').eq(j).val(kw);
                                seed_panel.find('[name="seed_keywords[]"]').eq(j).val(kw);
                            }

                            if (isGlobal && selected_seed_keywords.length > 0) {
                                $('.seed-keywords-panel-start').hide();
                                $('.seed-keywords-panel-select').show();
                            } else {
                                $('.seed-keywords-panel-start').show();
                                $('.seed-keywords-panel-select').hide();
                            }


                            // Determine groups: all groups for global, or just the current group
                            let groups = isGlobal ? $('.project-groups').find('.xagio-group') : $clicked.closest('.xagio-group');

                            groups.each(function () {
                                const group = $(this);
                                // Set last seed group id from this group
                                lastSeedGroupId = group.find('[name="group_id"]').val();

                                // Clear all seed group containers and reset inputs
                                const seedKeywordModal = $('#seedKeywordsModal');
                                seedKeywordModal.find(".seed_group_container_template").remove();
                                seedKeywordModal.find("input[type='text']").val("");

                                // Remove any existing highlight for this word in the group's keywords
                                group.find('.keywordInput[data-target="keyword"]').each(function () {
                                    const $kwElem = $(this);
                                    const newHtml = $kwElem.html().replace(
                                        new RegExp(`<b\\s+class="highlightCloud">\\s*${actions.escapeRegExp(word)}\\s*<\\/b>`, "gi"),
                                        word
                                    );
                                    $kwElem.html(newHtml);
                                    const tr = $kwElem.closest(".ui-sortable-handle");
                                    tr.find("input.keyword-selection").prop('checked', false);
                                    tr.removeClass("selected");
                                });

                                // If the clicked word is now highlighted, add highlighting and update seed form containers
                                if ($clicked.hasClass('highlightWordInCloud')) {
                                    let forms = $('#seedKeywordsForm');
                                    // Get the cloud container that holds highlighted words
                                    let cloud;
                                    if (isGlobal) {
                                        cloud = $('.xagio-keyword-cloud-global').find('.cloud.template.seen.jqcloud').find('.highlightWordInCloud');
                                    } else {
                                        cloud = group.find('.cloud.template.seen.jqcloud').find('.highlightWordInCloud');
                                    }


                                    // Loop through each highlighted word in the cloud to update the seed form
                                    cloud.each(function (i) {
                                        const t = $(this).text().trim();


                                        if (i > 0) {
                                            let template = $(".seed_group_container_template.xagio-hidden").clone().removeClass('xagio-hidden');
                                            $("#seed_group_container").append(template);
                                        }

                                        forms.find('[name="seed_group_name[]"]').eq(i).val(t);
                                        forms.find('[name="seed_keywords[]"]').eq(i).val(t);
                                    });


                                    // For each keyword in this group, wrap matching occurrences of the clicked word with <b>
                                    group.find('.keywordInput[data-target="keyword"]').each(function () {
                                        const $kwElem = $(this);
                                        let html = $kwElem.html();
                                        let newHtml = html.replace(
                                            new RegExp(`\\b(${actions.escapeRegExp(word)})\\b`, "gi"),
                                            '<b class="highlightCloud">$1</b>'
                                        );
                                        $kwElem.html(newHtml);
                                        const tr = $kwElem.closest(".ui-sortable-handle");
                                        if (newHtml.indexOf('<b class="highlightCloud">') !== -1) {
                                            tr.find("input.keyword-selection").prop('checked', true);
                                            tr.addClass("selected");
                                        }
                                    });
                                }
                            });

                            // Update the text of the keywords-action-button based on the count of highlighted keywords
                            let cloud;
                            if (isGlobal) {
                                lastSeedGroupId = 0;
                                cloud = $('.xagio-keyword-cloud-global').find('.cloud.template.seen.jqcloud').find('.highlightWordInCloud');
                            } else {
                                cloud = $clicked.parents('.cloud.template.seen.jqcloud').find('.highlightWordInCloud');
                            }
                            let selectedKeywordsCount = cloud.length;
                            if (selectedKeywordsCount > 0) {
                                $('.seedKeyword').html(`Seed Keywords <span class="seed_keywords_selected">(${selectedKeywordsCount})</span>`);
                                $('.keywords-action-button').html(`Keywords<span class="seed_keywords_selected">Seed (${selectedKeywordsCount})</span> <i class="xagio-icon xagio-icon-arrow-down"></i>`);
                            } else {
                                $('.seedKeyword').html('Seed Keywords');
                                $('.keywords-action-button').html('Keywords <i class="xagio-icon xagio-icon-arrow-down"></i>');
                            }


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
        parseUrl             : function (url) {
            return $('<a>', {
                href: url
            });
        },
        parseNumber          : function (num) {
            if (num === null || num === "") {
                return '';
            } else {
                if (typeof num === 'string') {
                    num = num.replaceAll(',', '');
                }
                return parseInt(num).toLocaleString();
            }
        },
        selectAllKeywords    : function () {
            $(document).on('click', '.select-all', function () {
                let table = $(this).parents('table.keywords');
                table.find('.keyword-selection').each(function () {
                    $(this).prop("checked", !$(this).prop("checked"));
                });
            });
        },

        newGroup                                 : function () {
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
        refreshXags                              : function () {
            $.post(xagio_data.wp_post, 'action=xagio_refreshXags', function (d) {

                if (d.status == 'error') {

                    actions.allowances.xags.find('.value').html(0);
                    actions.allowances.xags_allowance.find('.value').html(0);

                } else {

                    actions.allowances.xags_allowance.find('.value').html(parseFloat(d.data.xags_allowance).toFixed(2));

                    if (d.data['xags'] > 0) {
                        actions.allowances.xags.find('.value').html(parseFloat(d.data.xags).toFixed(2));
                    } else {
                        actions.allowances.xags.find('.value').html("0.00");
                    }
                    actions.allowances.cost = d.data.xags_cost;
                    actions.allowances.xags_total = d.data.xags_total;

                    actions.allowances.xags_sum = d.data.xags + d.data.xags_allowance;

                    // Store competitionCost from xags_cost.comp (as float)
                    GLOBAL_PRICING_DATA.competitionCost = parseFloat(d.data.xags_cost.comp);

                    let template_credits_el = $('.template-credits-holder');
                    if (d.data.template_bonus != 999) {
                        // Show template credits
                        template_credits_el.html(`<button class="credits monthly" data-xagio-tooltip data-xagio-title="This is the number of monthly Templates you have left on your account. These claims expire and are refreshed with a new number of claims each month, depending on your account type.">
                                    <span class="credits-value">${d.data.template_monthly}</span> Monthly Claims
                                </button>
                                <button class="credits permanent" data-xagio-tooltip data-xagio-title="This is the number of xTemplates you have on your account. These claims do not expire and you can use them anytime you see fit!">
                                    <span class="credits-value">${d.data.template_bonus}</span> Bonus Claims
                                </button>
                                <a href="https://xagio.com/store/" target="_blank" class="xagio-button xagio-button-secondary"><i class="xagio-icon xagio-icon-store"></i> PURCHASE TEMPLATES</a>`);
                    } else {
                        // Show unlimited
                        template_credits_el.html(`<button class="credits monthly" data-xagio-tooltip data-xagio-title="You can claim all current and future templates free of charge!">
                                    Unlimited
                                </button>
                                <span class="credits-value hidden">999</span>`);
                    }
                }
            });
        },
        decodeHtml                               : function (html) {
            var txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        },
        prepareURL                               : function (url) {
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
        cleanComma                               : function (num) {
            if (typeof num === 'string') {
                num = num.replaceAll(',', '');
            }

            return num;
        },
        updateGrid                               : function () {
            $grid.masonry('reloadItems');
            $grid.masonry('layout');
            actions.calculateCosts();
        },
        updateElements                           : function () {
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
                                      items        : "tr",
                                      selectedClass: "selected",
                                      stop         : function (e) {
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
                                 stop       : function () {
                                     // Update tablesorter on both source and target tables
                                     $(".keywords").trigger("update");
                                 }
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
        newKeyword                               : function () {
            $(document).on('click', '.add-keywords', function (e) {
                e.preventDefault();

                let keywords = $('#keywords-input').val();

                if (keywords == '') {
                    xagioNotify("danger", "You must insert some keywords first.");
                    return;
                }

                $.post(xagio_data.wp_post, 'action=xagio_addKeyword&group_id=' + keywordGroupID + '&keywords=' +
                                           encodeURIComponent(keywords), function (d) {

                    $("#addKeywords")[0].close();
                    xagioNotify("success", "Successfully added keywords.");
                    actions.loadProjectManually();
                    actions.calculateCosts();

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
        expandGroupWordCount                     : function () {

            $('.wordCloud').each(function () {
                let btn = $(this);

                // if (btn.hasClass('open')) return;

                let cloudBoxTemplate = $('.cloud.template.hide').clone();
                cloudBoxTemplate.removeClass('hide').show().addClass('seen');

                let cloudKeyword = btn.parents('.xagio-group').find('.xagio-keyword-cloud');

                if (!cloudKeyword.hasClass("generated")) {
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
                            height    : 180,
                            fontSize  : {
                                from: 0.1,
                                to  : 0.03
                            }
                        });

                        // actions.updateGrid();
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
        wordCountCloud                           : function () {

            $(document).on('click', '.global-wordCloud', function (e) {
                e.preventDefault();

                let cloudBoxTemplate = $('.cloud.template.hide').clone();
                cloudBoxTemplate.removeClass('hide').show().addClass('seen');

                let btn = $(this);
                let cloudKeyword = $('.xagio-keyword-cloud-global');

                if (btn.hasClass('open')) {
                    $('.seed-keywords-global').hide();
                    if (cloudKeyword.hasClass('generated')) {
                        btn.removeClass('open');
                        btn.attr('data-xagio-title', 'Open Global Word Cloud');
                        btn.find('i').removeClass().addClass('xagio-icon xagio-icon-cloud');
                        $('.xagio-tooltip').remove();


                        $('.keywords-action-button').html('Keywords <i class="xagio-icon xagio-icon-arrow-down"></i>');
                        $('.seedKeyword').html('Seed Keywords');
                        for (let m = 0; m < 15; m++) {
                            // Remove b tag from keywords
                            $('.xagio-group').find('.updateKeywords').find('.keywordInput[data-target="keyword"]').each(function () {
                                $(this).html($(this).html().replace(/<b class="highlightCloud">(.+)<\/b>/gi, "$1"));
                            });
                        }
                        cloudKeyword.toggle();
                        $(".jqcloud").css("display", "block").resize();
                    }

                } else {

                    $('.seed-keywords-global').show();
                    if (!cloudKeyword.hasClass("generated")) {
                        let tbody_keywords = $('.xagio-group').find('.updateKeywords').find('.keywords').find('.keywords-data tr').find('div.keywordInput[data-target="keyword"]');
                        let keywords = [];
                        tbody_keywords.each(function () {
                            keywords.push($(this).text());
                        });

                        if (keywords.length > 0) {
                            cloudKeyword.html(cloudBoxTemplate);
                            cloudBoxTemplate.jQCloud(actions.calculateAndTrim(keywords), {
                                colors    : [
                                    "#ffffff",
                                    "#FAF9F6",
                                    "#F1F0ED",
                                    "#E5E4E2",
                                    "#D9D8D6"
                                ],
                                autoResize: true,
                                height    : 300,
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

            $(document).on('click', '.wordCloud', function () {

                let cloudBoxTemplate = $('.cloud.template.hide').clone();
                cloudBoxTemplate.removeClass('hide').show().addClass('seen');

                let btn = $(this);
                let cloudKeyword = btn.parents('.xagio-group').find('.xagio-keyword-cloud');

                if (btn.hasClass('open')) {
                    if (cloudKeyword.hasClass('generated')) {
                        btn.removeClass('open');
                        btn.attr('data-xagio-title', 'Open Word Cloud');
                        btn.find('i').removeClass().addClass('xagio-icon xagio-icon-cloud');
                        $('.xagio-tooltip').remove();

                        $('.keywords-action-button').html('Keywords <i class="xagio-icon xagio-icon-arrow-down"></i>');
                        $('.seedKeyword').html('Seed Keywords');
                        for (let m = 0; m < 15; m++) {
                            // Remove b tag from keywords
                            btn.parents('.xagio-group').find('.updateKeywords').find('.keywordInput[data-target="keyword"]').each(function () {
                                $(this).html($(this).html().replace(/<b class="highlightCloud">(.+)<\/b>/gi, "$1"));
                            });
                        }
                        cloudKeyword.toggle();
                        $(".jqcloud").css("display", "block").resize();
                    }

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
                                height    : 180,
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
        deleteGroup                              : function () {
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


                $.post(xagio_data.wp_post, 'action=xagio_deleteGroup&group_id=' + group_id +
                                           '&deleteRanks=0', function (d) {
                    modal[0].close();
                    actions.loadProjectManually();
                    xagioNotify("success", "Group successfully deleted.");

                    actions.calculateCosts();
                })
            });

            $('#deleteGroup')[0].addEventListener("close", (event) => {
                let modal = $(event.target);
                modal.find('#groupId').val(0);
                modal.find('#deleteGroupRanks').val(0).prop('checked', false);
            });
        },
        loadProjectManually                      : function () {
            if (currentProjectID == 0) return;
            $('.xagio-header-actions-in-project').show();
            $('.xagio-header-actions').hide();
            $('.xagio-keyword-cloud').removeClass('generated');
            // $('.xagio-group-button').removeClass('open');

            let project_groups = $('.project-groups');
            let project_empty = $('.project-empty');
            let data = project_groups.find('.data');

            data.empty();
            data.append('<div class="loading-project">Loading Project... Please wait...</div>');

            let project_dashboard = $('.project-dashboard');

            $.post(xagio_data.wp_post, 'action=xagio_get_project_info&project_id=' + currentProjectID, function (d) {
                project_dashboard.find('.project-name').html("<i class='xagio-icon xagio-icon-file'></i> #" +
                                                             d.data.id + ": " + d.data.name);
            });

            $.post(xagio_data.wp_post, 'action=xagio_getGroups&project_id=' + currentProjectID +
                                       '&post_type=', function (d) {

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

                        // Deselect if selectedGroups are empty
                        if (selectedGroups.length > 0) {
                            if (!selectedGroups.includes(row.id)) {
                                template.find('.groupSelect').prop('checked', false);
                            }
                        }

                        // Append the Group ID
                        template.find('[name="group_id"]').val(row.id);
                        if (homepage_group == row.id) {
                            template.find('.setHome').addClass('xagio-group-button-orange');
                        }
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
                        let count_seo_title, count_seo_title_mobile, count_seo_description,
                            count_seo_description_mobile = 0;

                        if (row.title != null) {
                            count_seo_title = row.title.length;
                            count_seo_title_mobile = row.title.length;
                        }

                        if (count_seo_title > 70) {
                            count_seo_title = `<span class="xagio-seo-count-danger">${count_seo_title}</span>`;
                        }
                        if (count_seo_title_mobile > 78) {
                            count_seo_title_mobile = `<span class="xagio-seo-count-danger">${count_seo_title_mobile}</span>`;
                        }

                        if (row.description != null) {
                            count_seo_description = row.description.length;
                            count_seo_description_mobile = row.description.length;
                        }

                        if (count_seo_description > 300) {
                            count_seo_description = `<span class="xagio-seo-count-danger">${count_seo_description}</span>`;
                        }
                        if (count_seo_description_mobile > 120) {
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
                                if (keyword.intitle == 0 && keyword.intitle !== "") {
                                    title_ratio = "0";
                                } else if (keyword.volume != "" && keyword.intitle != "") {
                                    if (keyword.volume != 0) {
                                        title_ratio = keyword.intitle / keyword.volume;
                                    }
                                }

                                let url_ratio = "";
                                if (keyword.inurl == 0 && keyword.inurl !== "") {
                                    url_ratio = "0";
                                } else if (keyword.volume !== "" && keyword.inurl !== "") {
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


                actions.expandGroupWordCount();
                actions.updateElements();
                actions.updateGrid();

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
        setDefaultAiWizardSearchEngine           : function () {
            let engineSelect = $("#top_ten_search_engine");
            let value = engineSelect.data('default');

            if (value) {
                $('#top_ten_search_engine option').removeAttr('selected');
                $(`#top_ten_search_engine option[value=${value}]`).attr('selected', true);
            }
        },
        setDefaultAiWizardLocation               : function () {
            let engineSelect = $("#top_ten_search_location");
            let value = engineSelect.data('default');

            if (value) {
                $('#top_ten_search_location option').removeAttr('selected');
                $(`#top_ten_search_location option[value=${value}]`).attr('selected', true);
            }
        },
        useTemplate                              : function () {
            $(document).on('change', '#XAGIO_USE_TEMPLATE', function () {
                let input = $(this);
                let templates_holder = $('#templates');
                let search_templates = $('.search-templates');
                let xagio_gutenberg_filter = $('.xagio-gutenberg-filter');

                if (input.val() == 1) {
                    search_templates.show();
                    xagio_gutenberg_filter.show();
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

                                // New server response: elementor always exists (base row), gutenberg is optional
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
                                box_clone.find('.template-name').html(template.name).attr('data-xagio-tooltip', '').attr('data-xagio-title', template.name);
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

                                // Button: always use Elementor id for claiming
                                box_clone.find('.template-action-button')
                                    .attr('data-template', template.key)
                                    .attr('data-id', template.id)
                                    .attr('data-claimed', template.claimed ? 1 : 0)
                                    .html(template.claimed ? "Select" : "Claim")
                                    .addClass(template.claimed ? "btn-blue download-template" : "btn-orange claim-template");
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
                            const search = ($(this).val() || '').toLowerCase();
                            const gutenbergOnly = ($('#xagio_show_gutenberg').val() === '1');

                            filteredTemplates = templates.filter(function (template) {
                                const matchesSearch = !search || (template.name || '').toLowerCase().includes(search);

                                const gbId = parseInt(template.gutenberg_id || 0, 10);
                                const hasGutenberg = (template.has_gutenberg === true || template.has_gutenberg === 1 || template.has_gutenberg === '1' || gbId > 0);

                                return matchesSearch && (!gutenbergOnly || hasGutenberg);
                            });

                            currentPage = 1;
                            renderPage(currentPage, filteredTemplates);
                            renderPagination(filteredTemplates);

                            $("#no-templates").toggle(filteredTemplates.length === 0);
                        });
                        // --- END SEARCH HANDLER ---

                        $(document).on('click', '.xagio-slider-button[data-element="xagio_show_gutenberg"]', function () {
                            // toggle hidden input 0/1
                            const $input = $('#xagio_show_gutenberg');
                            const nextVal = ($input.val() === '1') ? '1' : '0';

                            const search = ($('.search').val() || '').toLowerCase();
                            const gutenbergOnly = (nextVal === '1');

                            filteredTemplates = templates.filter(function (template) {
                                const matchesSearch = !search || (template.name || '').toLowerCase().includes(search);

                                // treat as "has gutenberg" if either flag is set OR gutenberg_id exists
                                const gbId = parseInt(template.gutenberg_id || 0, 10);
                                const hasGutenberg = (template.has_gutenberg === true || template.has_gutenberg === 1 || template.has_gutenberg === '1' || gbId > 0);

                                return matchesSearch && (!gutenbergOnly || hasGutenberg);
                            });

                            currentPage = 1;
                            renderPage(currentPage, filteredTemplates);
                            renderPagination(filteredTemplates);

                            $("#no-templates").toggle(filteredTemplates.length === 0);
                        });
                    }
                } else {
                    search_templates.hide();
                    xagio_gutenberg_filter.hide();
                    templates_holder.html('');
                    $('#pagination').empty();
                }
            });

            $(document).on('click', '.template-action-button.download-template', function (e) {
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
                    // remove any existing leading check icon
                    $b.find('.xagio-icon.xagio-icon-check').remove();

                    // visual 'active' state cleanup
                    $b.removeClass('active');
                });

                // ensure a single leading check icon on the selected button
                if ($btn.find('.xagio-icon.xagio-icon-check').length === 0) {
                    $btn.prepend('<i class="xagio-icon xagio-icon-check"></i> ');
                }
                $btn.addClass('active');
            });

            $(document).on('click', '.claim-template', function (e) {
                e.preventDefault();
                $('.box-template').removeClass('selected');
                let btn = $(this);
                let template_id = btn.data('id');
                let template = btn.parents('.box-template');

                btn.disable();


                $.post(xagio_data.wp_post, `action=xagio_ocw_claim_template&template_id=${template_id}`, function (d) {
                    btn.disable();

                    actions.refreshXags();

                    if (d.status === 'success') {
                        template.attr('data-claimed', true);
                        btn.attr('data-claimed', true).removeClass('btn-orange claim-template').addClass('btn-blue download-template').html('Select');
                        xagioNotify('success', d.message);
                    } else {
                        xagioNotify('error', d.message);
                    }
                });


            });
        },
        loadTemplates                            : function () {
            $.post(xagio_data.wp_post, 'action=xagio_ocw_get_templates', function (d) {
                templates = d.data;
            });
        },
        loadSteps                                : function (run_wizard = false) {
            let additional_arg = '';
            if (run_wizard) {
                additional_arg += '&run_wizard=1'
                $.post(xagio_data.wp_post, 'action=xagio_ocw_check_statuses');
            }
            $.post(xagio_data.wp_post, 'action=xagio_ocw_get_steps' + additional_arg, function (d) {
                let steps = d.data;
                global_steps = d.data;
                let info = d.data.data;

                let projectId = info.project_id || ' - ';

                let editor    = (info.editor_type || '').toString().toLowerCase();
                editor = editor ? (editor.charAt(0).toUpperCase() + editor.slice(1)) : '-';

                // "sauna-contractors" -> "Sauna Contractors"
                let templateName = (info.template_key || '').toString().replace(/[-_]+/g, ' ').replace(/\b\w/g, function(m){ return m.toUpperCase(); });

                let installed = (parseInt(info.templates, 10) > 0) ? 'Yes' : 'No';
                let templateInstalledName = '';
                if(installed === 'Yes') {
                    templateInstalledName = '<div>' + (templateName || '-') + ' <span class="template-platform gutenberg">' + editor + '</span></div><div><strong>Continue to generate pages</strong></div>';
                }

                let html = ''
                    + '<div class="xagio-ocw-summary">'
                    +   '<div>Loaded project #ID: <strong>' + projectId + '</strong></div>'
                    +   '<div>Using Template: <strong>' + installed + '</strong></div>'
                    +   templateInstalledName
                    + '</div>';

                $('.xagio-ocw-steps-info').html(html);



                if (steps.data.hasOwnProperty('homepage_group')) {
                    homepage_group = steps.data.homepage_group;
                }

                if (steps.step == 'keyword_research') {

                    let step_start = $('.ocw-start');
                    let step_images = $('.ocw-step-1');

                    step_start.fadeOut(function () {
                        step_images.fadeIn();
                    });

                } else if (steps.step == 'project_created') {

                    currentProjectID = steps.data.project_id;

                    let step_1 = $('.ocw-start');
                    let step_2 = $('.ocw-step-templates');

                    actions.checkAIClusterStatus();


                    step_1.fadeOut(function () {
                        // actions.loadProjectManually();
                        // actions.getSavedKeywordSettingsLanguageAndCountry();
                        // actions.setDefaultAiWizardSearchEngine();
                        // actions.setDefaultAiWizardLocation();

                        step_2.fadeIn(function () {
                            // actions.updateGrid();
                        });
                    });

                } else if (steps.step == 'running_wizard') {

                    if (steps.data.progress == 1) {
                        $.post(xagio_data.wp_post, `action=xagio_checkBatchCron`);
                    }

                    let step_1 = $('.ocw-start');
                    let step_4 = $('.ocw-step-4');

                    let item = $('.xagio-ocw-progress-item');

                    for (let i = 0; i < (steps.data.progress - 1); i++) {

                        item.eq(i).addClass('finished');
                        item.eq(i).find('.xagio-icon').addClass('xagio-icon-check').removeClass('xagio-icon-refresh xagio-icon-spin');

                    }

                    item.eq(steps.data.progress - 1).addClass('running');

                    $('.xagio-ocw-progress-item:not(.finished)').find('.xagio-icon').addClass('xagio-icon-refresh xagio-icon-spin');

                    step_1.fadeOut(function () {
                        step_4.fadeIn();
                    });

                    setTimeout(function () {

                        actions.loadSteps(true);

                    }, 10000);

                } else if (steps.step == 'wizard_finished') {

                    let step_1 = $('.ocw-start');
                    let step_4 = $('.ocw-step-4');
                    let step_5 = $('.ocw-step-finish');

                    if (step_1.is(':visible')) {
                        step_1.fadeOut(function () {
                            step_5.fadeIn();
                        });
                    } else {
                        step_4.fadeOut(function () {
                            step_5.fadeIn();
                        });
                    }

                }
            });
        },
        calculateKeywordWeight                   : function (t) {
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
        auditWebsite                             : function (type, domains) {
            let generating_el = $('.generating-project-loading');
            let next_btn = generating_el.parents('#step-2').find('.next-step');
            let back_btn = generating_el.parents('#step-2').find('.prev-step');
            let step_2 = $('.ocw-step-2');
            project_ids = [];

            back_btn.hide();
            next_btn.disable('Working...');
            step_2.find('a.create-project').hide();
            step_2.find('.ai-wizard-cost-label').hide();
            step_2.find('a.prev-step').disable();
            $('.top-ten-options').hide();

            let lang_code = step_2.find('#top-ten-language').val();
            let lang = step_2.find('#top-ten-language-select option:selected').val();

            let keyword_contain = $('#keyword_contain').val();
            let keyword_contain_text = $('.main_keyword_contain').val();
            let is_relative = '0';
            let agent_type = $('#aiwizard-type').val();

            requestsRemaining = domains.length * 2; // Counter for remaining requests
            domains_length = domains.length;

            generating_el.html(`Finding & Clustering Your Keywords...`);

            domains.forEach(function (domain) {

                $.ajaxq('ProjectQueue', {
                    type    : 'POST',
                    url     : xagio_data.wp_post,
                    data    : `action=xagio_generate_audit&name=Agent X&type=${type}&website=${domain}&lang_code=${lang_code}&lang=${lang}&keyword_contain=${keyword_contain}&keyword_contain_text=${keyword_contain_text}&is_relative=${is_relative}&agent_type=${agent_type}`,
                    success : function (d) {
                        if (d.status === 'credits') {
                            $.ajaxq.clear('ProjectQueue');
                            generating_el.html(d.message);
                            xagioNotify("warning", d.message, false, 15);
                            setTimeout(function () {
                                document.location.reload();
                            }, 5000);
                        } else {
                            if (d.hasOwnProperty('project_id')) {
                                generating_el.html(`Processing... <br><span class="processing-domain">${domain}</span>`);
                                actions.processProject(d.project_id);
                            } else {
                                generating_el.html(`No keywords found for... <br><span class="processing-domain">${domain}</span>`);
                                requestsRemaining--; // Decrement the counter on each completion
                            }
                        }
                    },
                    complete: actions.completeRequests
                });
            });
        },
        processProject                           : function (project_id) {
            $.ajaxq('ProjectQueue', {
                type    : 'POST',
                url     : xagio_data.wp_post,
                data    : `action=xagio_generate_phrasematch&project_id=${project_id}`,
                success : function (dd) {
                    project_id = dd.project_id;
                    project_ids.push(project_id);
                },
                complete: actions.completeRequests
            });
        },
        completeRequests                         : function () {
            requestsRemaining--; // Decrement the counter on each completion
            if (requestsRemaining === 0) {
                console.log('finished all');
                // If all requests are completed, redirect
                setTimeout(function () {
                    actions.finalProcessing();
                }, 15000);
            }
        },
        finalProcessing                          : function () {
            if (domains_length > 1) {
                if (project_ids.length < 1) {
                    const generatingEl = $('.top-ten-results');
                    generatingEl.html(`
        <div class="no-keywords-results">
            <h2>No Keywords Found — You have not been charged any credits.</h2>
            <p>This likely happened for one (or both) of the following reasons:</p>
            <ol>
                <li>
                    <strong>Location Matching:</strong>
                    Agent X only displays keywords that include your entered location to ensure strong local SEO.
                    Competitors aren’t ranking for any keywords that include your location — likely because the location is too small.
                </li>
                <li>
                    <strong>Niche Size:</strong>
                    Some niches are so specific or rare that even in large cities there isn’t enough search demand to find usable keywords — for example, “Roofers” has high demand, but “Clay Tile Roofers” might be too narrow.
                </li>
            </ol>
            <p><strong>Next Steps:</strong> Restart the wizard and try a bigger area or a broader niche.</p>
            <p>
                <strong>Optional:</strong>
                If you want to proceed anyway, we suggest using the <em>AI Wizard</em> for a less restrictive approach
                and launching Agent X from the Project Planner.
            </p>
        </div>
    `);
                    $('.prev-step-2')
                        .html('<i class="xagio-icon xagio-icon-refresh"></i> Reload Wizard')
                        .removeClass('prev-step-2')
                        .addClass('reload-wizard');
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
                                data   : `action=xagio_generate_phrasematch&project_id=${final_project_id}`,
                                success: function (dd) {
                                    actions.redirectToProject(dd.project_id);
                                }
                            });
                        } else {
                            xagioNotify("warning", d.message, 15);
                        }
                    }
                });
            } else {
                actions.redirectToProject(project_ids[project_ids.length - 1]);
            }
        },
        redirectToProject                        : function (project_id) {
            currentProjectID = project_id;

            let step_2 = $('.ocw-step-2');
            let step_3 = $('.ocw-step-3');


            step_2.fadeOut(function () {

                actions.loadProjectManually();
                actions.getSavedKeywordSettingsLanguageAndCountry();

                step_3.fadeIn(function () {
                    actions.refreshXags();
                    actions.updateGrid();
                });
            });


            let language = $('#getCompetition_languageCode').val();
            let location = $('#getCompetition_locationCode').val();

            $.post(xagio_data.wp_post, `action=xagio_ocw_step&step=project_created&project_id=${currentProjectID}&language=${language}&location=${location}`);
        },
        copyTextToClipboard                      : function (text) {
            if (!navigator.clipboard) {
                fallbackCopyTextToClipboard(text);
                return;
            }
            navigator.clipboard.writeText(text).then(function () {
                console.log('Async: Copying to clipboard was successful!');
            }, function (err) {
                console.error('Async: Could not copy text: ', err);
            });
        },
        uploadKit                                : function (e_import_file, kit_id) {
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
        },
        importKit                                : function (session) {
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
        },
        runImportKitRunner                       : function (session, runner) {
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
        },
        startImportProcess: function () {
            let file = elementorTemplateZip;
            let elementor_output = $('#elementor-output');
            elementor_output.append('<p><i class="xagio-icon xagio-icon-history"></i> Uploading kit file...</p>');

            // helper to detect the specific error
            function isThirdPartyError(err) {
                try {
                    if (err && err.responseJSON && err.responseJSON.data === 'third-party-error') return true;
                    if (err && err.responseText) {
                        const j = JSON.parse(err.responseText);
                        if (j && j.data === 'third-party-error') return true;
                    }
                    if (typeof err === 'string' && err.indexOf('third-party-error') !== -1) return true;
                } catch (e) {}
                return false;
            }

            actions.uploadKit(file, undefined)
                   .then(function (uploadResponse) {
                       var session = uploadResponse.data.session;
                       elementor_output.append('<p><i class="xagio-icon xagio-icon-check"></i> Upload completed.</p>');
                       elementor_output.append('<p><i class="xagio-icon xagio-icon-history"></i> Starting kit import...</p>');
                       return actions.importKit(session).then(function () {
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
                               elementor_output.append('<p><i class="xagio-icon xagio-icon-history"></i> Importing: ' + runner + '...</p>');

                               // IMPORTANT: handle the specific error and continue
                               return actions.runImportKitRunner(session, runner).then(
                                   function () {
                                       elementor_output.append('<p><i class="xagio-icon xagio-icon-check"></i> Import of "' + runner + '" completed.</p>');
                                   },
                                   function (err) {
                                       if (isThirdPartyError(err)) {
                                           elementor_output.append(
                                               '<p><i class="xagio-icon xagio-icon-warning"></i> "' + runner +
                                               '" import not necessary. Skipping and continuing…</p>'
                                           );
                                           // swallow the error so the chain continues
                                           return $.Deferred().resolve().promise();
                                       }
                                       // propagate any other error to stop the chain
                                       return $.Deferred().reject(err).promise();
                                   }
                               );
                           });
                       });

                       return chain;
                   })
                   .done(function () {
                       elementor_output.append('<p><i class="xagio-icon xagio-icon-check"></i> Elementor kit import process complete.</p>');
                       $('.ocw-step-elementor').fadeOut(function () {
                           $('.ocw-step-profiles').fadeIn();
                       });
                   })
                   .fail(function (error) {
                       elementor_output.append('<p style="color:red;">Error: ' + JSON.stringify(error) + '</p>');
                   });
        },
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

        checkAndInstallElementor                 : function () {
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
                                          _wpnonce: xagio_data._wpnonce
                                      }
                                  });

                } else {
                    return $.Deferred().reject(response.data.error || 'Unknown error installing Elementor.');
                }
            });
        },
        getAveragePrices                         : function () {
            $.post(xagio_data.wp_post, `action=xagio_ai_get_average_prices`, function (d) {

                if (d.status == 'error') {
                    return;
                }

                // The response data structure
                const avgPrices = d.data.average_prices;

                // Extract Optimize AI cost from SEO_SUGGESTIONS_MAIN_KW with id:6
                if (avgPrices.SEO_SUGGESTIONS_MAIN_KW && Array.isArray(avgPrices.SEO_SUGGESTIONS_MAIN_KW)) {
                    avgPrices.SEO_SUGGESTIONS_MAIN_KW.forEach(item => {
                        if (item.id === 6) {
                            GLOBAL_PRICING_DATA.optimizeAiCost = parseFloat(item.price);
                        }
                    });
                }

                // Extract Generate AI Content cost:
                // Look into PAGE_CONTENT_TEMPLATE with id:11,
                // if not found then check PAGE_CONTENT for id:2.
                let contentFound = false;
                if (avgPrices.PAGE_CONTENT_TEMPLATE && Array.isArray(avgPrices.PAGE_CONTENT_TEMPLATE)) {
                    avgPrices.PAGE_CONTENT_TEMPLATE.forEach(item => {
                        if (item.id === 11) {
                            GLOBAL_PRICING_DATA.generateAiContentCost = parseFloat(item.price);
                            contentFound = true;
                        }
                    });
                }
                if (!contentFound && avgPrices.PAGE_CONTENT && Array.isArray(avgPrices.PAGE_CONTENT)) {
                    avgPrices.PAGE_CONTENT.forEach(item => {
                        if (item.id === 2) {
                            GLOBAL_PRICING_DATA.generateAiContentCost = parseFloat(item.price);
                        }
                    });
                }

                // Extract Generate AI Schema cost from SCHEMA with id:10
                if (avgPrices.SCHEMA && Array.isArray(avgPrices.SCHEMA)) {
                    avgPrices.SCHEMA.forEach(item => {
                        if (item.id === 10) {
                            GLOBAL_PRICING_DATA.generateAiSchemaCost = parseFloat(item.price);
                        }
                    });
                }

                // Extract Image Edit
                if (avgPrices.IMAGE_EDIT && Array.isArray(avgPrices.IMAGE_EDIT)) {
                    avgPrices.IMAGE_EDIT.forEach(item => {
                        if (item.id === 12) {
                            GLOBAL_PRICING_DATA.imagesEditAI = parseFloat(item.price);
                        }
                    });
                }

                // Extract Image Gen
                if (avgPrices.IMAGE_GEN && Array.isArray(avgPrices.IMAGE_GEN)) {
                    avgPrices.IMAGE_GEN.forEach(item => {
                        if (item.id === 13) {
                            GLOBAL_PRICING_DATA.imagesGenerateAI = parseFloat(item.price);
                        }
                    });
                }

                actions.calculateCosts();

            });
        },
        calculateCosts                           : function () {

            let keywordCount = 0;
            let groupCount = 0;
            $('.project-groups .groupSelect:checked').each(function () {
                let group = $(this).parents('.xagio-group');
                let keywords = group.find('.keywordInput');
                groupCount++;
                keywordCount += keywords.length;
            });

            // Competition Credits cost applied per keyword
            const competitionTotal = GLOBAL_PRICING_DATA.competitionCost * keywordCount;
            // Optimize AI cost is applied per group
            const optimizeAITotal = GLOBAL_PRICING_DATA.optimizeAiCost * groupCount;
            // Generate AI Content cost is applied per group
            const generateAIContentTotal = GLOBAL_PRICING_DATA.generateAiContentCost * groupCount;
            // Generate AI Schema cost is applied per group
            const generateAISchemaTotal = GLOBAL_PRICING_DATA.generateAiSchemaCost * groupCount;

            let total = competitionTotal + optimizeAITotal + generateAIContentTotal + generateAISchemaTotal;
            total = total.toFixed(2);

            $('.calculated-prices').html(`Total XAGS cost: <span class="calculated-wizard-cost">${total}</span>`);
            return competitionTotal + optimizeAITotal + generateAIContentTotal + generateAISchemaTotal;
        },
        /**
         * Poll “checkClusterStatus” until the job is finished.
         * @param {number} projectId
         */
        pollClusterStatus                        : function (projectId) {
            const MAX_ATTEMPTS  = 60;   // 60 × 5 s = 5 min timeout
            const INTERVAL_MS   = 5000; // 5 s
            let   attempts      = 0;

            const timer = setInterval( () => {

                $.post( xagio_data.wp_post, {
                    action       : 'xagio_ai_check_status_cluster',
                    project_id   : projectId
                }, function ( resp ) {

                    // resp example: {status:"success", message:"Image Status retrieved!", data:"completed"}
                    if ( resp.status === 'success' ) {

                        const status = resp.data; // "running" | "queued" | "completed" | false

                        if ( status === 'completed' || status === false ) {
                            clearInterval( timer );
                            actions.enableAiButtons();
                            xagioNotify( 'success', 'AI clustering finished – reloading…', true );

                            actions.loadProjectManually(  );
                        }

                    } else if ( resp.status === 'error' ) {
                        clearInterval( timer );
                        actions.enableAiButtons();
                        xagioNotify( 'danger', resp.message || 'Error checking AI-cluster status', true );
                    }
                }, 'json' );

                if ( ++attempts >= MAX_ATTEMPTS ) {
                    clearInterval( timer );
                    actions.enableAiButtons();
                    xagioNotify( 'warning', 'AI clustering is still running; please try again later.', true );
                }

            }, INTERVAL_MS );
        },
        enableAiButtons                          : function () {
            $('.start-wizard, .reset-wizard, .ai-clustering')
                .each(function () {
                    // works with your custom .enable() plugin if present,
                    // otherwise fall back to a plain prop().
                    if ( $.fn.disable ) {
                        $(this).disable();
                    } else {
                        $(this).prop('disabled', false);
                    }
                });

            $('.loading').addClass('hidden');
        },
        aiClustering                             : function () {
            $(document).on('click', '.ai-clustering', function (e) {
                e.preventDefault();
                let btn = $(this);

                if ($('.project-groups .groupSelect:checked').length < 1) {
                    xagioNotify('danger', 'Please select some groups before');
                    return;
                }

                actions.openAveragePrices(btn, 'AI Clustering', "CLUSTER", "generateAiContent");

            });

            $(document).on('click', '.makeAiRequest', function (e) {
                e.preventDefault();

                let price = parseInt($('#aiPrice').find('.average-price').html());
                let credits = parseInt($('#aiPrice').find('.ai-credits').html());

                if (credits < price) {
                    xagioNotify("danger", "You do not have enough AI Credits, please top up and try again!");
                    return;
                }

                $('#aiPrice')[0].close();
                $('.start-wizard').disable();
                $('.reset-wizard').disable();
                $('.ai-clustering').disable();
                $('.loading').toggleClass('hidden');

                let all_groups = $('.project-groups .groupSelect:checked');

                let data = [
                    {
                        name : 'action',
                        value: 'xagio_ai_clustering'
                    },
                    {
                        name : 'project_id',
                        value: currentProjectID
                    }
                ];

                // Push each keyword as a separate field named "keywords[]"
                all_groups.each(function () {
                    let all_keywords = $(this).parents('.xagio-group').find('.keywordInput[data-target="keyword"]');
                    all_keywords.each(function () {
                        let val = jQuery(this).text().trim();
                        if (val.length > 0) {
                            data.push({
                                          name : 'keywords[]',
                                          value: val
                                      });
                        }
                    });
                });

                $.post(xagio_data.wp_post, data, function (d) {

                    if (d.status == 'upgrade') {
                        $('#aiUpgrade')[0].showModal();
                        return;
                    }

                    xagioNotify(d.status, d.message, true);

                    if (d.status == 'error') {
                        btn.disable();
                        return;
                    }

                    actions.pollClusterStatus( currentProjectID );
                });

            });
        },
        openAveragePrices                        : function (btn, title, input) {
            btn.disable();

            $.post(xagio_data.wp_post, `action=xagio_ai_get_average_prices`, function (d) {
                btn.disable();

                if (d.status == 'error') {
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

                $('#aiPrice')[0].showModal();

                actions.refreshXags();

            });
        },
        loadProfiles: function () {
            $.get(xagio_data.wp_post, `action=xagio_load_profiles`, function (d) {
                if (d.data) {
                    $.each(d.data, function (category, item) {
                        $.each(item, function (key, value) {
                            $(`input[name="XAGIO_SEO_PROFILES[${category}][${key}]"]`).val(value);
                        });
                    });
                }
            });
        },
        saveProfiles: function () {
            function debounce(func, wait) {
                let timeout;
                return function (...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            let debouncedSave = debounce(function () {
                let data = [
                    {
                        name : 'action',
                        value: 'xagio_save_profiles'
                    }
                ];

                $('.profiles_input').each(function () {
                    data.push({
                        name : $(this).attr('name'),
                        value: $(this).val()
                    });
                });

                $.post(xagio_data.wp_post, data, function (d) {
                    setTimeout(function () {
                        xagioNotify(d.status, d.message);
                    }, 500);
                });
            }, 350);

            $(document).on('input', '.profiles_input', function (e) {
                debouncedSave($(this));
            });
        },
        showProfiles: function () {
            $(document).on('change', '#XAGIO_PROFILE_DATA', function (e) {
                let val = $(this).val();
                let profiles_holder = $('#profiles-holder');

                if (val === "1") {
                    profiles_holder.show();
                } else {
                    profiles_holder.hide();
                }
            })
        }
    };

    $(document).ready(function () {

        actions.refreshXags();
        actions.getAveragePrices();
        actions.loadSteps();
        actions.loadTemplates();
        actions.loadProjectEvents();
        actions.wizardEvents();
        actions.trackRankings();
        actions.wordCountCloud();
        actions.deleteGroup();
        actions.useTemplate();
        actions.deleteKeywords();
        actions.copyKeywords();
        actions.seedKeyword();
        actions.phraseMatch();
        actions.previewCluster();
        actions.deleteGroups();
        actions.consolidateKeywords();
        actions.selectAllGroups();
        actions.deselectAllGroups();
        actions.newKeyword();
        actions.saveLabelOnInput();
        actions.newGroup();
        actions.selectAllKeywords();
        actions.remoteCheckStatuses();
        actions.aiClustering();
        actions.loadProfiles();
        actions.saveProfiles();
        actions.showProfiles();

    });


})(jQuery);