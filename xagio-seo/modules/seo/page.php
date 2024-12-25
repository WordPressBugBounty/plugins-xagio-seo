<?php
/**
 * Type: SUBMENU
 * Page_Title: SEO Settings
 * Menu_Title: SEO Settings
 * Capability: manage_options
 * Slug: xagio-seo
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: xagio_tagsinput,xagio_seo-settings,media-upload,thickbox
 * Css: xagio_animate,xagio_tagsinput,xagio_settings,thickbox
 * Position: 3
 * Version: 1.0.0
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

$post_types = get_option('XAGIO_SEO_DEFAULT_POST_TYPES');
$og         = get_option('XAGIO_SEO_DEFAULT_OG');
$ogs        = [
    'homepage'     => 'Homepage',
    'post'         => 'Posts',
    'page'         => 'Pages',
    'search'       => 'Search',
    'author'       => 'Author',
    'archive'      => 'Archive',
    'archive_post' => 'Archive Post',
    'not_found'    => 'Not Found'
];

$MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');
?>

<div class="xagio-main-header xagio-main-header-big-gaps">
    <img class="logo-image repo-xagio" src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        SEO Settings
    </h2>

    <?php if(isset($MEMBERSHIP_INFO["membership"]) && $MEMBERSHIP_INFO["membership"] === "Xagio AI Free") { ?>
        <div class="xagio-header-actions">
            <a href="https://xagio.com/?goto=wppremfeatures" target="_blank" class="xagio-button xagio-button-secondary xagio-button-premium-button">
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
            <span>From here you can apply all the settings that will be applied globally for your website's SEO.</span>
            <i class="xagio-icon xagio-icon-arrow-down"></i>
        </h3>
        <div class="xagio-accordion-content">
            <div>
                <div class="xagio-accordion-panel"></div>
            </div>
        </div>
    </div>

    <ul class="xagio-tab">

        <li class="xagio-tab-active"><a href="">SEO</a></li>
        <li><a href="">Open Graph</a></li>
        <li><a href="">Taxonomies</a></li>
        <li><a href="">Scripts</a></li>
    </ul>

    <div class="xagio-tab-content-holder">

        <!-- SEO -->
        <div class="xagio-tab-content">
            <div class="xagio-2-column-grid">
                <div class="xagio-column-1">
                    <div class="xagio-panel xagio-margin-bottom-medium">
                        <h5 class="xagio-panel-title">General</h5>
                        <form class="ts">
                            <input type="hidden" name="action" value="xagio_settings"/>

                            <?php if (class_exists('XAGIO_MODEL_SEO')): ?>

                                <!-- Xagio Force Enable -->
                                <div class="xagio-slider-container">
                                    <input type="hidden" name="XAGIO_SEO_FORCE_ENABLE" id="XAGIO_SEO_FORCE_ENABLE"
                                           value="<?php echo XAGIO_SEO_FORCE_ENABLE ? 1 : 0; ?>"/>
                                    <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button xagio-slider-button-settings <?php echo XAGIO_SEO_FORCE_ENABLE ? 'on' : ''; ?>"
                                              data-element="XAGIO_SEO_FORCE_ENABLE"></span>
                                    </div>
                                    <p class="xagio-slider-label">Force enable Xagio SEO <i
                                                class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                                data-xagio-title="If you turn On this option, Xagio will always force SEO part of On-Page SEO to be toggled to On."></i>
                                    </p>
                                </div>

                                <!-- Remove footprint -->
                                <div class="xagio-slider-container">
                                    <input type="hidden" name="XAGIO_DISABLE_HTML_FOOTPRINT"
                                           id="XAGIO_DISABLE_HTML_FOOTPRINT"
                                           value="<?php echo (TRUE == XAGIO_DISABLE_HTML_FOOTPRINT) ? 1 : 0; ?>"/>
                                    <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button xagio-slider-button-settings <?php echo XAGIO_DISABLE_HTML_FOOTPRINT ? 'on' : ''; ?>"
                                              data-element="XAGIO_DISABLE_HTML_FOOTPRINT"></span>
                                    </div>
                                    <p class="xagio-slider-label">Disable HTML Footprint <i
                                                class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                                data-xagio-title="Xagio displays HTML comments inside of your website's source code when rendering schemas, seo settings, meta robots and etc. Google is not actually seeing or parsing those, but if you want to remove them, you can by setting this to Yes."></i>
                                    </p>
                                </div>

                                <!-- Disable Canonicals -->
                                <div class="xagio-slider-container">
                                    <input type="hidden" name="XAGIO_DISABLE_WP_CANONICALS"
                                           id="XAGIO_DISABLE_WP_CANONICALS"
                                           value="<?php echo XAGIO_DISABLE_WP_CANONICALS ? 1 : 0; ?>"/>
                                    <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button xagio-slider-button-settings <?php echo XAGIO_DISABLE_WP_CANONICALS ? 'on' : ''; ?>"
                                              data-element="XAGIO_DISABLE_WP_CANONICALS"></span>
                                    </div>
                                    <p class="xagio-slider-label">Disable WordPress Canonical URLs <i
                                                class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                                data-xagio-title="Use this option if you want to prevent default Canonical URLs that WordPress generates. Instead, xagio will generate Canonical URLs for your pages automatically."></i>
                                    </p>
                                </div>

                            <?php endif; ?>

                        </form>

                    </div>

                    <div class="xagio-panel xagio-margin-bottom-medium">
                        <h5 class="xagio-panel-title">Xagio Shortcodes</h5>

                        <button type="button" class="xagio-button xagio-button-primary"
                                data-xagio-modal="viewShortcodes"><i class="xagio-icon xagio-icon-code"></i> View Shortcodes
                        </button>

                        <h3 class="pop">Title Separator</h3>
                        <fieldset class="titleSeparators" id="separator"
                                  data-value="<?php echo esc_attr(get_option('XAGIO_SEO_TITLE_SEPARATOR')); ?>">
                            <input type="radio" class="radio" id="separator-sc-dash" name="XAGIO_SEO_TITLE_SEPARATOR"
                                   value="-">
                            <label class="radio" for="separator-sc-dash">-</label>

                            <input type="radio" class="radio" id="separator-sc-ndash" name="XAGIO_SEO_TITLE_SEPARATOR"
                                   value="–">
                            <label class="radio" for="separator-sc-ndash">–</label>

                            <input type="radio" class="radio" id="separator-sc-mdash" name="XAGIO_SEO_TITLE_SEPARATOR"
                                   value="—">
                            <label class="radio" for="separator-sc-mdash">—</label>

                            <input type="radio" class="radio" id="separator-sc-bull" name="XAGIO_SEO_TITLE_SEPARATOR"
                                   value="•">
                            <label class="radio" for="separator-sc-bull">•</label>

                            <input type="radio" class="radio" id="separator-sc-pipe" name="XAGIO_SEO_TITLE_SEPARATOR"
                                   value="|">
                            <label class="radio" for="separator-sc-pipe">|</label>

                            <input type="radio" class="radio" id="separator-sc-tilde" name="XAGIO_SEO_TITLE_SEPARATOR"
                                   value="~">
                            <label class="radio" for="separator-sc-tilde">~</label>

                            <input type="radio" class="radio" id="separator-sc-laquo" name="XAGIO_SEO_TITLE_SEPARATOR"
                                   value="«">
                            <label class="radio" for="separator-sc-laquo">«</label>

                            <input type="radio" class="radio" id="separator-sc-raquo" name="XAGIO_SEO_TITLE_SEPARATOR"
                                   value="»">
                            <label class="radio" for="separator-sc-raquo">»</label>

                            <input type="radio" class="radio" id="separator-sc-lt" name="XAGIO_SEO_TITLE_SEPARATOR"
                                   value="<">
                            <label class="radio" for="separator-sc-lt">&lt;</label>

                            <input type="radio" class="radio" id="separator-sc-gt" name="XAGIO_SEO_TITLE_SEPARATOR"
                                   value=">">
                            <label class="radio" for="separator-sc-gt">&gt;</label>
                        </fieldset>

                        <p class="xagio-gray-label">
                            <i class="xagio-icon xagio-icon-info"></i> Set up which title separator should be used for
                            separating parts in your titles.
                        </p>
                    </div>

                    <div class="xagio-panel">
                        <h5 class="xagio-panel-title">Schema</h5>

                        <?php if (class_exists('XAGIO_MODEL_SCHEMA')): ?>

                            <!-- Force Schemas -->
                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_FORCE_HOMEPAGE_SCHEMA" id="XAGIO_FORCE_HOMEPAGE_SCHEMA"
                                       value="<?php echo (XAGIO_FORCE_HOMEPAGE_SCHEMA == TRUE) ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button xagio-slider-button-settings <?php echo (XAGIO_FORCE_HOMEPAGE_SCHEMA == TRUE) ? 'on' : ''; ?>"
                                          data-element="XAGIO_FORCE_HOMEPAGE_SCHEMA"></span>
                                </div>
                                <p class="xagio-slider-label">Force Homepage Schemas <i
                                            class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                            data-xagio-title="To force your Homepage schema's to appear on all of your pages, set this to Yes. However be warned that this may result in getting spammy structured data warnings from Google."></i>
                                </p>
                            </div>

                            <!-- Render Pretty Schemas -->
                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_RENDER_PRETTY_SCHEMAS" id="XAGIO_RENDER_PRETTY_SCHEMAS"
                                       value="<?php echo (XAGIO_RENDER_PRETTY_SCHEMAS == TRUE) ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button xagio-slider-button-settings <?php echo (XAGIO_RENDER_PRETTY_SCHEMAS == TRUE) ? 'on' : ''; ?>"
                                          data-element="XAGIO_RENDER_PRETTY_SCHEMAS"></span>
                                </div>
                                <p class="xagio-slider-label">Render Pretty Schemas <i
                                            class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                            data-xagio-title="This can be useful during the schemas setup. It will show you schemas in source code in human readable format instead of minified version you normally see. Should be turned off after setup to save server resources."></i>
                                </p>
                            </div>

                        <?php endif; ?>
                    </div>
                </div>
                <div class="xagio-column-2">
                    <div class="xagio-panel">
                        <h5 class="xagio-panel-title">Migration</h5>

                        <form class="ts">
                            <input type="hidden" name="action" value="xag_migrate"/>

                            <div class="xagio-migration-panel">
                                <h3 class="xagio-migration-panel-title">Rank Math
                                    SEO <?php echo (get_option('XAGIO_MIGRATE_RANKMATH')) ? '<i class="xagio-icon xagio-icon-check" data-xagio-tooltip data-xagio-title="Migrated"></i>' : "" ?></h3>
                                <?php if (is_plugin_active('seo-by-rank-math/rank-math.php')) { ?>

                                    <p class="migration-info">Following page/post data will be migrated from Rank Math
                                        SEO to Xagio:</p>
                                    <ul class="migration-list">
                                        <li>Titles</li>
                                        <li>Descriptions</li>
                                        <li>Focus Keywords</li>
                                        <li>OpenGraph Titles</li>
                                        <li>OpenGraph Descriptions</li>
                                        <li>OpenGraph Images</li>
                                    </ul>

                                    <div class="xagio-flex-right">
                                        <button type="button"
                                                class="xagio-button xagio-button-primary uk-button-big migration-rankmath">
                                            <i class="xagio-icon xagio-icon-arrow-right"></i> Start Rank Math SEO Migration
                                        </button>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="xagio-migration-panel">
                                <h3 class="xagio-migration-panel-title">Yoast
                                    SEO <?php echo (get_option('XAGIO_MIGRATE_YOAST')) ? '<i class="xagio-icon xagio-icon-check" data-xagio-tooltip data-xagio-title="Migrated"></i>' : "" ?></h3>
                                <?php if (is_plugin_active('wordpress-seo/wp-seo.php') || is_plugin_active('wordpress-seo-premium/wp-seo-premium.php')) { ?>

                                    <p class="migration-info">Following page/post data will be migrated from Yoast SEO
                                        to Xagio:</p>
                                    <ul class="migration-list">
                                        <li>Titles</li>
                                        <li>Descriptions</li>
                                        <li>Focus Keywords</li>
                                        <li>Canonical URLs</li>
                                        <li>OpenGraph Titles</li>
                                        <li>OpenGraph Descriptions</li>
                                        <li>OpenGraph Images</li>
                                    </ul>

                                    <div class="xagio-flex-right">
                                        <button type="button" class="xagio-button xagio-button-primary migration-yoast">
                                            <i class="xagio-icon xagio-icon-check"></i> Start Migration
                                        </button>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="xagio-migration-panel">
                                <h3 class="xagio-migration-panel-title">All in One
                                    SEO <?php echo (get_option('XAGIO_MIGRATE_AIO')) ? '<i class="xagio-icon xagio-icon-check" data-xagio-tooltip data-xagio-title="Migrated"></i>' : "" ?></h3>
                                <?php if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php')) { ?>
                                    <p class="migration-info">Following page/post data will be migrated from All in One
                                        SEO to Xagio:</p>
                                    <ul class="migration-list">
                                        <li>Titles</li>
                                        <li>Descriptions</li>
                                        <li>Canonical URLs</li>
                                    </ul>

                                    <div class="xagio-flex-right">
                                        <button type="button" class="xagio-button xagio-button-primary migration-aio"><i
                                                    class="xagio-icon xagio-icon-arrow-right"></i> Start AIO Migration
                                        </button>
                                    </div>
                                <?php } ?>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>

        <!-- Open Graph -->
        <div class="xagio-tab-content">

            <?php foreach ($ogs as $key => $title): ?>

                <div class="xagio-accordion xagio-margin-bottom-medium <?php echo $key == 'homepage' ? 'xagio-accordion-opened' : ''; ?>">
                    <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                        <span><?php echo esc_html($title); ?></span>
                        <i class="xagio-icon xagio-icon-arrow-down"></i>
                    </h3>
                    <div class="xagio-accordion-content">
                        <div>
                            <div class="xagio-accordion-panel">

                                <div class="xagio-alert xagio-alert-primary">
                                    <i class="xagio-icon xagio-icon-info"></i> After saving Open Graph settings, you can preview the changes on your website by either using Facebook's <a href="https://developers.facebook.com/tools/debug/" target="_blank">Sharing
                                        Debugger</a> or Twitter's <a href="https://www.bannerbear.com/tools/twitter-card-preview-tool/" target="_blank">Card Preview Tool</a>.
                                </div>

                                <div class="xagio-2-column-grid xagio-gap-large xagio-margin-bottom-large xagio-margin-top-medium">
                                    <div class="xagio-column">

                                        <h2 class="uk-margin-top">Facebook Settings</h2>

                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input XAGIO_OG_TITLE"
                                               name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($key); ?>][XAGIO_SEO_FACEBOOK_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$key]['XAGIO_SEO_FACEBOOK_TITLE']))); ?>"/>

                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5"
                                                  class="xagio-input-textarea defaults-input XAGIO_OG_DESCRIPTION"
                                                  name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($key); ?>][XAGIO_SEO_FACEBOOK_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$key]['XAGIO_SEO_FACEBOOK_DESCRIPTION']))); ?></textarea>

                                        <!-- Image -->
                                        <h3 class="pop">Image</h3>

                                        <div class="input-group">
                                            <input type="text"
                                                   class="xagio-input-text-mini defaults-input XAGIO_OG_IMAGE"
                                                   id="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($key); ?>_XAGIO_SEO_FACEBOOK_IMAGE"
                                                   name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($key); ?>][XAGIO_SEO_FACEBOOK_IMAGE]"
                                                   value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$key]['XAGIO_SEO_FACEBOOK_IMAGE']))); ?>"/>

                                            <button class="xagio-button xagio-button-primary xagio-select-image"
                                                    type="button"
                                                    data-target="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($key); ?>_XAGIO_SEO_FACEBOOK_IMAGE">
                                                <i
                                                        class="xagio-icon xagio-icon-folder-open"></i> Browse
                                            </button>
                                        </div>

                                        <div class="facebook-preview uk-margin-large-top">
                                            <div class="facebook-preview-header">
                                                <div class="facebook-preview-author-profile">
                                                    <img src="<?php echo esc_url(get_site_icon_url(160, XAGIO_URL . 'assets/img/logo-xagio.webp')); ?>">
                                                    <div class="facebook-preview-author">
                                                        <div><?php echo esc_html(get_bloginfo('name')); ?></div>
                                                        <div><?php echo esc_html(gmdate('d M')); ?></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <i class="xagio-icon xagio-icon-dots-horizontal"></i>
                                                </div>
                                            </div>
                                            <img src="<?php echo filter_var(@$og[$key]['XAGIO_SEO_FACEBOOK_IMAGE'], FILTER_VALIDATE_URL) ? esc_url(@$og[$key]['XAGIO_SEO_FACEBOOK_IMAGE']) : esc_url(XAGIO_URL) . 'assets/img/facebook-placeholder.webp' ?>" class="facebook-image-preview">
                                            <div class="facebook-preview-content">
                                                <div class="facebook-preview-url"><?php echo esc_url(strtoupper(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                                                <div class="facebook-preview-title"><?php echo esc_html(@$og[$key]['XAGIO_SEO_FACEBOOK_TITLE']); ?></div>
                                                <div class="facebook-preview-description"><?php echo esc_html(@$og[$key]['XAGIO_SEO_FACEBOOK_DESCRIPTION']); ?></div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="xagio-column">

                                        <h2 class="uk-margin-top">Twitter Settings</h2>

                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input XAGIO_OG_TITLE"
                                               name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($key); ?>][XAGIO_SEO_TWITTER_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$key]['XAGIO_SEO_TWITTER_TITLE']))); ?>"/>

                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5"
                                                  class="xagio-input-textarea defaults-input XAGIO_OG_DESCRIPTION"
                                                  name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($key); ?>][XAGIO_SEO_TWITTER_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$key]['XAGIO_SEO_TWITTER_DESCRIPTION']))); ?></textarea>

                                        <!-- Image -->
                                        <h3 class="pop">Image</h3>

                                        <div class="input-group">
                                            <input type="text"
                                                   class="xagio-input-text-mini defaults-input XAGIO_OG_IMAGE"
                                                   id="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($key); ?>_XAGIO_SEO_TWITTER_IMAGE"
                                                   name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($key); ?>][XAGIO_SEO_TWITTER_IMAGE]"
                                                   value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$key]['XAGIO_SEO_TWITTER_IMAGE']))); ?>"/>

                                            <button class="xagio-button xagio-button-primary xagio-select-image"
                                                    type="button"
                                                    data-target="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($key); ?>_XAGIO_SEO_TWITTER_IMAGE">
                                                <i
                                                        class="xagio-icon xagio-icon-folder-open"></i> Browse
                                            </button>
                                        </div>

                                        <div class="twitter-preview uk-margin-large-top">
                                            <div class="twitter-preview-header">
                                                <div class="twitter-preview-author-profile">
                                                    <img src="<?php echo esc_url(get_site_icon_url(160, XAGIO_URL . 'assets/img/logo-xagio.webp')); ?>">
                                                    <div class="twitter-preview-author">
                                                        <div><?php echo esc_html(get_bloginfo('name')); ?></div>
                                                        <div><?php echo esc_html(gmdate('d M')); ?></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <i class="xagio-icon xagio-icon-dots-horizontal"></i>
                                                </div>
                                            </div>

                                            <div class="twitter-preview-holder">
                                                <div class="twitter-image-preview-holder">
                                                    <img src="<?php echo filter_var(@$og[$key]['XAGIO_SEO_TWITTER_IMAGE'], FILTER_VALIDATE_URL) ? esc_url(@$og[$key]['XAGIO_SEO_TWITTER_IMAGE']) : esc_url(XAGIO_URL) . 'assets/img/twitter-placeholder.webp' ?>" class="twitter-image-preview">
                                                </div>
                                                <div class="twitter-preview-content">
                                                    <div class="twitter-preview-url"><?php echo esc_url(strtoupper(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                                                    <div class="twitter-preview-title"><?php echo esc_html(@$og[$key]['XAGIO_SEO_TWITTER_TITLE']); ?></div>
                                                    <div class="twitter-preview-description"><?php echo esc_html(@$og[$key]['XAGIO_SEO_TWITTER_DESCRIPTION']); ?></div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

            <?php foreach (XAGIO_MODEL_SEO::getOtherPostObjects() as $post_type) {
                /* Code for get post label from post object */
                $post_name = (is_array($post_type) ? $post_type['label'] : $post_type);
                $post_type = (is_array($post_type) ? $post_type['name'] : $post_type);
                ?>

                <div class="xagio-accordion xagio-margin-bottom-medium">
                    <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                        <span><?php echo esc_html($post_name); ?></span>
                        <i class="xagio-icon xagio-icon-arrow-down"></i>
                    </h3>
                    <div class="xagio-accordion-content">
                        <div>
                            <div class="xagio-accordion-panel">

                                <div class="xagio-alert xagio-alert-primary">
                                    <i class="xagio-icon xagio-icon-info"></i> After saving Open Graph settings, you can preview
                                    the
                                    changes on your website by either using Facebook's <a
                                            href="https://developers.facebook.com/tools/debug/" target="_blank">Sharing
                                        Debugger</a> or Twitter's <a
                                            href="https://www.bannerbear.com/tools/twitter-card-preview-tool/"
                                            target="_blank">Card Preview Tool</a>.
                                </div>

                                <div class="xagio-2-column-grid xagio-gap-large xagio-margin-bottom-large xagio-margin-top-medium">
                                    <div class="xagio-column">

                                        <h2 class="uk-margin-top">Facebook Settings</h2>

                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input XAGIO_OG_TITLE"
                                               name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_FACEBOOK_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$post_type]['XAGIO_SEO_FACEBOOK_TITLE']))); ?>"/>

                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5"
                                                  class="xagio-input-textarea defaults-input XAGIO_OG_DESCRIPTION"
                                                  name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_FACEBOOK_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$post_type]['XAGIO_SEO_FACEBOOK_DESCRIPTION']))); ?></textarea>

                                        <!-- Image -->
                                        <h3 class="pop">Image</h3>

                                        <div class="input-group">
                                            <input type="text"
                                                   class="xagio-input-text-mini defaults-input XAGIO_OG_IMAGE"
                                                   id="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($post_type); ?>_XAGIO_SEO_FACEBOOK_IMAGE"
                                                   name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_FACEBOOK_IMAGE]"
                                                   value="<?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$post_type]['XAGIO_SEO_FACEBOOK_IMAGE']))); ?>"/>

                                            <button class="xagio-button xagio-button-primary xagio-select-image"
                                                    type="button"
                                                    data-target="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($post_type); ?>_XAGIO_SEO_FACEBOOK_IMAGE">
                                                <i
                                                        class="xagio-icon xagio-icon-folder-open"></i> Browse
                                            </button>
                                        </div>

                                        <div class="facebook-preview uk-margin-large-top">
                                            <div class="facebook-preview-header">
                                                <div class="facebook-preview-author-profile">
                                                    <img src="<?php echo esc_url(get_site_icon_url(160, XAGIO_URL . 'assets/img/logo-xagio.webp')); ?>">
                                                    <div class="facebook-preview-author">
                                                        <div><?php echo esc_html(get_bloginfo('name')); ?></div>
                                                        <div><?php echo esc_html(gmdate('d M')); ?></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <i class="xagio-icon xagio-icon-dots-horizontal"></i>
                                                </div>
                                            </div>
                                            <img src="<?php echo filter_var(@$og[$post_type]['XAGIO_SEO_FACEBOOK_IMAGE'], FILTER_VALIDATE_URL) ? esc_url(@$og[$post_type]['XAGIO_SEO_FACEBOOK_IMAGE']) : esc_url(XAGIO_URL) . 'assets/img/facebook-placeholder.webp' ?>" class="facebook-image-preview">
                                            <div class="facebook-preview-content">
                                                <div class="facebook-preview-url"><?php echo esc_url(strtoupper(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                                                <div class="facebook-preview-title"><?php echo esc_html(@$og[$post_type]['XAGIO_SEO_FACEBOOK_TITLE']); ?></div>
                                                <div class="facebook-preview-description"><?php echo esc_html(@$og[$post_type]['XAGIO_SEO_FACEBOOK_DESCRIPTION']); ?></div>
                                            </div>
                                        </div>


                                    </div>
                                    <div class="xagio-column">

                                        <h2 class="uk-margin-top">Twitter Settings</h2>

                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input XAGIO_OG_TITLE"
                                               name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_TWITTER_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$post_type]['XAGIO_SEO_TWITTER_TITLE']))); ?>"/>

                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5"
                                                  class="xagio-input-textarea defaults-input XAGIO_OG_DESCRIPTION"
                                                  name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_TWITTER_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$post_type]['XAGIO_SEO_TWITTER_DESCRIPTION']))); ?></textarea>

                                        <!-- Image -->
                                        <h3 class="pop">Image</h3>

                                        <div class="input-group">
                                            <input type="text"
                                                   class="xagio-input-text-mini defaults-input XAGIO_OG_IMAGE"
                                                   id="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($post_type); ?>_XAGIO_SEO_TWITTER_IMAGE"
                                                   name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_TWITTER_IMAGE]"
                                                   value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$post_type]['XAGIO_SEO_TWITTER_IMAGE']))); ?>"/>

                                            <button class="xagio-button xagio-button-primary xagio-select-image"
                                                    type="button"
                                                    data-target="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($post_type); ?>_XAGIO_SEO_TWITTER_IMAGE">
                                                <i
                                                        class="xagio-icon xagio-icon-folder-open"></i> Browse
                                            </button>
                                        </div>

                                        <div class="twitter-preview uk-margin-large-top">
                                            <div class="twitter-preview-header">
                                                <div class="twitter-preview-author-profile">
                                                    <img src="<?php echo esc_url(get_site_icon_url(160, XAGIO_URL . 'assets/img/logo-xagio.webp')); ?>">
                                                    <div class="twitter-preview-author">
                                                        <div><?php echo esc_html(get_bloginfo('name')); ?></div>
                                                        <div><?php echo esc_html(gmdate('d M')); ?></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <i class="xagio-icon xagio-icon-dots-horizontal"></i>
                                                </div>
                                            </div>

                                            <div class="twitter-preview-holder">
                                                <div class="twitter-image-preview-holder">
                                                    <img src="<?php echo  filter_var(@$og[$post_type]['XAGIO_SEO_TWITTER_IMAGE'], FILTER_VALIDATE_URL) ? esc_url(@$og[$post_type]['XAGIO_SEO_TWITTER_IMAGE']) : esc_url(XAGIO_URL) . 'assets/img/twitter-placeholder.webp' ?>" class="twitter-image-preview">
                                                </div>
                                                <div class="twitter-preview-content">
                                                    <div class="twitter-preview-url"><?php echo esc_url(strtoupper(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                                                    <div class="twitter-preview-title"><?php echo esc_html(@$og[$post_type]['XAGIO_SEO_TWITTER_TITLE']); ?></div>
                                                    <div class="twitter-preview-description"><?php echo esc_html(@$og[$post_type]['XAGIO_SEO_TWITTER_DESCRIPTION']); ?></div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            <?php } ?>

            <?php $taxonomies = get_option('XAGIO_SEO_DEFAULT_TAXONOMIES'); ?>
            <?php foreach (XAGIO_MODEL_SEO::getAllTaxonomies() as $taxonomy) {
                // Extract the taxonomy real name
                $tax = get_taxonomy($taxonomy);
                ?>

                <div class="xagio-accordion xagio-margin-bottom-medium">
                    <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                        <span><?php echo esc_html($tax->label); ?></span>
                        <i class="xagio-icon xagio-icon-arrow-down"></i>
                    </h3>
                    <div class="xagio-accordion-content">
                        <div>
                            <div class="xagio-accordion-panel">

                                <div class="xagio-alert xagio-alert-primary">
                                    <i class="xagio-icon xagio-icon-info"></i> After saving Open Graph settings, you can preview
                                    the
                                    changes on your website by either using Facebook's <a
                                            href="https://developers.facebook.com/tools/debug/" target="_blank">Sharing
                                        Debugger</a> or Twitter's <a
                                            href="https://www.bannerbear.com/tools/twitter-card-preview-tool/"
                                            target="_blank">Card Preview Tool</a>.
                                </div>

                                <div class="xagio-2-column-grid xagio-gap-large xagio-margin-bottom-large xagio-margin-top-medium">
                                    <div class="xagio-column">

                                        <h2 class="uk-margin-top">Facebook Settings</h2>

                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input XAGIO_OG_TITLE"
                                               name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_FACEBOOK_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$taxonomy]['XAGIO_SEO_FACEBOOK_TITLE']))); ?>"/>

                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5"
                                                  class="xagio-input-textarea defaults-input XAGIO_OG_DESCRIPTION"
                                                  name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_FACEBOOK_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$taxonomy]['XAGIO_SEO_FACEBOOK_DESCRIPTION']))); ?></textarea>

                                        <!-- Image -->
                                        <h3 class="pop">Image</h3>

                                        <div class="input-group">
                                            <input type="text"
                                                   class="xagio-input-text-mini defaults-input XAGIO_OG_IMAGE"
                                                   id="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($taxonomy); ?>_XAGIO_SEO_FACEBOOK_IMAGE"
                                                   name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_FACEBOOK_IMAGE]"
                                                   value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$taxonomy]['XAGIO_SEO_FACEBOOK_IMAGE']))); ?>"/>

                                            <button class="xagio-button xagio-button-primary xagio-select-image"
                                                    type="button"
                                                    data-target="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($taxonomy); ?>_XAGIO_SEO_FACEBOOK_IMAGE">
                                                <i
                                                        class="xagio-icon xagio-icon-folder-open"></i> Browse
                                            </button>
                                        </div>

                                        <div class="facebook-preview uk-margin-large-top">
                                            <div class="facebook-preview-header">
                                                <div class="facebook-preview-author-profile">
                                                    <img src="<?php echo esc_url(get_site_icon_url(160, XAGIO_URL . 'assets/img/logo-xagio.webp')); ?>">
                                                    <div class="facebook-preview-author">
                                                        <div><?php echo esc_html(get_bloginfo('name')); ?></div>
                                                        <div><?php echo esc_html(gmdate('d M')); ?></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <i class="xagio-icon xagio-icon-dots-horizontal"></i>
                                                </div>
                                            </div>
                                            <img src="<?php echo filter_var(@$og[$taxonomy]['XAGIO_SEO_FACEBOOK_IMAGE'], FILTER_VALIDATE_URL) ? esc_url(@$og[$taxonomy]['XAGIO_SEO_FACEBOOK_IMAGE']) : esc_url(XAGIO_URL) . 'assets/img/facebook-placeholder.webp' ?>" class="facebook-image-preview">
                                            <div class="facebook-preview-content">
                                                <div class="facebook-preview-url"><?php echo esc_url(strtoupper(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                                                <div class="facebook-preview-title"><?php echo esc_html(@$og[$taxonomy]['XAGIO_SEO_FACEBOOK_TITLE']); ?></div>
                                                <div class="facebook-preview-description"><?php echo esc_html(@$og[$taxonomy]['XAGIO_SEO_FACEBOOK_DESCRIPTION']); ?></div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="xagio-column">

                                        <h2 class="uk-margin-top">Twitter Settings</h2>

                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input XAGIO_OG_TITLE"
                                               name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_TWITTER_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$taxonomy]['XAGIO_SEO_TWITTER_TITLE']))); ?>"/>

                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5"
                                                  class="xagio-input-textarea defaults-input XAGIO_OG_DESCRIPTION"
                                                  name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_TWITTER_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$taxonomy]['XAGIO_SEO_TWITTER_DESCRIPTION']))); ?></textarea>

                                        <!-- Image -->
                                        <h3 class="pop">Image</h3>

                                        <div class="input-group">
                                            <input type="text"
                                                   class="xagio-input-text-mini defaults-input XAGIO_OG_IMAGE"
                                                   id="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($taxonomy); ?>_XAGIO_SEO_TWITTER_IMAGE"
                                                   name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_TWITTER_IMAGE]"
                                                   value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$og[$taxonomy]['XAGIO_SEO_TWITTER_IMAGE']))); ?>"/>

                                            <button class="xagio-button xagio-button-primary xagio-select-image"
                                                    type="button"
                                                    data-target="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($taxonomy); ?>_XAGIO_SEO_TWITTER_IMAGE">
                                                <i
                                                        class="xagio-icon xagio-icon-folder-open"></i> Browse
                                            </button>
                                        </div>

                                        <div class="twitter-preview uk-margin-large-top">
                                            <div class="twitter-preview-header">
                                                <div class="twitter-preview-author-profile">
                                                    <img src="<?php echo esc_url(get_site_icon_url(160, XAGIO_URL . 'assets/img/logo-xagio.webp')); ?>">
                                                    <div class="twitter-preview-author">
                                                        <div><?php echo esc_html(get_bloginfo('name')); ?></div>
                                                        <div><?php echo esc_html(gmdate('d M')); ?></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <i class="xagio-icon xagio-icon-dots-horizontal"></i>
                                                </div>
                                            </div>

                                            <div class="twitter-preview-holder">
                                                <div class="twitter-image-preview-holder">
                                                    <img src="<?php echo  filter_var(@$og[$taxonomy]['XAGIO_SEO_TWITTER_IMAGE'], FILTER_VALIDATE_URL) ? esc_url(@$og[$taxonomy]['XAGIO_SEO_TWITTER_IMAGE']) : esc_url(XAGIO_URL) . 'assets/img/twitter-placeholder.webp' ?>" class="twitter-image-preview">
                                                </div>
                                                <div class="twitter-preview-content">
                                                    <div class="twitter-preview-url"><?php echo esc_url(strtoupper(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                                                    <div class="twitter-preview-title"><?php echo esc_html(@$og[$taxonomy]['XAGIO_SEO_TWITTER_TITLE']); ?></div>
                                                    <div class="twitter-preview-description"><?php echo esc_html(@$og[$taxonomy]['XAGIO_SEO_TWITTER_DESCRIPTION']); ?></div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            <?php } ?>

        </div>

        <!-- Taxonomies -->
        <div class="xagio-tab-content">
            <div class="xagio-accordion xagio-margin-bottom-medium xagio-accordion-opened">
                <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                    <span>Homepage</span>
                    <i class="xagio-icon xagio-icon-arrow-down"></i>
                </h3>
                <div class="xagio-accordion-content">
                    <div>
                        <div class="xagio-accordion-panel">
                            <div class="xagio-alert xagio-alert-primary">
                                <i class="xagio-icon xagio-icon-info"></i> When your home page settings in WordPress are set to
                                "Your Latest Posts" this will control the title and description of your home page. When
                                a static page is set as the home page, the settings for that page Title and Description
                                will override this setting.
                            </div>

                            <!-- Title -->
                            <h3 class="pop">Title</h3>
                            <input type="text" class="xagio-input-text-mini defaults-input"
                                   name="XAGIO_SEO_DEFAULT_POST_TYPES[homepage][XAGIO_SEO_TITLE]"
                                   value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$post_types['homepage']['XAGIO_SEO_TITLE']))); ?>"/>

                            <!-- Description -->
                            <h3 class="pop">Description</h3>
                            <textarea rows="5" class="xagio-input-textarea defaults-input"
                                      name="XAGIO_SEO_DEFAULT_POST_TYPES[homepage][XAGIO_SEO_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag(@$post_types['homepage']['XAGIO_SEO_DESCRIPTION']))); ?></textarea>

                            <div class="xagio-save-changes-holder xagio-margin-top-large">
                                <div class="xagio-slider-container">
                                    <input type="hidden" class="defaults-input"
                                           name="XAGIO_SEO_DEFAULT_POST_TYPES[homepage][XAGIO_SEO_ROBOTS]"
                                           id="ps_seo_slider-homepage"
                                           value="<?php echo (@$post_types['homepage']['XAGIO_SEO_ROBOTS'] == 1) ? 1 : 0; ?>">
                                    <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button <?php echo (@$post_types['homepage']['XAGIO_SEO_ROBOTS'] == 1) ? 'on' : ''; ?>"
                                              data-element="ps_seo_slider-homepage"></span>
                                    </div>
                                    <p class="xagio-slider-label">Don't Index & Follow <i
                                                class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                                data-xagio-title="Do not index these kind of post types but add meta robots follow to them."></i>
                                    </p>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="xagio-2-column-grid">
                <div class="xagio-column-1">
                    <div class="xagio-accordion xagio-margin-bottom-medium">
                        <h3 class="xagio-accordion-title xagio-accordion-panel-title"><span>Posts</span><i
                                    class="xagio-icon xagio-icon-arrow-down"></i></h3>
                        <div class="xagio-accordion-content">
                            <div>
                                <div class="xagio-accordion-panel">
                                    <!-- Title -->
                                    <h3 class="pop">Title</h3>

                                    <input type="text" class="xagio-input-text-mini defaults-input"
                                           name="XAGIO_SEO_DEFAULT_POST_TYPES[post][XAGIO_SEO_TITLE]"
                                           value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$post_types['post']['XAGIO_SEO_TITLE']))); ?>"/>
                                    <!-- Description -->
                                    <h3 class="pop">Description</h3>
                                    <textarea rows="6" class="xagio-input-textarea defaults-input"
                                              name="XAGIO_SEO_DEFAULT_POST_TYPES[post][XAGIO_SEO_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag(@$post_types['post']['XAGIO_SEO_DESCRIPTION']))); ?></textarea>

                                    <div class="xagio-save-changes-holder xagio-margin-top-large">
                                        <div class="xagio-slider-container">
                                            <input type="hidden" class="defaults-input"
                                                   name="XAGIO_SEO_DEFAULT_POST_TYPES[post][XAGIO_SEO_ROBOTS]"
                                                   id="ps_seo_slider-post"
                                                   value="<?php echo (@$post_types['post']['XAGIO_SEO_ROBOTS'] == 1) ? 1 : 0; ?>">
                                            <div class="xagio-slider-frame">
                                                <span class="xagio-slider-button <?php echo (@$post_types['post']['XAGIO_SEO_ROBOTS'] == 1) ? 'on' : ''; ?>"
                                                      data-element="ps_seo_slider-post"></span>
                                            </div>
                                            <p class="xagio-slider-label">Don't Index & Follow <i
                                                        class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                                        data-xagio-title="Do not index these kind of post types but add meta robots follow to them."></i>
                                            </p>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php foreach (XAGIO_MODEL_SEO::getOtherPostObjects() as $post_type) {
                        /* Code for get post label from post object */
                        $post_name = (is_array($post_type) ? $post_type['label'] : $post_type);
                        $post_type = (is_array($post_type) ? $post_type['name'] : $post_type);
                        ?>
                        <div class="xagio-accordion xagio-margin-bottom-medium">
                            <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                <span><?php echo esc_html(ucfirst($post_name)); ?></span><i
                                        class="xagio-icon xagio-icon-arrow-down"></i></h3>
                            <div class="xagio-accordion-content">
                                <div>
                                    <div class="xagio-accordion-panel">
                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input"
                                               name="XAGIO_SEO_DEFAULT_POST_TYPES[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$post_types[$post_type]['XAGIO_SEO_TITLE']))); ?>"/>
                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="6" class="xagio-input-textarea defaults-input"
                                                  name="XAGIO_SEO_DEFAULT_POST_TYPES[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag(@$post_types[$post_type]['XAGIO_SEO_DESCRIPTION']))); ?></textarea>

                                        <div class="xagio-save-changes-holder xagio-margin-top-large">
                                            <div class="xagio-slider-container">
                                                <input type="hidden" class="defaults-input"
                                                       name="XAGIO_SEO_DEFAULT_POST_TYPES[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_ROBOTS]"
                                                       id="ps_seo_slider-<?php echo esc_attr($post_type); ?>"
                                                       value="<?php echo (@$post_types[$post_type]['XAGIO_SEO_ROBOTS'] == 1) ? 1 : 0; ?>">
                                                <div class="xagio-slider-frame">
                                                    <span class="xagio-slider-button <?php echo (@$post_types[$post_type]['XAGIO_SEO_ROBOTS'] == 1) ? 'on' : ''; ?>"
                                                          data-element="ps_seo_slider-<?php echo esc_attr($post_type); ?>"></span>
                                                </div>
                                                <p class="xagio-slider-label">Don't Index & Follow <i
                                                            class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                                            data-xagio-title="Do not index these kind of post types but add meta robots follow to them."></i>
                                                </p>
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php } ?>

                    <?php $miscellaneous = get_option('XAGIO_SEO_DEFAULT_MISCELLANEOUS'); ?>
                    <?php $special_pages = [
                        'search',
                        'author',
                        'archive',
                        'archive_post',
                        'not_found'
                    ]; ?>

                    <?php foreach ($special_pages as $post_type) {
                        /* Code for get post label from post object */
                        $post_name = ucfirst(str_replace("_", " ", $post_type));
                        ?>
                        <div class="xagio-accordion xagio-margin-bottom-medium">
                            <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                <span><?php echo esc_html(ucfirst($post_name)); ?></span><i
                                        class="xagio-icon xagio-icon-arrow-down"></i></h3>
                            <div class="xagio-accordion-content">
                                <div>
                                    <div class="xagio-accordion-panel">
                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input"
                                               name="XAGIO_SEO_DEFAULT_MISCELLANEOUS[<?php echo esc_attr($post_type) ?>][XAGIO_SEO_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$miscellaneous[$post_type]['XAGIO_SEO_TITLE']))); ?>"/>
                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5" class="xagio-input-textarea defaults-input"
                                                  name="XAGIO_SEO_DEFAULT_MISCELLANEOUS[<?php echo esc_attr($post_type) ?>][XAGIO_SEO_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag(@$miscellaneous[$post_type]['XAGIO_SEO_DESCRIPTION']))); ?></textarea>

                                        <div class="xagio-save-changes-holder xagio-margin-top-large">
                                            <div class="xagio-slider-container">
                                                <input type="hidden" class="defaults-input"
                                                       name="XAGIO_SEO_DEFAULT_MISCELLANEOUS[<?php echo esc_attr($post_type) ?>][XAGIO_SEO_ROBOTS]"
                                                       id="ps_seo_slider-<?php echo esc_attr($post_type) ?>"
                                                       value="<?php echo (@$miscellaneous[$post_type]['XAGIO_SEO_ROBOTS'] == 1) ? 1 : 0; ?>">
                                                <div class="xagio-slider-frame">
                                                    <span class="xagio-slider-button <?php echo (@$miscellaneous['search']['XAGIO_SEO_ROBOTS'] == 1) ? 'on' : ''; ?>"
                                                          data-element="ps_seo_slider-<?php echo esc_attr($post_type) ?>"></span>
                                                </div>
                                                <p class="xagio-slider-label">Don't Index & Follow <i
                                                            class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                                            data-xagio-title="Do not index these kind of post types but add meta robots follow to them."></i>
                                                </p>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php } ?>
                </div>
                <div class="xagio-column-2">
                    <div class="xagio-accordion xagio-margin-bottom-medium">
                        <h3 class="xagio-accordion-title xagio-accordion-panel-title"><span>Pages</span><i
                                    class="xagio-icon xagio-icon-arrow-down"></i></h3>
                        <div class="xagio-accordion-content">
                            <div>
                                <div class="xagio-accordion-panel">
                                    <!-- Title -->
                                    <h3 class="pop">Title</h3>
                                    <input type="text" class="xagio-input-text-mini defaults-input"
                                           name="XAGIO_SEO_DEFAULT_POST_TYPES[page][XAGIO_SEO_TITLE]"
                                           value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$post_types['page']['XAGIO_SEO_TITLE']))); ?>"/>
                                    <!-- Description -->
                                    <h3 class="pop">Description</h3>
                                    <textarea rows="6" class="xagio-input-textarea defaults-input"
                                              name="XAGIO_SEO_DEFAULT_POST_TYPES[page][XAGIO_SEO_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag(@$post_types['page']['XAGIO_SEO_DESCRIPTION']))); ?></textarea>

                                    <div class="xagio-save-changes-holder xagio-margin-top-large">
                                        <div class="xagio-slider-container">
                                            <input type="hidden" class="defaults-input"
                                                   name="XAGIO_SEO_DEFAULT_POST_TYPES[page][XAGIO_SEO_ROBOTS]"
                                                   id="ps_seo_slider-page"
                                                   value="<?php echo (@$post_types['page']['XAGIO_SEO_ROBOTS'] == 1) ? 1 : 0; ?>">
                                            <div class="xagio-slider-frame">
                                                <span class="xagio-slider-button <?php echo (@$post_types['page']['XAGIO_SEO_ROBOTS'] == 1) ? 'on' : ''; ?>"
                                                      data-element="ps_seo_slider-page"></span>
                                            </div>
                                            <p class="xagio-slider-label">Don't Index & Follow <i
                                                        class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                                        data-xagio-title="Do not index these kind of post types but add meta robots follow to them."></i>
                                            </p>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php $taxonomies = get_option('XAGIO_SEO_DEFAULT_TAXONOMIES'); ?>
                    <?php foreach (XAGIO_MODEL_SEO::getAllTaxonomies() as $taxonomy) {
                        // Extract the taxonomy real name
                        $tax = get_taxonomy($taxonomy);
                        ?>

                        <div class="xagio-accordion xagio-margin-bottom-medium">
                            <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                <span><?php echo esc_html(ucfirst($taxonomy)); ?></span><i
                                        class="xagio-icon xagio-icon-arrow-down"></i></h3>
                            <div class="xagio-accordion-content">
                                <div>
                                    <div class="xagio-accordion-panel">
                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input"
                                               name="XAGIO_SEO_DEFAULT_TAXONOMIES[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag(@$taxonomies[$taxonomy]['XAGIO_SEO_TITLE']))); ?>"/>
                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5" class="xagio-input-textarea defaults-input"
                                                  name="XAGIO_SEO_DEFAULT_TAXONOMIES[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag(@$taxonomies[$taxonomy]['XAGIO_SEO_DESCRIPTION']))); ?></textarea>

                                        <div class="xagio-save-changes-holder xagio-margin-top-large">
                                            <div class="xagio-slider-container">
                                                <input type="hidden" class="defaults-input"
                                                       name="XAGIO_SEO_DEFAULT_TAXONOMIES[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_ROBOTS]"
                                                       id="ps_seo_slider-<?php echo esc_attr($taxonomy); ?>"
                                                       value="<?php echo (@$taxonomies[$taxonomy]['XAGIO_SEO_ROBOTS'] == 1) ? 1 : 0; ?>">
                                                <div class="xagio-slider-frame">
                                                    <span class="xagio-slider-button <?php echo (@$taxonomies[$taxonomy]['XAGIO_SEO_ROBOTS'] == 1) ? 'on' : ''; ?>"
                                                          data-element="ps_seo_slider-<?php echo esc_attr($taxonomy); ?>"></span>
                                                </div>
                                                <p class="xagio-slider-label">Don't Index & Follow <i
                                                            class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip
                                                            data-xagio-title="Do not index these kind of post types but add meta robots follow to them."></i>
                                                </p>
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

            </div>


        </div>

        <!-- Scripts -->
        <div class="xagio-tab-content">

            <div class="xagio-2-column-grid xagio-2-column-grid-scripts">
                <div class="xagio-column-1">
                    <div class="xagio-panel">
                        <h5 class="xagio-panel-title">Global Scripts</h5>

                        <h3 class="pop">Header</h3>
                        <textarea id="XAGIO_SEO_GLOBAL_SCRIPTS_HEAD"
                                  name="XAGIO_SEO_GLOBAL_SCRIPTS_HEAD"><?php echo esc_textarea(stripslashes_deep(get_option('XAGIO_SEO_GLOBAL_SCRIPTS_HEAD'))); ?></textarea>
                        <h3 class="pop">Body</h3>
                        <textarea id="XAGIO_SEO_GLOBAL_SCRIPTS_BODY"
                                  name="XAGIO_SEO_GLOBAL_SCRIPTS_BODY"><?php echo esc_textarea(stripslashes_deep(get_option('XAGIO_SEO_GLOBAL_SCRIPTS_BODY'))); ?></textarea>
                        <h3 class="pop">Footer</h3>
                        <textarea id="XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER"
                                  name="XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER"><?php echo esc_textarea(stripslashes_deep(get_option('XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER'))); ?></textarea>

                        <div class="xagio-flex-right xagio-margin-top-large">
                            <button type="button" class="xagio-button xagio-button-primary xagio-save-scripts"><i
                                        class="xagio-icon xagio-icon-check"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
                <div class="xagio-column-2">
                    <div class="xagio-panel">
                        <h5 class="xagio-panel-title">Webmaster Scripts</h5>

                        <h3 class="pop">Google</h3>
                        <input type="text" class="xagio-input-text-mini verification-input"
                               name="XAGIO_SEO_VERIFICATION_GOOGLE" placeholder="eg. 1234567890"
                               value="<?php echo esc_html(get_option('XAGIO_SEO_VERIFICATION_GOOGLE')); ?>"/>
                        <h3 class="pop">Bing</h3>
                        <input type="text" class="xagio-input-text-mini verification-input"
                               name="XAGIO_SEO_VERIFICATION_BING" placeholder="eg. 1234567890"
                               value="<?php echo esc_attr(get_option('XAGIO_SEO_VERIFICATION_BING')); ?>"/>
                        <h3 class="pop">Google Analytics</h3>
                        <input type="text" class="xagio-input-text-mini verification-input"
                               name="XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS" placeholder="eg. UA-57398293-12"
                               value="<?php echo esc_attr(get_option('XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS')); ?>"/>
                        <h3 class="pop">Google Analytics 4</h3>
                        <input type="text" class="xagio-input-text-mini verification-input"
                               name="XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS_4" placeholder="eg. G-QBXBB3DWY8"
                               value="<?php echo esc_attr(get_option('XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS_4')); ?>"/>
                        <h3 class="pop">Google Tag Manager (Head)</h3>
                        <!-- Google Tag Head -->
                        <textarea class="xagio-input-textarea verification-input" rows="5"
                                  name="XAGIO_SEO_VERIFICATION_GOOGLE_TAG_HEAD"
                                  placeholder="eg. <!-- Google Tag Manager --> etc. etc."><?php echo esc_textarea(stripslashes_deep(get_option('XAGIO_SEO_VERIFICATION_GOOGLE_TAG_HEAD'))); ?></textarea>
                        <h3 class="pop">Google Tag Manager (Body)</h3>
                        <!-- Google Tag Body -->
                        <textarea class="xagio-input-textarea verification-input" rows="5"
                                  name="XAGIO_SEO_VERIFICATION_GOOGLE_TAG_BODY"
                                  placeholder="eg. <!-- Google Tag Manager (noscript) --> etc. etc."><?php echo esc_textarea(stripslashes_deep(get_option('XAGIO_SEO_VERIFICATION_GOOGLE_TAG_BODY'))); ?></textarea>
                        <h3 class="pop">Pintrest</h3>
                        <input type="text" class="xagio-input-text-mini verification-input"
                               name="XAGIO_SEO_VERIFICATION_PINTEREST" placeholder="eg. 1234567890"
                               value="<?php echo esc_attr(get_option('XAGIO_SEO_VERIFICATION_PINTEREST')); ?>"/>
                        <h3 class="pop">Yandex</h3>
                        <input type="text" class="xagio-input-text-mini verification-input"
                               name="XAGIO_SEO_VERIFICATION_YANDEX" placeholder="eg. 1234567890"
                               value="<?php echo esc_attr(get_option('XAGIO_SEO_VERIFICATION_YANDEX')); ?>"/>

                        <div class="xagio-flex-right xagio-margin-top-large">
                            <button type="button" class="xagio-button xagio-button-primary xagio-save-webmaster"><i
                                        class="xagio-icon xagio-icon-check"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <dialog id="viewShortcodes" class="xagio-modal">
        <div class="xagio-modal-header">
            <h3 class="xagio-modal-title">
                <i class="xagio-icon xagio-icon-code"></i> Shortcode List
            </h3>
            <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
        </div>
        <div class="xagio-modal-body">
            <div class="xagio-alert xagio-alert-primary xagio-margin-bottom-medium">
                <i class="xagio-icon xagio-icon-info"></i> Did you know that you can use shortcodes inside your schema
                properties? Copy and paste one of the shortcodes from below and see how it works for yourself.
            </div>

            <table class="uk-table uk-table-hover table-shortcodes">
                <tbody>
                <tr>
                    <td class="shortcode-cell">%%sitename%%</td>
                    <td>The site’s name</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%siteurl%%</td>
                    <td>The site’s url</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%currurl%%</td>
                    <td>The current url</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%tagline%%</td>
                    <td>The site’s tagline / description</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%sep%%</td>
                    <td>The separator defined in your SEO settings</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%title%%</td>
                    <td>Replaced with the title of the post/page</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%parent_title%%</td>
                    <td>Replaced with the title of the parent page of the current page</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%term_title%%</td>
                    <td>Replaced with the term name</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%date%%</td>
                    <td>Replaced with the date of the post/page</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%pretty_date%%</td>
                    <td>Replaced with the date of the post/page in format ex. June 2017</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%search_query%%</td>
                    <td>Replaced with the current search query</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%author_name%%</td>
                    <td>Replaced with author's name</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%ps_seo_title%%</td>
                    <td>Replaced with Xagio SEO Title</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%ps_seo_description%%</td>
                    <td>Replaced with Xagio SEO Description</td>
                </tr>

                <?php include_once(ABSPATH . 'wp-admin/includes/plugin.php'); ?>
                <?php if (!is_plugin_active('wpglow-builder/wpglow-builder.php')) { ?>
                    <tr>
                        <td class="shortcode-cell">%%excerpt%%</td>
                        <td>Replaced with the post/page excerpt</td>
                    </tr>
                <?php } ?>

                <tr>
                    <td class="shortcode-cell">%%tag%%</td>
                    <td>Replaced with the current tag/tags</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%category%%</td>
                    <td>Replaced with the post categories (comma separated)</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%category_primary%%</td>
                    <td>Replaced with the primary category of the post/page</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%current_year%%</td>
                    <td>Replaced with the current year ex. (<?php echo esc_html(gmdate("Y")); ?>)</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%current_month%%</td>
                    <td>Replaced with the current month ex. (<?php echo esc_html(gmdate("m")); ?>)</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%current_day_numerical%%</td>
                    <td>Replaced with the current day (numerical) ex. (<?php echo esc_html(gmdate("d")); ?>)</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%current_day_textual%%</td>
                    <td>Replaced with the current day (textual) ex. (<?php echo esc_html(gmdate("l")); ?>)</td>
                </tr>

                <tr>
                    <td class="shortcode-cell">%%current_date_DD_MM_YYYY%%</td>
                    <td>Replaced with the current date ex. (<?php echo esc_html(gmdate("d_m_y")); ?>)</td>
                </tr>

                </tbody>
            </table>

            <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-large">
                <button type="button" class="xagio-button xagio-button-primary" data-xagio-close-modal><i
                            class="xagio-icon xagio-icon-close"></i> Close
                </button>
            </div>
        </div>
    </dialog>

</div> <!-- .wrap -->