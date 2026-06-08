<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Header-band template — renders the right side of the RingRobin panel
 * title row. Empty when disconnected; compact "Connected as X" row +
 * Disconnect button when connected.
 *
 * Used by:
 *   - modules/settings/page.php (initial render)
 *   - XAGIO_RINGROBIN::ajax_render_panel() (AJAX swap on connect/disconnect)
 *
 * Expected locals (set by caller before include):
 *   $is_connected     bool
 *   $connected_user   array|null  (set when $is_connected is true)
 */
?>

<?php if (!empty($is_connected)) : ?>

    <div class="xagio-rr-header-band xagio-flex-row xagio-align-center xagio-flex-gap-small">
        <span class="xagio-flex-row xagio-align-center xagio-flex-gap-small" style="color:#545454; font-size:14px;">
            <i class="xagio-icon xagio-icon-check" style="color:#00bf63; font-size:16px;"></i>
            <?php
            printf(
                /* translators: %s: user's display name */
                esc_html__('Connected as %s', 'xagio-seo'),
                '<strong style="color:#1a4674;">' . esc_html(isset($connected_user['display_name']) ? $connected_user['display_name'] : '') . '</strong>'
            );
            ?>
            <?php if (!empty($connected_user['account_type'])) : ?>
                <span style="background: rgba(0, 191, 99, 0.15); color:#008f4a; padding:2px 10px; border-radius:12px; font-size:12px; font-weight:500;">
                    <?php echo esc_html($connected_user['account_type']); ?>
                </span>
            <?php endif; ?>
        </span>
        <button type="button" class="xagio-button xagio-button-outline xagio-button-small xagio-rr-disconnect" style="margin-left:12px;">
            <i class="xagio-icon xagio-icon-close"></i>
            <?php esc_html_e('Disconnect', 'xagio-seo'); ?>
        </button>
    </div>

<?php endif; ?>
