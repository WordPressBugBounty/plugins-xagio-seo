<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="xagio-slider-container">
    <input type="hidden" name="XAGIO_SEO_SEARCH_PREVIEW_ENABLE"
           class="XAGIO_SEO_SEARCH_PREVIEW_ENABLE edit-page-seo-enable" value="<?php echo  @$XAGIO_SEO_SEARCH_PREVIEW_ENABLE ? "1" : "0" ?>"/>
    <div class="xagio-slider-frame">
        <span class="xagio-slider-button <?php echo  @$XAGIO_SEO_SEARCH_PREVIEW_ENABLE ? "on" : "off" ?>" data-element="XAGIO_SEO_SEARCH_PREVIEW_ENABLE" data-page="edit"></span>
    </div>
    <input type="hidden" name="post_id" value="<?php echo intval($post_id); ?>"/>
</div>