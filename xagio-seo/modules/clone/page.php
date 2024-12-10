<?php
/**
 * Type: SUBMENU
 * Page_Title: Clone
 * Menu_Title: Clone
 * Capability: manage_options
 * Slug: xagio-clone
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: xagio_clone
 * Css: xagio_clone
 * Position: 12
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


$MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');
?>
<div class="xagio-main-header">
    <img class="logo-image repo-xagio" src="<?php echo  esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        Clone Website
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
            <span>By using Xagio Clone, you will be able to effortlessly transfer whole websites.</span>
            <i class="xagio-icon xagio-icon-arrow-down"></i>
        </h3>
        <div class="xagio-accordion-content">
            <div>
                <div class="xagio-accordion-panel"></div>
            </div>
        </div>
    </div>


    <div class="xagio-panel">
        <h3 class="xagio-panel-title">Clone Entire Website</h3>

        <form class="verify">

            <input type="hidden" name="action" value="xagio_verify_connection"/>

            <div class="clone-grid xagio-margin-bottom-medium">

                <div>
                    <input name="url" type="url" class="xagio-input-text-mini clone-url"
                           placeholder="Website to clone from... eg. https://abc.com" required value=""/>
                </div>
                <div class="clone-grid-inner">
                    <button type="submit"
                            class="verify-button xagio-button xagio-button-primary"><i
                                class="xagio-icon xagio-icon-plug"></i> Verify Connection
                    </button>

                    <button type="button" class="clone-button xagio-button xagio-button-primary"
                            disabled><i class="xagio-icon xagio-icon-check"></i> Start Cloning
                    </button>
                </div>

            </div>

            <div class="xagio-progress xagio-progress-green xagio-margin-bottom-medium" style="display: none">
                <div class="xagio-progress-bar">...</div>
            </div>

            <div class="output-window">
                <p class="output-status"><i class="xagio-icon xagio-icon-info"></i> Waiting for action...</p>
            </div>

        </form>

    </div>


    <!-- Clone Notice Modal -->
    <dialog id="cloneNotice" class="xagio-modal">
        <div class="xagio-modal-header">
            <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-info"></i> Cloning Requirements</h3>
            <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
        </div>
        <div class="xagio-modal-body">
            <div class="xagio-alert xagio-alert-primary" data-uk-alert="" style="margin-bottom: 5px;">
                <a href="" class="uk-alert-close uk-close"></a>
                <p>
                    <i class="xagio-icon xagio-icon-warning"></i> Since <b>xagio Clone</b> is a robust feature that will
                    automatically copy over your entire website, it will have certain requirements
                    in order to run smoothly. Before you start cloning, make sure that all of the requirements are met
                    and checks have been verified. <br><br>

                <b>In order for xagio Clone to work correctly, please make sure that BOTH websites meet the
                    requirements from the list below.</b> <br><br>

                Also note that these are <b>Soft-Requirements</b>, meaning that we won't stop you from trying to
                Clone a website, even if it doesn't meet all of these requirements, just
                because it can work out depending on many different factors.
            </div>

            <table class="xagio-clone-table">
                <tbody>
                <tr>
                    <td>PHP Maximum Execution Time:</td>
                    <?php
                    $execution = ini_get('max_execution_time');
                    if ($execution < 120) {
                    ?>
                    <td>
                        <?php echo esc_html($execution); ?> <i class="xagio-icon xagio-icon-close uk-text-danger"></i>
                    </td>
                </tr>
                <tr class="danger">
                    <td colspan="2">It's important to have <kbd>max_execution_time</kbd> in <kbd>php.ini</kbd> set to at
                        least <kbd>120</kbd> seconds, since process of creating backups
                        is often something that takes some time and in the event of smaller
                        <kbd>max_execution_time</kbd> values, PHP tends to time out and so the Clone process fails.
                    </td>
                </tr>
                <?php
                } else {
                    ?>
                    <td>
                        <?php echo esc_html($execution); ?> <i class="xagio-icon xagio-icon-check uk-text-success"></i>
                    </td>
                    </tr>
                    <?php
                }
                ?>

                <tr>
                    <td>PHP Memory Limit:</td>
                    <?php
                    $memory_limit = ini_get('memory_limit');
                    if ($memory_limit != '-1' && str_replace('MB', '', $memory_limit) < 256) {
                    ?>
                    <td>
                        <?php echo esc_html($memory_limit); ?> <i class="xagio-icon xagio-icon-close uk-text-danger"></i>
                    </td>
                </tr>
                    <tr class="danger">
                        <td colspan="2">It's important to have <kbd>memory_limit</kbd> in <kbd>php.ini</kbd> set to at
                            least <kbd>256</kbd> Megabytes, but lower values will work as well,
                            depending on the final size of your website.
                        </td>
                    </tr>
                <?php
                } else {
                    ?>
                    <td>
                        <?php echo ($memory_limit == '-1') ? 'Unlimited' : esc_html($memory_limit); ?> <i
                                class="xagio-icon xagio-icon-check uk-text-success"></i>
                    </td>
                    </tr>
                    <?php
                }
                ?>

                <tr>
                    <td>PHP cURL Module:</td>
                    <td><?php echo  (function_exists('curl_init')) ? '<i class="xagio-icon xagio-icon-check uk-text-success"></i>' : '<i class="xagio-icon xagio-icon-close uk-text-danger"></i>'; ?></td>
                </tr>
                <tr>
                    <td>PHP ZipArchive Module:</td>
                    <td><?php echo  (class_exists('ZipArchive')) ? '<i class="xagio-icon xagio-icon-check uk-text-success"></i>' : '<i class="xagio-icon xagio-icon-close uk-text-danger"></i>'; ?></td>
                </tr>
                <tr>
                    <td>Using CloudFlare/CDN:</td>
                    <td><i class="xagio-icon xagio-icon-info"></i></td>
                </tr>
                <tr>
                    <td colspan="2">Using <b>CloudFlare</b> should be fine in most cases, where the websites that are
                        being cloned are small in size, but if you try to clone a website with larger content
                        with <b>xagio Clone</b> while the website is behind <b>CloudFlare</b>, you will have issues with
                        imposed timeouts that are controled by <b>CloudFlare</b>, meaning that files won't have enough
                        time to be transfered because of <b>CloudFlare</b> limits.
                    </td>
                </tr>
                </tbody>
            </table>

            <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
                <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> I Understand, and I have checked the Requirements.</button>
            </div>
        </div>
    </dialog>

</div> <!-- .wrap -->

