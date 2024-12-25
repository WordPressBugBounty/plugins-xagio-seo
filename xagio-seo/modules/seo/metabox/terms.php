<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$id   = $tag->term_id;
$tax  = $tag->taxonomy;
$meta = xagio_get_term_meta($id);

// Default Values
$taxonomies  = get_option('XAGIO_SEO_DEFAULT_TAXONOMIES');
$title       = '';
$description = '';

if (isset($taxonomies[$tax])) {

    $title = XAGIO_MODEL_SEO::replaceVars(@$taxonomies[$tax]['XAGIO_SEO_TITLE'], 0, [
            '%%term_title%%' => $tag->name,
    ]);

    $description = XAGIO_MODEL_SEO::replaceVars(@$taxonomies[$tax]['XAGIO_SEO_DESCRIPTION'], 0, [
            '%%term_title%%' => $tag->name,
    ]);

}

?>

</tbody>
</table>

<input type="hidden" name="meta[taxonomy]" value="<?php echo esc_attr($tax); ?>"/>
<?php wp_nonce_field('xagio_nonce', '_xagio_nonce'); ?>

<div class="xagio-panel xagio-margin-bottom-medium">
    <h3 class="xagio-panel-title"><img class="logo-image-seo" src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logo-xagio-smaller.webp"> Xagio Meta SEO Snippet</h3>

    <div class="xagio-tabs">
        <div class="xagio-seo-row">
            <div class="xagio-g-snippet">
                <div class="xagio-g-title">
                    <!-- SEO Title -->
                    <div class="xagio-title-length">
                        <div class="inside-check-circle"></div>
                    </div>
                    <div class="title-check-circle"></div>

                    <input type="hidden" name="meta[XAGIO_SEO_TITLE]" id="XAGIO_SEO_TITLE_INPUT" value="<?php echo esc_attr(@$meta['XAGIO_SEO_TITLE']); ?>"/>
                    <div class="xagio-editor" data-target="XAGIO_SEO_TITLE_INPUT" id="XAGIO_SEO_TITLE" contenteditable="true"
                         placeholder="<?php echo  esc_attr(XAGIO_MODEL_SEO::replaceVars(@$template['title'], absint($id))); ?>"><?php echo esc_html(@$meta['XAGIO_SEO_TITLE']); ?></div>
                </div>
                <div class="xagio-g-desc">
                    <!-- SEO Description -->
                    <div class="xagio-desc-length">
                        <div class="inside-check-circle"></div>
                    </div>
                    <div class="desc-check-circle"></div>

                    <input type="hidden" name="meta[XAGIO_SEO_DESCRIPTION]" id="XAGIO_SEO_DESCRIPTION_INPUT" value="<?php echo esc_attr(@$meta['XAGIO_SEO_DESCRIPTION']); ?>"/>
                    <div class="xagio-editor smaller-font" data-target="XAGIO_SEO_DESCRIPTION_INPUT" id="XAGIO_SEO_DESCRIPTION" contenteditable="true"
                         placeholder="<?php echo  esc_attr(XAGIO_MODEL_SEO::replaceVars(@$template['description'], absint($id))); ?>"><?php echo esc_html(@$meta['XAGIO_SEO_DESCRIPTION']); ?></div>
                </div>

            </div>
            <div class="xagio-g-robot xagio-margin-top-medium">

                <div class="xagio-slider-container">
                    <input type="hidden" name="meta[XAGIO_SEO_ROBOTS]" id="XAGIO_SEO_ROBOTS" value="<?php echo esc_html(@$meta['XAGIO_SEO_ROBOTS']); ?>"/>
                    <div class="xagio-slider-frame">
                        <span class="xagio-slider-button <?php echo ((@$meta['XAGIO_SEO_ROBOTS']) == 0 ? 'off' : 'on'); ?>" data-element="XAGIO_SEO_ROBOTS"></span>
                    </div>
                    <p class="xagio-slider-label">Don't Index & Follow</p>
                </div>

            </div>
        </div>

    </div>
</div>

