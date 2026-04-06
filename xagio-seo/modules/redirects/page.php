<?php
/**
 * Type: SUBMENU
 * Page_Title: 301 & 404
 * Menu_Title: 301 & 404
 * Capability: manage_options
 * Slug: xagio-redirects
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: xagio_select2,media-upload,thickbox,xagio_ajaxq,xagio_datatables,xagio_redirects
 * Css: xagio_select2,thickbox,xagio_redirects,xagio_datatables
 * Position: 7
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$XAGIO_MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');

// Get all post types dynamically
$xagio_post_types = get_post_types(['public' => true], 'names');
// Get all posts, pages, and CPTs
$xagio_all_posts = get_posts(['post_type' => $xagio_post_types, 'posts_per_page' => -1]);

$xagio_grouped_posts = [];
foreach ($xagio_all_posts as $post) {
    if ($post->post_title) {
        $xagio_grouped_posts[$post->post_type][] = $post;
    }
}

?>
<div class="xagio-main-header xagio-main-header-big-gaps">
    <img class="logo-image repo-xagio" src="<?php echo   esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        301 & 404 Management
    </h2>
    <div class="xagio-header-actions">
        <button class="xagio-button xagio-button-primary add-new-redirect"><i class="xagio-icon xagio-icon-plus"></i> Add New Redirect</button>
        <button class="xagio-button xagio-button-primary" id="csv_file_modal"><i class="xagio-icon xagio-icon-upload"></i> Upload CSV File</button>
        <?php if(isset($XAGIO_MEMBERSHIP_INFO["membership"]) && $XAGIO_MEMBERSHIP_INFO["membership"] === "Xagio AI Free") { ?>
            <a href="https://xagio.com/?goto=wppremfeatures" target="_blank" class="xagio-button xagio-button-secondary xagio-button-premium-button">
                See Xagio Premium Features
            </a>
        <?php } ?>
    </div>
</div>

<div class="xagio-content-wrapper">

    <div class="xagio-accordion xagio-margin-bottom-large">
        <h3 class="xagio-accordion-title">
            <i class="xagio-icon xagio-icon-info"></i>
            <span>Here you can manage your website 301 redirects and monitor 404 pages.</span>
            <i class="xagio-icon xagio-icon-arrow-down"></i>
        </h3>
        <div class="xagio-accordion-content">
            <div>
                <div class="xagio-accordion-panel"></div>
            </div>
        </div>
    </div>

    <ul class="xagio-tab">
        <li class="xagio-tab-active"><a href="">301 Redirects</a></li>
        <li><a href="">404 Monitor</a></li>
        <li><a href="">Settings</a></li>
    </ul>
    <div class="xagio-tab-content-holder">
        <div class="xagio-tab-content">
            <div class="xagio-panel">
                <h5 class="xagio-panel-title xagio-flex-row">
                    <div>
                        <span class="total-number-of-redirects"></span> Redirects
                    </div>

                    <div class="xagio-flex-right xagio-flex-gap-small">
                        <button type="button" class="xagio-button xagio-button-primary remove-selected-redirects" style="display: none;"><i class="xagio-icon xagio-icon-delete"></i> <span>Remove Selected ( <span class="selected-redirects"></span> )</span></button>
                        <button type="button" class="xagio-button xagio-button-primary remove-all-redirects"><i class="xagio-icon xagio-icon-delete"></i> Remove All</button>
                    </div>
                </h5>

                <div class="xagio-table-responsive">
                    <table class="xagio-table table-redirects">
                        <thead>
                        <tr>
                            <th style="width: 30px">
                                <input type="checkbox" class="xagio-input-checkbox select-all-redirects">
                            </th>
                            <th style="width: 20%;">URL</th>
                            <th>Redirects to</th>
                            <th style="width: 20%" >Date</th>
                            <th style="width: 20%" class="xagio-text-center">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="5" class="xagio-text-center">Can't find any active redirects.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
        <div class="xagio-tab-content">
            <div class="xagio-panel">
                <h5 class="xagio-panel-title xagio-flex-row">
                    <div>
                        <span class="total-number-of-logs"></span> Logs
                    </div>

                    <div class="xagio-flex-right xagio-flex-gap-small">
                        <button type="button" class="xagio-button xagio-button-primary retrieve-metrics" style="display:none;"><i class="xagio-icon xagio-icon-magnifying-glass-chart"></i> <span>Track Referers (<span class="selected-refs-count"></span>)</span></button>
                        <button type="button" class="xagio-button xagio-button-primary remove-selected-log404" style="display:none;"><i class="xagio-icon xagio-icon-delete"></i> <span>Remove Selected (<span class="selected-logs-count"></span>)</span></button>
                        <button type="button" title="Export 404s Log" class="xagio-button xagio-button-primary export_404s_log"><i class="xagio-icon xagio-icon-download"></i> Export Logs</button>
                        <button type="button" class="xagio-button xagio-button-primary clear-log404"><i class="xagio-icon xagio-icon-delete"></i> Clear Logs</button>
                    </div>
                </h5>

                <div class="xagio-table-responsive">
                    <table class="xagio-table logTable">
                        <thead>
                        <tr>
                            <th style="width: 20px;" class="check-column chkLogCol xagio-text-center"><input type="checkbox" class="xagio-input-checkbox select-all-log404"></th>
                            <th style="width: 50px;" class="column-hits xagio-text-center">Hits</th>
                            <th style="width: 300px;" class="column-url">404 URL</th>
                            <th style="width: 90px;" class="column-last-hit xagio-text-center">Last Hit</th>
                            <th style="width: 100px;" class="column-ip xagio-text-center">IP Addresses</th>
                            <th style="width: 70px;" class="column-referers xagio-text-center">Referers</th>
                            <th style="width: 100px;" class="column-agent xagio-text-center">User Agents</th>
                            <th style="width: 100px;" class="column-action xagio-text-center">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="8">Can't find any active logs.</td>
                        </tr>
                        </tbody>
                        <tfoot>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>
        <div class="xagio-tab-content">
            <div class="xagio-panel">

                <h5 class="xagio-panel-title xagio-flex-row xagio-margin-bottom-large">
                    Settings
                </h5>

                <form class="frmLogSettings">
                    <input type="hidden" name="action" value="xagio_log_404s_settings"/>
                    <?php wp_nonce_field('xagio_log_404s_settings', '_wpnonce'); ?>

                    <div class="xagio-2-column-grid">
                        <div class="xagio-column-1 xagio-padding-right-medium xagio-border-right">
                            <!-- Enable/Disable 404s log setting -->
                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_DISABLE_404_LOGS" id="XAGIO_DISABLE_404_LOGS" value="<?php echo  (XAGIO_DISABLE_404_LOGS == TRUE) ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button xagio-slider-save-logs <?php echo  (XAGIO_DISABLE_404_LOGS == TRUE) ? 'on' : ''; ?>" data-element="XAGIO_DISABLE_404_LOGS"></span>
                                </div>
                                <p class="xagio-slider-label">Disable 404's Logs <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will enabled/disable track of 404s log monitor."></i></p>
                            </div>

                            <!-- Enable/Disable 404s spider log -->
                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_ENABLE_SPIDER_404" id="XAGIO_ENABLE_SPIDER_404" value="<?php echo  (XAGIO_ENABLE_SPIDER_404 == TRUE) ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button xagio-slider-save-logs <?php echo  (XAGIO_ENABLE_SPIDER_404 == TRUE) ? 'on' : ''; ?>" data-element="XAGIO_ENABLE_SPIDER_404"></span>
                                </div>
                                <p class="xagio-slider-label">Log 404's From Spider Bot <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will enabled/disable track of 404s generated by spider visits."></i></p>
                            </div>
                        </div>
                        <div class="xagio-column-2">
                            <!-- Enable/Disable 404s with referring URLs -->
                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_ENABLE_404_REF_URL" id="XAGIO_ENABLE_404_REF_URL" value="<?php echo  (XAGIO_ENABLE_404_REF_URL == TRUE) ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button xagio-slider-save-logs <?php echo  (XAGIO_ENABLE_404_REF_URL == TRUE) ? 'on' : ''; ?>" data-element="XAGIO_ENABLE_404_REF_URL"></span>
                                </div>
                                <p class="xagio-slider-label">Log Only 404's With Referring URLs <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will enabled/disable track of 404s URLs with referring URLs Only."></i></p>
                            </div>

                            <?php if (class_exists('XAGIO_MODEL_REDIRECTS')): ?>

                                <!-- Disable 301 Redirects -->
                                <div class="xagio-slider-container">
                                    <input type="hidden" name="XAGIO_DISABLE_AUTOMATIC_REDIRECTS" id="XAGIO_DISABLE_AUTOMATIC_REDIRECTS" value="<?php echo  XAGIO_DISABLE_AUTOMATIC_REDIRECTS ? 1 : 0; ?>"/>
                                    <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button xagio-slider-save-logs <?php echo  XAGIO_DISABLE_AUTOMATIC_REDIRECTS ? 'on' : ''; ?>" data-element="XAGIO_DISABLE_AUTOMATIC_REDIRECTS"></span>
                                    </div>
                                    <p class="xagio-slider-label">Disable Automatic Redirects <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will disable automatic generation of 301 redirects that are made when you change the URL of an existing page/post."></i></p>
                                </div>

                            <?php endif; ?>
                        </div>
                    </div>


                    <div class="xagio-2-column-grid xagio-margin-top-medium">
                        <div class="xagio-column-1 xagio-padding-right-medium">
                            <!-- Set global 404 to 301 redirections -->
                            <?php $xagio_prsGlobal404RedirectUrl = get_option('XAGIO_GLOBAL_404_REDIRECTION_URL'); ?>
                            <h3 class="pop">Global 301 Redirect URL <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will allow to redirect all 404 URLs to this 301 URL (If not added redirect in 301 redirect section) but track of 404s log will not disabled. (DANGER: Creating invalid redirects may result in breaking of your website)"></i></h3>
                            <input type="url" class="xagio-input-text-mini" name="XAGIO_GLOBAL_404_REDIRECTION_URL" placeholder="http://testsite.com" value="<?php echo  $xagio_prsGlobal404RedirectUrl ? esc_attr($xagio_prsGlobal404RedirectUrl) : '' ?>"/>
                        </div>
                        <div class="xagio-column-2">
                            <!-- Select maximum log limit -->
                            <?php $xagio_prsMaxLogLimit = get_option('XAGIO_MAX_LOG_LIMIT'); ?>
                            <h3 class="pop">Maximum Log Entries <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will allow to choose amount of fresh logs to keep before deleting oldest."></i></h3>
                            <input type="text" class="xagio-input-text-mini" id="XAGIO_MAX_LOG_LIMIT" name="XAGIO_MAX_LOG_LIMIT" placeholder="Set maximum log limit" value="<?php echo  $xagio_prsMaxLogLimit ? esc_attr($xagio_prsMaxLogLimit) : '' ?>"/>
                        </div>
                    </div>


                    <!-- Ignored URLs -->
                    <?php $xagio_ignoredUrls = get_option('XAGIO_IGNORE_404_URLS');
                    $xagio_ignoredUrls = implode("\n", $xagio_ignoredUrls); ?>
                    <div class="m-t-20">
                        <h3 class="pop">URLs to Ignore <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will allow users to filter out vists to specific filetypes or paths. Insert URLs separated by a new line. (e.g. */xmlrpc.php , *.png)"></i></h3>
                        <textarea id="ignored-urls-list" name="ignored-urls-list" rows="6" placeholder="e.g. */xmlrpc.php OR */xmlrpc.php/" class="xagio-input-textarea"><?php echo  $xagio_ignoredUrls ? esc_textarea($xagio_ignoredUrls) : ''; ?></textarea>
                    </div>

                    <div class="xagio-flex-right xagio-margin-top-large">
                        <button type="submit" class="xagio-button xagio-button-primary btn-save-changes"><i class="xagio-icon xagio-icon-check"></i> Save Changes</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div>


