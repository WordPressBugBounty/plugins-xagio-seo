<?php
/**
 * Type: SUBMENU
 * Page_Title: Xagio Settings
 * Menu_Title: Xagio Settings
 * Capability: manage_options
 * Slug: xagio-settings
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: xagio_select2,xagio_tagsinput,xagio_settings
 * Css: xagio_select2,xagio_animate,xagio_tagsinput,xagio_settings
 * Position: 2
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');
?>

<div class="xagio-main-header xagio-main-header-big-gaps">
    <img class="logo-image repo-xagio" src="<?php echo  esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        Xagio Settings
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
            <span>Configure miscellaneous settings that tell our plugin how to behave on your website.</span>
            <i class="xagio-icon xagio-icon-arrow-down"></i>
        </h3>
        <div class="xagio-accordion-content">
            <div>
                <div class="xagio-accordion-panel"></div>
            </div>
        </div>
    </div>

    <ul class="xagio-tab xagio-settings">
        <li class="xagio-tab-active"><a href="">General</a></li>
        <li><a href="">WP Easy Set-Up</a></li>
        <li><a href="">Location</a></li>
        <li><a href="">Troubleshooting</a></li>
        <li><a href="">System Status</a></li>
    </ul>
    <div class="xagio-tab-content-holder">

        <!-- Settings -->
        <div class="xagio-tab-content">
            <div class="xagio-2-column-grid">
                <div class="xagio-column-1">
                    <div class="xagio-panel">
                        <h5 class="xagio-panel-title">
                            General Settings
                        </h5>

                        <form class="ts">
                            <input type="hidden" name="action" value="xagio_settings"/>

                            <!-- Disable Uploads -->
                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_DISABLE_UPLOADS" id="XAGIO_DISABLE_UPLOADS" value="<?php echo  XAGIO_DISABLE_UPLOADS ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button xagio-slider-button-settings <?php echo  XAGIO_DISABLE_UPLOADS ? 'on' : ''; ?>" data-element="XAGIO_DISABLE_UPLOADS"><span></span></span>
                                </div>
                                <p class="xagio-slider-label">
                                    Disable File Uploads <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will let xagio control file uploads on WordPress. Enable this to increase your website security."></i>
                                </p>
                            </div>

                            <!-- reCaptcha Protection -->
                            <div class="xagio-slider-container last-container">
                                <input type="hidden" name="XAGIO_RECAPTCHA" id="XAGIO_RECAPTCHA" value="<?php echo  XAGIO_RECAPTCHA ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button xagio-slider-button-settings <?php echo  XAGIO_RECAPTCHA ? 'on' : ''; ?>" data-element="XAGIO_RECAPTCHA"><span></span></span>
                                </div>
                                <p class="xagio-slider-label">
                                    reCAPTCHA V3 Protection <a data-xagio-tooltip data-xagio-title="Use reCaptcha to prevent spam from your comment forms and login password brute-forcing. Click here to navigate to reCAPTCHA admin console to create your keys for this domain." href="https://www.google.com/recaptcha/admin/create" target="_blank"><i class="xagio-icon xagio-icon-info"></i></a>
                                </p>
                            </div>

                            <div class="recaptcha-settings <?php echo !XAGIO_RECAPTCHA ? 'xagio-hidden' : ''; ?>">
                                <h3 class="pop">reCAPTCHA Site Key</h3>
                                <input type="text" class="xagio-input-text-mini text-settings" name="XAGIO_RECAPTCHA_SITE_KEY" placeholder="eg. 6Ld4tYQpAAAAAHsHW6KGW8_NlcCRtKAvqpnYGco4" value="<?php echo  esc_attr(XAGIO_RECAPTCHA_SITE_KEY);?>">
                                <h3 class="pop">reCAPTCHA Secret Key</h3>
                                <input type="text" class="xagio-input-text-mini text-settings" name="XAGIO_RECAPTCHA_SECRET_KEY" placeholder="eg. 6Ld4tYQpAAAAALOubWJCbCjhWuHNjnqrN7HQvusA" value="<?php echo  esc_attr(XAGIO_RECAPTCHA_SECRET_KEY);?>">
                            </div>

                        </form>

                    </div>
                </div>
                <div class="xagio-column-2">
                    <div class="xagio-panel">
                        <h5 class="xagio-panel-title">
                            Export & Import
                        </h5>

                        <p>
                            Export and Import all configuration of SEO Settings and On Page SEO for each post/page
                        </p>

                        <div class="xagio-buttons-flex">
                            <!-- Export / Import SEO settings -->
                            <button type="button" class="xagio-button xagio-button-primary export_to_file export_options" data-target="xagio_export_options"><i class="xagio-icon xagio-icon-download"></i> Export Options</button>

                            <form action="<?php echo esc_url(get_admin_url()) . 'admin-post.php' ?>" id="import_options" class="import_options" method="post" enctype="multipart/form-data" style="display: inline;">
                                <input type="hidden" name="action" value="xagio_import_options" />
                                <label for="import_options_file" type="button" class="xagio-button xagio-button-primary import_to_file" data-target="xagio_import_options"><i class="xagio-icon xagio-icon-upload"></i> Import Options</label>
                                <input id="import_options_file" name="import_options_file" type="file" accept=".psexp" style="display: none;" />
                            </form>
                        </div>

                    </div>
                </div>

            </div>

        </div>

        <!-- WP Easy Set-Up -->
        <div class="xagio-tab-content">
            <form class="fs">
                <input type="hidden" name="action" value="xagio_fs_perform"/>

                <div class="xagio-2-column-grid">
                    <div class="xagio-column-1">
                        <div class="xagio-panel xagio-margin-bottom-medium">
                            <h5 class="xagio-panel-title">General Settings</h5>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_remove_pages" id="fs_remove_pages" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_remove_pages">Remove ALL Pages - <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will remove all the pages in WordPress."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_remove_posts" id="fs_remove_posts" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_remove_posts">Remove ALL Posts - <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will remove all the posts in WordPress."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_remove_themes" id="fs_remove_themes" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_remove_themes">Remove default Themes <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Removes TwentySixteen and TwentyFifteen themes from your WordPress."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_remove_plugins" id="fs_remove_plugins" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_remove_plugins">Remove default Plugins <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Removes Akismet & Hello Dolly from your WordPress."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_permalinks" id="fs_permalinks" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_permalinks">Set permalink structure to Post Name <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will set up permalink structure to Post Name. Example: /page-name/"></i></label>
                            </div>
                        </div>
                        <div class="xagio-panel xagio-margin-bottom-medium">
                            <h5 class="xagio-panel-title">Comments Settings</h5>


                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_remove_comments" id="fs_remove_comments" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_remove_comments">Remove all Comments <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will remove all existing comments from your WordPress."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_disable_comment_notifications" id="fs_disable_comment_notifications" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_disable_comment_notifications">Disable new comments notifications <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Disables notifications when new comments are made."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_disable_comment_moderation" id="fs_disable_comment_moderation" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_disable_comment_moderation">Disable comments moderation notifications <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Disables notifications for comment moderation."></i></label>
                            </div>
                        </div>
                        <div class="xagio-panel xagio-margin-bottom-medium">
                            <h5 class="xagio-panel-title">Content Settings</h5>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_create_aboutus" id="fs_create_aboutus" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_create_aboutus">Create About Us page <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Creates an About Us page."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_create_privacypolicy" id="fs_create_privacypolicy" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_create_privacypolicy">Create Privacy Policy page <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Creates a Privacy Policy page."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_create_termsofuse" id="fs_create_termsofuse" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_create_termsofuse">Create Terms of Use page <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Creates Terms of Use page."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_create_earningsdisclaimer" id="fs_create_earningsdisclaimer" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_create_earningsdisclaimer">Create Earnings Disclaimer page <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Creates Earning Disclaimer page."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_create_contactus" id="fs_create_contactus" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_create_contactus">Create Contact Us page <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Creates Contact Us page."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_create_amazonassociatedisclosure" id="fs_create_amazonassociatedisclosure" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_create_amazonassociatedisclosure">Create Amazon Associate Disclose page <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Creates Amazon Associates Disclosure page."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_create_affiliatedisclosure" id="fs_create_affiliatedisclosure" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_create_affiliatedisclosure">Create Affiliate Disclose page <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Creates Affiliate Disclosure page."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_create_copyright" id="fs_create_copyright" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_create_copyright">Create Copyright Notice page <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Creates Copyright Notice page."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_create_antispam" id="fs_create_antispam" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_create_antispam">Create Anti Spam Policy page <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Creates Anti Spam Policy page."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_create_medicaldisclaimer" id="fs_create_medicaldisclaimer" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_create_medicaldisclaimer">Create Medical Disclaimer page <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Creates Medical Disclaimer page."></i></label>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_create_categories" id="fs_create_categories" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_create_categories">Create Multiple Categories <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Creates 5 Categories for you to customize."></i></label>
                            </div>

                            <div class="fs_create_categories_list xagio-hidden">

                                <input name="fs_create_categories_list[]" type="text" placeholder="eg. Category Name" class="xagio-input-text-mini"/>
                                <input name="fs_create_categories_list[]" type="text" placeholder="eg. Category Name" class="xagio-input-text-mini"/>
                                <input name="fs_create_categories_list[]" type="text" placeholder="eg. Category Name" class="xagio-input-text-mini"/>
                                <input name="fs_create_categories_list[]" type="text" placeholder="eg. Category Name" class="xagio-input-text-mini"/>

                                <div class="xagio-buttons-flex">
                                    <button type="button" class="xagio-button xagio-button-primary xagio-button-padding-small uk-button-add-category"><i class="xagio-icon xagio-icon-plus"></i> Add</button>
                                    <button type="button" class="xagio-button xagio-button-primary xagio-button-padding-small uk-button-remove-category"><i class="xagio-icon xagio-icon-close"></i> Remove</button>
                                </div>
                            </div>


                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_create_blank_pages" id="fs_create_blank_pages" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_create_blank_pages">Create Multiple Pages <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Creates 5 blank pages for you to customize."></i></label>
                            </div>

                            <div class="fs_create_blank_pages_list xagio-hidden">

                                <input name="fs_create_blank_pages_list[]" type="text" placeholder="eg. Page Name" class="xagio-input-text-mini"/>
                                <input name="fs_create_blank_pages_list[]" type="text" placeholder="eg. Page Name" class="xagio-input-text-mini"/>
                                <input name="fs_create_blank_pages_list[]" type="text" placeholder="eg. Page Name" class="xagio-input-text-mini"/>
                                <input name="fs_create_blank_pages_list[]" type="text" placeholder="eg. Page Name" class="xagio-input-text-mini"/>

                                <div class="xagio-buttons-flex">
                                    <button type="button" class="xagio-button xagio-button-primary xagio-button-padding-small uk-button-add-pages"><i class="xagio-icon xagio-icon-plus"></i> Add</button>
                                    <button type="button" class="xagio-button xagio-button-primary xagio-button-padding-small uk-button-remove-pages"><i class="xagio-icon xagio-icon-close"></i> Remove</button>
                                </div>
                            </div>

                            <div class="xagio-checkbox">
                                <input type="checkbox" name="fs_create_blank_posts" id="fs_create_blank_posts" class="xagio-input-checkbox" value="0"/>
                                <label for="fs_create_blank_posts">Create Multiple Posts <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Creates 5 blank posts for you to customize."></i></label>
                            </div>

                            <div class="fs_create_blank_posts_list xagio-hidden">

                                <input name="fs_create_blank_posts_list[]" type="text" placeholder="eg. Post Name" class="xagio-input-text-mini"/>
                                <input name="fs_create_blank_posts_list[]" type="text" placeholder="eg. Post Name" class="xagio-input-text-mini"/>
                                <input name="fs_create_blank_posts_list[]" type="text" placeholder="eg. Post Name" class="xagio-input-text-mini"/>
                                <input name="fs_create_blank_posts_list[]" type="text" placeholder="eg. Post Name" class="xagio-input-text-mini"/>

                                <div class="xagio-buttons-flex">
                                    <button type="button" class="xagio-button xagio-button-primary xagio-button-padding-small uk-button-add-post"><i class="xagio-icon xagio-icon-plus"></i> Add</button>
                                    <button type="button" class="xagio-button xagio-button-primary xagio-button-padding-small uk-button-remove-post"><i class="xagio-icon xagio-icon-close"></i> Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="xagio-column-2">
                        <div class="xagio-panel xagio-margin-bottom-medium xagio-relative">
                            <h5 class="xagio-panel-title">Install Plugins</h5>

                            <!-- Plugin Search -->
                            <div class="xagio-search-plugins xagio-margin-bottom-medium">
                                <input data-type="plugins" class="xagio-input-text-mini" id="search_plugins" placeholder="type here to search for Plugins...">
                            </div>

                            <!-- Plugins results -->
                            <div class="result-container xagio-margin-bottom-medium" id="result_plugins">

                            </div>

                            <!-- Plugin tags -->
                            <input name="fs_plugins" id="plugins" value=""/>

                            <button type="button" class="xagio-button xagio-button-primary btn-save-changes perform-easy-setup"><i class="xagio-icon xagio-icon-draw"></i> Run Easy WP Setup</button>
                        </div>
                        <div class="xagio-panel">
                            <h5 class="xagio-panel-title">Install Themes</h5>

                            <!-- Theme Search -->
                            <div class="xagio-search-themes xagio-margin-bottom-medium">
                                <input data-type="themes" class="xagio-input-text-mini" id="search_themes" placeholder="type here to search for Themes...">
                            </div>

                            <!-- Theme results -->
                            <div class="result-container xagio-margin-bottom-medium" id="result_themes">

                            </div>

                            <!-- Theme tags -->
                            <input name="fs_themes" id="themes" value=""/>
                        </div>
                    </div>
                </div>

                <p class="xagio-alert xagio-alert-danger easy-setup-backup-notice xagio-hidden">
                    <i class="xagio-icon xagio-icon-info"></i> Following backup has been generated:
                    <span class="easy-setup-backup"></span><br>
                    If you started Easy WP Setup by mistake, use the backup from above to restore your website to the original state.
                    To restore the backup, navigate to <a href="/wp-admin/admin.php?page=xagio-clone-backup" style="text-decoration: underline;">Clone & Backup</a> page.
                </p>

            </form>
        </div>

        <!-- Location -->
        <div class="xagio-tab-content location-tab">
            <div class="xagio-2-column-grid">
                <div class="xagio-column-1">
                    <div class="xagio-panel">
                        <h5 class="xagio-panel-title">Keyword Settings</h5>
                        <div class="xagio-alert xagio-alert-primary">
                            <i class="xagio-icon xagio-icon-info"></i>This sets the default language & country when looking up keyword data such as Volume, CPC, inTitle & inURL. This can be updated at anytime.
                        </div>

                        <div>
                        <?php
                            $language = get_option('XAGIO_LOCATION_DEFAULT_KEYWORD_LANGUAGE');
                        ?>
                        <h3 class="pop">Language</h3>
                        <select class="xagio-input-select xagio-input-select-gray" id="xagioSettings-locationKeywordLanguage" name="xagioSettings-locationKeywordLanguage" data-default="<?php echo esc_html($language) ?? ""; ?>"
                        >
                            <option value="">-- All Languages --</option>
                            <option	value="ar">Arabic</option>
                            <option	value="bn">Bengali</option>
                            <option	value="bg">Bulgarian</option>
                            <option	value="ca">Catalan</option>
                            <option	value="zh_CN">Chinese (Simplified)</option>
                            <option	value="zh_TW">Chinese (Traditional)</option>
                            <option	value="hr">Croatian</option>
                            <option	value="cs">Czech</option>
                            <option	value="da">Danish</option>
                            <option	value="nl">Dutch</option>
                            <option	value="en">English</option>
                            <option	value="et">Estonian</option>
                            <option	value="fa">Farsi</option>
                            <option	value="fi">Finnish</option>
                            <option	value="fr">French</option>
                            <option	value="de">German</option>
                            <option	value="el">Greek</option>
                            <option	value="iw">Hebrew (old)</option>
                            <option	value="hi">Hindi</option>
                            <option	value="hu">Hungarian</option>
                            <option	value="is">Icelandic</option>
                            <option	value="id">Indonesian</option>
                            <option	value="it">Italian</option>
                            <option	value="ja">Japanese</option>
                            <option	value="ko">Korean</option>
                            <option	value="lv">Latvian</option>
                            <option	value="lt">Lithuanian</option>
                            <option	value="ms">Malay</option>
                            <option	value="no">Norwegian</option>
                            <option	value="pl">Polish</option>
                            <option	value="pt">Portuguese</option>
                            <option	value="ro">Romanian</option>
                            <option	value="ru">Russian</option>
                            <option	value="sr">Serbian</option>
                            <option	value="sk">Slovak</option>
                            <option	value="sl">Slovenian</option>
                            <option	value="es">Spanish</option>
                            <option	value="sv">Swedish</option>
                            <option	value="tl">Tagalog</option>
                            <option	value="ta">Tamil</option>
                            <option	value="te">Telugu</option>
                            <option	value="th">Thai</option>
                            <option	value="tr">Turkish</option>
                            <option	value="uk">Ukrainian</option>
                            <option	value="ur">Urdu</option>
                            <option	value="vi">Vietnamese</option>
                            <option	value="zh_CN">Chinese (Simplified)</option>
                            <option	value="zh_TW">Chinese (Traditional)</option>
                        </select>
                        </div>
                        <?php
                            $country = get_option('XAGIO_LOCATION_DEFAULT_KEYWORD_COUNTRY');
                        ?>
                        <h3 class="pop">Country</h3>
                        <select class="xagio-input-select xagio-input-select-gray" id="xagioSettings-locationKeywordCountry" name="xagioSettings-locationKeywordCountry" data-default="<?php echo esc_html($country) ?? ""; ?>">
                            <option	value="">WorldWide</option>
                            <option	value="Afghanistan">Afghanistan</option>
                            <option	value="Albania">Albania</option>
                            <option	value="Antarctica">Antarctica</option>
                            <option	value="Algeria">Algeria</option>
                            <option	value="American Samoa">American Samoa</option>
                            <option	value="Andorra">Andorra</option>
                            <option	value="Angola">Angola</option>
                            <option	value="Antigua and Barbuda">Antigua and Barbuda</option>
                            <option	value="Azerbaijan">Azerbaijan</option>
                            <option	value="Argentina">Argentina</option>
                            <option	value="Australia">Australia</option>
                            <option	value="Austria">Austria</option>
                            <option	value="The Bahamas">The Bahamas</option>
                            <option	value="Bahrain">Bahrain</option>
                            <option	value="Bangladesh">Bangladesh</option>
                            <option	value="Armenia">Armenia</option>
                            <option	value="Barbados">Barbados</option>
                            <option	value="Belgium">Belgium</option>
                            <option	value="Bermuda">Bermuda</option>
                            <option	value="Bhutan">Bhutan</option>
                            <option	value="Bolivia">Bolivia</option>
                            <option	value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
                            <option	value="Botswana">Botswana</option>
                            <option	value="Bouvet Island">Bouvet Island</option>
                            <option	value="Brazil">Brazil</option>
                            <option	value="Belize">Belize</option>
                            <option	value="British Indian Ocean Territory">British Indian Ocean Territory</option>
                            <option	value="Solomon Islands">Solomon Islands</option>
                            <option	value="British Virgin Islands">British Virgin Islands</option>
                            <option	value="Brunei">Brunei</option>
                            <option	value="Bulgaria">Bulgaria</option>
                            <option	value="Myanmar (Burma)">Myanmar (Burma)</option>
                            <option	value="Burundi">Burundi</option>
                            <option	value="Cambodia">Cambodia</option>
                            <option	value="Cameroon">Cameroon</option>
                            <option	value="Canada">Canada</option>
                            <option	value="Cape Verde">Cape Verde</option>
                            <option	value="Cayman Islands">Cayman Islands</option>
                            <option	value="Central African Republic">Central African Republic</option>
                            <option	value="Sri Lanka">Sri Lanka</option>
                            <option	value="Chad">Chad</option>
                            <option	value="Chile">Chile</option>
                            <option	value="China">China</option>
                            <option	value="Taiwan">Taiwan</option>
                            <option	value="Christmas Island">Christmas Island</option>
                            <option	value="Cocos (Keeling) Islands">Cocos (Keeling) Islands</option>
                            <option	value="Colombia">Colombia</option>
                            <option	value="Comoros">Comoros</option>
                            <option	value="Mayotte">Mayotte</option>
                            <option	value="Republic of the Congo">Republic of the Congo</option>
                            <option	value="Democratic Republic of the Congo">Democratic Republic of the Congo</option>
                            <option	value="Cook Islands">Cook Islands</option>
                            <option	value="Costa Rica">Costa Rica</option>
                            <option	value="Croatia">Croatia</option>
                            <option	value="Cyprus">Cyprus</option>
                            <option	value="Czechia">Czechia</option>
                            <option	value="Benin">Benin</option>
                            <option	value="Denmark">Denmark</option>
                            <option	value="Dominica">Dominica</option>
                            <option	value="Dominican Republic">Dominican Republic</option>
                            <option	value="Ecuador">Ecuador</option>
                            <option	value="El Salvador">El Salvador</option>
                            <option	value="Equatorial Guinea">Equatorial Guinea</option>
                            <option	value="Ethiopia">Ethiopia</option>
                            <option	value="Eritrea">Eritrea</option>
                            <option	value="Estonia">Estonia</option>
                            <option	value="Faroe Islands">Faroe Islands</option>
                            <option	value="Falkland Islands (Islas Malvinas)">Falkland Islands (Islas Malvinas)</option>
                            <option	value="South Georgia and the South Sandwich Islands">South Georgia and the South Sandwich Islands</option>
                            <option	value="Fiji">Fiji</option>
                            <option	value="Finland">Finland</option>
                            <option	value="France">France</option>
                            <option	value="French Guiana">French Guiana</option>
                            <option	value="French Polynesia">French Polynesia</option>
                            <option	value="French Southern and Antarctic Lands">French Southern and Antarctic Lands</option>
                            <option	value="Djibouti">Djibouti</option>
                            <option	value="Gabon">Gabon</option>
                            <option	value="Georgia">Georgia</option>
                            <option	value="The Gambia">The Gambia</option>
                            <option	value="Palestine">Palestine</option>
                            <option	value="Germany">Germany</option>
                            <option	value="Ghana">Ghana</option>
                            <option	value="Gibraltar">Gibraltar</option>
                            <option	value="Kiribati">Kiribati</option>
                            <option	value="Greece">Greece</option>
                            <option	value="Greenland">Greenland</option>
                            <option	value="Grenada">Grenada</option>
                            <option	value="Guadeloupe">Guadeloupe</option>
                            <option	value="Guam">Guam</option>
                            <option	value="Guatemala">Guatemala</option>
                            <option	value="Guinea">Guinea</option>
                            <option	value="Guyana">Guyana</option>
                            <option	value="Haiti">Haiti</option>
                            <option	value="Heard Island and McDonald Islands">Heard Island and McDonald Islands</option>
                            <option	value="Vatican City">Vatican City</option>
                            <option	value="Honduras">Honduras</option>
                            <option	value="Hong Kong">Hong Kong</option>
                            <option	value="Hungary">Hungary</option>
                            <option	value="Iceland">Iceland</option>
                            <option	value="India">India</option>
                            <option	value="Indonesia">Indonesia</option>
                            <option	value="Iraq">Iraq</option>
                            <option	value="Ireland">Ireland</option>
                            <option	value="Israel">Israel</option>
                            <option	value="Italy">Italy</option>
                            <option	value="Cote d'Ivoire">Cote d'Ivoire</option>
                            <option	value="Jamaica">Jamaica</option>
                            <option	value="Japan">Japan</option>
                            <option	value="Kazakhstan">Kazakhstan</option>
                            <option	value="Jordan">Jordan</option>
                            <option	value="Kenya">Kenya</option>
                            <option	value="South Korea">South Korea</option>
                            <option	value="Kuwait">Kuwait</option>
                            <option	value="Kyrgyzstan">Kyrgyzstan</option>
                            <option	value="Laos">Laos</option>
                            <option	value="Lebanon">Lebanon</option>
                            <option	value="Lesotho">Lesotho</option>
                            <option	value="Latvia">Latvia</option>
                            <option	value="Liberia">Liberia</option>
                            <option	value="Libya">Libya</option>
                            <option	value="Liechtenstein">Liechtenstein</option>
                            <option	value="Lithuania">Lithuania</option>
                            <option	value="Luxembourg">Luxembourg</option>
                            <option	value="Macao">Macao</option>
                            <option	value="Madagascar">Madagascar</option>
                            <option	value="Malawi">Malawi</option>
                            <option	value="Malaysia">Malaysia</option>
                            <option	value="Maldives">Maldives</option>
                            <option	value="Mali">Mali</option>
                            <option	value="Malta">Malta</option>
                            <option	value="Martinique">Martinique</option>
                            <option	value="Mauritania">Mauritania</option>
                            <option	value="Mauritius">Mauritius</option>
                            <option	value="Mexico">Mexico</option>
                            <option	value="Monaco">Monaco</option>
                            <option	value="Mongolia">Mongolia</option>
                            <option	value="Moldova">Moldova</option>
                            <option	value="Montenegro">Montenegro</option>
                            <option	value="Montserrat">Montserrat</option>
                            <option	value="Morocco">Morocco</option>
                            <option	value="Mozambique">Mozambique</option>
                            <option	value="Oman">Oman</option>
                            <option	value="Namibia">Namibia</option>
                            <option	value="Nauru">Nauru</option>
                            <option	value="Nepal">Nepal</option>
                            <option	value="Netherlands">Netherlands</option>
                            <option	value="Curacao">Curacao</option>
                            <option	value="Aruba">Aruba</option>
                            <option	value="Sint Maarten">Sint Maarten</option>
                            <option	value="Caribbean Netherlands">Caribbean Netherlands</option>
                            <option	value="New Caledonia">New Caledonia</option>
                            <option	value="Vanuatu">Vanuatu</option>
                            <option	value="New Zealand">New Zealand</option>
                            <option	value="Nicaragua">Nicaragua</option>
                            <option	value="Niger">Niger</option>
                            <option	value="Nigeria">Nigeria</option>
                            <option	value="Niue">Niue</option>
                            <option	value="Norfolk Island">Norfolk Island</option>
                            <option	value="Norway">Norway</option>
                            <option	value="Northern Mariana Islands">Northern Mariana Islands</option>
                            <option	value="United States Minor Outlying Islands">United States Minor Outlying Islands</option>
                            <option	value="Micronesia">Micronesia</option>
                            <option	value="Marshall Islands">Marshall Islands</option>
                            <option	value="Palau">Palau</option>
                            <option	value="Pakistan">Pakistan</option>
                            <option	value="Panama">Panama</option>
                            <option	value="Papua New Guinea">Papua New Guinea</option>
                            <option	value="Paraguay">Paraguay</option>
                            <option	value="Peru">Peru</option>
                            <option	value="Philippines">Philippines</option>
                            <option	value="Pitcairn Islands">Pitcairn Islands</option>
                            <option	value="Poland">Poland</option>
                            <option	value="Portugal">Portugal</option>
                            <option	value="Guinea-Bissau">Guinea-Bissau</option>
                            <option	value="Timor-Leste">Timor-Leste</option>
                            <option	value="Puerto Rico">Puerto Rico</option>
                            <option	value="Qatar">Qatar</option>
                            <option	value="Reunion">Reunion</option>
                            <option	value="Romania">Romania</option>
                            <option	value="Rwanda">Rwanda</option>
                            <option	value="Saint Barthelemy">Saint Barthelemy</option>
                            <option	value="Saint Helena, Ascension and Tristan da Cunha">Saint Helena, Ascension and Tristan da Cunha</option>
                            <option	value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
                            <option	value="Anguilla">Anguilla</option>
                            <option	value="Saint Lucia">Saint Lucia</option>
                            <option	value="Saint Martin">Saint Martin</option>
                            <option	value="Saint Pierre and Miquelon">Saint Pierre and Miquelon</option>
                            <option	value="Saint Vincent and the Grenadines">Saint Vincent and the Grenadines</option>
                            <option	value="San Marino">San Marino</option>
                            <option	value="Sao Tome and Principe">Sao Tome and Principe</option>
                            <option	value="Saudi Arabia">Saudi Arabia</option>
                            <option	value="Senegal">Senegal</option>
                            <option	value="Serbia">Serbia</option>
                            <option	value="Seychelles">Seychelles</option>
                            <option	value="Sierra Leone">Sierra Leone</option>
                            <option	value="Singapore">Singapore</option>
                            <option	value="Slovakia">Slovakia</option>
                            <option	value="Vietnam">Vietnam</option>
                            <option	value="Slovenia">Slovenia</option>
                            <option	value="Somalia">Somalia</option>
                            <option	value="South Africa">South Africa</option>
                            <option	value="Zimbabwe">Zimbabwe</option>
                            <option	value="Spain">Spain</option>
                            <option	value="South Sudan">South Sudan</option>
                            <option	value="Western Sahara">Western Sahara</option>
                            <option	value="Sudan">Sudan</option>
                            <option	value="Suriname">Suriname</option>
                            <option	value="Svalbard and Jan Mayen">Svalbard and Jan Mayen</option>
                            <option	value="Eswatini">Eswatini</option>
                            <option	value="Sweden">Sweden</option>
                            <option	value="Switzerland">Switzerland</option>
                            <option	value="Tajikistan">Tajikistan</option>
                            <option	value="Thailand">Thailand</option>
                            <option	value="Togo">Togo</option>
                            <option	value="Tokelau">Tokelau</option>
                            <option	value="Tonga">Tonga</option>
                            <option	value="Trinidad and Tobago">Trinidad and Tobago</option>
                            <option	value="United Arab Emirates">United Arab Emirates</option>
                            <option	value="Tunisia">Tunisia</option>
                            <option	value="Turkiye">Turkiye</option>
                            <option	value="Turkmenistan">Turkmenistan</option>
                            <option	value="Turks and Caicos Islands">Turks and Caicos Islands</option>
                            <option	value="Tuvalu">Tuvalu</option>
                            <option	value="Uganda">Uganda</option>
                            <option	value="Ukraine">Ukraine</option>
                            <option	value="North Macedonia">North Macedonia</option>
                            <option	value="Egypt">Egypt</option>
                            <option	value="United Kingdom">United Kingdom</option>
                            <option	value="Guernsey">Guernsey</option>
                            <option	value="Jersey">Jersey</option>
                            <option	value="Isle of Man">Isle of Man</option>
                            <option	value="Tanzania">Tanzania</option>
                            <option	value="United States">United States</option>
                            <option	value="U.S. Virgin Islands">U.S. Virgin Islands</option>
                            <option	value="Burkina Faso">Burkina Faso</option>
                            <option	value="Uruguay">Uruguay</option>
                            <option	value="Uzbekistan">Uzbekistan</option>
                            <option	value="Venezuela">Venezuela</option>
                            <option	value="Wallis and Futuna">Wallis and Futuna</option>
                            <option	value="Samoa">Samoa</option>
                            <option	value="Yemen">Yemen</option>
                            <option	value="Zambia">Zambia</option>
                            <option	value="Kosovo">Kosovo</option>
                        </select>

                    </div>
                </div>
                <div class="xagio-column-1">
                    <div class="xagio-panel">
                        <h5 class="xagio-panel-title">Rank Tracker</h5>
                        <div class="xagio-alert xagio-alert-primary">
                            <i class="xagio-icon xagio-icon-info"></i>This sets the default search engine & Country you would like to track your keyword rankings for. This can be updated at any time.
                        </div>

                        <div class="search-engine-holder">
                            <h3 class="pop">Search Engine</h3>
                            <?php
                                $engines = get_option('XAGIO_LOCATION_DEFAULT_SEARCH_ENGINE');
                                if (is_array($engines)) {
                                    $engines = join(',', array_column($engines, 'id'));
                                } else {
                                    $engines = '';
                                }
                            ?>
                            <select id="search_engine" class="xagio-input-select xagio-input-select-gray" data-default="<?php echo esc_html($engines); ?>" multiple="" name="search_engine[]">
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
                            <option value="2908">amazon.com.au (Australia/English)</option>
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
                            <option value="1023">search.yahoo.com (Bosnia and Herzegovina/All Languages)
                            </option>
                            <option value="844">search.yahoo.com (Bosnia and Herzegovina/Croatian)</option>
                            <option value="845">search.yahoo.com (Bosnia and Herzegovina/English)</option>
                            <option value="5293">google.co.bw (Botswana/English)</option>
                            <option value="72">google.co.bw (Botswana/Tswana)</option>
                            <option value="2907">amazon.com.br (Brasil/Portuguese (Brasil))</option>
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
                            <option value="2909">amazon.ca (Canada/English)</option>
                            <option value="2910">amazon.ca (Canada/French)</option>
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
                            <option value="100">google.cd (Democratic Republic of the Congo/Lingala)
                            </option>
                            <option value="98">google.cd (Democratic Republic of the Congo/Swahili)</option>
                            <option value="97">google.cd (Democratic Republic of the Congo/Tshiluba)
                            </option>
                            <option value="1033">search.yahoo.com (Democratic Republic of the
                                Congo/AllLanguages)
                            </option>
                            <option value="862">search.yahoo.com (Democratic Republic of the Congo/French)
                            </option>
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
                            <option value="116">google.com.do (Dominican Republic/Espanol (Latinoamerica))
                            </option>
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

                            <option value="2911">amazon.fr (France/French)</option>
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
                            <option value="2917">amazon.de (Germany/Czech)</option>
                            <option value="2914">amazon.de (Germany/Dutch)</option>
                            <option value="2913">amazon.de (Germany/English)</option>
                            <option value="2912">amazon.de (Germany/German)</option>
                            <option value="2915">amazon.de (Germany/Polish)</option>
                            <option value="2916">amazon.de (Germany/Turkish)</option>
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
                            <option value="5735">google.com.hk (Hong Kong/Chinese (Traditional Han))
                            </option>
                            <option value="2843">google.com.hk (Hong Kong/English)</option>
                            <option value="1046">hk.search.yahoo.com (Hong Kong/All Languages)</option>
                            <option value="881">hk.search.yahoo.com (Hong Kong/Chinese (Traditional Han))
                            </option>
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
                            <option value="2919">amazon.in (India/English)</option>
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

                            <option value="2920">amazon.it (Italy/Italian)</option>
                            <option value="985">bing.com (Italy/All Languages)</option>
                            <option value="415">bing.com (Italy/Italian)</option>
                            <option value="3427">google.it (Italy/English)</option>
                            <option value="176">google.it (Italy/Italian)</option>
                            <option value="1054">it.search.yahoo.com (Italy/All Languages)</option>
                            <option value="892">it.search.yahoo.com (Italy/English)</option>
                            <option value="893">it.search.yahoo.com (Italy/Italian)</option>

                            <option value="177">google.com.jm (Jamaica/English)</option>
                            <option value="2918">amazon.co.jp (Japan/Chinese (Simplified Han))</option>
                            <option value="2922">amazon.co.jp (Japan/English)</option>
                            <option value="2921">amazon.co.jp (Japan/Japanese)</option>
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
                            <option value="2923">amazon.com.mx (Mexico/Espanol (Latinoamerica))</option>
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
                            <option value="2926">amazon.nl (Netherlands/Dutch)</option>
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
                            <option value="277">google.com.vc (Saint Vincent and the Grenadines/English)
                            </option>
                            <option value="278">google.ws (Samoa/English)</option>
                            <option value="5375">google.sm (San Marino/English)</option>
                            <option value="279">google.sm (San Marino/Italian)</option>
                            <option value="280">google.st (Sao Tome and Principe/Portuguese)</option>
                            <option value="5824">google.st (Sao Tome and Principe/Portuguese (Brazil))
                            </option>
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
                            <option value="5737">google.com.sg (Singapore/Chinese (Traditional Han))
                            </option>
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
                            <option value="305">google.co.za (South Africa/Southern Sotho or Sesotho)
                            </option>
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

                            <option value="2924">amazon.es (Spain/Spanish)</option>
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
                            <option value="329">google.tt (Trinidad and Tobago/Chinese (Traditional Han))
                            </option>
                            <option value="333">google.tt (Trinidad and Tobago/English)</option>
                            <option value="332">google.tt (Trinidad and Tobago/Espanol (Latinoamerica))
                            </option>
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
                            <option value="1095">search.yahoo.com (United Arab Emirates/All Languages)
                            </option>
                            <option value="955">search.yahoo.com (United Arab Emirates/Arabic)</option>
                            <option value="956">search.yahoo.com (United Arab Emirates/English)</option>

                            <option value="2925">amazon.co.uk (United Kingdom/English)</option>
                            <option value="1011">bing.com (United Kingdom/All Languages)</option>
                            <option value="389">bing.com (United Kingdom/English)</option>
                            <option value="22">google.co.uk (United Kingdom/English)</option>
                            <option value="1096">uk.search.yahoo.com (United Kingdom/All Languages)</option>
                            <option value="957">uk.search.yahoo.com (United Kingdom/English)</option>

                            <option value="2897">amazon.com (United States/English)</option>
                            <option value="2906">amazon.com (United States/Espanol)</option>
                            <option value="1012">bing.com (United States/All Languages)</option>
                            <option value="5958">bing.com (United States/Arabic)</option>
                            <option value="397">bing.com (United States/English)</option>
                            <option value="404">bing.com (United States/Spanish)</option>
                            <option value="959">espanol.search.yahoo.com (United States/Spanish)</option>
                            <option value="3667">google.com (United States/Chinese (Simplified))</option>
                            <option value="3636">google.com (United States/Chinese (Traditional))</option>
                            <option value="3641">google.com (United States/Danish)</option>
                            <option value="3656">google.com (United States/Dutch)</option>
                            <option value="14">google.com (United States/English)</option>
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
                            <option value="960">vn.search.yahoo.com (Vietnam/Chinese (Traditional Han))
                            </option>
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
                        <h3 class="pop">Country</h3>
                        <select id="search_location" data-default="<?php echo esc_html(get_option('XAGIO_LOCATION_DEFAULT_COUNTRY')); ?>" class="xagio-input-select xagio-input-select-gray" name="search_location">
                            <option value="2004">Afghanistan</option>
                            <option value="2008">Albania</option>
                            <option value="2010">Antarctica</option>
                            <option value="2012">Algeria</option>
                            <option value="2016">American Samoa</option>
                            <option value="2020">Andorra</option>
                            <option value="2024">Angola</option>
                            <option value="2028">Antigua and Barbuda</option>
                            <option value="2031">Azerbaijan</option>
                            <option value="2032">Argentina</option>
                            <option value="2036">Australia</option>
                            <option value="2040">Austria</option>
                            <option value="2044">The Bahamas</option>
                            <option value="2048">Bahrain</option>
                            <option value="2050">Bangladesh</option>
                            <option value="2051">Armenia</option>
                            <option value="2052">Barbados</option>
                            <option value="2056">Belgium</option>
                            <option value="2060">Bermuda</option>
                            <option value="2064">Bhutan</option>
                            <option value="2068">Bolivia</option>
                            <option value="2070">Bosnia and Herzegovina</option>
                            <option value="2072">Botswana</option>
                            <option value="2074">Bouvet Island</option>
                            <option value="2076">Brazil</option>
                            <option value="2084">Belize</option>
                            <option value="2086">British Indian Ocean Territory</option>
                            <option value="2090">Solomon Islands</option>
                            <option value="2092">British Virgin Islands</option>
                            <option value="2096">Brunei</option>
                            <option value="2100">Bulgaria</option>
                            <option value="2104">Myanmar (Burma)</option>
                            <option value="2108">Burundi</option>
                            <option value="2112">Belarus</option>
                            <option value="2116">Cambodia</option>
                            <option value="2120">Cameroon</option>
                            <option value="2124">Canada</option>
                            <option value="2132">Cape Verde</option>
                            <option value="2136">Cayman Islands</option>
                            <option value="2140">Central African Republic</option>
                            <option value="2144">Sri Lanka</option>
                            <option value="2148">Chad</option>
                            <option value="2152">Chile</option>
                            <option value="2156">China</option>
                            <option value="2158">Taiwan</option>
                            <option value="2162">Christmas Island</option>
                            <option value="2166">Cocos (Keeling) Islands</option>
                            <option value="2170">Colombia</option>
                            <option value="2174">Comoros</option>
                            <option value="2175">Mayotte</option>
                            <option value="2178">Republic of the Congo</option>
                            <option value="2180">Democratic Republic of the Congo</option>
                            <option value="2184">Cook Islands</option>
                            <option value="2188">Costa Rica</option>
                            <option value="2191">Croatia</option>
                            <option value="2196">Cyprus</option>
                            <option value="2203">Czechia</option>
                            <option value="2204">Benin</option>
                            <option value="2208">Denmark</option>
                            <option value="2212">Dominica</option>
                            <option value="2214">Dominican Republic</option>
                            <option value="2218">Ecuador</option>
                            <option value="2222">El Salvador</option>
                            <option value="2226">Equatorial Guinea</option>
                            <option value="2231">Ethiopia</option>
                            <option value="2232">Eritrea</option>
                            <option value="2233">Estonia</option>
                            <option value="2234">Faroe Islands</option>
                            <option value="2238">Falkland Islands (Islas Malvinas)</option>
                            <option value="2239">South Georgia and the South Sandwich Islands</option>
                            <option value="2242">Fiji</option>
                            <option value="2246">Finland</option>
                            <option value="2250">France</option>
                            <option value="2254">French Guiana</option>
                            <option value="2258">French Polynesia</option>
                            <option value="2260">French Southern and Antarctic Lands</option>
                            <option value="2262">Djibouti</option>
                            <option value="2266">Gabon</option>
                            <option value="2268">Georgia</option>
                            <option value="2270">The Gambia</option>
                            <option value="2275">Palestine</option>
                            <option value="2276">Germany</option>
                            <option value="2288">Ghana</option>
                            <option value="2292">Gibraltar</option>
                            <option value="2296">Kiribati</option>
                            <option value="2300">Greece</option>
                            <option value="2304">Greenland</option>
                            <option value="2308">Grenada</option>
                            <option value="2312">Guadeloupe</option>
                            <option value="2316">Guam</option>
                            <option value="2320">Guatemala</option>
                            <option value="2324">Guinea</option>
                            <option value="2328">Guyana</option>
                            <option value="2332">Haiti</option>
                            <option value="2334">Heard Island and McDonald Islands</option>
                            <option value="2336">Vatican City</option>
                            <option value="2340">Honduras</option>
                            <option value="2344">Hong Kong</option>
                            <option value="2348">Hungary</option>
                            <option value="2352">Iceland</option>
                            <option value="2356">India</option>
                            <option value="2360">Indonesia</option>
                            <option value="2368">Iraq</option>
                            <option value="2372">Ireland</option>
                            <option value="2376">Israel</option>
                            <option value="2380">Italy</option>
                            <option value="2384">Cote d'Ivoire</option>
                            <option value="2388">Jamaica</option>
                            <option value="2392">Japan</option>
                            <option value="2398">Kazakhstan</option>
                            <option value="2400">Jordan</option>
                            <option value="2404">Kenya</option>
                            <option value="2410">South Korea</option>
                            <option value="2414">Kuwait</option>
                            <option value="2417">Kyrgyzstan</option>
                            <option value="2418">Laos</option>
                            <option value="2422">Lebanon</option>
                            <option value="2426">Lesotho</option>
                            <option value="2428">Latvia</option>
                            <option value="2430">Liberia</option>
                            <option value="2434">Libya</option>
                            <option value="2438">Liechtenstein</option>
                            <option value="2440">Lithuania</option>
                            <option value="2442">Luxembourg</option>
                            <option value="2446">Macau</option>
                            <option value="2450">Madagascar</option>
                            <option value="2454">Malawi</option>
                            <option value="2458">Malaysia</option>
                            <option value="2462">Maldives</option>
                            <option value="2466">Mali</option>
                            <option value="2470">Malta</option>
                            <option value="2474">Martinique</option>
                            <option value="2478">Mauritania</option>
                            <option value="2480">Mauritius</option>
                            <option value="2484">Mexico</option>
                            <option value="2492">Monaco</option>
                            <option value="2496">Mongolia</option>
                            <option value="2498">Moldova</option>
                            <option value="2499">Montenegro</option>
                            <option value="2500">Montserrat</option>
                            <option value="2504">Morocco</option>
                            <option value="2508">Mozambique</option>
                            <option value="2512">Oman</option>
                            <option value="2516">Namibia</option>
                            <option value="2520">Nauru</option>
                            <option value="2524">Nepal</option>
                            <option value="2528">Netherlands</option>
                            <option value="2530">Netherlands Antilles</option>
                            <option value="2531">Curacao</option>
                            <option value="2533">Aruba</option>
                            <option value="2534">Sint Maarten</option>
                            <option value="2535">Caribbean Netherlands</option>
                            <option value="2540">New Caledonia</option>
                            <option value="2548">Vanuatu</option>
                            <option value="2554">New Zealand</option>
                            <option value="2558">Nicaragua</option>
                            <option value="2562">Niger</option>
                            <option value="2566">Nigeria</option>
                            <option value="2570">Niue</option>
                            <option value="2574">Norfolk Island</option>
                            <option value="2578">Norway</option>
                            <option value="2580">Northern Mariana Islands</option>
                            <option value="2581">United States Minor Outlying Islands</option>
                            <option value="2583">Federated States of Micronesia</option>
                            <option value="2584">Marshall Islands</option>
                            <option value="2585">Palau</option>
                            <option value="2586">Pakistan</option>
                            <option value="2591">Panama</option>
                            <option value="2598">Papua New Guinea</option>
                            <option value="2600">Paraguay</option>
                            <option value="2604">Peru</option>
                            <option value="2608">Philippines</option>
                            <option value="2612">Pitcairn Islands</option>
                            <option value="2616">Poland</option>
                            <option value="2620">Portugal</option>
                            <option value="2624">Guinea-Bissau</option>
                            <option value="2626">Timor-Leste</option>
                            <option value="2630">Puerto Rico</option>
                            <option value="2634">Qatar</option>
                            <option value="2638">Reunion</option>
                            <option value="2642">Romania</option>
                            <option value="2643">Russia</option>
                            <option value="2646">Rwanda</option>
                            <option value="2654">Saint Helena, Ascension and Tristan da Cunha</option>
                            <option value="2659">Saint Kitts and Nevis</option>
                            <option value="2660">Anguilla</option>
                            <option value="2662">Saint Lucia</option>
                            <option value="2666">Saint Pierre and Miquelon</option>
                            <option value="2670">Saint Vincent and the Grenadines</option>
                            <option value="2674">San Marino</option>
                            <option value="2678">Sao Tome and Principe</option>
                            <option value="2682">Saudi Arabia</option>
                            <option value="2686">Senegal</option>
                            <option value="2688">Serbia</option>
                            <option value="2690">Seychelles</option>
                            <option value="2694">Sierra Leone</option>
                            <option value="2702">Singapore</option>
                            <option value="2703">Slovakia</option>
                            <option value="2704">Vietnam</option>
                            <option value="2705">Slovenia</option>
                            <option value="2706">Somalia</option>
                            <option value="2710">South Africa</option>
                            <option value="2716">Zimbabwe</option>
                            <option value="2724">Spain</option>
                            <option value="2732">Western Sahara</option>
                            <option value="2740">Suriname</option>
                            <option value="2744">Svalbard and Jan Mayen</option>
                            <option value="2748">Eswatini</option>
                            <option value="2752">Sweden</option>
                            <option value="2756">Switzerland</option>
                            <option value="2762">Tajikistan</option>
                            <option value="2764">Thailand</option>
                            <option value="2768">Togo</option>
                            <option value="2772">Tokelau</option>
                            <option value="2776">Tonga</option>
                            <option value="2780">Trinidad and Tobago</option>
                            <option value="2784">United Arab Emirates</option>
                            <option value="2788">Tunisia</option>
                            <option value="2792">Turkey</option>
                            <option value="2795">Turkmenistan</option>
                            <option value="2796">Turks and Caicos Islands</option>
                            <option value="2798">Tuvalu</option>
                            <option value="2800">Uganda</option>
                            <option value="2804">Ukraine</option>
                            <option value="2807">North Macedonia</option>
                            <option value="2818">Egypt</option>
                            <option value="2826">United Kingdom</option>
                            <option value="2831">Guernsey</option>
                            <option value="2832">Jersey</option>
                            <option value="2834">Tanzania</option>
                            <option value="2840" selected>United States</option>
                            <option value="2850">U.S. Virgin Islands</option>
                            <option value="2854">Burkina Faso</option>
                            <option value="2858">Uruguay</option>
                            <option value="2860">Uzbekistan</option>
                            <option value="2862">Venezuela</option>
                            <option value="2876">Wallis and Futuna</option>
                            <option value="2882">Samoa</option>
                            <option value="2887">Yemen</option>
                            <option value="2894">Zambia</option>
                        </select>

                    </div>
                </div>
                <div class="xagio-column-1">
                    <div class="xagio-panel">
                        <h5 class="xagio-panel-title">Audit</h5>
                        <div class="xagio-alert xagio-alert-primary">
                            <i class="xagio-icon xagio-icon-info"></i>This sets the default Audit location. This can be updated at any time.
                        </div>

                        <?php
                            $audit_location = get_option('XAGIO_LOCATION_DEFAULT_AUDIT_LANGUAGE');
                        ?>

                        <h3 class="pop">Location</h3>
                        <select id="auditWebsite_default-location" name="lang" class="xagio-input-select xagio-input-select-gray" data-default="<?php echo $audit_location ? wp_kses_post($audit_location) : "en,US" ?>">
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
                            <option value="zh_tw" data-lang="zh_tw" data-lang-code="HK">Hong Kong (zh_tw)</option>
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
                            <option value="zh_tw" data-lang="zh_tw" data-lang-code="TW">Taiwan (zh_tw)</option>
                            <option value="th" data-lang="th" data-lang-code="TH">Thailand (th)</option>
                            <option value="ar" data-lang="ar" data-lang-code="TN">Tunisia (ar)</option>
                            <option value="tr" data-lang="tr" data-lang-code="TR">Turkey (tr)</option>
                            <option value="uk" data-lang="uk" data-lang-code="UA">Ukraine (uk)</option>
                            <option value="ru" data-lang="ru" data-lang-code="UA">Ukraine (ru)</option>
                            <option value="en" data-lang="en" data-lang-code="AE">United Arab Emirates (en)
                            </option>
                            <option value="ar" data-lang="ar" data-lang-code="AE">United Arab Emirates (ar)
                            </option>
                            <option value="en" data-lang="en" data-lang-code="GB">United Kingdom (en)</option>
                            <option value="en" data-lang="en" data-lang-code="US">United States
                                (en)
                            </option>
                            <option value="es" data-lang="es" data-lang-code="UY">Uruguay (es)</option>
                            <option value="es" data-lang="es" data-lang-code="VE">Venezuela (es)</option>
                            <option value="vi" data-lang="vi" data-lang-code="VN">Vietnam (vi)</option>
                        </select>

                    </div>
                </div>
                <div class="xagio-column-1">
                    <div class="xagio-panel">
                        <h5 class="xagio-panel-title">AI Wizard</h5>
                        <div class="xagio-alert xagio-alert-primary">
                            <i class="xagio-icon xagio-icon-info"></i>This sets the default AI Wizard search engine and location. This can be updated at any time.
                        </div>

                        <?php
                            $ai_wizard_se = get_option('XAGIO_LOCATION_DEFAULT_AI_SEARCH_ENGINE');
                        ?>

                        <h3 class="pop">Search Engine</h3>
                        <select id="AiWizard_default-search-engine" class="xagio-input-select xagio-input-select-gray" data-default="<?php echo $ai_wizard_se ? wp_kses_post($ai_wizard_se) : "14" ?>">
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
                            <option value="1033">search.yahoo.com (Democratic Republic of the Congo/All Languages)</option>
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
                            <option value="14">google.com (United States/English)</option>
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


                        <?php
                            $ai_wizard_location = get_option('XAGIO_LOCATION_DEFAULT_AI_LOCATION');
                        ?>

                        <h3 class="pop">Location</h3>
                        <select id="AiWizard_default-location" class="xagio-input-select xagio-input-select-gray" data-default="<?php echo $ai_wizard_location ? wp_kses_post($ai_wizard_location) : "2840" ?>">
                            <option value="2004">Afghanistan</option>
                            <option value="2008">Albania</option>
                            <option value="2010">Antarctica</option>
                            <option value="2012">Algeria</option>
                            <option value="2016">American Samoa</option>
                            <option value="2020">Andorra</option>
                            <option value="2024">Angola</option>
                            <option value="2028">Antigua and Barbuda</option>
                            <option value="2031">Azerbaijan</option>
                            <option value="2032">Argentina</option>
                            <option value="2036">Australia</option>
                            <option value="2040">Austria</option>
                            <option value="2044">The Bahamas</option>
                            <option value="2048">Bahrain</option>
                            <option value="2050">Bangladesh</option>
                            <option value="2051">Armenia</option>
                            <option value="2052">Barbados</option>
                            <option value="2056">Belgium</option>
                            <option value="2060">Bermuda</option>
                            <option value="2064">Bhutan</option>
                            <option value="2068">Bolivia</option>
                            <option value="2070">Bosnia and Herzegovina</option>
                            <option value="2072">Botswana</option>
                            <option value="2074">Bouvet Island</option>
                            <option value="2076">Brazil</option>
                            <option value="2084">Belize</option>
                            <option value="2086">British Indian Ocean Territory</option>
                            <option value="2090">Solomon Islands</option>
                            <option value="2092">British Virgin Islands</option>
                            <option value="2096">Brunei</option>
                            <option value="2100">Bulgaria</option>
                            <option value="2104">Myanmar (Burma)</option>
                            <option value="2108">Burundi</option>
                            <option value="2112">Belarus</option>
                            <option value="2116">Cambodia</option>
                            <option value="2120">Cameroon</option>
                            <option value="2124">Canada</option>
                            <option value="2132">Cape Verde</option>
                            <option value="2136">Cayman Islands</option>
                            <option value="2140">Central African Republic</option>
                            <option value="2144">Sri Lanka</option>
                            <option value="2148">Chad</option>
                            <option value="2152">Chile</option>
                            <option value="2156">China</option>
                            <option value="2158">Taiwan</option>
                            <option value="2162">Christmas Island</option>
                            <option value="2166">Cocos (Keeling) Islands</option>
                            <option value="2170">Colombia</option>
                            <option value="2174">Comoros</option>
                            <option value="2175">Mayotte</option>
                            <option value="2178">Republic of the Congo</option>
                            <option value="2180">Democratic Republic of the Congo</option>
                            <option value="2184">Cook Islands</option>
                            <option value="2188">Costa Rica</option>
                            <option value="2191">Croatia</option>
                            <option value="2196">Cyprus</option>
                            <option value="2203">Czechia</option>
                            <option value="2204">Benin</option>
                            <option value="2208">Denmark</option>
                            <option value="2212">Dominica</option>
                            <option value="2214">Dominican Republic</option>
                            <option value="2218">Ecuador</option>
                            <option value="2222">El Salvador</option>
                            <option value="2226">Equatorial Guinea</option>
                            <option value="2231">Ethiopia</option>
                            <option value="2232">Eritrea</option>
                            <option value="2233">Estonia</option>
                            <option value="2234">Faroe Islands</option>
                            <option value="2238">Falkland Islands (Islas Malvinas)</option>
                            <option value="2239">South Georgia and the South Sandwich Islands</option>
                            <option value="2242">Fiji</option>
                            <option value="2246">Finland</option>
                            <option value="2250">France</option>
                            <option value="2254">French Guiana</option>
                            <option value="2258">French Polynesia</option>
                            <option value="2260">French Southern and Antarctic Lands</option>
                            <option value="2262">Djibouti</option>
                            <option value="2266">Gabon</option>
                            <option value="2268">Georgia</option>
                            <option value="2270">The Gambia</option>
                            <option value="2275">Palestine</option>
                            <option value="2276">Germany</option>
                            <option value="2288">Ghana</option>
                            <option value="2292">Gibraltar</option>
                            <option value="2296">Kiribati</option>
                            <option value="2300">Greece</option>
                            <option value="2304">Greenland</option>
                            <option value="2308">Grenada</option>
                            <option value="2312">Guadeloupe</option>
                            <option value="2316">Guam</option>
                            <option value="2320">Guatemala</option>
                            <option value="2324">Guinea</option>
                            <option value="2328">Guyana</option>
                            <option value="2332">Haiti</option>
                            <option value="2334">Heard Island and McDonald Islands</option>
                            <option value="2336">Vatican City</option>
                            <option value="2340">Honduras</option>
                            <option value="2344">Hong Kong</option>
                            <option value="2348">Hungary</option>
                            <option value="2352">Iceland</option>
                            <option value="2356">India</option>
                            <option value="2360">Indonesia</option>
                            <option value="2368">Iraq</option>
                            <option value="2372">Ireland</option>
                            <option value="2376">Israel</option>
                            <option value="2380">Italy</option>
                            <option value="2384">Cote d'Ivoire</option>
                            <option value="2388">Jamaica</option>
                            <option value="2392">Japan</option>
                            <option value="2398">Kazakhstan</option>
                            <option value="2400">Jordan</option>
                            <option value="2404">Kenya</option>
                            <option value="2410">South Korea</option>
                            <option value="2414">Kuwait</option>
                            <option value="2417">Kyrgyzstan</option>
                            <option value="2418">Laos</option>
                            <option value="2422">Lebanon</option>
                            <option value="2426">Lesotho</option>
                            <option value="2428">Latvia</option>
                            <option value="2430">Liberia</option>
                            <option value="2434">Libya</option>
                            <option value="2438">Liechtenstein</option>
                            <option value="2440">Lithuania</option>
                            <option value="2442">Luxembourg</option>
                            <option value="2446">Macau</option>
                            <option value="2450">Madagascar</option>
                            <option value="2454">Malawi</option>
                            <option value="2458">Malaysia</option>
                            <option value="2462">Maldives</option>
                            <option value="2466">Mali</option>
                            <option value="2470">Malta</option>
                            <option value="2474">Martinique</option>
                            <option value="2478">Mauritania</option>
                            <option value="2480">Mauritius</option>
                            <option value="2484">Mexico</option>
                            <option value="2492">Monaco</option>
                            <option value="2496">Mongolia</option>
                            <option value="2498">Moldova</option>
                            <option value="2499">Montenegro</option>
                            <option value="2500">Montserrat</option>
                            <option value="2504">Morocco</option>
                            <option value="2508">Mozambique</option>
                            <option value="2512">Oman</option>
                            <option value="2516">Namibia</option>
                            <option value="2520">Nauru</option>
                            <option value="2524">Nepal</option>
                            <option value="2528">Netherlands</option>
                            <option value="2530">Netherlands Antilles</option>
                            <option value="2531">Curacao</option>
                            <option value="2533">Aruba</option>
                            <option value="2534">Sint Maarten</option>
                            <option value="2535">Caribbean Netherlands</option>
                            <option value="2540">New Caledonia</option>
                            <option value="2548">Vanuatu</option>
                            <option value="2554">New Zealand</option>
                            <option value="2558">Nicaragua</option>
                            <option value="2562">Niger</option>
                            <option value="2566">Nigeria</option>
                            <option value="2570">Niue</option>
                            <option value="2574">Norfolk Island</option>
                            <option value="2578">Norway</option>
                            <option value="2580">Northern Mariana Islands</option>
                            <option value="2581">United States Minor Outlying Islands</option>
                            <option value="2583">Federated States of Micronesia</option>
                            <option value="2584">Marshall Islands</option>
                            <option value="2585">Palau</option>
                            <option value="2586">Pakistan</option>
                            <option value="2591">Panama</option>
                            <option value="2598">Papua New Guinea</option>
                            <option value="2600">Paraguay</option>
                            <option value="2604">Peru</option>
                            <option value="2608">Philippines</option>
                            <option value="2612">Pitcairn Islands</option>
                            <option value="2616">Poland</option>
                            <option value="2620">Portugal</option>
                            <option value="2624">Guinea-Bissau</option>
                            <option value="2626">Timor-Leste</option>
                            <option value="2630">Puerto Rico</option>
                            <option value="2634">Qatar</option>
                            <option value="2638">Reunion</option>
                            <option value="2642">Romania</option>
                            <option value="2643">Russia</option>
                            <option value="2646">Rwanda</option>
                            <option value="2654">Saint Helena, Ascension and Tristan da Cunha</option>
                            <option value="2659">Saint Kitts and Nevis</option>
                            <option value="2660">Anguilla</option>
                            <option value="2662">Saint Lucia</option>
                            <option value="2666">Saint Pierre and Miquelon</option>
                            <option value="2670">Saint Vincent and the Grenadines</option>
                            <option value="2674">San Marino</option>
                            <option value="2678">Sao Tome and Principe</option>
                            <option value="2682">Saudi Arabia</option>
                            <option value="2686">Senegal</option>
                            <option value="2688">Serbia</option>
                            <option value="2690">Seychelles</option>
                            <option value="2694">Sierra Leone</option>
                            <option value="2702">Singapore</option>
                            <option value="2703">Slovakia</option>
                            <option value="2704">Vietnam</option>
                            <option value="2705">Slovenia</option>
                            <option value="2706">Somalia</option>
                            <option value="2710">South Africa</option>
                            <option value="2716">Zimbabwe</option>
                            <option value="2724">Spain</option>
                            <option value="2732">Western Sahara</option>
                            <option value="2740">Suriname</option>
                            <option value="2744">Svalbard and Jan Mayen</option>
                            <option value="2748">Eswatini</option>
                            <option value="2752">Sweden</option>
                            <option value="2756">Switzerland</option>
                            <option value="2762">Tajikistan</option>
                            <option value="2764">Thailand</option>
                            <option value="2768">Togo</option>
                            <option value="2772">Tokelau</option>
                            <option value="2776">Tonga</option>
                            <option value="2780">Trinidad and Tobago</option>
                            <option value="2784">United Arab Emirates</option>
                            <option value="2788">Tunisia</option>
                            <option value="2792">Turkey</option>
                            <option value="2795">Turkmenistan</option>
                            <option value="2796">Turks and Caicos Islands</option>
                            <option value="2798">Tuvalu</option>
                            <option value="2800">Uganda</option>
                            <option value="2804">Ukraine</option>
                            <option value="2807">North Macedonia</option>
                            <option value="2818">Egypt</option>
                            <option value="2826">United Kingdom</option>
                            <option value="2831">Guernsey</option>
                            <option value="2832">Jersey</option>
                            <option value="2834">Tanzania</option>
                            <option value="2840">United States</option>
                            <option value="2850">U.S. Virgin Islands</option>
                            <option value="2854">Burkina Faso</option>
                            <option value="2858">Uruguay</option>
                            <option value="2860">Uzbekistan</option>
                            <option value="2862">Venezuela</option>
                            <option value="2876">Wallis and Futuna</option>
                            <option value="2882">Samoa</option>
                            <option value="2887">Yemen</option>
                            <option value="2894">Zambia</option>
                            <option value="2900">Kosovo</option>
                        </select>

                    </div>
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="xagio-tab-content">
            <div class="xagio-2-column-grid">

                <div class="xagio-column-1">
                    <div class="xagio-panel">

                        <h5 class="xagio-panel-title">Local Fixes</h5>

                        <!-- Enable/Disable Scripts while logged in -->
                        <div class="xagio-slider-container">
                            <input type="hidden" name="XAGIO_DISABLE_SCRIPTS_LOGGED_IN" id="XAGIO_DISABLE_SCRIPTS_LOGGED_IN" value="<?php echo  XAGIO_DISABLE_SCRIPTS_LOGGED_IN ? 1 : 0; ?>"/>
                            <div class="xagio-slider-frame">
                                <span class="xagio-slider-button xagio-slider-button-settings <?php echo  XAGIO_DISABLE_SCRIPTS_LOGGED_IN ? 'on' : ''; ?>" data-element="XAGIO_DISABLE_SCRIPTS_LOGGED_IN"></span>
                            </div>
                            <p class="xagio-slider-label">Disable <b>Scripts</b> while Logged In <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will prevent loading of the Header/Body/Footer scripts while you're logged in."></i></p>
                        </div>

                        <!-- Enable/Disable Force Disable Maintenance Mode -->
                        <div class="xagio-slider-container">
                            <input type="hidden" name="XAGIO_DISABLE_MAINTENANCE" id="XAGIO_DISABLE_MAINTENANCE" value="<?php echo  XAGIO_DISABLE_MAINTENANCE ? 1 : 0; ?>"/>
                            <div class="xagio-slider-frame">
                                <span class="xagio-slider-button xagio-slider-button-settings <?php echo  XAGIO_DISABLE_MAINTENANCE ? 'on' : ''; ?>" data-element="XAGIO_DISABLE_MAINTENANCE"></span>
                            </div>
                            <p class="xagio-slider-label">Force <b>Disable</b> Maintenance Mode <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will prevent maintenance mode on some problematic hosting platforms to prevent errors in communication."></i></p>
                        </div>

                        <!-- Enable/Disable DEV mode -->
                        <div class="xagio-slider-container">
                            <input type="hidden" name="XAGIO_DEV_MODE" id="XAGIO_DEV_MODE" value="<?php echo  XAGIO_DEV_MODE ? 1 : 0; ?>"/>
                            <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button xagio-slider-button-settings <?php echo  XAGIO_DEV_MODE ? 'on' : ''; ?>" data-element="XAGIO_DEV_MODE"></span>
                            </div>
                            <p class="xagio-slider-label">Activate <b>DEV Mode</b> <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Turns on DEV mode and unlocks features useful only to developers."></i></p>
                        </div>

                    </div>
                </div>

                <div class="xagio-column-2">
                    <div class="xagio-panel">
                        <h5 class="xagio-panel-title">Server Fixes</h5>

                        <?php if (XAGIO_CONNECTED): ?>
                            <p>
                                This option will establish connection and synchronize all necessary keys
                                between your plugin and main application. If our main application shows that your plugin is deactivated, and it's not, please use this
                                option.
                            </p>

                            <button type="button" class="xagio-button xagio-button-primary action-button" data-target="xagio_settings_troubleshoot_common_issues"><i class="xagio-icon xagio-icon-gear"></i> Fix Common Issues</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="xagio-tab-content">

            <div class="xagio-panel xagio-margin-bottom-medium">
                <div class="xagio-panel-title">Xagio Variables</div>

                <div class="xagio-table-responsive">
                    <table class="xagio-system-status-table">
                        <tbody>
                        <tr>
                            <td>Current Version</td>
                            <td><?php echo  esc_html(xagio_get_version()); ?></td>
                        </tr>
                        <tr>
                            <td>Panel URL</td>
                            <td><?php echo  esc_url(XAGIO_PANEL_URL); ?></td>
                        </tr>
                        <tr>
                            <td>Plugin Path</td>
                            <td><?php echo  esc_url(XAGIO_PATH); ?></td>
                        </tr>
                        <tr>
                            <td>Plugin URL Path</td>
                            <td><?php echo  esc_url(XAGIO_URL); ?></td>
                        </tr>
                        <tr>
                            <td>Plugin API URL</td>
                            <td><?php echo  esc_html(XAGIO_MODEL_SETTINGS::getApiUrl()); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="xagio-panel xagio-margin-bottom-medium">
                <div class="xagio-panel-title">WordPress Environment</div>

                <div class="xagio-table-responsive">
                    <table class="xagio-system-status-table">
                        <tbody>
                        <tr>
                            <td>Home URL:</td>
                            <td><?php echo  esc_url(get_home_url()); ?></td>
                        </tr>
                        <tr>
                            <td>Site URL:</td>
                            <td><?php echo  esc_url(get_site_url()); ?></td>
                        </tr>
                        <tr>
                            <td>WP Version:</td>
                            <td><?php echo  esc_html(get_bloginfo('version')); ?></td>
                        </tr>
                        <tr>
                            <td>WP Multisite:</td>
                            <td><?php echo  (is_multisite()) ? '<i class="xagio-icon xagio-icon-check uk-text-danger"></i>' : '-' ?></td>
                        </tr>
                        <tr>
                            <td>WP Memory Limit:</td>
                            <td><?php echo  esc_html(WP_MEMORY_LIMIT); ?></td>
                        </tr>
                        <tr>
                            <td>WP Debug Mode:</td>
                            <td><?php echo  (defined('WP_DEBUG') && TRUE === WP_DEBUG) ? '<i class="xagio-icon xagio-icon-check"><i>' : '<i class="xagio-icon xagio-icon-close"><i>' ?></td>
                        </tr>
                        <tr>
                            <td>Language:</td>
                            <td><?php echo  esc_html(get_bloginfo('language')); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="xagio-panel xagio-margin-bottom-medium">
                <div class="xagio-panel-title">Server Environment</div>

                <div class="xagio-table-responsive">
                    <table class="xagio-system-status-table">
                        <tbody>
                        <tr>
                            <td>Server Info:</td>
                            <td><?php echo isset($_SERVER['SERVER_SOFTWARE']) ? esc_html(sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE']))) : 'n/a'; ?></td>
                        </tr>
                        <tr>
                            <td>PHP Version:</td>
                            <td><?php echo  esc_html(phpversion()); ?></td>
                        </tr>
                        <tr>
                            <td>PHP Post Max Size:</td>
                            <td>
                                <?php echo esc_html(ini_get('post_max_size')); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>PHP Max Upload Size:</td>
                            <td>
                                <?php echo esc_html(ini_get('upload_max_filesize')); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>PHP Time Limit:</td>
                            <td>
                                <?php echo esc_html(ini_get('max_execution_time')); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>PHP Max Input Vars:</td>
                            <td>
                                <?php echo esc_html(ini_get('max_input_vars')); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>PHP Memory Limit:</td>
                            <td>
                                <?php echo esc_html(ini_get('memory_limit')); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>OpenSSL:</td>
                            <td><?php
                                if (defined('OPENSSL_VERSION_NUMBER')) {

                                    if (OPENSSL_VERSION_NUMBER >= 268439647) {
                                        ?><i class="xagio-icon xagio-icon-check uk-text-success"></i><?php
                                    } else {
                                        ?><i title="OpenSSL version must be at least 1.0.1e."
                                             class="xagio-icon xagio-icon-close uk-text-danger"></i><?php
                                    }

                                } else {
                                    ?><i title="OpenSSL is not installed." class="xagio-icon xagio-icon-close uk-text-danger"></i><?php
                                }
                                ?></td>
                        </tr>
                        <tr>
                            <td>cURL:</td>
                            <td><?php echo  (function_exists('curl_init')) ? '<i class="xagio-icon xagio-icon-check uk-text-success"></i>' : '<i class="xagio-icon xagio-icon-close uk-text-danger"></i>'; ?></td>
                        </tr>
                        <tr>
                            <td>ZipArchive:</td>
                            <td><?php echo  (class_exists('ZipArchive')) ? '<i class="xagio-icon xagio-icon-check uk-text-success"></i>' : '<i class="xagio-icon xagio-icon-close uk-text-danger"></i>'; ?></td>
                        </tr>
                        <tr>
                            <td>DOMDocument:</td>
                            <td><?php echo  (class_exists('DOMDocument')) ? '<i class="xagio-icon xagio-icon-check uk-text-success"></i>' : '<i class="xagio-icon xagio-icon-close uk-text-danger"></i>'; ?></td>
                        </tr>
                        <tr>
                            <td>WP Remote Get:</td>
                            <td><?php echo  (function_exists('wp_remote_get')) ? '<i class="xagio-icon xagio-icon-check uk-text-success"></i>' : '<i class="xagio-icon xagio-icon-close uk-text-danger"></i>'; ?></td>
                        </tr>
                        <tr>
                            <td>WP Remote Post:</td>
                            <td><?php echo  (function_exists('wp_remote_post')) ? '<i class="xagio-icon xagio-icon-check uk-text-success"></i>' : '<i class="xagio-icon xagio-icon-close uk-text-danger"></i>'; ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div> <!-- .wrap -->