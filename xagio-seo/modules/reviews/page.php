<?php
/**
 * Type: SUBMENU
 * Page_Title: Reviews
 * Menu_Title: Reviews
 * Capability: manage_options
 * Slug: xagio-reviews
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: xagio_datatables,xagio_reviews
 * Css: xagio_datatables,xagio_reviews
 * Position: 10
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Changed xagio_stripAllSlashes to stripslashes_deep because of Avada Update
/* Removed wp_strip_all_tags because of prevent the option to be empty */
$ps_review = stripslashes_deep(get_option('XAGIO_REVIEW'));
$MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');
?>

<div class="xagio-main-header xagio-main-header-big-gaps">
    <img class="logo-image repo-xagio" src="<?php echo  esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        Reviews
    </h2>
    <div class="xagio-header-actions">
        <button class="xagio-button xagio-button-primary add_review"><i class="xagio-icon xagio-icon-plus"></i> Add new review</button>
        <?php if(isset($MEMBERSHIP_INFO["membership"]) && $MEMBERSHIP_INFO["membership"] === "Xagio AI Free") { ?>
            <a href="https://xagio.com/?goto=wppremfeatures" target="_blank" class="xagio-button xagio-button-secondary xagio-button-premium-button">
                See Xagio Premium Features
            </a>
        <?php } ?>
    </div>
</div>

