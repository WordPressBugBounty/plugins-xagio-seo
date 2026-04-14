<?php
/**
 * Type: SUBMENU
 * Page_Title: Sitemaps
 * Menu_Title: Sitemaps
 * Capability: manage_options
 * Slug: xagio-sitemaps
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: xagio_tagsinput,xagio_sitemaps
 * Css: xagio_animate,xagio_sitemaps
 * Position: 6
 * Version: 1.0.0
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

$XAGIO_MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');
?>
<div class="xagio-main-header xagio-main-header-big-gaps">
    <img class="logo-image repo-xagio" src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        Sitemaps
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
            <span>Sitemaps allow you to easily create a visual representation of your website's structure and content, making it easier for search engines to index and understand the site.</span>
            <i class="xagio-icon xagio-icon-arrow-down"></i>
        </h3>
        <div class="xagio-accordion-content">
            <div>
                <div class="xagio-accordion-panel"></div>
            </div>
        </div>
    </div>

    <ul class="xagio-tab">
        <li class="xagio-tab-active"><a href="">Overview</a></li>
        <li><a href="">Sitemaps Content</a></li>
    </ul>

    <div class="xagio-tab-content-holder">

        <!-- Settings -->
        <div class="xagio-tab-content">

            <form class="settings">
                <input type="hidden" name="action" value="xagio_sitemaps_settings"/>
                <div class="xagio-panel">
                    <h5 class="xagio-panel-title">Sitemap Settings</h5>

                    <div class="xagio-2-column-grid xagio-margin-bottom-large">
                        <div class="xagio-column-1 xagio-padding-right-medium xagio-border-right">
                            <!-- Enable/Disable Sitemaps -->
                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_ENABLE_SITEMAPS" id="XAGIO_ENABLE_SITEMAPS"
                                       value="<?php echo (XAGIO_ENABLE_SITEMAPS == TRUE) ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button <?php echo (XAGIO_ENABLE_SITEMAPS == TRUE) ? 'on' : ''; ?>"
                                          data-element="XAGIO_ENABLE_SITEMAPS"></span>
                                </div>
                                <p class="xagio-slider-label">Enable Sitemaps <i class="xagio-icon xagio-icon-info"
                                                                                 data-xagio-tooltip
                                                                                 data-xagio-title="Enable Sitemaps"></i>
                                </p>
                            </div>
                        </div>
                        <div class="xagio-column-2">
                            <!-- Compression -->
                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_SITEMAP_COMPRESSION" id="XAGIO_SITEMAP_COMPRESSION"
                                       value="<?php echo (XAGIO_SITEMAP_COMPRESSION == TRUE) ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button <?php echo (XAGIO_SITEMAP_COMPRESSION == TRUE) ? 'on' : ''; ?>"
                                              data-element="XAGIO_SITEMAP_COMPRESSION"></span>
                                </div>
                                <p class="xagio-slider-label">Use Compression when possible <i
                                            class="xagio-icon xagio-icon-info" data-xagio-tooltip
                                            data-xagio-title="When this option is On, server will try to provide compressed version of sitemaps to browsers that support it."></i>
                                </p>
                            </div>

                            <!-- Cache on disk -->
                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_CACHE_SITEMAPS" id="XAGIO_CACHE_SITEMAPS"
                                       value="<?php echo (XAGIO_CACHE_SITEMAPS == TRUE) ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button <?php echo (XAGIO_CACHE_SITEMAPS == TRUE) ? 'on' : ''; ?>"
                                              data-element="XAGIO_CACHE_SITEMAPS"></span>
                                </div>
                                <p class="xagio-slider-label">Cache Sitemaps in Webroot <i
                                            class="xagio-icon xagio-icon-info" data-xagio-tooltip
                                            data-xagio-title="Instead of serving Sitemaps from database, they will be written directly on the web root, potentially improving performance."></i>
                                </p>
                            </div>

                        </div>
                    </div>

                    <div class="xagio-alert xagio-alert-primary sitemap-location">
                        Your sitemaps generated by Xagio are visible on the following URLs:<br>
                        <div class="sitemap-location-holder">
                        </div>
                    </div>
                </div>
            </form>

        </div>

        <!-- Content & Exclusions -->
        <div class="xagio-tab-content">

            <form class="content">

                <?php $XAGIO_SETT = get_option('XAGIO_SITEMAP_CONTENT_SETTINGS'); ?>

                <input type="hidden" name="action" value="xagio_content_settings"/>

                <div class="xagio-panel xagio-margin-bottom-medium">
                    <h5 class="xagio-panel-title">Post Types</h5>

                    <div class="xagio-3-columns">
                        <?php foreach (XAGIO_MODEL_SEO::getAllPostTypes() as $xagio_postType): ?>

                            <?php
                            if (!isset($XAGIO_SETT['post_types']))
                                $XAGIO_SETT = ['post_types' => []];
                            if (!isset($XAGIO_SETT['post_types'][$xagio_postType]))
                                $XAGIO_SETT['post_types'][$xagio_postType] = [
                                    'enabled'          => 0,
                                    'priority'         => 1.0,
                                    'change_frequency' => 'daily',
                                    'exclusions'       => ''
                                ];

                            ?>

                            <div class="content-settings">
                                <input type="hidden" name="values[post_types][<?php echo esc_attr($xagio_postType); ?>][name]"
                                       value="<?php echo esc_attr($xagio_postType); ?>"/>

                                <div class="content-settings-header">
                                    <!-- Include in Sitemaps -->
                                    <div class="xagio-slider-container">
                                        <input type="hidden"
                                               name="values[post_types][<?php echo esc_attr($xagio_postType); ?>][enabled]"
                                               id="XAGIO_POST_TYPE_<?php echo esc_attr(strtoupper($xagio_postType)); ?>_ENABLED"
                                               value="<?php echo ($XAGIO_SETT['post_types'][$xagio_postType]['enabled'] ?? 0) ? 1 : 0; ?>"/>
                                        <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button <?php echo ($XAGIO_SETT['post_types'][$xagio_postType]['enabled'] ?? 0) ? 'on' : ''; ?>"
                                              data-element="XAGIO_POST_TYPE_<?php echo esc_attr(strtoupper($xagio_postType)); ?>_ENABLED"></span>
                                        </div>
                                    </div>
                                    <h3 class="post-type"><?php echo esc_html(ucfirst($xagio_postType)); ?></h3>
                                </div>


                                <label for="<?php echo esc_attr($xagio_postType); ?>_prio">Priority <i
                                            class="xagio-icon xagio-icon-info" data-xagio-tooltip
                                            data-xagio-title="The priority element in a sitemap is a hint to search engines about the importance of a particular URL relative to other URLs on the same site. This can be used by search engines to determine how to prioritize the indexing of the pages on your site."></i></label>

                                <div class="xagio-flex-row">
                                    <input id="<?php echo esc_attr($xagio_postType); ?>_prio" type="range"
                                           value="<?php echo esc_attr($XAGIO_SETT['post_types'][$xagio_postType]['priority'] ?? ''); ?>"
                                           min="0.0" max="1.0" step="0.1"
                                           name="values[post_types][<?php echo esc_attr($xagio_postType); ?>][priority]"
                                           class="xagio-range"> <span
                                            class="current-value"><?php echo esc_html($XAGIO_SETT['post_types'][$xagio_postType]['priority'] ?? ''); ?></span>
                                </div>


                                <label for="<?php echo esc_attr($xagio_postType); ?>_freq">Change Frequency <i
                                            class="xagio-icon xagio-icon-info" data-xagio-tooltip
                                            data-xagio-title="The change freq element in a sitemap is a hint to search engines about how frequently a particular URL is likely to change. This can be used by search engines to determine how often to crawl a particular page."></i></label>
                                <select class="xagio-input-select" id="<?php echo esc_attr($xagio_postType); ?>_freq"
                                        name="values[post_types][<?php echo esc_attr($xagio_postType); ?>][change_frequency]">
                                    <option <?php echo (($XAGIO_SETT['post_types'][$xagio_postType]['change_frequency'] ?? '') == 'always') ? 'selected' : ''; ?>
                                            value="always">Always
                                    </option>
                                    <option <?php echo (($XAGIO_SETT['post_types'][$xagio_postType]['change_frequency'] ?? '') == 'hourly') ? 'selected' : ''; ?>
                                            value="hourly">Hourly
                                    </option>
                                    <option <?php echo (($XAGIO_SETT['post_types'][$xagio_postType]['change_frequency'] ?? '') == 'daily') ? 'selected' : ''; ?>
                                            value="daily">Daily
                                    </option>
                                    <option <?php echo (($XAGIO_SETT['post_types'][$xagio_postType]['change_frequency'] ?? '') == 'weekly') ? 'selected' : ''; ?>
                                            value="weekly">Weekly
                                    </option>
                                    <option <?php echo (($XAGIO_SETT['post_types'][$xagio_postType]['change_frequency'] ?? '') == 'monthly') ? 'selected' : ''; ?>
                                            value="monthly">Monthly
                                    </option>
                                    <option <?php echo (($XAGIO_SETT['post_types'][$xagio_postType]['change_frequency'] ?? '') == 'yearly') ? 'selected' : ''; ?>
                                            value="yearly">Yearly
                                    </option>
                                    <option <?php echo (($XAGIO_SETT['post_types'][$xagio_postType]['change_frequency'] ?? '') == 'never') ? 'selected' : ''; ?>
                                            value="never">Never
                                    </option>
                                </select>

                                <label for="<?php echo esc_attr($xagio_postType); ?>_ex">Exclusions (exclude specific content
                                    by ID) <i class="xagio-icon xagio-icon-info" data-xagio-tooltip
                                              data-xagio-title="Content with these IDs will be skipped from going into the sitemap."></i></label>
                                <input class="xagio-input-text-mini xagio-input-text-white"
                                       id="<?php echo esc_attr($xagio_postType); ?>_ex" type="text"
                                       placeholder="eg. 102,333,40"
                                       name="values[post_types][<?php echo esc_attr($xagio_postType); ?>][exclusions]"
                                       value="<?php echo esc_attr($XAGIO_SETT['post_types'][$xagio_postType]['exclusions'] ?? ''); ?>">

                            </div>

                        <?php endforeach; ?>
                    </div>

                </div>

                <div class="xagio-panel">
                    <h5 class="xagio-panel-title">Taxonomies</h5>

                    <div class="xagio-3-columns">
                        <?php foreach (XAGIO_MODEL_SEO::getAllTaxonomies() as $taxonomy): ?>

                            <?php
                            if (!isset($XAGIO_SETT['taxonomies']))
                                $XAGIO_SETT = ['taxonomies' => []];
                            if (!isset($XAGIO_SETT['taxonomies'][$taxonomy]))
                                $XAGIO_SETT['post_types'][$taxonomy] = [
                                    'enabled'          => 0,
                                    'priority'         => 1.0,
                                    'change_frequency' => 'daily',
                                    'exclusions'       => ''
                                ];

                            ?>

                            <div class="content-settings">
                                <input type="hidden" name="values[taxonomies][<?php echo esc_attr($taxonomy); ?>][name]"
                                       value="<?php echo esc_attr($taxonomy); ?>"/>

                                <div class="content-settings-header">
                                    <!-- Include in Sitemaps -->
                                    <div class="xagio-slider-container">
                                        <input type="hidden"
                                               name="values[taxonomies][<?php echo esc_attr($taxonomy); ?>][enabled]"
                                               id="XAGIO_TAXONOMY_<?php echo esc_attr(strtoupper($taxonomy)); ?>_ENABLED"
                                               value="<?php echo ($XAGIO_SETT['taxonomies'][$taxonomy]['enabled'] ?? 0) ? 1 : 0; ?>"/>
                                        <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button <?php echo ($XAGIO_SETT['taxonomies'][$taxonomy]['enabled'] ?? 0) ? 'on' : ''; ?>"
                                              data-element="XAGIO_TAXONOMY_<?php echo esc_attr(strtoupper($taxonomy)); ?>_ENABLED"></span>
                                        </div>
                                    </div>

                                    <h3 class="taxonomy"><?php echo esc_html(ucfirst($taxonomy)); ?></h3>
                                </div>


                                <label for="<?php echo esc_attr($taxonomy); ?>_prio">Priority <i
                                            class="xagio-icon xagio-icon-info" data-xagio-tooltip
                                            data-xagio-title="The priority element in a sitemap is a hint to search engines about the importance of a particular URL relative to other URLs on the same site. This can be used by search engines to determine how to prioritize the indexing of the pages on your site."></i></label>

                                <div class="xagio-flex-row">
                                    <input id="<?php echo esc_attr($taxonomy); ?>_prio" type="range"
                                           value="<?php echo esc_attr($XAGIO_SETT['taxonomies'][$taxonomy]['priority'] ?? '0.5'); ?>"
                                           min="0.0" max="1.0" step="0.1"
                                           name="values[taxonomies][<?php echo esc_attr($taxonomy); ?>][priority]"
                                           class="xagio-range"> <span
                                            class="current-value"><?php echo esc_attr($XAGIO_SETT['taxonomies'][$taxonomy]['priority'] ?? '0.5'); ?></span>
                                </div>


                                <label for="<?php echo esc_attr($taxonomy); ?>_freq">Change Frequency <i
                                            class="xagio-icon xagio-icon-info" data-xagio-tooltip
                                            data-xagio-title="The change freq element in a sitemap is a hint to search engines about how frequently a particular URL is likely to change. This can be used by search engines to determine how often to crawl a particular page."></i></label>
                                <select class="xagio-input-select" id="<?php echo esc_attr($taxonomy); ?>_freq"
                                        name="values[taxonomies][<?php echo esc_attr($taxonomy); ?>][change_frequency]">
                                    <option <?php echo (($XAGIO_SETT['taxonomies'][$taxonomy]['change_frequency'] ?? '') == 'always') ? 'selected' : ''; ?>
                                            value="always">Always
                                    </option>
                                    <option <?php echo (($XAGIO_SETT['taxonomies'][$taxonomy]['change_frequency'] ?? '') == 'hourly') ? 'selected' : ''; ?>
                                            value="hourly">Hourly
                                    </option>
                                    <option <?php echo (($XAGIO_SETT['taxonomies'][$taxonomy]['change_frequency'] ?? '') == 'daily') ? 'selected' : ''; ?>
                                            value="daily">Daily
                                    </option>
                                    <option <?php echo (($XAGIO_SETT['taxonomies'][$taxonomy]['change_frequency'] ?? '') == 'weekly') ? 'selected' : ''; ?>
                                            value="weekly">Weekly
                                    </option>
                                    <option <?php echo (($XAGIO_SETT['taxonomies'][$taxonomy]['change_frequency'] ?? '') == 'monthly') ? 'selected' : ''; ?>
                                            value="monthly">Monthly
                                    </option>
                                    <option <?php echo (($XAGIO_SETT['taxonomies'][$taxonomy]['change_frequency'] ?? '') == 'yearly') ? 'selected' : ''; ?>
                                            value="yearly">Yearly
                                    </option>
                                    <option <?php echo (($XAGIO_SETT['taxonomies'][$taxonomy]['change_frequency'] ?? '') == 'never') ? 'selected' : ''; ?>
                                            value="never">Never
                                    </option>
                                </select>

                                <label for="<?php echo esc_attr($taxonomy); ?>_ex">Exclusions (exclude specific content
                                    by ID) <i
                                            class="xagio-icon xagio-icon-info" data-xagio-tooltip
                                            data-xagio-title="Content with these IDs will be skipped from going into the sitemap."></i></label>
                                <input class="xagio-input-text-mini xagio-input-text-white"
                                       id="<?php echo esc_attr($taxonomy); ?>_ex" type="text"
                                       placeholder="eg. 102,333,40"
                                       name="values[taxonomies][<?php echo esc_attr($taxonomy); ?>][exclusions]"
                                       value="<?php echo esc_attr($XAGIO_SETT['taxonomies'][$taxonomy]['exclusions'] ?? ''); ?>">

                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>


            </form>
        </div>

    </div>


</div> <!-- .wrap -->