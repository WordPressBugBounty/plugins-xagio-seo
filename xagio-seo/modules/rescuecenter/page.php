<?php
/**
 * Type: SUBMENU
 * Page_Title: Rescue Center
 * Menu_Title: Rescue Center
 * Capability: manage_options
 * Slug: xagio-rescuecenter
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: xagio_jstree,xagio_rescuecenter
 * Css: xagio_jstree,xagio_rescuecenter
 * Position: 13
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


$MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');
?>
<div class="xagio-main-header">
    <img class="logo-image repo-xagio" src="<?php echo  esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        Rescue Center
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
            <span>Rescue Center is to be used after a suspected activity on your WordPress.</span>
            <i class="xagio-icon xagio-icon-arrow-down"></i>
        </h3>
        <div class="xagio-accordion-content">
            <div>
                <div class="xagio-accordion-panel">This means that if you believe your website got hacked and some files are infected or added, you should use the Rescue Center to revert any changes to your WordPress back to initial state.</div>
            </div>
        </div>
    </div>


    <ul class="xagio-tab">
        <li class="xagio-tab-active"><a href="">WordPress</a></li>
        <li><a href="">Plugin & Themes</a></li>
        <li><a href="">Upload</a></li>
    </ul>

    <div class="xagio-tab-content-holder">

        <!-- WordPress -->
        <div class="xagio-tab-content">
            <?php if (file_exists(XAGIO_PATH . '/tmp/wordpress')) { ?>

                <p class="logo-paragraph logo-paragraph-warning logo-paragraph-small uk-block-xagio old-core-files-message">
                    <i class="xagio-icon xagio-icon-warning"></i> <b>Previous WordPress core files detected!</b> It's
                    highly advisable to remove these files from previous WordPress Rescue process.

                    <button class="xagio-button xagio-button-primary remove-old-core-files"><i
                                class="xagio-icon xagio-icon-delete"></i> Remove Old Files
                    </button>
                </p>

            <?php } ?>

            <div class="xagio-2-column-grid rescue-select-mode">
                <div class="xagio-column-1">

                    <div class="xagio-panel xagio-panel-jumbotron">
                        <h2>Easy Mode</h2>
                        <p>If you want to remove all non-WordPress core files and overwrite existing core files with the
                            ones from WordPress repository, select this mode.</p>

                        <button data-type="easy" class="xagio-button xagio-button-primary begin-core-rescue" type="button">
                            Start Now
                        </button>

                    </div>

                </div>
                <div class="xagio-column-2">

                    <div class="xagio-panel xagio-panel-jumbotron">
                        <h2>Advanced Mode</h2>
                        <p>If there are specific non-WordPress core files in your WordPress installation that you want
                            to keep while overwriting existing WordPress core files, select this mode.</p>

                        <button data-type="advanced" class="xagio-button xagio-button-primary begin-core-rescue" type="button">
                            Start Now
                        </button>

                    </div>

                </div>
            </div>

            <div class="xagio-panel rescue-container" style="display: none">

                <h2>WordPress Rescue (<span class="rescue-core-type">...</span> mode)</h2>

                <!-- Select WP Version -->
                <div class="rescue-core-version">

                    <p>
                        Please pick a version of WordPress core files to download. Usually, you should go for the
                        current version of your WordPress, but in some cases where you think that the current
                        version is compromised, you can use the latest one offered.
                    </p>

                    <div class="uk-form-row m-b-10">

                        <select class="xagio-input-select xagio-input-select-gray" id="rescue-core-version-value">
                            <option value="">Select WordPress Version</option>

                            <optgroup label="Current Version">
                                <option value="<?php echo esc_attr(get_bloginfo('version')); ?>"><?php echo esc_html(get_bloginfo('version')); ?></option>
                            </optgroup>

                            <!-- There has to be a better way to do this -->
                            <optgroup label="All Versions">
                                <?php foreach (XAGIO_MODEL_RESCUE::getAvailableCoreVersions() as $version) { ?>
                                    <option value="<?php echo esc_attr($version); ?>"><?php echo esc_html($version); ?></option>
                                <?php } ?>
                            </optgroup>

                        </select>
                    </div>

                    <div class="xagio-alert xagio-alert-primary xagio-alert-large" data-uk-alert="">
                        <p><i class="xagio-icon xagio-icon-info"></i>  If you choose to proceed, your WordPress core files will be downloaded to the following folder
                            <br>
                            <b><?php echo esc_url(XAGIO_PATH); ?>/tmp/wordpress</b></p>
                    </div>


                    <div class="xagio-flex-right xagio-margin-top-large xagio-gap-13">
                        <button class="xagio-button xagio-button-primary rescue-core-close" type="button"><i
                                    class="xagio-icon xagio-icon-arrow-left"></i> Back
                        </button>
                        <button class="xagio-button xagio-button-primary select-core-version" type="button"><i
                                    class="xagio-icon xagio-icon-download"></i> Download Selected WordPress Version
                        </button>
                    </div>

                </div>

                <!-- Download WP -->
                <div class="rescue-core-download" style="display: none">

                    <div class="download-core-message xagio-alert xagio-alert-primary xagio-alert-large xagio-margin-bottom-medium" data-uk-alert="">
                        <h2>...</h2>
                        <p>...</p>
                    </div>

                    <button class="xagio-button xagio-button-primary download-core-close rescue-core-close xagio-hidden" type="button"><i
                                class="xagio-icon xagio-icon-close"></i> Close
                    </button>
                    <button class="xagio-button xagio-button-primary preview-core-files" type="button"><i
                                class="xagio-icon xagio-icon-search"></i> Perform File Analysis
                    </button>

                </div>

                <!-- Preview Files -->
                <div class="rescue-core-files" style="display: none">

                    <div class="xagio-alert xagio-alert-primary xagio-alert-large xagio-margin-bottom-medium" data-uk-alert="">
                        <h2>Preview files changes.</h2>
                        <p>
                            Below you'll see a list of files and their changes. <b data-action="overwrite">Black</b>
                            files are identical both locally and remotely. <b data-action="add">Green</b> are present
                            remotely but not locally and will be added. <b data-action="force-overwrite">Blue</b> files
                            are different locally and should be overwritten by all means, while <b data-action="delete">Red</b>
                            files are new files locally that does not exist remotely.
                        </p>

                        <h3>Easy Mode and Advanced Mode</h3>

                        <p>In Easy Mode, <b data-action="add">Green</b> files will be automatically added to local file
                            base, <b data-action="force-overwrite">Blue</b> files will be automatically overwritten by
                            remote file counterparts while <b data-action="delete">Red</b> files will be deleted.
                            However, if you pick Advanced mode, you will be able to custom select files to rescue.</p>

                        <h3>wp-config.php</h3>

                        <p>WordPress configuration file will be automatically re-built during the rescue operation, no
                            matter the mode selected.</p>
                    </div>

                    <div id="rescue-core-files-list"></div>

                    <button class="xagio-margin-top-medium xagio-button xagio-button-primary xagio-button xagio-button-primary-large xagio-button xagio-button-primary-success start-core-rescue" type="button"><i
                                class="xagio-icon xagio-icon-plus"></i> Start Rescue Operation
                    </button>

                </div>

                <!-- Start Rescue -->
                <div class="rescue-core-operation" style="display: none">

                    <div class="rescue-core-message xagio-alert xagio-alert-primary xagio-alert-large" data-uk-alert="">
                        <h2>...</h2>
                        <p>...</p>
                    </div>

                </div>


            </div>

        </div>
        <!-- Plugins -->
        <div class="xagio-tab-content">
            <div class="xagio-panel">

                <h3 class="xagio-panel-title">Rescue from WordPress Repository</h3>

                <p class="xagio-text-info">
                    Plugins & Themes in this list can easily be rescued by automatically downloading
                    them from their official WordPress repository. We will match the
                    version of plugin / theme you currently have installed when rescuing.
                </p>

                <div class="rescue-loading-skeleton first">
                    <div class="rescue-skelet"></div>
                    <div class="rescue-skelet"></div>
                    <div class="rescue-skelet"></div>
                    <div class="rescue-skelet"></div>
                </div>

                <div class="rescue-found-plugins"></div>
                <div class="rescue-found-themes"></div>

            </div>

            <div class="xagio-panel xagio-margin-top-medium">

                <h3 class="xagio-panel-title">Rescue by Upload</h3>

                <p class="xagio-text-info">
                    Since we couldn't find the official WordPress repository for these plugins & themes
                    in the list below, you will need to supply your own original zip
                    files of plugins / themes.
                </p>

                <div class="rescue-loading-skeleton first">
                    <div class="rescue-skelet"></div>
                    <div class="rescue-skelet"></div>
                    <div class="rescue-skelet"></div>
                    <div class="rescue-skelet"></div>
                </div>

                <div class="rescue-missing-plugins"></div>
                <div class="rescue-missing-themes"></div>

            </div>
        </div>
        <!-- Uploads -->
        <div class="xagio-tab-content">
            <div class="xagio-panel">

                <div class="xagio-flex-space-between xagio-margin-bottom-medium upload-title-holder">
                    <h3 class="xagio-panel-title upload-title">Upload</h3>
                    <button type="button"
                            class="xagio-button xagio-button-primary rescue-uploads-remove-selected xagio-hidden">
                        <i class="xagio-icon xagio-icon-delete"></i> Remove Selected Files
                    </button>
                </div>

                <div class="rescue-loading-skeleton second">
                    <h3 class="xagio-panel-title"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Analyzing your Uploads directory...</h3>
                    <div class="rescue-skelet"></div>
                    <div class="rescue-skelet"></div>
                    <div class="rescue-skelet"></div>
                    <div class="rescue-skelet"></div>
                </div>

                <div class="xagio-alert xagio-alert-large rescue-uploads-alert xagio-hidden">
                    <h2>...</h2>
                    <p>
                        ...
                    </p>
                </div>

                <div class="rescue-uploads-files xagio-margin-top-medium xagio-hidden"></div>
            </div>
        </div>
    </div>

<!--    <ul class="repo-xagio uk-tab uk-tab-big" data-uk-tab="{connect:'#tab-content', animation: 'fade'}">-->
<!--        <li class="uk-active"><a href="">WordPress</a></li>-->
<!--        <li><a href="">Plugin & Themes</a></li>-->
<!--        <li><a href="">Upload</a></li>-->
<!--    </ul>-->


    <div id="tab-content" class="uk-switcher">


        <div>



        </div>


        <div>



        </div>


        <div>

        </div>

    </div>

    <div class="rescue-upload-template xagio-hidden">

        <div class="rescue-two-rows">

            <div class="rescue-description">
                <h3 class="rescue-type">...</h3>
                <input type="checkbox" class="xagio-input-checkbox rescue-uploads-select"/>
                <h3 class="rescue-name">...</h3>
            </div>

            <span class="rescue-location">...</span>

        </div>

        <div class="rescue-upload-buttons">

            <button class="xagio-button xagio-button-small xagio-button-danger rescue-uploads-remove "
                    type="button"><i class="xagio-icon xagio-icon-delete"></i>
            </button>

        </div>

        </div>

    <div class="rescue-plugin-theme-template xagio-hidden">

        <div class="rescue-description">
            <h3 class="rescue-type">...</h3>
            <div>
                <h3 class="rescue-name">...</h3>
                <span class="rescue-version">...</span>
            </div>
        </div>

        <div class="rescue-plugin-theme-buttons">

            <button class="xagio-button xagio-button-primary begin-plugin-theme-rescue xagio-hidden"
                    type="button"><i class="xagio-icon xagio-icon-plus"></i> Rescue
            </button>

            <label for="plugin-theme-upload"
                   class="xagio-button xagio-button-primary upload-plugin-theme-rescue xagio-hidden">
                <i class="xagio-icon xagio-icon-upload"></i> Upload & Rescue
            </label>

            <input id="plugin-theme-upload" class="plugin-theme-upload" type="file"/>

            <button class="xagio-button xagio-button-primary remove-plugin-theme-rescue" type="button"><i
                        class="xagio-icon xagio-icon-delete"></i> Uninstall
            </button>

        </div>

        <div class="rescue-plugin-theme-alert xagio-hidden">
            Successfully finished rescue operation.
        </div>

    </div>

</div> <!-- .wrap -->

