<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Load all variables
$post_types = get_option('XAGIO_SEO_DEFAULT_POST_TYPES');
$post_type  = $post->post_type;

$robots_obj = XAGIO_MODEL_SEO::getRobots($post, true);
$robots = $robots_obj['robots'];
$global_robots_settings = $robots_obj['global'];
$robots_post_type = $robots_obj['post_type'];

$meta       = XAGIO_MODEL_SEO::formatMetaVariables(get_post_meta($post->ID));
$home_url   = preg_replace('#^https?://#', '', get_home_url());
$template   = $post_types[$post_type] ?? NULL;

$meta['XAGIO_SEO_SEARCH_PREVIEW_ENABLE']    = !isset($meta['XAGIO_SEO_SEARCH_PREVIEW_ENABLE']) ? 1 : $meta['XAGIO_SEO_SEARCH_PREVIEW_ENABLE'];
$meta['XAGIO_SEO_SOCIAL_ENABLE']            = !isset($meta['XAGIO_SEO_SOCIAL_ENABLE']) ? 1 : $meta['XAGIO_SEO_SOCIAL_ENABLE'];
$meta['XAGIO_SEO_META_ROBOTS_ENABLE']       = !isset($meta['XAGIO_SEO_META_ROBOTS_ENABLE']) ? 1 : $meta['XAGIO_SEO_META_ROBOTS_ENABLE'];
$meta['XAGIO_SEO_SCHEMA_ENABLE']            = !isset($meta['XAGIO_SEO_SCHEMA_ENABLE']) ? 1 : $meta['XAGIO_SEO_SCHEMA_ENABLE'];
$meta['XAGIO_SEO_SCRIPTS_ENABLE']           = !isset($meta['XAGIO_SEO_SCRIPTS_ENABLE']) ? 1 : $meta['XAGIO_SEO_SCRIPTS_ENABLE'];

?>
<input type="hidden" id="xagio_post_id" value="<?php echo absint($post->ID); ?>"/>
<input type="hidden" id="xagio_post_thumbnail" value="<?php echo  esc_attr(get_the_post_thumbnail_url($post->ID)); ?>"/>
<?php wp_nonce_field('xagio_nonce', '_xagio_nonce'); ?>


<ul class="xagio-tab xagio-tab-mini">
    <li class="xagio-tab-active"><a href="">SEO</a></li>
    <li><a href="">Social</a></li>
    <li><a href="">Robots</a></li>
    <?php if (class_exists('XAGIO_MODEL_SCHEMA')): ?>
    <li><a href="">Schema</a></li>
    <?php endif; ?>
    <li><a href="">Scripts</a></li>
    <?php if (class_exists('XAGIO_MODEL_AI')): ?>
    <li><a href="">AI Content</a></li>
    <?php endif; ?>
    <li><a href="">Notes</a></li>
</ul>

