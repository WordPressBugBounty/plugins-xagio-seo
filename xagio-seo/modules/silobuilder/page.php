<?php
/**
 * Type: SUBMENU
 * Page_Title: Silo Builder
 * Menu_Title: Silo Builder
 * Capability: manage_options
 * Slug: xagio-silobuilder
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: xagio_datatables,jquery-ui-core,jquery-ui-sortable,jquery-ui-draggable,xagio_tablesorter,xagio_jstree,xagio_select2,xagio_tagsinput,jquery-ui-core,xagio_jquery_sortable,xagio_multisortable,xagio_jqcloud,xagio_panzoom,xagio_mousewheel,xagio_flowchart,xagio_selectables,xagio_silobuilder
 * Css: xagio_multi_draggable,xagio_datatables,xagio_jstree,xagio_select2,xagio_tagsinput,xagio_jqcloud,xagio_flowchart,xagio_selectables,xagio_projectplanner,xagio_silobuilder
 * Position: 8
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


$MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');
?>
<div class="xagio-main-header">
    <img class="logo-image repo-xagio" src="<?php echo  esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        Silo Builder
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
            <span>Create a visual structure of your website</span>
            <i class="xagio-icon xagio-icon-arrow-down"></i>
        </h3>
        <div class="xagio-accordion-content">
            <div>
                <div class="xagio-accordion-panel"></div>
            </div>
        </div>
    </div>

    <!-- Projects Silo -->
    <div class="project-silo">

        <div class="project-silo-action-buttons">
            <a data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Create a new Page/Post" class="xagio-button xagio-button-primary silo-add-page-post"><i class="xagio-icon xagio-icon-file"></i> New Page/Post
            </a>

            <button class="xagio-button xagio-button-primary xagio-button-reset-all" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Perform a HARD reset and clear out all the operators from your Pages and Posts SILOs. This operation is irreversible, proceed with caution.">
                <i class="xagio-icon xagio-icon-warning"></i> HARD Reset
            </button>

            <button data-xagio-tooltip
                    data-xagio-tooltip-position="bottom"
                    data-xagio-title="You can press DELETE on your keyboard to remove operators/links instead of clicking here."
                    type="button" class="xagio-button xagio-button-primary silo-remove"><i
                        class="xagio-icon xagio-icon-delete"></i> Delete
            </button>

            <button data-xagio-tooltip
                    data-xagio-tooltip-position="bottom"
                    data-xagio-title="You can press Control (CTRL) + S keys to save changes instead of clicking here." type="button"
                    class="xagio-button xagio-button-primary silo-save"><i
                        class="xagio-icon xagio-icon-save"></i> Save Pages
            </button>
        </div>

        <!-- This is the tabbed navigation containing the toggling elements -->
        <ul class="xagio-tab main-nav">
            <li class="xagio-tab-active silo-pages-tab"><a href="#">Pages</a></li>
            <li class="silo-posts-tab"><a href="#">Posts</a></li>
            <li class="silo-links-tab"><a href="#">Links</a></li>
        </ul>

        <!-- This is the container of the content items -->
        <div id="silo-tabs" class="xagio-tab-content-holder">
            <div class="xagio-tab-content">
                <div class="xagio-2-column-25-75-grid">
                    <div class="xagio-column-1">
                        <button type="button" class="xagio-button xagio-button-primary xagio-button-big xagio-button-generate-silo"><i class="xagio-icon xagio-icon-check"></i> Generate SILO from Website</button>

                        <div class="settings xagio-accordion xagio-margin-top-medium xagio-accordion-opened">
                            <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                <span>Canvas Settings</span>
                                <i class="xagio-icon xagio-icon-arrow-down"></i>
                            </h3>
                            <div class="xagio-accordion-content">
                                <div>
                                    <div class="xagio-accordion-panel">
                                        <form class="silo-settings-save">

                                            <label class="xagio-label-text" for="pages_line_width">Line Thickness:</label>
                                            <div class="xagio-flex-row xagio-min-height-40">
                                                <input type="range" name="line_thickness" id="pages_line_width" class="xagio-range" min="1" max="20" value="2"/>
                                            </div>

                                            <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                                                <div style="flex-grow: 2">
                                                    <label class="xagio-label-text" for="pages_line_type">Line Type:</label>
                                                    <select name="line_type" id="pages_line_type" class="xagio-input-select xagio-input-select-gray">
                                                        <option value="">–– Select ––</option>
                                                        <option value="0">Solid</option>
                                                        <option value="15" selected="selected">Dashed</option>
                                                        <option value="3">Dotted</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="xagio-label-text" for="pages_line_color">Line Color:</label>
                                                    <div class="xagio-color-swatch">
                                                        <input type="color" name="line_color" id="pages_line_color" class="color-picker" value="#559acc"/>
                                                    </div>
                                                </div>
                                            </div>

                                            <label class="xagio-label-text" for="pages_canvas_size">Canvas Size:</label>
                                            <div class="xagio-flex-row xagio-min-height-40">
                                                <input type="range" name="canvas_size" id="pages_canvas_size" class="xagio-range" step="1000" min="5000" max="20000"/>
                                            </div>

                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="xagio-panel xagio-margin-top-medium">
                            <div class="silo-pages">
                                <div class="silo-page">

                                    <table class="xagio-table-silo siloPagesTable" cellspacing="0" width="100%">
                                        <thead>
                                        <tr>
                                            <th>Pages</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td class="xagio-text-center"><i class="xagio-icon xagio-icon-refresh xagio-icon-spin"></i> Loading</td>
                                        </tr>
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="xagio-column-2">
                        <div class="silo-area">
                            <div class="silo-tabs" data-type="pages">
                                <ul class="xagio-tab xagio-tab-mini">
                                    <li data-xagio-tooltip data-xagio-title="Add a new SILO."><a href="#" class="new-silo"><i class="xagio-icon xagio-icon-plus"></i></a></li>
                                </ul>
                            </div>
                            <div class="silo-container">
                                <div class="navigation-controls">
                                    <div class="navigation-arrow" data-type="up" data-xagio-tooltip data-xagio-title="You can use arrow key UP to move canvas in this direction">
                                        <i class="xagio-icon xagio-icon-arrow-up"></i></div>
                                    <div class="navigation-arrow" data-type="down" data-xagio-tooltip data-xagio-title="You can use arrow key DOWN to move canvas in this direction">
                                        <i class="xagio-icon xagio-icon-arrow-down"></i></div>
                                    <div class="navigation-arrow" data-type="left" data-xagio-tooltip data-xagio-title="You can use arrow key LEFT to move canvas in this direction">
                                        <i class="xagio-icon xagio-icon-arrow-left"></i></div>
                                    <div class="navigation-arrow" data-type="right" data-xagio-tooltip data-xagio-title="You can use arrow key RIGHT to move canvas in this direction">
                                        <i class="xagio-icon xagio-icon-arrow-right"></i></div>
                                </div>
                                <div class="silo pages">


                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="xagio-tab-content">
                <div class="xagio-2-column-25-75-grid">
                    <div class="xagio-column-1">
                        <button type="button" class="xagio-button xagio-button-primary xagio-button-big xagio-button-generate-silo"><i class="xagio-icon xagio-icon-check"></i> Generate SILO from Website</button>

                        <div class="settings xagio-accordion xagio-margin-top-medium xagio-accordion-opened">
                            <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                <span>Canvas Settings</span>
                                <i class="xagio-icon xagio-icon-arrow-down"></i>
                            </h3>
                            <div class="xagio-accordion-content">
                                <div>
                                    <div class="xagio-accordion-panel">
                                        <form class="silo-settings-save">

                                            <h3 class="pop">Categories</h3>

                                            <label class="xagio-label-text" for="posts_line_width1">Line Thickness:</label>
                                            <div class="xagio-flex-row xagio-min-height-40">
                                                <input type="range" name="line_category_thickness" id="posts_line_width1" class="xagio-range" min="1" max="20" value="2"/>
                                            </div>

                                            <label class="xagio-label-text" for="posts_line_type1">Line Type:</label>
                                            <select name="line_category_type" id="posts_line_type1" class="xagio-input-select xagio-input-select-gray">
                                                <option value="">–– Select ––</option>
                                                <option value="0">Solid</option>
                                                <option value="15" selected="selected">Dashed</option>
                                                <option value="3">Dotted</option>
                                            </select>

                                            <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                                                <div>
                                                    <label class="xagio-label-text" for="posts_line_color1">Line Color:</label>
                                                    <div class="xagio-color-swatch">
                                                        <input type="color" name="line_category_color" id="posts_line_color1" value="#729d9a" class="color-picker"/>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="xagio-label-text" for="posts_box_color1">Box Color:</label>
                                                    <div class="xagio-color-swatch">
                                                        <input type="color" value="#d1fffc" name="box_category_color" id="posts_box_color1" class="color-picker"/>
                                                    </div>
                                                </div>
                                            </div>

                                            <h3 class="pop">Tags</h3>

                                            <label class="xagio-label-text" for="posts_line_width2">Line Thickness:</label>
                                            <div class="xagio-flex-row xagio-min-height-40">
                                                <input type="range" name="line_tag_thickness" id="posts_line_width2" class="xagio-range" min="1" max="20" value="2"/>
                                            </div>


                                            <label class="xagio-label-text" for="posts_line_type2">Line Type:</label>
                                            <select name="line_tag_type" id="posts_line_type2" class="xagio-input-select xagio-input-select-gray">
                                                <option value="">–– Select ––</option>
                                                <option value="0">Solid</option>
                                                <option value="15" selected="selected">Dashed</option>
                                                <option value="3">Dotted</option>
                                            </select>

                                            <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                                                <div>
                                                    <label class="xagio-label-text" for="posts_line_color2">Line Color:</label>
                                                    <div class="xagio-color-swatch">
                                                        <input type="color" name="line_tag_color" id="posts_line_color2" value="#989898"  class="color-picker" />
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="xagio-label-text" for="posts_box_color2">Box Color:</label>
                                                    <div class="xagio-color-swatch">
                                                        <input type="color" value="#dbdbdb" name="box_tag_color" id="posts_box_color2"  class="color-picker" />
                                                    </div>
                                                </div>
                                            </div>

                                            <label class="xagio-label-text" for="posts_canvas_size">Canvas Size:</label>
                                            <div class="xagio-flex-row xagio-min-height-40">
                                                <input type="range" name="canvas_size" id="posts_canvas_size" class="xagio-range" step="1000" min="5000" max="20000"/>
                                            </div>


                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="xagio-accordion xagio-margin-bottom-medium xagio-margin-top-medium">
                            <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                <span>Permalink Settings</span>
                                <i class="xagio-icon xagio-icon-arrow-down"></i>
                            </h3>
                            <div class="xagio-accordion-content">
                                <div>
                                    <div class="xagio-accordion-panel">
                                        <form class="silo-permalinks-save" method="post">

                                            <?php
                                            wp_nonce_field('update-permalink'); ?>

                                            <ul class="xagio-tab xagio-tab-mini">
                                                <li class="xagio-tab-active"><a href="">Common Settings</a></li>
                                                <li><a href="">Optional</a></li>
                                            </ul>

                                            <div class="xagio-tab-content-holder">
                                                <div class="xagio-tab-content">
                                                    <div>
                                                        <label class="xagio-label-text"><input name="selection" class="permalink-radio" type="radio" value=""> Plain</label>
                                                        <label class="xagio-label-text"><input name="selection" class="permalink-radio" type="radio" value="/%year%/%monthnum%/%day%/%postname%/"> Day and name</label>
                                                        <label class="xagio-label-text"><input name="selection" class="permalink-radio" type="radio" value="/%year%/%monthnum%/%postname%/"> Month and name</label>
                                                        <label class="xagio-label-text"><input name="selection" class="permalink-radio" type="radio" value="/archives/%post_id%"> Numeric</label>
                                                        <label class="xagio-label-text"><input name="selection" class="permalink-radio" type="radio" value="/%postname%/" checked> Post name</label>
                                                        <label class="xagio-label-text"><input name="selection" id="custom_selection" type="radio" value="custom"> Custom Structure </label>
                                                    </div>
                                                    <input name="permalink_structure" id="permalink_structure" type="text" value="/%postname%/" class="xagio-input-text-mini silo-permalink-input">
                                                    <!-- Permalink Tags -->
                                                    <label class="xagio-label-text">Available tags</label>

                                                    <div id="permalink-tags">
                                                        <button type="button" class="xagio-button-tag permalink-button" aria-label="year (The year of the post, four digits, for example 2004.)" data-added="year added to permalink structure" data-used="year (already used in permalink structure)">
                                                            %year%
                                                        </button>

                                                        <button type="button" class="xagio-button-tag permalink-button" aria-label="monthnum (Month of the year, for example 05.)" data-added="monthnum added to permalink structure" data-used="monthnum (already used in permalink structure)">
                                                            %monthnum%
                                                        </button>
                                                        <button type="button" class="xagio-button-tag permalink-button" aria-label="day (Day of the month, for example 28.)" data-added="day added to permalink structure" data-used="day (already used in permalink structure)">
                                                            %day%
                                                        </button>

                                                        <button type="button" class="xagio-button-tag permalink-button" aria-label="hour (Hour of the day, for example 15.)" data-added="hour added to permalink structure" data-used="hour (already used in permalink structure)">
                                                            %hour%
                                                        </button>
                                                        <button type="button" class="xagio-button-tag permalink-button" aria-label="minute (Minute of the hour, for example 43.)" data-added="minute added to permalink structure" data-used="minute (already used in permalink structure)">
                                                            %minute%
                                                        </button>

                                                        <button type="button" class="xagio-button-tag permalink-button" aria-label="second (Second of the minute, for example 33.)" data-added="second added to permalink structure" data-used="second (already used in permalink structure)">
                                                            %second%
                                                        </button>
                                                        <button type="button" class="xagio-button-tag permalink-button" aria-label="post_id (The unique ID of the post, for example 423.)" data-added="post_id added to permalink structure" data-used="post_id (already used in permalink structure)">
                                                            %post_id%
                                                        </button>
                                                        <button type="button" class="xagio-button-tag permalink-button" aria-label="postname (already used in permalink structure)" data-added="postname added to permalink structure" data-used="postname (already used in permalink structure)">
                                                            %postname%
                                                        </button>
                                                        <button type="button" class="xagio-button-tag permalink-button" aria-label="category (Category slug. Nested sub-categories appear as nested directories in the URL.)" data-added="category added to permalink structure" data-used="category (already used in permalink structure)">
                                                            %category%
                                                        </button>
                                                        <button type="button" class="xagio-button-tag permalink-button" aria-label="author (A sanitized version of the author name.)" data-added="author added to permalink structure" data-used="author (already used in permalink structure)">
                                                            %author%
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="xagio-tab-content">
                                                    <label class="xagio-label-text" for="category_base">Category base:</label>
                                                    <input name="category_base" id="category_base" type="text" value="and" class="xagio-input-text-mini">
                                                    <label class="xagio-label-text" for="tag_base">Tag base:</label>
                                                    <input name="tag_base" id="tag_base" type="text" value="also" class="xagio-input-text-mini">
                                                </div>
                                            </div>

                                            <p class="submit"><input type="submit" name="submit" id="submit" class="xagio-button xagio-button-primary permalink-save-button" value="Save Changes"></p>

                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="xagio-panel xagio-margin-top-medium">
                            <div class="silo-pages">

                                <div class="silo-post">

                                    <table class="xagio-table-silo siloPostsTable" cellspacing="0" width="100%">
                                        <thead>
                                        <tr>
                                            <th>Posts</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td class="xagio-text-center"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i>
                                                Loading
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>

                                </div>

                            </div>
                        </div>

                    </div>
                    <div class="xagio-column-2">
                        <div class="silo-area">
                            <div class="silo-tabs" data-type="posts">
                                <ul class="xagio-tab xagio-tab-mini">
                                    <li data-xagio-tooltip data-xagio-title="Add a new SILO."><a href="#" class="new-silo"><i class="xagio-icon xagio-icon-plus"></i></a></li>
                                </ul>
                            </div>

                            <div class="silo-categories-tags">
                                <div class="silo-categories-holder">
                                    <h3 class="pop">Categories</h3>
                                    <button type="button" class="xagio-button xagio-button-primary xagio-button-mini silo-categories-tags-button add-category" data-xagio-tooltip data-xagio-title="Add a new category."><i class="xagio-icon xagio-icon-plus"></i></button>
                                    <button type="button" class="xagio-button xagio-button-primary xagio-button-mini silo-categories-tags-button hide-all-categories" data-xagio-tooltip data-xagio-title="Hide all categories."><i class="xagio-icon xagio-icon-eye-slash"></i></button>
                                    <div class="silo-categories"></div>
                                </div>

                                <div class="silo-tags-holder">
                                    <h3 class="pop">Tags</h3>
                                    <button type="button" class="xagio-button xagio-button-primary xagio-button-mini silo-categories-tags-button add-tags" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Add a new tag."><i class="xagio-icon xagio-icon-plus"></i></button>
                                    <button type="button" class="xagio-button xagio-button-primary xagio-button-mini silo-categories-tags-button hide-all-tags" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="Hide all tags."><i class="xagio-icon xagio-icon-eye-slash"></i></button>
                                    <div class="silo-tags"></div>
                                </div>
                            </div>

                            <div class="silo-container">
                                <div class="navigation-controls">
                                    <div class="navigation-arrow" data-type="up"><i class="xagio-icon xagio-icon-arrow-up"></i></div>
                                    <div class="navigation-arrow" data-type="down"><i class="xagio-icon xagio-icon-arrow-down"></i>
                                    </div>
                                    <div class="navigation-arrow" data-type="left"><i class="xagio-icon xagio-icon-arrow-left"></i>
                                    </div>
                                    <div class="navigation-arrow" data-type="right"><i class="xagio-icon xagio-icon-arrow-right"></i>
                                    </div>
                                </div>
                                <div class="silo posts"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="xagio-tab-content">
                <div class="xagio-2-column-25-75-grid">
                    <div class="xagio-column-1">

                        <button type="button" class="xagio-button xagio-button-primary xagio-button-big generate-silo-links">
                            <i class="xagio-icon xagio-icon-check"></i> Generate SILO from Website
                        </button>

                        <div class="settings xagio-accordion xagio-margin-top-medium xagio-accordion-opened">
                            <h3 class="xagio-accordion-title xagio-accordion-panel-title">
                                <span>Canvas Settings</span>
                                <i class="xagio-icon xagio-icon-arrow-down"></i>
                            </h3>
                            <div class="xagio-accordion-content">
                                <div>
                                    <div class="xagio-accordion-panel">
                                        <form class="silo-settings-save">

                                            <h3 class="pop">Internal</h3>

                                            <label class="xagio-label-text" for="posts_line_width1">Line Thickness:</label>
                                            <div class="xagio-flex-row xagio-min-height-40">
                                                <input type="range" name="line_category_thickness" id="posts_line_width1" class="xagio-range" min="1" max="20" value="2"/>
                                            </div>


                                            <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                                                <div style="flex-grow: 2">
                                                    <label class="xagio-label-text" for="internal_links_line_type">Line Type:</label>
                                                    <select name="internal_line_type" id="internal_links_line_type" class="xagio-input-select xagio-input-select-gray">
                                                        <option value="">–– Select ––</option>
                                                        <option value="0">Solid</option>
                                                        <option value="15">Dashed</option>
                                                        <option value="3">Dotted</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="xagio-label-text" for="internal_line_color">Line Color:</label>
                                                    <div class="xagio-color-swatch">
                                                        <input type="color" name="internal_line_color" id="internal_links_line_color" value="#559acc" class="color-picker" />
                                                    </div>
                                                </div>
                                            </div>


                                            <h3 class="pop">External</h3>


                                            <label class="xagio-label-text" for="external_line_thickness">Line Thickness:</label>
                                            <div class="xagio-flex-row xagio-min-height-40">
                                                <input type="range" name="external_line_thickness" id="external_links_line_width" class="xagio-range" min="1" max="20" value="2"/>
                                            </div>


                                            <label class="xagio-label-text" for="external_links_line_type">Line Type:</label>
                                            <div class="uk-form-controls">
                                                <select name="external_line_type" id="external_links_line_type" class="xagio-input-select xagio-input-select-gray">
                                                    <option value="">–– Select ––</option>
                                                    <option value="0">Solid</option>
                                                    <option value="15">Dashed</option>
                                                    <option value="3">Dotted</option>
                                                </select>
                                            </div>

                                            <div class="xagio-flex-even-columns xagio-flex-gap-medium">
                                                <div>
                                                    <label class="xagio-label-text" for="external_links_line_color">Line Color:</label>
                                                    <div class="xagio-color-swatch">
                                                        <input type="color" name="external_line_color" id="external_links_line_color" value="#559acc" class="color-picker" />
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="xagio-label-text" for="links_external_color">Box
                                                        Color:</label>
                                                    <div class="xagio-color-swatch">
                                                        <input type="color" name="external_color" id="links_external_color" value="#2b2b2b" class="color-picker" />
                                                    </div>
                                                </div>
                                            </div>

                                            <label class="xagio-label-text" for="links_canvas_size">Canvas Size:</label>
                                            <div class="xagio-flex-row xagio-min-height-40">
                                                <input type="range" name="canvas_size" id="links_canvas_size" class="xagio-range" step="1000" min="5000" max="20000"/>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="xagio-panel xagio-margin-top-medium">
                            <div class="silo-pages">
                                <div class="silo-page">

                                    <table class="xagio-table-silo siloPagesTableLinks" cellspacing="0" width="100%">
                                        <thead>
                                        <tr>
                                            <th>Title</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td colspan="1" class="xagio-text-center"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Loading</td>
                                        </tr>
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="xagio-column-1">
                        <div class="silo-area">
                            <div class="silo-tabs" data-type="links">
                                <ul class="xagio-tab xagio-tab-mini">
                                    <li data-xagio-tooltip data-xagio-title="Add a new SILO."><a href="#" class="new-silo"><i class="xagio-icon xagio-icon-plus"></i></a></li>
                                </ul>
                            </div>
                            <div class="silo-container">
                                <div class="silo links"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- .wrap -->

    </div>


    <div class="silo-context-menu template">

        <div>

            <input type="text" class="operator_h1" placeholder="eg. My Page H1" value="">
            <input type="text" class="operator_title" placeholder="eg. My Page Title" value="">
            <div class="uk-grid uk-grid-collapse">
                <div class="uk-grid-width-auto">
                    <span class="operator_slug_before">...</span>
                </div>
                <div class="uk-grid-width-expand">
                    <input type="text" class="operator_slug" placeholder="eg. mypagetitle" value="">
                </div>
            </div>
            <textarea class="operator_description" placeholder="eg. This page is about..."></textarea>

            <label class="ps-label ps-label-block">Title Length <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Displays the current number of characters in SEO Title."></i></label>
            <small class="ps-metric">

                <div class="count-seo-container-left">

                    <i class="xagio-icon xagio-icon-desktop" data-xagio-tooltip data-xagio-title="Desktop devices limits."></i>
                    <span class="count-seo-bold">
                                    <span class="count-seo-title">0</span> / 70
                                </span>
                    chars.

                </div>

                <div class="count-seo-container-right">

                    <i class="xagio-icon xagio-icon-mobile" data-xagio-tooltip data-xagio-title="Mobile devices limits."></i>
                    <span class="count-seo-bold">
                                    <span class="count-seo-title-mobile">0</span> / 78
                                </span>
                    chars.

                </div>

                <div class="clear"></div>

            </small>

            <label class="ps-label ps-label-block">Description Length <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="Displays the current number of characters in SEO Description."></i></label>
            <small class="ps-metric">

                <div class="count-seo-container-left">

                    <i class="xagio-icon xagio-icon-desktop" data-xagio-tooltip data-xagio-title="Desktop devices limits."></i>
                    <span class="count-seo-bold">
                                    <span class="count-seo-description">0</span> / 300
                                </span>
                    chars.

                </div>

                <div class="count-seo-container-right">

                    <i class="xagio-icon xagio-icon-mobile" data-xagio-tooltip data-xagio-title="Mobile devices limits."></i>
                    <span class="count-seo-bold">
                                    <span class="count-seo-description-mobile">0</span> / 120
                                </span>
                    chars.

                </div>

                <div class="clear"></div>

            </small>

        </div>

        <div class="silo-context-menu-actions">
            <button class="flat-button flat-button-success context-menu-save" type="button"><i class="xagio-icon xagio-icon-check"></i>
                Apply
            </button>
            <button class="flat-button flat-button-danger context-menu-discard" type="button"><i class="xagio-icon xagio-icon-close"></i> Discard
            </button>
        </div>
    </div>

    <dialog id="silo-generate-modal" class="xagio-modal">
        <div class="xagio-modal-header">
            <h3 class="xagio-modal-title">
                <i class="xagio-icon xagio-icon-cogs"></i> Generate SILO Settings
            </h3>
            <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
        </div>
        <div class="xagio-modal-body">
            <input type="radio" id="imp_connections" name="radio" class="imp_connections" checked>
            <label for="imp_connections">Import Pages with connections <i data-xagio-tooltip data-xagio-title="This will Generate a SILO based only on pages that have parents." class="xagio-icon xagio-icon-info"></i></label>
            <hr>

            <input type="radio" id="imp_all" name="radio" class="imp_all">
            <label for="imp_all">Import all Pages <i class="xagio-icon xagio-icon-info" data-xagio-tooltip data-xagio-title="This will Generate a SILO based on all pages your have in WordPress."></i></label>

            <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-large">
                <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
                <button type="button" class="xagio-button xagio-button-primary silo-continue-generate">Generate SILO</button>
            </div>
        </div>
    </dialog>

    <dialog id="silo-add-page-post" class="xagio-modal">
        <div class="xagio-modal-header">
            <h3 class="xagio-modal-title">New Page/Post</h3>
            <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
        </div>
        <div class="xagio-modal-body">

            <form class="new-post-page">
                <div>
                    <div class="modal-label">Title</div>
                    <input type="text" class="xagio-input-text-mini post-page-title" placeholder="eg. My First Post">
                </div>


                <div class="xagio-margin-top-medium">
                    <div class="modal-label">Permalink</div>
                    <div class="modal-inline-input">
                        <?php
                            if (!isset($_SERVER['HTTP_HOST'])) {
                                $_SERVER['HTTP_HOST'] = get_permalink();
                            }
                        ?>
                        <a href="<?php echo 'https://' . esc_attr(sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']))) . '/'; ?>"><?php echo 'https://' . esc_attr(sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']))) . '/'; ?> </a><input type="text" class="xagio-input-text-mini post-page-url" placeholder="">
                    </div>
                </div>

                <div class="xagio-flex-even-columns xagio-flex-gap-medium xagio-margin-top-medium">
                    <div>
                        <div class="modal-label">Status</div>
                        <select class="xagio-input-select xagio-input-select-gray post-page-type">
                            <option value="page">Page</option>
                            <option value="post">Post</option>
                        </select>
                    </div>
                    <div>
                        <div class="modal-label">Type</div>
                        <select class="xagio-input-select xagio-input-select-gray post-page-status">
                            <option value="publish">Publish</option>
                            <option value="draft">Draft</option>
                            <option value="private">Private</option>
                            <option value="schedule">Schedule</option>
                        </select>
                    </div>
                </div>

                <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-large">
                    <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Cancel</button>
                    <button type="button" class="xagio-button xagio-button-primary silo-post-page-create"><i class="xagio-icon xagio-icon-save"></i> Create</button>
                </div>
            </form>
        </div>
    </dialog>

</div>