<!-- Add Redirect Modal -->
<dialog id="addRedirectModal" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-plus"></i> Add Redirect</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <label for="redirect_select" class="modal-label">Select a page/post from the dropdown: </label>

        <select id="redirect_select">
            <option></option>
                <?php foreach ($xagio_grouped_posts as $post_type => $posts) { ?>
                    <optgroup label="<?php echo esc_html(ucfirst($post_type)) ?>">
                        <?php foreach ($posts as $post) { ?>
                            <option value="<?php echo esc_attr($post->post_name); ?>"><?php echo esc_html($post->post_title); ?></option>
                        <?php } ?>
                    </optgroup>
                <?php } ?>
        </select>

        <div class="xagio-margin-top-small">
            <label for="old_url" class="modal-label">or add url manually (use the /oldurl/ format)</label>
            <input type="text" class="xagio-input-text-mini" id="old_url" placeholder="e.g. /oldurl/ ">
        </div>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
            <button type="button" class="xagio-button xagio-button-primary redirect-next-step"><i class="xagio-icon xagio-icon-check"></i> Continue</button>
        </div>
    </div>
</dialog>

<!-- Add Redirect To Modal -->
<dialog id="addRedirectToModal" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-info"></i> Confirm New URL:</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>

    <div class="xagio-modal-body">
        <label for="redirect-to-select" class="modal-label">Select a page/post from the dropdown: </label>
        <select id="redirect-to-select">
            <option></option>
            <?php foreach ($xagio_grouped_posts as $post_type => $posts) { ?>
                <optgroup label="<?php echo esc_html(ucfirst($post_type)) ?>">
                    <?php foreach ($posts as $post) { ?>
                        <option value="<?php echo esc_attr($post->post_name); ?>"><?php echo esc_html($post->post_title); ?></option>
                    <?php } ?>
                </optgroup>
            <?php } ?>
        </select>

        <div class="xagio-margin-top-small">
            <label for="redirect-to-input" class="modal-label">or edit url manually (use the /oldurl/ format)</label>
            <input type="text" class="xagio-input-text-mini" id="redirect-to-input" placeholder="e.g. oldurl ">
        </div>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
            <button type="button" class="xagio-button xagio-button-primary submit-redirect"><i class="xagio-icon xagio-icon-check"></i> Continue</button>
        </div>
    </div>
