<?php
/**
 * Type: SUBMENU
 * Page_Title: Agent X
 * Menu_Title: Agent X
 * Capability: manage_options
 * Slug: xagio-ocw
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: jquery-masonry,xagio_select2,jquery-ui-core,jquery-ui-sortable,xagio_jqcloud,xagio_tablesorter,xagio_jquery_sortable,xagio_multisortable,xagio_ocw,xagio_ajaxq
 * Css: xagio_animate,xagio_select2,xagio_ocw
 * Position: 1
 * Version: 1.0.0
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

global $wpdb;

if (class_exists('\Elementor\Plugin')) {
    \Elementor\Plugin::$instance->files_manager->clear_cache();
}

$xagio_elementor_thumbs = glob(WP_CONTENT_DIR . '/uploads/elementor/thumbs/*');
if ($xagio_elementor_thumbs) {
    foreach ($xagio_elementor_thumbs as $xagio_file) {
        wp_delete_file($xagio_file);
    }
}


$xagio_country         = get_option('XAGIO_LOCATION_DEFAULT_KEYWORD_COUNTRY', null);
$xagio_language        = get_option('XAGIO_LOCATION_DEFAULT_KEYWORD_LANGUAGE', null);
$xagio_ai_wizard_se    = get_option('XAGIO_LOCATION_DEFAULT_AI_SEARCH_ENGINE');
$xagio_rank_tracker_se = get_option('XAGIO_LOCATION_DEFAULT_SEARCH_ENGINE');

$xagio_ai_wizard_location = get_option('XAGIO_LOCATION_DEFAULT_AI_LOCATION');
if ($xagio_ai_wizard_location != null) {
    $xagio_ai_wizard_location = wp_kses_post($xagio_ai_wizard_location);
} else {
    $xagio_ai_wizard_location = '2840';
}

$xagio_rank_tracker_country   = get_option('XAGIO_LOCATION_DEFAULT_COUNTRY', 2840);
$xagio_rank_tracker_city_code = get_option('XAGIO_LOCATION_DEFAULT_CITY');
$xagio_country_list           = xagio_get_countries();
if ($xagio_rank_tracker_city_code != null) {
    $xagio_rank_tracker_city = xagio_get_city_by_code($xagio_rank_tracker_city_code);
} else {
    $xagio_rank_tracker_city = array();
}

$XAGIO_MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');

$xagio_ocw_steps = get_option('XAGIO_OCW', [
    'step' => 'not_running',
    'data' => []
]);

$xagio_admin_email = get_option('admin_email');

?>

<script>
    let siteUrl = '<?php echo esc_url(XAGIO_URL); ?>';
</script>

<div class="ocw">

    <div class="ocw-start">

        <div class="xagio-flex xagio-flex-align-center">

            <div class="xagio-width-max700">

                <img class="heading-image" src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/agent_x_white.webp"
                     alt=""/>

                <h1 class="ai-wizard-welcome">
                    Welcome to Agent X!
                </h1>

                <p class="ai-wizard-information">
                    This helps us determine what kind of website you are running and how we can use AI to generate
                    useful content for you with Xagio.
                </p>

                <div class="aiwizard-type">
                    <div class="option-picker" data-type="global">
                        <div class="ai-image-column">
                            <i class="xagio-icon xagio-icon-globe"></i>
                        </div>
                        <h3>Global</h3>
                        <div>Use for any sites where you promote a product or service</div>
                        <u>GET STARTED</u>
                    </div>
                    <div class="option-picker" data-type="local">
                        <div class="ai-image-column">
                            <i class="xagio-icon xagio-icon-map"></i>
                        </div>
                        <h3>Local</h3>
                        <div>Use for any sites that target geographic locations</div>
                        <u>GET STARTED</u>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <div class="ocw-step ocw-step-templates" style="display: none">

        <div class="xagio-flex xagio-flex-align-center">

            <div class="xagio-width-max">

                <h5 class="aiwizard-breadcrumb"><img class="ai-wizard-breadcrumb-xagio-image"
                                                     src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/agent_x_logo.webp"
                                                     alt=""/></h5>

                <div class="step-body">

                    <?php if (isset($_GET['project_id'], $_GET['project_name'])): ?>

                        <h3 class="xagio-margin-top-medium">
                            <b>Project</b>: <?php echo wp_kses_post(sanitize_text_field(wp_unslash($_GET['project_name']))); ?>
                        </h3>
                        <input type="hidden" name="selected_project" id="selected_project"
                               value="<?php echo wp_kses_post(absint(wp_unslash($_GET['project_id']))); ?>"/>

                        <div class="xagio-alert xagio-alert-primary">
                            <i class="xagio-icon xagio-icon-info"></i> This project is pre-selected from your Project
                            Planner. If you wish to cancel this selection, simply navigate again to <b>Agent X</b> in
                            your WordPress sidebar.
                        </div>

                    <?php endif; ?>

                    <div class="xagio-slider-container xagio-margin-top-medium">
                        <input type="hidden" name="XAGIO_REMOVE_PAGES" id="XAGIO_REMOVE_PAGES" value="0"/>
                        <div class="xagio-slider-frame">
                            <span class="xagio-slider-button" data-element="XAGIO_REMOVE_PAGES"></span>
                        </div>
                        <p class="xagio-slider-label">Remove existing <b>Pages</b></p>
                    </div>

                    <div class="xagio-slider-container xagio-margin-top-medium">
                        <div class="xagio-alert xagio-alert-primary">
                            <i class="xagio-icon xagio-icon-info"></i> This makes sure that you start fresh with only
                            pages from Agent X.
                        </div>
                    </div>

                    <div class="xagio-flex-space-between xagio-flex-wrap xagio-flex-gap-medium xagio-margin-top-medium">
                        <div class="xagio-slider-container" style="margin: 0;">
                            <input type="hidden" name="XAGIO_USE_TEMPLATE" id="XAGIO_USE_TEMPLATE" value="0"/>
                            <div class="xagio-slider-frame">
                                <span class="xagio-slider-button" data-element="XAGIO_USE_TEMPLATE"></span>
                            </div>
                            <p class="xagio-slider-label">Use <b>Templates</b></p>
                        </div>

                        <div class="template-credits-holder">

                        </div>
                    </div>

                    <div class="xagio-slider-container xagio-margin-top-medium">
                        <div class="xagio-alert xagio-alert-primary">
                            <i class="xagio-icon xagio-icon-info"></i> Our templates are built specifically for
                            Elementor plugin.
                            If you select a template and Elementor is not already installed on your site, our system
                            will automatically download and install the plugin before applying your chosen template.
                            <div class="xagio-ocw-steps-info">

                            </div>
                        </div>
                    </div>

                    <input type="text" class="xagio-input-text-mini search-templates search" placeholder="Search Templates..." style="display: none">

                    <div class="xagio-slider-container xagio-gutenberg-filter xagio-margin-top-small" style="display: none">
                        <input type="hidden" name="xagio_show_gutenberg" id="xagio_show_gutenberg" value="0"/>
                        <div class="xagio-slider-frame">
                            <span class="xagio-slider-button" data-element="xagio_show_gutenberg"></span>
                        </div>
                        <p class="xagio-slider-label">Show <b>Gutenberg</b></p>
                    </div>

                    <div id="templates">

                    </div>
                    <!-- Pagination controls -->
                    <div id="pagination"></div>

                </div>

                <div class="ai-wizard-buttons ai-wizard-one-button">
                    <a href="#" class="xagio-button xagio-button-outline prev-step-templates"><i
                                class="xagio-icon xagio-icon-close"></i> Cancel</a>
                    <a href="#" class="xagio-button xagio-button-primary next-templates"><i
                                class="xagio-icon xagio-icon-arrow-right"></i> Continue</a>
                </div>

            </div>

        </div>

    </div>

    <div class="ocw-step ocw-step-elementor" style="display: none">

        <div class="xagio-flex xagio-flex-align-center">

            <div class="xagio-width-max900">

                <h5 class="aiwizard-breadcrumb"><img class="ai-wizard-breadcrumb-xagio-image"
                                                     src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/agent_x_logo.webp"
                                                     alt=""/></h5>

                <div class="step-body">

                    <div id="elementor-output"></div>

                </div>

            </div>

        </div>

    </div>

    <div class="ocw-step ocw-step-profiles" style="display: none">

        <div class="xagio-flex xagio-flex-align-center">

            <div class="xagio-width-max900">

                <h5 class="aiwizard-breadcrumb"><img class="ai-wizard-breadcrumb-xagio-image"
                                                     src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/agent_x_logo.webp"
                                                     alt=""/></h5>

                <div class="step-body">

                    <div class="xagio-flex-space-between xagio-flex-wrap xagio-flex-gap-medium xagio-margin-top-medium">
                        <div class="xagio-slider-container" style="margin: 0;">
                            <input type="hidden" name="XAGIO_PROFILE_DATA" id="XAGIO_PROFILE_DATA" value="0"/>
                            <div class="xagio-slider-frame">
                                <span class="xagio-slider-button off" data-element="XAGIO_PROFILE_DATA"></span>
                            </div>
                            <p class="xagio-slider-label">Modify Profile Data</p>
                        </div>
                    </div>

                    <div class="xagio-slider-container xagio-margin-top-medium">
                        <div class="xagio-alert xagio-alert-primary">
                            <i class="xagio-icon xagio-icon-info"></i>
                            From here you can apply all the settings that will be applied globally for your website's SEO.
                        </div>
                    </div>

                    <div class="xagio-flex-column xagio-margin-top-large" id="profiles-holder" style="display: none;">
                        <!-- Contact Details -->
                        <div class="xagio-margin-bottom-medium">
                                <h5 class="profiles-title">Contact Details</h5>

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
                        <div class="xagio-margin-bottom-medium">
                                <h5 class="profiles-title">Business Directories</h5>

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
                        <div class="xagio-margin-bottom-medium">
                                <h5 class="profiles-title">Map Services</h5>

                                <div class="xagio-2-column-35-65-grid xagio-align-center">
                                    <label for="apple_maps_connect">Apple Maps Connect</label>
                                    <input id="apple_maps_connect" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[map_services][apple_maps_connect]" value="">
                                </div>

                            </div>

                        <!-- Professional Networks -->
                        <div class="xagio-margin-bottom-medium">
                                <h5 class="profiles-title">Professional Networks</h5>

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
                        <div class="xagio-margin-bottom-medium">
                                <h5 class="profiles-title">Industry-Specific Directories</h5>

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

                        <!-- Social Media -->
                        <div class="xagio-margin-bottom-medium">
                                <h5 class="profiles-title">Social Media</h5>

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
                        <div class="xagio-margin-bottom-medium">
                                <h5 class="profiles-title">Review Sites</h5>

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
                        <div class="xagio-margin-bottom-medium">
                                <h5 class="profiles-title">Local Listings</h5>

                                <div class="xagio-2-column-35-65-grid xagio-align-center">
                                    <label for="trip_advisor">TripAdvisor</label>
                                    <input id="trip_advisor" type="text" class="xagio-input-text-mini profiles_input" name="XAGIO_SEO_PROFILES[local_listing][trip_advisor]" value="">
                                </div>

                            </div>

                        <!-- E-Commerce Platforms -->
                        <div class="xagio-margin-bottom-medium">
                                <h5 class="profiles-title">E-Commerce Platforms</h5>

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
                        <div>
                                <h5 class="profiles-title">Mobile App Directories</h5>

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

                    <div class="xagio-flex-space-between xagio-flex-gap-medium xagio-margin-top-large">

                        <a href="#" class="xagio-button xagio-button-outline prev-step-profiles"><i
                                    class="xagio-icon xagio-icon-arrow-left"></i> Previous</a>
                        <a href="#" class="xagio-button xagio-button-primary next-profiles"><i
                                    class="xagio-icon xagio-icon-arrow-right"></i> Continue</a>
                    </div>

            </div>

        </div>

    </div>

    <div class="ocw-step ocw-step-1" style="display: none">

        <div class="xagio-flex xagio-flex-align-center">

            <div class="xagio-width-max900">

                <h5 class="aiwizard-breadcrumb"><img class="ai-wizard-breadcrumb-xagio-image"
                                                     src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/agent_x_logo.webp"
                                                     alt=""/>

                    <div class="xags-container-heading">

                        <div class="xags-container">
                            <div class="xags-item xrenew" data-xagio-tooltip data-xagio-tooltip-position="bottom"
                                 data-xagio-title="These are your current XAGS (xRenew)">
                                <img src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/xRenew.png" alt="xR"
                                     class="xags-icon">
                                <span class="value">0</span>
                            </div>
                            <span class="xags-divider"></span>
                            <div class="xags-item xbanks" data-xagio-tooltip data-xagio-tooltip-position="bottom"
                                 data-xagio-title="These are your current XAGS (xBank)">
                                <img src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/xBanks.png" alt="xB"
                                     class="xags-icon">
                                <span class="value">0</span>
                            </div>
                        </div>

                        <a href="https://xagio.com/store/" target="_blank"
                           class="xagio-button xagio-button-secondary"><i class="xagio-icon xagio-icon-store"></i>
                            PURCHASE XAGS</a>

                    </div>

                </h5>

                <div class="step-body">

                    <div class="keyword-research-local">
                        <div class="step-text">
                            In what <b class="with-underscore">City</b> is your Business located at?
                        </div>

                        <input type="hidden" name="main-keyword" class="main-keyword" value="">
                        <input type="hidden" id="aiwizard-type" value="local">
                        <div class="step-input">
                            <input class="xagio-input-text-mini" id="top-ten-location-text" type="text"
                                   placeholder="e.g. austin" name="location" value="">
                        </div>
                        <p class="help">You can leave this empty, however, it is always recommended to include City for
                            your
                            Businesses.</p>
                    </div>

                    <div class="step-text xagio-margin-top-medium">
                        Enter a <b class="with-underscore">Keyword</b> that best describes your Business
                    </div>

                    <div class="step-input">
                        <input class="xagio-input-text-mini top-websites-keyword" id="top-ten-keyword" type="text"
                               placeholder="e.g. pool cleaning" name="keyword" value="">
                    </div>

                    <div class="aiwizard-search-holder xagio-margin-top-medium">
                        <div>
                            <label for="top_ten_search_engine" class="step-text-secondary">Search Engine</label>
                            <select id="top_ten_search_engine" name="top_ten_search_engine"
                                    class="xagio-input-select xagio-input-select-gray"
                                    data-default="<?php echo esc_attr($xagio_ai_wizard_se) ? wp_kses_post($xagio_ai_wizard_se) : "14" ?>">
                                <option value="5820">google.com.af (Afghanistan/Arabic)</option>
                                <option value="5297">google.com.af (Afghanistan/English)</option>
                                <option value="37">google.com.af (Afghanistan/Pashto)</option>
                                <option value="38">google.com.af (Afghanistan/Persian)</option>
                                <option value="39">google.al (Albania/Albanian)</option>
                                <option value="5765">google.al (Albania/English)</option>
                                <option value="1013">search.yahoo.com (Albania/All Languages)</option>
                                <option value="828">search.yahoo.com (Albania/English)</option>
                                <option value="41">google.dz (Algeria/Arabic)</option>
                                <option value="5339">google.dz (Algeria/English)</option>
                                <option value="40">google.dz (Algeria/French)</option>
                                <option value="1014">search.yahoo.com (Algeria/All Languages)</option>
                                <option value="829">search.yahoo.com (Algeria/Arabic)</option>
                                <option value="830">search.yahoo.com (Algeria/French)</option>
                                <option value="42">google.as (American Samoa/English)</option>
                                <option value="43">google.ad (Andorra/Catalan)</option>
                                <option value="5271">google.ad (Andorra/English)</option>
                                <option value="5291">google.co.ao (Angola/English)</option>
                                <option value="44">google.co.ao (Angola/Kongo)</option>
                                <option value="45">google.co.ao (Angola/Portuguese)</option>
                                <option value="46">google.com.ai (Anguilla/English)</option>

                                <option value="47">google.com.ag (Antigua and Barbuda/English)</option>
                                <option value="1015">ar.search.yahoo.com (Argentina/All Languages)</option>
                                <option value="831">ar.search.yahoo.com (Argentina/English)</option>
                                <option value="832">ar.search.yahoo.com (Argentina/Spanish)</option>
                                <option value="962">bing.com (Argentina/All Languages)</option>
                                <option value="400">bing.com (Argentina/Spanish)</option>
                                <option value="3402">google.com.ar (Argentina/English)</option>
                                <option value="48">google.com.ar (Argentina/Espanol (Latinoamerica))</option>
                                <option value="5437">google.com.ar (Argentina/Spanish)</option>

                                <option value="49">google.am (Armenia/Armenian)</option>
                                <option value="5273">google.am (Armenia/English)</option>
                                <option value="50">google.am (Armenia/Russian)</option>
                                <option value="1016">search.yahoo.com (Armenia/All Languages)</option>
                                <option value="833">search.yahoo.com (Armenia/English)</option>
                                <option value="834">search.yahoo.com (Armenia/Russian)</option>

                                <option value="6222">google.com (Aruba/English)</option>
                                <option value="1017">au.search.yahoo.com (Australia/All Languages)</option>
                                <option value="835">au.search.yahoo.com (Australia/English)</option>
                                <option value="963">bing.com (Australia/All Languages)</option>
                                <option value="387">bing.com (Australia/English)</option>
                                <option value="51">google.com.au (Australia/English)</option>

                                <option value="1018">at.search.yahoo.com (Austria/All Languages)</option>
                                <option value="836">at.search.yahoo.com (Austria/English)</option>
                                <option value="837">at.search.yahoo.com (Austria/German)</option>
                                <option value="964">bing.com (Austria/All Languages)</option>
                                <option value="383">bing.com (Austria/German)</option>
                                <option value="3385">google.at (Austria/English)</option>
                                <option value="52">google.at (Austria/German)</option>

                                <option value="53">google.az (Azerbaijan/Azerbaijani)</option>
                                <option value="3386">google.az (Azerbaijan/English)</option>
                                <option value="13">google.az (Azerbaijan/Russian)</option>
                                <option value="1019">search.yahoo.com (Azerbaijan/All Languages)</option>
                                <option value="838">search.yahoo.com (Azerbaijan/English)</option>
                                <option value="839">search.yahoo.com (Azerbaijan/Russian)</option>

                                <option value="55">google.bs (Bahamas/English)</option>
                                <option value="56">google.com.bh (Bahrain/Arabic)</option>
                                <option value="2868">google.com.bh (Bahrain/English)</option>
                                <option value="1020">search.yahoo.com (Bahrain/All Languages)</option>
                                <option value="840">search.yahoo.com (Bahrain/Arabic)</option>
                                <option value="57">google.com.bd (Bangladesh/Bengali)</option>
                                <option value="3403">google.com.bd (Bangladesh/English)</option>
                                <option value="5708">google.com (Barbados/English)</option>
                                <option value="58">google.by (Belarus/Belarusian)</option>
                                <option value="5285">google.by (Belarus/English)</option>
                                <option value="12">google.by (Belarus/Russian)</option>


                                <option value="965">bing.com (Belgium/All Languages)</option>
                                <option value="421">bing.com (Belgium/Dutch)</option>
                                <option value="408">bing.com (Belgium/French)</option>
                                <option value="842">fr.search.yahoo.com (Belgium/French)</option>
                                <option value="60">google.be (Belgium/Dutch)</option>
                                <option value="3387">google.be (Belgium/English)</option>
                                <option value="61">google.be (Belgium/French)</option>
                                <option value="62">google.be (Belgium/German)</option>
                                <option value="841">nl.search.yahoo.com (Belgium/Dutch)</option>
                                <option value="1021">search.yahoo.com (Belgium/All Languages)</option>
                                <option value="5972">search.yahoo.com (Belgium/English)</option>

                                <option value="63">google.com.bz (Belize/English)</option>
                                <option value="64">google.com.bz (Belize/Espanol (Latinoamerica))</option>
                                <option value="5782">google.com.bz (Belize/Spanish)</option>
                                <option value="5283">google.bj (Benin/English)</option>
                                <option value="65">google.bj (Benin/French)</option>
                                <option value="66">google.bj (Benin/Yoruba)</option>
                                <option value="5761">google.com (Bermuda/English)</option>
                                <option value="3388">google.bt (Bhutan/English)</option>
                                <option value="5299">google.com.bo (Bolivia/English)</option>
                                <option value="67">google.com.bo (Bolivia/Espanol (Latinoamerica))</option>
                                <option value="68">google.com.bo (Bolivia/Quechua)</option>
                                <option value="1022">search.yahoo.com (Bolivia/All Languages)</option>
                                <option value="843">search.yahoo.com (Bolivia/English)</option>
                                <option value="69">google.ba (Bosnia and Herzegovina/Bosnian)</option>
                                <option value="71">google.ba (Bosnia and Herzegovina/Croatian)</option>
                                <option value="5275">google.ba (Bosnia and Herzegovina/English)</option>
                                <option value="70">google.ba (Bosnia and Herzegovina/Serbian)</option>
                                <option value="1023">search.yahoo.com (Bosnia and Herzegovina/All Languages)</option>
                                <option value="844">search.yahoo.com (Bosnia and Herzegovina/Croatian)</option>
                                <option value="845">search.yahoo.com (Bosnia and Herzegovina/English)</option>
                                <option value="5293">google.co.bw (Botswana/English)</option>
                                <option value="72">google.co.bw (Botswana/Tswana)</option>
                                <option value="966">bing.com (Brazil/All Languages)</option>
                                <option value="424">bing.com (Brazil/Portuguese)</option>
                                <option value="1024">br.search.yahoo.com (Brazil/All Languages)</option>
                                <option value="846">br.search.yahoo.com (Brazil/English)</option>
                                <option value="847">br.search.yahoo.com (Brazil/Portuguese)</option>
                                <option value="3405">google.com.br (Brazil/English)</option>
                                <option value="73">google.com.br (Brazil/Portuguese (Brasil))</option>

                                <option value="360">google.vg (British Virgin Islands/English)</option>
                                <option value="75">google.com.bn (Brunei/Chinese (Simplified Han))</option>
                                <option value="3404">google.com.bn (Brunei/English)</option>
                                <option value="74">google.com.bn (Brunei/Malay)</option>
                                <option value="967">bing.com (Bulgaria/All Languages)</option>
                                <option value="380">bing.com (Bulgaria/Bulgarian)</option>
                                <option value="76">google.bg (Bulgaria/Bulgarian)</option>
                                <option value="5279">google.bg (Bulgaria/English)</option>
                                <option value="1025">search.yahoo.com (Bulgaria/All Languages)</option>
                                <option value="848">search.yahoo.com (Bulgaria/Bulgarian)</option>
                                <option value="849">search.yahoo.com (Bulgaria/English)</option>

                                <option value="5277">google.bf (Burkina Faso/English)</option>
                                <option value="77">google.bf (Burkina Faso/French)</option>
                                <option value="5281">google.bi (Burundi/English)</option>
                                <option value="79">google.bi (Burundi/French)</option>
                                <option value="78">google.bi (Burundi/Kirundi)</option>
                                <option value="80">google.bi (Burundi/Swahili)</option>
                                <option value="3409">google.com.kh (Cambodia/English)</option>
                                <option value="81">google.com.kh (Cambodia/Khmer)</option>
                                <option value="3391">google.cm (Cameroon/English)</option>
                                <option value="82">google.cm (Cameroon/French)</option>
                                <option value="968">bing.com (Canada/All Languages)</option>
                                <option value="388">bing.com (Canada/English)</option>
                                <option value="409">bing.com (Canada/French)</option>
                                <option value="1026">ca.search.yahoo.com (Canada/All Languages)</option>
                                <option value="850">ca.search.yahoo.com (Canada/English)</option>
                                <option value="851">ca.search.yahoo.com (Canada/French)</option>
                                <option value="23">google.ca (Canada/English)</option>
                                <option value="84">google.ca (Canada/French)</option>

                                <option value="5333">google.cv (Cape Verde/English)</option>
                                <option value="85">google.cv (Cape Verde/Portuguese)</option>
                                <option value="5783">google.cv (Cape Verde/Portuguese (Brasil))</option>
                                <option value="267">google.cat (Catalonia/Catalan)</option>
                                <option value="5763">google.com (Cayman Islands/English)</option>
                                <option value="5287">google.cf (Central African Republic/English)</option>
                                <option value="86">google.cf (Central African Republic/French)</option>
                                <option value="87">google.td (Chad/Arabic)</option>
                                <option value="5381">google.td (Chad/English)</option>
                                <option value="88">google.td (Chad/French)</option>
                                <option value="969">bing.com (Chile/All Languages)</option>
                                <option value="401">bing.com (Chile/Spanish)</option>
                                <option value="853">espanol.search.yahoo.com (Chile/Spanish)</option>
                                <option value="3390">google.cl (Chile/English)</option>
                                <option value="89">google.cl (Chile/Espanol (Latinoamerica))</option>
                                <option value="5769">google.cl (Chile/Spanish)</option>
                                <option value="1027">search.yahoo.com (Chile/All Languages)</option>
                                <option value="970">bing.com (China/All Languages)</option>
                                <option value="434">bing.com (China/Chinese)</option>
                                <option value="90">google.com.hk (China/Chinese (Simplified Han))</option>
                                <option value="1028">search.yahoo.com (China/All Languages)</option>
                                <option value="5974">search.yahoo.com (China/Chinese (Simplified Han))</option>
                                <option value="854">search.yahoo.com (China/Chinese (Traditional))</option>
                                <option value="855">search.yahoo.com (China/English)</option>

                                <option value="856">espanol.search.yahoo.com (Colombia/Spanish)</option>
                                <option value="3406">google.com.co (Colombia/English)</option>
                                <option value="91">google.com.co (Colombia/Espanol (Latinoamerica))</option>
                                <option value="5742">google.com.co (Colombia/Spanish)</option>
                                <option value="1029">search.yahoo.com (Colombia/All Languages)</option>
                                <option value="857">search.yahoo.com (Colombia/English)</option>
                                <option value="5799">google.com (Comoros/French)</option>
                                <option value="101">google.co.ck (Cook Islands/English)</option>
                                <option value="3392">google.co.cr (Costa Rica/English)</option>
                                <option value="102">google.co.cr (Costa Rica/Espanol (Latinoamerica))</option>
                                <option value="5781">google.co.cr (Costa Rica/Spanish)</option>
                                <option value="1030">search.yahoo.com (Costa Rica/All Languages)</option>
                                <option value="859">search.yahoo.com (Costa Rica/English)</option>
                                <option value="858">search.yahoo.com (Costa Rica/Spanish)</option>
                                <option value="5289">google.ci (Cote dIvoire/English)</option>
                                <option value="103">google.ci (Cote dIvoire/French)</option>
                                <option value="971">bing.com (Croatia/All Languages)</option>
                                <option value="413">bing.com (Croatia/Croatian)</option>
                                <option value="104">google.hr (Croatia/Croatian)</option>
                                <option value="5767">google.hr (Croatia/English)</option>
                                <option value="1031">search.yahoo.com (Croatia/All Languages)</option>
                                <option value="860">search.yahoo.com (Croatia/Croatian)</option>
                                <option value="5301">google.com.cu (Cuba/English)</option>
                                <option value="105">google.com.cu (Cuba/Espanol (Latinoamerica))</option>
                                <option value="6225">google.com (Curacao/English)</option>
                                <option value="106">google.com.cy (Cyprus/English)</option>
                                <option value="107">google.com.cy (Cyprus/Greek)</option>
                                <option value="108">google.com.cy (Cyprus/Turkish)</option>

                                <option value="972">bing.com (Czechia/All Languages)</option>
                                <option value="381">bing.com (Czechia/Czech)</option>
                                <option value="109">google.cz (Czechia/Czech)</option>
                                <option value="5335">google.cz (Czechia/English)</option>
                                <option value="1032">search.yahoo.com (Czechia/All Languages)</option>
                                <option value="861">search.yahoo.com (Czechia/Czech)</option>
                                <option value="95">google.cd (Democratic Republic of the Congo/Alur)</option>
                                <option value="96">google.cd (Democratic Republic of the Congo/French)</option>
                                <option value="99">google.cd (Democratic Republic of the Congo/Kongo)</option>
                                <option value="100">google.cd (Democratic Republic of the Congo/Lingala)</option>
                                <option value="98">google.cd (Democratic Republic of the Congo/Swahili)</option>
                                <option value="97">google.cd (Democratic Republic of the Congo/Tshiluba)</option>
                                <option value="1033">search.yahoo.com (Democratic Republic of the Congo/All Languages)
                                </option>
                                <option value="862">search.yahoo.com (Democratic Republic of the Congo/French)</option>
                                <option value="973">bing.com (Denmark/All Languages)</option>
                                <option value="382">bing.com (Denmark/Danish)</option>
                                <option value="1034">dk.search.yahoo.com (Denmark/All Languages)</option>
                                <option value="863">dk.search.yahoo.com (Denmark/Danish)</option>
                                <option value="864">dk.search.yahoo.com (Denmark/English)</option>
                                <option value="110">google.dk (Denmark/Danish)</option>
                                <option value="3421">google.dk (Denmark/English)</option>
                                <option value="111">google.dk (Denmark/Faroese)</option>

                                <option value="114">google.dj (Djibouti/Arabic)</option>
                                <option value="5337">google.dj (Djibouti/English)</option>
                                <option value="113">google.dj (Djibouti/French)</option>
                                <option value="112">google.dj (Djibouti/Somali)</option>
                                <option value="115">google.dm (Dominica/English)</option>
                                <option value="5303">google.com.do (Dominican Republic/English)</option>
                                <option value="116">google.com.do (Dominican Republic/Espanol (Latinoamerica))</option>
                                <option value="5760">google.com.do (Dominican Republic/Spanish)</option>
                                <option value="5305">google.com.ec (Ecuador/English)</option>
                                <option value="117">google.com.ec (Ecuador/Espanol (Latinoamerica))</option>
                                <option value="5774">google.com.ec (Ecuador/Spanish)</option>
                                <option value="1035">search.yahoo.com (Ecuador/All Languages)</option>
                                <option value="865">search.yahoo.com (Ecuador/English)</option>
                                <option value="118">google.com.eg (Egypt/Arabic)</option>
                                <option value="2873">google.com.eg (Egypt/English)</option>
                                <option value="1036">search.yahoo.com (Egypt/All Languages)</option>
                                <option value="866">search.yahoo.com (Egypt/Arabic)</option>
                                <option value="867">search.yahoo.com (Egypt/English)</option>

                                <option value="5321">google.com.sv (El Salvador/English)</option>
                                <option value="119">google.com.sv (El Salvador/Espanol (Latinoamerica))</option>
                                <option value="5743">google.com.sv (El Salvador/Spanish)</option>
                                <option value="1037">search.yahoo.com (El Salvador/All Languages)</option>
                                <option value="868">search.yahoo.com (El Salvador/English)</option>
                                <option value="5787">google.com (Eritrea/Arabic)</option>
                                <option value="974">bing.com (Estonia/All Languages)</option>
                                <option value="406">bing.com (Estonia/Estonian)</option>
                                <option value="3422">google.ee (Estonia/English)</option>
                                <option value="121">google.ee (Estonia/Estonian)</option>
                                <option value="120">google.ee (Estonia/Russian)</option>
                                <option value="1038">search.yahoo.com (Estonia/All Languages)</option>
                                <option value="869">search.yahoo.com (Estonia/Estonian)</option>
                                <option value="870">search.yahoo.com (Estonia/Russian)</option>
                                <option value="122">google.com.et (Ethiopia/Amharic)</option>
                                <option value="3407">google.com.et (Ethiopia/English)</option>
                                <option value="123">google.com.et (Ethiopia/Oromo)</option>
                                <option value="125">google.com.et (Ethiopia/Somali)</option>
                                <option value="124">google.com.et (Ethiopia/Tigrinya)</option>
                                <option value="5789">google.com (Faroe Islands/Faroese)</option>
                                <option value="126">google.com.fj (Fiji/English)</option>
                                <option value="975">bing.com (Finland/All Languages)</option>
                                <option value="407">bing.com (Finland/Finnish)</option>
                                <option value="1039">fi.search.yahoo.com (Finland/All Languages)</option>
                                <option value="871">fi.search.yahoo.com (Finland/English)</option>
                                <option value="872">fi.search.yahoo.com (Finland/Finnish)</option>
                                <option value="873">fi.search.yahoo.com (Finland/Swedish)</option>
                                <option value="3423">google.fi (Finland/English)</option>
                                <option value="128">google.fi (Finland/Finnish)</option>
                                <option value="127">google.fi (Finland/Swedish)</option>
                                <option value="976">bing.com (France/All Languages)</option>
                                <option value="411">bing.com (France/French)</option>
                                <option value="1040">fr.search.yahoo.com (France/All Languages)</option>
                                <option value="874">fr.search.yahoo.com (France/French)</option>
                                <option value="2838">google.fr (France/English)</option>
                                <option value="129">google.fr (France/French)</option>

                                <option value="5793">google.com (French Guiana/French)</option>
                                <option value="5816">google.com (French Polynesia/French)</option>
                                <option value="5341">google.ga (Gabon/English)</option>
                                <option value="130">google.ga (Gabon/French)</option>
                                <option value="131">google.gm (Gambia/English)</option>
                                <option value="132">google.gm (Gambia/Wolof)</option>
                                <option value="5343">google.ge (Georgia/English)</option>
                                <option value="133">google.ge (Georgia/Georgian)</option>
                                <option value="1041">search.yahoo.com (Georgia/All Languages)</option>
                                <option value="875">search.yahoo.com (Georgia/English)</option>
                                <option value="977">bing.com (Germany/All Languages)</option>
                                <option value="385">bing.com (Germany/German)</option>
                                <option value="1042">de.search.yahoo.com (Germany/All Languages)</option>
                                <option value="876">de.search.yahoo.com (Germany/German)</option>
                                <option value="2927">google.de (Germany/English)</option>
                                <option value="25">google.de (Germany/German)</option>

                                <option value="136">google.com.gh (Ghana/Akan)</option>
                                <option value="138">google.com.gh (Ghana/English)</option>
                                <option value="137">google.com.gh (Ghana/Ewe)</option>
                                <option value="139">google.com.gh (Ghana/Ga)</option>
                                <option value="135">google.com.gh (Ghana/Hausa)</option>
                                <option value="141">google.com.gi (Gibraltar/English)</option>
                                <option value="5400">google.com.gi (Gibraltar/Espanol (Latinoamerica))</option>
                                <option value="143">google.com.gi (Gibraltar/Italian)</option>
                                <option value="140">google.com.gi (Gibraltar/Portuguese)</option>
                                <option value="142">google.com.gi (Gibraltar/Spanish)</option>
                                <option value="978">bing.com (Greece/All Languages)</option>
                                <option value="386">bing.com (Greece/Greek)</option>
                                <option value="3424">google.gr (Greece/English)</option>
                                <option value="144">google.gr (Greece/Greek)</option>
                                <option value="1043">gr.search.yahoo.com (Greece/All Languages)</option>
                                <option value="877">gr.search.yahoo.com (Greece/English)</option>
                                <option value="878">gr.search.yahoo.com (Greece/Greek)</option>

                                <option value="145">google.gl (Greenland/Danish)</option>
                                <option value="5345">google.gl (Greenland/English)</option>
                                <option value="5791">google.com (Grenada/English)</option>
                                <option value="5347">google.gp (Guadeloupe/English)</option>
                                <option value="146">google.gp (Guadeloupe/French)</option>
                                <option value="5702">google.com (Guam/English)</option>
                                <option value="3673">google.com.gt (Guatemala/English)</option>
                                <option value="147">google.com.gt (Guatemala/Espanol (Latinoamerica))</option>
                                <option value="5772">google.com.gt (Guatemala/Spanish)</option>
                                <option value="1044">search.yahoo.com (Guatemala/All Languages)</option>
                                <option value="879">search.yahoo.com (Guatemala/English)</option>
                                <option value="148">google.gg (Guernsey/English)</option>
                                <option value="149">google.gg (Guernsey/French)</option>
                                <option value="5795">google.com (Guinea/French)</option>
                                <option value="150">google.gy (Guyana/English)</option>
                                <option value="5351">google.ht (Haiti/English)</option>
                                <option value="152">google.ht (Haiti/French)</option>
                                <option value="151">google.ht (Haiti/Haitian)</option>
                                <option value="5349">google.hn (Honduras/English)</option>
                                <option value="153">google.hn (Honduras/Espanol (Latinoamerica))</option>
                                <option value="5768">google.hn (Honduras/Spanish)</option>
                                <option value="1045">search.yahoo.com (Honduras/All Languages)</option>
                                <option value="880">search.yahoo.com (Honduras/English)</option>
                                <option value="979">bing.com (Hong Kong/All Languages)</option>
                                <option value="435">bing.com (Hong Kong/Chinese (Traditional Han))</option>
                                <option value="5733">google.com.hk (Hong Kong/Chinese)</option>
                                <option value="3611">google.com.hk (Hong Kong/Chinese (Simplified))</option>
                                <option value="154">google.com.hk (Hong Kong/Chinese (Traditional))</option>
                                <option value="5735">google.com.hk (Hong Kong/Chinese (Traditional Han))</option>
                                <option value="2843">google.com.hk (Hong Kong/English)</option>
                                <option value="1046">hk.search.yahoo.com (Hong Kong/All Languages)</option>
                                <option value="881">hk.search.yahoo.com (Hong Kong/Chinese (Traditional Han))</option>
                                <option value="882">hk.search.yahoo.com (Hong Kong/English)</option>
                                <option value="980">bing.com (Hungary/All Languages)</option>
                                <option value="414">bing.com (Hungary/Hungarian)</option>
                                <option value="3425">google.hu (Hungary/English)</option>
                                <option value="155">google.hu (Hungary/Hungarian)</option>
                                <option value="1047">search.yahoo.com (Hungary/All Languages)</option>
                                <option value="883">search.yahoo.com (Hungary/English)</option>
                                <option value="884">search.yahoo.com (Hungary/Hungarian)</option>

                                <option value="3426">google.is (Iceland/English)</option>
                                <option value="156">google.is (Iceland/Icelandic)</option>
                                <option value="1048">search.yahoo.com (Iceland/All Languages)</option>
                                <option value="885">search.yahoo.com (Iceland/English)</option>
                                <option value="981">bing.com (India/All Languages)</option>
                                <option value="392">bing.com (India/English)</option>
                                <option value="159">google.co.in (India/Bengali)</option>
                                <option value="163">google.co.in (India/English)</option>
                                <option value="161">google.co.in (India/Gujarati)</option>
                                <option value="166">google.co.in (India/Hindi)</option>
                                <option value="164">google.co.in (India/Kannada)</option>
                                <option value="157">google.co.in (India/Malayalam)</option>
                                <option value="165">google.co.in (India/Marathi)</option>
                                <option value="160">google.co.in (India/Panjabi)</option>
                                <option value="158">google.co.in (India/Tamil)</option>
                                <option value="162">google.co.in (India/Telugu)</option>
                                <option value="1049">in.search.yahoo.com (India/All Languages)</option>
                                <option value="886">in.search.yahoo.com (India/English)</option>

                                <option value="982">bing.com (Indonesia/All Languages)</option>
                                <option value="390">bing.com (Indonesia/English)</option>
                                <option value="5836">bing.com (Indonesia/Indonesian)</option>
                                <option value="5295">google.co.id (Indonesia/Balinese)</option>
                                <option value="168">google.co.id (Indonesia/Basa Jawa)</option>
                                <option value="2901">google.co.id (Indonesia/English)</option>
                                <option value="167">google.co.id (Indonesia/Indonesian)</option>
                                <option value="1050">id.search.yahoo.com (Indonesia/All Languages)</option>
                                <option value="887">id.search.yahoo.com (Indonesia/English)</option>
                                <option value="5975">id.search.yahoo.com (Indonesia/Indonesian)</option>
                                <option value="170">google.iq (Iraq/Arabic)</option>
                                <option value="2878">google.iq (Iraq/English)</option>
                                <option value="169">google.iq (Iraq/Kurdish)</option>
                                <option value="1051">search.yahoo.com (Iraq/All Languages)</option>
                                <option value="888">search.yahoo.com (Iraq/Arabic)</option>
                                <option value="983">bing.com (Ireland/All Languages)</option>
                                <option value="391">bing.com (Ireland/English)</option>
                                <option value="172">google.ie (Ireland/English)</option>
                                <option value="171">google.ie (Ireland/Irish)</option>
                                <option value="1052">ie.search.yahoo.com (Ireland/All Languages)</option>
                                <option value="889">ie.search.yahoo.com (Ireland/English)</option>
                                <option value="173">google.im (Isle of Man/English)</option>
                                <option value="984">bing.com (Israel/All Languages)</option>
                                <option value="412">bing.com (Israel/Hebrew)</option>
                                <option value="175">google.co.il (Israel/Arabic)</option>
                                <option value="3393">google.co.il (Israel/English)</option>
                                <option value="174">google.co.il (Israel/Hebrew)</option>
                                <option value="1053">search.yahoo.com (Israel/All Languages)</option>
                                <option value="890">search.yahoo.com (Israel/Arabic)</option>
                                <option value="891">search.yahoo.com (Israel/Hebrew)</option>
                                <option value="985">bing.com (Italy/All Languages)</option>
                                <option value="415">bing.com (Italy/Italian)</option>
                                <option value="3427">google.it (Italy/English)</option>
                                <option value="176">google.it (Italy/Italian)</option>
                                <option value="1054">it.search.yahoo.com (Italy/All Languages)</option>
                                <option value="892">it.search.yahoo.com (Italy/English)</option>
                                <option value="893">it.search.yahoo.com (Italy/Italian)</option>

                                <option value="177">google.com.jm (Jamaica/English)</option>
                                <option value="986">bing.com (Japan/All Languages)</option>
                                <option value="416">bing.com (Japan/Japanese)</option>
                                <option value="3394">google.co.jp (Japan/English)</option>
                                <option value="178">google.co.jp (Japan/Japanese)</option>
                                <option value="5435">search.yahoo.co.jp (Japan/English)</option>
                                <option value="5436">search.yahoo.co.jp (Japan/Japanese)</option>
                                <option value="1055">search.yahoo.com (Japan/All Languages)</option>
                                <option value="894">search.yahoo.com (Japan/English)</option>
                                <option value="895">search.yahoo.com (Japan/Japanese)</option>

                                <option value="179">google.je (Jersey/English)</option>
                                <option value="180">google.je (Jersey/French)</option>
                                <option value="181">google.jo (Jordan/Arabic)</option>
                                <option value="2863">google.jo (Jordan/English)</option>
                                <option value="5355">google.kz (Kazakhstan/English)</option>
                                <option value="183">google.kz (Kazakhstan/Kazakh)</option>
                                <option value="11">google.kz (Kazakhstan/Russian)</option>


                                <option value="5959">bing.com (Kenya/Swahili)</option>
                                <option value="3395">google.co.ke (Kenya/English)</option>
                                <option value="184">google.co.ke (Kenya/Swahili)</option>
                                <option value="1056">search.yahoo.com (Kenya/All Languages)</option>
                                <option value="896">search.yahoo.com (Kenya/English)</option>
                                <option value="185">google.ki (Kiribati/English)</option>
                                <option value="187">google.com.kw (Kuwait/Arabic)</option>
                                <option value="2853">google.com.kw (Kuwait/English)</option>
                                <option value="1057">search.yahoo.com (Kuwait/All Languages)</option>
                                <option value="897">search.yahoo.com (Kuwait/Arabic)</option>
                                <option value="5353">google.kg (Kyrgyzstan/English)</option>
                                <option value="189">google.kg (Kyrgyzstan/Kyrgyz)</option>
                                <option value="188">google.kg (Kyrgyzstan/Russian)</option>
                                <option value="3428">google.la (Laos/English)</option>
                                <option value="190">google.la (Laos/Lao)</option>
                                <option value="987">bing.com (Latvia/All Languages)</option>
                                <option value="419">bing.com (Latvia/Latvian)</option>
                                <option value="3431">google.lv (Latvia/English)</option>
                                <option value="193">google.lv (Latvia/Latvian)</option>
                                <option value="191">google.lv (Latvia/Lithuanian)</option>
                                <option value="192">google.lv (Latvia/Russian)</option>
                                <option value="1058">search.yahoo.com (Latvia/All Languages)</option>
                                <option value="898">search.yahoo.com (Latvia/English)</option>
                                <option value="899">search.yahoo.com (Latvia/Latvian)</option>
                                <option value="901">search.yahoo.com (Latvia/Russian)</option>

                                <option value="194">google.com.lb (Lebanon/Arabic)</option>
                                <option value="196">google.com.lb (Lebanon/Armenian)</option>
                                <option value="2883">google.com.lb (Lebanon/English)</option>
                                <option value="195">google.com.lb (Lebanon/French)</option>
                                <option value="1059">search.yahoo.com (Lebanon/All Languages)</option>
                                <option value="902">search.yahoo.com (Lebanon/Arabic)</option>
                                <option value="903">search.yahoo.com (Lebanon/French)</option>
                                <option value="197">google.co.ls (Lesotho/English)</option>
                                <option value="198">google.co.ls (Lesotho/Southern Sotho)</option>
                                <option value="5803">google.com (Liberia/English)</option>
                                <option value="199">google.com.ly (Libya/Arabic)</option>
                                <option value="3410">google.com.ly (Libya/English)</option>
                                <option value="200">google.com.ly (Libya/Italian)</option>
                                <option value="1060">search.yahoo.com (Libya/All Languages)</option>
                                <option value="904">search.yahoo.com (Libya/Arabic)</option>
                                <option value="905">search.yahoo.com (Libya/Italian)</option>
                                <option value="5357">google.li (Liechtenstein/English)</option>
                                <option value="201">google.li (Liechtenstein/German)</option>
                                <option value="988">bing.com (Lithuania/All Languages)</option>
                                <option value="418">bing.com (Lithuania/Lithuanian)</option>
                                <option value="3429">google.lt (Lithuania/English)</option>
                                <option value="202">google.lt (Lithuania/Lithuanian)</option>
                                <option value="1061">search.yahoo.com (Lithuania/All Languages)</option>
                                <option value="906">search.yahoo.com (Lithuania/English)</option>
                                <option value="900">search.yahoo.com (Lithuania/Lithuanian)</option>

                                <option value="3430">google.lu (Luxembourg/English)</option>
                                <option value="204">google.lu (Luxembourg/French)</option>
                                <option value="203">google.lu (Luxembourg/German)</option>
                                <option value="1062">search.yahoo.com (Luxembourg/All Languages)</option>
                                <option value="907">search.yahoo.com (Luxembourg/French)</option>
                                <option value="908">search.yahoo.com (Luxembourg/German)</option>
                                <option value="5770">google.com (Macao/Chinese (Traditional Han))</option>
                                <option value="5363">google.mk (Macedonia/English)</option>
                                <option value="205">google.mk (Macedonia/Macedonian)</option>
                                <option value="1063">search.yahoo.com (Macedonia/All Languages)</option>
                                <option value="909">search.yahoo.com (Macedonia/English)</option>
                                <option value="5361">google.mg (Madagascar/English)</option>
                                <option value="207">google.mg (Madagascar/French)</option>
                                <option value="206">google.mg (Madagascar/Malagasy)</option>
                                <option value="208">google.mw (Malawi/Chichewa)</option>
                                <option value="5369">google.mw (Malawi/English)</option>
                                <option value="209">google.mw (Malawi/Malawi)</option>
                                <option value="989">bing.com (Malaysia/All Languages)</option>
                                <option value="393">bing.com (Malaysia/English)</option>
                                <option value="2896">google.com.my (Malaysia/English)</option>
                                <option value="210">google.com.my (Malaysia/Malay)</option>
                                <option value="1064">malaysia.search.yahoo.com (Malaysia/All Languages)</option>
                                <option value="910">malaysia.search.yahoo.com (Malaysia/English)</option>
                                <option value="5976">malaysia.search.yahoo.com (Malaysia/Malay)</option>
                                <option value="211">google.mv (Maldives/English)</option>
                                <option value="5365">google.ml (Mali/English)</option>
                                <option value="212">google.ml (Mali/French)</option>
                                <option value="3412">google.com.mt (Malta/English)</option>
                                <option value="213">google.com.mt (Malta/Maltese)</option>
                                <option value="1065">search.yahoo.com (Malta/All Languages)</option>
                                <option value="911">search.yahoo.com (Malta/English)</option>

                                <option value="5810">google.com (Martinique/French)</option>
                                <option value="5812">google.com (Mauritania/Arabic)</option>
                                <option value="214">google.mu (Mauritius/English)</option>
                                <option value="215">google.mu (Mauritius/French)</option>
                                <option value="216">google.mu (Mauritius/Kreol morisien)</option>
                                <option value="990">bing.com (Mexico/All Languages)</option>
                                <option value="403">bing.com (Mexico/Spanish)</option>
                                <option value="3413">google.com.mx (Mexico/English)</option>
                                <option value="217">google.com.mx (Mexico/Espanol (Latinoamerica))</option>
                                <option value="5741">google.com.mx (Mexico/Spanish)</option>
                                <option value="1066">mx.search.yahoo.com (Mexico/All Languages)</option>
                                <option value="912">mx.search.yahoo.com (Mexico/English)</option>
                                <option value="913">mx.search.yahoo.com (Mexico/Spanish)</option>

                                <option value="218">google.fm (Micronesia/English)</option>
                                <option value="219">google.md (Moldova/Moldovian)</option>
                                <option value="5753">google.md (Moldova/Romanian)</option>
                                <option value="220">google.md (Moldova/Russian)</option>

                                <option value="5367">google.mn (Mongolia/English)</option>
                                <option value="221">google.mn (Mongolia/Mongolian)</option>
                                <option value="222">google.me (Montenegro/Bosnian)</option>
                                <option value="5359">google.me (Montenegro/English)</option>
                                <option value="223">google.me (Montenegro/Montenegro)</option>
                                <option value="224">google.me (Montenegro/Serbian)</option>
                                <option value="225">google.ms (Montserrat/English)</option>
                                <option value="227">google.co.ma (Morocco/Arabic)</option>
                                <option value="3397">google.co.ma (Morocco/English)</option>
                                <option value="226">google.co.ma (Morocco/French)</option>
                                <option value="1067">search.yahoo.com (Morocco/All Languages)</option>
                                <option value="914">search.yahoo.com (Morocco/Arabic)</option>
                                <option value="915">search.yahoo.com (Morocco/French)</option>
                                <option value="229">google.co.mz (Mozambique/Chichewa)</option>
                                <option value="3398">google.co.mz (Mozambique/English)</option>
                                <option value="231">google.co.mz (Mozambique/Portuguese)</option>
                                <option value="5785">google.co.mz (Mozambique/Portuguese (Brasil))</option>
                                <option value="230">google.co.mz (Mozambique/Shona)</option>
                                <option value="228">google.co.mz (Mozambique/Swahili)</option>
                                <option value="5832">google.com.mm (Myanmar/Burmese)</option>
                                <option value="3411">google.com.mm (Myanmar/English)</option>
                                <option value="234">google.com.na (Namibia/Afrikaans)</option>
                                <option value="233">google.com.na (Namibia/English)</option>
                                <option value="232">google.com.na (Namibia/German)</option>
                                <option value="235">google.nr (Nauru/English)</option>
                                <option value="3414">google.com.np (Nepal/English)</option>
                                <option value="236">google.com.np (Nepal/Nepali)</option>
                                <option value="991">bing.com (Netherlands/All Languages)</option>
                                <option value="422">bing.com (Netherlands/Dutch)</option>
                                <option value="24">google.nl (Netherlands/Dutch)</option>
                                <option value="237">google.nl (Netherlands/English)</option>
                                <option value="5373">google.nl (Netherlands/Frisian)</option>
                                <option value="1068">nl.search.yahoo.com (Netherlands/All Languages)</option>
                                <option value="916">nl.search.yahoo.com (Netherlands/Dutch)</option>
                                <option value="917">nl.search.yahoo.com (Netherlands/English)</option>

                                <option value="5814">google.com (New Caledonia/French)</option>
                                <option value="992">bing.com (New Zealand/All Languages)</option>
                                <option value="394">bing.com (New Zealand/English)</option>
                                <option value="239">google.co.nz (New Zealand/English)</option>
                                <option value="240">google.co.nz (New Zealand/Maori)</option>
                                <option value="1069">search.yahoo.com (New Zealand/All Languages)</option>
                                <option value="918">search.yahoo.com (New Zealand/English)</option>

                                <option value="5307">google.com.ni (Nicaragua/English)</option>
                                <option value="241">google.com.ni (Nicaragua/Espanol (Latinoamerica))</option>
                                <option value="5830">google.com.ni (Nicaragua/Spanish)</option>
                                <option value="1070">search.yahoo.com (Nicaragua/All Languages)</option>
                                <option value="919">search.yahoo.com (Nicaragua/English)</option>
                                <option value="5371">google.ne (Niger/English)</option>
                                <option value="242">google.ne (Niger/French)</option>
                                <option value="243">google.ne (Niger/Hausa)</option>
                                <option value="248">google.com.ng (Nigeria/English)</option>
                                <option value="245">google.com.ng (Nigeria/Hausa)</option>
                                <option value="246">google.com.ng (Nigeria/Igbo)</option>
                                <option value="244">google.com.ng (Nigeria/Pidgin)</option>
                                <option value="247">google.com.ng (Nigeria/Yoruba)</option>
                                <option value="249">google.nu (Niue/English)</option>
                                <option value="250">google.com.nf (Norfolk Island/English)</option>
                                <option value="5808">google.com (Northern Mariana Islands/English)</option>
                                <option value="993">bing.com (Norway/All Languages)</option>
                                <option value="5837">bing.com (Norway/English)</option>
                                <option value="420">bing.com (Norway/Norwegian)</option>
                                <option value="3432">google.no (Norway/English)</option>
                                <option value="251">google.no (Norway/Norwegian)</option>
                                <option value="3680">google.no (Norway/Norwegian Bokmal)</option>
                                <option value="252">google.no (Norway/Norwegian Nynorsk)</option>
                                <option value="1071">no.search.yahoo.com (Norway/All Languages)</option>
                                <option value="920">no.search.yahoo.com (Norway/English)</option>
                                <option value="921">no.search.yahoo.com (Norway/Norwegian)</option>

                                <option value="253">google.com.om (Oman/Arabic)</option>
                                <option value="2894">google.com.om (Oman/English)</option>
                                <option value="1072">search.yahoo.com (Oman/All Languages)</option>
                                <option value="922">search.yahoo.com (Oman/Arabic)</option>
                                <option value="254">google.com.pk (Pakistan/English)</option>
                                <option value="5313">google.com.pk (Pakistan/Pashto, Pushto)</option>
                                <option value="5315">google.com.pk (Pakistan/Sindhi)</option>
                                <option value="255">google.com.pk (Pakistan/Urdu)</option>
                                <option value="1073">search.yahoo.com (Pakistan/All Languages)</option>
                                <option value="923">search.yahoo.com (Pakistan/English)</option>
                                <option value="5818">google.com (Palau/English)</option>
                                <option value="256">google.ps (Palestine/Arabic)</option>
                                <option value="2892">google.ps (Palestine/English)</option>
                                <option value="5309">google.com.pa (Panama/English)</option>
                                <option value="257">google.com.pa (Panama/Espanol (Latinoamerica))</option>
                                <option value="1074">search.yahoo.com (Panama/All Languages)</option>
                                <option value="924">search.yahoo.com (Panama/English)</option>
                                <option value="3416">google.com.pg (Papua New Guina/English)</option>
                                <option value="5319">google.com.py (Paraguay/English)</option>
                                <option value="258">google.com.py (Paraguay/Espanol (Latinoamerica))</option>
                                <option value="5775">google.com.py (Paraguay/Spanish)</option>
                                <option value="1075">search.yahoo.com (Paraguay/All Languages)</option>
                                <option value="925">search.yahoo.com (Paraguay/English)</option>
                                <option value="5977">espanol.search.yahoo.com (Peru/Spanish)</option>
                                <option value="3415">google.com.pe (Peru/English)</option>
                                <option value="259">google.com.pe (Peru/Espanol (Latinoamerica))</option>
                                <option value="260">google.com.pe (Peru/Quechua)</option>
                                <option value="5776">google.com.pe (Peru/Spanish)</option>
                                <option value="1076">search.yahoo.com (Peru/All Languages)</option>
                                <option value="926">search.yahoo.com (Peru/English)</option>
                                <option value="994">bing.com (Philippines/All Languages)</option>
                                <option value="395">bing.com (Philippines/English)</option>
                                <option value="5311">google.com.ph (Philippines/Cebuano)</option>
                                <option value="3417">google.com.ph (Philippines/English)</option>
                                <option value="5746">google.com.ph (Philippines/Filipino)</option>
                                <option value="261">google.com.ph (Philippines/Tagalog)</option>
                                <option value="1077">ph.search.yahoo.com (Philippines/All Languages)</option>
                                <option value="927">ph.search.yahoo.com (Philippines/English)</option>
                                <option value="5978">ph.search.yahoo.com (Philippines/Spanish)</option>
                                <option value="262">google.pn (Pitcairn Islands/English)</option>
                                <option value="995">bing.com (Poland/All Languages)</option>
                                <option value="5838">bing.com (Poland/English)</option>
                                <option value="423">bing.com (Poland/Polish)</option>
                                <option value="3433">google.pl (Poland/English)</option>
                                <option value="21">google.pl (Poland/Polish)</option>
                                <option value="1078">pl.search.yahoo.com (Poland/All Languages)</option>
                                <option value="928">pl.search.yahoo.com (Poland/English)</option>
                                <option value="929">pl.search.yahoo.com (Poland/Polish)</option>

                                <option value="996">bing.com (Portugal/All Languages)</option>
                                <option value="425">bing.com (Portugal/Portuguese)</option>
                                <option value="3434">google.pt (Portugal/English)</option>
                                <option value="264">google.pt (Portugal/Portuguese)</option>
                                <option value="1079">search.yahoo.com (Portugal/All Languages)</option>
                                <option value="930">search.yahoo.com (Portugal/Portuguese)</option>
                                <option value="5317">google.com.pr (Puerto Rico/English)</option>
                                <option value="265">google.com.pr (Puerto Rico/Espanol (Latinoamerica))</option>
                                <option value="5739">google.com.pr (Puerto Rico/Spanish)</option>
                                <option value="1080">search.yahoo.com (Puerto Rico/All Languages)</option>
                                <option value="931">search.yahoo.com (Puerto Rico/English)</option>
                                <option value="266">google.com.qa (Qatar/Arabic)</option>
                                <option value="2895">google.com.qa (Qatar/English)</option>
                                <option value="1081">search.yahoo.com (Qatar/All Languages)</option>
                                <option value="932">search.yahoo.com (Qatar/Arabic)</option>
                                <option value="92">google.cg (Republic of the Congo/French)</option>
                                <option value="94">google.cg (Republic of the Congo/Kongo)</option>
                                <option value="93">google.cg (Republic of the Congo/Lingala)</option>
                                <option value="5807">google.com (Reunion/French)</option>
                                <option value="997">bing.com (Romania/All Languages)</option>
                                <option value="426">bing.com (Romania/Romanian)</option>
                                <option value="3435">google.ro (Romania/English)</option>
                                <option value="269">google.ro (Romania/German)</option>
                                <option value="268">google.ro (Romania/Hungarian)</option>
                                <option value="270">google.ro (Romania/Romanian)</option>
                                <option value="1082">ro.search.yahoo.com (Romania/All Languages)</option>
                                <option value="933">ro.search.yahoo.com (Romania/German)</option>
                                <option value="934">ro.search.yahoo.com (Romania/Romanian)</option>
                                <option value="998">bing.com (Russia/All Languages)</option>
                                <option value="427">bing.com (Russia/Russian)</option>
                                <option value="3437">google.ru (Russia/English)</option>
                                <option value="3">google.ru (Russia/Russian)</option>
                                <option value="1083">ru.search.yahoo.com (Russia/All Languages)</option>
                                <option value="935">ru.search.yahoo.com (Russia/English)</option>
                                <option value="936">ru.search.yahoo.com (Russia/Russian)</option>


                                <option value="274">google.rw (Rwanda/English)</option>
                                <option value="275">google.rw (Rwanda/French)</option>
                                <option value="273">google.rw (Rwanda/Kinyarwanda)</option>
                                <option value="272">google.rw (Rwanda/Swahili)</option>
                                <option value="276">google.sh (Saint Helena/English)</option>
                                <option value="5801">google.com (Saint Kitts and Nevis/English)</option>
                                <option value="277">google.com.vc (Saint Vincent and the Grenadines/English)</option>
                                <option value="278">google.ws (Samoa/English)</option>
                                <option value="5375">google.sm (San Marino/English)</option>
                                <option value="279">google.sm (San Marino/Italian)</option>
                                <option value="280">google.st (Sao Tome and Principe/Portuguese)</option>
                                <option value="5824">google.st (Sao Tome and Principe/Portuguese (Brazil))</option>
                                <option value="5956">bing.com (Saudi Arabia/Arabic)</option>
                                <option value="5957">bing.com (Saudi Arabia/English)</option>
                                <option value="281">google.com.sa (Saudi Arabia/Arabic)</option>
                                <option value="2858">google.com.sa (Saudi Arabia/English)</option>
                                <option value="1084">search.yahoo.com (Saudi Arabia/All Languages)</option>
                                <option value="937">search.yahoo.com (Saudi Arabia/Arabic)</option>
                                <option value="5377">google.sn (Senegal/English)</option>
                                <option value="282">google.sn (Senegal/French)</option>
                                <option value="283">google.sn (Senegal/Wolof)</option>
                                <option value="3436">google.rs (Serbia/English)</option>
                                <option value="284">google.rs (Serbia/Serbian)</option>
                                <option value="5402">google.rs (Serbia/Serbian (Latin))</option>

                                <option value="286">google.sc (Seychelles/English)</option>
                                <option value="285">google.sc (Seychelles/French)</option>
                                <option value="287">google.sc (Seychelles/Kreol Seselwa)</option>
                                <option value="288">google.com.sl (Sierra Leone/English)</option>
                                <option value="289">google.com.sl (Sierra Leone/Krio)</option>
                                <option value="999">bing.com (Singapore/All Languages)</option>
                                <option value="396">bing.com (Singapore/English)</option>
                                <option value="5731">google.com.sg (Singapore/Chinese)</option>
                                <option value="290">google.com.sg (Singapore/Chinese (Simplified))</option>
                                <option value="5732">google.com.sg (Singapore/Chinese (Traditional))</option>
                                <option value="5737">google.com.sg (Singapore/Chinese (Traditional Han))</option>
                                <option value="293">google.com.sg (Singapore/English)</option>
                                <option value="291">google.com.sg (Singapore/Malay)</option>
                                <option value="292">google.com.sg (Singapore/Tamil)</option>
                                <option value="1085">sg.search.yahoo.com (Singapore/All Languages)</option>
                                <option value="938">sg.search.yahoo.com (Singapore/English)</option>
                                <option value="1000">bing.com (Slovakia/All Languages)</option>
                                <option value="428">bing.com (Slovakia/Slovak)</option>
                                <option value="3439">google.sk (Slovakia/English)</option>
                                <option value="294">google.sk (Slovakia/Slovak)</option>

                                <option value="1001">bing.com (Slovenia/All Languages)</option>
                                <option value="429">bing.com (Slovenia/Slovene)</option>
                                <option value="3438">google.si (Slovenia/English)</option>
                                <option value="295">google.si (Slovenia/Slovene)</option>

                                <option value="296">google.com.sb (Solomon Islands/English)</option>
                                <option value="297">google.so (Somalia/Arabic)</option>
                                <option value="5379">google.so (Somalia/English)</option>
                                <option value="298">google.so (Somalia/Somali)</option>
                                <option value="1002">bing.com (South Africa/All Languages)</option>
                                <option value="399">bing.com (South Africa/English)</option>
                                <option value="303">google.co.za (South Africa/Afrikaans)</option>
                                <option value="301">google.co.za (South Africa/English)</option>
                                <option value="299">google.co.za (South Africa/Northern Sotho)</option>
                                <option value="305">google.co.za (South Africa/Southern Sotho or Sesotho)</option>
                                <option value="304">google.co.za (South Africa/Tswana or Setswana)</option>
                                <option value="302">google.co.za (South Africa/Xhosa or IsiXhosa)</option>
                                <option value="300">google.co.za (South Africa/Zulu or IsiZulu)</option>
                                <option value="5982">search.yahoo.com (South Africa/English)</option>
                                <option value="1003">bing.com (South Korea/All Languages)</option>
                                <option value="417">bing.com (South Korea/Korean)</option>
                                <option value="3396">google.co.kr (South Korea/English)</option>
                                <option value="186">google.co.kr (South Korea/Korean)</option>
                                <option value="1086">search.yahoo.com (South Korea/All Languages)</option>
                                <option value="939">search.yahoo.com (South Korea/Korean)</option>
                                <option value="1004">bing.com (Spain/All Languages)</option>
                                <option value="5835">bing.com (Spain/Catalan)</option>
                                <option value="402">bing.com (Spain/Spanish)</option>
                                <option value="1087">es.search.yahoo.com (Spain/All Languages)</option>
                                <option value="940">es.search.yahoo.com (Spain/English)</option>
                                <option value="941">es.search.yahoo.com (Spain/Spanish)</option>
                                <option value="307">google.es (Spain/Basque)</option>
                                <option value="309">google.es (Spain/Catalan)</option>
                                <option value="2849">google.es (Spain/English)</option>
                                <option value="5401">google.es (Spain/Espanol (Latinoamerica))</option>
                                <option value="306">google.es (Spain/Galician)</option>
                                <option value="26">google.es (Spain/Spanish)</option>

                                <option value="310">google.lk (Sri Lanka/English)</option>
                                <option value="311">google.lk (Sri Lanka/Sinhala)</option>
                                <option value="312">google.lk (Sri Lanka/Tamil)</option>
                                <option value="5822">google.sr (Suriname/French)</option>
                                <option value="5805">google.com (Swaziland/English)</option>
                                <option value="1005">bing.com (Sweden/All Languages)</option>
                                <option value="430">bing.com (Sweden/Swedish)</option>
                                <option value="2893">google.se (Sweden/English)</option>
                                <option value="313">google.se (Sweden/Swedish)</option>
                                <option value="1088">se.search.yahoo.com (Sweden/All Languages)</option>
                                <option value="942">se.search.yahoo.com (Sweden/English)</option>
                                <option value="943">se.search.yahoo.com (Sweden/Swedish)</option>

                                <option value="1006">bing.com (Switzerland/All Languages)</option>
                                <option value="410">bing.com (Switzerland/French)</option>
                                <option value="384">bing.com (Switzerland/German)</option>
                                <option value="5834">bing.com (Switzerland/Italian)</option>
                                <option value="6226">chfr.search.yahoo.com (Switzerland/French)</option>
                                <option value="6227">chit.search.yahoo.com (Switzerland/Italian)</option>
                                <option value="1089">ch.search.yahoo.com (Switzerland/All Languages)</option>
                                <option value="944">ch.search.yahoo.com (Switzerland/English)</option>
                                <option value="945">ch.search.yahoo.com (Switzerland/French)</option>
                                <option value="946">ch.search.yahoo.com (Switzerland/German)</option>
                                <option value="5973">ch.search.yahoo.com (Switzerland/Italian)</option>
                                <option value="3389">google.ch (Switzerland/English)</option>
                                <option value="316">google.ch (Switzerland/French)</option>
                                <option value="315">google.ch (Switzerland/German)</option>
                                <option value="317">google.ch (Switzerland/Italian)</option>
                                <option value="314">google.ch (Switzerland/Romansh)</option>

                                <option value="1007">bing.com (Taiwan/All Languages)</option>
                                <option value="436">bing.com (Taiwan/Chinese)</option>
                                <option value="318">google.com.tw (Taiwan/Chinese)</option>
                                <option value="5727">google.com.tw (Taiwan/Chinese (Simplified))</option>
                                <option value="5730">google.com.tw (Taiwan/Chinese (Traditional))</option>
                                <option value="3419">google.com.tw (Taiwan/English)</option>
                                <option value="3682">tw.search.yahoo.com (Taiwan/Chinese (Traditional))</option>
                                <option value="5323">google.com.tj (Tajikistan/English)</option>
                                <option value="319">google.com.tj (Tajikistan/Russian)</option>
                                <option value="320">google.com.tj (Tajikistan/Tajik)</option>

                                <option value="3400">google.co.tz (Tanzania/English)</option>
                                <option value="321">google.co.tz (Tanzania/Swahili)</option>
                                <option value="1008">bing.com (Thailand/All Languages)</option>
                                <option value="431">bing.com (Thailand/Thai)</option>
                                <option value="3399">google.co.th (Thailand/English)</option>
                                <option value="322">google.co.th (Thailand/Thai)</option>
                                <option value="1091">th.search.yahoo.com (Thailand/All Languages)</option>
                                <option value="948">th.search.yahoo.com (Thailand/English)</option>
                                <option value="5979">th.search.yahoo.com (Thailand/Thai)</option>

                                <option value="323">google.tl (Timor-Leste/Portuguese)</option>
                                <option value="5383">google.tg (Togo/English)</option>
                                <option value="325">google.tg (Togo/Ewe)</option>
                                <option value="324">google.tg (Togo/French)</option>
                                <option value="326">google.tk (Tokelau/English)</option>
                                <option value="327">google.to (Tonga/English)</option>
                                <option value="328">google.to (Tonga/Tonga (Tonga Islands))</option>
                                <option value="329">google.tt (Trinidad and Tobago/Chinese (Traditional Han))</option>
                                <option value="333">google.tt (Trinidad and Tobago/English)</option>
                                <option value="332">google.tt (Trinidad and Tobago/Espanol (Latinoamerica))</option>
                                <option value="331">google.tt (Trinidad and Tobago/French)</option>
                                <option value="330">google.tt (Trinidad and Tobago/Hindi)</option>
                                <option value="334">google.tn (Tunisia/Arabic)</option>
                                <option value="5387">google.tn (Tunisia/English)</option>
                                <option value="335">google.tn (Tunisia/French)</option>
                                <option value="1092">search.yahoo.com (Tunisia/All Languages)</option>
                                <option value="949">search.yahoo.com (Tunisia/Arabic)</option>
                                <option value="950">search.yahoo.com (Tunisia/French)</option>
                                <option value="1009">bing.com (Turkey/All Languages)</option>
                                <option value="432">bing.com (Turkey/Turkish)</option>
                                <option value="3418">google.com.tr (Turkey/English)</option>
                                <option value="336">google.com.tr (Turkey/Turkish)</option>
                                <option value="1093">tr.search.yahoo.com (Turkey/All Languages)</option>
                                <option value="951">tr.search.yahoo.com (Turkey/English)</option>
                                <option value="952">tr.search.yahoo.com (Turkey/Turkish)</option>

                                <option value="5385">google.tm (Turkmenistan/English)</option>
                                <option value="337">google.tm (Turkmenistan/Russian)</option>
                                <option value="339">google.tm (Turkmenistan/Turkmen)</option>
                                <option value="338">google.tm (Turkmenistan/Uzbek)</option>
                                <option value="5826">google.com (Turks and Caicos Islands/English)</option>
                                <option value="3401">google.co.ug (Uganda/English)</option>
                                <option value="342">google.co.ug (Uganda/Ganda)</option>
                                <option value="340">google.co.ug (Uganda/Kinyarwanda)</option>
                                <option value="341">google.co.ug (Uganda/Luo)</option>
                                <option value="5327">google.co.ug (Uganda/Nyankole)</option>
                                <option value="343">google.co.ug (Uganda/Runyakitara)</option>
                                <option value="344">google.co.ug (Uganda/Swahili)</option>
                                <option value="1010">bing.com (Ukraine/All Languages)</option>
                                <option value="433">bing.com (Ukraine/Ukrainian)</option>
                                <option value="3420">google.com.ua (Ukraine/English)</option>
                                <option value="5">google.com.ua (Ukraine/Russian)</option>
                                <option value="372">google.com.ua (Ukraine/Ukrainian)</option>
                                <option value="1094">search.yahoo.com (Ukraine/All Languages)</option>
                                <option value="953">search.yahoo.com (Ukraine/English)</option>
                                <option value="954">search.yahoo.com (Ukraine/Russian)</option>
                                <option value="5954">bing.com (United Arab Emirates/Arabic)</option>
                                <option value="5955">bing.com (United Arab Emirates/English)</option>
                                <option value="345">google.ae (United Arab Emirates/Arabic)</option>
                                <option value="826">google.ae (United Arab Emirates/English)</option>
                                <option value="347">google.ae (United Arab Emirates/Hindi)</option>
                                <option value="346">google.ae (United Arab Emirates/Persian)</option>
                                <option value="348">google.ae (United Arab Emirates/Urdu)</option>
                                <option value="1095">search.yahoo.com (United Arab Emirates/All Languages)</option>
                                <option value="955">search.yahoo.com (United Arab Emirates/Arabic)</option>
                                <option value="956">search.yahoo.com (United Arab Emirates/English)</option>
                                <option value="1011">bing.com (United Kingdom/All Languages)</option>
                                <option value="389">bing.com (United Kingdom/English)</option>
                                <option value="22">google.co.uk (United Kingdom/English)</option>
                                <option value="1096">uk.search.yahoo.com (United Kingdom/All Languages)</option>
                                <option value="957">uk.search.yahoo.com (United Kingdom/English)</option>
                                <option value="1012">bing.com (United States/All Languages)</option>
                                <option value="5958">bing.com (United States/Arabic)</option>
                                <option value="397">bing.com (United States/English)</option>
                                <option value="404">bing.com (United States/Spanish)</option>
                                <option value="959">espanol.search.yahoo.com (United States/Spanish)</option>
                                <option value="3667">google.com (United States/Chinese (Simplified))</option>
                                <option value="3636">google.com (United States/Chinese (Traditional))</option>
                                <option value="3641">google.com (United States/Danish)</option>
                                <option value="3656">google.com (United States/Dutch)</option>
                                <option value="14" selected>google.com (United States/English)</option>
                                <option value="2833">google.com (United States/Espanol (Latinoamerica))</option>
                                <option value="3651">google.com (United States/French)</option>
                                <option value="3646">google.com (United States/German)</option>
                                <option value="3631">google.com (United States/Norwegian)</option>
                                <option value="3616">google.com (United States/Portuguese (Portugal))</option>
                                <option value="3621">google.com (United States/Russian)</option>
                                <option value="2828">google.com (United States/Spanish)</option>
                                <option value="3626">google.com (United States/Swedish)</option>
                                <option value="3662">google.com (United States/Vietnamese)</option>
                                <option value="1097">search.yahoo.com (United States/All Languages)</option>
                                <option value="958">search.yahoo.com (United States/English)</option>

                                <option value="5325">google.com.uy (Uruguay/English)</option>
                                <option value="351">google.com.uy (Uruguay/Espanol (Latinoamerica))</option>
                                <option value="5777">google.com.uy (Uruguay/Spanish)</option>
                                <option value="5329">google.co.uz (Uzbekistan/English)</option>
                                <option value="353">google.co.uz (Uzbekistan/Russian)</option>
                                <option value="352">google.co.uz (Uzbekistan/Uzbek)</option>

                                <option value="355">google.vu (Vanuatu/English)</option>
                                <option value="354">google.vu (Vanuatu/French)</option>
                                <option value="5980">espanol.search.yahoo.com (Venezuela/Spanish)</option>
                                <option value="5331">google.co.ve (Venezuela/English)</option>
                                <option value="356">google.co.ve (Venezuela/Espanol)</option>
                                <option value="5779">google.co.ve (Venezuela/Spanish)</option>
                                <option value="357">google.com.vn (Vietnam/Chinese (Traditional Han))</option>
                                <option value="2977">google.com.vn (Vietnam/English)</option>
                                <option value="359">google.com.vn (Vietnam/French)</option>
                                <option value="358">google.com.vn (Vietnam/Vietnamese)</option>
                                <option value="1098">vn.search.yahoo.com (Vietnam/All Languages)</option>
                                <option value="960">vn.search.yahoo.com (Vietnam/Chinese (Traditional Han))</option>
                                <option value="961">vn.search.yahoo.com (Vietnam/English)</option>
                                <option value="5981">vn.search.yahoo.com (Vietnam/Vietnamese)</option>
                                <option value="361">google.co.vi (Virgin Islands US/English)</option>
                                <option value="5828">google.com (Yemen/Arabic)</option>
                                <option value="363">google.co.zm (Zambia/Chichewa)</option>
                                <option value="365">google.co.zm (Zambia/Chitumbuka-Chisenga)</option>
                                <option value="366">google.co.zm (Zambia/English)</option>
                                <option value="364">google.co.zm (Zambia/IciBemba)</option>
                                <option value="362">google.co.zm (Zambia/Silozi)</option>
                                <option value="367">google.co.zw (Zimbabwe/Chichewa)</option>
                                <option value="368">google.co.zw (Zimbabwe/English)</option>
                                <option value="370">google.co.zw (Zimbabwe/Shona)</option>
                                <option value="369">google.co.zw (Zimbabwe/Tswana)</option>
                                <option value="371">google.co.zw (Zimbabwe/Zulu)</option>
                            </select>
                        </div>
                        <div>
                            <label for="top_ten_search_location" class="step-text-secondary">Search Location</label>
                            <select id="top_ten_search_location" name="top_ten_search_location"
                                    class="xagio-input-select xagio-input-select-gray"
                                    data-default="<?php echo wp_kses_post($xagio_ai_wizard_location); ?>">
                                <?php
                                foreach ($xagio_country_list as $xagio_item) {
                                    ?>
                                    <option value="<?php echo esc_html($xagio_item['location_code']) ?>" <?php if ($xagio_item['location_code'] == $xagio_ai_wizard_location)
                                        echo 'selected'; ?>><?php echo esc_html($xagio_item['location_name']) ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>


                    <div class="step-text-secondary xagio-margin-top-large">
                        Seed Keyword for your website is:
                    </div>

                    <div class="xagio-alert keyword-search">
                        <div class="keyword-example"></div>
                        <button type="button" title="Swap words"
                                class="xagio-button xagio-button-mini xagio-button-outline" id="swap-words">
                            <i class="xagio-icon xagio-icon-sync"></i>
                        </button>
                    </div>

                    <p class="help">If you feel the keyword is correct, press <b>Search</b> to continue, if not go back
                        to make adjustments.</p>

                    <div class="xagio-alert xagio-alert-primary">
                        <i class="xagio-icon xagio-icon-info"></i> By clicking on Search button we will pull Top 10
                        competitor websites on Google for provided keyword which will be presented on the next step.
                    </div>

                    <div class="ai-wizard-buttons">
                        <a href="#" class="xagio-button xagio-button-outline prev-step-search"><i
                                    class="xagio-icon xagio-icon-arrow-left"></i> Previous</a>
                        <a href="#" class="xagio-button xagio-button-primary search-top-ten"><i
                                    class="xagio-icon xagio-icon-arrow-right"></i> Continue</a>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="ocw-step ocw-step-2" style="display: none">

        <div class="xagio-flex xagio-flex-align-center">

            <div class="xagio-width-max900">

                <h5 class="aiwizard-breadcrumb"><img class="ai-wizard-breadcrumb-xagio-image"
                                                     src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/agent_x_logo.webp"
                                                     alt=""/>

                    <div class="xags-container-heading">

                        <div class="xags-container">
                            <div class="xags-item xrenew" data-xagio-tooltip data-xagio-tooltip-position="bottom"
                                 data-xagio-title="These are your current XAGS (xRenew)">
                                <img src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/xRenew.png" alt="xR"
                                     class="xags-icon">
                                <span class="value">0</span>
                            </div>
                            <span class="xags-divider"></span>
                            <div class="xags-item xbanks" data-xagio-tooltip data-xagio-tooltip-position="bottom"
                                 data-xagio-title="These are your current XAGS (xBank)">
                                <img src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/xBanks.png" alt="xB"
                                     class="xags-icon">
                                <span class="value">0</span>
                            </div>
                        </div>

                        <a href="https://xagio.com/store/" target="_blank"
                           class="xagio-button xagio-button-secondary"><i class="xagio-icon xagio-icon-store"></i>
                            PURCHASE XAGS</a>

                    </div>

                </h5>

                <div class="step-body">

                    <div class="xagio-alert xagio-alert-primary top-ten-results-info">
                        <i class="xagio-icon xagio-icon-info"></i> Now select which website most resembles what you want
                        to create, and we are Audit and pull their ranking keywords
                    </div>

                    <div class="xagio-margin-top-medium xagio-agent-type">

                        <div class="xagio-flex-even-columns xagio-flex-gap-medium">

                            <div>

                                <p class="slider-label">Filter results to only contain keyword below</p>
                                <div class="xagio-slider-container xagio-slider-with-grid">
                                    <input type="hidden" name="keyword_contain" id="keyword_contain" value="1"/>
                                    <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button on" data-element="keyword_contain"></span>
                                    </div>
                                    <input type="text" class="xagio-input-text-mini main_keyword_contain" value="">
                                </div>

                            </div>
                            <div>
                                <div>
                                    <p class="slider-label">Location</p>
                                    <input type="hidden" name="lang_code" id="top-ten-language" value="US"/>
                                    <select id="top-ten-language-select" name="lang">
                                        <option value="fr" data-lang="fr" data-lang-code="DZ">Algeria (fr)</option>
                                        <option value="es" data-lang="es" data-lang-code="AR">Argentina (es)</option>
                                        <option value="en" data-lang="en" data-lang-code="AU">Australia (en)</option>
                                        <option value="de" data-lang="de" data-lang-code="AT">Austria (de)</option>
                                        <option value="bn" data-lang="bn" data-lang-code="BD">Bangladesh (bn)</option>
                                        <option value="fr" data-lang="fr" data-lang-code="BE">Belgium (fr)</option>
                                        <option value="nl" data-lang="nl" data-lang-code="BE">Belgium (nl)</option>
                                        <option value="es" data-lang="es" data-lang-code="BO">Bolivia (es)</option>
                                        <option value="pt" data-lang="pt" data-lang-code="BR">Brazil (pt)</option>
                                        <option value="bg" data-lang="bg" data-lang-code="BG">Bulgaria (bg)</option>
                                        <option value="en" data-lang="en" data-lang-code="CA">Canada (en)</option>
                                        <option value="es" data-lang="es" data-lang-code="CL">Chile (es)</option>
                                        <option value="es" data-lang="es" data-lang-code="CO">Colombia (es)</option>
                                        <option value="es" data-lang="es" data-lang-code="CR">Costa Rica (es)</option>
                                        <option value="hr" data-lang="hr" data-lang-code="HR">Croatia (hr)</option>
                                        <option value="cs" data-lang="cs" data-lang-code="CZ">Czechia (cs)</option>
                                        <option value="da" data-lang="da" data-lang-code="DK">Denmark (da)</option>
                                        <option value="es" data-lang="es" data-lang-code="EC">Ecuador (es)</option>
                                        <option value="ar" data-lang="ar" data-lang-code="EG">Egypt (ar)</option>
                                        <option value="es" data-lang="es" data-lang-code="SV">El Salvador (es)</option>
                                        <option value="et" data-lang="et" data-lang-code="EE">Estonia (et)</option>
                                        <option value="fi" data-lang="fi" data-lang-code="FI">Finland (fi)</option>
                                        <option value="fr" data-lang="fr" data-lang-code="FR">France (fr)</option>
                                        <option value="de" data-lang="de" data-lang-code="DE">Germany (de)</option>
                                        <option value="el" data-lang="el" data-lang-code="GR">Greece (el)</option>
                                        <option value="es" data-lang="es" data-lang-code="GT">Guatemala (es)</option>
                                        <option value="zh_tw" data-lang="zh_tw" data-lang-code="HK">Hong Kong (zh_tw)
                                        </option>
                                        <option value="hu" data-lang="hu" data-lang-code="HU">Hungary (hu)</option>
                                        <option value="en" data-lang="en" data-lang-code="IN">India (en)</option>
                                        <option value="id" data-lang="id" data-lang-code="ID">Indonesia (id)</option>
                                        <option value="en" data-lang="en" data-lang-code="IE">Ireland (en)</option>
                                        <option value="iw" data-lang="iw" data-lang-code="IL">Israel (iw)</option>
                                        <option value="ar" data-lang="ar" data-lang-code="IL">Israel (ar)</option>
                                        <option value="it" data-lang="it" data-lang-code="IT">Italy (it)</option>
                                        <option value="ja" data-lang="ja" data-lang-code="JP">Japan (ja)</option>
                                        <option value="ms" data-lang="ms" data-lang-code="MY">Malaysia (ms)</option>
                                        <option value="en" data-lang="en" data-lang-code="MT">Malta (en)</option>
                                        <option value="es" data-lang="es" data-lang-code="MX">Mexico (es)</option>
                                        <option value="nl" data-lang="nl" data-lang-code="NL">Netherlands (nl)</option>
                                        <option value="en" data-lang="en" data-lang-code="NZ">New Zealand (en)</option>
                                        <option value="es" data-lang="es" data-lang-code="NI">Nicaragua (es)</option>
                                        <option value="no" data-lang="no" data-lang-code="NO">Norway (no)</option>
                                        <option value="es" data-lang="es" data-lang-code="PY">Paraguay (es)</option>
                                        <option value="es" data-lang="es" data-lang-code="PE">Peru (es)</option>
                                        <option value="pl" data-lang="pl" data-lang-code="PL">Poland (pl)</option>
                                        <option value="pt" data-lang="pt" data-lang-code="PT">Portugal (pt)</option>
                                        <option value="ro" data-lang="ro" data-lang-code="RO">Romania (ro)</option>
                                        <option value="ru" data-lang="ru" data-lang-code="RU">Russia (ru)</option>
                                        <option value="ar" data-lang="ar" data-lang-code="SA">Saudi Arabia (ar)</option>
                                        <option value="sr" data-lang="sr" data-lang-code="RS">Serbia (sr)</option>
                                        <option value="en" data-lang="en" data-lang-code="SG">Singapore (en)</option>
                                        <option value="sk" data-lang="sk" data-lang-code="SK">Slovakia (sk)</option>
                                        <option value="sl" data-lang="sl" data-lang-code="SI">Slovenia (sl)</option>
                                        <option value="en" data-lang="en" data-lang-code="ZA">South Africa (en)</option>
                                        <option value="es" data-lang="es" data-lang-code="ES">Spain (es)</option>
                                        <option value="en" data-lang="en" data-lang-code="LK">Sri Lanka (en)</option>
                                        <option value="sv" data-lang="sv" data-lang-code="SE">Sweden (sv)</option>
                                        <option value="fr" data-lang="fr" data-lang-code="CH">Switzerland (fr)</option>
                                        <option value="de" data-lang="de" data-lang-code="CH">Switzerland (de)</option>
                                        <option value="zh_tw" data-lang="zh_tw" data-lang-code="TW">Taiwan (zh_tw)
                                        </option>
                                        <option value="th" data-lang="th" data-lang-code="TH">Thailand (th)</option>
                                        <option value="ar" data-lang="ar" data-lang-code="TN">Tunisia (ar)</option>
                                        <option value="tr" data-lang="tr" data-lang-code="TR">Turkey (tr)</option>
                                        <option value="uk" data-lang="uk" data-lang-code="UA">Ukraine (uk)</option>
                                        <option value="ru" data-lang="ru" data-lang-code="UA">Ukraine (ru)</option>
                                        <option value="en" data-lang="en" data-lang-code="AE">United Arab Emirates (en)
                                        </option>
                                        <option value="ar" data-lang="ar" data-lang-code="AE">United Arab Emirates (ar)
                                        </option>
                                        <option value="en" data-lang="en" data-lang-code="GB">United Kingdom (en)
                                        </option>
                                        <option value="en" data-lang="en" data-lang-code="US" selected="">United States
                                            (en)
                                        </option>
                                        <option value="es" data-lang="es" data-lang-code="UY">Uruguay (es)</option>
                                        <option value="es" data-lang="es" data-lang-code="VE">Venezuela (es)</option>
                                        <option value="vi" data-lang="vi" data-lang-code="VN">Vietnam (vi)</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <br>

                        <span class="small-info">
                            <i class="xagio-icon xagio-icon-info"></i> Unchecking the toggle above may result in Agent producing a lot of keywords and groups unrelated to your specific keyword.
                        </span>

                    </div>

                    <div class="top-ten-results xagio-margin-top-large"></div>

                </div>

                <div class="top-ten-pagination-container">

                </div>

                <div class="ai-wizard-buttons ai-wizard-sticky-buttons">
                    <div class="ai-wizard-cost-label" style="display: none;">
                        <div class="xag-cost-container xagio-flex-gap-small">
                            <i class="xagio-icon xagio-icon-info"></i>
                            <div id="xagsCost">This action will cost you
                                <div>
                                    <img src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/xRenew.png" alt="xR"
                                         class="xags-icon"><span>0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="xagio-flex xagio-flex-gap-medium">
                        <a href="#" class="xagio-button xagio-button-outline prev-step-2"><i
                                    class="xagio-icon xagio-icon-close"></i> Cancel</a>
                        <a href="#" class="xagio-button xagio-button-primary create-project"><i
                                    class="xagio-icon xagio-icon-check"></i> Continue</a>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <div class="ocw-step ocw-step-3" style="display: none">
        <div class="loading hidden"><div class="ocw-loading-text"> Clustering groups...</div></div>

        <div class="xagio-flex xagio-flex-align-center">

            <div class="xagio-width-max">

                <h5 class="aiwizard-breadcrumb"><img class="ai-wizard-breadcrumb-xagio-image"
                                                     src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/agent_x_logo.webp"
                                                     alt=""/>

                    <div class="xags-container-heading">

                        <div class="xags-container">
                            <div class="xags-item xrenew" data-xagio-tooltip data-xagio-tooltip-position="bottom"
                                 data-xagio-title="These are your current XAGS (xRenew)">
                                <img src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/xRenew.png" alt="xR"
                                     class="xags-icon">
                                <span class="value">0</span>
                            </div>
                            <span class="xags-divider"></span>
                            <div class="xags-item xbanks" data-xagio-tooltip data-xagio-tooltip-position="bottom"
                                 data-xagio-title="These are your current XAGS (xBank)">
                                <img src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/xBanks.png" alt="xB"
                                     class="xags-icon">
                                <span class="value">0</span>
                            </div>
                        </div>

                        <a href="https://xagio.com/store/" target="_blank"
                           class="xagio-button xagio-button-secondary"><i class="xagio-icon xagio-icon-store"></i>
                            PURCHASE XAGS</a>

                    </div>

                </h5>

                <div class="step-body">

                    <div class="project-dashboard">

                        <div class="project-header xagio-margin-bottom-large">
                            <h1 class="project-name"><i class="xagio-icon xagio-icon-file"></i> #00: Untitled Project
                            </h1>


                            <div class="project-actions">

                                <button type="button" class="xagio-group-button ai-clustering"><i
                                            class="xagio-icon xagio-icon-ai"></i> AI Clustering</button>

                                <button type="button" class="xagio-group-button global-wordCloud" data-xagio-tooltip
                                        data-xagio-title="Open Global Word Cloud"><i
                                            class="xagio-icon xagio-icon-cloud"></i></button>

                                <!-- Keyword Functions -->
                                <div class="xagio-dropdown-simple actions-button">
                                    <!-- This is the button toggling the dropdown -->
                                    <button class="xagio-button xagio-button-primary keywords-action-button">Keywords <i
                                                class="xagio-icon xagio-icon-arrow-down"></i></button>
                                    <!-- This is the dropdown -->
                                    <ul class="xagio-button-dropdown">
                                        <li><a href="#" class="phraseMatch" data-xagio-dropdown-close data-group-id="0">Cluster
                                                Keywords</a></li>
                                        <li><a href="#" class="seedKeyword" data-xagio-dropdown-close data-group-id="0">Seed
                                                Keywords</a></li>
                                        <li><a href="#" class="consolidateKeywords" data-xagio-dropdown-close
                                               data-xagio-tooltip
                                               data-xagio-title="Move all keywords into a new group in this project">Consolidate
                                                Keywords</a></li>
                                    </ul>
                                </div>

                                <!-- Group Functions -->
                                <div class="xagio-dropdown-simple actions-button">
                                    <!-- This is the button toggling the dropdown -->
                                    <button class="xagio-button xagio-button-primary">Groups <i
                                                class="xagio-icon xagio-icon-arrow-down"></i></button>
                                    <!-- This is the dropdown -->
                                    <ul class="xagio-button-dropdown">
                                        <li><a href="#" class="addGroup" data-xagio-dropdown-close>Add Group</a></li>
                                        <li><a href="#" class="deleteGroups" data-xagio-dropdown-close>Delete Selected
                                                Groups</a></li>
                                        <li><a href="#" class="deleteEmptyGroups" data-xagio-dropdown-close>Delete Empty
                                                Groups</a></li>
                                        <li><a href="#" class="selectAllGroups" data-xagio-dropdown-close>Select All
                                                Groups</a></li>
                                        <li><a href="#" class="deselectAllGroups" data-xagio-dropdown-close>DeSelect All
                                                Groups</a></li>
                                    </ul>
                                </div>

                                <button class="xagio-button xagio-button-primary xagio-action-button add-empty-group"
                                        data-xagio-tooltip data-xagio-tooltip-position="bottom"
                                        data-xagio-title="Add Empty Group"><i class="xagio-icon xagio-icon-plus"></i>
                                </button>

                                <div class="competition-options-holder" style="display: none;">
                                    <div>
                                        Competition Options:
                                    </div>
                                    <div>
                                        <select id="getCompetition_languageCode"
                                                class="xagio-input-select xagio-input-select-gray" name="language"
                                                data-default="<?php echo ($xagio_language != null) ? esc_attr($xagio_language) : "en"; ?>">
                                            <option value="">-- All Languages --</option>
                                            <option value="ar">Arabic</option>
                                            <option value="bn">Bengali</option>
                                            <option value="bg">Bulgarian</option>
                                            <option value="ca">Catalan</option>
                                            <option value="zh_CN">Chinese (Simplified)</option>
                                            <option value="zh_TW">Chinese (Traditional)</option>
                                            <option value="hr">Croatian</option>
                                            <option value="cs">Czech</option>
                                            <option value="da">Danish</option>
                                            <option value="nl">Dutch</option>
                                            <option value="en">English</option>
                                            <option value="et">Estonian</option>
                                            <option value="fa">Farsi</option>
                                            <option value="fi">Finnish</option>
                                            <option value="fr">French</option>
                                            <option value="de">German</option>
                                            <option value="el">Greek</option>
                                            <option value="iw">Hebrew (old)</option>
                                            <option value="hi">Hindi</option>
                                            <option value="hu">Hungarian</option>
                                            <option value="is">Icelandic</option>
                                            <option value="id">Indonesian</option>
                                            <option value="it">Italian</option>
                                            <option value="ja">Japanese</option>
                                            <option value="ko">Korean</option>
                                            <option value="lv">Latvian</option>
                                            <option value="lt">Lithuanian</option>
                                            <option value="ms">Malay</option>
                                            <option value="no">Norwegian</option>
                                            <option value="pl">Polish</option>
                                            <option value="pt">Portuguese</option>
                                            <option value="ro">Romanian</option>
                                            <option value="ru">Russian</option>
                                            <option value="sr">Serbian</option>
                                            <option value="sk">Slovak</option>
                                            <option value="sl">Slovenian</option>
                                            <option value="es">Spanish</option>
                                            <option value="sv">Swedish</option>
                                            <option value="tl">Tagalog</option>
                                            <option value="ta">Tamil</option>
                                            <option value="te">Telugu</option>
                                            <option value="th">Thai</option>
                                            <option value="tr">Turkish</option>
                                            <option value="uk">Ukrainian</option>
                                            <option value="ur">Urdu</option>
                                            <option value="vi">Vietnamese</option>
                                            <option value="zh_CN">Chinese (Simplified)</option>
                                            <option value="zh_TW">Chinese (Traditional)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <select id="getCompetition_locationCode"
                                                class="xagio-input-select xagio-input-select-gray" name="location"
                                                data-default="<?php echo ($xagio_country != null) ? esc_attr($xagio_country) : "United States"; ?>">
                                            <option value="">WorldWide</option>
                                            <?php
                                            foreach ($xagio_country_list as $xagio_item) {
                                                ?>
                                                <option value="<?php echo esc_html($xagio_item['location_name']) ?>"><?php echo esc_html($xagio_item['location_name']) ?></option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="rank-tracker-options-holder" style="display: none;">

                                    <?php if (is_array($xagio_rank_tracker_se)) : ?>
                                        <?php foreach ($xagio_rank_tracker_se as $xagio_se): ?>
                                            <input type="hidden" name="search_engine[]"
                                                   value="<?php echo wp_kses_post($xagio_se['id']); ?>">
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <select id="search_country" class="xagio-input-select xagio-input-select-gray"
                                            name="search_country">
                                        <?php
                                        foreach ($xagio_country_list as $xagio_item) {
                                            ?>
                                            <option value="<?php echo esc_html($xagio_item['location_code']) ?>"
                                                    data-countrycode="<?php echo esc_html($xagio_item['country_iso_code']) ?>" <?php if ($xagio_item['location_code'] == $xagio_rank_tracker_country) { ?> selected <?php } ?>><?php echo esc_html($xagio_item['location_name']) ?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                    <select id="search_location" class="xagio-input-select xagio-input-select-gray"
                                            name="search_location">
                                        <?php if (!empty($xagio_rank_tracker_city)) { ?>
                                            <option value="<?php echo esc_html($xagio_rank_tracker_city['id']) ?>"><?php echo esc_html($xagio_rank_tracker_city['text']) ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <div class="keyword-cloud-global-holder">
                            <div class="xagio-keyword-cloud-global"></div>
                            <div class="seed-keywords-global" style="display: none">

                                <div class="seed-keywords-panel-start">
                                    <i class="xagio-icon xagio-icon-info"></i>
                                    <div>Select keywords to start seeding.</div>
                                </div>

                                <div class="seed-keywords-panel-select" style="display: none">
                                    <div>
                                        Seed groups for the following keywords
                                    </div>

                                    <form id="seedPanelForm">
                                        <div class="seed-keywords-inputs">

                                        </div>
                                    </form>

                                    <button class="xagio-button xagio-button-primary seed-keywords-panel-global"
                                            type="button"><i class="xagio-icon xagio-icon-check"></i> Seed New Group
                                    </button>
                                </div>
                            </div>
                        </div>


                        <div class="project-groups">
                            <div class="data">

                            </div>

                        </div>

                    </div>

                </div>

                <div class="ai-wizard-buttons ai-wizard-sticky-buttons">
                    <a href="#" class="xagio-button xagio-button-outline reset-wizard"><i
                                class="xagio-icon xagio-icon-refresh"></i> Reset Agent X</a>
                    <div class="calculated-prices"></div>
                    <a href="#" class="xagio-button xagio-button-primary start-wizard"><i
                                class="xagio-icon xagio-icon-arrow-right"></i> Run Agent X</a>
                </div>

            </div>

        </div>

    </div>

    <div class="ocw-step ocw-step-4" style="display: none">

        <div class="xagio-flex xagio-flex-align-center">

            <div class="xagio-width-max900">

                <h5 class="aiwizard-breadcrumb">
                    <img class="ai-wizard-breadcrumb-xagio-image"
                         src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/agent_x_logo.webp" alt=""/>

                </h5>

                <div class="ocw-info">
                    For the fastest completion of Agent X, it’s recommended to stay on this page.<br><br>
                    However, you may still leave this page and let the process run in the background —
                    just note that in most cases, this will result in slower performance.
                    <br><br>You will be notified by e-mail "<?php echo wp_kses_post($xagio_admin_email); ?>" once the wizard
                    is completed.
                </div>


                <?php if (isset($xagio_ocw_steps['data']['error'])): ?>
                    <div class="xagio-alert xagio-alert-danger">
                        An error has occurred: <?php echo wp_kses_post($xagio_ocw_steps['data']['error']); ?> Please reset the
                        Wizard and try again.
                    </div>
                <?php endif; ?>

                <div class="step-body">

                    <div class="xagio-ocw-progress">
                        <div class="xagio-ocw-progress-item" data-id="competition">
                            <div class="xagio-ocw-progress-item-header">
                                <div class="xagio-ocw-progress-item-icon">
                                    <i class="xagio-icon xagio-icon-refresh xagio-icon-spin"></i>
                                </div>
                                <div class="xagio-ocw-progress-item-text">Getting Keyword Competition Metrics</div>
                            </div>
                            <div class="xagio-ocw-progress-item-info">
                                Analyzing keyword competition by evaluating occurrences in <b>InTitle</b> and
                                <b>InURL</b> to gauge ranking difficulty.
                            </div>
                        </div>

                        <div class="xagio-ocw-progress-item" data-id="optimize">
                            <div class="xagio-ocw-progress-item-header">
                                <div class="xagio-ocw-progress-item-icon">
                                    <i class="xagio-icon xagio-icon-refresh xagio-icon-spin"></i>
                                </div>
                                <div class="xagio-ocw-progress-item-text">Optimizing Groups with AI</div>
                            </div>
                            <div class="xagio-ocw-progress-item-info">
                                Using AI to analyze, optimize, and generate the best possible SEO title, meta
                                description, and H1 for each keyword group.
                            </div>
                        </div>

                        <div class="xagio-ocw-progress-item" data-id="pages">
                            <div class="xagio-ocw-progress-item-header">
                                <div class="xagio-ocw-progress-item-icon">
                                    <i class="xagio-icon xagio-icon-refresh xagio-icon-spin"></i>
                                </div>
                                <div class="xagio-ocw-progress-item-text">Creating WordPress Pages</div>
                            </div>
                            <div class="xagio-ocw-progress-item-info">
                                Automatically generating WordPress pages based on the optimized keyword groups and
                                assigned metadata.
                            </div>
                        </div>

                        <div class="xagio-ocw-progress-item" data-id="content">
                            <div class="xagio-ocw-progress-item-header">
                                <div class="xagio-ocw-progress-item-icon">
                                    <i class="xagio-icon xagio-icon-refresh xagio-icon-spin"></i>
                                </div>
                                <div class="xagio-ocw-progress-item-text">Generating Page Content with AI</div>
                            </div>
                            <div class="xagio-ocw-progress-item-info">
                                Leveraging AI to create high-quality, SEO-optimized content tailored to each keyword and
                                page.
                            </div>
                        </div>

                        <div class="xagio-ocw-progress-item" data-id="schema">
                            <div class="xagio-ocw-progress-item-header">
                                <div class="xagio-ocw-progress-item-icon">
                                    <i class="xagio-icon xagio-icon-refresh xagio-icon-spin"></i>
                                </div>
                                <div class="xagio-ocw-progress-item-text">Generating Page Schemas with AI</div>
                            </div>
                            <div class="xagio-ocw-progress-item-info">
                                Creating structured data schemas (JSON-LD) to enhance search engine understanding and
                                improve visibility in search results.
                            </div>
                        </div>

                    </div>


                </div>

                <div class="ai-wizard-buttons">
                    <a href="#" class="xagio-button xagio-button-outline reset-wizard"><i
                                class="xagio-icon xagio-icon-refresh"></i> Stop & Reset Agent X</a>
                </div>

            </div>

        </div>

    </div>

    <div class="ocw-step ocw-step-finish" style="display: none">

        <div class="xagio-flex xagio-flex-align-center">

            <div class="xagio-width-max750">


                <h5 class="aiwizard-breadcrumb">
                    <img class="ai-wizard-breadcrumb-xagio-image"
                         src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logos/agent_x_logo.webp" alt=""/>

                </h5>

                <div class="step-body">

                    <div class="xagio-ocw-grid">
                        <div><img
                                  src="<?php echo esc_url(XAGIO_URL); ?>assets/img/xagio-rocket.webp"
                                  alt=""/></div>
                        <div>
                            <h2>Your Website is Ready for 🚀 Takeoff!</h2>

                            <div class="xagio-ocw-message">
                                Your new pages have been created, optimized and customized, you're ready to go live.
                            </div>

                        </div>
                    </div>



                    <a href="/" class="xagio-button xagio-button-orange" data-text="> View your Website <">View your
                        Website</a>

                    <a href="#" class="reset-wizard"><i
                                class="xagio-icon xagio-icon-refresh"></i> Reset Agent X & Start Over</a>

                </div>

            </div>

        </div>

    </div>

</div>


<div class="xagio-column-container box-template template" data-category="">
    <div class="xagio-theme-picker">
        <span class="close-theme-select"><i class="xagio-icon xagio-icon-close"></i></span>
        <p class="theme-picker-title"></p>
        <div class="theme-picker-buttons">
            <button type="button" class="xagio-button xagio-button-elementor select-theme-editor" style="display: none" data-platform="elementor"><i class="xagio-icon xagio-icon-check"></i> Use Elementor</button>
            <button type="button" class="xagio-button xagio-button-gutenberg select-theme-editor" style="display: none" data-platform="gutenberg">Use Gutenberg</button>
        </div>
    </div>

    <figure>
        <img class="screenshot" alt="screenshot" src=""/>
    </figure>

    <span class="template-platform-box xagio-flex m-t-10 gap-5"></span>

    <div class="actions xagio-flex-row xagio-align-center xagio-space-between m-t-10 gap-10">
        <!-- add activate, preview buttons -->
        <div class="template-name">

        </div>
        <div class="buttons xagio-flex-row xagio-align-center gap-5">
            <a href="https://templates.xagio.net/" target="_blank" class="xagio-btn btn-blue btn-small preview-template"
               data-xagio-tooltip data-xagio-title='Preview Template'><i class="xagio-icon xagio-icon-search"></i></a>
            <a data-template="" data-id="" href="#"
               class="xagio-btn btn-height-30 template-action-button">Select</a>
        </div>
    </div>
</div>

<div class="top-ten-result-template template">
    <div class="top-ten-result">
        <input type="checkbox" name="select-website" class="select-website" id="select-website">
        <a href="#" target="_blank" class="g-url"></a>
        <label class="g-title" for="select-website"></label>
        <p class="g-desc"></p>
    </div>
</div>

<div data-name="Group Name" class="xagio-group template" data-post-type="">
    <div class="group-action-buttons">
        <div class="group-name">
            <!-- Bulk Manage Groups -->
            <input type="checkbox" class="groupSelect" checked/>
            <input type="text" class="groupInput" placeholder="eg. My Group" value="" name="group_name"/>
            <h3></h3>
        </div>
        <div class="group-buttons">
            <div class="action-buttons-holder">

                <button type="button" class="xagio-group-button setHome" data-xagio-tooltip
                        data-xagio-title="Set as Homepage"><i class="xagio-icon xagio-icon-home"></i></button>

                <div class="xagio-dropdown-simple" data-xagio-tooltip data-xagio-title="Actions">
                    <button type="button" class="xagio-group-button groupSettings"><i
                                class="xagio-icon xagio-icon-gear"></i></button>
                    <ul class="xagio-button-dropdown">
                        <li class="xagio-nav-header">KEYWORD MANAGEMENT</li>

                        <li><a href="#" class="addKeyword" data-xagio-dropdown-close>Add Keywords</a></li>
                        <li><a href="#" class="deleteKeywords" data-xagio-dropdown-close>Delete Selected Keywords</a>
                        </li>
                        <li><a href="#" class="copyKeywords" data-xagio-dropdown-close>Copy To Clipboard</a></li>
                        <li><a href="#" class="seedKeyword" data-xagio-dropdown-close data-group-id="0">Seed
                                Keywords</a></li>
                        <li><a href="#" class="phraseMatch" data-xagio-dropdown-close data-group-id="0">Cluster
                                Keywords</a></li>
                    </ul>
                </div>

                <button type="button" class="xagio-group-button wordCloud" data-xagio-tooltip
                        data-xagio-title="Open Word Cloud"><i class="xagio-icon xagio-icon-cloud"></i></button>

                <button type="button" class="xagio-group-button deleteGroup" data-xagio-tooltip
                        data-xagio-title="Delete Group"><i class="xagio-icon xagio-icon-delete"></i></button>

            </div>
        </div>
    </div>

    <form class="updateGroup">
        <div class="group-seo">
            <input type="hidden" name="action" value="xagio_updateGroup"/>
            <input type="hidden" name="group_id" value="0"/>
        </div>
    </form>

    <div class="xagio-keyword-cloud"></div>

    <div class="group-keywords">
        <!-- Keywords -->
        <form class="updateKeywords">
            <table class="keywords">
                <thead>
                <tr>
                    <th class="select-all xagio-text-center"><i class="xagio-icon xagio-icon-check-double"></i></th>
                    <th>Keyword</th>
                    <th>Volume</th>
                    <th>CPC&nbsp;($)</th>
                </tr>
                </thead>
                <tbody class="keywords-data uk-sortable">
                <tr>
                    <td colspan="11">
                        <div class="empty-keywords">
                            <i class="xagio-icon xagio-icon-warning"></i> No added keywords yet...
                            <button type="button" class="xagio-button xagio-button-primary addKeyword"><i
                                        class="xagio-icon xagio-icon-plus"></i> Add Keyword(s)
                            </button>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>

<!-- Cloud Template -->
<div class="cloud template hide" style="display: none;"></div>

<!-- Modal for AI prices -->
<dialog id="aiPrice" class="xagio-modal">

    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-info"></i> Requesting AI Input</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>

    <div class="xagio-modal-body">
        <input type="hidden" id="suggestion-main-keyword" value="">
        <p class="ai-heading">
            <i class="xagio-icon xagio-icon-ai"></i> You are about to request <span class="input-name">AI Generated Content</span>. Below are the details regarding this operation.
        </p>

        <p>
            This prompt performs strict keyword clustering by normalizing all service, role, and location terms (e.g., singular form, merged synonyms for modifiers) and grouping them into unique clusters based on those normalized keys. It recursively audits and merges/splits clusters to ensure that no duplicates, missing assignments, or invalid merges (like true synonyms such as “attorney” and “lawyer”) exist, enforcing complete consistency and accuracy in the final output.
        </p>

        <div class="xagio-flex-even-columns xagio-flex-gap-medium xagio-margin-top-medium">
            <div class="ai-avg-price-box">
                Average XAGS cost for this is: <span class="average-price">0</span>
            </div>
            <div class="ai-avg-price-box">
                Your remaining XAGS are: <span class="ai-credits">0</span>
            </div>
        </div>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <select name="prompt_id" id="prompt_id" class="xagio-input-select xagio-input-select-gray">
            </select>
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
            <button type="button" class="xagio-button xagio-button-primary makeAiRequest" data-target=""><i class="xagio-icon xagio-icon-check"></i> Continue</button>
        </div>
    </div>
</dialog>

<!-- Image Configuration -->
<dialog id="imageConfiguration" class="xagio-modal xagio-modal-lg">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-image"></i> Configure Image</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">

        <input type="hidden" name="id" value="0"/>

        <div class="xagio-2-column-grid">
            <div class="image-preview-holder">
                <img class="image-preview" src=""/>
            </div>
            <div class="image-configuration">
                <div class="group">
                    <label>Image AI Task Type:</label>
                <fieldset>
                    <label class="xagio-radio"><input name="ai_type" value="edit" type="radio" checked> Edit this Image</label>
                    <label class="xagio-radio"><input name="ai_type" value="generate" type="radio"> Generate a new Image</label>
                </fieldset>
                </div>
                <div class="group">
                    <label>Alt Text:</label>
                    <input type="text" class="image-alt"/>
                </div>
                <div class="group">
                    <label>Prompt:</label>
                    <textarea rows="10"></textarea>
                </div>
            </div>

        </div>


        <div class="xagio-2-column-65-35-grid xagio-margin-top-large">
            <div class="xagio-flex xagio-flex-gap-medium">
                <input type="hidden" value="0" id="groupId">
            </div>
            <div class="xagio-flex-right xagio-flex-gap-medium">
                <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i
                            class="xagio-icon xagio-icon-close"></i> Cancel
                </button>
                <button type="button" class="xagio-button xagio-button-primary image-save-config"><i
                            class="xagio-icon xagio-icon-save"></i> Save
                </button>
            </div>
        </div>
    </div>
</dialog>

<!-- Delete Group -->
<dialog id="deleteGroup" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-download"></i> Confirm Delete</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <label class="modal-label">Are you sure that you want to remove this group permanently?</label>
        <div class="xagio-2-column-65-35-grid xagio-margin-top-large">
            <div class="xagio-flex xagio-flex-gap-medium">
                <input type="hidden" value="0" id="groupId">
            </div>
            <div class="xagio-flex-right xagio-flex-gap-medium">
                <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i
                            class="xagio-icon xagio-icon-close"></i> Cancel
                </button>
                <button type="button" class="xagio-button xagio-button-primary delete-group"><i
                            class="xagio-icon xagio-icon-check"></i> Ok
                </button>
            </div>
        </div>
    </div>
</dialog>

<!-- Delete Keywords -->
<dialog id="deleteKeywords" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-download"></i> Confirm Delete</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <label class="modal-label">Are you sure that you want to remove <span class="delete-keywords-number"></span>
            keywords permanently</label>
        <div class="xagio-2-column-65-35-grid xagio-margin-top-large">
            <div class="xagio-flex xagio-flex-gap-medium">
                <input type="hidden" value="0" id="keywordIds">
            </div>
            <div class="xagio-flex-right xagio-flex-gap-medium">
                <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i
                            class="xagio-icon xagio-icon-close"></i> Cancel
                </button>
                <button type="button" class="xagio-button xagio-button-primary delete-keywords"><i
                            class="xagio-icon xagio-icon-check"></i> Ok
                </button>
            </div>
        </div>
    </div>
</dialog>

<!-- Copy Keywords -->
<dialog id="copyKeywords" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-info"></i> Copy Keywords</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>

    <div class="xagio-modal-body">
        <textarea id="copiedKeywords" rows="10" class="xagio-input-textarea"></textarea>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i
                        class="xagio-icon xagio-icon-close"></i> Cancel
            </button>
            <button type="button" class="xagio-button xagio-button-primary copyKeywordsButton"><i
                        class="xagio-icon xagio-icon-copy"></i> Copy to Clipboard
            </button>
        </div>
    </div>
</dialog>

<!-- Seed keywords to a new group -->
<dialog id="seedKeywordsModal" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title">Seed Keywords</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>

    <div class="xagio-modal-body">
        <form id="seedKeywordsForm">
            <input type="hidden" name="group_id" value="0">
            <div class="xagio-alert xagio-alert-primary xagio-margin-bottom-medium">
                <i class="xagio-icon xagio-icon-info"></i> This function will search a current project for any keywords
                that contain phrases entered below, create a new group and move all keywords found into newly created
                group
            </div>

            <div>
                <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                    <div>
                        <label class="modal-label">New Group name</label>
                    </div>
                    <div>
                        <label class="modal-label">Your seed keywords</label>
                    </div>
                </div>

                <div class="xagio-flex-column" id="seed_group_container">
                    <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                        <div class="seed-group-name-container">
                            <input type="text" name="seed_group_name[]" class="xagio-input-text-mini"
                                   placeholder="eg. My New Group">
                        </div>

                        <div class="seed-keywords-container">
                            <input type="text" name="seed_keywords[]" class="xagio-input-text-mini"
                                   placeholder="eg. flowers, blanket, bicycle, house">
                        </div>
                    </div>
                </div>

                <div class="xagio-flex-even-columns x xagio-flex-gap-medium xagio-margin-top-small xagio-flex-align-top">
                    <div class="modal-label-small xagio-margin-top-remove">If left empty, a group will have name of your
                        first seed keyword
                    </div>
                    <div class="modal-label-small xagio-margin-top-remove">*insert keyword(s) separated by a comma ","
                        to create a new group with keywords containing entered above
                    </div>
                </div>
            </div>

            <div class="xagio-flex-row xagio-margin-bottom-large m-t-20">
                <div class="xagio-slider-container xagio-margin-none">
                    <input type="hidden" name="word_match" id="word_match" value="0" class="seed-word-match"/>
                    <div class="xagio-slider-frame">
                        <span class="xagio-slider-button" data-element="word_match"></span>
                    </div>
                    <p class="xagio-slider-label">
                        <span class="word_match_label">Broad Match (
                            <span class="phrase-match-underline">cat</span>,
                            <span class="phrase-match-underline">cat</span>s,
                            <span class="phrase-match-underline">cat</span>apult,
                            wild<span class="phrase-match-underline">cat</span>
                        )</span>
                    </p>
                </div>

                <button type="button" class="xagio-button xagio-button-primary xagio-button-small" data-xagio-tooltip
                        data-xagio-title="Add Multiple Groups" id="add_multiple_groups">
                    <i class="xagio-icon xagio-icon-plus"></i>
                </button>
            </div>

            <div class="xagio-checkbox-button">
                <button class="xagio-button xagio-button-outline" data-xagio-close-modal="" type="button">
                    <i class="xagio-icon xagio-icon-close"></i>
                    Cancel
                </button>
                <div>
                    <button class="xagio-button xagio-button-primary autoGenerateGroupsBtn" type="submit"><i
                                class="xagio-icon xagio-icon-check"></i> Seed New Group
                    </button>
                </div>
            </div>
        </form>
    </div>

</dialog>

<dialog id="phraseMatchModal" class="xagio-modal xagio-modal-lg">

    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title">Clustering</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>

    <div class="xagio-modal-body">
        <form id="phraseMatchForm">
            <input type="hidden" name="group_id" value="0">

            <div class="cluster_top_options xagio-margin-bottom-medium">
                <div data-xagio-tooltip data-xagio-title="Minimum number of matching words in group name">
                    <label class="modal-label" for="min_match">Minimum Word Matches</label>
                    <input type="number" min="1" value="2" name="min_match" id="min_match" class="xagio-input-text-mini"
                           required>
                </div>

                <div data-xagio-tooltip data-xagio-title="Minimum number of keywords in the group">
                    <label class="modal-label" for="min_kws">Minimum Cluster Size</label>
                    <input type="number" min="1" value="2" name="min_kws" id="min_kws" class="xagio-input-text-mini"
                           required>
                </div>

                <div data-xagio-tooltip
                     data-xagio-title="Insert word(s) separated by a comma (,) to be excluded from group names">
                    <label class="modal-label" for="excluded_words">Ignore Words Before Matching<span
                                class="phrase_match_exclude">(top 3 most common words below)</span>:</label>
                    <input type="text" name="excluded_words" id="excluded_words" class="xagio-input-text-mini"
                           placeholder="eg. flowers, blanket, bicycle, house">
                </div>

                <div data-xagio-tooltip
                     data-xagio-title="Include or exclude common prepositions in group names such as: for, and, or, of, in, the, etc.">
                    <label class="modal-label" for="include_prepositions">Prepositions:</label>
                    <div class="xagio-slider-container">
                        <input type="hidden" name="include_prepositions" id="include_prepositions" value="0"/>
                        <div class="xagio-slider-frame">
                            <span class="xagio-slider-button" data-element="include_prepositions"></span>
                        </div>
                    </div>
                </div>
            </div>


            <div class="xagio-flex-space-between xagio-margin-bottom-medium">
                <button type="button" class="xagio-button xagio-button-outline previewCluster"><i
                            class="xagio-icon xagio-icon-eye"></i> Preview Cluster
                </button>

                <div class="xagio-flex-right xagio-flex-gap-medium">

                    <button class="xagio-button xagio-button-primary autoGenerateGroupsBtn" type="submit"><i
                                class="xagio-icon xagio-icon-check"></i> Cluster Keywords
                    </button>

                </div>
            </div>


            <div class="xagio-accordion xagio-margin-bottom-medium xagio-ai-suggestion-accordion">
                <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                    <span>Keyword Group</span>
                    <i class="xagio-icon xagio-icon-arrow-down"></i>
                </h3>
                <div class="xagio-accordion-content">
                    <div>
                        <div class="xagio-accordion-panel">
                            <div class="xagio-flex-space-between xagio-margin-top-medium">
                                <label class="modal-label">Select Keywords to Cluster</label>
                                <button type="button" class="xagio-button xagio-button-primary phraseMatchSelectAll"><i
                                            class="xagio-icon xagio-icon-check-double"></i> Select All
                                </button>
                            </div>

                            <div class="phraseMatchingKeywords">
                                <div class="uk-grid uk-grid-small">
                                    <div class="uk-width-medium-1-2">
                                        <div class="kw-group-1"></div>
                                    </div>
                                    <div class="uk-width-medium-1-2">
                                        <div class="kw-group-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </form>

        <div class="cluster-preview">

        </div>

    </div>

</dialog>

<div class="cluster_preview_template template hide">
    <div class="cluster_group">
        <div class="cluster_group_name"></div>
        <div class="cluster_group_keywords"></div>
    </div>
</div>

<!--new row in seed keywords modal-->
<div class="seed_panel_container_template xagio-hidden">
    <div class="seed-panel-name-container">
        <input type="hidden" name="seed_group_name[]">
        <input type="text" name="seed_keywords[]" class="xagio-input-text-mini" placeholder="eg. flowers, blanket">
    </div>
</div>

<!--new row in seed keywords modal-->
<div class="xagio-flex-even-columns xagio-flex-gap-medium seed_group_container_template xagio-hidden">
    <div class="seed-group-name-container">
        <input type="text" name="seed_group_name[]" class="xagio-input-text-mini" placeholder="eg. My New Group">
    </div>

    <div class="xagio-flex xagio-flex-gap-small seed-keywords-container">
        <input type="text" name="seed_keywords[]" class="xagio-input-text-mini"
               placeholder="eg. flowers, blanket, bicycle, house">
        <button type="button" class="xagio-button xagio-button-outline xagio-button-small delete_seed_row"
                data-xagio-tooltip data-xagio-title="Delete Row">
            <i class="xagio-icon xagio-icon-close"></i>
        </button>
    </div>
</div>

<!-- Delete Selected Groups -->
<dialog id="deleteSelectedGroups" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-download"></i> Confirm Delete</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <label class="modal-label">Are you sure that you want to remove following groups</label>
        <ul class="delete-selected-groups-ul">

        </ul>
        <div class="xagio-2-column-65-35-grid xagio-margin-top-large">
            <div class="xagio-flex xagio-flex-gap-medium">
                <input type="checkbox" class="xagio-input-checkbox" id="deleteSelectedGroupRanks">
                <label for="deleteRanks">Delete keywords rank from RankTracker?</label>
            </div>
            <div class="xagio-flex-right xagio-flex-gap-medium">
                <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i
                            class="xagio-icon xagio-icon-close"></i> Cancel
                </button>
                <button type="button" class="xagio-button xagio-button-primary delete-selected-groups"><i
                            class="xagio-icon xagio-icon-check"></i> Ok
                </button>
            </div>
        </div>
    </div>
</dialog>
<!-- Delete Empty Groups -->
<dialog id="deleteEmptyGroups" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-download"></i> Confirm Delete</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <label class="modal-label">This will delete all groups that have zero keywords. Continue?</label>

        <div class="xagio-2-column-65-35-grid xagio-margin-top-large">
            <div class="xagio-flex xagio-flex-gap-medium">
                <input type="checkbox" class="xagio-input-checkbox" id="skipGroups">
                <label for="deleteRanks">Keep groups if H1, SEO Title and SEO Description present</label>
            </div>
            <div class="xagio-flex-right xagio-flex-gap-medium">
                <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i
                            class="xagio-icon xagio-icon-close"></i> Cancel
                </button>
                <button type="button" class="xagio-button xagio-button-primary delete-empty-groups"><i
                            class="xagio-icon xagio-icon-check"></i> Ok
                </button>
            </div>
        </div>
    </div>
</dialog>

<!-- Consolidate Keywords into group -->
<dialog id="consolidateModal" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title">Consolidate keywords</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>

    <div class="xagio-modal-body">
        <form id="consolidateForm">
            <div class="modal-label">Enter a new Group name:</div>
            <input type="text" name="group_name" id="group_name_phr" class="xagio-input-text-mini" required
                   placeholder="eg. My New Group">
            <div class="modal-label-small">creates a new group with given name and moves all keywords into newly created
                group
            </div>

            <div class="xagio-flex-space-between xagio-flex-gap-large xagio-margin-top-large">
                <div class="xagio-slider-container xagio-margin-none">
                    <input type="hidden" name="XAGIO_REMOVE_EMPTY_GROUPS" id="XAGIO_REMOVE_EMPTY_GROUPS" value="0"/>
                    <div class="xagio-slider-frame">
                        <span class="xagio-slider-button" data-element="XAGIO_REMOVE_EMPTY_GROUPS"></span>
                    </div>
                    <p class="xagio-slider-label">Remove empty groups? <i class="xagio-icon xagio-icon-info help-icon"
                                                                          data-xagio-tooltip
                                                                          data-xagio-title="Remove empty groups."></i>
                    </p>
                </div>
                <div class="xagio-flex-right xagio-flex-gap-medium">
                    <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i
                                class="xagio-icon xagio-icon-close"></i> Cancel
                    </button>
                    <button type="submit" class="xagio-button xagio-button-primary consolidateBtn"><i
                                class="xagio-icon xagio-icon-check"></i> Consolidate
                    </button>
                </div>
            </div>

        </form>
    </div>
</dialog>

<!-- Add Keywords -->
<dialog id="addKeywords" class="xagio-modal">

    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-download"></i> Add New Keywords</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>

    <div class="xagio-modal-body">
        <label class="modal-label">Insert keywords separated by a <b>new line</b> (<kbd>Enter ↵</kbd> key):</span>
        </label>
        <textarea id="keywords-input" rows="6" class="xagio-input-textarea"></textarea>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i
                        class="xagio-icon xagio-icon-close"></i> Cancel
            </button>
            <button type="button" class="xagio-button xagio-button-primary add-keywords"><i
                        class="xagio-icon xagio-icon-plus"></i> Add
            </button>
        </div>
    </div>
</dialog>
<!-- New Group -->
<dialog id="newGroup" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-magnifying-glass-chart"></i> Name Your New Group
        </h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>

    <div class="xagio-modal-body">
        <form id="newProjectForm">
            <h3 class="modal-label">Group Name</h3>
            <input type="text" class="xagio-input-text-mini" id="newGroupInput" placeholder="My Group 1, My Group 2">
            <div class="modal-label-small">You can add multiple groups separated by comma (,)</div>

            <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
                <button class="xagio-button xagio-button-outline" type="button" data-xagio-close-modal><i
                            class="xagio-icon xagio-icon-close"></i> Cancel
                </button>
                <button class="xagio-button xagio-button-primary newGroupsButton" type="submit"><i
                            class="xagio-icon xagio-icon-save"></i> Save
                </button>
            </div>
        </form>
    </div>
</dialog>