<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Panel-body template — renders the body of the single RingRobin panel.
 * Always a two-column grid. Three states (driven by $is_connected and
 * $rr_is_linked):
 *
 *   - Disconnected:           Left = Connect card,   Right = locked "What you'll get"
 *   - Connected, not linked:  Left = Link this site, Right = locked "Widgets & Numbers"
 *   - Connected, linked:      Left = This Site info, Right = Widgets + Numbers stack
 *
 * Used by:
 *   - modules/settings/page.php (initial render)
 *   - XAGIO_RINGROBIN::ajax_render_panel() (AJAX swap on every state change)
 *
 * Expected locals (set by caller before include):
 *   $is_connected          bool
 *   $rr_is_linked          bool
 *   $rr_site_domain        string
 *   $rr_show_conflict      bool
 *   $rr_scripts_tab_url    string
 *   $rr_campaign_name         string  (linked branch)
 *   $rr_campaign_settings_url string  (linked branch — empty when no campaign id)
 *   $rr_domain_name           string  (linked branch)
 *   $rr_is_verified           bool    (linked branch)
 *   $rr_last_checked_at    int     (linked branch — unix timestamp)
 *   $rr_form_widgets       array   (linked branch)
 *   $rr_text_widgets       array   (linked branch)
 *   $rr_numbers            array   (linked branch)
 *   $rr_twilio             array   (linked branch — connected, connect_url)
 */

$rr_card_style = 'background:#f5f7fb; border-radius:10px; padding:20px;';
?>

<?php if (!empty($rr_show_conflict)) : ?>
    <div class="xagio-alert xagio-alert-danger xagio-margin-bottom-medium xagio-rr-conflict-notice">
        <p>
            <strong><?php esc_html_e('Manual RingRobin script detected', 'xagio-seo'); ?></strong>
        </p>
        <p class="xagio-margin-top-small">
            <?php
            printf(
                /* translators: %s: link to the Scripts settings tab */
                esc_html__('A RingRobin tracking script was found in your %s. Automatic tracking is paused to prevent duplicate script tags. Remove the manual <script> tag from the Scripts settings to re-enable automatic tracking from this plugin.', 'xagio-seo'),
                '<a href="' . esc_url($rr_scripts_tab_url) . '">' . esc_html__('Scripts settings', 'xagio-seo') . '</a>'
            );
            ?>
        </p>
        <div class="xagio-flex-right xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline xagio-rr-dismiss-conflict">
                <i class="xagio-icon xagio-icon-close"></i>
                <?php esc_html_e('Dismiss this notice', 'xagio-seo'); ?>
            </button>
        </div>
    </div>
<?php endif; ?>