<!-- HTML STARTS HERE -->
<div class="xagio-content-wrapper">

    <div class="xagio-accordion xagio-margin-bottom-large">
        <h3 class="xagio-accordion-title">
            <i class="xagio-icon xagio-icon-info"></i>
            <span>You can manage your website reviews and configure how your Review Widget looks like and how it behaves.</span>
            <i class="xagio-icon xagio-icon-arrow-down"></i>
        </h3>
        <div class="xagio-accordion-content">
            <div>
                <div class="xagio-accordion-panel"></div>
            </div>
        </div>
    </div>

    <ul class="xagio-tab">
        <li class="xagio-tab-active"><a href="">Reviews</a></li>
        <li><a href="">Customize</a></li>
        <li><a href="">Shortcodes</a></li>
    </ul>

    <div class="xagio-tab-content-holder">

        <!-- Reviews -->
        <div class="xagio-tab-content">
            <div class="xagio-panel no-padding reviews-table-holder">
                <h5 class="xagio-panel-title xagio-flex-row">
                    <div>
                        <span class="total-number-of-reviews"></span> Reviews
                    </div>

                    <div class="xagio-flex-right xagio-flex-gap-small">
                        <input class="xagio-input-text-mini" placeholder="Search">
                        <select class="xagio-input-select xagio-input-select-gray" id="ReviewState">
                            <option value="">–– All Reviews ––</option>
                            <option value="1">Approved</option>
                            <option value="0">Unapproved</option>
                        </select>

                        <select class="xagio-input-select xagio-input-select-gray" id="ReviewBulkActions">
                            <option value="">–– Bulk Actions ––</option>
                            <option value="approve">Approve</option>
                            <option value="unapprove">Unapprove</option>
                            <option value="delete">Trash</option>
                            <option value="move">Move</option>
                        </select>
                    </div>
                </h5>

                <div class="uk-block uk-block-muted rTable-no-reviews">
                    <h1><i class="xagio-icon xagio-icon-comment"></i> You currently don't have any reviews for your website...</h1>
                    <p>
                        <i class="xagio-icon xagio-icon-info"></i> In order for your visitors to be able to leave reviews for your
                        website,
                        you can go to <a href="<?php echo esc_url(admin_url()); ?>widgets.php" target="_blank">Appearance > Widgets</a> page
                        and
                        drag <b>xagio - Reviews</b> widgets in one of the widget areas on your website or you can use
                        shortcode <kbd>[xagio_reviews_widget]</kbd> in your Page/Post content where you want your Review
                        Widget to appear.
                    </p>
                </div>

                <div class="xagio-table-responsive">
                    <table class="xagio-table rTable">
                        <thead>
                        <tr>
                            <th class="check-column">
                                <input class="xagio-input-checkbox select-review-all" type="checkbox">
                            </th>
                            <th class="column-author" style="width: 20%">Author</th>
                            <th style="width: 40%">Review Content</th>
                            <th class="column-page">In Response To</th>
                            <th class="column-date">Submitted On</th>
                            <th class="column-actions xagio-text-center">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="5">No reviews found. To collect reviews, use the <kbd>Reviews Widget</kbd> in
                                Appearance > Widgets.
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Design -->
        <div class="xagio-tab-content">
            <form class="save-review-widget">
            <div class="xagio-2-column-70-30-grid">
                <div class="xagio-column-1">
                    <input type="hidden" name="action" value="xagio_saveReviewWidget"/>
                    <?php wp_nonce_field('xagio_saveReviewWidget', '_wpnonce'); ?>

                    <ul class="xagio-tab xagio-tab-mini show_widget">
                        <li class="xagio-tab-active"><a href="">Settings</a></li>
                        <li><a href="">Fields</a></li>
                        <li><a href="">Placeholders</a></li>
                        <li><a href="">Design</a></li>
                        <li data-widget="reviews"><a href="">Reviews</a></li>
                        <li data-widget="ratings"><a href="">Ratings</a></li>
                    </ul>

                    <div class="xagio-tab-content-holder">
                        <div class="xagio-tab-content">
                            <div class="xagio-accordion xagio-margin-bottom-medium xagio-accordion-opened">
                                <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                    <span>Function Settings</span>
                                    <i class="xagio-icon xagio-icon-arrow-down"></i>
                                </h3>
                                <div class="xagio-accordion-content">
                                    <div>
                                        <div class="xagio-accordion-panel">
                                            <div class="xagio-2-column-grid">
                                                <div>
                                                    <div class="xagio-slider-container">
                                                        <input type="hidden" name="XAGIO_REVIEW[settings][per_page_reviews]" id="per_page_reviews" value="<?php echo  (@$ps_review['settings']['per_page_reviews'] == 1) ? 1 : 0; ?>">
                                                        <div class="xagio-slider-frame">
                                                            <span class="xagio-slider-button <?php echo  (@$ps_review['settings']['per_page_reviews'] == 1) ? 'on' : ''; ?>" data-element="per_page_reviews"></span>
                                                        </div>
                                                        <p class="xagio-slider-label">Use Per Page Reviews <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip data-xagio-title="Reviews will be visible only on specific pages/posts where they were originally left. Useful if you have a website selling products."></i></p>
                                                    </div>

                                                    <div class="xagio-slider-container">
                                                        <input type="hidden" name="XAGIO_REVIEW[settings][prevent_multiple]" id="prevent_multiple" value="<?php echo  (@$ps_review['settings']['prevent_multiple'] == 1) ? 1 : 0; ?>">
                                                        <div class="xagio-slider-frame">
                                                            <span class="xagio-slider-button <?php echo  (@$ps_review['settings']['prevent_multiple'] == 1) ? 'on' : ''; ?>" data-element="prevent_multiple"></span>
                                                        </div>
                                                        <p class="xagio-slider-label">Stop Multiple Reviews <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip data-xagio-title="Adds cookie tracking to your visitors to prevent adding more than one review per day."></i></p>
                                                    </div>

                                                    <div class="xagio-slider-container">
                                                        <input type="hidden" name="XAGIO_REVIEW[settings][reviews_approve]" id="reviews_approve" value="<?php echo  (@$ps_review['settings']['reviews_approve'] == 1) ? 1 : 0; ?>">
                                                        <div class="xagio-slider-frame">
                                                            <span class="xagio-slider-button <?php echo  (@$ps_review['settings']['reviews_approve'] == 1) ? 'on' : ''; ?>" data-element="reviews_approve"></span>
                                                        </div>
                                                        <p class="xagio-slider-label">Auto Approve Reviews <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip data-xagio-title="If you turn on this option, reviews will be automatically approved without the need for your interaction. If you're using ratings instead of reviews, please see the next option on the right."></i></p>
                                                    </div>

                                                </div>
                                                <div>
                                                    <div class="xagio-slider-container">
                                                        <input type="hidden" name="XAGIO_REVIEW[settings][natural_reviews]" id="natural_reviews" value="<?php echo  (@$ps_review['settings']['natural_reviews'] == 1) ? 1 : 0; ?>">
                                                        <div class="xagio-slider-frame">
                                                            <span class="xagio-slider-button <?php echo  (@$ps_review['settings']['natural_reviews'] == 1) ? 'on' : ''; ?>" data-element="natural_reviews"></span>
                                                        </div>
                                                        <p class="xagio-slider-label">Natural Reviews <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip data-xagio-title="All ratings from reviews will be calculated into assigned Schema(s) for current page/post."></i></p>
                                                    </div>
                                                    <div class="xagio-slider-container">
                                                        <input type="hidden" name="XAGIO_REVIEW[settings][stars_approve]" id="stars_approve" value="<?php echo  (@$ps_review['settings']['stars_approve'] == 1) ? 1 : 0; ?>">
                                                        <div class="xagio-slider-frame">
                                                            <span class="xagio-slider-button <?php echo  (@$ps_review['settings']['stars_approve'] == 1) ? 'on' : ''; ?>" data-element="stars_approve"></span>
                                                        </div>
                                                        <p class="xagio-slider-label">Auto Approve Ratings <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip data-xagio-title="If you have Widget Ratings Mode activated globally, or for certain pages using a shortcode, you can turn on this option to automatically approve ratings."></i></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="xagio-accordion xagio-margin-bottom-medium">
                                <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                    <span>Popup Settings</span>
                                    <i class="xagio-icon xagio-icon-arrow-down"></i>
                                </h3>
                                <div class="xagio-accordion-content">
                                    <div>
                                        <div class="xagio-accordion-panel">
                                            <div class="xagio-2-column-grid">
                                                <div>
                                                    <div class="xagio-slider-container">
                                                        <input type="hidden" name="XAGIO_REVIEW[settings][popup]" id="popup" value="<?php echo  (@$ps_review['settings']['popup'] == 1) ? 1 : 0; ?>">
                                                        <div class="xagio-slider-frame">
                                                            <span class="xagio-slider-button <?php echo  (@$ps_review['settings']['popup'] == 1) ? 'on' : ''; ?>" data-element="popup"></span>
                                                        </div>
                                                        <p class="xagio-slider-label">Button Popup Mode <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip data-xagio-title="Instead of showing Review Widget directly on page, this will make it appear as a popup when clicked on a button. Useful if you have limited space on your website."></i></p>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="xagio-slider-container" style="<?php echo  (@$ps_review['settings']['popup'] == 0) ? 'display:none;' : ''; ?>">
                                                        <input type="hidden" name="XAGIO_REVIEW[settings][popup_text]" id="popup_text" value="<?php echo  (@$ps_review['settings']['popup_text'] == 1) ? 1 : 0; ?>">
                                                        <div class="xagio-slider-frame">
                                                            <span class="xagio-slider-button <?php echo  (@$ps_review['settings']['popup_text'] == 1) ? 'on' : ''; ?>" data-element="popup_text"></span>
                                                        </div>
                                                        <p class="xagio-slider-label">Use Text Popup Button <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip data-xagio-title="If you want to display a text link instead of a button to display the review widget popup, turn this on."></i></p>
                                                    </div>
                                                    <div class="xagio-slider-container" style="<?php echo  (@$ps_review['settings']['popup'] == 0) ? 'display:none;' : ''; ?>">
                                                        <input type="hidden" name="XAGIO_REVIEW[settings][exit_popup]" id="exit_popup" value="<?php echo  (@$ps_review['settings']['exit_popup'] == 1) ? 1 : 0; ?>">
                                                        <div class="xagio-slider-frame">
                                                            <span class="xagio-slider-button <?php echo  (@$ps_review['settings']['exit_popup'] == 1) ? 'on' : ''; ?>" data-element="exit_popup"></span>
                                                        </div>
                                                        <p class="xagio-slider-label">Exit Popup Mode <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip data-xagio-title="Stop visitors from leaving your website without leaving a review! This operation will be only available when Popup Mode is enabled."></i></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <label class="xagio-label-text" for="popup_button_title">Popup Button Text: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Changes the text of a button used to display review widget in popup mode."></i></label>
                                            <input type="text" class="xagio-input-text-mini" value="<?php echo  esc_attr(@$ps_review['details']['popup_button_title']); ?>" id="popup_button_title" name="XAGIO_REVIEW[details][popup_button_title]" placeholder="eg. Submit"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="xagio-accordion xagio-margin-bottom-medium">
                                <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                    <span>Mode Settings</span>
                                    <i class="xagio-icon xagio-icon-arrow-down"></i>
                                </h3>
                                <div class="xagio-accordion-content">
                                    <div>
                                        <div class="xagio-accordion-panel">
                                            <div class="xagio-2-column-grid">
                                                <div>
                                                    <div class="xagio-slider-container">
                                                        <input type="hidden" name="XAGIO_REVIEW[settings][stars_only]" id="stars_only" value="<?php echo  (@$ps_review['settings']['stars_only'] == 1) ? 1 : 0; ?>">
                                                        <div class="xagio-slider-frame">
                                                            <span class="xagio-slider-button <?php echo  (@$ps_review['settings']['stars_only'] == 1) ? 'on' : ''; ?>" data-element="stars_only"></span>
                                                        </div>
                                                        <p class="xagio-slider-label">Widget Ratings Mode <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip data-xagio-title="Turns Review Widget into the Ratings Widget. Basically removes Submit Review button along with all other input fields except stars and instead adds ratings when stars are clicked."></i></p>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="xagio-slider-container">
                                                        <input type="hidden" name="XAGIO_REVIEW[settings][alpha_bg]" id="alpha_bg" value="<?php echo  (@$ps_review['settings']['alpha_bg'] == 1) ? 1 : 0; ?>">
                                                        <div class="xagio-slider-frame">
                                                            <span class="xagio-slider-button <?php echo  (@$ps_review['settings']['alpha_bg'] == 1) ? 'on' : ''; ?>" data-element="alpha_bg"></span>
                                                        </div>
                                                        <p class="xagio-slider-label">Alpha Widget Mode <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip data-xagio-title="If you want your widget to blend in with the rest of the background, use this option."></i></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="xagio-accordion xagio-margin-bottom-medium">
                                <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                    <span>Message Settings</span>
                                    <i class="xagio-icon xagio-icon-arrow-down"></i>
                                </h3>
                                <div class="xagio-accordion-content">
                                    <div>
                                        <div class="xagio-accordion-panel">
                                            <label for="details_thank_you" class="xagio-label-text">Thank You - Message: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Changes the message user receives after he leaves a review."></i></label>
                                            <input type="text" class="xagio-input-text-mini" id="details_thank_you" value="<?php echo esc_attr(@$ps_review['details']['thank_you']); ?>" name="XAGIO_REVIEW[details][thank_you]" placeholder="eg. Thank you for leaving a Review!"/>

                                            <label for="details_no_reviews_message" class="xagio-label-text">No Reviews - Message: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Changes the message user sees when there are no reviews for current content."></i></label>
                                            <input type="text" class="xagio-input-text-mini" id="details_no_reviews_message" value="<?php echo esc_attr(@$ps_review['details']['no_reviews_message']); ?>" name="XAGIO_REVIEW[details][no_reviews_message]" placeholder="eg. Nobody yet left a review. Be first?!"/>

                                            <?php if (class_exists('XAGIO_MODEL_REVIEWS')): ?>

                                                <!-- Enable/Disable review widget css,js -->
                                                <div class="xagio-slider-container xagio-margin-top-medium">
                                                    <input type="hidden" name="XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS" id="XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS" value="<?php echo  !XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS ? 0 : 1; ?>"/>
                                                    <div class="xagio-slider-frame">
                                                        <span class="xagio-slider-button slider-button-settings <?php echo  XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS ? 'on' : ''; ?>" data-element="XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS"></span>
                                                    </div>
                                                    <p class="xagio-slider-label">Disable CSS & JS for Review Widget <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip data-xagio-title="This will enable/disable review widget css, js file on this website."></i></p>
                                                </div>

                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="xagio-tab-content">

                            <div class="xagio-panel">
                                <h5 class="xagio-panel-title">Review Widget – Fields</h5>

                                <p class="xagio-text-info">In this section you can choose which fields should be present / required on your Review Widget. You can also change the order of fields by dragging them around the list.</p>

                                <input type="hidden" name="XAGIO_REVIEW[fields]" value="<?php echo  (@$ps_review['fields'] != NULL) ? esc_attr($ps_review['fields']) : '' ?>"/>

                                <ul class="uk-sortable fields" data-uk-sortable="{handleClass:'uk-sortable-handle'}">

                                    <li>
                                        <div class="xagio-sortable-row" data-name="name">
                                            <div class="xagio-sortable-title">
                                                <i class="uk-sortable-handle uk-icon uk-icon-bars uk-margin-small-right"></i> Name
                                            </div>

                                            <div class="xagio-sortable-buttons">
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-required" title="Require Field" data-value="0" style="display: none;"><i class="xagio-icon xagio-icon-ban"></i></button>
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-switch" data-value="0"><i class="xagio-icon xagio-icon-plus"></i></button>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="xagio-sortable-row" data-name="review">
                                            <div class="xagio-sortable-title">
                                                <i class="uk-sortable-handle uk-icon uk-icon-bars uk-margin-small-right"></i> Review
                                            </div>

                                            <div class="xagio-sortable-buttons">
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-required" title="Require Field" data-value="0" style="display: none;"><i class="xagio-icon xagio-icon-ban"></i></button>
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-switch" data-value="0"><i class="xagio-icon xagio-icon-plus"></i></button>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="xagio-sortable-row" data-name="rating">
                                            <div class="xagio-sortable-title">
                                                <i class="uk-sortable-handle uk-icon uk-icon-bars uk-margin-small-right"></i> Rating
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="xagio-sortable-row" data-name="email">
                                            <div class="xagio-sortable-title">
                                                <i class="uk-sortable-handle uk-icon uk-icon-bars uk-margin-small-right"></i> E-Mail Address
                                            </div>

                                            <div class="xagio-sortable-buttons">
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-required" title="Require Field" data-value="0" style="display: none;"><i class="xagio-icon xagio-icon-ban"></i></button>
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-switch" data-value="0"><i class="xagio-icon xagio-icon-plus"></i></button>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="xagio-sortable-row" data-name="website">
                                            <div class="xagio-sortable-title">
                                                <i class="uk-sortable-handle uk-icon uk-icon-bars uk-margin-small-right"></i> Website
                                            </div>

                                            <div class="xagio-sortable-buttons">
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-required" title="Require Field" data-value="0" style="display: none;"><i class="xagio-icon xagio-icon-ban"></i></button>
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-switch" data-value="0"><i class="xagio-icon xagio-icon-plus"></i></button>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="xagio-sortable-row" data-name="title">
                                            <div class="xagio-sortable-title">
                                                <i class="uk-sortable-handle uk-icon uk-icon-bars uk-margin-small-right"></i> Title
                                            </div>

                                            <div class="xagio-sortable-buttons">
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-required" title="Require Field" data-value="0" style="display: none;"><i class="xagio-icon xagio-icon-ban"></i></button>
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-switch" data-value="0"><i class="xagio-icon xagio-icon-plus"></i></button>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="xagio-sortable-row" data-name="telephone">
                                            <div class="xagio-sortable-title">
                                                <i class="uk-sortable-handle uk-icon uk-icon-bars uk-margin-small-right"></i> Telephone
                                            </div>

                                            <div class="xagio-sortable-buttons">
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-required" title="Require Field" data-value="0" style="display: none;"><i class="xagio-icon xagio-icon-ban"></i></button>
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-switch" data-value="0"><i class="xagio-icon xagio-icon-plus"></i></button>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="xagio-sortable-row" data-name="location">
                                            <div class="xagio-sortable-title">
                                                <i class="uk-sortable-handle uk-icon uk-icon-bars uk-margin-small-right"></i> Location
                                            </div>

                                            <div class="xagio-sortable-buttons">
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-required" title="Require Field" data-value="0" style="display: none;"><i class="xagio-icon xagio-icon-ban"></i></button>
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-switch" data-value="0"><i class="xagio-icon xagio-icon-plus"></i></button>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="xagio-sortable-row" data-name="age">
                                            <div class="xagio-sortable-title">
                                                <i class="uk-sortable-handle uk-icon uk-icon-bars uk-margin-small-right"></i> Age
                                            </div>

                                            <div class="xagio-sortable-buttons">
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-required" title="Require Field" data-value="0" style="display: none;"><i class="xagio-icon xagio-icon-ban"></i></button>
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-switch" data-value="0"><i class="xagio-icon xagio-icon-plus"></i></button>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="xagio-sortable-row" data-name="captcha">
                                            <div class="xagio-sortable-title">
                                                <i class="uk-sortable-handle uk-icon uk-icon-bars uk-margin-small-right"></i> reCaptcha
                                            </div>

                                            <div class="xagio-sortable-buttons">
                                                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini uk-button-switch" data-value="0"><i class="xagio-icon xagio-icon-plus"></i></button>
                                            </div>
                                        </div>
                                    </li>

                                </ul>
                            </div>
                        </div>
                        <div class="xagio-tab-content">
                            <div class="xagio-panel ps_review_placeholders">
                                <h5 class="xagio-panel-title">Review Widget – Placeholders</h5>

                                <p class="xagio-text-info">Changing the fields below will allow you to set placeholders for fields when there is no text in them, ie. when they're empty.</p>

                                <label for="review_name_placeholder" class="xagio-label-text">Name:</label>
                                <input type="text" class="xagio-input-text-mini" id="review_name_placeholder" data-name="name" value="eg. John"/>

                                <label for="review_review_placeholder" class="xagio-label-text">Review:</label>
                                <input type="text" class="xagio-input-text-mini" id="review_review_placeholder" data-name="review" value="eg. This is really a cool website!" name="XAGIO_REVIEW[placeholders][review]"/>

                                <label for="review_email_placeholder" class="xagio-label-text">E-mail:</label>
                                <input type="text" class="xagio-input-text-mini" id="review_email_placeholder" data-name="email" value="eg. your@email.com" name="XAGIO_REVIEW[placeholders][email]"/>

                                <label for="review_website_placeholder" class="xagio-label-text">Website:</label>
                                <input type="text" class="xagio-input-text-mini" id="review_website_placeholder" data-name="website" value="eg. http://www.website.com" name="XAGIO_REVIEW[placeholders][website]"/>

                                <label for="review_title_placeholder" class="xagio-label-text">Title:</label>
                                <input type="text" class="xagio-input-text-mini" id="review_title_placeholder" data-name="title" value="eg. I like this product" name="XAGIO_REVIEW[placeholders][title]"/>

                                <label for="review_tel_placeholder" class="xagio-label-text">Telephone:</label>
                                <input type="text" class="xagio-input-text-mini" id="review_tel_placeholder" data-name="telephone" value="eg. 1-800-500-6000" name="XAGIO_REVIEW[placeholders][telephone]"/>

                                <label for="review_loc_placeholder" class="xagio-label-text">Location :</label>
                                <input type="text" class="xagio-input-text-mini" id="review_loc_placeholder" data-name="location" value="eg. Los Angeles" name="XAGIO_REVIEW[placeholders][location]"/>

                                <label for="review_age_placeholder" class="xagio-label-text">Age:</label>
                                <input type="text" class="xagio-input-text-mini" id="review_age_placeholder" data-name="age" value="eg. 35" name="XAGIO_REVIEW[placeholders][age]"/>
                            </div>
                        </div>
                        <div class="xagio-tab-content">
                            <div class="xagio-accordion xagio-margin-bottom-medium xagio-accordion-opened">
                                <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                    <span>Review Widget – Text</span>
                                    <i class="xagio-icon xagio-icon-arrow-down"></i>
                                </h3>
                                <div class="xagio-accordion-content">
                                    <div>
                                        <div class="xagio-accordion-panel">
                                            <div class="xagio-2-column-grid">
                                                <div>
                                                    <label for="details_title" class="xagio-label-text">Heading: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Changes the main heading of the Review Widget. HTML allowed."></i></label>
                                                    <input type="text" class="xagio-input-text-mini" id="details_title" value="<?php echo esc_attr(@$ps_review['details']['title']); ?>" name="XAGIO_REVIEW[details][title]" placeholder="eg. Leave a Review"/>

                                                    <label for="details_text" class="xagio-label-text">Subheading: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Changes the subheading of the Review Widget. HTML allowed."></i></label>
                                                    <textarea rows="1" class="xagio-input-textarea" id="details_text" name="XAGIO_REVIEW[details][text]" placeholder="eg. Describe what should your visitors do..."><?php echo esc_textarea(@$ps_review['details']['text']); ?></textarea>

                                                    <label for="details_button_title" class="xagio-label-text">Submit Button Text: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Changes the text of a button used to submit reviews."></i></label>
                                                    <input type="text" class="xagio-input-text-mini" id="details_button_title" value="<?php echo esc_attr(@$ps_review['details']['button_title']); ?>" name="XAGIO_REVIEW[details][button_title]" placeholder="eg. Submit"/>
                                                </div>
                                                <div>

                                                    <label for="heading-size" class="xagio-label-text">Heading Size:</label>
                                                    <div class="xagio-flex-row xagio-min-height-40">
                                                        <input type="range" value="<?php echo esc_attr(@$ps_review['font_size']['heading']) ?>" min="10" max="40" name="XAGIO_REVIEW[font_size][heading]" id="heading-size" class="xagio-range"/>
                                                    </div>

                                                    <label for="subheading-size" class="xagio-label-text">Subheading Size:</label>
                                                    <div class="xagio-flex-row xagio-margin-bottom-medium xagio-min-height-40">
                                                        <input type="range" value="<?php echo esc_attr(@$ps_review['font_size']['subheading']) ?>" min="8" max="20" name="XAGIO_REVIEW[font_size][subheading]" id="subheading-size" class="xagio-range"/>
                                                    </div>



                                                    <div class="xagio-flex-space-between">
                                                        <div>
                                                            <label for="color-picker-6" class="color-picker-text xagio-label-text">Background</label>
                                                            <div class="xagio-color-swatch">
                                                                <input value="<?php echo  (@$ps_review['colors']['button_background'] != NULL) ? esc_attr($ps_review['colors']['button_background']) : '#eaeaea'; ?>" type="color" id="color-picker-6" name="XAGIO_REVIEW[colors][button_background]" class="color-picker"/>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <label for="color-picker-7" class="color-picker-text xagio-label-text">Text</label>
                                                            <div class="xagio-color-swatch">
                                                                <input value="<?php echo  (@$ps_review['colors']['button_text'] != NULL) ? esc_attr($ps_review['colors']['button_text']) : '#656565'; ?>" type="color" id="color-picker-7" name="XAGIO_REVIEW[colors][button_text]" class="color-picker"/>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="xagio-accordion xagio-margin-bottom-medium">
                                <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                    <span>Field & Widget Design</span>
                                    <i class="xagio-icon xagio-icon-arrow-down"></i>
                                </h3>
                                <div class="xagio-accordion-content">
                                    <div>
                                        <div class="xagio-accordion-panel">
                                            <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                                                <div>
                                                    <label for="widget-theme" class="xagio-label-text">Widget Theme: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Allows you to use different widget themes to match your website design."></i></label>
                                                    <select name="XAGIO_REVIEW[settings][widget_theme]" id="widget-theme" class="xagio-input-select xagio-input-select-gray">
                                                        <option <?php echo  (@$ps_review['settings']['widget_theme'] == 0) ? 'selected' : ''; ?> value="0">Default</option>
                                                        <option <?php echo  (@$ps_review['settings']['widget_theme'] == 1) ? 'selected' : ''; ?> value="1">Flat</option>
                                                        <option <?php echo  (@$ps_review['settings']['widget_theme'] == 2) ? 'selected' : ''; ?> value="2">Minimal</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label for="widget-width" class="xagio-label-text">Widget Width: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Allows you to switch between fixed width & automatic full width for Review Widget."></i></label>
                                                    <select name="XAGIO_REVIEW[settings][widget_width]" id="widget-width" class="xagio-input-select xagio-input-select-gray">
                                                        <option <?php echo  (@$ps_review['settings']['widget_width'] == 0) ? 'selected' : ''; ?> value="0">Fixed</option>
                                                        <option <?php echo  (@$ps_review['settings']['widget_width'] == 1) ? 'selected' : ''; ?> value="1">Auto</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                                                <div>
                                                    <label for="alignment" class="xagio-label-text">Content Alignment: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Allows you to use different alignment for content inside of Review Widget."></i></label><select name="XAGIO_REVIEW[settings][alignment]" id="alignment" class="xagio-input-select xagio-input-select-gray">
                                                        <option <?php echo  (@$ps_review['settings']['alignment'] == 'left') ? 'selected' : ''; ?>value="left">Left</option>
                                                        <option <?php echo  (@$ps_review['settings']['alignment'] == 'center') ? 'selected' : ''; ?>value="center">Center</option>
                                                        <option <?php echo  (@$ps_review['settings']['alignment'] == 'right') ? 'selected' : ''; ?>value="right">Right</option>
                                                    </select>

                                                </div>
                                                <div>
                                                    <label for="form-labels" class="xagio-label-text">Label Rendering Mode: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Allows you to change how the form labels will look like on your Review Widget."></i></label>
                                                    <select name="XAGIO_REVIEW[settings][form_labels]" id="form-labels" class="xagio-input-select xagio-input-select-gray">
                                                        <option <?php echo  (@$ps_review['settings']['form_labels'] == 0) ? 'selected' : ''; ?>value="0">Above the text boxes</option>
                                                        <option <?php echo  (@$ps_review['settings']['form_labels'] == 1) ? 'selected' : ''; ?>value="1">Next to text boxes</option>
                                                        <option <?php echo  (@$ps_review['settings']['form_labels'] == 2) ? 'selected' : ''; ?>value="2">As placeholders</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                                                <div>
                                                    <label for="widget-padding" class="xagio-label-text">Widget Padding:</label>
                                                    <div class="xagio-flex-row xagio-min-height-40">
                                                        <input type="range" value="<?php echo esc_attr(@$ps_review['padding']['widget']) ?>" min="1" max="50" name="XAGIO_REVIEW[padding][widget]" id="widget-padding" class="xagio-range" />
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="input-padding" class="xagio-label-text">Field Padding:</label>
                                                    <div class="xagio-flex-row xagio-min-height-40">
                                                        <input type="range" value="<?php echo esc_attr(@$ps_review['padding']['input']) ?>" min="1" max="50" name="XAGIO_REVIEW[padding][input]" id="input-padding" class="xagio-range" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                                                <div>
                                                    <label for="input-size" class="xagio-label-text">Field Text Size:</label>
                                                    <div class="xagio-flex-row xagio-min-height-40">
                                                        <input type="range" value="<?php echo esc_attr(@$ps_review['font_size']['input']) ?>" min="8" max="25" name="XAGIO_REVIEW[font_size][input]" id="input-size" class="xagio-range" />
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="label-size" class="xagio-label-text">Label Text Size:</label>
                                                    <div class="xagio-flex-row xagio-min-height-40">
                                                        <input type="range" value="<?php echo esc_attr(@$ps_review['font_size']['label']) ?>" min="8" max="25" name="XAGIO_REVIEW[font_size][label]" id="label-size" class="xagio-range" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                                                <div>
                                                    <label for="stars-size" class="xagio-label-text">Stars Size:</label>
                                                    <div class="xagio-flex-row xagio-min-height-40">
                                                        <input type="range" value="<?php echo esc_attr(@$ps_review['font_size']['stars']) ?>" min="14" max="50" name="XAGIO_REVIEW[font_size][stars]" id="stars-size" class="xagio-range" />
                                                    </div>
                                                </div>
                                                <div></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="xagio-accordion xagio-margin-bottom-medium">
                                <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                    <span>Color Design</span>
                                    <i class="xagio-icon xagio-icon-arrow-down"></i>
                                </h3>
                                <div class="xagio-accordion-content">
                                    <div>
                                        <div class="xagio-accordion-panel">
                                            <div class="xagio-flex-space-between xagio-flex-wrap">
                                                <div>
                                                    <label for="color-picker-1" class="xagio-label-text">Background</label>
                                                    <div class="xagio-color-swatch">
                                                        <input value="<?php echo  (@$ps_review['colors']['background'] != NULL) ? esc_attr($ps_review['colors']['background']) : '#ffffff'; ?>" type="color" id="color-picker-1" name="XAGIO_REVIEW[colors][background]" class="color-picker" />
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="color-picker-2" class="xagio-label-text">Border</label>
                                                    <div class="xagio-color-swatch">
                                                        <input value="<?php echo  (@$ps_review['colors']['border'] != NULL) ? esc_attr($ps_review['colors']['border']) : '#bbbbbb'; ?>" type="color" id="color-picker-2" name="XAGIO_REVIEW[colors][border]" class="color-picker" />
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="color-picker-3" class="xagio-label-text">Text</label>
                                                    <div class="xagio-color-swatch">
                                                        <input value="<?php echo  (@$ps_review['colors']['text'] != NULL) ? esc_attr($ps_review['colors']['text']) : '#444444'; ?>" type="color" id="color-picker-3" name="XAGIO_REVIEW[colors][text]" class="color-picker" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="xagio-flex-space-between xagio-flex-wrap">
                                                <div>
                                                    <label for="color-picker-4" class="xagio-label-text">Input Background</label>
                                                    <div class="xagio-color-swatch">
                                                        <input value="<?php echo  (@$ps_review['colors']['input_background'] != NULL) ? esc_attr($ps_review['colors']['input_background']) : '#ffffff'; ?>" type="color" id="color-picker-4" name="XAGIO_REVIEW[colors][input_background]" class="color-picker" />
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="color-picker-8" class="xagio-label-text">Stars</label>
                                                    <div class="xagio-color-swatch">
                                                        <input value="<?php echo  (@$ps_review['colors']['stars'] != NULL) ? esc_attr($ps_review['colors']['stars']) : '#000012'; ?>" type="color" id="color-picker-8" name="XAGIO_REVIEW[colors][stars]" class="color-picker" />
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="color-picker-5" class="xagio-label-text">Input Text</label>
                                                    <div class="xagio-color-swatch">
                                                        <input value="<?php echo  (@$ps_review['colors']['input_text'] != NULL) ? esc_attr($ps_review['colors']['input_text']) : '#000012'; ?>" type="color" id="color-picker-5" name="XAGIO_REVIEW[colors][input_text]" class="color-picker" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="xagio-tab-content">
                            <div class="xagio-panel">
                                <h5 class="xagio-panel-title">Review Widget – Text</h5>
                                <p class="xagio-text-info">Change the fields below to alter how your Display Reviews Widget will look like. You'll see all the changes you make in the preview area on the right.</p>

                                <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                                    <div>
                                        <label class="xagio-label-text">Heading: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Change this field if you want Heading above your ratings, if you leave this field emtpy it will not be shown. HTML allowed."></i></label>
                                        <input type="text" class="xagio-input-text-mini" value="<?php echo esc_attr(@$ps_review['details']['display_reviews_heading']); ?>" name="XAGIO_REVIEW[details][display_reviews_heading]" placeholder="eg. Check out our Reviews!" />
                                    </div>
                                    <div>
                                        <label for="display-heading-size" class="xagio-label-text">Heading Size:</label>
                                        <div class="xagio-flex-row xagio-min-height-40">
                                            <input type="range" value="<?php echo esc_attr(@$ps_review['details']['heading_size']) ?>" min="10" max="40" name="XAGIO_REVIEW[details][heading_size]" id="display-heading-size" class="xagio-range"/>
                                        </div>
                                    </div>
                                </div>

                                <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                                    <div>
                                        <label class="xagio-label-text">Subheading: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Changes the subheading of the Display Reviews Widget. Use {calc} & {sum} inside of this field to display calculated ratings & number of reviews / ratings. HTML allowed."></i></label>
                                        <input type="text" class="xagio-input-text-mini" value="<?php echo esc_attr(@$ps_review['details']['display_reviews_text']); ?>" name="XAGIO_REVIEW[details][display_reviews_text]" placeholder="eg. {calc} Rating From {sum} Reviews."/>
                                    </div>
                                    <div>
                                        <label for="display-subheading-size" class="xagio-label-text">Subheading Size:</label>
                                        <div class="xagio-flex-row xagio-min-height-40">
                                            <input type="range" value="<?php echo esc_attr(@$ps_review['details']['subheading_size']) ?>" min="8" max="20" name="XAGIO_REVIEW[details][subheading_size]" id="display-subheading-size" class="xagio-range"/>
                                        </div>
                                    </div>
                                </div>

                                <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                                    <div>
                                        <label for="stars-size-display" class="xagio-label-text">Star Size:</label>
                                        <div class="xagio-flex-row xagio-min-height-40">
                                            <input type="range" value="<?php echo esc_attr(@$ps_review['details']['display_star_size']) ?>" min="10" max="40" name="XAGIO_REVIEW[details][display_star_size]" id="stars-size-display" class="xagio-range"/>
                                        </div>
                                    </div>
                                    <div>

                                    </div>
                                </div>
                            </div>

                            <div class="xagio-accordion xagio-margin-bottom-medium xagio-margin-top-medium">
                                <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                    <span>Color Design</span>
                                    <i class="xagio-icon xagio-icon-arrow-down"></i>
                                </h3>
                                <div class="xagio-accordion-content">
                                    <div>
                                        <div class="xagio-accordion-panel">


                                            <div class="xagio-flex-space-between xagio-flex-wrap">
                                                <div>
                                                    <label for="color-picker-1" class="xagio-label-text">Background</label>
                                                    <div class="xagio-color-swatch">
                                                        <input value="<?php echo  (@$ps_review['colors_display']['background'] != NULL) ? esc_attr($ps_review['colors_display']['background']) : '#fbfbfb'; ?>" type="color" id="color-picker-51" name="XAGIO_REVIEW[colors_display][background]" class="color-picker"/>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="color-picker-2" class="xagio-label-text">Border</label>
                                                    <div class="xagio-color-swatch">
                                                        <input value="<?php echo  (@$ps_review['colors_display']['border'] != NULL) ? esc_attr($ps_review['colors_display']['border']) : '#fbfbfb'; ?>" type="color" id="color-picker-52" name="XAGIO_REVIEW[colors_display][border]" class="color-picker"/>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="color-picker-3" class="xagio-label-text">Text</label>
                                                    <div class="xagio-color-swatch">
                                                        <input value="<?php echo  (@$ps_review['colors_display']['text'] != NULL) ? esc_attr($ps_review['colors_display']['text']) : '#626a74'; ?>" type="color" id="color-picker-53" name="XAGIO_REVIEW[colors_display][text]" class="color-picker"/>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="color-picker-8" class="xagio-label-text">Stars</label>
                                                    <div class="xagio-color-swatch">
                                                        <input value="<?php echo  (@$ps_review['colors_display']['stars'] != NULL) ? esc_attr($ps_review['colors_display']['stars']) : '#626a74'; ?>" type="color" id="color-picker-58" name="XAGIO_REVIEW[colors_display][stars]" class="color-picker"/>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="xagio-tab-content">
                            <div class="xagio-panel">
                                <h5 class="xagio-panel-title">Widget Ratings Mode – Text & Colors</h5>
                                <p class="xagio-text-info">Change the fields below to alter how your Review Widget will look like when you use the "Widget Ratings Mode". You'll see all the changes you make in the preview area on the right.</p>

                                <label class="xagio-label-text">Heading: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Changes the main heading of the Review Widget when Widget Ratings Mode is turned on. Use {num} inside of this field to display percentage, {calc} to display rating value and {sum} to display total number of reviews. HTML allowed."></i></label>
                                <input type="text" class="xagio-input-text-mini" value="<?php echo  esc_attr(@$ps_review['details']['rating_text']); ?>" name="XAGIO_REVIEW[details][rating_text]" placeholder="eg. {num} of users found this article interesting"/>

                                <div class="xagio-flex-even-columns xagio-flex-gap-large xagio-margin-top-medium xagio-margin-bottom-medium">
                                    <div>
                                        <label for="rating-heading-size" class="xagio-label-text">Heading Size</label>
                                        <div class="xagio-flex-row xagio-min-height-40">
                                            <input type="range" value="<?php echo  esc_attr(@$ps_review['details']['rating_heading_size']) ?>" min="10" max="40" name="XAGIO_REVIEW[details][rating_heading_size]" id="rating-heading-size" class="xagio-range"/>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="color-picker-11" class="xagio-label-text">Heading Color</label>
                                        <div class="xagio-color-swatch">
                                            <input value="<?php echo  (@$ps_review['colors']['rating_heading'] != NULL) ? esc_attr($ps_review['colors']['rating_heading']) : '#434440'; ?>" type="color" id="color-picker-11" name="XAGIO_REVIEW[colors][rating_heading]" class="color-picker"/>
                                        </div>
                                    </div>
                                </div>

                                <label class="xagio-label-text">Instruction Text: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Changes the instruction text that tells the user how to use the ratings widget."></i></label>
                                <input type="text" class="xagio-input-text-mini" value="<?php echo  esc_attr(@$ps_review['details']['rating_info']); ?>" name="XAGIO_REVIEW[details][rating_info]" placeholder="eg. Click a star to add your rating"/>

                                <div class="xagio-flex-even-columns xagio-flex-gap-large xagio-margin-top-medium xagio-margin-bottom-medium">
                                    <div>
                                        <label for="rating-heading-size" class="xagio-label-text">Instruction Size:</label>
                                        <div class="xagio-flex-row xagio-min-height-40">
                                            <input type="range" value="<?php echo  esc_attr(@$ps_review['details']['rating_instruction_size']) ?>" min="8" max="20" name="XAGIO_REVIEW[details][rating_instruction_size]" id="rating-heading-size" class="xagio-range"/>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="color-picker-12" class="xagio-label-text">Information Text</label>
                                        <div class="xagio-color-swatch">
                                            <input value="<?php echo  (@$ps_review['colors']['rating_info'] != NULL) ? esc_attr($ps_review['colors']['rating_info']) : '#3a3a3a70'; ?>" type="color" id="color-picker-12" name="XAGIO_REVIEW[colors][rating_info]" class="color-picker"/>
                                        </div>
                                    </div>
                                </div>

                                <label class="xagio-label-text">Thank You - Message: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Changes the message user sees when there are no ratings for current content."></i></label>
                                <input type="text" class="xagio-input-text-mini" value="<?php echo  esc_attr(@$ps_review['details']['rating_thank_you']); ?>" name="XAGIO_REVIEW[details][rating_thank_you]" placeholder="eg. Thank you for leaving a rating!"/>

                                <label class="xagio-label-text">No Ratings - Message: <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Changes the message user sees when there are no ratings for current content."></i></label>
                                <input type="text" class="xagio-input-text-mini" value="<?php echo  esc_attr(@$ps_review['details']['no_ratings_message']); ?>" name="XAGIO_REVIEW[details][no_ratings_message]" placeholder="eg. Nobody yet left a rating. Be first?!"/>


                                <div class="xagio-alert xagio-alert-primary xagio-margin-top-medium">
                                    <i class="xagio-icon xagio-icon-info"></i> To use the "Widget Ratings Mode" either set the "Widget Ratings Mode" to Yes in Settings, or when using Review Widget shortcode use the attribute <kbd>stars_only=1</kbd>.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="xagio-column-2" style="padding-top: 66px;">
                    <div class="submit-button-holder">
                        <button class="xagio-button xagio-button-primary uk-button-save-review-design"><i class="xagio-icon xagio-icon-check"></i> Save Changes</button>
                    </div>
                    <div id="preview-area">

                        <!-- Preview Form -->
                        <div class="review-widget">

                            <div class="review-widget-title">
                                <h2>Leave a Review</h2>
                            </div>

                            <div class="review-widget-text">
                                Please be kind and leave us a review!
                            </div>

                            <div class="review-widget-stars-ratings-sum">
                                <b>100%</b> Please be kind and leave us a review!
                            </div>

                            <div class="review-widget-block-container">

                            </div>

                            <button class="review-widget-button" type="button">Submit Review</button>

                            <span class="review-widget-stars-ratings-info">
                                    Click a star to add your rating
                                </span>

                        </div>


                        <!-- Preview Form -->
                        <div class="review-display" style="display: none">
                            <div class="prs-review-display-heading"><h2></h2></div>
                            <div class="prs-review-container-aggregate" style="width: auto;">
                                <b>5</b>/<b>5</b> Rating From <b>13</b> Reviews.
                            </div>

                            <div class="prs-review-container" style="width: auto;">
                                <div class="prs-review-spacer">
                                    <i class="xagio-icon xagio-icon-quote"></i>
                                </div>
                                <div class="prs-review-stars">
                                    <i class="xagio-icon xagio-icon-star"></i> <i class="xagio-icon xagio-icon-star"></i> <i class="xagio-icon xagio-icon-star"></i>
                                    <i class="xagio-icon xagio-icon-star"></i> <i class="xagio-icon xagio-icon-star-o"></i>
                                    <span class="prs-review-date"> on 02, Apr 2017</span>
                                </div>
                                <div class="prs-review-body">
                                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc tristique
                                    sollicitudin ligula, ut elementum ipsum tempor at. Integer laoreet dignissim
                                    eros, eu tincidunt leo. Ut finibus lectus quis elit cursus pulvinar. Fusce
                                    ornare, enim non convallis tincidunt, diam neque cursus tellus, sit amet
                                    fringilla ipsum metus eu sapien.
                                </div>
                                <div class="prs-review-author">
                                    <b>Michael</b>
                                    <br>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>




            </form>
        </div>

        <!-- Shortcodes -->
        <div class="xagio-tab-content">
            <div class="xagio-2-column-grid rescue-select-mode">
                <div class="xagio-column-1">

                    <div class="xagio-panel">
                        <h2 class="shortcode-title">[xagio_reviews_widget]</h2>
                        <div class="xagio-alert xagio-alert-primary">
                            <i class="xagio-icon xagio-icon-info"></i> Used to display the Review Widget where users can leave their reviews for your website (or the current page).
                        </div>
                        <h3 class="pop">Options:</h3>

                        <ul class="review-shortcodes-options">
                            <li><b>alpha_mode=1</b> (displays the Review Widget in transparent background mode)</li>
                            <li><b>popup_mode=1</b> (displays the Review Widget in button Popup Mode)</li>
                            <li><b>popup_text=1</b> (displays the popup button as a text link)</li>
                            <li><b>exit_popup=1</b> (displays widget when user is trying to exit page. Only works with popup_mode enabled )</li>
                            <li><b>stars_only=1</b> (displays the Review Widget in Stars Only Mode)</li>
                        </ul>

                        <h3 class="pop">Example:</h3>

                        <div class="xagio-alert xagio-alert-ghost">
                            [xagio_reviews_widget popup_mode=1 popup_text=1 stars_only=1 alpha_mode=1 exit_popup=1]
                        </div>

                    </div>

                </div>
                <div class="xagio-column-2">

                    <div class="xagio-panel">
                        <h2 class="shortcode-title">[xagio_reviews]</h2>

                        <div class="xagio-alert xagio-alert-primary">
                            <i class="xagio-icon xagio-icon-info"></i> Used to display reviews left by users on specific page or post.
                        </div>

                        <h3 class="pop">Options:</h3>

                        <ul class="review-shortcodes-options">
                            <li><b>aggregate_rating=1</b> (shows average review rating and total left reviews)</li>
                            <li><b>random_reviews=1</b> (displays random reviews)</li>
                            <li><b>limit_reviews=1</b> (limits the number of reviews displayed)</li>
                            <li><b>limit_reviews_number=5</b> (number of reviews to be displayed if limit_reviews is activated)
                            </li>
                        </ul>

                        <h3 class="pop">Example:</h3>

                        <div class="xagio-alert xagio-alert-ghost">
                            [xagio_reviews aggregate_rating=1 random_reviews=1 limit_reviews=1 limit_reviews_number=5]
                        </div>
                    </div>

                </div>
            </div>
    </div>

    <!-- Edit Review -->
    <dialog id="edit_review" class="xagio-modal xagio-modal-lg">
        <div class="xagio-modal-header">
            <h3 class="xagio-modal-title">
                <i class="xagio-icon xagio-icon-upload"></i> Add New Review
            </h3>
            <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
        </div>
        <div class="xagio-modal-body">
            <form class="edit_review_submit">

                <input type="hidden" class="review-action" name="action" value="xagio_editReview"/>
                <input type="hidden" class="review-id" id="review-id" name="id" value="0"/>
                <input type="hidden" class="review-approved" id="review-approved" name="approved" value="1"/>
                <?php wp_nonce_field('xagio_editReview', '_wpnonce'); ?>

                <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                    <div>
                        <div class="modal-label">Name</div>
                        <input type="text" id="review-name" class="xagio-input-text-mini" name="name" placeholder="eg. John Doe" data-placeholder="eg. John Doe" data-alt-placeholder="Your Name" required>

                        <div class="modal-label xagio-margin-top-medium">Telephone</div>
                        <input type="text" id="review-telephone" class="xagio-input-text-mini" name="telephone" placeholder="eg. +1 800 500 4025" data-placeholder="eg. +1 800 500 4025" data-alt-placeholder="Your Phone Number">

                        <div class="modal-label xagio-margin-top-medium">Email ID</div>
                        <input type="text" id="review-email" class="xagio-input-text-mini" name="email" placeholder="eg. johndoe@email.com" data-placeholder="eg. johndoe@email.com" data-alt-placeholder="E-Mail Address">

                        <div class="modal-label xagio-margin-top-medium">Rating</div>
                        <input type="text" id="review-rating" class="xagio-input-text-mini" name="rating" placeholder="eg. 1 - 5" required>
                    </div>
                    <div>
                        <div class="modal-label">Title</div>
                        <input type="text" id="review-title" class="xagio-input-text-mini" name="title" placeholder="eg. I like this product" data-placeholder="eg. I like this product" data-alt-placeholder="Your Title">

                        <div class="modal-label xagio-margin-top-medium">Location</div>
                        <input type="text" id="review-location" class="xagio-input-text-mini" name="location" placeholder="eg. New York" data-placeholder="eg. New York" data-alt-placeholder="Your Location">

                        <div class="modal-label xagio-margin-top-medium">Age</div>
                        <input type="text" id="review-age" class="xagio-input-text-mini" name="age" placeholder="eg. 32" data-placeholder="eg. 32" data-alt-placeholder="Your Age">

                        <div class="modal-label xagio-margin-top-medium">Review Date</div>
                        <input type="text" id="review-date" class="xagio-input-text-mini" name="date" data-uk-datepicker="{format:'YYYY-MM-DD'}">
                    </div>
                </div>

                <div class="modal-label">Review</div>
                <textarea rows="7" id="review-review" name="review" class="xagio-input-textarea" placeholder="eg. This is a really nice product" data-placeholder="eg. This is a really nice product" data-alt-placeholder="Your Review" required></textarea>


                <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-large">
                    <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
                    <button type="submit" class="xagio-button xagio-button-primary"><i class="xagio-icon xagio-icon-edit"></i> Save Changes</button>
                </div>

            </form>
        </div>


    </dialog>

