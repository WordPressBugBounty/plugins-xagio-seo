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

$tokens    = get_option('XAGIO_BACKUP_SETTINGS');
$location  = get_option("XAGIO_BACKUP_LOCATION");
$copies    = get_option("XAGIO_BACKUP_LIMIT");
$frequency = get_option("XAGIO_BACKUP_DATE");

$MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');
?>
<div class="xagio-main-header">
    <img class="logo-image repo-xagio" src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        Backup & Restore
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
                        $backups  = glob(XAGIO_PATH . '/backups/*_full_*.zip');
                        if (!empty($backups)):

                            // Sort backups by date created using usort
                            usort($backups, function ($a, $b) {
                                return filectime($b) - filectime($a);
                            });

                            ?><?php foreach ($backups as $backup):
                            $date = gmdate("F d Y H:i:s", filectime($backup));
                            $name = basename($backup);
                            $url  = XAGIO_URL . 'backups/' . $name;
                            ?>
                            <div class="backup-template">

                                <div class="backup-description">
                                    <h3 class="backup-type full">Full</h3>
                                    <h3 class="backup-name"><?php echo esc_html($date); ?></h3>
                                </div>

                                <div class="backup-buttons">

                                    <button class="xagio-button xagio-button-small xagio-button-primary download-backup"
                                            type="button" data-url="<?php echo esc_attr($url); ?>"
                                            data-xagio-tooltip data-xagio-title="Download this backup"><i class="xagio-icon xagio-icon-download"></i>
                                    </button>

                                    <button class="xagio-button xagio-button-small xagio-button-alternative restore-backup"
                                            type="button" data-url="<?php echo esc_attr($url); ?>"
                                            data-xagio-tooltip data-xagio-title="Restore this backup"><i class="xagio-icon xagio-icon-upload"></i>
                                    </button>

                                    <button class="xagio-button xagio-button-small xagio-button-danger remove-backup"
                                            type="button" data-name="<?php echo esc_attr($name); ?>" data-xagio-tooltip data-xagio-title="Remove this backup"><i class="xagio-icon xagio-icon-delete"></i>
                                    </button>

                                </div>

                            </div>
                        <?php
                        endforeach;
                        endif;

                        $backups      = glob(XAGIO_PATH . '/backups/*_files_*.zip');
                        if (!empty($backups)):

                            // Sort backups by date created using usort
                            usort($backups, function ($a, $b) {
                                return filectime($b) - filectime($a);
                            });

                            foreach ($backups as $backup):
                                $date = gmdate("F d Y H:i:s", filectime($backup));
                                $name = basename($backup);
                                $url  = XAGIO_URL . 'backups/' . $name;

                                ?>

                                <div class="backup-template">

                                    <div class="backup-description">
                                        <h3 class="backup-type files">Files</h3>
                                        <h3 class="backup-name"><?php echo esc_html($date); ?></h3>
                                    </div>

                                    <div class="backup-buttons">

                                        <button class="xagio-button xagio-button-small xagio-button-primary download-backup"
                                                type="button" data-url="<?php echo esc_attr($url); ?>"
                                                data-xagio-tooltip data-xagio-title="Download this backup"><i
                                                    class="xagio-icon xagio-icon-download"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-alternative restore-backup"
                                                type="button" data-url="<?php echo esc_attr($url); ?>"
                                                data-xagio-tooltip data-xagio-title="Restore this backup"><i
                                                    class="xagio-icon xagio-icon-upload"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-danger remove-backup"
                                                type="button" data-name="<?php echo esc_attr($name); ?>" data-xagio-tooltip data-xagio-title="Remove this backup"><i class="xagio-icon xagio-icon-delete"></i>
                                        </button>

                                    </div>

                                </div>

                            <?php
                            endforeach;
                        endif;

                        $backups      = glob(XAGIO_PATH . '/backups/*_mysql_*.zip');
                        if (!empty($backups)):

                            // Sort backups by date created using usort
                            usort($backups, function ($a, $b) {
                                return filectime($b) - filectime($a);
                            });

                            foreach ($backups as $backup):
                                $date = gmdate("F d Y H:i:s", filectime($backup));
                                $name = basename($backup);
                                $url  = XAGIO_URL . 'backups/' . $name;
                                ?>
                                <div class="backup-template">

                                    <div class="backup-description">
                                        <h3 class="backup-type mysql">Database</h3>
                                        <h3 class="backup-name"><?php echo esc_html($date); ?></h3>
                                    </div>

                                    <div class="backup-buttons">

                                        <button class="xagio-button xagio-button-small xagio-button-primary download-backup"
                                                type="button" data-url="<?php echo esc_attr($url); ?>"
                                                data-xagio-tooltip data-xagio-title="Download this backup"><i
                                                    class="xagio-icon xagio-icon-download"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-alternative restore-backup"
                                                type="button" data-url="<?php echo esc_attr($url); ?>"
                                                data-xagio-tooltip data-xagio-title="Restore this backup"><i
                                                    class="xagio-icon xagio-icon-upload"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-danger remove-backup"
                                                type="button" data-name="<?php echo esc_attr($name); ?>" data-xagio-tooltip data-xagio-title="Remove this backup"><i class="xagio-icon xagio-icon-delete"></i>
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

                        <select data-selected="<?php echo esc_attr($location); ?>"
                                class="xagio-input-select xagio-input-select-gray view-remote-backups">
                            <option value="none">View Backups from...</option>
                            <optgroup label="Remote Locations">
                                <option value="onedrive">OneDrive</option>
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
                        $backups  = glob(XAGIO_PATH . '/backups/*_full_*.zip');
                        if (!empty($backups)):

                            // Sort backups by date created using usort
                            usort($backups, function ($a, $b) {
                                return filectime($b) - filectime($a);
                            });

                            ?><?php foreach ($backups as $backup):
                            $date = gmdate("F d Y H:i:s", filectime($backup));
                            $name = basename($backup);
                            $url  = XAGIO_URL . 'backups/' . $name;
                            ?>
                            <div class="backup-template">

                                <div class="backup-description">
                                    <h3 class="backup-type full">Full</h3>
                                    <h3 class="backup-name"><?php echo esc_html($date); ?></h3>
                                </div>

                                <div class="backup-buttons">

                                    <button class="xagio-button xagio-button-small xagio-button-primary download-backup"
                                            type="button" data-url="<?php echo esc_attr($url); ?>"
                                            data-xagio-tooltip data-xagio-title="Download this backup"><i class="xagio-icon xagio-icon-download"></i>
                                    </button>

                                    <button class="xagio-button xagio-button-small xagio-button-alternative restore-backup"
                                            type="button" data-url="<?php echo esc_attr($url); ?>"
                                            data-xagio-tooltip data-xagio-title="Restore this backup"><i class="xagio-icon xagio-icon-upload"></i>
                                    </button>

                                    <button class="xagio-button xagio-button-small xagio-button-danger remove-backup"
                                            type="button" data-name="<?php echo esc_attr($name); ?>" data-xagio-tooltip data-xagio-title="Remove this backup"><i class="xagio-icon xagio-icon-delete"></i>
                                    </button>

                                </div>

                            </div>
                        <?php
                        endforeach;
                        endif;

                        $backups      = glob(XAGIO_PATH . '/backups/*_files_*.zip');
                        if (!empty($backups)):

                            // Sort backups by date created using usort
                            usort($backups, function ($a, $b) {
                                return filectime($b) - filectime($a);
                            });

                            foreach ($backups as $backup):
                                $date = gmdate("F d Y H:i:s", filectime($backup));
                                $name = basename($backup);
                                $url  = XAGIO_URL . 'backups/' . $name;

                                ?>

                                <div class="backup-template">

                                    <div class="backup-description">
                                        <h3 class="backup-type files">Files</h3>
                                        <h3 class="backup-name"><?php echo esc_html($date); ?></h3>
                                    </div>

                                    <div class="backup-buttons">

                                        <button class="xagio-button xagio-button-small xagio-button-primary download-backup"
                                                type="button" data-url="<?php echo esc_attr($url); ?>"
                                                data-xagio-tooltip data-xagio-title="Download this backup"><i
                                                    class="xagio-icon xagio-icon-download"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-alternative restore-backup"
                                                type="button" data-url="<?php echo esc_attr($url); ?>"
                                                data-xagio-tooltip data-xagio-title="Restore this backup"><i
                                                    class="xagio-icon xagio-icon-upload"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-danger remove-backup"
                                                type="button" data-name="<?php echo esc_attr($name); ?>" data-xagio-tooltip data-xagio-title="Remove this backup"><i class="xagio-icon xagio-icon-delete"></i>
                                        </button>

                                    </div>

                                </div>

                            <?php
                            endforeach;
                        endif;

                        $backups      = glob(XAGIO_PATH . '/backups/*_mysql_*.zip');
                        if (!empty($backups)):

                            // Sort backups by date created using usort
                            usort($backups, function ($a, $b) {
                                return filectime($b) - filectime($a);
                            });

                            foreach ($backups as $backup):
                                $date = gmdate("F d Y H:i:s", filectime($backup));
                                $name = basename($backup);
                                $url  = XAGIO_URL . 'backups/' . $name;
                                ?>
                                <div class="backup-template">

                                    <div class="backup-description">
                                        <h3 class="backup-type mysql">Database</h3>
                                        <h3 class="backup-name"><?php echo esc_html($date); ?></h3>
                                    </div>

                                    <div class="backup-buttons">

                                        <button class="xagio-button xagio-button-small xagio-button-primary download-backup"
                                                type="button" data-url="<?php echo esc_attr($url); ?>"
                                                data-xagio-tooltip data-xagio-title="Download this backup"><i
                                                    class="xagio-icon xagio-icon-download"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-alternative restore-backup"
                                                type="button" data-url="<?php echo esc_attr($url); ?>"
                                                data-xagio-tooltip data-xagio-title="Restore this backup"><i
                                                    class="xagio-icon xagio-icon-upload"></i>
                                        </button>

                                        <button class="xagio-button xagio-button-small xagio-button-danger remove-backup"
                                                type="button" data-name="<?php echo esc_attr($name); ?>" data-xagio-tooltip data-xagio-title="Remove this backup"><i class="xagio-icon xagio-icon-delete"></i>
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

                                <?php if (!empty($tokens['onedrive'])): ?>

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
                                        if (!empty($tokens['onedrive'])) {
                                            ?>
                                            <div class="xagio-flex-right">
                                                <a type="button"
                                                        class="xagio-button xagio-button-primary deauth-dropbox"
                                                        href="<?php echo esc_url(XAGIO_PANEL_URL); ?>/settings/oneDriveDeAuthorize">
                                                    Deauthorize
                                                </a>
                                            </div>
                                            <?php
                                        } else {
                                            ?>
                                            <div class="xagio-flex-right">
                                                <a type="button" class="xagio-button xagio-button-primary auth-dropbox"
                                                        href="<?php echo esc_url(XAGIO_PANEL_URL); ?>/settings/oneDriveAuthorize">
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

                        <!-- XAGIO_GoogleDrive -->
                        <div class="xagio-accordion xagio-margin-bottom-medium">

                            <h3 class="xagio-accordion-title">
                                <img src="<?php echo esc_url(XAGIO_URL); ?>/assets/img/logos/GoogleDrive.webp"/>

                                <?php if (!empty($tokens['googledrive'])): ?>

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
                                        if (!empty($tokens['googledrive'])) {
                                            ?>
                                            <div class="xagio-flex-right">
                                                <a type="button"
                                                        class="xagio-button xagio-button-primary deauth-googledrive"
                                                        href="<?php echo esc_url(XAGIO_PANEL_URL); ?>/settings/GoogleDriveDeauthorize">
                                                    Deauthorize
                                                </a>
                                            </div>
                                            <?php
                                        } else {
                                            ?>
                                            <div class="xagio-flex-right">
                                                <a type="button"
                                                        class="xagio-button xagio-button-primary auth-googledrive"
                                                        href="<?php echo esc_url(XAGIO_PANEL_URL); ?>/settings/GoogleDriveAuthorize">
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

                                <?php if (!empty($tokens['dropbox'])): ?>

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
                                        if (!empty($tokens['dropbox'])) {
                                            ?>
                                            <div class="xagio-flex-right">
                                                <a type="button"
                                                        class="xagio-button xagio-button-primary deauth-dropbox"
                                                        href="<?php echo esc_url(XAGIO_PANEL_URL); ?>/settings/dropBoxDeauthorize">
                                                    Deauthorize
                                                </a>
                                            </div>
                                            <?php
                                        } else {
                                            ?>
                                            <div class="xagio-flex-right">
                                                <a type="button" class="xagio-button xagio-button-primary auth-dropbox"
                                                        href="<?php echo esc_url(XAGIO_PANEL_URL); ?>/settings/dropBoxAuthorize">
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

                            <select data-selected="<?php echo esc_attr($location); ?>" name="location"
                                    class="xagio-input-select xagio-input-select-gray" required>
                                <option value="">Save to...</option>
                                <option value="none">Backup Location</option>
                                <optgroup label="Remote Locations">
                                    <option value="onedrive">OneDrive</option>
                                    <option value="googledrive">Google Drive</option>
                                    <option value="dropbox">Dropbox</option>
                                </optgroup>
                            </select>

                            <div class="xagio-2-column-grid xagio-margin-top-medium xagio-margin-bottom-medium">
                                <div>
                                    <select data-selected="<?php echo esc_attr($frequency); ?>" name="frequency"
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
                                    <select data-selected="<?php echo esc_attr($copies); ?>" name="copies"
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
                            <?php $bs = get_option('XAGIO_BACKUP_SPEED'); ?>
                            <span class="info-title">Backup Grade <a href="#" class="xagio-circle-btn xagio-circle-btn-primary xagio-circle-btn-small xagio-margin-left-small check-backup-speed" data-xagio-tooltip="" data-xagio-tooltip-position="bottom" data-xagio-title="Refresh Grade"><i class="xagio-icon xagio-icon-sync"></i></a></span>
                            <span class="backup-grade">
            <?php
            $grade = $bs['grade'];
            for ($i = 1; $i <= 10; $i++) {
                if ($i <= $grade) {
                    echo '<span class="star">★</span>';
                } else {
                    echo '<span class="star gray">★</span>';
                }
            }
            ?>
        </span>
                            <span class="info-subtitle">The grade above indicates the quality of the backup process used by Xagio. If the grade is too low, there is a possibility of backups failing and not being processed correctly.</span>
                        </div>

                        <div class="info-item xagio-margin-top-medium">
                            <?php $bs = get_option('XAGIO_BACKUP_SIZE'); ?>
                            <span class="info-title">Backup Estimated Size <a href="#" class="xagio-circle-btn xagio-circle-btn-primary xagio-circle-btn-small xagio-margin-left-small check-backup-size" data-xagio-tooltip="" data-xagio-tooltip-position="bottom" data-xagio-title="Refresh Estimated Size"><i class="xagio-icon xagio-icon-sync"></i></a></span>
                            <span class="backup-size"><?php echo esc_html($bs); ?> Mb</span>

                            <span class="info-subtitle">Websites with greater size than 1000 Mb have a chance of failing due to server or browser timeouts.</span>
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

