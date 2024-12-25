<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>

<!DOCTYPE html>
<html>

<head>
    <title>Xagio Seo Plan</title>
    <?php wp_head(); ?>
</head>
<body>


<div class="wrap prs">
    <div class="main-area">
        <div class="report_content">
            <div class="logo-heading">
                <h1 class="project-name"><i class="xagio-icon xagio-icon-file"></i></h1>
            </div>
            <div class="logo-section">
                <img class="logo-xagio-image" src="https://cdn.xagio.net/company_logo/">
                <div>
                    <p class="logo-first-name"></p>
                    <p class="logo-email"></p>
                    <p class="logo-address"></p>
                    <p class="logo-phone-number"></p>
                </div>
            </div>
        </div>
        <!-- Project Dashboard -->
        <div class="project-dashboard">
            <div class="project-groups">
                <div class="data">

                    <div data-name="Group Name" class="xagio-group skeleton" data-post-type="">
                        <div class="group-action-buttons">
                            <div class="group-name">
                                <input type="text" class="groupInput" placeholder="eg. My Group" value="" name="group_name"/>
                                <h3></h3>
                            </div>
                        </div>

                        <form class="updateGroup">
                            <div class="group-seo">
                                <input type="hidden" name="group_id" value="0"/>
                                <input type="hidden" name="project_id" value="0"/>
                                <input type="hidden" name="request_type" value="single"/>
                                <input type="hidden" name="oriUrl" value=""/>
                                <div class="group-h1">

                                    <div class="h-1-holder">H1</div>

                                    <input type="hidden" class="groupInput" placeholder="eg. My Header" value="" name="h1"/>
                                    <div class="prs-h1tag" spellcheck="false" data-target="h1tag"  placeholder="eg. My Header"></div>
                                </div>

                                <div class="group-google">
                                    <!-- SEO URL -->
                                    <div class="url-container">
                                        <input type="hidden" name="url" value=""/>
                                        <div class="prs-editor url-container-inner" spellcheck="false" contenteditable="false">
                                            <label class="host-url"><?php echo esc_url(site_url()); ?> </label>
                                            <label class="pre-url"></label>
                                            <div spellcheck="false" class="url-edit" ></div>
                                            <label class="post-url"></label>
                                        </div>
                                    </div>

                                    <!-- SEO Title -->
                                    <input type="hidden" name="title" class="groupInput" value=""/>
                                    <div class="prs-editor prs-title" spellcheck="false" data-target="title"  placeholder="SEO Title"></div>

                                    <!-- SEO Description -->
                                    <input type="hidden" name="description" class="groupInput" value=""/>
                                    <div class="prs-editor prs-description" spellcheck="false" data-target="description"  placeholder="SEO Description"></div>

                                </div>

                                <div class="group-metrics">
                                    <p>Meta Title Length</p>
                                    <div class="title-length-holder">
                                        <div class="title-length-desk">
                                            <i class="xagio-icon xagio-icon-desktop" data-xagio-tooltip data-xagio-title="Desktop devices limits."></i>
                                            <span class="count-seo-bold"><span class="count-seo-title">0</span> / 70</span>
                                        </div>
                                        <div class="title-length-mob">
                                            <i class="xagio-icon xagio-icon-mobile" data-xagio-tooltip data-xagio-title="Mobile devices limits."></i>
                                            <span class="count-seo-bold"><span class="count-seo-title-mobile">0</span> / 78</span>
                                        </div>
                                    </div>
                                    <p>Meta Description Length</p>
                                    <div class="description-length-holder">
                                        <div class="description-length-desk">
                                            <i class="xagio-icon xagio-icon-desktop" data-xagio-tooltip data-xagio-title="Desktop devices limits."></i>
                                            <span class="count-seo-bold"><span class="count-seo-description">0</span> / 300</span>
                                        </div>
                                        <div class="description-length-mob">
                                            <i class="xagio-icon xagio-icon-mobile" data-xagio-tooltip data-xagio-title="Mobile devices limits."></i>
                                            <span class="count-seo-bold"><span class="count-seo-description-mobile">0</span> / 120</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="xagio-keyword-cloud"></div>

                        <div class="group-keywords">
                            <!-- Keywords -->
                            <form class="updateKeywords">
                                <table class="uk-table uk-table-striped uk-table-hover keywords">
                                    <thead>
                                    <tr>
                                        <th>Keyword</th>
                                        <th>Volume</th>
                                        <th>CPC&nbsp;($)</th>
                                        <th>inTitle</th>
                                        <th>inURL</th>
                                        <th data-xagio-tooltip data-xagio-title="Title Ratio (Volume / InTitle)" class="text-center">TR</th>
                                        <th data-xagio-tooltip data-xagio-title="URL Ratio (Volume / InURL)" class="text-center">UR</th>
                                        <th class="xagio-text-center">Rank</th>
                                    </tr>
                                    </thead>
                                    <tbody class="keywords-data uk-sortable">
                                    <tr>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                    </tr>
                                    <tr>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </form>
                        </div>
                    </div>
                    <div data-name="Group Name" class="xagio-group skeleton" data-post-type="">
                        <div class="group-action-buttons">
                            <div class="group-name">
                                <input type="text" class="groupInput" placeholder="eg. My Group" value="" name="group_name"/>
                                <h3></h3>
                            </div>
                        </div>

                        <form class="updateGroup">
                            <div class="group-seo">
                                <input type="hidden" name="group_id" value="0"/>
                                <input type="hidden" name="project_id" value="0"/>
                                <input type="hidden" name="request_type" value="single"/>
                                <input type="hidden" name="oriUrl" value=""/>
                                <div class="group-h1">

                                    <div class="h-1-holder">H1</div>

                                    <input type="hidden" class="groupInput" placeholder="eg. My Header" value="" name="h1"/>
                                    <div class="prs-h1tag" spellcheck="false" data-target="h1tag" placeholder="eg. My Header"></div>
                                </div>

                                <div class="group-google">
                                    <!-- SEO URL -->
                                    <div class="url-container">
                                        <input type="hidden" name="url" value=""/>
                                        <div class="prs-editor url-container-inner" spellcheck="false" contenteditable="false">
                                            <label class="host-url"><?php echo esc_url(site_url()); ?> </label>
                                            <label class="pre-url"></label>
                                            <div spellcheck="false" class="url-edit" ></div>
                                            <label class="post-url"></label>
                                        </div>
                                    </div>

                                    <!-- SEO Title -->
                                    <input type="hidden" name="title" class="groupInput" value=""/>
                                    <div class="prs-editor prs-title" spellcheck="false" data-target="title" placeholder="SEO Title"></div>

                                    <!-- SEO Description -->
                                    <input type="hidden" name="description" class="groupInput" value=""/>
                                    <div class="prs-editor prs-description" spellcheck="false" data-target="description" placeholder="SEO Description"></div>

                                </div>

                                <div class="group-metrics">
                                    <p>Meta Title Length</p>
                                    <div class="title-length-holder">
                                        <div class="title-length-desk">
                                            <i class="xagio-icon xagio-icon-desktop" data-xagio-tooltip data-xagio-title="Desktop devices limits."></i>
                                            <span class="count-seo-bold"><span class="count-seo-title">0</span> / 70</span>
                                        </div>
                                        <div class="title-length-mob">
                                            <i class="xagio-icon xagio-icon-mobile" data-xagio-tooltip data-xagio-title="Mobile devices limits."></i>
                                            <span class="count-seo-bold"><span class="count-seo-title-mobile">0</span> / 78</span>
                                        </div>
                                    </div>
                                    <p>Meta Description Length</p>
                                    <div class="description-length-holder">
                                        <div class="description-length-desk">
                                            <i class="xagio-icon xagio-icon-desktop" data-xagio-tooltip data-xagio-title="Desktop devices limits."></i>
                                            <span class="count-seo-bold"><span class="count-seo-description">0</span> / 300</span>
                                        </div>
                                        <div class="description-length-mob">
                                            <i class="xagio-icon xagio-icon-mobile" data-xagio-tooltip data-xagio-title="Mobile devices limits."></i>
                                            <span class="count-seo-bold"><span class="count-seo-description-mobile">0</span> / 120</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="xagio-keyword-cloud"></div>

                        <div class="group-keywords">
                            <!-- Keywords -->
                            <form class="updateKeywords">
                                <table class="uk-table uk-table-striped uk-table-hover keywords">
                                    <thead>
                                    <tr>
                                        <th>Keyword</th>
                                        <th>Volume</th>
                                        <th>CPC&nbsp;($)</th>
                                        <th>inTitle</th>
                                        <th>inURL</th>
                                        <th data-xagio-tooltip data-xagio-title="Title Ratio (Volume / InTitle)" class="text-center">TR</th>
                                        <th data-xagio-tooltip data-xagio-title="URL Ratio (Volume / InURL)" class="text-center">UR</th>
                                        <th class="xagio-text-center">Rank</th>
                                    </tr>
                                    </thead>
                                    <tbody class="keywords-data uk-sortable">
                                    <tr>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                    </tr>
                                    <tr>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                        <td><div class="keywordInput"></div></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </form>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<div data-name="Group Name" class="xagio-group template" data-post-type="">
    <div class="group-action-buttons">
        <div class="group-name">
            <input type="text" class="groupInput" placeholder="eg. My Group" value="" name="group_name"/>
            <h3></h3>
        </div>
    </div>

    <form class="updateGroup">
        <div class="group-seo">
            <input type="hidden" name="action" value="xagio_updateGroup"/>
            <input type="hidden" name="group_id" value="0"/>
            <input type="hidden" name="project_id" value="0"/>
            <input type="hidden" name="request_type" value="single"/>
            <input type="hidden" name="oriUrl" value=""/>
            <div class="group-h1">

                <div class="h-1-holder">H1</div>

                <input type="hidden" class="groupInput" placeholder="eg. My Header" value="" name="h1"/>
                <div class="prs-h1tag" spellcheck="false" data-target="h1tag"  placeholder="eg. My Header"></div>
            </div>

            <div class="group-google">
                <!-- SEO URL -->
                <div class="url-container">
                    <input type="hidden" name="url" value=""/>
                    <div class="prs-editor url-container-inner" spellcheck="false" contenteditable="false">
                        <label class="host-url"><?php echo esc_url(site_url()); ?> </label>
                        <label class="pre-url"></label>
                        <div spellcheck="false" class="url-edit" ></div>
                        <label class="post-url"></label>
                    </div>
                </div>

                <!-- SEO Title -->
                <input type="hidden" name="title" class="groupInput" value=""/>
                <div class="prs-editor prs-title" spellcheck="false" data-target="title"  placeholder="SEO Title"></div>

                <!-- SEO Description -->
                <input type="hidden" name="description" class="groupInput" value=""/>
                <div class="prs-editor prs-description" spellcheck="false" data-target="description"  placeholder="SEO Description"></div>

            </div>

            <div class="group-metrics">
                <p>Meta Title Length</p>
                <div class="title-length-holder">
                    <div class="title-length-desk">
                        <i class="xagio-icon xagio-icon-desktop" data-xagio-tooltip data-xagio-title="Desktop devices limits."></i>
                        <span class="count-seo-bold"><span class="count-seo-title">0</span> / 70</span>
                    </div>
                    <div class="title-length-mob">
                        <i class="xagio-icon xagio-icon-mobile" data-xagio-tooltip data-xagio-title="Mobile devices limits."></i>
                        <span class="count-seo-bold"><span class="count-seo-title-mobile">0</span> / 78</span>
                    </div>
                </div>
                <p>Meta Description Length</p>
                <div class="description-length-holder">
                    <div class="description-length-desk">
                        <i class="xagio-icon xagio-icon-desktop" data-xagio-tooltip data-xagio-title="Desktop devices limits."></i>
                        <span class="count-seo-bold"><span class="count-seo-description">0</span> / 300</span>
                    </div>
                    <div class="description-length-mob">
                        <i class="xagio-icon xagio-icon-mobile" data-xagio-tooltip data-xagio-title="Mobile devices limits."></i>
                        <span class="count-seo-bold"><span class="count-seo-description-mobile">0</span> / 120</span>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="xagio-keyword-cloud"></div>

    <div class="group-keywords">
        <!-- Keywords -->
        <form class="updateKeywords">
            <table class="uk-table uk-table-striped uk-table-hover keywords">
                <thead>
                <tr>
                    <th>Keyword</th>
                    <th>Volume</th>
                    <th>CPC&nbsp;($)</th>
                    <th>inTitle</th>
                    <th>inURL</th>
                    <th data-xagio-tooltip data-xagio-title="Title Ratio (Volume / InTitle)" class="text-center">TR</th>
                    <th data-xagio-tooltip data-xagio-title="URL Ratio (Volume / InURL)" class="text-center">UR</th>
                    <th class="xagio-text-center">Rank</th>
                </tr>
                </thead>
                <tbody class="keywords-data uk-sortable">
                <tr>
                    <td colspan="11">
                        <div class="empty-keywords">
                            <i class="xagio-icon xagio-icon-warning"></i> No added keywords yet...
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>

<?php wp_footer(); ?>

</body>
</html>

