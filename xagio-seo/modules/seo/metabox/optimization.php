<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$meta       = XAGIO_MODEL_SEO::formatMetaVariables(get_post_meta($post->ID));
?>

<h3 class="xagio-side-info">Enter a Focus Keyword Manually or select from the Keyword Group</h3>

<div class="xagio-preview">
    <div class="xagio-preview-input">
        <div class="xagio-preview-text">
            <input type="text" name="XAGIO_SEO_TARGET_KEYWORD" id="XAGIO_SEO_TARGET_KEYWORD" value="<?php echo esc_attr(@$meta['XAGIO_SEO_TARGET_KEYWORD']); ?>"/>
        </div>
        <div class="xagio-icons">
            <i class="xagio-icon xagio-icon-close g-x-icon clear-target-keyword"></i>
        </div>
    </div>

</div>

<div class="xagio-accordion xagio-accordion-opened xagio-side-accordion">
    <h3 class="xagio-accordion-title xagio-accordion-panel-title">
        <span>Rankings <span class="uk-badge uk-badge-a"><span>...</span></span></span>
        <i class="xagio-icon xagio-icon-arrow-down"></i>
    </h3>
    <div class="xagio-accordion-content">
        <div>
            <div class="xagio-accordion-panel analysis-ranking">
                <span class="analysis-object tFK_SeoTitle"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tFK_SeoDesc"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tFK_SeoUrl"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tFK_Content"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tFK_SubHead"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tFK_ImageAlt"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tFK_BeginSeoTitle"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tFK_KwDensity"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
            </div>
        </div>
    </div>
</div>
<div class="settings xagio-accordion xagio-accordion-opened xagio-side-accordion">
    <h3 class="xagio-accordion-title xagio-accordion-panel-title">
        <span>Optimizations <span class="uk-badge uk-badge-a"><span>...</span></span></span>
        <i class="xagio-icon xagio-icon-arrow-down"></i>
    </h3>
    <div class="xagio-accordion-content">
        <div>
            <div class="xagio-accordion-panel analysis-optimization">
                <span class="analysis-object tOP_TitleLength"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tOP_DescLength"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tOP_UrlLength"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tOP_ContentLength"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tOP_NumberTitle"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tOP_AddMedia"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tOP_IntLinks"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
                <span class="analysis-object tOP_ExtLinks"><i class="xagio-icon xagio-icon-analytics"></i> <span></span></span>
                <span class="analysis-object tOP_ReadScore"><i class="xagio-icon xagio-icon-analytics"></i> <span>...</span></span>
            </div>
        </div>
    </div>
</div>