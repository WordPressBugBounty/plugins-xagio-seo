<?php
/**
 * Type: SUBMENU
 * Page_Title: Dashboard
 * Menu_Title: Dashboard
 * Capability: manage_options
 * Slug: xagio-dashboard
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: xagio_vimeo,xagio_wizard,xagio_dashboard
 * Css: xagio_animate,xagio_wizard,xagio_dashboard
 * Position: 1
 * Version: 1.0.0
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

global $wpdb;

$MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');

?>

<div class="xagio-main-header xagio-main-header-big-gaps">
    <img class="logo-image repo-xagio" src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        Dashboard
    </h2>
</div>

<!-- HTML STARTS HERE -->
<div class="xagio-content-wrapper">

    <?php
    XAGIO_MODEL_DASHBOARD::checkRequirements();
    ?>

    <?php if (!XAGIO_CONNECTED): ?>
        <div class="xagio-border-panel xagio-main-panel">
            <div class="xagio-main-panel-left">
                <div class="xagio-panel xagio-panel-border">

                    <div class="xagio-margin-bottom-large">
                        <h1 class="xagio-connect-h1">Connect with <img class="xagio-connect-logo"
                                                                       src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logo-xagio-black.webp"/>
                            Cloud for Free</h1>
                        <p class="xagio-sub-heading">& Experience An Entirely New Way To Do SEO</p>
                    </div>

                    <div class="xagio-connect">

                        <div>
                            <?php if (!XAGIO_CONNECTED): ?>

                                <div class="xagio-ranking-holder">
                                    <div class="xagio-website-rank">
                                        <div class="xagio-ranking-website-profile">
                                            <img src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp">
                                            <div class="xagio-ranking-website-name">
                                                <div><?php echo !empty(get_option('blogname')) ? esc_attr(get_option('blogname')) : "Your Website"; ?></div>
                                                <div><?php echo esc_attr(XAGIO_DOMAIN); ?></div>
                                            </div>
                                        </div>
                                        <div class="website-using-xagio-rank">RANK 1</div>
                                    </div>
                                    <div class="xagio-website-rank">
                                        <div>Competitor Website</div>
                                        <div>RANK 2</div>
                                    </div>
                                    <div class="xagio-website-rank">
                                        <div>Competitor Website</div>
                                        <div>RANK 3</div>
                                    </div>

                                </div>


                            <?php else: ?>
                                <h1 class="xagio-connect-h1">Your Account is Connected</h1>
                                <p class="xagio-connect-p xagio-margin-top-remove"><i
                                            class="xagio-icon xagio-icon-chart-line xagio-icon-blue"></i> Enjoy using all Xagio features</p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <form class="activate-account">

                                <input type="hidden" name="action"
                                       value="xagio_activate"/>

                                <input type="hidden" name="url" class="xagio-panel-url"
                                       value="<?php echo esc_url(XAGIO_PANEL_URL); ?>/signup"/>

                                <input type="hidden" name="redirect"
                                       value="/activate/<?php echo esc_attr(XAGIO_DOMAIN); ?>/"/>

                                <input type="text" name="email" <?php echo XAGIO_CONNECTED ? 'disabled' : ''; ?>
                                       class="xagio-input-text xagio-margin-bottom-small xagio-account-email"
                                       placeholder="Enter your email"
                                       value="<?php echo XAGIO_CONNECTED ? esc_attr(XAGIO_LICENSE_EMAIL) : ''; ?>"
                                       required/>

                                <?php if (!XAGIO_CONNECTED): ?>
                                    <button type="submit" class="xagio-button xagio-button-purple"
                                            data-text="Connect Now">Connect for Free Now
                                    </button>
                                <?php endif; ?>

                            </form>

                        </div>

                        <?php if (!XAGIO_CONNECTED): ?>
                            <div class="xagio-text-center">
                                <p class="xagio-has-account">Already Have An Account?</p>
                                <a class="xagio-connect-have-account"
                                   href="#">Click
                                    Here To Connect It</a>
                            </div>
                        <?php endif; ?>

                    </div>

                    <div class="xagio-welcome-tut">
                        <div class="xagio-button-welcome-play">
                            <span></span>
                        </div>
                        We've made a short video to explain you everything
                    </div>
                </div>
            </div>
            <div class="xagio-main-panel-right">
                <div>
                    <h2 class="xagio-benefits-h2">Get these additional benefits by connecting</h2>

                    <div class="xagio-benefits xagio-margin-bottom-large">
                        <div class="xagio-feature">
                            <i class="xagio-icon xagio-icon-info"></i>
                            <span>3 Free <a href="https://xagio.com/store/" target="_blank">xBank XAGS</a></span>
                        </div>
                        <div class="xagio-feature">
                            <i class="xagio-icon xagio-icon-info"></i>
                            <span>1 FREE Xagio <a href="https://xagio.com/templates/" target="_blank">Website Template</a> of your choice</span>
                        </div>
                        <div class="xagio-feature">
                            <i class="xagio-icon xagio-icon-info"></i>
                            <span>Free <a href="https://xagio.com/wordpress-management/" target="_blank">WordPress Management</a> Features</span>
                        </div>
                        <div class="xagio-feature">
                            <i class="xagio-icon xagio-icon-info"></i>
                            <span>Website <a href="https://xagio.com/cloud-dashboard/#uptime" target="_blank">Uptime Monitoring</a></span>
                        </div>
                        <div class="xagio-feature">
                            <i class="xagio-icon xagio-icon-info"></i>
                            <span>1 Click <a href="https://xagio.com/wordpress-management/#login" target="_blank">Logins</a> to WordPress, Panel, Host & Registrar</span>
                        </div>
                        <div class="xagio-feature">
                            <i class="xagio-icon xagio-icon-info"></i>
                            <span>Automated <a href="https://xagio.com/back-ups/" target="_blank">Website Backups</a></span>
                        </div>
                        <div class="xagio-feature">
                            <i class="xagio-icon xagio-icon-info"></i>
                            <span>Easily Build & Deploy <a href="https://xagio.com/schema-generator-json/" target="_blank">Schema</a></span>
                        </div>
                        <div class="xagio-feature">
                            <i class="xagio-icon xagio-icon-info"></i>
                            <span>Store & Deploy Favorite <a href="https://xagio.com/repository/" target="_blank">Themes & Plugins</a></span>
                        </div>
                        <div class="xagio-feature">
                            <i class="xagio-icon xagio-icon-info"></i>
                            <span>Create multiple <a href="https://xagio.com/sub-users/" target="_blank">Sub User Accounts</a></span>
                        </div>
                    </div>


                    <div class="xagio-person-review">
                        <svg class="xagio-review-quote" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <g>
                                <path class="st0"
                                      d="M119.472,66.59C53.489,66.59,0,120.094,0,186.1c0,65.983,53.489,119.487,119.472,119.487 c0,0-0.578,44.392-36.642,108.284c-4.006,12.802,3.135,26.435,15.945,30.418c9.089,2.859,18.653,0.08,24.829-6.389 c82.925-90.7,115.385-197.448,115.385-251.8C238.989,120.094,185.501,66.59,119.472,66.59z"/>
                                <path class="st0"
                                      d="M392.482,66.59c-65.983,0-119.472,53.505-119.472,119.51c0,65.983,53.489,119.487,119.472,119.487 c0,0-0.578,44.392-36.642,108.284c-4.006,12.802,3.136,26.435,15.945,30.418c9.089,2.859,18.653,0.08,24.828-6.389 C479.539,347.2,512,240.452,512,186.1C512,120.094,458.511,66.59,392.482,66.59z"/>
                            </g>
                        </svg>
                        <div class="xagio-review-head">
                            <img src="/wp-content/plugins/xagio-seo/assets/img/david-clark.webp" alt="">
                            <div class="name">
                                <span>David Clark</span>
                                <span>SEO</span>
                            </div>
                        </div>
                        <div class="xagio-review-content">
                            I use Xagio every day. I couldn't build sites now without Xagio! It makes it easier for me
                            to rank in Google, and the support and training is first rate.
                        </div>
                    </div>
                </div>
            </div>
        </div>


    <?php else: ?>

        <div class="xagio-border-panel xagio-margin-bottom-medium panel-connected">
            <div class="panel-connected-info">
                <img src="<?php echo esc_url(get_avatar_url(esc_attr(XAGIO_LICENSE_EMAIL)));?>">
                <div>
                    <p>Welcome,</p>
                    <p class="welcome-name"><?php echo esc_attr($MEMBERSHIP_INFO['first_name'] . ' ' . $MEMBERSHIP_INFO['last_name']); ?>

                        <?php if (XAGIO_CONNECTED) {
                            ?>
                            <a data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Disconnect Account"
                               type="button" class="xagio-circle-btn xagio-circle-btn-danger disconnect-account"><i class="xagio-icon xagio-icon-link-off"></i></a>
                            <?php
                        } ?>
                    </p>
                    <p class="welcome-email"><?php echo esc_attr(XAGIO_LICENSE_EMAIL) ?></p>
                </div>
            </div>

            <div class="xagio-buttons-connected">
                <a href="<?php echo esc_url(XAGIO_PANEL_URL); ?>" target="_blank" class="xagio-button xagio-button-purple xagio-button-connected" data-text="Access Dashboard">Access Dashboard</a>
                <?php if($MEMBERSHIP_INFO["membership"] === "Xagio AI Free") { ?>
                    <a href="https://xagio.com/?goto=wppremfeatures" target="_blank" class="xagio-button xagio-button-orange xagio-button-connected xagio-button-dashboard-link" data-text="Try our premium features">Try our premium features <i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></a>
                <?php } ?>
            </div>
        </div>

    <?php endif; ?>


    <?php if (XAGIO_CONNECTED): ?>
        <div class="xagio-border-panel second-panel">
            <h2 class="xagio-text-center"><b>“Getting Started”</b> Interactive Roadmaps</h2>
            <div class="xagio-welcome-reviews">
                <a href="https://xagio.com/getting-started/new-affiliate/" target="_blank" class="xagio-person-review xagio-person-review-gray xagio-gird-arrow-bottom-right">
                    <div>
                        <h3>New Affiliate</h3>
                        <p>Starting from scratch with a new Affiliate SEO site.</p>
                    </div>
                    <i class="xagio-icon xagio-icon-long-arrow-up"></i>
                </a>

                <a href="https://xagio.com/getting-started/existing-affiliate/" target="_blank" class="xagio-person-review xagio-person-review-gray xagio-gird-arrow-bottom-right">
                    <div>
                        <h3>Existing Affiliate</h3>
                        <p>Starting with an existing Affiliate SEO site.</p>
                    </div>
                    <i class="xagio-icon xagio-icon-long-arrow-up"></i>
                </a>
                <a href="https://xagio.com/getting-started/new-local/" target="_blank" class="xagio-person-review xagio-person-review-gray xagio-gird-arrow-bottom-right">
                    <div>
                        <h3>New Local</h3>
                        <p>Starting from scratch with a new Local SEO site.</p>
                    </div>
                    <i class="xagio-icon xagio-icon-long-arrow-up"></i>
                </a>
                <a href="https://xagio.com/getting-started/existing-local/" target="_blank" class="xagio-person-review xagio-person-review-gray xagio-gird-arrow-bottom-right">
                    <div>
                        <h3>Existing Local</h3>
                        <p>Starting with an existing Local SEO site.</p>
                    </div>
                    <i class="xagio-icon xagio-icon-long-arrow-up"></i>
                </a>

            </div>
        </div>
    <?php endif; ?>

    <div class="xagio-welcome-footer xagio-margin-top-medium">
        <div class="xagio-border-panel xagio-panel-special">
            <img class="xagio-care-logo-img" src="/wp-content/plugins/xagio-seo/assets/img/logos/xagio_care_logo.webp">
            <p>We Will Fix Your WordPress & Hosting Issues</p>
            <a href="https://xagiocare.com/#pricing" target="_blank" type="button" class="xagio-care-button-orange">View Plans & Pricing</a>
        </div>
        <div class="xagio-border-panel">
            <h3>Xagio Store</h3>
            <p>Purchase non-expiring XAGS and Xagio templates!</p>
            <a href="https://xagio.com/store/" target="_blank" type="button" class="xagio-button xagio-button-purple" data-text="Visit Store">Visit Store</a>
        </div>
        <div class="xagio-border-panel">
            <h3>Earn Rewards</h3>
            <p>Earn free Xagio rewards simply for spreading the word!</p>
            <a href="https://xagio.com/earn-credits/" target="_blank" type="button" class="xagio-button xagio-button-purple" data-text="Earn Free Rewards">Earn Free Rewards</a>
        </div>
        <div class="xagio-border-panel">
            <h3>Xagio Affiliate</h3>
            <p>Earn Commission telling people about how amazing Xagio is.</p>
            <a href="https://xagio.com/affiliates/" target="_blank" type="button" class="xagio-button xagio-button-purple" data-text="Become An Affiliate">Become An Affiliate</a>
        </div>
        <div class="xagio-border-panel">
            <h3>Xagio Templates</h3>
            <p>Browse our library of amazing website templates, your first one is Free!</p>
            <a href="https://xagio.com/templates/" target="_blank" type="button" class="xagio-button xagio-button-purple" data-text="Browse Templates">Browse Templates</a>
        </div>
        <div class="xagio-border-panel">
            <h3>Chrome Extension</h3>
            <p>Grab our Free Chrome Extension and connect your site directly to ChatGPT</p>
            <a href="https://chromewebstore.google.com/detail/xagio-ai-integrate-chatgp/igibfmljolpknjbpofgekhcnmfaipgli" target="_blank" type="button" class="xagio-button xagio-button-purple" data-text="Download Extension">Download Extension</a>
        </div>
        <div class="xagio-border-panel">
            <?php if (XAGIO_CONNECTED): ?>
                <h3>Live Support</h3>
                <p>Chat live with someone on our support team for faster resolutions.</p>
                <a target="popup" rel="noopener noreferrer" class="xagio-button xagio-button-purple"
                   data-text="Let's Talk"
                   onclick="window.open('https://tawk.to/chat/5f9af4237f0a8e57c2d8421e/default','popup','width=600,height=600'); return false;"
                   href="https://tawk.to/chat/5f9af4237f0a8e57c2d8421e/default">Start Chat</a>
            <?php else: ?>
                <h3>Need Help?</h3>
                <p>Have questions or need help? Be free to open a support ticket.</p>
                <a href="https://support.xagio.net/" target="_blank" type="button" class="xagio-button xagio-button-purple" data-text="Contact Support">Contact Support</a>
            <?php endif; ?>
        </div>
        <div class="xagio-border-panel">
            <h3>Xagio Blog</h3>
            <p>Visit our Blog and learn the best ways to get the most out of Xagio</p>
            <a href="https://xagio.com/blog/" target="_blank" type="button" class="xagio-button xagio-button-purple" data-text="Start Learning">Start Learning</a>
        </div>
        <div class="xagio-border-panel">
            <h3>Stay Connected</h3>
            <p>Your account is connected to Xagio. All available features are unlocked.</p>
            <a href="https://www.facebook.com/groups/xagio" target="_blank" type="button" class="xagio-button xagio-button-purple" data-text="Join Our Group">Join Our Group</a>
        </div>
    </div>

    <div class="xagio-border-panel xagio-margin-top-medium">
        <div class="xagio-welcome-reviews">
            <div class="xagio-person-review xagio-person-review-gray">
                <svg class="xagio-review-quote" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                    <g>
                        <path class="st0"
                              d="M119.472,66.59C53.489,66.59,0,120.094,0,186.1c0,65.983,53.489,119.487,119.472,119.487 c0,0-0.578,44.392-36.642,108.284c-4.006,12.802,3.135,26.435,15.945,30.418c9.089,2.859,18.653,0.08,24.829-6.389 c82.925-90.7,115.385-197.448,115.385-251.8C238.989,120.094,185.501,66.59,119.472,66.59z"/>
                        <path class="st0"
                              d="M392.482,66.59c-65.983,0-119.472,53.505-119.472,119.51c0,65.983,53.489,119.487,119.472,119.487 c0,0-0.578,44.392-36.642,108.284c-4.006,12.802,3.136,26.435,15.945,30.418c9.089,2.859,18.653,0.08,24.828-6.389 C479.539,347.2,512,240.452,512,186.1C512,120.094,458.511,66.59,392.482,66.59z"/>
                    </g>
                </svg>
                <div class="xagio-review-head">
                    <img src="/wp-content/plugins/xagio-seo/assets/img/michael-milas-150x150.webp" alt="">
                    <div class="name"><span>Michael Milas</span><span>SEO Specialist</span></div>
                </div>
                <div class="xagio-review-content">
                    No software is comparable. Xagio is the first "all in one" solution that I truly feel is better than
                    it's stand alone competitor solutions. Usually you find that when a software does everything, it's
                    lacking slightly in that area. Xagio is nothing like that. Unlike the others that do everything but
                    are the best at nothing, Xagio does everything and is the best at everything AND is the best price
                    for everything.
                </div>
            </div>
            <div class="xagio-person-review xagio-person-review-gray">
                <svg class="xagio-review-quote" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                    <g>
                        <path class="st0"
                              d="M119.472,66.59C53.489,66.59,0,120.094,0,186.1c0,65.983,53.489,119.487,119.472,119.487 c0,0-0.578,44.392-36.642,108.284c-4.006,12.802,3.135,26.435,15.945,30.418c9.089,2.859,18.653,0.08,24.829-6.389 c82.925-90.7,115.385-197.448,115.385-251.8C238.989,120.094,185.501,66.59,119.472,66.59z"/>
                        <path class="st0"
                              d="M392.482,66.59c-65.983,0-119.472,53.505-119.472,119.51c0,65.983,53.489,119.487,119.472,119.487 c0,0-0.578,44.392-36.642,108.284c-4.006,12.802,3.136,26.435,15.945,30.418c9.089,2.859,18.653,0.08,24.828-6.389 C479.539,347.2,512,240.452,512,186.1C512,120.094,458.511,66.59,392.482,66.59z"/>
                    </g>
                </svg>
                <div class="xagio-review-head">
                    <img src="/wp-content/plugins/xagio-seo/assets/img/keith-james-best-150x150.webp" alt="">
                    <div class="name"><span>Keith James Best</span><span>SEO</span></div>
                </div>
                <div class="xagio-review-content">
                    Had Xagio from the start and thought it was awesome then. I didn't know half of what it did and
                    wasn't using it to its full potential till I watched their webinars. It's brilliant! Been able to
                    update all sites to latest WP release or plugin release at the touch of a button. And the Project
                    Planner is a gold mine, which I have played with in v2 but never really got into till I saw the
                    webinar, now I'm using it all of the time. Cheers for a great product!
                </div>
            </div>
            <div class="xagio-person-review xagio-person-review-gray">
                <svg class="xagio-review-quote" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                    <g>
                        <path class="st0"
                              d="M119.472,66.59C53.489,66.59,0,120.094,0,186.1c0,65.983,53.489,119.487,119.472,119.487 c0,0-0.578,44.392-36.642,108.284c-4.006,12.802,3.135,26.435,15.945,30.418c9.089,2.859,18.653,0.08,24.829-6.389 c82.925-90.7,115.385-197.448,115.385-251.8C238.989,120.094,185.501,66.59,119.472,66.59z"/>
                        <path class="st0"
                              d="M392.482,66.59c-65.983,0-119.472,53.505-119.472,119.51c0,65.983,53.489,119.487,119.472,119.487 c0,0-0.578,44.392-36.642,108.284c-4.006,12.802,3.136,26.435,15.945,30.418c9.089,2.859,18.653,0.08,24.828-6.389 C479.539,347.2,512,240.452,512,186.1C512,120.094,458.511,66.59,392.482,66.59z"/>
                    </g>
                </svg>
                <div class="xagio-review-head">
                    <img src="/wp-content/plugins/xagio-seo/assets/img/simon-white-150x150.webp" alt="">
                    <div class="name"><span>Simon White</span><span>UX Designer</span></div>
                </div>
                <div class="xagio-review-content">
                    Xagio is one of my go to plugins now in almost every new project. We use it to manage over 70+
                    Wordpress sites for ourselves and clients. Many useful features that make setting up and managing
                    sites easy. The support is rock solid which is important for us.
                </div>
            </div>
        </div>

        <div class="xagio-learn-more">
            <p><a href="https://xagio.com/" target="_blank" class="">Learn more about us at xagio.com</a></p>
        </div>
    </div>

    <!-- Changelog -->
    <dialog id="changelog" class="xagio-modal">
        <div class="xagio-modal-header">
            <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-info"></i> Latest Updates</h3>
            <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
        </div>

        <div class="xagio-modal-body">
            <p>Version: <b><?php echo esc_html(xagio_get_version()); ?></b>, what is new?</p>

            <pre class="changelog"><?php echo esc_html(xagio_file_get_contents(XAGIO_PATH . '/readme.txt')); ?></pre>

            <p>Due to nature of updates and constant changes of different files in our plugins, we <b
                        style="color:red">strongly advise</b> you to clear your browser cache (and WordPress cache
                if you are using any caching plugins) when you're done with installing new plugin updates.
                If you aren't tech savvy, please follow one of the links according to your browser type in order
                to get instructions on how to clear your browser cache:
                <br>[ <a href="https://support.google.com/chrome/answer/95582?hl=en" target="_blank">Google
                    Chrome</a> |
                <a href="https://support.mozilla.org/en-US/kb/how-clear-firefox-cache" target="_blank">Mozilla
                    Firefox</a> |
                <a href="https://kb.wisc.edu/page.php?id=45060" target="_blank">Safari</a> |
                <a href="https://kb.wisc.edu/helpdesk/page.php?id=12381" target="_blank">Opera</a> |
                <a href="https://kb.wisc.edu/page.php?id=15141" target="_blank">Internet Explorer</a> ]

            </p>

            <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
                <button class="xagio-button xagio-button-outline" type="button" data-xagio-close-modal><i
                            class="xagio-icon xagio-icon-close"></i> Cancel
                </button>
            </div>
        </div>
    </dialog>

    <!-- Welcome Video -->
    <dialog id="welcome-video" class="xagio-modal-outline">
        <div class="welcome-vimeo-holder" style="padding:56.25% 0 0 0;position:relative;margin-bottom: -1px;margin-right: -1px;">
            <iframe src="https://player.vimeo.com/video/1004605667?h=3b3706477e&amp;badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479"
                    frameborder="0" allow="autoplay; fullscreen; picture-in-picture; clipboard-write"
                    style="position:absolute;top:0;left:0;width:100%;height:100%;"
                    title="Connect Free - No Splash"></iframe>
        </div>
    </dialog>

    <div class="announcement template">
        <div class="announcement-heading">
            <span class="announcement-title">...</span>
            <div></div>
            <span class="announcement-date">...</span>
            <div></div>
        </div>
        <div class="announcement-body">
            ...
        </div>
    </div>

</div> <!-- .wrap -->