</dialog>

<!-- Edit Redirect Modal -->
<dialog id="editRedirectModal" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-edit"></i> Edit Redirect: <span></span></h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <label for="edit_redirect_select" class="modal-label">Select a page/post from the dropdown: </label>

        <select id="edit_redirect_select">
            <option></option>
            <?php foreach ($xagio_grouped_posts as $post_type => $posts) { ?>
                <optgroup label="<?php echo esc_html(ucfirst($post_type)) ?>">
                    <?php foreach ($posts as $post) { ?>
                        <option value="<?php echo esc_attr($post->post_name); ?>"><?php echo esc_html($post->post_title); ?></option>
                    <?php } ?>
                </optgroup>
            <?php } ?>
        </select>

        <div class="xagio-margin-top-small">
            <label for="edit_old_url" class="modal-label">or edit url manually</label>
            <input type="text" class="xagio-input-text-mini" id="edit_old_url" placeholder="e.g. oldurl ">
        </div>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
            <button type="button" class="xagio-button xagio-button-primary edit-redirect-next-step"><i class="xagio-icon xagio-icon-check"></i> Continue</button>
        </div>
    </div>
</dialog>

<!-- Redirect To Modal -->
<dialog id="redirectToModal" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-info"></i> Confirm Editing URL: <span></span></h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>

    <div class="xagio-modal-body">
        <label for="editing-new-url" class="modal-label">Select a page/post from the dropdown: </label>
        <select id="editing-new-url">
            <option></option>
            <?php foreach ($xagio_grouped_posts as $post_type => $posts) { ?>
                <optgroup label="<?php echo esc_html(ucfirst($post_type)) ?>">
                    <?php foreach ($posts as $post) { ?>
                        <option value="<?php echo esc_attr($post->post_name); ?>"><?php echo esc_html($post->post_title); ?></option>
                    <?php } ?>
                </optgroup>
            <?php } ?>
        </select>

        <div class="xagio-margin-top-small">
            <label for="edit_new_url" class="modal-label">or edit url manually</label>
            <input type="text" class="xagio-input-text-mini" id="edit_new_url" placeholder="e.g. oldurl ">
        </div>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
            <button type="button" class="xagio-button xagio-button-primary submit-edit-url"><i class="xagio-icon xagio-icon-check"></i> Continue</button>
        </div>
    </div>
