<?php
/**
 * Type: SUBMENU
 * Page_Title: AI Settings
 * Menu_Title: AI Settings
 * Capability: manage_options
 * Slug: xagio-aisettings
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: xagio_aisettings
 * Css: xagio_animate,xagio_settings,xagio_aisettings
 * Position: 4
 * Version: 1.0.0
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

$XAGIO_MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');
?>

<div class="xagio-main-header xagio-main-header-big-gaps">
    <img class="logo-image repo-xagio" src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        AI Settings
    </h2>

    <?php if (isset($XAGIO_MEMBERSHIP_INFO["membership"]) && $XAGIO_MEMBERSHIP_INFO["membership"] === "Xagio AI Free") { ?>
        <div class="xagio-header-actions">
            <a href="https://xagio.com/?goto=wppremfeatures" target="_blank"
               class="xagio-button xagio-button-secondary xagio-button-premium-button">
                See Xagio Premium Features
            </a>
        </div>
    <?php } ?>
</div>

<!-- HTML STARTS HERE -->
<div class="xagio-content-wrapper">

    <div class="xagio-accordion xagio-margin-bottom-large">
        <h3 class="xagio-accordion-title">
            <i class="xagio-icon xagio-icon-info"></i>
            <span>From here you can manage all the AI related settings that Xagio applies globally across your website.</span>
            <i class="xagio-icon xagio-icon-arrow-down"></i>
        </h3>
        <div class="xagio-accordion-content">
            <div>
                <div class="xagio-accordion-panel"></div>
            </div>
        </div>
    </div>

    <ul class="xagio-tab">
        <li class="xagio-tab-active"><a href="">LLMs.txt</a></li>
        <li><a href="">OKF</a></li>
    </ul>

    <div class="xagio-tab-content-holder">

        <!-- LLMs.txt -->
        <div class="xagio-tab-content">

            <?php
            $xagio_llms_enabled         = (string) get_option('XAGIO_LLMS_ENABLED', '0');
            $xagio_llms_intro           = (string) get_option('XAGIO_LLMS_INTRO', '');
            $xagio_llms_post_types      = (array)  get_option('XAGIO_LLMS_POST_TYPES', ['page' => 1, 'post' => 1]);
            $xagio_llms_include_sitemap = (string) get_option('XAGIO_LLMS_INCLUDE_SITEMAP', '1');
            $xagio_llms_max_items       = (int)    get_option('XAGIO_LLMS_MAX_ITEMS', 100);
            $xagio_llms_preview         = (string) get_option('XAGIO_LLMS_TXT', '');
            $xagio_llms_post_type_list  = function_exists('get_post_types') ? array_values(array_unique(array_merge(['page', 'post'], get_post_types(['public' => true, '_builtin' => false], 'names')))) : ['page', 'post'];
            $xagio_llms_disk_path       = trailingslashit(ABSPATH) . 'llms.txt';
            $xagio_llms_disk_exists     = file_exists($xagio_llms_disk_path);
            ?>

            <?php if ($xagio_llms_disk_exists) : ?>
                <div class="xagio-alert xagio-alert-danger m-b-20">
                    <i class="xagio-icon xagio-icon-warning"></i>
                    A static <strong>llms.txt</strong> file exists at <code><?php echo esc_html($xagio_llms_disk_path); ?></code>. The web server is serving that file directly, so the settings below are <strong>not</strong> being used. Click <em>Save</em> below to remove it automatically, or delete it manually.
                </div>
            <?php endif; ?>

            <form id="xagio-llms-form" class="ts xagio-panel">
                <h5 class="xagio-panel-title">LLMs.txt</h5>

                <div class="xagio-alert xagio-alert-primary m-b-20">
                    <i class="xagio-icon xagio-icon-info"></i>
                    <kbd>llms.txt</kbd> is a Markdown file served at <code>/llms.txt</code> that tells AI engines (ChatGPT, Claude, Perplexity, Gemini) what your site is about and which pages matter. Xagio generates it from your site title, tagline and the SEO data on your chosen post types.
                </div>

                <input type="hidden" name="action" value="xagio_llms_save"/>

                <div class="xagio-2-column-grid xagio-gap-large xagio-margin-bottom-large">
                    <div class="xagio-column">
                        <div class="xagio-margin-bottom-medium">
                            <h5 class="xagio-panel-title">General</h5>

                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_LLMS_ENABLED" id="XAGIO_LLMS_ENABLED"
                                       value="<?php echo $xagio_llms_enabled === '1' ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button <?php echo $xagio_llms_enabled === '1' ? 'on' : ''; ?>"
                                          data-element="XAGIO_LLMS_ENABLED"></span>
                                </div>
                                <p class="xagio-slider-label">Enable <code>/llms.txt</code>
                                    <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                       data-xagio-title="When enabled, Xagio serves /llms.txt with an AI-friendly summary of your site."></i>
                                </p>
                            </div>

                            <div class="xagio-slider-container xagio-margin-top-small">
                                <input type="hidden" name="XAGIO_LLMS_INCLUDE_SITEMAP" id="XAGIO_LLMS_INCLUDE_SITEMAP"
                                       value="<?php echo $xagio_llms_include_sitemap === '1' ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button <?php echo $xagio_llms_include_sitemap === '1' ? 'on' : ''; ?>"
                                          data-element="XAGIO_LLMS_INCLUDE_SITEMAP"></span>
                                </div>
                                <p class="xagio-slider-label">Link to the Xagio sitemap
                                    <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                       data-xagio-title="Append a Key resources section that points to /sitemap-xag.xml."></i>
                                </p>
                            </div>

                            <label for="XAGIO_LLMS_INTRO" class="xagio-margin-top-medium">
                                Intro / description
                                <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip="" data-xagio-title="Rendered as a Markdown blockquote under the site title."></i>
                            </label>

                            <textarea id="XAGIO_LLMS_INTRO" name="XAGIO_LLMS_INTRO" rows="4" class="xagio-input-textarea xagio-margin-top-small"
                                      placeholder="Leave blank to use the site tagline."><?php echo esc_textarea($xagio_llms_intro); ?></textarea>

                            <div class="xagio-margin-top-small">
                                <label for="XAGIO_LLMS_MAX_ITEMS">Max items per post type</label>
                                <input type="number" min="1" max="1000" id="XAGIO_LLMS_MAX_ITEMS" name="XAGIO_LLMS_MAX_ITEMS"
                                       class="xagio-input-text-mini xagio-margin-top-medium"
                                       value="<?php echo esc_attr($xagio_llms_max_items); ?>"/>
                            </div>
                        </div>

                        <div>
                            <h5 class="xagio-panel-title">Included post types</h5>
                            <p class="xagio-gray-label">Pick which post types Xagio should list in <code>llms.txt</code>. Each post's Xagio SEO title and description (or excerpt fallback) is used.</p>

                            <div class="xagio-3-columns xagio-margin-top-small xagio-llms-post-types">
                                <?php foreach ($xagio_llms_post_type_list as $xagio_pt):
                                    $xagio_pt_on = !empty($xagio_llms_post_types[$xagio_pt]); ?>
                                    <div class="xagio-slider-container">
                                        <input type="hidden"
                                               name="XAGIO_LLMS_POST_TYPES[<?php echo esc_attr($xagio_pt); ?>]"
                                               id="XAGIO_LLMS_PT_<?php echo esc_attr(strtoupper($xagio_pt)); ?>"
                                               value="<?php echo $xagio_pt_on ? 1 : 0; ?>"/>
                                        <div class="xagio-slider-frame">
                                            <span class="xagio-slider-button <?php echo $xagio_pt_on ? 'on' : ''; ?>"
                                                  data-element="XAGIO_LLMS_PT_<?php echo esc_attr(strtoupper($xagio_pt)); ?>"></span>
                                        </div>
                                        <p class="xagio-slider-label"><?php echo esc_html(ucfirst($xagio_pt)); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="xagio-column">
                        <div>
                            <h5 class="xagio-panel-title">
                                Preview
                            </h5>
                            <textarea id="xagio-llms-preview" rows="22" class="xagio-input-textarea" readonly spellcheck="false"><?php echo esc_textarea($xagio_llms_preview); ?></textarea>
                            <div class="xagio-flex xagio-flex-align-right xagio-margin-top-small">
                                <a href="<?php echo esc_url(home_url('/llms.txt')); ?>" target="_blank" rel="noopener"
                                   class="xagio-button xagio-button-primary">
                                    <i class="xagio-icon xagio-icon-external-link"></i> Open /llms.txt
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="xagio-flex-row xagio-flex-gap-medium xagio-margin-top-medium">
                    <button type="button" class="xagio-button xagio-button-outline llms-reset">
                        <i class="xagio-icon xagio-icon-refresh"></i> Reset to Default
                    </button>
                    <button type="button" class="xagio-button xagio-button-primary llms-save">
                        <i class="xagio-icon xagio-icon-check"></i> Save
                    </button>
                </div>
            </form>

        </div>

        <!-- OKF -->
        <div class="xagio-tab-content">

            <?php
            $xagio_okf_enabled        = (string) get_option('XAGIO_OKF_ENABLED', '0');
            $xagio_okf_post_types     = (array)  get_option('XAGIO_OKF_POST_TYPES', ['page' => 1, 'post' => 1]);
            $xagio_okf_bundle         = get_option('XAGIO_OKF_BUNDLE');
            $xagio_okf_count          = (is_array($xagio_okf_bundle) && isset($xagio_okf_bundle['files'])) ? count($xagio_okf_bundle['files']) : 0;
            $xagio_okf_built          = (is_array($xagio_okf_bundle) && !empty($xagio_okf_bundle['built'])) ? date_i18n('Y-m-d H:i', (int) $xagio_okf_bundle['built']) : '';
            $xagio_okf_post_type_list = function_exists('get_post_types') ? array_values(array_unique(array_merge(['page', 'post'], get_post_types(['public' => true, '_builtin' => false], 'names')))) : ['page', 'post'];

            $xagio_okf_lint           = get_option('XAGIO_OKF_LINT_STATUS');
            $xagio_okf_lint_bad       = ($xagio_okf_enabled === '1') && is_array($xagio_okf_lint) && empty($xagio_okf_lint['ok']);
            $xagio_okf_lint_errors    = ($xagio_okf_lint_bad && isset($xagio_okf_lint['errors'])) ? array_slice((array) $xagio_okf_lint['errors'], 0, 8) : [];
            $xagio_okf_lint_published = !is_array($xagio_okf_lint) || !empty($xagio_okf_lint['published']);
            $xagio_okf_lint_warnings  = ($xagio_okf_enabled === '1' && is_array($xagio_okf_lint) && isset($xagio_okf_lint['warnings'])) ? array_slice((array) $xagio_okf_lint['warnings'], 0, 12) : [];
            ?>

            <form id="xagio-okf-form" class="ts xagio-panel">
                <h5 class="xagio-panel-title">OKF (Open Knowledge Format)</h5>

                <div class="xagio-alert xagio-alert-primary m-b-20">
                    <i class="xagio-icon xagio-icon-info"></i>
                    <kbd>OKF</kbd> publishes each page as clean Markdown under <code>/okf/</code> with a per-page link graph, so AI engines can read your content directly instead of parsing HTML. The index lives at <code>/okf/</code> and each page at <code>/okf/&lt;slug&gt;.md</code>. Up to 1,000 most-recent published items are included.
                </div>

                <input type="hidden" name="action" value="xagio_okf_save"/>

                <div class="xagio-2-column-grid xagio-gap-large xagio-margin-bottom-large">
                    <div class="xagio-column">
                        <div class="xagio-margin-bottom-medium">
                            <h5 class="xagio-panel-title">General</h5>

                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_OKF_ENABLED" id="XAGIO_OKF_ENABLED"
                                       value="<?php echo $xagio_okf_enabled === '1' ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button <?php echo $xagio_okf_enabled === '1' ? 'on' : ''; ?>"
                                          data-element="XAGIO_OKF_ENABLED"></span>
                                </div>
                                <p class="xagio-slider-label">Enable <code>/okf/</code>
                                    <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                       data-xagio-title="When enabled, Xagio serves the OKF Markdown bundle at /okf/ and rebuilds it automatically when content changes."></i>
                                </p>
                            </div>
                        </div>

                        <div>
                            <h5 class="xagio-panel-title">Included post types</h5>
                            <p class="xagio-gray-label">Pick which post types Xagio renders into the OKF bundle. Each page uses its Xagio SEO title and description (or excerpt fallback).</p>

                            <div class="xagio-3-columns xagio-margin-top-small xagio-okf-post-types">
                                <?php foreach ($xagio_okf_post_type_list as $xagio_okf_pt):
                                    $xagio_okf_pt_on = !empty($xagio_okf_post_types[$xagio_okf_pt]); ?>
                                    <div class="xagio-slider-container">
                                        <input type="hidden"
                                               name="XAGIO_OKF_POST_TYPES[<?php echo esc_attr($xagio_okf_pt); ?>]"
                                               id="XAGIO_OKF_PT_<?php echo esc_attr(strtoupper($xagio_okf_pt)); ?>"
                                               value="<?php echo $xagio_okf_pt_on ? 1 : 0; ?>"/>
                                        <div class="xagio-slider-frame">
                                            <span class="xagio-slider-button <?php echo $xagio_okf_pt_on ? 'on' : ''; ?>"
                                                  data-element="XAGIO_OKF_PT_<?php echo esc_attr(strtoupper($xagio_okf_pt)); ?>"></span>
                                        </div>
                                        <p class="xagio-slider-label"><?php echo esc_html(ucfirst($xagio_okf_pt)); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="xagio-column">
                        <div>
                            <h5 class="xagio-panel-title">Bundle status</h5>
                            <p class="xagio-gray-label">
                                Documents in bundle: <strong id="xagio-okf-count"><?php echo esc_html($xagio_okf_count); ?></strong><br/>
                                Last built: <strong id="xagio-okf-built"><?php echo $xagio_okf_built !== '' ? esc_html($xagio_okf_built) : '&mdash;'; ?></strong>
                            </p>

                            <div id="xagio-okf-lint-alert" class="xagio-alert xagio-alert-danger m-b-20 m-t-20"
                                 style="<?php echo $xagio_okf_lint_bad ? '' : 'display:none;'; ?>">
                                <strong>Validation failed.</strong>
                                <span id="xagio-okf-lint-headline"><?php
                                    echo $xagio_okf_lint_published
                                        ? 'The bundle was rebuilt but did not pass validation.'
                                        : 'The new bundle was not published &mdash; the previous version is still served.';
                                ?></span>
                                <ul id="xagio-okf-lint-list" style="list-style:disc;margin:8px 0 0 20px;"><?php
                                    foreach ($xagio_okf_lint_errors as $xagio_okf_err) {
                                        echo '<li>' . esc_html((string) $xagio_okf_err) . '</li>';
                                    }
                                ?></ul>
                            </div>

                            <div id="xagio-okf-warn-alert" class="xagio-alert xagio-alert-primary m-b-20 m-t-20"
                                 style="<?php echo !empty($xagio_okf_lint_warnings) ? '' : 'display:none;'; ?>">
                                <strong>Content quality notices</strong>
                                <span class="xagio-gray-label">(informational &mdash; the bundle is still published)</span>
                                <ul id="xagio-okf-warn-list" style="list-style:disc;margin:8px 0 0 20px;"><?php
                                    foreach ($xagio_okf_lint_warnings as $xagio_okf_warn) {
                                        echo '<li>' . esc_html((string) $xagio_okf_warn) . '</li>';
                                    }
                                ?></ul>
                            </div>

                            <div id="xagio-okf-ok-alert" class="xagio-alert xagio-alert-success m-b-20 m-t-20"
                                 style="<?php echo (!$xagio_okf_lint_bad && empty($xagio_okf_lint_warnings) && $xagio_okf_count > 0) ? '' : 'display:none;'; ?>">
                                <strong>All good.</strong>
                                <span>The bundle passed validation with no content issues.</span>
                            </div>
                            <div class="xagio-flex xagio-flex-gap-medium xagio-margin-top-small">
                                <a href="<?php echo esc_url(home_url('/okf/')); ?>" target="_blank" rel="noopener"
                                   class="xagio-button xagio-button-primary">
                                    <i class="xagio-icon xagio-icon-external-link"></i> Open /okf/
                                </a>
                                <button type="button" class="xagio-button xagio-button-outline okf-rebuild">
                                    <i class="xagio-icon xagio-icon-refresh"></i> Rebuild now
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="xagio-flex-row xagio-flex-gap-medium xagio-margin-top-medium">
                    <button type="button" class="xagio-button xagio-button-outline okf-reset">
                        <i class="xagio-icon xagio-icon-refresh"></i> Reset to Default
                    </button>
                    <button type="button" class="xagio-button xagio-button-primary okf-save">
                        <i class="xagio-icon xagio-icon-check"></i> Save
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
