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

$xagio_post_types = get_option('XAGIO_SEO_DEFAULT_POST_TYPES');
$xagio_og         = get_option('XAGIO_SEO_DEFAULT_OG');
$xagio_ogs        = [
    'homepage'     => 'Homepage',
    'post'         => 'Posts',
    'page'         => 'Pages',
    'search'       => 'Search',
    'author'       => 'Author',
    'archive'      => 'Archive',
    'archive_post' => 'Archive Post',
    'not_found'    => 'Not Found'
];
$XAGIO_MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');
?>

<div class="xagio-main-header xagio-main-header-big-gaps">
    <img class="logo-image repo-xagio" src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        SEO Settings
    </h2>

    <?php if(isset($XAGIO_MEMBERSHIP_INFO["membership"]) && $XAGIO_MEMBERSHIP_INFO["membership"] === "Xagio AI Free") { ?>
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
        <li><a href="">Profiles</a></li>
        <li><a href="">Open Graph</a></li>
        <li><a href="">Taxonomies</a></li>
        <li><a href="">Scripts</a></li>
        <li><a href="">LLMs</a></li>
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

                                <!-- Dont Index Subpages -->
                                <div class="xagio-slider-container">
                                    <input type="hidden" name="XAGIO_DONT_INDEX_SUBPAGES"
                                           id="XAGIO_DONT_INDEX_SUBPAGES"
                                           value="<?php echo XAGIO_DONT_INDEX_SUBPAGES ? 1 : 0; ?>"/>
                                    <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button xagio-slider-button-settings <?php echo XAGIO_DONT_INDEX_SUBPAGES ? 'on' : ''; ?>"
                                              data-element="XAGIO_DONT_INDEX_SUBPAGES"></span>
                                    </div>
                                    <p class="xagio-slider-label">Don't Index Sub-pages <i
                                                class="xagio-icon xagio-icon-info" data-xagio-tooltip
                                                data-xagio-title="Don't Index Sub-pages"></i>
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

        <!-- Profiles -->
        <div class="xagio-tab-content">
            <div class="xagio-2-column-grid" id="profiles_container">
                <div class="xagio-column-1">

                    <!-- Contact Details -->
                    <div class="xagio-panel xagio-margin-bottom-medium">
                        <h5 class="xagio-panel-title">Contact Details</h5>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="business_name">Business Name</label>
                            <input id="business_name" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[contact_details][business_name]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="business_address">Business Address</label>
                            <input id="business_address" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[contact_details][business_address]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="business_city">Business City</label>
                            <input id="business_city" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[contact_details][business_city]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="business_state">Business State/Province</label>
                            <input id="business_state" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[contact_details][business_state]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="business_country">Business Country</label>
                            <input id="business_country" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[contact_details][business_country]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="business_zip">Business ZIP/Postal</label>
                            <input id="business_zip" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[contact_details][business_zip]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="business_phone">Business Phone</label>
                            <input id="business_phone" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[contact_details][business_phone]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="business_alternate_phone">Business Alternate Phone</label>
                            <input id="business_alternate_phone" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[contact_details][business_alternate_phone]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center">
                            <label for="business_email">Business Email</label>
                            <input id="business_email" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[contact_details][business_email]" value="">
                        </div>

                    </div>

                    <!-- Business Directories -->
                    <div class="xagio-panel xagio-margin-bottom-medium">
                        <h5 class="xagio-panel-title">Business Directories</h5>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="google_business_profile">Google Business Profile</label>
                            <input id="google_business_profile" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[business_directories][google_business_profile]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="yelp">Yelp</label>
                            <input id="yelp" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[business_directories][yelp]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="bing_places">Bing Places</label>
                            <input id="bing_places" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[business_directories][bing_places]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="bbb_org">BBB.org</label>
                            <input id="bbb_org" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[business_directories][bbb_org]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="angi">Angi</label>
                            <input id="angi" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[business_directories][angi]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="yellow_pages">Yellow Pages</label>
                            <input id="yellow_pages" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[business_directories][yellow_pages]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center">
                            <label for="foursquare">Foursquare</label>
                            <input id="foursquare" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[business_directories][foursquare]" value="">
                        </div>

                    </div>

                    <!-- Map Services -->
                    <div class="xagio-panel xagio-margin-bottom-medium">
                        <h5 class="xagio-panel-title">Map Services</h5>

                        <div class="xagio-2-column-35-65-grid xagio-align-center">
                            <label for="apple_maps_connect">Apple Maps Connect</label>
                            <input id="apple_maps_connect" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[map_services][apple_maps_connect]" value="">
                        </div>

                    </div>

                    <!-- Professional Networks -->
                    <div class="xagio-panel xagio-margin-bottom-medium">
                        <h5 class="xagio-panel-title">Professional Networks</h5>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="indeed">Indeed</label>
                            <input id="indeed" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[professional_networks][indeed]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="angel_list">AngelList</label>
                            <input id="angel_list" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[professional_networks][angel_list]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center">
                            <label for="meetup">Meetup</label>
                            <input id="meetup" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[professional_networks][meetup]" value="">
                        </div>

                    </div>

                    <!-- Industry-Specific Directories -->
                    <div class="xagio-panel">
                        <h5 class="xagio-panel-title">Industry-Specific Directories</h5>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="healthgrades">Healthgrades</label>
                            <input id="healthgrades" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[industry_specific][healthgrades]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="zocdoc">Zocdoc</label>
                            <input id="zocdoc" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[industry_specific][zocdoc]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="houzz">Houzz</label>
                            <input id="houzz" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[industry_specific][houzz]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="thumbtack">Thumbtack</label>
                            <input id="thumbtack" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[industry_specific][thumbtack]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="the_knot">The Knot</label>
                            <input id="the_knot" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[industry_specific][the_knot]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="wedding_wire">WeddingWire</label>
                            <input id="wedding_wire" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[industry_specific][wedding_wire]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="lawyers_com">Lawyers.com</label>
                            <input id="lawyers_com" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[industry_specific][lawyers_com]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="avvo">Avvo</label>
                            <input id="avvo" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[industry_specific][avvo]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center">
                            <label for="clutch">Clutch</label>
                            <input id="clutch" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[industry_specific][clutch]" value="">
                        </div>
                    </div>

                </div>

                <div class="xagio-column-2">
                    <!-- Social Media -->
                    <div class="xagio-panel xagio-margin-bottom-medium">
                        <h5 class="xagio-panel-title">Social Media</h5>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="facebook">Facebook</label>
                            <input id="facebook" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[social_media][facebook]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="youtube">Youtube</label>
                            <input id="youtube" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[social_media][youtube]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="facebook">Instagram</label>
                            <input id="facebook" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[social_media][instagram]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="linkedin">Linked In</label>
                            <input id="linkedin" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[social_media][linkedin]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="x">X</label>
                            <input id="x" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[social_media][x]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="tiktok">Tiktok</label>
                            <input id="tiktok" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[social_media][tiktok]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center">
                            <label for="pinterest">Pinterest</label>
                            <input id="pinterest" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[social_media][pinterest]" value="">
                        </div>

                    </div>

                    <!-- Review Sites -->
                    <div class="xagio-panel xagio-margin-bottom-medium">
                        <h5 class="xagio-panel-title">Review Sites</h5>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="trustpilot">Trustpilot</label>
                            <input id="trustpilot" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[review_sites][trustpilot]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="glassdoor">Glassdoor</label>
                            <input id="glassdoor" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[review_sites][glassdoor]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="consumer_affairs">ConsumerAffairs</label>
                            <input id="consumer_affairs" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[review_sites][consumer_affairs]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center">
                            <label for="sitejabber">Sitejabber</label>
                            <input id="sitejabber" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[review_sites][sitejabber]" value="">
                        </div>

                    </div>

                    <!-- Local Listings -->
                    <div class="xagio-panel xagio-margin-bottom-medium">
                        <h5 class="xagio-panel-title">Local Listings</h5>

                        <div class="xagio-2-column-35-65-grid xagio-align-center">
                            <label for="trip_advisor">TripAdvisor</label>
                            <input id="trip_advisor" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[local_listing][trip_advisor]" value="">
                        </div>

                    </div>

                    <!-- E-Commerce Platforms -->
                    <div class="xagio-panel xagio-margin-bottom-medium">
                        <h5 class="xagio-panel-title">E-Commerce Platforms</h5>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="amazon_business">Amazon Business</label>
                            <input id="amazon_business" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[e_commerce][amazon_business]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="etsy">Etsy</label>
                            <input id="etsy" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[e_commerce][etsy]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="shopify">Shopify</label>
                            <input id="shopify" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[e_commerce][shopify]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="walmart_marketplace">Walmart Marketplace</label>
                            <input id="walmart_marketplace" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[e_commerce][walmart_marketplace]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center">
                            <label for="big_commerce">BigCommerce</label>
                            <input id="big_commerce" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[e_commerce][big_commerce]" value="">
                        </div>

                    </div>

                    <!-- Mobile App Directories -->
                    <div class="xagio-panel">
                        <h5 class="xagio-panel-title">Mobile App Directories</h5>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="apple_app_store">Apple App Store</label>
                            <input id="apple_app_store" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[mobile_app][apple_app_store]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="google_play_store">Google Play Store</label>
                            <input id="google_play_store" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[mobile_app][google_play_store]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center xagio-margin-bottom-small">
                            <label for="amazon_appstore">Amazon Appstore</label>
                            <input id="amazon_appstore" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[mobile_app][amazon_appstore]" value="">
                        </div>

                        <div class="xagio-2-column-35-65-grid xagio-align-center">
                            <label for="samsung_galaxy_store">Samsung Galaxy Store</label>
                            <input id="samsung_galaxy_store" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[mobile_app][samsung_galaxy_store]" value="">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Open Graph -->
        <div class="xagio-tab-content">

            <?php foreach ($xagio_ogs as $xagio_key => $title): ?>

                <div class="xagio-accordion xagio-margin-bottom-medium <?php echo $xagio_key == 'homepage' ? 'xagio-accordion-opened' : ''; ?>">
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
                                               name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($xagio_key); ?>][XAGIO_SEO_FACEBOOK_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$xagio_key]['XAGIO_SEO_FACEBOOK_TITLE'] ?? ''))); ?>"/>

                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5"
                                                  class="xagio-input-textarea defaults-input XAGIO_OG_DESCRIPTION"
                                                  name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($xagio_key); ?>][XAGIO_SEO_FACEBOOK_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$xagio_key]['XAGIO_SEO_FACEBOOK_DESCRIPTION'] ?? ''))); ?></textarea>

                                        <!-- Image -->
                                        <h3 class="pop">Image</h3>

                                        <div class="input-group">
                                            <input type="text"
                                                   class="xagio-input-text-mini defaults-input XAGIO_OG_IMAGE"
                                                   id="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($xagio_key); ?>_XAGIO_SEO_FACEBOOK_IMAGE"
                                                   name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($xagio_key); ?>][XAGIO_SEO_FACEBOOK_IMAGE]"
                                                   value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$xagio_key]['XAGIO_SEO_FACEBOOK_IMAGE'] ?? ''))); ?>"/>

                                            <button class="xagio-button xagio-button-primary xagio-select-image"
                                                    type="button"
                                                    data-target="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($xagio_key); ?>_XAGIO_SEO_FACEBOOK_IMAGE">
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
                                            <img src="<?php echo filter_var($xagio_og[$xagio_key]['XAGIO_SEO_FACEBOOK_IMAGE'] ?? '', FILTER_VALIDATE_URL) ? esc_url($xagio_og[$xagio_key]['XAGIO_SEO_FACEBOOK_IMAGE'] ?? '') : esc_url(XAGIO_URL) . 'assets/img/facebook-placeholder.webp' ?>" class="facebook-image-preview">
                                            <div class="facebook-preview-content">
                                                <div class="facebook-preview-url"><?php echo esc_url(strtoupper(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                                                <div class="facebook-preview-title"><?php echo esc_html($xagio_og[$xagio_key]['XAGIO_SEO_FACEBOOK_TITLE'] ?? ''); ?></div>
                                                <div class="facebook-preview-description"><?php echo esc_html($xagio_og[$xagio_key]['XAGIO_SEO_FACEBOOK_DESCRIPTION'] ?? ''); ?></div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="xagio-column">

                                        <h2 class="uk-margin-top">Twitter Settings</h2>

                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input XAGIO_OG_TITLE"
                                               name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($xagio_key); ?>][XAGIO_SEO_TWITTER_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$xagio_key]['XAGIO_SEO_TWITTER_TITLE'] ?? ''))); ?>"/>

                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5"
                                                  class="xagio-input-textarea defaults-input XAGIO_OG_DESCRIPTION"
                                                  name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($xagio_key); ?>][XAGIO_SEO_TWITTER_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$xagio_key]['XAGIO_SEO_TWITTER_DESCRIPTION'] ?? ''))); ?></textarea>

                                        <!-- Image -->
                                        <h3 class="pop">Image</h3>

                                        <div class="input-group">
                                            <input type="text"
                                                   class="xagio-input-text-mini defaults-input XAGIO_OG_IMAGE"
                                                   id="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($xagio_key); ?>_XAGIO_SEO_TWITTER_IMAGE"
                                                   name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($xagio_key); ?>][XAGIO_SEO_TWITTER_IMAGE]"
                                                   value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$xagio_key]['XAGIO_SEO_TWITTER_IMAGE'] ?? ''))); ?>"/>

                                            <button class="xagio-button xagio-button-primary xagio-select-image"
                                                    type="button"
                                                    data-target="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($xagio_key); ?>_XAGIO_SEO_TWITTER_IMAGE">
                                                <i class="xagio-icon xagio-icon-folder-open"></i> Browse
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
                                                    <img src="<?php echo filter_var($xagio_og[$xagio_key]['XAGIO_SEO_TWITTER_IMAGE'] ?? '', FILTER_VALIDATE_URL) ? esc_url($xagio_og[$xagio_key]['XAGIO_SEO_TWITTER_IMAGE'] ?? '') : esc_url(XAGIO_URL) . 'assets/img/twitter-placeholder.webp' ?>" class="twitter-image-preview">
                                                </div>
                                                <div class="twitter-preview-content">
                                                    <div class="twitter-preview-url"><?php echo esc_url(strtoupper(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                                                    <div class="twitter-preview-title"><?php echo esc_html($xagio_og[$xagio_key]['XAGIO_SEO_TWITTER_TITLE'] ?? ''); ?></div>
                                                    <div class="twitter-preview-description"><?php echo esc_html($xagio_og[$xagio_key]['XAGIO_SEO_TWITTER_DESCRIPTION'] ?? ''); ?></div>
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
                $xagio_post_name = (is_array($post_type) ? $post_type['label'] : $post_type);
                $post_type = (is_array($post_type) ? $post_type['name'] : $post_type);
                ?>

                <div class="xagio-accordion xagio-margin-bottom-medium">
                    <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                        <span><?php echo esc_html($xagio_post_name); ?></span>
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
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$post_type]['XAGIO_SEO_FACEBOOK_TITLE'] ?? ''))); ?>"/>

                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5" class="xagio-input-textarea defaults-input XAGIO_OG_DESCRIPTION" name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_FACEBOOK_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$post_type]['XAGIO_SEO_FACEBOOK_DESCRIPTION'] ?? ''))); ?>
                                        </textarea>

                                        <!-- Image -->
                                        <h3 class="pop">Image</h3>

                                        <div class="input-group">
                                            <input type="text"
                                                   class="xagio-input-text-mini defaults-input XAGIO_OG_IMAGE"
                                                   id="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($post_type); ?>_XAGIO_SEO_FACEBOOK_IMAGE"
                                                   name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_FACEBOOK_IMAGE]"
                                                   value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$post_type]['XAGIO_SEO_FACEBOOK_IMAGE'] ?? ''))); ?>"/>

                                            <button class="xagio-button xagio-button-primary xagio-select-image"
                                                    type="button"
                                                    data-target="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($post_type); ?>_XAGIO_SEO_FACEBOOK_IMAGE">
                                                <i class="xagio-icon xagio-icon-folder-open"></i> Browse
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
                                            <img src="<?php echo filter_var($xagio_og[$post_type]['XAGIO_SEO_FACEBOOK_IMAGE'] ?? '', FILTER_VALIDATE_URL) ? esc_url($xagio_og[$post_type]['XAGIO_SEO_FACEBOOK_IMAGE'] ?? '') : esc_url(XAGIO_URL) . 'assets/img/facebook-placeholder.webp'; ?>" class="facebook-image-preview">
                                            <div class="facebook-preview-content">
                                                <div class="facebook-preview-url"><?php echo esc_url(strtoupper(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                                                <div class="facebook-preview-title"><?php echo esc_html($xagio_og[$post_type]['XAGIO_SEO_FACEBOOK_TITLE'] ?? ''); ?></div>
                                                <div class="facebook-preview-description"><?php echo esc_html($xagio_og[$post_type]['XAGIO_SEO_FACEBOOK_DESCRIPTION'] ?? ''); ?></div>
                                            </div>
                                        </div>


                                    </div>
                                    <div class="xagio-column">

                                        <h2 class="uk-margin-top">Twitter Settings</h2>

                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input XAGIO_OG_TITLE"
                                               name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_TWITTER_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$post_type]['XAGIO_SEO_TWITTER_TITLE'] ?? ''))); ?>"/>

                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5"
                                                  class="xagio-input-textarea defaults-input XAGIO_OG_DESCRIPTION"
                                                  name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_TWITTER_DESCRIPTION]">
                                                <?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$post_type]['XAGIO_SEO_TWITTER_DESCRIPTION'] ?? ''))); ?>
                                                </textarea>

                                        <!-- Image -->
                                        <h3 class="pop">Image</h3>

                                        <div class="input-group">
                                            <input type="text"
                                                   class="xagio-input-text-mini defaults-input XAGIO_OG_IMAGE"
                                                   id="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($post_type); ?>_XAGIO_SEO_TWITTER_IMAGE"
                                                   name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_TWITTER_IMAGE]"
                                                   value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$post_type]['XAGIO_SEO_TWITTER_IMAGE'] ?? ''))); ?>"/>

                                            <button class="xagio-button xagio-button-primary xagio-select-image"
                                                    type="button"
                                                    data-target="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($post_type); ?>_XAGIO_SEO_TWITTER_IMAGE">
                                                <i class="xagio-icon xagio-icon-folder-open"></i> Browse
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
                                                    <img src="<?php echo filter_var($xagio_og[$post_type]['XAGIO_SEO_TWITTER_IMAGE'] ?? '', FILTER_VALIDATE_URL) ? esc_url($xagio_og[$post_type]['XAGIO_SEO_TWITTER_IMAGE'] ?? '') : esc_url(XAGIO_URL) . 'assets/img/twitter-placeholder.webp'; ?>" class="twitter-image-preview">
                                                </div>
                                                <div class="twitter-preview-content">
                                                    <div class="twitter-preview-url"><?php echo esc_url(strtoupper(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                                                    <div class="twitter-preview-title"><?php echo esc_html($xagio_og[$post_type]['XAGIO_SEO_TWITTER_TITLE'] ?? ''); ?></div>
                                                    <div class="twitter-preview-description"><?php echo esc_html($xagio_og[$post_type]['XAGIO_SEO_TWITTER_DESCRIPTION'] ?? ''); ?></div>
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

            <?php $xagio_taxonomies = get_option('XAGIO_SEO_DEFAULT_TAXONOMIES'); ?>
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
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$taxonomy]['XAGIO_SEO_FACEBOOK_TITLE'] ?? ''))); ?>"/>

                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5"
                                                  class="xagio-input-textarea defaults-input XAGIO_OG_DESCRIPTION"
                                                  name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_FACEBOOK_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$taxonomy]['XAGIO_SEO_FACEBOOK_DESCRIPTION'] ?? ''))); ?></textarea>

                                        <!-- Image -->
                                        <h3 class="pop">Image</h3>

                                        <div class="input-group">
                                            <input type="text"
                                                   class="xagio-input-text-mini defaults-input XAGIO_OG_IMAGE"
                                                   id="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($taxonomy); ?>_XAGIO_SEO_FACEBOOK_IMAGE"
                                                   name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_FACEBOOK_IMAGE]"
                                                   value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$taxonomy]['XAGIO_SEO_FACEBOOK_IMAGE'] ?? ''))); ?>"/>

                                            <button class="xagio-button xagio-button-primary xagio-select-image"
                                                    type="button"
                                                    data-target="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($taxonomy); ?>_XAGIO_SEO_FACEBOOK_IMAGE">
                                                <i class="xagio-icon xagio-icon-folder-open"></i> Browse
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
                                            <img src="<?php echo filter_var($xagio_og[$taxonomy]['XAGIO_SEO_FACEBOOK_IMAGE'] ?? '', FILTER_VALIDATE_URL) ? esc_url($xagio_og[$taxonomy]['XAGIO_SEO_FACEBOOK_IMAGE'] ?? '') : esc_url(XAGIO_URL) . 'assets/img/facebook-placeholder.webp' ?>" class="facebook-image-preview">
                                            <div class="facebook-preview-content">
                                                <div class="facebook-preview-url"><?php echo esc_url(strtoupper(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                                                <div class="facebook-preview-title"><?php echo esc_html($xagio_og[$taxonomy]['XAGIO_SEO_FACEBOOK_TITLE'] ?? ''); ?></div>
                                                <div class="facebook-preview-description"><?php echo esc_html($xagio_og[$taxonomy]['XAGIO_SEO_FACEBOOK_DESCRIPTION'] ?? ''); ?></div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="xagio-column">

                                        <h2 class="uk-margin-top">Twitter Settings</h2>

                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input XAGIO_OG_TITLE"
                                               name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_TWITTER_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$taxonomy]['XAGIO_SEO_TWITTER_TITLE'] ?? ''))); ?>"/>

                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5"
                                                  class="xagio-input-textarea defaults-input XAGIO_OG_DESCRIPTION"
                                                  name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_TWITTER_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$taxonomy]['XAGIO_SEO_TWITTER_DESCRIPTION'] ?? ''))); ?></textarea>

                                        <!-- Image -->
                                        <h3 class="pop">Image</h3>

                                        <div class="input-group">
                                            <input type="text"
                                                   class="xagio-input-text-mini defaults-input XAGIO_OG_IMAGE"
                                                   id="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($taxonomy); ?>_XAGIO_SEO_TWITTER_IMAGE"
                                                   name="XAGIO_SEO_DEFAULT_OG[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_TWITTER_IMAGE]"
                                                   value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_og[$taxonomy]['XAGIO_SEO_TWITTER_IMAGE'] ?? ''))); ?>"/>

                                            <button class="xagio-button xagio-button-primary xagio-select-image"
                                                    type="button"
                                                    data-target="XAGIO_SEO_DEFAULT_OG_<?php echo esc_attr($taxonomy); ?>_XAGIO_SEO_TWITTER_IMAGE">
                                                <i class="xagio-icon xagio-icon-folder-open"></i> Browse
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
                                                    <img src="<?php echo filter_var($xagio_og[$taxonomy]['XAGIO_SEO_TWITTER_IMAGE'] ?? '', FILTER_VALIDATE_URL) ? esc_url($xagio_og[$taxonomy]['XAGIO_SEO_TWITTER_IMAGE'] ?? '') : esc_url(XAGIO_URL) . 'assets/img/twitter-placeholder.webp' ?>" class="twitter-image-preview">
                                                </div>
                                                <div class="twitter-preview-content">
                                                    <div class="twitter-preview-url"><?php echo esc_url(strtoupper(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                                                    <div class="twitter-preview-title"><?php echo esc_html($xagio_og[$taxonomy]['XAGIO_SEO_TWITTER_TITLE'] ?? ''); ?></div>
                                                    <div class="twitter-preview-description"><?php echo esc_html($xagio_og[$taxonomy]['XAGIO_SEO_TWITTER_DESCRIPTION'] ?? ''); ?></div>
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
                                   value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_post_types['homepage']['XAGIO_SEO_TITLE'] ?? ''))); ?>"/>

                            <!-- Description -->
                            <h3 class="pop">Description</h3>
                            <textarea rows="5" class="xagio-input-textarea defaults-input"
                                      name="XAGIO_SEO_DEFAULT_POST_TYPES[homepage][XAGIO_SEO_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_post_types['homepage']['XAGIO_SEO_DESCRIPTION'] ?? ''))); ?></textarea>

                            <div class="xagio-save-changes-holder xagio-margin-top-large">
                                <div class="xagio-slider-container">
                                    <input type="hidden" class="defaults-input"
                                           name="XAGIO_SEO_DEFAULT_POST_TYPES[homepage][XAGIO_SEO_ROBOTS]"
                                           id="ps_seo_slider-homepage"
                                           value="<?php echo ($xagio_post_types['homepage']['XAGIO_SEO_ROBOTS'] ?? 0) == 1 ? 1 : 0; ?>">
                                    <div class="xagio-slider-frame">
            <span class="xagio-slider-button <?php echo ($xagio_post_types['homepage']['XAGIO_SEO_ROBOTS'] ?? 0) == 1 ? 'on' : ''; ?>"
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
                                           value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_post_types['post']['XAGIO_SEO_TITLE'] ?? ''))); ?>"/>
                                    <!-- Description -->
                                    <h3 class="pop">Description</h3>
                                    <textarea rows="6" class="xagio-input-textarea defaults-input"
                                              name="XAGIO_SEO_DEFAULT_POST_TYPES[post][XAGIO_SEO_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_post_types['post']['XAGIO_SEO_DESCRIPTION'] ?? ''))); ?></textarea>

                                    <div class="xagio-save-changes-holder xagio-margin-top-large">
                                        <div class="xagio-slider-container">
                                            <input type="hidden" class="defaults-input"
                                                   name="XAGIO_SEO_DEFAULT_POST_TYPES[post][XAGIO_SEO_ROBOTS]"
                                                   id="ps_seo_slider-post"
                                                   value="<?php echo ($xagio_post_types['post']['XAGIO_SEO_ROBOTS'] ?? 0) == 1 ? 1 : 0; ?>">
                                            <div class="xagio-slider-frame">
                <span class="xagio-slider-button <?php echo ($xagio_post_types['post']['XAGIO_SEO_ROBOTS'] ?? 0) == 1 ? 'on' : ''; ?>"
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
                        $xagio_post_name = (is_array($post_type) ? $post_type['label'] : $post_type);
                        $post_type = (is_array($post_type) ? $post_type['name'] : $post_type);
                        ?>
                        <div class="xagio-accordion xagio-margin-bottom-medium">
                            <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                <span><?php echo esc_html(ucfirst($xagio_post_name)); ?></span><i
                                        class="xagio-icon xagio-icon-arrow-down"></i></h3>
                            <div class="xagio-accordion-content">
                                <div>
                                    <div class="xagio-accordion-panel">
                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input"
                                               name="XAGIO_SEO_DEFAULT_POST_TYPES[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_post_types[$post_type]['XAGIO_SEO_TITLE'] ?? ''))); ?>"/>
                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="6" class="xagio-input-textarea defaults-input"
                                                  name="XAGIO_SEO_DEFAULT_POST_TYPES[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_DESCRIPTION]">
                                                <?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_post_types[$post_type]['XAGIO_SEO_DESCRIPTION'] ?? ''))); ?>
                                                </textarea>

                                        <div class="xagio-save-changes-holder xagio-margin-top-large">
                                            <div class="xagio-slider-container">
                                                <input type="hidden" class="defaults-input"
                                                       name="XAGIO_SEO_DEFAULT_POST_TYPES[<?php echo esc_attr($post_type); ?>][XAGIO_SEO_ROBOTS]"
                                                       id="ps_seo_slider-<?php echo esc_attr($post_type); ?>"
                                                       value="<?php echo (($xagio_post_types[$post_type]['XAGIO_SEO_ROBOTS'] ?? 0) == 1) ? 1 : 0; ?>">
                                                <div class="xagio-slider-frame">
                                                    <span class="xagio-slider-button <?php echo (($xagio_post_types[$post_type]['XAGIO_SEO_ROBOTS'] ?? 0) == 1) ? 'on' : ''; ?>" data-element="ps_seo_slider-<?php echo esc_attr($post_type); ?>"></span>
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

                    <?php $xagio_miscellaneous = get_option('XAGIO_SEO_DEFAULT_MISCELLANEOUS'); ?>
                    <?php $xagio_special_pages = [
                        'search',
                        'author',
                        'archive',
                        'archive_post',
                        'not_found'
                    ]; ?>

                    <?php foreach ($xagio_special_pages as $post_type) {
                        /* Code for get post label from post object */
                        $xagio_post_name = ucfirst(str_replace("_", " ", $post_type));
                        ?>
                        <div class="xagio-accordion xagio-margin-bottom-medium">
                            <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                <span><?php echo esc_html(ucfirst($xagio_post_name)); ?></span><i
                                        class="xagio-icon xagio-icon-arrow-down"></i></h3>
                            <div class="xagio-accordion-content">
                                <div>
                                    <div class="xagio-accordion-panel">
                                        <!-- Title -->
                                        <h3 class="pop">Title</h3>
                                        <input type="text" class="xagio-input-text-mini defaults-input"
                                               name="XAGIO_SEO_DEFAULT_MISCELLANEOUS[<?php echo esc_attr($post_type) ?>][XAGIO_SEO_TITLE]"
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_miscellaneous[$post_type]['XAGIO_SEO_TITLE'] ?? ''))); ?>"/>
                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5" class="xagio-input-textarea defaults-input"
                                                  name="XAGIO_SEO_DEFAULT_MISCELLANEOUS[<?php echo esc_attr($post_type) ?>][XAGIO_SEO_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_miscellaneous[$post_type]['XAGIO_SEO_DESCRIPTION'] ?? ''))); ?></textarea>

                                        <div class="xagio-save-changes-holder xagio-margin-top-large">
                                            <div class="xagio-slider-container">
                                                <input type="hidden" class="defaults-input"
                                                       name="XAGIO_SEO_DEFAULT_MISCELLANEOUS[<?php echo esc_attr($post_type) ?>][XAGIO_SEO_ROBOTS]"
                                                       id="ps_seo_slider-<?php echo esc_attr($post_type) ?>"
                                                       value="<?php echo ($xagio_miscellaneous[$post_type]['XAGIO_SEO_ROBOTS'] ?? 0) == 1 ? 1 : 0; ?>">
                                                <div class="xagio-slider-frame">
                <span class="xagio-slider-button <?php echo ($xagio_miscellaneous[$post_type]['XAGIO_SEO_ROBOTS'] ?? 0) == 1 ? 'on' : ''; ?>"
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
                                           value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_post_types['page']['XAGIO_SEO_TITLE'] ?? ''))); ?>"/>
                                    <!-- Description -->
                                    <h3 class="pop">Description</h3>
                                    <textarea rows="6" class="xagio-input-textarea defaults-input"
                                              name="XAGIO_SEO_DEFAULT_POST_TYPES[page][XAGIO_SEO_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_post_types['page']['XAGIO_SEO_DESCRIPTION'] ?? ''))); ?></textarea>

                                    <div class="xagio-save-changes-holder xagio-margin-top-large">
                                        <div class="xagio-slider-container">
                                            <input type="hidden" class="defaults-input"
                                                   name="XAGIO_SEO_DEFAULT_POST_TYPES[page][XAGIO_SEO_ROBOTS]"
                                                   id="ps_seo_slider-page"
                                                   value="<?php echo ($xagio_post_types['page']['XAGIO_SEO_ROBOTS'] ?? 0) == 1 ? 1 : 0; ?>">
                                            <div class="xagio-slider-frame">
                <span class="xagio-slider-button <?php echo ($xagio_post_types['page']['XAGIO_SEO_ROBOTS'] ?? 0) == 1 ? 'on' : ''; ?>"
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

                    <?php $xagio_taxonomies = get_option('XAGIO_SEO_DEFAULT_TAXONOMIES'); ?>
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
                                               value="<?php echo esc_attr(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_taxonomies[$taxonomy]['XAGIO_SEO_TITLE'] ?? ''))); ?>"/>
                                        <!-- Description -->
                                        <h3 class="pop">Description</h3>
                                        <textarea rows="5" class="xagio-input-textarea defaults-input"
                                                  name="XAGIO_SEO_DEFAULT_TAXONOMIES[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_DESCRIPTION]"><?php echo esc_textarea(stripslashes_deep(xagio_stripUnwantedCharTag($xagio_taxonomies[$taxonomy]['XAGIO_SEO_DESCRIPTION'] ?? ''))); ?></textarea>

                                        <div class="xagio-save-changes-holder xagio-margin-top-large">
                                            <div class="xagio-slider-container">
                                                <input type="hidden" class="defaults-input"
                                                       name="XAGIO_SEO_DEFAULT_TAXONOMIES[<?php echo esc_attr($taxonomy); ?>][XAGIO_SEO_ROBOTS]"
                                                       id="ps_seo_slider-<?php echo esc_attr($taxonomy); ?>"
                                                       value="<?php echo ($xagio_taxonomies[$taxonomy]['XAGIO_SEO_ROBOTS'] ?? 0) == 1 ? 1 : 0; ?>">
                                                <div class="xagio-slider-frame">
                <span class="xagio-slider-button <?php echo ($xagio_taxonomies[$taxonomy]['XAGIO_SEO_ROBOTS'] ?? 0) == 1 ? 'on' : ''; ?>"
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
                            <button type="button" class="xagio-button xagio-button-primary xagio-save-scripts"><i
                                        class="xagio-icon xagio-icon-check"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- LLMs.txt -->
        <div class="xagio-tab-content">
            <div class="xagio-accordion xagio-margin-bottom-medium xagio-accordion-opened">
                <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                    <span>LLMs.txt</span>
                    <i class="xagio-icon xagio-icon-arrow-down"></i>
                </h3>
                <div class="xagio-accordion-content">
                    <div>
                        <div class="xagio-accordion-panel">
                            <div class="xagio-alert xagio-alert-primary">
                                <i class="xagio-icon xagio-icon-info"></i> <kbd>llms.txt</kbd> is a proposed standard file format designed to help large language models (LLMs) better understand and process website content. It's a simple, text-based file (usually Markdown) that sits in the root directory of a website and provides a structured, prioritized overview of the site's most important information.
                            </div>

                            <?php
                            // Load & defaults
                            $XAGIO_LLMS_OPTION = 'XAGIO_LLMS_TXT_CONFIG';

                            $xagio_llms_config = get_option($XAGIO_LLMS_OPTION, [
                                'rules' => [
                                    ['user_agent' => '*', 'allow' => [], 'disallow' => ['/wp-admin/']]
                                ],
                                'extra' => ''
                            ]);

                            $xagio_llms_preview = esc_textarea(XAGIO_MODEL_LLMS::generate_text($xagio_llms_config));

                            // Common LLM crawler presets
                            $xagio_llms_presets = [
                                'GPTBot'             => 'OpenAI',
                                'ChatGPT-User'       => 'OpenAI Fetch',
                                'Google-Extended'    => 'Google Licensing',
                                'GoogleOther'        => 'Google Non-Search',
                                'ClaudeBot'          => 'Anthropic',
                                'Claude-Web'         => 'Anthropic Web',
                                'PerplexityBot'      => 'Perplexity',
                                'CCBot'              => 'Common Crawl',
                                'Amazonbot'          => 'Amazon',
                                'Meta-ExternalAgent' => 'Meta',
                                'FacebookBot'        => 'Facebook/Meta',
                                'Bytespider'         => 'ByteDance',
                                'DataForSeoBot'      => 'DataForSeo',
                            ];
                            ?>

                            <form id="xagio-llms-form" class="ts">
                                <?php wp_nonce_field('xagio_llms_save', 'xagio_llms_nonce'); ?>

                                <input type="hidden" name="action" value="xagio_llms_save" />

                                <div class="xagio-2-column-grid xagio-gap-large xagio-margin-bottom-large xagio-margin-top-medium">
                                    <div class="xagio-column">
                                        <h3 class="pop">Rules</h3>
                                        <p class="xagio-gray-label">
                                            Add a row per crawler. Paths are prefixes (like <code>/private/</code>). Use <code>*</code> to target all crawlers.
                                        </p>

                                        <table class="widefat fixed striped xagio-margin-top-small" id="xagio-llms-rules">
                                            <thead>
                                            <tr>
                                                <th style="width:220px">User-Agent</th>
                                                <th>Allow (one per line)</th>
                                                <th>Disallow (one per line)</th>
                                                <th style="width:40px"></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach (($xagio_llms_config['rules'] ?? []) as $xagio_i => $xagio_r): ?>
                                                <tr>
                                                    <td>
                                                        <input type="text" name="ua[]" class="xagio-input-text-mini" value="<?php echo esc_attr($xagio_r['user_agent'] ?? ''); ?>" list="xagio-llms-ua" />
                                                        <div class="xagio-gray-label"><?php echo esc_html($xagio_llms_presets[$xagio_r['user_agent'] ?? ''] ?? ''); ?></div>
                                                    </td>
                                                    <td>
                                                        <textarea placeholder="eg. /my-article/" name="allow[]" rows="4" class="xagio-input-textarea"><?php echo esc_textarea(implode("\n", (array)($xagio_r['allow'] ?? []))); ?></textarea>
                                                    </td>
                                                    <td>
                                                        <textarea placeholder="eg. /wp-admin/" name="disallow[]" rows="4" class="xagio-input-textarea"><?php echo esc_textarea(implode("\n", (array)($xagio_r['disallow'] ?? []))); ?></textarea>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="link-delete-row" title="Remove">✕</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>

                                        <p class="xagio-flex xagio-flex-gap-small xagio-margin-top-small">
                                            <button type="button" class="xagio-button xagio-button-primary" id="xagio-llms-add-row">+ Add Crawler</button>
                                            <button type="button" class="xagio-button xagio-button-primary" id="xagio-llms-add-presets">Add Common Presets</button>
                                        </p>

                                        <datalist id="xagio-llms-ua">
                                            <option value="*">All crawlers</option>
                                            <?php foreach ($xagio_llms_presets as $xagio_ua => $xagio_desc): ?>
                                                <option value="<?php echo esc_attr($xagio_ua); ?>"><?php echo esc_html($xagio_desc); ?></option>
                                            <?php endforeach; ?>
                                        </datalist>

                                        <br>

                                        <h3 class="pop xagio-margin-top-large">Extra Rules (Advanced)</h3>
                                        <textarea name="extra" rows="6" class="xagio-input-textarea" placeholder="# Any additional lines"><?php echo esc_textarea($xagio_llms_config['extra'] ?? ''); ?></textarea>

                                    </div>

                                    <div class="xagio-column">
                                        <h3 class="pop">Preview</h3>
                                        <textarea id="xagio-llms-preview" rows="10" class="xagio-input-textarea" readonly><?php echo esc_html($xagio_llms_preview); ?></textarea>
                                        <p class="xagio-gray-label">This is what will be written into <code>/llms.txt</code>.<br>
                                            You can view the live preview at <a target="_blank" href="<?php echo esc_url(get_site_url()); ?>/llms.txt"><?php echo esc_url(get_site_url()); ?>/llms.txt</a>.
                                        </p>
                                    </div>
                                </div>

                                <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-large">
                                    <button type="button" class="xagio-button xagio-button-primary" id="xagio-llms-update">
                                        <i class="xagio-icon xagio-icon-check"></i> Update Settings (don’t write file)
                                    </button>
                                    <button type="button" class="xagio-button xagio-button-primary" id="xagio-llms-save">
                                        <i class="xagio-icon xagio-icon-folder-open"></i> Save to /llms.txt
                                    </button>
                                </div>
                            </form>

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