<div class="xagio-2-column-grid">

    <!-- LEFT COLUMN -->
    <div class="xagio-column-1">

        <?php if (empty($is_connected)) : ?>

            <div style="<?php echo esc_attr($rr_card_style); ?>">
                <h3 class="pop" style="margin-top:0;"><?php esc_html_e('Connect to RingRobin', 'xagio-seo'); ?></h3>
                <p class="xagio-margin-bottom-medium">
                    <?php esc_html_e('Call tracking and form attribution for your sites.', 'xagio-seo'); ?>
                </p>
                <label for="xagio_rr_api_key" class="xagio-flex-row xagio-align-center" style="gap:6px;">
                    <strong><?php esc_html_e('API Key', 'xagio-seo'); ?></strong>
                    <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="<?php esc_attr_e('Generate an API key from RingRobin Settings.', 'xagio-seo'); ?>"></i>
                </label>
                <input
                    type="password"
                    id="xagio_rr_api_key"
                    class="xagio-input-text-mini"
                    placeholder="rr_live_..."
                    autocomplete="off"
                    spellcheck="false" />
                <div class="xagio-flex-right xagio-margin-top-medium">
                    <button type="button" class="xagio-button xagio-button-primary xagio-rr-connect">
                        <i class="xagio-icon xagio-icon-check"></i>
                        <?php esc_html_e('Connect', 'xagio-seo'); ?>
                    </button>
                </div>
                <p class="description xagio-margin-top-small">
                    <?php
                    printf(
                        /* translators: %s: link to RingRobin settings */
                        esc_html__('Generate a key from %s.', 'xagio-seo'),
                        '<a href="' . esc_url('https://ringrobin.net/app/settings') . '" target="_blank" rel="noopener noreferrer">'
                            . esc_html__('RingRobin &rarr; Settings &rarr; API Keys', 'xagio-seo')
                            . '</a>'
                    );
                    ?>
                </p>
            </div>

        <?php elseif (empty($rr_is_linked)) : ?>

            <div style="<?php echo esc_attr($rr_card_style); ?>">
                <h3 class="pop" style="margin-top:0;"><?php esc_html_e('This Site', 'xagio-seo'); ?></h3>
                <p class="xagio-margin-bottom-medium">
                    <?php esc_html_e('Link this site to a RingRobin campaign to automatically inject the tracking snippet on the front-end.', 'xagio-seo'); ?>
                </p>
                <strong><?php esc_html_e('Site Domain', 'xagio-seo'); ?></strong>
                <input
                    type="text"
                    class="xagio-input-text-mini"
                    id="xagio-rr-site-domain"
                    value="<?php echo esc_attr($rr_site_domain); ?>"
                    readonly />
                <div class="xagio-flex-right xagio-margin-top-medium">
                    <button type="button" class="xagio-button xagio-button-primary xagio-rr-link-open">
                        <i class="xagio-icon xagio-icon-check"></i>
                        <?php esc_html_e('Link to RingRobin', 'xagio-seo'); ?>
                    </button>
                </div>
            </div>

        <?php else : ?>

            <div style="<?php echo esc_attr($rr_card_style); ?>" id="xagio-rr-this-site">
                <h3 class="pop" style="margin-top:0;"><?php esc_html_e('This Site', 'xagio-seo'); ?></h3>
                <div class="xagio-margin-bottom-small">
                    <strong><?php esc_html_e('Campaign:', 'xagio-seo'); ?></strong>
                    <span class="xagio-rr-campaign-name"><?php echo esc_html($rr_campaign_name); ?></span>
                </div>
                <div class="xagio-margin-bottom-small">
                    <strong><?php esc_html_e('Domain:', 'xagio-seo'); ?></strong>
                    <span class="xagio-rr-domain-name"><?php echo esc_html($rr_domain_name); ?></span>
                </div>
                <div class="xagio-margin-bottom-medium">
                    <strong><?php esc_html_e('Status:', 'xagio-seo'); ?></strong>
                    <span class="xagio-rr-status">
                        <?php if (!empty($rr_is_verified)) : ?>
                            <span style="color:#00bf63; font-weight:600;"><?php esc_html_e('Verified ✓', 'xagio-seo'); ?></span>
                            <?php if (!empty($rr_last_checked_at) && $rr_last_checked_at > 0) : ?>
                                <span class="xagio-rr-last-checked" style="color:#9ca3af; margin-left:8px; font-size:12px;">
                                    <?php
                                    printf(
                                        /* translators: %s: human-readable time difference */
                                        esc_html__('last checked %s ago', 'xagio-seo'),
                                        esc_html(human_time_diff($rr_last_checked_at, time()))
                                    );
                                    ?>
                                </span>
                            <?php endif; ?>
                        <?php else : ?>
                            <span class="xagio-rr-status-text"><?php esc_html_e('Checking…', 'xagio-seo'); ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="xagio-flex-right xagio-flex-gap-medium">
                    <button type="button" class="xagio-button xagio-button-outline xagio-rr-unlink">
                        <i class="xagio-icon xagio-icon-link-off"></i>
                        <?php esc_html_e('Unlink', 'xagio-seo'); ?>
                    </button>
                    <?php if (empty($rr_is_verified)) : ?>
                        <button type="button" class="xagio-button xagio-button-primary xagio-rr-verify">
                            <i class="xagio-icon xagio-icon-check"></i>
                            <?php esc_html_e('Verify Installation', 'xagio-seo'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>

    </div>

    <!-- RIGHT COLUMN -->
    <div class="xagio-column-2">

        <?php if (empty($is_connected)) : ?>

            <div style="background:linear-gradient(135deg,#eef5ff 0%,#f5f7fb 100%); border:1px solid #d6e4ff; border-radius:10px; padding:24px;">
                <div class="xagio-flex-row xagio-align-center" style="gap:12px; margin-bottom:14px;">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width:42px; height:42px; background:#1a4674; border-radius:50%; flex-shrink:0;">
                        <i class="xagio-icon xagio-icon-plug" style="color:#fff; font-size:20px;"></i>
                    </span>
                    <h3 class="pop" style="margin:0;"><?php esc_html_e("What you'll get", 'xagio-seo'); ?></h3>
                </div>
                <p class="xagio-margin-bottom-small" style="color:#545454;">
                    <?php esc_html_e('Connect RingRobin to unlock:', 'xagio-seo'); ?>
                </p>
                <ul style="margin:0; padding:0; list-style:none; color:#1a4674; font-size:14px;">
                    <li style="display:flex; align-items:flex-start; gap:8px; margin:8px 0;">
                        <i class="xagio-icon xagio-icon-check" style="color:#00bf63; margin-top:2px; flex-shrink:0;"></i>
                        <span><?php esc_html_e('Link a campaign to this site', 'xagio-seo'); ?></span>
                    </li>
                    <li style="display:flex; align-items:flex-start; gap:8px; margin:8px 0;">
                        <i class="xagio-icon xagio-icon-check" style="color:#00bf63; margin-top:2px; flex-shrink:0;"></i>
                        <span><?php esc_html_e('Create form and click-to-text widgets', 'xagio-seo'); ?></span>
                    </li>
                    <li style="display:flex; align-items:flex-start; gap:8px; margin:8px 0;">
                        <i class="xagio-icon xagio-icon-check" style="color:#00bf63; margin-top:2px; flex-shrink:0;"></i>
                        <span><?php esc_html_e('Buy a tracking phone number', 'xagio-seo'); ?></span>
                    </li>
                    <li style="display:flex; align-items:flex-start; gap:8px; margin:8px 0;">
                        <i class="xagio-icon xagio-icon-check" style="color:#00bf63; margin-top:2px; flex-shrink:0;"></i>
                        <span><?php esc_html_e('Drag-and-drop widgets into Elementor or Gutenberg pages', 'xagio-seo'); ?></span>
                    </li>
                </ul>
            </div>

        <?php elseif (empty($rr_is_linked)) : ?>

            <div style="background:#f5f7fb; border:1px dashed #c5c5c5; border-radius:10px; padding:24px;">
                <div class="xagio-flex-row xagio-align-center" style="gap:12px; margin-bottom:10px;">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width:42px; height:42px; background:#fff; border:1px solid #c5c5c5; border-radius:50%; flex-shrink:0;">
                        <i class="xagio-icon xagio-icon-link-off" style="color:#9ca3af; font-size:20px;"></i>
                    </span>
                    <h3 class="pop" style="margin:0;"><?php esc_html_e('Widgets & Numbers', 'xagio-seo'); ?></h3>
                </div>
                <p style="margin:0; color:#545454;">
                    <?php esc_html_e('Link this site to a campaign first to manage form/text widgets and phone numbers here.', 'xagio-seo'); ?>
                </p>
            </div>

        <?php else : ?>

            <div style="<?php echo esc_attr($rr_card_style); ?>" id="xagio-rr-widgets">
                <h3 class="pop" style="margin-top:0;"><?php esc_html_e('Form Widgets', 'xagio-seo'); ?></h3>
                <div class="xagio-rr-widgets-list" data-widget-type="form">
                    <?php if (empty($rr_form_widgets)) : ?>
                        <p class="xagio-rr-empty"><?php esc_html_e('No form widgets associated with this site yet.', 'xagio-seo'); ?></p>
                    <?php else : ?>
                        <?php foreach ($rr_form_widgets as $widget) : ?>
                            <div class="xagio-rr-widget-row xagio-flex-row xagio-align-center xagio-space-between xagio-margin-bottom-small" data-id="<?php echo esc_attr($widget['id']); ?>">
                                <span class="xagio-rr-widget-name"><?php echo esc_html(!empty($widget['name']) ? $widget['name'] : $widget['id']); ?></span>
                                <div class="xagio-flex-row xagio-flex-gap-small">
                                    <?php
                                    $rr_widget_btn_style = 'padding:8px 14px; border-radius:5px; min-width:110px; display:inline-flex; align-items:center; justify-content:center; gap:6px; box-sizing:border-box; height:36px;';
                                    ?>
                                    <?php if (!empty($rr_campaign_settings_url)) : ?>
                                        <a href="<?php echo esc_url($rr_campaign_settings_url); ?>" target="_blank" rel="noopener noreferrer" class="xagio-button xagio-button-outline" style="<?php echo esc_attr($rr_widget_btn_style); ?>" data-xagio-tooltip data-xagio-title="<?php esc_attr_e('Edit on RingRobin', 'xagio-seo'); ?>">
                                            <i class="xagio-icon xagio-icon-external-link"></i>
                                            <?php esc_html_e('Edit', 'xagio-seo'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="xagio-button xagio-button-outline xagio-rr-widget-remove" data-id="<?php echo esc_attr($widget['id']); ?>" style="<?php echo esc_attr($rr_widget_btn_style); ?>" data-xagio-tooltip data-xagio-title="<?php esc_attr_e('Remove from this site', 'xagio-seo'); ?>">
                                        <i class="xagio-icon xagio-icon-close"></i>
                                        <?php esc_html_e('Remove', 'xagio-seo'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="xagio-flex-right xagio-margin-top-small">
                    <button type="button" class="xagio-button xagio-button-primary xagio-button-small xagio-rr-widget-create" data-widget-type="form">
                        <i class="xagio-icon xagio-icon-plus"></i>
                        <?php esc_html_e('Create Form widget', 'xagio-seo'); ?>
                    </button>
                </div>
            </div>

            <div style="<?php echo esc_attr($rr_card_style); ?> margin-top:16px;">
                <h3 class="pop" style="margin-top:0;"><?php esc_html_e('Text Widgets', 'xagio-seo'); ?></h3>
                <div class="xagio-rr-widgets-list" data-widget-type="text">
                    <?php if (empty($rr_text_widgets)) : ?>
                        <p class="xagio-rr-empty"><?php esc_html_e('No text widgets associated with this site yet.', 'xagio-seo'); ?></p>
                    <?php else : ?>
                        <?php foreach ($rr_text_widgets as $widget) : ?>
                            <div class="xagio-rr-widget-row xagio-flex-row xagio-align-center xagio-space-between xagio-margin-bottom-small" data-id="<?php echo esc_attr($widget['id']); ?>">
                                <span class="xagio-rr-widget-name"><?php echo esc_html(!empty($widget['name']) ? $widget['name'] : $widget['id']); ?></span>
                                <div class="xagio-flex-row xagio-flex-gap-small">
                                    <?php
                                    $rr_widget_btn_style = 'padding:8px 14px; border-radius:5px; min-width:110px; display:inline-flex; align-items:center; justify-content:center; gap:6px; box-sizing:border-box; height:36px;';
                                    ?>
                                    <?php if (!empty($rr_campaign_settings_url)) : ?>
                                        <a href="<?php echo esc_url($rr_campaign_settings_url); ?>" target="_blank" rel="noopener noreferrer" class="xagio-button xagio-button-outline" style="<?php echo esc_attr($rr_widget_btn_style); ?>" data-xagio-tooltip data-xagio-title="<?php esc_attr_e('Edit on RingRobin', 'xagio-seo'); ?>">
                                            <i class="xagio-icon xagio-icon-external-link"></i>
                                            <?php esc_html_e('Edit', 'xagio-seo'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="xagio-button xagio-button-outline xagio-rr-widget-remove" data-id="<?php echo esc_attr($widget['id']); ?>" style="<?php echo esc_attr($rr_widget_btn_style); ?>" data-xagio-tooltip data-xagio-title="<?php esc_attr_e('Remove from this site', 'xagio-seo'); ?>">
                                        <i class="xagio-icon xagio-icon-close"></i>
                                        <?php esc_html_e('Remove', 'xagio-seo'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="xagio-flex-right xagio-margin-top-small">
                    <button type="button" class="xagio-button xagio-button-primary xagio-button-small xagio-rr-widget-create" data-widget-type="text">
                        <i class="xagio-icon xagio-icon-plus"></i>
                        <?php esc_html_e('Create Text widget', 'xagio-seo'); ?>
                    </button>
                </div>
            </div>

            <div style="<?php echo esc_attr($rr_card_style); ?> margin-top:16px;" id="xagio-rr-numbers">
                <h3 class="pop" style="margin-top:0;"><?php esc_html_e('Phone Numbers', 'xagio-seo'); ?></h3>
                <?php if (empty($rr_twilio['connected'])) : ?>
                    <p class="xagio-margin-bottom-small">
                        <?php esc_html_e('To buy a phone number, connect Twilio in your RingRobin account.', 'xagio-seo'); ?>
                    </p>
                    <div class="xagio-flex-right">
                        <a href="<?php echo esc_url($rr_twilio['connect_url']); ?>" target="_blank" rel="noopener noreferrer" class="xagio-button xagio-button-primary xagio-button-small">
                            <i class="xagio-icon xagio-icon-external-link"></i>
                            <?php esc_html_e('Connect Twilio in RingRobin', 'xagio-seo'); ?>
                        </a>
                    </div>
                <?php else : ?>
                    <div class="xagio-rr-numbers-list">
                        <?php if (empty($rr_numbers)) : ?>
                            <p class="xagio-rr-empty"><?php esc_html_e('No phone numbers attached to this site yet.', 'xagio-seo'); ?></p>
                        <?php else : ?>
                            <?php foreach ($rr_numbers as $number) : ?>
                                <div class="xagio-rr-number-row xagio-flex-row xagio-align-center xagio-space-between xagio-margin-bottom-small" data-id="<?php echo esc_attr($number['id']); ?>">
                                    <div>
                                        <strong class="xagio-rr-number-friendly"><?php echo esc_html(!empty($number['friendly_name']) ? $number['friendly_name'] : $number['phone_number']); ?></strong>
                                        <?php if (!empty($number['locality']) || !empty($number['region'])) : ?>
                                            <span style="color:#9ca3af; margin-left:8px; font-size:12px;">
                                                <?php echo esc_html(trim((!empty($number['locality']) ? $number['locality'] : '') . (!empty($number['region']) ? ', ' . $number['region'] : ''))); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($number['status']) && $number['status'] !== 'active') : ?>
                                            <span class="xagio-alert xagio-alert-danger" style="display:inline-block; padding:2px 8px; margin-left:8px; font-size:11px;">
                                                <?php echo esc_html($number['status']); ?>
                                                <?php if (!empty($number['status_reason'])) : ?>
                                                    — <?php echo esc_html($number['status_reason']); ?>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="color:#6b7280; font-size:12px;">
                                        <?php echo esc_html((!empty($number['price_monthly']) ? $number['price_monthly'] : '0.00') . ' ' . (!empty($number['currency']) ? $number['currency'] : 'USD') . ' / mo'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="xagio-flex-right xagio-margin-top-small">
                        <button type="button" class="xagio-button xagio-button-primary xagio-button-small xagio-rr-number-search">
                            <i class="xagio-icon xagio-icon-search"></i>
                            <?php esc_html_e('Search & buy a number', 'xagio-seo'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </div>

</div>
