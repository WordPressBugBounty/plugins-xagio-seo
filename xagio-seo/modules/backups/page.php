<?php
/**
 * Type: SUBMENU
 * Page_Title: Backup & Restore
 * Menu_Title: Backup & Restore
 * Capability: manage_options
 * Slug: xagio-backups
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: xagio_backup
 * Css: xagio_backup
 * Position: 11
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

XAGIO_SYNC::getBackupSettings();

XAGIO_MODEL_BACKUPS::trimBackups();

$xagio_tokens    = get_option('XAGIO_BACKUP_SETTINGS');
$xagio_location  = get_option("XAGIO_BACKUP_LOCATION");
$xagio_copies    = get_option("XAGIO_BACKUP_LIMIT");
$xagio_frequency = get_option("XAGIO_BACKUP_DATE");

$XAGIO_MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');
?>

<div class="xagio-main-header xagio-main-header-big-gaps">
    <img class="logo-image repo-xagio" src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        Backup & Restore
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
            <span>By using Xagio Backup & Restore, you will be able to effortlessly backup and restore whole websites.</span>
            <i class="xagio-icon xagio-icon-arrow-down"></i>
        </h3>
        <div class="xagio-accordion-content">
            <div>
                <div class="xagio-accordion-panel"></div>
            </div>
        </div>
    </div>


    <ul class="xagio-tab">
        <li class="xagio-tab-active"><a href="">Backup</a></li>
        <li><a href="">Restore</a></li>
        <li><a href="">Backup Settings</a></li>
    </ul>
    <div class="xagio-tab-content-holder">
        <!-- Backup -->
        <div class="xagio-tab-content">
            <div class="xagio-panel">
                <h3 class="xagio-panel-title">Backup Website</h3>
                <p class="xagio-text-info">A full backup creates an archive of all your WordPress files and database
                    settings. You can use this file to move your website to another location or to keep a copy of your
                    website. Here, you are able to either backup and restore files or databases only of this
                    website.</p>

                <form class="create-backup">

                    <input type="hidden" name="action" value="xagio_create_backup"/>

                    <div class="backup-grid">
                        <div>
                            <select name="type" class="xagio-input-select xagio-input-select-gray">
                                <option value="full">Create Full Backup</option>
                                <optgroup label="Partial Backup">
                                    <option value="files">Create Files Backup</option>
                                    <option value="mysql">Create Database Backup</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <select name="destination" class="xagio-input-select xagio-input-select-gray" required>
                                <option value="">Save to...</option>
                                <option value="local">Local Backups</option>
                                <optgroup label="Remote Backups">
                                    <option value="dropbox">Dropbox</option>
                                    <option value="googledrive">Google Drive</option>
                                    <option value="onedrive">OneDrive</option>
                                    <option value="amazons3">Amazon S3</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="xagio-button xagio-button-primary"><i class="xagio-icon xagio-icon-check"></i>
                                Create Backup
                            </button>
                        </div>
                    </div>

                </form>

                <div class="xagio-progress xagio-progress-green xagio-progress-bar-infinite xagio-margin-top-medium backup-progress"
                        style="display: none">
                    <div class="xagio-progress-bar"> </div>
                </div>

            </div>

            <div class="xagio-2-column-grid xagio-margin-top-medium">

                <div class="xagio-panel">
                    <h3 class="xagio-panel-title local-backups">Local Backups</h3>
                    <p class="xagio-text-info">A list of locally stored backups on this server for this website.</p>

                    <div class="backups"><?php
                        $xagio_backups  = glob(XAGIO_PATH . '/backups/*_full_*.zip');
                        if (!empty($xagio_backups)):

                            // Sort backups by date created using usort
                            usort($xagio_backups, function ($a, $b) {
                                return filectime($b) - filectime($a);
                            });

                            ?><?php foreach ($xagio_backups as $xagio_backup):
                            $xagio_date = gmdate("F d Y H:i:s", filectime($xagio_backup));
                            $xagio_name = basename($xagio_backup);
                            $xagio_url  = XAGIO_URL . 'backups/' . $xagio_name;
                            ?>
                            <div class="backup-template">

                                <div class="backup-description">
                                    <h3 class="backup-type full">Full</h3>
                                    <h3 class="backup-name"><?php echo esc_html($xagio_date); ?></h3>
                                </div>

                                <div class="backup-buttons">

                                    <button class="xagio-button xagio-button-small xagio-button-primary download-backup"
                                            type="button" data-url="<?php echo esc_attr($xagio_url); ?>"
                                            data-xagio-tooltip data-xagio-title="Download this backup"><i class="xagio-icon xagio-icon-download"></i>
                                    </button>

                                    <button class="xagio-button xagio-button-small xagio-button-alternative restore-backup"
                                            type="button" data-url="<?php echo esc_attr($xagio_url); ?>"
                                            data-xagio-tooltip data-xagio-title="Restore this backup"><i class="xagio-icon xagio-icon-upload"></i>
                                    </button>

                                    <button class="xagio-button xagio-button-small xagio-button-danger remove-backup"
                                            type="button" data-name="<?php echo esc_attr($xagio_name); ?>" data-xagio-tooltip data-xagio-title="Remove this backup"><i class="xagio-icon xagio-icon-delete"></i>
                                    </button>

                                </div>

                            </div>
                        <?php
                        endforeach;
                        endif;

                        $xagio_backups      = glob(XAGIO_PATH . '/backups/*_files_*.zip');
                        if (!empty($xagio_backups)):

                            // Sort backups by date created using usort
                            usort($xagio_backups, function ($a, $b) {
                                return filectime($b) - filectime($a);
                            });

                            foreach ($xagio_backups as $xagio_backup):
                                $xagio_date = gmdate("F d Y H:i:s", filectime($xagio_backup));
                                $xagio_name = basename($xagio_backup);
                                $xagio_url  = XAGIO_URL . 'backups/' . $xagio_name;

                                ?>

                                <div class="backup-template">

                                    <div class="backup-description">
                                        <h3 class="backup-type files">Files</h3>
                                        <h3 class="backup-name"><?php echo esc_html($xagio_date); ?></h3>
                                    </div>

                                    <div class="backup-buttons">

                                        <button class="xagio-button xagio-button-small xagio-button-primary download-backup"
                                                type="button" data-url="<?php echo esc_attr($xagio_url); ?>"
                                                data-xagio-tooltip data-xagio-title="Download this backup"><i
                                                    class="xagio-icon xagio-icon-download"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-alternative restore-backup"
                                                type="button" data-url="<?php echo esc_attr($xagio_url); ?>"
                                                data-xagio-tooltip data-xagio-title="Restore this backup"><i
                                                    class="xagio-icon xagio-icon-upload"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-danger remove-backup"
                                                type="button" data-name="<?php echo esc_attr($xagio_name); ?>" data-xagio-tooltip data-xagio-title="Remove this backup"><i class="xagio-icon xagio-icon-delete"></i>
                                        </button>

                                    </div>

                                </div>

                            <?php
                            endforeach;
                        endif;

                        $xagio_backups      = glob(XAGIO_PATH . '/backups/*_mysql_*.zip');
                        if (!empty($xagio_backups)):

                            // Sort backups by date created using usort
                            usort($xagio_backups, function ($a, $b) {
                                return filectime($b) - filectime($a);
                            });

                            foreach ($xagio_backups as $xagio_backup):
                                $xagio_date = gmdate("F d Y H:i:s", filectime($xagio_backup));
                                $xagio_name = basename($xagio_backup);
                                $xagio_url  = XAGIO_URL . 'backups/' . $xagio_name;
                                ?>
                                <div class="backup-template">

                                    <div class="backup-description">
                                        <h3 class="backup-type mysql">Database</h3>
                                        <h3 class="backup-name"><?php echo esc_html($xagio_date); ?></h3>
                                    </div>

                                    <div class="backup-buttons">

                                        <button class="xagio-button xagio-button-small xagio-button-primary download-backup"
                                                type="button" data-url="<?php echo esc_attr($xagio_url); ?>"
                                                data-xagio-tooltip data-xagio-title="Download this backup"><i
                                                    class="xagio-icon xagio-icon-download"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-alternative restore-backup"
                                                type="button" data-url="<?php echo esc_attr($xagio_url); ?>"
                                                data-xagio-tooltip data-xagio-title="Restore this backup"><i
                                                    class="xagio-icon xagio-icon-upload"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-danger remove-backup"
                                                type="button" data-name="<?php echo esc_attr($xagio_name); ?>" data-xagio-tooltip data-xagio-title="Remove this backup"><i class="xagio-icon xagio-icon-delete"></i>
                                        </button>

                                    </div>

                                </div>
                            <?php

                            endforeach;
                        endif; ?></div>


                </div>

                <div class="xagio-panel">
                    <h3 class="xagio-panel-title view-remote">
                        Remote Backups

                        <select data-selected="<?php echo esc_attr($xagio_location); ?>"
                                class="xagio-input-select xagio-input-select-gray view-remote-backups">
                            <option value="none">View Backups from...</option>
                            <optgroup label="Remote Locations">
                                <option value="onedrive">OneDrive</option>
                                <option value="amazons3">Amazon S3</option>
                                <option value="googledrive">Google Drive</option>
                                <option value="dropbox">Dropbox</option>
                            </optgroup>
                        </select>

                    </h3>
                    <p class="xagio-text-info">A list of remotely stored backups of this website on a storage method of
                        your choice.</p>

                    <div class="remote-backups"></div>
                </div>

            </div>
        </div>
        <!-- Restore -->
        <div class="xagio-tab-content">
            <div class="xagio-panel">
                <h3 class="xagio-panel-title">Restore Website</h3>
                <p class="xagio-text-info">A full backup creates an archive of all your WordPress files and database
                    settings. You can use this file to move your website to another location or to keep a copy of your
                    website. Here, you are able to either backup and restore files or databases only of this
                    website.</p>

                <div class="restore-area">

                    <i class="xagio-icon xagio-icon-upload"></i>
                    <input type="file" id="fileInput" style="display:none;"/>
                    <p>Drag your backup file here or browse by <a href="">selecting a file</a></p>

                </div>

                <div class="restore-progress xagio-margin-top-medium" style="display: none">
                    <div class="xagio-progress xagio-progress-green">
                        <div class="xagio-progress-bar"> </div>
                    </div>
                    <h3 class="restore-status"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Uploading...</span></h3>
                </div>

            </div>

            <div class="xagio-2-column-grid xagio-margin-top-medium">

                <div class="xagio-panel">
                    <h3 class="xagio-panel-title local-backups">Local Backups</h3>
                    <p class="xagio-text-info">A list of locally stored backups on this server for this website.</p>

                    <div class="backups"><?php
                        $xagio_backups  = glob(XAGIO_PATH . '/backups/*_full_*.zip');
                        if (!empty($xagio_backups)):

                            // Sort backups by date created using usort
                            usort($xagio_backups, function ($a, $b) {
                                return filectime($b) - filectime($a);
                            });

                            ?><?php foreach ($xagio_backups as $xagio_backup):
                            $xagio_date = gmdate("F d Y H:i:s", filectime($xagio_backup));
                            $xagio_name = basename($xagio_backup);
                            $xagio_url  = XAGIO_URL . 'backups/' . $xagio_name;
                            ?>
                            <div class="backup-template">

                                <div class="backup-description">
                                    <h3 class="backup-type full">Full</h3>
                                    <h3 class="backup-name"><?php echo esc_html($xagio_date); ?></h3>
                                </div>

                                <div class="backup-buttons">

                                    <button class="xagio-button xagio-button-small xagio-button-primary download-backup"
                                            type="button" data-url="<?php echo esc_attr($xagio_url); ?>"
                                            data-xagio-tooltip data-xagio-title="Download this backup"><i class="xagio-icon xagio-icon-download"></i>
                                    </button>

                                    <button class="xagio-button xagio-button-small xagio-button-alternative restore-backup"
                                            type="button" data-url="<?php echo esc_attr($xagio_url); ?>"
                                            data-xagio-tooltip data-xagio-title="Restore this backup"><i class="xagio-icon xagio-icon-upload"></i>
                                    </button>

                                    <button class="xagio-button xagio-button-small xagio-button-danger remove-backup"
                                            type="button" data-name="<?php echo esc_attr($xagio_name); ?>" data-xagio-tooltip data-xagio-title="Remove this backup"><i class="xagio-icon xagio-icon-delete"></i>
                                    </button>

                                </div>

                            </div>
                        <?php
                        endforeach;
                        endif;

                        $xagio_backups      = glob(XAGIO_PATH . '/backups/*_files_*.zip');
                        if (!empty($xagio_backups)):

                            // Sort backups by date created using usort
                            usort($xagio_backups, function ($a, $b) {
                                return filectime($b) - filectime($a);
                            });

                            foreach ($xagio_backups as $xagio_backup):
                                $xagio_date = gmdate("F d Y H:i:s", filectime($xagio_backup));
                                $xagio_name = basename($xagio_backup);
                                $xagio_url  = XAGIO_URL . 'backups/' . $xagio_name;

                                ?>

                                <div class="backup-template">

                                    <div class="backup-description">
                                        <h3 class="backup-type files">Files</h3>
                                        <h3 class="backup-name"><?php echo esc_html($xagio_date); ?></h3>
                                    </div>

                                    <div class="backup-buttons">

                                        <button class="xagio-button xagio-button-small xagio-button-primary download-backup"
                                                type="button" data-url="<?php echo esc_attr($xagio_url); ?>"
                                                data-xagio-tooltip data-xagio-title="Download this backup"><i
                                                    class="xagio-icon xagio-icon-download"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-alternative restore-backup"
                                                type="button" data-url="<?php echo esc_attr($xagio_url); ?>"
                                                data-xagio-tooltip data-xagio-title="Restore this backup"><i
                                                    class="xagio-icon xagio-icon-upload"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-danger remove-backup"
                                                type="button" data-name="<?php echo esc_attr($xagio_name); ?>" data-xagio-tooltip data-xagio-title="Remove this backup"><i class="xagio-icon xagio-icon-delete"></i>
                                        </button>

                                    </div>

                                </div>

                            <?php
                            endforeach;
                        endif;

                        $xagio_backups      = glob(XAGIO_PATH . '/backups/*_mysql_*.zip');
                        if (!empty($xagio_backups)):

                            // Sort backups by date created using usort
                            usort($xagio_backups, function ($a, $b) {
                                return filectime($b) - filectime($a);
                            });

                            foreach ($xagio_backups as $xagio_backup):
                                $xagio_date = gmdate("F d Y H:i:s", filectime($xagio_backup));
                                $xagio_name = basename($xagio_backup);
                                $xagio_url  = XAGIO_URL . 'backups/' . $xagio_name;
                                ?>
                                <div class="backup-template">

                                    <div class="backup-description">
                                        <h3 class="backup-type mysql">Database</h3>
                                        <h3 class="backup-name"><?php echo esc_html($xagio_date); ?></h3>
                                    </div>

                                    <div class="backup-buttons">

                                        <button class="xagio-button xagio-button-small xagio-button-primary download-backup"
                                                type="button" data-url="<?php echo esc_attr($xagio_url); ?>"
                                                data-xagio-tooltip data-xagio-title="Download this backup"><i
                                                    class="xagio-icon xagio-icon-download"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-alternative restore-backup"
                                                type="button" data-url="<?php echo esc_attr($xagio_url); ?>"
                                                data-xagio-tooltip data-xagio-title="Restore this backup"><i
                                                    class="xagio-icon xagio-icon-upload"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-danger remove-backup"
                                                type="button" data-name="<?php echo esc_attr($xagio_name); ?>" data-xagio-tooltip data-xagio-title="Remove this backup"><i class="xagio-icon xagio-icon-delete"></i>
                                        </button>

                                    </div>

                                </div>
                            <?php

                            endforeach;
                        endif; ?></div>


                </div>

                <div class="xagio-panel">
                    <h3 class="xagio-panel-title view-remote">
                        Remote Backups

                        <select class="xagio-input-select xagio-input-select-gray view-remote-backups">
                            <option value="">View Backups from...</option>
                            <optgroup label="Remote Locations">
                                <option value="onedrive">OneDrive</option>
                                <option value="amazons3">Amazon S3</option>
                                <option value="googledrive">Google Drive</option>
                                <option value="dropbox">Dropbox</option>
                            </optgroup>
                        </select>

                    </h3>
                    <p class="xagio-text-info">A list of remotely stored backups of this website on a storage method of
                        your choice.</p>

                    <div class="remote-backups"></div>
                </div>

            </div>
        </div>
        <!-- Backup Settings -->
        <div class="xagio-tab-content">
            <div class="xagio-2-column-grid ">

                <div>

                    <div class="xagio-panel backup-settings">
                        <h3 class="xagio-panel-title">Backup Location Settings</h3>
                        <p class="xagio-text-info">
                            Authorize third-party cloud storage services to keep your backups in a completely different location to your website for added protection.
                        </p>

                        <!-- OneDrive -->
                        <div class="xagio-accordion xagio-margin-bottom-medium">
                            <h3 class="xagio-accordion-title">

                                <img src="<?php echo esc_url(XAGIO_URL); ?>/assets/img/logos/onedrive-logo.webp"
                                        class="api-logo"/>

                                <?php if (!empty($xagio_tokens['onedrive'])): ?>

                                    <span class="ribbon-wrapper authorized">
                                        Authorized
                                    </span>

                                <?php else: ?>

                                    <span class="ribbon-wrapper not-authorized">
                                        Not Authorized
                                    </span>

                                <?php endif; ?>

                            </h3>
                            <div class="xagio-accordion-content">
                                <div>
                                    <div class="xagio-accordion-panel">

                                        <p class="info-paragraph">
                                            <b class="lg">How to connect OneDrive?</b>
                                            You need to authorize your OneDrive account with Xagio to be able to
                                            use OneDrive as Remote Storage method. Click on button below to be
                                            redirected to
                                            OneDrive in order to authorize Xagio.
                                        </p>

                                        <?php
                                        if (!empty($xagio_tokens['onedrive'])) {
                                            ?>
                                            <div class="xagio-flex-right">
                                                <a type="button"
                                                        class="xagio-button xagio-button-primary deauth-dropbox"
                                                        href="<?php echo esc_url(XAGIO_PANEL_URL); ?>/backup_settings/oneDriveDeAuthorize">
                                                    Deauthorize
                                                </a>
                                            </div>
                                            <?php
                                        } else {
                                            ?>
                                            <div class="xagio-flex-right">
                                                <a type="button" class="xagio-button xagio-button-primary auth-dropbox"
                                                        href="<?php echo esc_url(XAGIO_PANEL_URL); ?>/backup_settings/oneDriveAuthorize">
                                                    <i class="xagio-icon xagio-icon-hourglass"></i> Authorize Now
                                                </a>
                                            </div>
                                            <?php
                                        }
                                        ?>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amazon S3 -->
                        <div class="xagio-accordion xagio-margin-bottom-medium">

                            <?php
                            $xagio_amazon_s3_key    = $xagio_tokens['amazon']['access_key'] ?? '';
                            $xagio_amazon_s3_secret = $xagio_tokens['amazon']['secret_key'] ?? '';
                            $xagio_amazon_s3_bucket = $xagio_tokens['amazon']['bucket'] ?? '';
                            $xagio_amazon_s3_region = $xagio_tokens['amazon']['region'] ?? '';
                            ?>

                            <h3 class="xagio-accordion-title">

                                <img src="<?php echo wp_kses_post(XAGIO_URL); ?>/assets/img/logos/amazon-s3.webp"/>

                                <?php if (!empty($xagio_amazon_s3_key) && !empty($xagio_amazon_s3_secret) && !empty($xagio_amazon_s3_bucket) && !empty($xagio_amazon_s3_region)): ?>

                                    <span class="ribbon-wrapper authorized">
                                        Authorized
                                    </span>

                                <?php else: ?>

                                    <span class="ribbon-wrapper not-authorized">
                                        Not Authorized
                                    </span>

                                <?php endif; ?>

                            </h3>

                            <div class="xagio-accordion-content">
                                <div>
                                    <div class="xagio-accordion-panel">

                                        <p class="info-paragraph">
                                            <b class="lg">How to connect Amazon S3?</b>
                                            In order to use Amazon S3 Remote Storage, you need to insert your Access and
                                            Secret keys which you can obtain <a target="_blank"
                                                                                href="https://console.aws.amazon.com/iam/home?region=us-west-2#security_credential">here</a>.
                                        </p>

                                        <form class="backup-amazons3">

                                            <input type="hidden" name="action" value="xagio_save_backup_amazons3_settings"/>

                                            <div class="form-group">
                                                <label class="">Access Key</label>
                                                <input type="text" class="xagio-input-text-mini showHidePassword"
                                                       name="amazon_s3_key"
                                                       placeholder="eg. AKIAITIG7N65GQQ4W5FB"
                                                       value="<?php echo esc_attr($xagio_amazon_s3_key); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label class="">Secret Key</label>
                                                <input type="text" class="xagio-input-text-mini showHidePassword"
                                                       name="amazon_s3_secret"
                                                       placeholder="eg. h/o1OAspM1v8T5s4VAiVolNUDpc7kRxNFzu4KdBn"
                                                       value="<?php echo esc_attr($xagio_amazon_s3_secret); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label class="">Bucket Name</label>
                                                <input type="text" class="xagio-input-text-mini" name="amazon_s3_bucket"
                                                       placeholder="eg. mybucket" value="<?php echo esc_attr($xagio_amazon_s3_bucket); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label class="">Region</label>
                                                <select class="xagio-input-select xagio-input-select-gray"
                                                        name="amazon_s3_region">
                                                    <option value="us-east-1" <?php echo ($xagio_amazon_s3_region == "us-east-1") ? 'selected' : '' ?>>
                                                        US East (N. Virginia)
                                                    </option>
                                                    <option value="us-east-2" <?php echo ($xagio_amazon_s3_region == "us-east-2") ? 'selected' : '' ?>>
                                                        US East (Ohio)
                                                    </option>
                                                    <option value="us-west-1" <?php echo ($xagio_amazon_s3_region == "us-west-1") ? 'selected' : '' ?>>
                                                        US West (N. California)
                                                    </option>
                                                    <option value="us-west-2" <?php echo ($xagio_amazon_s3_region == "us-west-2") ? 'selected' : '' ?>>
                                                        US West (Oregon)
                                                    </option>
                                                    <option value="af-south-1" <?php echo ($xagio_amazon_s3_region == "af-south-1") ? 'selected' : '' ?>>
                                                        Africa (Cape Town)
                                                    </option>
                                                    <option value="ap-east-1" <?php echo ($xagio_amazon_s3_region == "ap-east-1") ? 'selected' : '' ?>>
                                                        Asia Pacific (Hong Kong)
                                                    </option>
                                                    <option value="ap-south-2" <?php echo ($xagio_amazon_s3_region == "ap-south-2") ? 'selected' : '' ?>>
                                                        Asia Pacific (Hyderabad)
                                                    </option>
                                                    <option value="ap-southeast-3" <?php echo ($xagio_amazon_s3_region == "ap-southeast-3") ? 'selected' : '' ?>>
                                                        Asia Pacific (Jakarta)
                                                    </option>
                                                    <option value="ap-southeast-4" <?php echo ($xagio_amazon_s3_region == "ap-southeast-4") ? 'selected' : '' ?>>
                                                        Asia Pacific (Melbourne)
                                                    </option>
                                                    <option value="ap-south-1" <?php echo ($xagio_amazon_s3_region == "ap-south-1") ? 'selected' : '' ?>>
                                                        Asia Pacific (Mumbai)
                                                    </option>
                                                    <option value="ap-northeast-3" <?php echo ($xagio_amazon_s3_region == "ap-northeast-3") ? 'selected' : '' ?>>
                                                        Asia Pacific (Osaka)
                                                    </option>
                                                    <option value="ap-northeast-2" <?php echo ($xagio_amazon_s3_region == "ap-northeast-2") ? 'selected' : '' ?>>
                                                        Asia Pacific (Seoul)
                                                    </option>
                                                    <option value="ap-southeast-1" <?php echo ($xagio_amazon_s3_region == "ap-southeast-1") ? 'selected' : '' ?>>
                                                        Asia Pacific (Singapore)
                                                    </option>
                                                    <option value="ap-southeast-2" <?php echo ($xagio_amazon_s3_region == "ap-southeast-2") ? 'selected' : '' ?>>
                                                        Asia Pacific (Sydney)
                                                    </option>
                                                    <option value="ap-northeast-1" <?php echo ($xagio_amazon_s3_region == "ap-northeast-1") ? 'selected' : '' ?>>
                                                        Asia Pacific (Tokyo)
                                                    </option>
                                                    <option value="ca-central-1" <?php echo ($xagio_amazon_s3_region == "ca-central-1") ? 'selected' : '' ?>>
                                                        Canada (Central)
                                                    </option>
                                                    <option value="eu-central-1" <?php echo ($xagio_amazon_s3_region == "eu-central-1") ? 'selected' : '' ?>>
                                                        Europe (Frankfurt)
                                                    </option>
                                                    <option value="eu-west-1" <?php echo ($xagio_amazon_s3_region == "eu-west-1") ? 'selected' : '' ?>>
                                                        Europe (Ireland)
                                                    </option>
                                                    <option value="eu-west-2" <?php echo ($xagio_amazon_s3_region == "eu-west-2") ? 'selected' : '' ?>>
                                                        Europe (London)
                                                    </option>
                                                    <option value="eu-south-1" <?php echo ($xagio_amazon_s3_region == "eu-south-1") ? 'selected' : '' ?>>
                                                        Europe (Milan)
                                                    </option>
                                                    <option value="eu-west-3" <?php echo ($xagio_amazon_s3_region == "eu-west-3") ? 'selected' : '' ?>>
                                                        Europe (Paris)
                                                    </option>
                                                    <option value="eu-south-2" <?php echo ($xagio_amazon_s3_region == "eu-south-2") ? 'selected' : '' ?>>
                                                        Europe (Spain)
                                                    </option>
                                                    <option value="eu-north-1" <?php echo ($xagio_amazon_s3_region == "eu-north-1") ? 'selected' : '' ?>>
                                                        Europe (Stockholm)
                                                    </option>
                                                    <option value="eu-central-2" <?php echo ($xagio_amazon_s3_region == "eu-central-2") ? 'selected' : '' ?>>
                                                        Europe (Zurich)
                                                    </option>
                                                    <option value="il-central-1" <?php echo ($xagio_amazon_s3_region == "il-central-1") ? 'selected' : '' ?>>
                                                        Israel (Tel Aviv)
                                                    </option>
                                                    <option value="me-south-1" <?php echo ($xagio_amazon_s3_region == "me-south-1") ? 'selected' : '' ?>>
                                                        Middle East (Bahrain)
                                                    </option>
                                                    <option value="me-central-1" <?php echo ($xagio_amazon_s3_region == "me-central-1") ? 'selected' : '' ?>>
                                                        Middle East (UAE)
                                                    </option>
                                                    <option value="sa-east-1" <?php echo ($xagio_amazon_s3_region == "sa-east-1") ? 'selected' : '' ?>>
                                                        South America (São Paulo)
                                                    </option>

                                                </select>
                                            </div>

                                            <div class="xagio-flex-right xagio-margin-top-medium">
                                                <button type="submit"
                                                        class="xagio-button xagio-button-primary btn-save-amazons3">
                                                    Save & Verify
                                                </button>
                                            </div>
                                        </form>

                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- XAGIO_GoogleDrive -->
                        <div class="xagio-accordion xagio-margin-bottom-medium">

                            <h3 class="xagio-accordion-title">
                                <img src="<?php echo esc_url(XAGIO_URL); ?>/assets/img/logos/GoogleDrive.webp"/>

                                <?php if (!empty($xagio_tokens['googledrive'])): ?>

                                    <span class="ribbon-wrapper authorized">
                                        Authorized
                                    </span>

                                <?php else: ?>

                                    <span class="ribbon-wrapper not-authorized">
                                        Not Authorized
                                    </span>

                                <?php endif; ?>

                            </h3>

                            <div class="xagio-accordion-content">
                                <div>
                                    <div class="xagio-accordion-panel">

                                        <p class="info-paragraph">
                                            <b class="lg">How to connect GoogleDrive?</b>
                                            You need to authorize your Google account with Xagio to be able to
                                            use Google Drive as Remote Storage method. Click on button below to be
                                            redirected to
                                            Google in order to authorize Xagio.
                                        </p>

                                        <?php
                                        if (!empty($xagio_tokens['googledrive'])) {
                                            ?>
                                            <div class="xagio-flex-right">
                                                <a type="button"
                                                        class="xagio-button xagio-button-primary deauth-googledrive"
                                                        href="<?php echo esc_url(XAGIO_PANEL_URL); ?>/backup_settings/GoogleDriveDeauthorize">
                                                    Deauthorize
                                                </a>
                                            </div>
                                            <?php
                                        } else {
                                            ?>
                                            <div class="xagio-flex-right">
                                                <a type="button"
                                                        class="xagio-button xagio-button-primary auth-googledrive"
                                                        href="<?php echo esc_url(XAGIO_PANEL_URL); ?>/backup_settings/GoogleDriveAuthorize">
                                                    <i class="xagio-icon xagio-icon-hourglass"></i> Authorize Now
                                                </a>
                                            </div>
                                            <?php
                                        }
                                        ?>

                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Dropbox -->
                        <div class="xagio-accordion">

                            <h3 class="xagio-accordion-title">

                                <img src="<?php echo esc_url(XAGIO_URL); ?>/assets/img/logos/Dropbox.webp"/>

                                <?php if (!empty($xagio_tokens['dropbox'])): ?>

                                    <span class="ribbon-wrapper authorized">
                                        Authorized
                                    </span>

                                <?php else: ?>

                                    <span class="ribbon-wrapper not-authorized">
                                        Not Authorized
                                    </span>

                                <?php endif; ?>


                            </h3>

                            <div class="xagio-accordion-content">
                                <div>
                                    <div class="xagio-accordion-panel">

                                        <p class="info-paragraph">
                                            <b class="lg">How to connect Dropbox?</b>
                                            You need to authorize your DropBox account with Xagio to be able to
                                            use Dropbox as Remote Storage method. Click on button below to be redirected
                                            to
                                            Dropbox in order to authorize Xagio.
                                        </p>

                                        <?php
                                        if (!empty($xagio_tokens['dropbox'])) {
                                            ?>
                                            <div class="xagio-flex-right">
                                                <a type="button"
                                                        class="xagio-button xagio-button-primary deauth-dropbox"
                                                        href="<?php echo esc_url(XAGIO_PANEL_URL); ?>/backup_settings/dropBoxDeauthorize">
                                                    Deauthorize
                                                </a>
                                            </div>
                                            <?php
                                        } else {
                                            ?>
                                            <div class="xagio-flex-right">
                                                <a type="button" class="xagio-button xagio-button-primary auth-dropbox"
                                                        href="<?php echo esc_url(XAGIO_PANEL_URL); ?>/backup_settings/dropBoxAuthorize">
                                                    <i class="xagio-icon xagio-icon-hourglass"></i> Authorize Now
                                                </a>
                                            </div>
                                            <?php
                                        }
                                        ?>

                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <div>

                    <div class="xagio-panel backup-settings">
                        <h3 class="xagio-panel-title">Automated Backup Settings</h3>
                        <p class="xagio-text-info">
                            Set up your storage location, time schedule, and the number of backup copies to store. The more regularly you update your site, the more frequently you need updates and the more copies you should keep.
                        </p>

                        <form class="save-settings">

                            <input type="hidden" name="action" value="xagio_save_backup_settings"/>

                            <select data-selected="<?php echo esc_attr($xagio_location); ?>" name="location"
                                    class="xagio-input-select xagio-input-select-gray" required>
                                <option value="">Save to...</option>
                                <option value="none">Backup Location</option>
                                <optgroup label="Remote Locations">
                                    <option value="onedrive">OneDrive</option>
                                    <option value="amazons3">Amazon S3</option>
                                    <option value="googledrive">Google Drive</option>
                                    <option value="dropbox">Dropbox</option>
                                </optgroup>
                            </select>

                            <div class="xagio-2-column-grid xagio-margin-top-medium xagio-margin-bottom-medium">
                                <div>
                                    <select data-selected="<?php echo esc_attr($xagio_frequency); ?>" name="frequency"
                                            class="xagio-input-select xagio-input-select-gray"
                                            required>
                                        <option value="">Backup Frequency</option>
                                        <optgroup label="Periods">
                                            <option value="never">Never</option>
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly">Monthly</option>
                                        </optgroup>
                                    </select>
                                </div>
                                <div>
                                    <select data-selected="<?php echo esc_attr($xagio_copies); ?>" name="copies"
                                            class="xagio-input-select xagio-input-select-gray" required>
                                        <option value="">Copies to keep</option>
                                        <optgroup label="Number of copies">
                                            <option value="1">1</option>
                                            <option value="5">5</option>
                                            <option value="10">10</option>
                                            <option value="15">15</option>
                                            <option value="20">20</option>
                                            <option value="50">50</option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>

                            <!-- Enable/Disable Scripts while logged in -->
                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_BACKUPS_IGNORE_DOMAINS" id="XAGIO_BACKUPS_IGNORE_DOMAINS" value="<?php echo  XAGIO_BACKUPS_IGNORE_DOMAINS ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button xagio-slider-button-settings <?php echo  XAGIO_BACKUPS_IGNORE_DOMAINS ? 'on' : ''; ?>" data-element="XAGIO_BACKUPS_IGNORE_DOMAINS"></span>
                                </div>
                                <p class="xagio-slider-label">Backups <b>Ignore</b> Domains <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="In case of using cPanel and having multiple addon domains located in the root of your main domain, you might want to check this as this will try to prevent backups from collecting those addon domain folders while backing up main domain. This is to be used ONLY on your main/root domain, it does not apply to addon domains."></i></p>
                            </div>

                            <div class="xagio-flex-right">

                                <button type="submit" class="xagio-button xagio-button-primary"><i
                                            class="xagio-icon xagio-icon-check"></i>
                                    Save Settings
                                </button>

                            </div>

                        </form>

                    </div>

                    <div class="xagio-panel xagio-margin-top-medium">
                        <h3 class="xagio-panel-title">Information</h3>
                        <p class="xagio-text-info">
                            Monitor your backup quality based on the storage integration and overall size of your website to ensure maximum safety and integrity.
                        </p>

                        <div class="info-item">
                            <?php $xagio_bs = get_option('XAGIO_BACKUP_SPEED'); ?>
                            <span class="info-title">
            Backup Grade
            <a href="#" class="xagio-circle-btn xagio-circle-btn-primary xagio-circle-btn-small xagio-margin-left-small check-backup-speed" data-xagio-tooltip="" data-xagio-tooltip-position="bottom" data-xagio-title="Refresh Grade">
                <i class="xagio-icon xagio-icon-sync"></i>
            </a>
        </span>
                            <span class="backup-grade">
            <?php
            $xagio_grade = $xagio_bs['grade'];
            for ($xagio_i = 1; $xagio_i <= 10; $xagio_i++) {
                if ($xagio_i <= $xagio_grade) {
                    echo '<span class="star">★</span>';
                } else {
                    echo '<span class="star gray">★</span>';
                }
            }
            ?>
        </span>
                            <span class="info-subtitle">
            The grade above indicates the quality of the backup process used by Xagio. If the grade is too low, there is a possibility of backups failing and not being processed correctly.
        </span>
                        </div>

                        <div class="info-item xagio-margin-top-medium">
                            <?php $xagio_bs = get_option('XAGIO_BACKUP_SIZE'); ?>
                            <span class="info-title">
            Backup Estimated Size
            <a href="#" class="xagio-circle-btn xagio-circle-btn-primary xagio-circle-btn-small xagio-margin-left-small check-backup-size" data-xagio-tooltip="" data-xagio-tooltip-position="bottom" data-xagio-title="Refresh Estimated Size">
                <i class="xagio-icon xagio-icon-sync"></i>
            </a>
        </span>
                            <span class="backup-size"><?php echo esc_html($xagio_bs); ?> Mb</span>
                            <span class="info-subtitle">
            Websites with greater size than 1000 Mb have a chance of failing due to server or browser timeouts.
        </span>
                        </div>










                        <!-- New section for Cloud Upload Hooks information -->
                        <div class="info-item xagio-margin-top-medium">
                            <span class="info-title">Cloud Upload Hooks Status</span>
                            <br>
                            <span class="info-subtitle">
        These hooks are responsible for processing chunked file uploads via WP Cron. When a backup is in progress, tasks are scheduled to run every 10 seconds.
    </span>
                            <br><br>
                            <?php
                            // Retrieve all scheduled WP Cron jobs
                            $xagio_cron_jobs = _get_cron_array();

                            // Arrays to hold scheduled events for each provider
                            $xagio_onedrive_events   = [];
                            $xagio_googledrive_events = [];
                            $xagio_dropbox_events    = [];
                            $xagio_s3_events         = [];

                            if ( ! empty( $xagio_cron_jobs ) ) {
                                foreach ( $xagio_cron_jobs as $xagio_timestamp => $xagio_cron ) {
                                    foreach ( $xagio_cron as $xagio_hook_name => $xagio_cron_details ) {
                                        foreach ( $xagio_cron_details as $xagio_event ) {
                                            $xagio_args = ! empty( $xagio_event['args'] ) ? json_encode( $xagio_event['args'] ) : 'No arguments';
                                            switch ( $xagio_hook_name ) {
                                                case 'XAGIO_OnedriveClient_Process_Upload':
                                                    $xagio_onedrive_events[] = [ 'timestamp' => $xagio_timestamp, 'args' => $xagio_args ];
                                                    break;
                                                case 'XAGIO_GoogleDrive_Process_Upload':
                                                    $xagio_googledrive_events[] = [ 'timestamp' => $xagio_timestamp, 'args' => $xagio_args ];
                                                    break;
                                                case 'XAGIO_Dropbox_Process_Upload':
                                                    $xagio_dropbox_events[] = [ 'timestamp' => $xagio_timestamp, 'args' => $xagio_args ];
                                                    break;
                                                case 'XAGIO_S3_Process_Upload':
                                                    $xagio_s3_events[] = [ 'timestamp' => $xagio_timestamp, 'args' => $xagio_args ];
                                                    break;
                                            }
                                        }
                                    }
                                }
                            }

                            /**
                             * Renders the status block for a given provider.
                             *
                             * @param string $provider The provider name.
                             * @param array  $events   The list of scheduled events.
                             */
                            function xagio_render_events( $provider, $events ) {
                                if ( empty( $events )) {
                                    return;
                                }

                                echo '<div class="cloud-provider">';
                                echo '<h4 style="margin-top:0;">' . esc_html( $provider ) . '</h4>';
                                echo '<p>Status: <span class="status running">Running</span></p>';

                                if ( ! empty( $events ) ) {
                                    echo '<ul style="list-style:disc; padding-left:20px;">';
                                    foreach ( $events as $xagio_event ) {
                                        echo '<li>Next run: ' . wp_kses_post(date_i18n( 'Y-m-d H:i:s', $xagio_event['timestamp'] )) . '<br>';
                                        echo '<small>Arguments: ' . esc_html( $xagio_event['args'] ) . '</small></li>';
                                    }
                                    echo '</ul>';
                                } else {
                                    echo '<p>No scheduled backup tasks.</p>';
                                }
                                echo '</div>';
                            }

                            // Render status cards for each cloud provider
                            xagio_render_events( 'OneDrive', $xagio_onedrive_events );
                            xagio_render_events( 'Google Drive', $xagio_googledrive_events );
                            xagio_render_events( 'Dropbox', $xagio_dropbox_events );
                            xagio_render_events( 'Amazon S3', $xagio_s3_events );

                            ?>

                        </div>















                    </div>


                </div>

            </div>
        </div>
    </div>

    <div class="backup-template xagio-hidden">

        <div class="backup-description">
            <h3 class="backup-type">...</h3>
            <h3 class="backup-name">...</h3>
        </div>

        <div class="backup-buttons">

            <button class="xagio-button xagio-button-small xagio-button-primary download-remote-backup"
                    type="button" data-id=""
                    data-xagio-tooltip data-xagio-title="Download this backup"><i class="xagio-icon xagio-icon-download"></i>
            </button>

            <button class="xagio-button xagio-button-small xagio-button-danger remove-remote-backup"
                    type="button" data-id="" data-xagio-tooltip data-xagio-title="Remove this backup"><i class="xagio-icon xagio-icon-delete"></i>
            </button>

        </div>

    </div>

</div> <!-- .wrap -->