</div> <!-- .wrap -->

<!-- Available Pages -->
<dialog id="availablePagesModal" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title">
            <i class="xagio-icon xagio-icon-file"></i> Pages/Posts
        </h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <div class="uk-modal-body">
            <input type="hidden" id="selectedReviews" name="selectedReviews" value="">
            <table class="wp-list-table widefat fixed striped postsTable2" cellspacing="0" width="100%">
                <thead>
                <tr>
                    <th width="70">Action</th>
                    <th>Title</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td colspan="4" class="xagio-text-center"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Loading</td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <th width="70">Action</th>
                    <th>Title</th>
                    <th>Date</th>
                </tr>
                </tfoot>
            </table>
        </div>


        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-large">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
        </div>
    </div>
</dialog>

<!-- Available Pages for Cloning -->
<dialog id="availablePagesCloneModal" class="xagio-modal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title">
            <i class="xagio-icon xagio-icon-file"></i> Pages/Posts
        </h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <input type="hidden" id="selectedReviewId" name="selectedReviewId" value="">
        <table class="wp-list-table widefat fixed striped postsCloneTable" cellspacing="0" width="100%">
            <thead>
            <tr>
                <td class="check-column"><input class="select-posts-all" type="checkbox"></td>
                <th>Title</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td colspan="4" class="xagio-text-center"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Loading</td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td class="check-column"><input class="select-posts-all" type="checkbox"></td>
                <th>Title</th>
                <th>Date</th>
            </tr>
            </tfoot>
        </table>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-large">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
            <button type="submit" class="xagio-button xagio-button-primary" id="cloneReview"><i class="xagio-icon xagio-icon-copy"></i> Clone</button>
        </div>
    </div>

</dialog>