</dialog>

<!-- Delete Keywords -->
<dialog id="confirmAddRedirects" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-download"></i> Continue</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <label class="modal-label">This will add all redirections from your CSV file. Continue?</label>
        <div class="xagio-flex-right xagio-flex-gap-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
            <button type="button" class="xagio-button xagio-button-primary confirm-add-redirects"><i class="xagio-icon xagio-icon-check"></i> Ok</button>
        </div>
    </div>
</dialog>

<!-- Add Bulk Redirects By CSV -->
<dialog id="csv_modal" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title">
            <i class="xagio-icon xagio-icon-upload"></i> Upload CSV
        </h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <div class="modal-label">CSV format needed to import</div>

        <table class="CSV_example_table">
            <tr>
                <th></th>
                <th>A</th>
                <th>B</th>
                <th>C</th>
                <th>D</th>
                <th>E</th>
            </tr>
            <tr>
                <td>1</td>
                <td>/oldUrl1</td>
                <td>/newurl1</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>2</td>
                <td>/oldUrl2</td>
                <td>/newurl2</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>3</td>
                <td>/oldUrl3</td>
                <td>/newur3</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>

        <div class="xagio-alert xagio-alert-primary xagio-margin-top-medium xagio-margin-bottom-medium">
            <i class="xagio-icon xagio-icon-info"></i> Need only two columns in a single row. The first column( A ) contains the <b>old-URL/ ( URL )</b> and the second column( B )
            contains the <b>new-URL/ ( Redirects to )</b>.
        </div>
        <div class="upload-btn-wrapper">
            <button class="xagio-button xagio-button-primary">Upload CSV Here</button>
            <input type="file" id="csv_file" name="csv_file"/>
        </div>

    </div>

</dialog>