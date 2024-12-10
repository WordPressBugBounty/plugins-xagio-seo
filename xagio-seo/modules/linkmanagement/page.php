<?php
/**
 * Type: SUBMENU
 * Page_Title: Link Manager
 * Menu_Title: Link Manager
 * Capability: manage_options
 * Slug: xagio-linkmanagement
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: xagio_datatables,media-upload,thickbox,xagio_google_charts,xagio_linkmanagement
 * Css: xagio_datatables,thickbox,xagio_linkmanagement
 * Position: 9
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$redirect_mask = get_option('XAGIO_REDIRECT_MASK');
$MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');
if (!$redirect_mask) $redirect_mask = 'xredirect';
?>
<div class="xagio-main-header">
    <img class="logo-image repo-xagio" src="<?php echo  esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        Link Manager
    </h2>
    <div class="xagio-header-actions">
        <button class="xagio-button xagio-button-primary create-shortcode" data-url="" data-group="Custom" data-xagio-modal="shortcodeModal"><i class="xagio-icon xagio-icon-plus"></i> Create Shortcode</button>
        <button type="button" class="xagio-button xagio-button-primary show-filters"><i class="xagio-icon xagio-icon-filter"></i> Filters</button>
        <button type="button" class="xagio-button xagio-button-primary export-links"><i class="xagio-icon xagio-icon-download"></i> Export</button>
        <button type="button" class="xagio-button xagio-button-primary" data-xagio-modal="importLinks"><i class="xagio-icon xagio-icon-upload"></i> Import</button>
        <?php if(isset($MEMBERSHIP_INFO["membership"]) && $MEMBERSHIP_INFO["membership"] === "Xagio AI Free") { ?>
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
            <span>From here you can create and manage shortcode affiliate links from different affiliate programs.</span>
            <i class="xagio-icon xagio-icon-arrow-down"></i>
        </h3>
        <div class="xagio-accordion-content">
            <div>
                <div class="xagio-accordion-panel"></div>
            </div>
        </div>
    </div>

    <div class="xagio-tab-container">
        <ul class="xagio-tab">
            <li class="xagio-tab-active"><a href="">Your Shortcodes</a></li>
            <li><a href="">Mask Settings</a></li>
        </ul>

        <div class="xagio-tab-content-holder">
            <div class="xagio-tab-content">
                <div class="shortcode-filters xagio-panel hidden">
                    <form class="filters">
                        <input type="hidden" name="page" id="page" value="1"/>
                        <input type="hidden" name="total_entries" id="total_entries" value="10"/>
                        <input type="hidden" name="group" value="all"/>

                        <div>
                            <label class="uk-form-label xagio-margin-bottom-small" for="filter_shortcode">Shortcode</label>
                            <div class="uk-form-controls">
                                <input class="xagio-input-text-mini" type="text" name="shortcode" id="filter_shortcode" placeholder="eg. my_shortcode">
                            </div>
                        </div>
                        <div>
                            <label class="uk-form-label xagio-margin-bottom-small" for="filter_title">Title</label>
                            <div class="uk-form-controls">
                                <input class="xagio-input-text-mini"  type="text" name="title" id="filter_title"
                                        placeholder="eg. mytitle">
                            </div>
                        </div>
                        <div>
                            <label class="uk-form-label xagio-margin-bottom-small" for="filter_url">URL</label>
                            <div class="uk-form-controls">
                                <input class="xagio-input-text-mini"  type="text" name="url" id="filter_url" placeholder="eg. myurl">
                            </div>
                        </div>
                        <div class="filter-toggles xagio-3-columns">
                            <div>
                                <div class="modal-label">Open in New Tab</div>
                                <div class="xagio-slider-container">
                                    <input type="hidden" name="target_blank" id="filter_target_blank"/>
                                    <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button" data-element="filter_target_blank"></span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="modal-label">Mask URL</div>
                                <div class="xagio-slider-container">
                                    <input type="hidden" name="mask" id="filter_mask"/>
                                    <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button" data-element="filter_mask"></span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="modal-label">No Follow Link</div>
                                <div class="xagio-slider-container">
                                    <input type="hidden" name="nofollow" id="filter_nofollow"/>
                                    <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button" data-element="filter_nofollow"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="xagio-button xagio-button-primary"><i class="xagio-icon xagio-icon-check"></i> Apply Filters</button>

                    </form>
                </div>

                <div class="shortcode-body">
                    <h4><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Loading ...</h4>
                </div>

                <div class="xagio-table-bottom">
                    <div class="xagio-table-length">
                        <label>Show
                            <select name="DataTables_Table_0_length">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="-1">All</option>
                            </select> entries
                        </label>
                    </div>
                    <div class="xagio-table-paginate dataTables_paginate paging_simple_numbers">
                        <a class="paginate_button previous disabled">Previous</a>
                        <span>

                        </span>
                        <a class="paginate_button next disabled">Next</a>
                    </div>
                </div>

            </div>
            <div class="xagio-tab-content">
                <div class="xagio-panel">
                    <form id="shortcode_setup">
                        <input type="hidden" name="action" value="xagio_save_shortcode_setup">
                        <div class="xagio-alert xagio-alert-primary">
                            <i class="xagio-icon xagio-icon-info"></i> Use field below to edit how your masked URL look like. Default value for masked URL is xredirect and now you can change that.
                            You can also see your masked URL preview, but keep in mind that the best practise is to write something unique like:
                            xagio_id, xagio_goto etc, because this will be setup globally and if any other plugin or theme have some functionality bind to for example ?id=1 it could lead to conflict and unexpected behavior.
                        </div>
                        <h5 class="xagio-panel-title xagio-margin-top-medium">Your URL Mask</h5>
                        <input type="text" name="redirect_mask" class="xagio-input-text-mini" id="redirect_mask" placeholder="eg. xredirect" value="<?php echo esc_attr($redirect_mask) ?>">

                        <div class="xagio-flex-space-between xagio-margin-top-medium">
                            <label>Masked URL preview - <a href="<?php echo esc_url(get_site_url()); ?>/?<?php echo esc_attr($redirect_mask) ?>=internal-name"><?php echo esc_url(get_site_url()); ?>/?<span class="redirect_mask_preview"><?php echo esc_html($redirect_mask) ?></span>=internal-name</a></label>
                            <button type="submit" class="xagio-button xagio-button-primary save-shortcode-setup"><i class="xagio-icon xagio-icon-check"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</div> <!-- .wrap -->

<!-- Shortcode Template -->
<div class="shortcode xagio-panel xagio-margin-top-medium xagio-hidden">
    <div class="xagio-2-column-grid">
        <div class="shortcode-link-holder">
            <div class="xagio-flex xagio-flex-wrap xagio-flex-gap-medium">
                <span class="name"></span>
                <div class="link-buttons-holder xagio-buttons-flex xagio-flex-no-wrap">
                    <a class="xagio-button xagio-button-primary xagio-button-mini shortcode-duplicate" href="#" data-xagio-tooltip data-xagio-title="Duplicate this shortcode."><i class="xagio-icon xagio-icon-copy"></i></a>
                    <button data-xagio-tooltip data-xagio-title="Edit this shortcode" class="xagio-button xagio-button-primary xagio-button-mini shortcode-edit" data-xagio-modal="shortcodeModal"><i class="xagio-icon xagio-icon-external-link"></i></button>
                    <button data-xagio-tooltip data-xagio-title="Delete this shortcode" class="xagio-button xagio-button-danger xagio-button-mini shortcode-delete"><i class="xagio-icon xagio-icon-delete"></i></button>
                </div>
            </div>
            <span class="img"></span>
            <div class="shortcode-url">
                Link - <a class="url" href="#" target="_blank"></a>
            </div>
            <div class="xagio-buttons-flex">
                <a class="xagio-button xagio-button-primary copy-shortcode copy-shortcode-tag" href="#" data-xagio-modal="shortModal" data-xagio-tooltip data-xagio-title="Copy this shortcode"><i class="xagio-icon xagio-icon-copy"></i> Copy Shortcode</a>
                <a class="xagio-button xagio-button-primary copy-masked-tag" href="#" data-xagio-modal="maskedModal" data-xagio-tooltip data-xagio-title="Copy this shortcode's masked URL."><i class="xagio-icon xagio-icon-copy"></i> Copy Masked URL</a>
            </div>
        </div>

        <div class="">
            <table class="table-in-table">
                <thead>
                <tr>
                    <th>Stats</th>
                    <th class="xagio-text-center">Impressions</th>
                    <th class="xagio-text-center">Clicks</th>
                    <th class="xagio-text-center">CVR</th>
                    <th class="xagio-text-center">Action</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Shortcode</td>
                    <td class="impressions xagio-text-center" data-xagio-tooltip data-xagio-title="Impressions for this shortcode">...</td>
                    <td class="unique_clicks xagio-text-center" data-xagio-tooltip data-xagio-title="Unique Clicks for this shortcode">...</td>
                    <td class="ctr xagio-text-center" data-xagio-tooltip data-xagio-title="Conversion shown in % for this shortcode">...</td>
                    <td class="">
                        <button class="xagio-button xagio-button-primary xagio-button-mini xagio-margin-inline-auto shortcode-tracking" data-xagio-modal="trackingModal" data-xagio-tooltip data-xagio-title="View tracking chart for this shortcode"><i class="xagio-icon xagio-icon-chart-line"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>Masked URL</td>
                    <td class="url_impressions xagio-text-center" data-xagio-tooltip data-xagio-title="Impressions for this masked URL">...</td>
                    <td class="url_unique_clicks xagio-text-center"  data-xagio-tooltip data-xagio-title="Unique Clicks for this masked URL">...</td>
                    <td class="url_ctr xagio-text-center" data-xagio-tooltip data-xagio-title="Conversion shown in % for this masked URL">...</td>
                    <td><button class="xagio-button xagio-button-primary xagio-button-mini xagio-margin-inline-auto shortcode-url-tracking" data-xagio-modal="urlTrackingModal" data-xagio-tooltip data-xagio-title="View tracking chart for this masked URL"><i class="xagio-icon xagio-icon-chart-line"></i></button></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Short Code Modal -->

<dialog id="shortModal" class="xagio-modal">
    <input type="hidden" class="ID"/>
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-copy"></i> Copy Shortcode</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <input type="text" class="xagio-input-text-mini" id="shortURL" readonly/>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Close</button>
            <button type="button" class="xagio-button xagio-button-primary copy-short-url"><i class="xagio-icon xagio-icon-copy"></i> Copy to Clipboard</button>
        </div>

    </div>
</dialog>

<!-- Masked URL Modal -->
<dialog id="maskedModal" class="xagio-modal">
    <input type="hidden" class="ID"/>

    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-copy"></i> Copy Masked URL</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <input type="text" class="xagio-input-text-mini" id="maskedURL" readonly/>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Close</button>
            <button type="button" class="xagio-button xagio-button-primary copy-masked-url"><i class="xagio-icon xagio-icon-copy"></i> Copy to Clipboard</button>
        </div>
    </div>
</dialog>

<dialog id="trackingModal" class="xagio-modal">
    <input type="hidden" class="ID"/>
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-chart-line"></i> Tracking for <b class="shortcode"></b></h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <div id="tracking_charts" style="width: 100%; height: 350px;"></div>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-primary uk-button-truncate-tracking" data-xagio-close-modal><i class="xagio-icon xagio-icon-delete"></i> Reset</button>
        </div>
    </div>
</dialog>

<dialog id="urlTrackingModal" class="xagio-modal">
    <input type="hidden" class="ID"/>
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-chart-line"></i> Tracking for <b class="shortcode"></b></h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <div id="url_tracking_charts" style="width: 100%; height: 350px;"></div>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-primary uk-button-url-truncate-tracking" data-xagio-close-modal><i class="xagio-icon xagio-icon-delete"></i> Reset</button>
        </div>
    </div>
</dialog>


<!-- Import Project Modal -->
<dialog id="importLinks" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-upload"></i> Import Links</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>

    <div class="xagio-modal-body">
        <div class="xagio-import">
            <div class="xagio-upload-drop">
                <div class="xagio-file-names">
                    <i class="xagio-icon xagio-icon-upload"></i>
                    Drag your CSV here or browse by selecting a file
                </div>
            </div>
            <input type="file" class="file-upload" style="display: none;" />
        </div>

        <div class="xagio-flex-right xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-primary xagio-file-upload-button"><i class="xagio-icon xagio-icon-upload"></i> Upload</button>
        </div>
    </div>
</dialog>

<dialog id="shortcodeModal" class="xagio-modal xagio-modal-lg">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title">
            <i class="xagio-icon xagio-icon-plus"></i> Create Shortcode
        </h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <form class="shortcodeForm">
            <input type="hidden" class="id" name="id" value="0"/>
            <input type="hidden" class="group" name="group" value="Custom"/>
            <input type="hidden" name="action" value="xagio_saveShortcode"/>

            <div class="shortcode-preview">
                <span class="empty">Fill in the fields to preview</span>
            </div>

            <div class="xagio-2-column-75-25-grid xagio-margin-top-medium">
                <div>
                    <div class="modal-label">Title</div>
                    <input type="text" id="title" name="title" class="xagio-input-text-mini" placeholder="eg. Buy product here!" required>
                </div>
                <div>
                    <div class="modal-label">Open in New Tab</div>
                    <div class="xagio-slider-container">
                        <input type="hidden" name="target_blank" id="target_blank" value="0"/>
                        <div class="xagio-slider-frame">
                            <span class="xagio-slider-button" data-element="target_blank"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="xagio-2-column-75-25-grid xagio-margin-top-medium">
                <div>
                    <div class="modal-label">Shortcode: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Format should be (my_shortcode)"></i></div>
                    <input pattern="[a-zA-Z0-9_]{3,}" type="text" id="shortcode" name="shortcode" class="xagio-input-text-mini" placeholder="eg. my_shortcode_name" required>
                </div>
                <div>
                    <div class="modal-label">No Follow Link</div>
                    <div class="xagio-slider-container">
                        <input type="hidden" name="nofollow" id="nofollow" value="0"/>
                        <div class="xagio-slider-frame">
                            <span class="xagio-slider-button" data-element="nofollow"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="xagio-2-column-75-25-grid xagio-margin-top-medium">
                <div>
                    <div class="modal-label">URL</div>
                    <input type="url" id="url" name="url" class="xagio-input-text-mini" placeholder="eg. http://affiliate.com/ref=123" required>
                </div>
                <div>
                    <div class="modal-label">Mask URL</div>
                    <div class="xagio-slider-container">
                        <input type="hidden" name="mask" id="mask" value="0"/>
                        <div class="xagio-slider-frame">
                            <span class="xagio-slider-button" data-element="mask"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="xagio-2-column-75-25-grid xagio-margin-top-medium">
                <div>
                    <div class="modal-label">Internal Name <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Only lowercase letters, minimum 4 characters"></i></div>
                    <input type="text" id="name" name="name" class="xagio-input-text-mini" placeholder="eg. myshortcode" pattern="[a-z]{4,}" required>
                </div>
                <div>

                </div>
            </div>

            <div class="xagio-margin-top-medium">
                <div class="modal-label">Image</div>
                <div class="modal-select-img-row">
                    <input type="url" id="image" name="image" class="xagio-input-text-mini" placeholder="eg. Your Image">
                    <button type="button" class="xagio-button xagio-button-primary xagio-button-mini imageSelect" data-target="image"><i class="xagio-icon xagio-icon-upload"></i></button>
                </div>
            </div>

            <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
                <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
                <button type="submit" class="xagio-button xagio-button-primary"><i class="xagio-icon xagio-icon-check"></i> Save</button>
            </div>
        </form>
    </div>
</dialog>