<div class="xagio-tab-content-holder" id="xagio-seo-sections">
    <div class="xagio-tab-content XAGIO_SEO_SEARCH_PREVIEW <?php echo  @$meta['XAGIO_SEO_SEARCH_PREVIEW_ENABLE'] ? "on" : "off" ?>">

        <div class="xagio-slider-container page-seo-section-enable">
            <span class="page-seo-section-text"><?php echo  @$meta['XAGIO_SEO_SEARCH_PREVIEW_ENABLE'] ? "Enabled" : "Disabled" ?></span>
            <input type="hidden" name="XAGIO_SEO_SEARCH_PREVIEW_ENABLE" id="XAGIO_SEO_SEARCH_PREVIEW" value="<?php echo  @$meta['XAGIO_SEO_SEARCH_PREVIEW_ENABLE'] ? "1" : "0" ?>"/>
            <div class="xagio-slider-frame">
                <span class="xagio-slider-button <?php echo  @$meta['XAGIO_SEO_SEARCH_PREVIEW_ENABLE'] ? "on" : "off" ?>" data-with-text data-element="XAGIO_SEO_SEARCH_PREVIEW"></span>
            </div>
        </div>

        <div class="xagio-panel xagio-margin-bottom-medium">
            <h3 class="xagio-panel-title">Meta SEO Snippet</h3>

            <div class="xagio-tabs">
                <div class="xagio-seo-row">
                    <div class="xagio-g-snippet">
                        <div class="xagio-g-url">
                            <?php
                            $relative_path = "/";
                            $URL           = XAGIO_MODEL_SEO::extract_url_parts($post->ID);
                            ?>
                            <span class="xagio-g-domain">
                                    <?php echo esc_html($URL['host']) ?>
                                </span>
                            <?php
                            if (!empty($URL['parts'])) {
                                ?>
                                <span class="xagio-g-url-path">
                                <?php

                                foreach ($URL['parts'] as $parent) {
                                    $relative_path .= $parent . "/";
                                    ?>
                                    <span class="caret">›</span> <?php echo esc_html($parent) ?>
                                    <?php
                                }
                                ?>
                                    </span>
                                <?php
                            }
                            if (!empty($URL['editable_url'])) {
                                echo '<span class="caret">›</span> ';
                            }
                            ?>

                            <span class="xagio-g-editable-url">
                                    <input type="hidden" name="XAGIO_RELATIVE_URL_PART" value="<?php echo esc_attr($relative_path) ?>">
                                    <?php
                                    if (!empty($URL['editable_url'])) {
                                        echo "<input type='hidden' name='XAGIO_SEO_ORIGINAL_URL' value='" . esc_attr($URL['editable_url']) . "'><input type='text' name='XAGIO_SEO_URL' value='" . esc_attr($URL['editable_url']) . "'>";
                                    }
                                    ?>
                            </span>
                        </div>
                        <div class="xagio-g-title">
                            <!-- SEO Title -->
                            <div class="xagio-title-length">
                                <div class="inside-check-circle"></div>
                            </div>
                            <div class="title-check-circle"></div>

                            <div class="xagio-blocks-button">
                                <span class="button"><i class="xagio-icon xagio-icon-arrow-down"></i> Shortcodes</span>

                                <div class="xagio-blocks">
                                    <div class="xagio-blocks-search-container">
                                        <div class="xagio-input-group">
                                            <i class="xagio-icon xagio-icon-search"></i>
                                            <input type="text" class="xagio-blocks-search"/>
                                        </div>
                                    </div>
                                    <div class="xagio-blocks-list">
                                        <ul class="xagio-blocks-data"></ul>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="XAGIO_SEO_TITLE" id="XAGIO_SEO_TITLE_INPUT" value="<?php echo esc_attr(@$meta['XAGIO_SEO_TITLE']); ?>"/>
                            <div class="xagio-editor" data-target="XAGIO_SEO_TITLE_INPUT" id="XAGIO_SEO_TITLE" contenteditable="true"
                                 placeholder="<?php echo  esc_attr(XAGIO_MODEL_SEO::replaceVars(@$template['title'], absint($post->ID))); ?>"><?php echo esc_html(@$meta['XAGIO_SEO_TITLE']); ?></div>
                        </div>
                        <div class="xagio-g-desc">
                            <!-- SEO Description -->
                            <div class="xagio-desc-length">
                                <div class="inside-check-circle"></div>
                            </div>
                            <div class="desc-check-circle"></div>

                            <div class="xagio-blocks-button">
                                <span class="button"><i class="xagio-icon xagio-icon-arrow-down"></i> Shortcodes</span>

                                <div class="xagio-blocks">
                                    <div class="xagio-blocks-search-container">
                                        <div class="xagio-input-group">
                                            <i class="xagio-icon xagio-icon-search"></i>
                                            <input type="text" class="xagio-blocks-search"/>
                                        </div>
                                    </div>
                                    <div class="xagio-blocks-list">
                                        <ul class="xagio-blocks-data"></ul>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="XAGIO_SEO_DESCRIPTION" id="XAGIO_SEO_DESCRIPTION_INPUT" value="<?php echo esc_attr(@$meta['XAGIO_SEO_DESCRIPTION']); ?>"/>
                            <div class="xagio-editor smaller-font" data-target="XAGIO_SEO_DESCRIPTION_INPUT" id="XAGIO_SEO_DESCRIPTION" contenteditable="true"
                                 placeholder="<?php echo  esc_attr(XAGIO_MODEL_SEO::replaceVars(@$template['description'], absint($post->ID))); ?>"><?php echo esc_html(@$meta['XAGIO_SEO_DESCRIPTION']); ?></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="xagio-panel xagio-margin-bottom-medium">
            <h3 class="xagio-panel-title keyword-group-attached">Keyword Group
                <?php $group = XAGIO_MODEL_PROJECTS::isAttachedToGroupArray($post->ID); ?>
                <div class="xagio-group-container <?php echo  empty($group['group_id']) ? 'xagio-hidden' : '' ?>" data-group-id="<?php echo  absint($group['group_id']) ?: 0 ?>" data-project-id="<?php echo  absint($group['project_id']) ?: 0 ?>">

                    <button type="button" class="xagio-button xagio-button-primary xagio-button-mini xagio-detach-group" data-xagio-tooltip data-xagio-title="Detach Group">
                        <i class="xagio-icon xagio-icon-link-off"></i>
                    </button>

                    <button type="button" class="xagio-button xagio-button-primary xagio-button-mini xagio-edit-group" data-xagio-tooltip data-xagio-title="Edit Group">
                        <i class="xagio-icon xagio-icon-edit"></i>
                    </button>

                    <button type="button" class="xagio-button xagio-button-primary xagio-button-mini xagio-save-keywords" data-xagio-tooltip data-xagio-title="Save Group">
                        <i class="xagio-icon xagio-icon-save"></i>
                    </button>

                </div>

            </h3>

            <ul class="xagio-g-tabs-extended <?php echo  $group['group_id'] ? 'xagio-hidden' : '' ?>">
                <!-- Search Optimization -->
                <li class="search-preview additional-options search-optimization">

                    <div class="xagio-search-group-input">
                        <input type="text" class="searchProjectGroups" placeholder="Attach a Group">
                        <i class="xagio-icon xagio-icon-search g-search-icon"></i>

                        <div class="xagio-group-search-results xagio-hidden">
                            <div class="xagio-search-info">Start typing to search for Groups from <a target="_blank" href="/wp-admin/admin.php?page=xagio-projectplanner">Project Planner</a> then select one to
                                attach to this page.
                            </div>
                        </div>
                    </div>

                </li>
            </ul>

            <div class="xagio-table-responsive">
                <table class="xagio-on-page-seo-table keywords <?php echo  $group['group_id'] ? '' : 'xagio-hidden' ?>">
                    <thead>
                    <tr>
                        <th></th>
                        <th>Keyword</th>
                        <th>Volume</th>
                        <th>CPC</th>
                        <th>inTitle</th>
                        <th>inURL</th>
                        <th data-xagio-tooltip data-xagio-title="Title Ratio (Volume / InTitle)" class="xagio-text-center">TR</th>
                        <th data-xagio-tooltip data-xagio-title="URL Ratio (Volume / InURL)" class="xagio-text-center">
                            UR
                        </th>
                        <th>Rank</th>
                    </tr>
                    </thead>
                    <tbody class="keywords-data group-keywords uk-sortable ui-sortable">
                    <tr>
                        <td colspan="9" class="xagio-text-center"><i class="xagio-icon xagio-icon-info"></i> No group has been attached to this page.</td>
                    </tr>
                    </tbody>
                </table>
            </div>


        </div>

        <div class="xagio-2-column-30-70-grid">
            <div>
                <div class="xagio-panel xagio-margin-bottom-medium">
                    <h3 class="xagio-panel-title">Keyword Cloud</h3>

                    <div class="xagio-word-cloud-container">
                        <div class="xagio-word-cloud"></div>
                    </div>

                </div>
            </div>
            <div>

                <div class="xagio-panel analysis-suggestions">
                    <div class="xagio-preview-text">
                        <h3 class="xagio-panel-title">Keyword Suggestions <span class="uk-badge uk-badge-a uk-badge-s"><span></span></span></h3>
                        <input type="text" name="XAGIO_SEO_TARGET_KEYWORD" id="XAGIO_SEO_TARGET_KEYWORD" placeholder="your target keyword" value="<?php echo esc_attr(@$meta['XAGIO_SEO_TARGET_KEYWORD']); ?>"/>
                    </div>
                    <div class="xagio-slider-container xagio-margin-bottom-medium">
                        <input type="hidden" name="lock-suggestions" id="lock-suggestions" value="1"/>
                        <div class="xagio-slider-frame">
                            <span class="xagio-slider-button on" data-element="lock-suggestions"></span>
                        </div>
                        <p class="xagio-slider-label">Lock Suggestions <i class="xagio-icon xagio-icon-info help-icon" data-xagio-tooltip data-xagio-title="This will prevent generating new Keyword Suggestions once Target Keyword changes. If you want to generate new Keyword Suggestions, simply uncheck this and change your Target Keyword."></i></p>
                    </div>

                    <div class="xagio-table-responsive">
                        <table class="xagio-on-page-seo-table keywords">
                            <thead>
                            <tr>
                                <th></th>
                                <th>Keyword</th>
                                <th>Volume</th>
                                <th>CPC</th>
                            </tr>
                            </thead>
                            <tbody class="keywords-data suggestion-keywords uk-sortable ui-sortable">
                            <tr>
                                <td colspan="9" class="xagio-text-center" style="padding: 10px 20px;"><i class="xagio-icon xagio-icon-info"></i> Keyword Suggestions still haven't been generated.</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>




    </div>
    <div class="xagio-tab-content XAGIO_SEO_SOCIAL <?php echo  @$meta['XAGIO_SEO_SOCIAL_ENABLE'] ? "on" : "off" ?>">
        <!-- Turn On/Off -->
        <div class="xagio-slider-container page-seo-section-enable">
            <span class="page-seo-section-text"><?php echo  @$meta['XAGIO_SEO_SOCIAL_ENABLE'] ? "Enabled" : "Disabled" ?></span>
            <input type="hidden" name="XAGIO_SEO_SOCIAL_ENABLE" id="XAGIO_SEO_SOCIAL" value="<?php echo  @$meta['XAGIO_SEO_SOCIAL_ENABLE'] ? "1" : "0" ?>"/>
            <div class="xagio-slider-frame">
                <span class="xagio-slider-button <?php echo  @$meta['XAGIO_SEO_SOCIAL_ENABLE'] ? "on" : "off" ?>" data-with-text data-element="XAGIO_SEO_SOCIAL"></span>
            </div>
        </div>

        <input type="hidden" value="0" name="XAGIO_SEO_FACEBOOK_TITLE_USE_FROM_SEO"/>
        <input type="hidden" value="0" name="XAGIO_SEO_FACEBOOK_DESCRIPTION_USE_FROM_SEO"/>
        <input type="hidden" value="0" name="XAGIO_SEO_FACEBOOK_USE_FEATURED_IMAGE"/>

        <input type="hidden" value="0" name="XAGIO_SEO_TWITTER_TITLE_USE_FROM_SEO"/>
        <input type="hidden" value="0" name="XAGIO_SEO_TWITTER_DESCRIPTION_USE_FROM_SEO"/>
        <input type="hidden" value="0" name="XAGIO_SEO_TWITTER_USE_FEATURED_IMAGE"/>

        <div class="xagio-2-column-grid">
            <div>
                <div class="xagio-panel">
                    <h2>Facebook</h2>

                    <!-- Facebook Title -->
                    <div class="xagio-flex xagio-flex-space-between xagio-margin-bottom-medium">
                        <label class="xagio-social-label" for="XAGIO_SEO_FACEBOOK_TITLE">Title</label>
                        <label class="xagio-social-checkbox" for="facebook_use_title_from_seo">
                            Use Title from SEO <input type="checkbox" class="xagio-input-checkbox xagio-input-checkbox-small" <?php echo !empty($meta['XAGIO_SEO_FACEBOOK_TITLE_USE_FROM_SEO']) ? "checked" : ""; ?> name="XAGIO_SEO_FACEBOOK_TITLE_USE_FROM_SEO" value="1" id="facebook_use_title_from_seo">
                        </label>
                    </div>

                    <input class="xagio-input-text-mini" type="text" id="XAGIO_SEO_FACEBOOK_TITLE" name="XAGIO_SEO_FACEBOOK_TITLE" value="<?php echo esc_attr(@$meta['XAGIO_SEO_FACEBOOK_TITLE']); ?>"/>

                    <!-- Facebook Description -->
                    <div class="xagio-flex xagio-flex-space-between xagio-margin-bottom-medium xagio-margin-top-medium">
                        <label class="xagio-social-label" for="XAGIO_SEO_FACEBOOK_DESCRIPTION">Description</label>
                        <label class="xagio-social-checkbox" for="facebook_use_description_from_seo">
                            Use Description from SEO <input type="checkbox" class="xagio-input-checkbox xagio-input-checkbox-small" <?php echo !empty($meta['XAGIO_SEO_FACEBOOK_DESCRIPTION_USE_FROM_SEO']) ? "checked" : ""; ?> name="XAGIO_SEO_FACEBOOK_DESCRIPTION_USE_FROM_SEO" value="1" id="facebook_use_description_from_seo">
                        </label>
                    </div>

                    <textarea class="xagio-input-textarea" id="XAGIO_SEO_FACEBOOK_DESCRIPTION" name="XAGIO_SEO_FACEBOOK_DESCRIPTION" rows="5"><?php echo esc_textarea(@$meta['XAGIO_SEO_FACEBOOK_DESCRIPTION']); ?></textarea>


                    <!-- Facebook Image -->
                    <div class="xagio-flex xagio-flex-space-between xagio-margin-bottom-medium xagio-margin-top-medium">
                        <label class="xagio-social-label" for="XAGIO_SEO_FACEBOOK_IMAGE">Image <i class="xagio-icon xagio-icon-warning xagio-social-facebook-image-warning" style="display: none" data-xagio-tooltip data-xagio-title="Feature Image Is Not Set"></i></label>
                        <label class="xagio-social-checkbox" for="facebook_use_featured_image">
                            Use Featured Image <input type="checkbox" class="xagio-input-checkbox xagio-input-checkbox-small" <?php echo !empty($meta['XAGIO_SEO_FACEBOOK_USE_FEATURED_IMAGE']) ? "checked" : ""; ?> name="XAGIO_SEO_FACEBOOK_USE_FEATURED_IMAGE" value="1" id="facebook_use_featured_image">
                        </label>
                    </div>
                    <div class="input-group">
                        <input class="xagio-input-text-mini" type="url" id="XAGIO_SEO_FACEBOOK_IMAGE"
                                name="XAGIO_SEO_FACEBOOK_IMAGE" value="<?php echo esc_attr(@$meta['XAGIO_SEO_FACEBOOK_IMAGE']); ?>"/>
                        <button class="xagio-button-upload-image imageSelect" type="button"
                                data-target="XAGIO_SEO_FACEBOOK_IMAGE"><i class="xagio-icon xagio-icon-upload"></i>
                        </button>
                    </div>

                    <div class="xagio-flex xagio-flex-space-between xagio-margin-bottom-medium xagio-margin-top-large">
                        <label class="xagio-title-label">Preview</label>
                        <a class="xagio-button xagio-button-primary" target="_blank" href="https://developers.facebook.com/tools/debug/?q=<?php echo urlencode(get_permalink($post->ID));?>">Refresh On Facebook</a>
                    </div>


                    <div class="facebook-preview">
                        <div class="facebook-preview-header">
                            <div class="facebook-preview-author-profile">
                                <img src="<?php echo esc_url(get_site_icon_url(160, XAGIO_URL . 'assets/img/logo-xagio.webp')); ?>">
                                <div class="facebook-preview-author">
                                    <div><?php echo esc_html(get_bloginfo('name')); ?></div>
                                    <div><?php echo esc_html(gmdate('d M')); ?></div>
                                </div>
                            </div>
                            <div>
                                <i class="xagio-icon xagio-icon-dots-horizontal"></i>
                            </div>
                        </div>
                        <img src="<?php echo filter_var(@$meta['XAGIO_SEO_FACEBOOK_IMAGE'], FILTER_VALIDATE_URL) ? esc_url(@$meta['XAGIO_SEO_FACEBOOK_IMAGE']) : esc_url(XAGIO_URL) . 'assets/img/facebook-placeholder.webp' ?>" data-no-image="<?php echo esc_url(XAGIO_URL) . 'assets/img/facebook-placeholder.webp'; ?>" class="facebook-image-preview">
                        <div class="facebook-preview-content">
                            <div class="facebook-preview-url"><?php echo esc_html(strtoupper(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                            <div class="facebook-preview-title"><?php echo esc_html(@$meta['XAGIO_SEO_FACEBOOK_TITLE']); ?></div>
                            <div class="facebook-preview-description"><?php echo esc_html(@$meta['XAGIO_SEO_FACEBOOK_DESCRIPTION']); ?></div>
                        </div>
                    </div>

                    <!-- Facebook Title -->
                    <div class="xagio-flex xagio-flex-space-between xagio-margin-bottom-medium xagio-margin-top-medium">
                        <label class="xagio-social-label" for="XAGIO_SEO_FACEBOOK_APP_ID">App ID</label>
                    </div>
                    <input class="xagio-input-text-mini" type="text" id="XAGIO_SEO_FACEBOOK_APP_ID" name="XAGIO_SEO_FACEBOOK_APP_ID" value="<?php echo esc_attr(@$meta['XAGIO_SEO_FACEBOOK_APP_ID']); ?>"/>


                    <div class="xagio-margin-top-medium hidden">
                        <div class="xagio-accordion">
                            <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                <span>App ID Template</span>
                                <i class="xagio-icon xagio-icon-arrow-down"></i>
                            </h3>
                            <div class="xagio-accordion-content">


                                <p><strong>Note:</strong>When you provide a Facebook Application ID, Facebook Open Graph Meta Tags will be added to your store's index page. These meta
                                    tags should not affect page load time for your store.</p>

                                <p>To create a Facebook Application ID:</p>

                                <ol>
                                    <li>
                                        <p>Go to the <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener">Facebook Developers Apps page</a>, and sign in with your Facebook username and
                                            password.</p>
                                    </li>
                                    <li>
                                        <p>Click the "Create New App" button.</p>
                                        <p>If you do not see the option to create a new app in the upper right-hand corner, click on "Register as Developer."</p>
                                    </li>
                                    <li>
                                        <p>Enter a name for the application in the "App Name" field. Using your store name is recommended.</p>
                                    </li>
                                    <li>
                                        <p>Read the Facebook Platform Policies and decide if you accept them. Once you've read the Facebook Platform Policies and have entered an App Name (step 2, above), click
                                            the "Continue" button. Note that by clicking the "Continue" button, you agree to the Facebook Platform Policies.</p>
                                    </li>
                                    <li>
                                        <p>You may be asked to verify your account by providing a mobile number or credit card number. If your Facebook account has already been verified, you may not be asked to
                                            verify your account.</p>
                                    </li>
                                    <li>
                                        <p>You may also encounter a Captcha security check. Enter the Captcha code and click the "Continue" button.</p>
                                    </li>
                                    <li>
                                        <p>You should now be on the Basic (Basic Settings) page for your app, where the App Name you provided in Step 2 will be shown in the "Display Name" field. Check that this
                                            name is correct and that your contact email address is correct, and then proceed to the "App Domains" field.</p>
                                    </li>
                                    <li>
                                        <p>Enter your domain name in the "App Domains" field.</p>
                                    </li>
                                    <li>
                                        <p>Next, scroll down to the "Select how your app integrates with Facebook" section of the Basic page, and click "Website with Facebook Login." This section will expand to
                                            show a "Site URL" field.</p>
                                    </li>
                                    <li>
                                        <p>Enter your store URL in the "Site URL" field.</p>
                                    </li>
                                    <li>
                                        <p>Click the "Save Changes" button.</p>
                                    </li>
                                </ol>

                            </div>
                        </div>

                    </div>




                </div>
            </div>
            <div>
                <div class="xagio-panel">
                    <h2>Twitter</h2>

                    <div class="xagio-flex xagio-flex-space-between xagio-margin-bottom-medium">
                        <label class="xagio-social-label" for="XAGIO_SEO_TWITTER_TITLE">Title</label>
                        <label class="xagio-social-checkbox" for="twitter_use_title_from_seo">
                            Use Title from SEO <input type="checkbox" class="xagio-input-checkbox xagio-input-checkbox-small" <?php echo  !empty($meta['XAGIO_SEO_TWITTER_TITLE_USE_FROM_SEO']) ? "checked" : ""; ?> name="XAGIO_SEO_TWITTER_TITLE_USE_FROM_SEO" value="1" id="twitter_use_title_from_seo">
                        </label>
                    </div>
                    <input class="xagio-input-text-mini" type="text" id="XAGIO_SEO_TWITTER_TITLE" name="XAGIO_SEO_TWITTER_TITLE" value="<?php echo esc_attr(@$meta['XAGIO_SEO_TWITTER_TITLE']); ?>"/>


                    <div class="xagio-flex xagio-flex-space-between xagio-margin-bottom-medium xagio-margin-top-medium">
                        <label class="xagio-social-label" for="XAGIO_SEO_TWITTER_DESCRIPTION">Description</label>

                        <label for="twitter_use_description_from_seo" class="xagio-social-checkbox">
                            Use Description from SEO <input type="checkbox" class="xagio-input-checkbox xagio-input-checkbox-small" <?php echo !empty($meta['XAGIO_SEO_TWITTER_DESCRIPTION_USE_FROM_SEO']) ? "checked" : ""; ?> name="XAGIO_SEO_TWITTER_DESCRIPTION_USE_FROM_SEO" value="1" id="twitter_use_description_from_seo">
                        </label>
                    </div>

                    <textarea class="xagio-input-textarea" id="XAGIO_SEO_TWITTER_DESCRIPTION" name="XAGIO_SEO_TWITTER_DESCRIPTION" rows="5"><?php echo esc_textarea(@$meta['XAGIO_SEO_TWITTER_DESCRIPTION']); ?></textarea>


                    <div class="xagio-flex xagio-flex-space-between xagio-margin-bottom-medium xagio-margin-top-medium">
                        <label class="xagio-social-label" for="XAGIO_SEO_TWITTER_IMAGE">Image <i class="xagio-icon xagio-icon-warning xagio-social-twitter-image-warning" style="display: none" data-xagio-tooltip data-xagio-title="Feature Image Is Not Set"></i></label>

                        <label for="twitter_use_featured_image" class="xagio-social-checkbox">
                            Use Featured Image <input type="checkbox" class="xagio-input-checkbox xagio-input-checkbox-small" <?php echo !empty($meta['XAGIO_SEO_TWITTER_USE_FEATURED_IMAGE']) ? "checked" : ""; ?> name="XAGIO_SEO_TWITTER_USE_FEATURED_IMAGE" value="1" id="twitter_use_featured_image">
                        </label>
                    </div>


                    <div class="input-group">
                        <input class="xagio-input-text-mini" type="url" id="XAGIO_SEO_TWITTER_IMAGE" name="XAGIO_SEO_TWITTER_IMAGE" value="<?php echo esc_attr(@$meta['XAGIO_SEO_TWITTER_IMAGE']); ?>"/>
                        <button class="xagio-button-upload-image imageSelect" type="button" data-target="XAGIO_SEO_TWITTER_IMAGE"><i class="xagio-icon xagio-icon-upload"></i></button>
                    </div>



                    <div class="xagio-flex xagio-flex-space-between xagio-margin-bottom-medium xagio-margin-top-large">
                        <label class="xagio-title-label">Preview</label>
                        <a class="xagio-button xagio-button-primary" target="_blank" href="https://www.bannerbear.com/tools/twitter-card-preview-tool/">Refresh On Twitter</a>
                    </div>


                    <div class="twitter-preview">
                        <div class="twitter-preview-header">
                            <div class="twitter-preview-author-profile">
                                <img src="<?php echo esc_url(get_site_icon_url(160, XAGIO_URL . 'assets/img/logo-xagio.webp')); ?>">
                                <div class="twitter-preview-author">
                                    <div><?php echo esc_html(get_bloginfo('name')); ?></div>
                                    <div><?php echo esc_html(gmdate('d M')); ?></div>
                                </div>
                            </div>
                            <div>
                                <i class="xagio-icon xagio-icon-dots-horizontal"></i>
                            </div>
                        </div>

                        <div class="twitter-preview-holder">
                            <div class="twitter-image-preview-holder">
                                <img src="<?php echo  filter_var(@$meta['XAGIO_SEO_TWITTER_IMAGE'], FILTER_VALIDATE_URL) ? esc_url(@$meta['XAGIO_SEO_TWITTER_IMAGE']) : esc_url(XAGIO_URL) . 'assets/img/twitter-placeholder.webp' ?>" data-no-image="<?php echo esc_url(XAGIO_URL) . 'assets/img/twitter-placeholder.webp'; ?>" class="twitter-image-preview">
                            </div>
                            <div class="twitter-preview-content">
                                <div class="twitter-preview-url"><?php echo esc_html(strtolower(wp_parse_url(get_site_url(), PHP_URL_HOST))); ?></div>
                                <div class="twitter-preview-title"><?php echo esc_html(@$meta['XAGIO_SEO_TWITTER_TITLE']); ?></div>
                                <div class="twitter-preview-description"><?php echo esc_html(@$meta['XAGIO_SEO_TWITTER_DESCRIPTION']); ?></div>
                            </div>
                        </div>


                    </div>

                </div>
            </div>
        </div>

    </div>
    <div class="xagio-tab-content XAGIO_SEO_META_ROBOTS <?php echo  @$meta['XAGIO_SEO_META_ROBOTS_ENABLE'] ? "on" : "off" ?>">
        <!-- Turn On/Off -->
        <div class="xagio-slider-container page-seo-section-enable">
            <span class="page-seo-section-text"><?php echo  @$meta['XAGIO_SEO_META_ROBOTS_ENABLE'] ? "Enabled" : "Disabled" ?></span>
            <input type="hidden" name="XAGIO_SEO_META_ROBOTS_ENABLE" id="XAGIO_SEO_META_ROBOTS" value="<?php echo  @$meta['XAGIO_SEO_META_ROBOTS_ENABLE'] ? "1" : "0" ?>"/>
            <div class="xagio-slider-frame">
                <span class="xagio-slider-button <?php echo  @$meta['XAGIO_SEO_META_ROBOTS_ENABLE'] ? "on" : "off" ?>" data-with-text data-element="XAGIO_SEO_META_ROBOTS"></span>
            </div>
        </div>

        <div class="xagio-2-column-grid">
            <div>
                <div class="xagio-panel xagio-margin-bottom-medium">
                    <h3 class="xagio-panel-title">Crawler Settings</h3>
                    <p class="xagio-text-info">Index Behavior</p>

                    <select class="xagio-input-select xagio-input-select-gray" name="XAGIO_SEO_META_ROBOTS_INDEX" data-value="<?php echo esc_attr(xagio_empty(@$meta['XAGIO_SEO_META_ROBOTS_INDEX'], 'default')); ?>">
                        <option value="default">From SEO Settings</option>
                        <option value="index">Index</option>
                        <option value="noindex">Do not Index</option>
                    </select>

                    <p class="xagio-text-info">Follow Behavior</p>
                    <select class="xagio-input-select xagio-input-select-gray" name="XAGIO_SEO_META_ROBOTS_FOLLOW" data-value="<?php echo esc_attr(xagio_empty(@$meta['XAGIO_SEO_META_ROBOTS_FOLLOW'], 'default')); ?>">
                        <option value="default">From SEO Settings</option>
                        <option value="follow">Follow</option>
                        <option value="nofollow">Do not Follow</option>
                    </select>

                    <p class="xagio-text-info">Preview</p>
                    <?php
                    if(!empty($robots)) {
                        ?>
                        <?php if($global_robots_settings) {
                            ?>
                            <div class="xagio-margin-bottom-small xagio-global-robots-info">The <b>noindex,follow</b> directive is currently applied based on your global <a href="/wp-admin/admin.php?page=xagio-seo&tab=2&tab_type=<?php echo wp_kses_post($robots_post_type); ?>">SEO Settings</a>. You can adjust this by visiting the SEO settings and disabling the option if needed.</div>
                            <?php
                        }
                        ?>
                        <pre class="xagio-preview-meta-robots" data-global="<?php echo ($global_robots_settings) ? 'noindex,follow' : '' ?>" data-robots="<?php echo wp_kses_post($robots) ?>">&lt;meta name="robots" content="<?php echo wp_kses_post($robots); ?>"/&gt;</pre>
                        <?php
                    } else {
                        ?>
                        <pre class="xagio-preview-meta-robots" data-global="" data-robots="">&lt;meta name="robots" content=""/&gt;</pre>
                        <?php
                    }
                    ?>
                </div>

            </div>
            <div>
                <div class="xagio-panel xagio-margin-bottom-medium">
                    <h3 class="xagio-panel-title">Optional Settings</h3>

                    <fieldset class="xagio-robots-optional" data-value="<?php echo !empty($meta['XAGIO_SEO_META_ROBOTS_ADVANCED']) ? esc_html(join('|', maybe_unserialize($meta['XAGIO_SEO_META_ROBOTS_ADVANCED']))) : ''; ?>">

                        <div class="xagio-slider-container">
                            <input type="hidden" name="XAGIO_SEO_META_ROBOTS_ADVANCED[]" value="" disabled/>
                            <input type="hidden" name="noimageindex" id="opt_noimageindex" value="1"/>
                            <div class="xagio-slider-frame">
                                <span class="xagio-slider-button on" data-element="opt_noimageindex"></span>
                            </div>
                            <p class="xagio-slider-label">Index images on this page</p>
                        </div>

                        <div class="xagio-slider-container">
                            <input type="hidden" name="XAGIO_SEO_META_ROBOTS_ADVANCED[]" value="" disabled/>
                            <input type="hidden" name="noarchive" id="opt_noarchive" value="1"/>
                            <div class="xagio-slider-frame">
                                <span class="xagio-slider-button on" data-element="opt_noarchive"></span>
                            </div>
                            <p class="xagio-slider-label">Show a cached link in search results</p>
                        </div>

                        <div class="xagio-slider-container">
                            <input type="hidden" name="XAGIO_SEO_META_ROBOTS_ADVANCED[]" value="" disabled/>
                            <input type="hidden" name="nosnippet" id="opt_nosnippet" value="1"/>
                            <div class="xagio-slider-frame">
                                <span class="xagio-slider-button on" data-element="opt_nosnippet"></span>
                            </div>
                            <p class="xagio-slider-label">Show a text snippet in search results</p>
                        </div>

                        <div class="xagio-slider-container">
                            <input type="hidden" name="XAGIO_SEO_META_ROBOTS_ADVANCED[]" value="" disabled/>
                            <input type="hidden" name="notranslate" id="opt_notranslate" value="1"/>
                            <div class="xagio-slider-frame">
                                <span class="xagio-slider-button on" data-element="opt_notranslate"></span>
                            </div>
                            <p class="xagio-slider-label">Offer translation of this page in search results</p>
                        </div>

                    </fieldset>
                </div>
                <div class="xagio-panel">
                    <h3 class="xagio-panel-title">Miscellaneous</h3>
                    <p class="xagio-text-info">Canonical URL</p>
                    <input class="xagio-input-text-mini" placeholder="eg. <?php echo  esc_url(get_permalink()); ?>" type="text" id="XAGIO_SEO_CANONICAL_URL" name="XAGIO_SEO_CANONICAL_URL"
                            value="<?php echo esc_attr(@$meta['XAGIO_SEO_CANONICAL_URL']); ?>"/>
                </div>
            </div>
        </div>

    </div>

    <?php if (class_exists('XAGIO_MODEL_SCHEMA')): ?>
    <div class="xagio-tab-content XAGIO_SEO_SCHEMA <?php echo  @$meta['XAGIO_SEO_SCHEMA_ENABLE'] ? "on" : "off" ?>">
        <!-- Turn On/Off -->
        <div class="xagio-slider-container page-seo-section-enable">
            <span class="page-seo-section-text"><?php echo  @$meta['XAGIO_SEO_SCHEMA_ENABLE'] ? "Enabled" : "Disabled" ?></span>
            <input type="hidden" name="XAGIO_SEO_SCHEMA_ENABLE" id="XAGIO_SEO_SCHEMA" value="<?php echo  @$meta['XAGIO_SEO_SCHEMA_ENABLE'] ? "1" : "0" ?>"/>
            <div class="xagio-slider-frame">
                <span class="xagio-slider-button <?php echo  @$meta['XAGIO_SEO_SCHEMA_ENABLE'] ? "on" : "off" ?>" data-element="XAGIO_SEO_SCHEMA" data-with-text></span>
            </div>
        </div>

        <div class="xagio-panel xagio-margin-bottom-medium">
            <h3 class="xagio-panel-title">Schema</h3>
            <p class="xagio-text-info">Please choose one of the options below to add Schema to this page.</p>
            <div class="schema-panels-holder">
                <div class="schema-panel">
                    <h4>Assign Schema</h4>
                    <p>Assign a pre-made schema from Xagio Cloud App</p>
                    <button class="xagio-button xagio-button-primary " type="button"
                            id="assignSchema"><i class="xagio-icon xagio-icon-plus"></i> Assign Schema(s)
                    </button>
                </div>
                <div class="schema-panel">
                    <h4>Generate Schema using AI</h4>
                    <p>Xagio will generate & assign schema for your page using AI</p>

                    <button class="xagio-button xagio-button-primary confirmGenerateAiSchema" type="button">
                        <i class="xagio-icon xagio-icon-ai"></i> Generate Schema
                    </button>
                </div>
                <div class="schema-panel">
                    <h4>Schema Wizard</h4>
                    <p>Generate & assign schema manually using the Xagio AI </p>

                    <button class="xagio-button xagio-button-primary" type="button" data-id="<?php echo absint($post->ID); ?>"
                            id="wizardSchema">
                        <i class="xagio-icon xagio-icon-draw"></i> Schema Wizard
                    </button>
                </div>
            </div>

        </div>

        <div class="xagio-panel xagio-margin-bottom-medium">
            <h3 class="xagio-panel-title">Assigned Schema</h3>
            <?php
            $schemas         = XAGIO_MODEL_SCHEMA::getSchemas($post->ID);

            $selectedSchemas = '';
            if (!empty($schemas)) {
                if (sizeof($schemas) > 0 && $schemas !== FALSE) {
                    $selectedSchemas = [];
                    foreach ($schemas as $s) {
                        $selectedSchemas[] = $s['id'];
                    }
                    $selectedSchemas = join(',', $selectedSchemas);
                }
            }
            ?>

            <input type="hidden" name="XAGIO_SEO_SCHEMAS" id="selectedSchemas" value="<?php echo esc_attr($selectedSchemas); ?>"/>
            <div class="assigned-schemas">

                <?php if (is_array($schemas) && sizeof($schemas) > 0) { ?>
                    <p class="xagio-text-info">You have <?php echo absint(sizeof($schemas)) ?> Schema assigned to your page</p>
                    <div class="assigned-schema-panel">
                        <?php foreach ($schemas as $k => $s) {
                            ?>

                            <div class="schemaTag" data-name="<?php echo esc_attr($s['name']); ?>" data-id="<?php echo esc_attr($s['id']); ?>">

                                <div class="schema-name">
                                    <a title='Edit this Schema' href='https://app.xagio.net/schema?id=<?php echo esc_attr($s['id']); ?>&type=<?php echo esc_attr($s['type']); ?>&name=<?php echo esc_attr($s['name']); ?>&group=' target='_blank'><?php echo esc_html($s['name']); ?></a>
                                </div>
                                <div class="schema-type">
                                    <?php echo esc_attr($s['type']) ?>
                                </div>

                                <div class="removeSchemaTag" title="Unassign this schema from the current page.">
                                    <i class="xagio-icon xagio-icon-delete"></i>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                <?php } else { ?>
                    <p class="xagio-text-info noSchemas">You have no schema assigned to this page.</p>
                <?php } ?>

            </div>
        </div>

        <div class="xagio-panel">
            <h3 class="xagio-panel-title">Validate Schema</h3>
            <p class="xagio-text-info">Check if the schema is Valid or Render the schema to check for errors.</p>

            <div class="xagio-buttons-flex xagio-margin-bottom-medium">
                <button class="xagio-button xagio-button-primary" type="button"
                        data-url="<?php echo esc_url(get_permalink($post->ID)); ?>" id="validateSchema"><i class="xagio-icon xagio-icon-cogs"></i>
                    Validate
                    Schema
                </button>

                <button class="xagio-button xagio-button-primary" type="button" data-id="<?php echo absint($post->ID); ?>"
                        id="renderSchema">
                    <i class="xagio-icon xagio-icon-code"></i> Render Schema(s)
                </button>
            </div>

            <div class="schemaValidationResult">
                <div class="schemaValidationOutput">
                    <i class="xagio-icon xagio-icon-info"></i> Results will be displayed here...
                </div>
            </div>
        </div>

    </div>
    <?php endif; ?>
    <div class="xagio-tab-content XAGIO_SEO_SCRIPTS <?php echo  @$meta['XAGIO_SEO_SCRIPTS_ENABLE'] ? "on" : "off" ?>">
        <!-- Turn On/Off -->
        <div class="xagio-slider-container page-seo-section-enable">
            <span class="page-seo-section-text"><?php echo  @$meta['XAGIO_SEO_SCRIPTS_ENABLE'] ? "Enabled" : "Disabled" ?></span>
            <input type="hidden" name="XAGIO_SEO_SCRIPTS_ENABLE" id="XAGIO_SEO_SCRIPTS" value="<?php echo  @$meta['XAGIO_SEO_SCRIPTS_ENABLE'] ? "1" : "0" ?>"/>
            <div class="xagio-slider-frame">
                <span class="xagio-slider-button <?php echo  @$meta['XAGIO_SEO_SCRIPTS_ENABLE'] ? "on" : "off" ?>" data-with-text data-element="XAGIO_SEO_SCRIPTS"></span>
            </div>
        </div>

        <input type="hidden" value="false" name="XAGIO_SEO_DISABLE_PAGE_HEADER_SCRIPTS"/>
        <input type="hidden" value="false" name="XAGIO_SEO_DISABLE_GLOBAL_HEADER_SCRIPTS"/>

        <input type="hidden" value="false" name="XAGIO_SEO_DISABLE_PAGE_BODY_SCRIPTS"/>
        <input type="hidden" value="false" name="XAGIO_SEO_DISABLE_GLOBAL_BODY_SCRIPTS"/>

        <input type="hidden" value="false" name="XAGIO_SEO_DISABLE_PAGE_FOOTER_SCRIPTS"/>
        <input type="hidden" value="false" name="XAGIO_SEO_DISABLE_GLOBAL_FOOTER_SCRIPTS"/>

        <div class="xagio-panel">
            <h3 class="xagio-panel-title">Header Scrips</h3>

            <div class="xagio-alert xagio-alert-primary xagio-margin-bottom-medium">
                <i class="xagio-icon xagio-icon-info"></i>
                Insert any scripts or styles here that you want to be included on this page (include &lt;script&gt; & &lt;/script&gt;
                and/or &lt;style&gt; & &lt;/style&gt; tags as well).
            </div>

            <textarea class="uk-textarea" rows="6" name="XAGIO_SEO_SCRIPTS_HEADER"
                      placeholder="Paste your code here..."><?php echo esc_textarea(stripslashes_deep(@$meta['XAGIO_SEO_SCRIPTS_HEADER'])); ?></textarea>

            <div class="xagio-script-labels xagio-margin-top-medium">
                <div class="xagio-slider-container">
                    <input type="hidden" name="XAGIO_SEO_DISABLE_PAGE_HEADER_SCRIPTS" id="XAGIO_SEO_DISABLE_PAGE_HEADER_SCRIPTS" value="<?php echo  @$meta['XAGIO_SEO_DISABLE_PAGE_HEADER_SCRIPTS'] ? "1" : "0" ?>"/>
                    <div class="xagio-slider-frame">
                        <span class="xagio-slider-button <?php echo  @$meta['XAGIO_SEO_DISABLE_PAGE_HEADER_SCRIPTS'] ? "on" : "off" ?>" data-element="XAGIO_SEO_DISABLE_PAGE_HEADER_SCRIPTS"></span>
                    </div>
                    <p class="xagio-slider-label">Disable page-specific Header scripts</p>
                </div>

                <div class="xagio-slider-container">
                    <input type="hidden" name="XAGIO_SEO_DISABLE_GLOBAL_HEADER_SCRIPTS" id="XAGIO_SEO_DISABLE_GLOBAL_HEADER_SCRIPTS" value="<?php echo  @$meta['XAGIO_SEO_DISABLE_GLOBAL_HEADER_SCRIPTS'] ? "1" : "0" ?>"/>
                    <div class="xagio-slider-frame">
                        <span class="xagio-slider-button <?php echo  @$meta['XAGIO_SEO_DISABLE_GLOBAL_HEADER_SCRIPTS'] ? "on" : "off" ?>" data-element="XAGIO_SEO_DISABLE_GLOBAL_HEADER_SCRIPTS"></span>
                    </div>
                    <p class="xagio-slider-label">Disable global Header scripts</p>
                </div>
            </div>

        </div>

        <div class="xagio-accordion xagio-margin-top-medium">
            <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                <span>Body Scripts</span>
                <i class="xagio-icon xagio-icon-arrow-down"></i>
            </h3>
            <div class="xagio-accordion-content">
                <div>
                    <div class="xagio-accordion-panel">

                        <textarea class="uk-textarea" rows="6" name="XAGIO_SEO_SCRIPTS_BODY"
                                  placeholder="Paste your code here..."><?php echo esc_textarea(stripslashes_deep(@$meta['XAGIO_SEO_SCRIPTS_BODY'])); ?></textarea>

                        <div class="xagio-script-labels xagio-margin-top-medium">
                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_SEO_DISABLE_PAGE_BODY_SCRIPTS" id="XAGIO_SEO_DISABLE_PAGE_BODY_SCRIPTS" value="<?php echo  @$meta['XAGIO_SEO_DISABLE_PAGE_BODY_SCRIPTS'] ? "1" : "0" ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button <?php echo @$meta['XAGIO_SEO_DISABLE_PAGE_BODY_SCRIPTS'] ? "on" : "off" ?>" data-element="XAGIO_SEO_DISABLE_PAGE_BODY_SCRIPTS"></span>
                                </div>
                                <p class="xagio-slider-label">Disable page-specific Body scripts</p>
                            </div>

                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_SEO_DISABLE_GLOBAL_BODY_SCRIPTS" id="XAGIO_SEO_DISABLE_GLOBAL_BODY_SCRIPTS" value="<?php echo  @$meta['XAGIO_SEO_DISABLE_GLOBAL_BODY_SCRIPTS'] ? "1" : "0" ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button <?php echo @$meta['XAGIO_SEO_DISABLE_GLOBAL_BODY_SCRIPTS'] ? "on" : "off" ?>" data-element="XAGIO_SEO_DISABLE_GLOBAL_BODY_SCRIPTS"></span>
                                </div>
                                <p class="xagio-slider-label">Disable global Body scripts</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="xagio-accordion xagio-margin-top-medium">
            <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                <span>Footer Scripts</span>
                <i class="xagio-icon xagio-icon-arrow-down"></i>
            </h3>
            <div class="xagio-accordion-content">
                <div>
                    <div class="xagio-accordion-panel">

                        <textarea class="uk-textarea" rows="6" name="XAGIO_SEO_SCRIPTS_FOOTER"
                                  placeholder="Paste your code here..."><?php echo esc_textarea(stripslashes_deep(@$meta['XAGIO_SEO_SCRIPTS_FOOTER'])); ?></textarea>

                        <div class="xagio-script-labels xagio-margin-top-medium">
                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_SEO_DISABLE_PAGE_FOOTER_SCRIPTS" id="XAGIO_SEO_DISABLE_PAGE_FOOTER_SCRIPTS" value="<?php echo  @$meta['XAGIO_SEO_DISABLE_PAGE_FOOTER_SCRIPTS'] ? "1" : "0" ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button <?php echo  @$meta['XAGIO_SEO_DISABLE_PAGE_FOOTER_SCRIPTS'] ? "on" : "off" ?>" data-element="XAGIO_SEO_DISABLE_PAGE_FOOTER_SCRIPTS"></span>
                                </div>
                                <p class="xagio-slider-label">Disable page-specific Footer scripts</p>
                            </div>

                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_SEO_DISABLE_GLOBAL_FOOTER_SCRIPTS" id="XAGIO_SEO_DISABLE_GLOBAL_FOOTER_SCRIPTS" value="<?php echo  @$meta['XAGIO_SEO_DISABLE_GLOBAL_FOOTER_SCRIPTS'] ? "1" : "0" ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button <?php echo  @$meta['XAGIO_SEO_DISABLE_GLOBAL_FOOTER_SCRIPTS'] ? "on" : "off" ?>" data-element="XAGIO_SEO_DISABLE_GLOBAL_FOOTER_SCRIPTS"></span>
                                </div>
                                <p class="xagio-slider-label">Disable global Footer scripts</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php if (class_exists('XAGIO_MODEL_AI')): ?>
    <div class="xagio-tab-content XAGIO_SEO_AI_CONTENT">
        <div class="xagio-2-column-65-35-grid">
            <div>
                <div class="xagio-panel">
                    <h3 class="xagio-panel-title">Generate AI Content</h3>

                    <?php $status = XAGIO_MODEL_AI::get_status_for_current_post(); ?>

                    <div class="xagio-ai-buttons xagio-margin-bottom-medium">
                        <select name="ai-writing-style" class="xagio-input-select xagio-input-select-gray" id="ai-writing-style">
                            <option value="" selected>Writing Style</option>
                            <option value="formal">Formal</option>
                            <option value="informative">Informative</option>
                            <option value="conversational">Conversational</option>
                            <option value="technical">Technical</option>
                            <option value="persuasive">Persuasive</option>
                            <option value="creative">Creative</option>
                            <option value="professional">Professional</option>
                            <option value="journalistic">Journalistic</option>
                            <option value="instructional">Instructional</option>
                            <option value="humorous">Humorous</option>
                        </select>

                        <select id="ai-writing-tone" class="xagio-input-select xagio-input-select-gray" name="ai-writing-tone">
                            <option value="" selected>Content Tone</option>
                            <option value="pleasing">Pleasing</option>
                            <option value="friendly">Friendly</option>
                            <option value="authoritative">Authoritative</option>
                            <option value="serious">Serious</option>
                            <option value="playful">Playful</option>
                            <option value="optimistic">Optimistic</option>
                            <option value="neutral">Neutral</option>
                            <option value="objective">Objective</option>
                            <option value="empathetic">Empathetic</option>
                            <option value="casual">Casual</option>
                            <option value="confident">Confident</option>
                            <option value="sympathetic">Sympathetic</option>
                            <option value="encouraging">Encouraging</option>
                            <option value="thoughtful">Thoughtful</option>
                        </select>

                        <button type="button" class="xagio-button xagio-button-primary confirmGenerateAiContent" <?php echo  $status ? "disabled" : "" ?>><i class="xagio-icon <?php echo  $status ? "xagio-icon-sync xagio-icon-spin" : "xagio-icon-check" ?>"></i>
                            Generate
                        </button>
                        <button type="button" class="xagio-button xagio-button-gray insertAiContent" style="display: none"><i class="xagio-icon xagio-icon-arrow-up"></i> Insert</button>
                    </div>


                    <?php wp_editor("", 'aiContentEditor', ['textarea_name' => 'aiContentEditor', 'editor_height' => 425,  'textarea_rows' => 25, 'media_buttons' => false, 'teeny' => false, 'quicktags' => false]); ?>
                </div>
            </div>
            <div>
                <div class="xagio-panel">
                    <h3 class="xagio-panel-title">History</h3>

                    <div class="aiHistoryHolder">

                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="xagio-tab-content">
        <div class="xagio-panel XAGIO_SEO_NOTES">
            <h3 class="xagio-panel-title">Personal Notes</h3>
            <textarea class="xagio-input-textarea" rows="10" name="XAGIO_SEO_NOTES" id="XAGIO_SEO_NOTES"><?php echo esc_textarea(@$meta['XAGIO_SEO_NOTES']) ?></textarea>
        </div>
    </div>
</div>

<div id="seo-disabled" style="display: none">
    <div class="uk-block uk-block-muted uk-block-xagio">

        SEO Has been disabled

        <li class="uk-disabled slider">
            <a class="<?php echo (@$meta['XAGIO_SEO'] == 1 || XAGIO_SEO_FORCE_ENABLE == 1) ? 'on' : ''; ?>" href="#"><?php echo (@$meta['XAGIO_SEO'] == 1 || XAGIO_SEO_FORCE_ENABLE == 1) ? 'On' : 'Off'; ?></a>
            <input type="hidden" name="XAGIO_SEO" id="XAGIO_SEO" value="<?php echo (@$meta['XAGIO_SEO'] == 1 || XAGIO_SEO_FORCE_ENABLE == 1) ? '1' : '0'; ?>"/>
        </li>

    </div>
</div>

<!-- Modal for displaying Rendered Schemas -->
<dialog class="xagio-modal" id="aiContentModal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-code"></i> AI Content</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <button type="button" class="uk-button uk-button-mini uk-button-primary uk-modal-close uk-close"><i class="xagio-icon xagio-icon-close"></i></button>
        <div class="uk-modal-header">
            <h1><i class="xagio-icon xagio-icon-code"></i> AI Generated Content</h1>
        </div>

        <?php
        wp_editor('', 'XAGIO_AI_CONTENT', [
            'text_area_name' => 'XAGIO_AI_CONTENT',
            'editor_height'  => 350,
            'textarea_rows'  => 20
        ]);
        ?>


        <div class="xagio-flex-right xagio-margin-top-large">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Close</button>
        </div>
    </div>

</dialog>

<!-- Modal for displaying Rendered Schemas -->
<dialog class="xagio-modal xagio-modal-lg" id="renderSchemasModal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-code"></i> Render Schema(s)</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">

        <pre id="renderedSchema"><code class="json"></code></pre>

        <div class="xagio-flex-right xagio-margin-top-large">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Close</button>
        </div>
    </div>
</dialog>

<!-- Modal for YouTube -->
<dialog class="xagio-modal xagio-modal-lg" id="youtubeModal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title xagio-modal-flex"><img style="max-height: 42px;" src="<?php echo  esc_url(XAGIO_URL); ?>assets/img/youtubee.webp"/> <span>Search</span></h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <div class="xagio_youtube_search">
            <div class="uk-input-group">
                <input class="xagio-input-text-mini" type="text" id="xagio_youtube_query" placeholder="Look for videos on YouTube..."/>
                <button type="button" id="xagio_youtube_search" class="xagio-button xagio-button-primary"><i class="xagio-icon xagio-icon-search"></i> Search
                </button>
            </div>
            <div class="xagio_youtube_results"><span class="xagio_youtube_results_msg"><i class="xagio-icon xagio-icon-info"></i> Your search results will appear here.</span>
            </div>
            <div class="xagio_youtube_pagination" style="display: none">
                <input type="hidden" id="xagio_youtube_prev_page" value="">
                <input type="hidden" id="xagio_youtube_next_page" value="">
                <input type="hidden" id="xagio_youtube_curr_page" value="">
                <ul class="uk-pagination">
                    <li class="uk-pagination-previous"><a href="#" class="xagio_youtube_prev"><i class="xagio-icon xagio-icon-arrow-left"></i> Previous</a></li>
                    <li class="uk-pagination-next"><a href="#" class="xagio_youtube_next">Next <i class="xagio-icon xagio-icon-arrow-right"></i></a></li>
                </ul>
            </div>
        </div>
        <div class="xagio_youtube_video" style="display: none">
            <button class="xagio-button xagio-button-primary xagio_youtube_back" type="button"><i class="xagio-icon xagio-icon-arrow-left"></i> Go Back</button>
            <input type="hidden" id="xagio_youtube_id"/>
            <div class="xagio_youtube_preview"><img src="<?php echo  esc_url(XAGIO_URL); ?>assets/img/videoPlaceholder.webp"/></div>
            <div class="yt-grid-controls">
                <div>
                    <div class="input-container">
                        <label for="xagio_youtube_title" class="input-label">Title:</label>
                        <input id="xagio_youtube_title" type="text" class="xagio-input-text-mini" disabled>
                    </div>
                    <div class="input-container">
                        <label for="xagio_youtube_url" class="input-label">URL:</label>
                        <input id="xagio_youtube_url" type="text" class="xagio-input-text-mini" disabled>
                    </div>
                </div>
                <div>
                    <div class="input-container">
                        <label for="xagio_youtube_width" class="input-label">Width:</label>
                        <input id="xagio_youtube_width" type="number" class="xagio-input-text-mini" placeholder="eg. 640" value="560">
                    </div>
                    <div class="input-container">
                        <label for="xagio_youtube_height" class="input-label">Height:</label>
                        <input id="xagio_youtube_height" type="number" class="xagio-input-text-mini" placeholder="eg. 480"
                                value="315">
                    </div>
                </div>
                <div>
                    <div class="slider-container uk-margin-bottom">
                        <label class="slider-label">Autoplay <i class="xagio-icon xagio-icon-info"></i></label>
                        <div class="prs-slider-frame ">
                            <input type="hidden" id="xagio_youtube_autoplay" value="0"/><span class="slider-button"
                                    data-element="xagio_youtube_autoplay">OFF</span>
                        </div>
                        <div class="slider-clear"></div>
                    </div>
                    <div class="slider-container">
                        <label class="slider-label">Strip <i class="xagio-icon xagio-icon-info"></i></label>
                        <div class="prs-slider-frame">
                            <input type="hidden" id="xagio_youtube_strip" value="0"/><span class="slider-button"
                                    data-element="xagio_youtube_strip">OFF</span>
                        </div>
                        <div class="slider-clear"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
            <button type="button" class="xagio-button xagio-button-primary xagio_youtube_insert"><i class="xagio-icon xagio-icon-check"></i> Insert Video</button>
        </div>
    </div>
</dialog>

<!-- Modal for PixaBay -->
<dialog class="xagio-modal xagio-modal-lg" id="pixabayModal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title xagio-modal-flex"><img style="max-height: 42px;" src="<?php echo  esc_url(XAGIO_URL); ?>assets/img/pixlogo.webp"/> <span>Search</span></h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <div class="xagio_pixabay_search_area">
            <div class="uk-input-group">
                <input class="xagio-input-text-mini" type="text" id="xagio_pixabay_query" placeholder="type in your search query..."/>
                <button type="button" id="xagio_pixabay_search" class="xagio-button xagio-button-primary"><i class="xagio-icon xagio-icon-search"></i> Search
                </button>
            </div>
            <div class="xagio_pixabay_results"><span class="xagio_pixabay_results_msg"><i class="xagio-icon xagio-icon-info"></i> Your search results will appear here.</span>
            </div>
        </div>
        <div class="xagio_pixabay_image_area" style="display: none;">
            <button class="xagio-button xagio-button-primary xagio_pixabay_back" type="button"><i class="xagio-icon xagio-icon-arrow-left"></i> Go Back</button>
            <div class="xagio_pixabay_image_container"><img src="" class="xagio_pixabay_image_selected"/></div>
            <div class="input-container">
                <label for="xagio_pixabay_image_title" class="input-label">Image Title:</label>
                <input id="xagio_pixabay_image_title" type="text" class="xagio-input-text-mini" placeholder="eg. My Image Title">
            </div>
            <div class="input-container">
                <label for="xagio_pixabay_image_alt" class="input-label">Image Alt:</label>
                <input id="xagio_pixabay_image_alt" type="text" class="xagio-input-text-mini" placeholder="eg. My Image Title #2">
            </div>
        </div>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
            <button type="button" class="xagio-button xagio-button-primary xagio_pixabay_insert"><i class="xagio-icon xagio-icon-check"></i> Insert Image</button>
        </div>
    </div>
</dialog>

<!-- Modal for Schema Wizard-->
<dialog class="xagio-modal" id="wizardSchemaModal">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-draw"></i> Schema Wizard</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <input type="hidden" id="sw_schema_type" value="0"/>
        <input type="hidden" id="sw_post_id" value="0"/>

        <div class="swSteps">

            <!-- Pick a Schema -->
            <div class="swStep1">
                <div class="xagio-alert xagio-alert-primary xagio-margin-bottom-medium">
                    <i class="xagio-icon xagio-icon-info"></i>
                    This wizard will help you quickly generate schemas for certain pages/posts without having to go to
                    Schema Editor and do all the work manually. Simply, select the type of your current page/post, fill
                    in the blanks and schema will be automatically generated for current page/post.
                </div>

                <div class="modal-label">What is this post about?</div>

                <select id="swTypes" class="xagio-input-select xagio-input-select-gray">

                </select>
<!--                <div class="swTypes">-->
<!---->
<!--                </div>-->
            </div>

            <!-- Fill the Fields -->
            <div class="swStep2">
                <div class="xagio-alert xagio-alert-primary">
                    <i class="xagio-icon xagio-icon-info"></i>
                    Awesome, you have selected <span class="swSelectedType">...</span> schema type. Now, fill in the
                    blanks and press <b>Next</b> once you're ready.
                </div>

                <div class="swFields">

                </div>
            </div>

            <!-- Name this schema -->
            <div class="swStep3">
                <div class="xagio-alert xagio-alert-primary xagio-margin-bottom-medium">
                    <i class="xagio-icon xagio-icon-info"></i>
                    <label for="swName">In the field below, set up a unique name by which you will know that this
                        generated schema belongs to this page/post inside the Schema Editor.</label>

                </div>
                <input id="swName" name="swName" placeholder="eg. Web Design services page" class="xagio-input-text-mini"/>
                <div class="xagio-alert xagio-alert-primary xagio-margin-top-medium">
                    <i class="xagio-icon xagio-icon-info"></i> If you want to adjust this generated schema further, press the <b>Generate Schema</b> button below and once the schema is generated, use the
                    <a href="https://app.xagio.net/schema" target="_blank">Schema Editor</a> from <b>xagio</b> Panel to add more properties & fields according to your needs.
                </div>
            </div>

        </div>


        <div class="xagio-flex-space-between xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Close</button>

            <div class="xagio-flex-right xagio-flex-gap-medium">
                <button type="button" class="xagio-button xagio-button-primary swPreviousStep" style="display: none;"><i class="xagio-icon xagio-icon-arrow-left"></i> Previous</button>
                <button type="button" class="xagio-button xagio-button-primary swNextStep" style="display: none;"><i class="xagio-icon xagio-icon-arrow-right"></i> Next</button>
                <button type="button" class="xagio-button xagio-button-primary swFinish" style="display: none;"><i class="xagio-icon xagio-icon-cogs"></i> Generate Schema</button>
            </div>
        </div>
    </div>
</dialog>

<!-- Modal for Schema Assign -->
<dialog class="xagio-modal xagio-modal-lg" id="remoteSchemas">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-home"></i> Local Schema(s)</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>
    <div class="xagio-modal-body">
        <div class="xagio-alert xagio-alert-primary">
            <i class="xagio-icon xagio-icon-info text-info"></i>
            Below are the schemas that you have created using Schema Editor on Panel.
            Press the icon next to schema to assign it.
        </div>

        <div class="schema-actions xagio-margin-top-medium">

            <div class="xagio-flex xagio-flex-gap-medium">
                <div>
                    <div class="modal-label">Schema Group</div>
                    <select data-default-group="<?php echo esc_attr(XAGIO_DOMAIN); ?>" class="xagio-input-select xagio-input-select-gray manage-schema-groups">
                        <option value="">– Schema Group –</option>
                    </select>
                </div>
                <div>
                    <div class="modal-label">Schema Type</div>
                    <select class="xagio-input-select xagio-input-select-gray manage-schema-types">
                        <option value="">– Schema Type –</option>
                    </select>
                </div>
            </div>
            <div>
                <div class="modal-label">Search</div>
                <input type="search" class="xagio-input-text-mini manage-schema-search" placeholder="Search Schemas..."/>
            </div>

        </div>

        <div class="schema-box xagio-margin-top-medium">

            <div class="schema-container-title xagio-margin-bottom-medium">
                <span><span class="schema-count">...</span> Schemas Found</span>
                <button type="button" class="xagio-button xagio-button-primary xagio-button-mini schema-toggle-collapse" data-value="expanded"><i class="xagio-icon xagio-icon-arrow-down"></i></button>
            </div>

            <div class="schema-container localSchemas">

            </div>

        </div>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Close</button>
        </div>

    </div>
</dialog>

<!-- Template - Schema type -->
<div class="xagio-accordion xagio-accordion-opened xagio-accordion-mini xagio-accordion-gray xagio-margin-bottom-medium schema-type-container template">
    <h3 class="xagio-accordion-title xagio-accordion-panel-title">
        <span class="schema-type-container-name">Local Business</span>
        <i class="xagio-icon xagio-icon-arrow-down"></i>
    </h3>
    <div class="xagio-accordion-content">
        <div>
            <div class="xagio-accordion-panel schema-type-container-schemas">

            </div>
        </div>
    </div>
</div>

<!-- Template - Schema loading -->
<div class="schema-loading template">
    <h4><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></h4>
    <p>Fetching Schemas ...</p>
</div>

<!-- Template - Schema tag -->
<div class="schema-tag template" data-id="0">

    <div class="schema-name"></div>

    <div class="schema-buttons">
        <a class="xagio-button xagio-button-primary xagio-button-mini schema-edit" title="Edit this Schema" href="" target="_blank"><i class="xagio-icon xagio-icon-edit"></i></a>

        <div class="schema-add xagio-button xagio-button-primary xagio-button-mini" title="Assign this schema to the current page.">
            <i class="xagio-icon xagio-icon-arrow-down"></i>
        </div>

        <div class="schema-close xagio-button xagio-button-primary xagio-button-mini" title="Unassign this schema from the current page.">
            <i class="xagio-icon xagio-icon-close"></i>
        </div>
    </div>
</div>

<!-- Modal for AI prices -->
<dialog id="aiPrice" class="xagio-modal">

    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-info"></i> Requesting AI Input</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>

    <div class="xagio-modal-body">
        <input type="hidden" id="suggestion-main-keyword" value="">
        <p class="ai-heading">
            <i class="xagio-icon xagio-icon-ai"></i> You are about to request <span class="input-name">AI Generated Content</span>. Below are the details regarding this operation.
        </p>

        <div class="xagio-flex-even-columns xagio-flex-gap-medium xagio-margin-top-medium">
            <div class="ai-avg-price-box">
                Average XAGS cost for this is: <span class="average-price">0</span>
            </div>
            <div class="ai-avg-price-box">
                Your remaining XAGS are: <span class="ai-credits">0</span>
            </div>
        </div>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <select name="prompt_id" id="prompt_id" class="xagio-input-select xagio-input-select-gray">
            </select>
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
            <button type="button" class="xagio-button xagio-button-primary makeAiRequest" data-target=""><i class="xagio-icon xagio-icon-check"></i> Continue</button>
        </div>
    </div>
</dialog>

<!-- AI Upgrade Modal -->
<dialog class="xagio-modal" id="aiUpgrade">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-ai"></i> Upgrade your Account</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>

    <div class="xagio-modal-body">
        <h3>You'll need to upgrade your account in order to use AI features!</h3>
        <p>Click on the <b>Upgrade Now</b> button to be redirected to an upgrade page where you can get <b>Xagio Pro Account</b> and unlock AI Features that it offers!</p>

        <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
            <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
            <a class="xagio-button xagio-button-primary" href="https://xagio.net/ai" target="_blank"><i class="xagio-icon xagio-icon-check"></i> Upgrade Now!</a>
        </div>
    </div>
</dialog>