<?php
/**
 * Type: SUBMENU
 * Page_Title: Niche Hunter
 * Menu_Title: Niche Hunter
 * Capability: manage_options
 * Slug: xagio-nichehunter
 * Parent_Slug: xagio-dashboard
 * Icon: /assets/img/logo-menu-xagio.webp
 * JavaScript: jquery-ui-core,xagio_trends,xagio_select2,xagio_chart,xagio_datatables,xagio_nichehunter
 * Css: xagio_select2,xagio_datatables,xagio_nichehunter
 * Position: 5
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$MEMBERSHIP_INFO = get_option('XAGIO_ACCOUNT_DETAILS');
?>
<div class="xagio-main-header">
    <img class="logo-image repo-xagio" src="<?php echo esc_url(XAGIO_URL); ?>assets/img/logo-xagio.webp"/>
    <h2 class="logo-title logo-title-big">
        Niche Hunter
    </h2>
    <div class="xagio-header-actions-in-project">
        <div class="xagio-credits">
            <ul>
                <li id="xags-allowance" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="These are your current XAGS (xRenew)">
                    <img class="xags" src="<?php echo esc_url(plugins_url('xagio-seo/assets/img/xrenew_white.png')); ?>"/> <span class="value"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></span>
                </li>
                <li id="xags" data-xagio-tooltip data-xagio-tooltip-position="bottom" data-xagio-title="These are your current XAGS (xBank)">
                    <img class="xags" src="<?php echo esc_url(plugins_url('xagio-seo/assets/img/xbank_white.png')); ?>"/> <span class="value"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i></span>
                </li>
            </ul>

        </div>
        <a href="https://xagio.com/xbank-store/" target="_blank" class="xagio-button xagio-button-secondary"><i class="xagio-icon xagio-icon-store"></i> Buy XAGS</a>
        <?php if(isset($MEMBERSHIP_INFO["membership"]) && $MEMBERSHIP_INFO["membership"] === "Xagio AI Free") { ?>
            <a href="https://xagio.com/?goto=wppremfeatures" target="_blank" class="xagio-button xagio-button-secondary xagio-button-premium-button">
                See Xagio Premium Features
            </a>
        <?php } ?>
    </div>
</div>

<!-- HTML STARTS HERE -->
<div class="xagio-content-wrapper">

    <div class="xagio-accordion xagio-margin-bottom-large">
        <h3 class="xagio-accordion-title">
            <i class="xagio-icon xagio-icon-info"></i>
            <span>Easily get keywords with preset filters, then you can pick keywords you like and add them to your Project Planner</span>
            <i class="xagio-icon xagio-icon-arrow-down"></i>
        </h3>
        <div class="xagio-accordion-content">
            <div>
                <div class="xagio-accordion-panel"></div>
            </div>
        </div>
    </div>

    <ul class="xagio-tab">
        <li class="xagio-tab-active"><a href="">Niche Hunter</a></li>
        <li><a href="">Settings</a></li>
    </ul>

    <div class="xagio-tab-content-holder">

        <!-- Niche Hunter -->
        <div class="xagio-tab-content">
            <div class="xagio-panel xagio-margin-bottom-medium">
                <h3 class="xagio-panel-title">Advanced Filters</h3>

                <form class="filters">

                    <input type="hidden" name="action" value="xagio_niche_hunter_results"/>

                    <div class="input-filters">
                        <div>
                            <h3 class="pop">Enter Keyword</h3>
                            <input type="text" class="xagio-input-text-mini" name="filters[keyword]" placeholder="eg. your keyword" value="" required>
                        </div>
                        <div>
                            <h3 class="pop">Match To</h3>
                            <select id="" name="keyword_like" class="xagio-input-select xagio-input-select-gray">
                                <option value="contains" selected>Contains</option>
                                <option value="first">Is First</option>
                                <option value="last">Is Last</option>
                            </select>
                        </div>
                        <div>
                            <h3 class="pop">Select Language</h3>
                            <select class="xagio-input-select xagio-input-select-gray" name="filters[location]">
                                <option value='Algeria' data-language='French'>Algeria(French)</option>
                                <option value='Algeria' data-language='Arabic'>Algeria(Arabic)</option>
                                <option value='Angola' data-language='Portuguese'>Angola(Portuguese)</option>
                                <option value='Argentina' data-language='Spanish'>Argentina(Spanish)</option>
                                <option value='Australia' data-language='English'>Australia(English)</option>
                                <option value='Austria' data-language='German'>Austria(German)</option>
                                <option value='Bahrain' data-language='Arabic'>Bahrain(Arabic)</option>
                                <option value='Bangladesh' data-language='Bengali'>Bangladesh(Bengali)</option>
                                <option value='Armenia' data-language='Armenian'>Armenia(Armenian)</option>
                                <option value='Belgium' data-language='French'>Belgium(French)</option>
                                <option value='Belgium' data-language='Dutch'>Belgium(Dutch)</option>
                                <option value='Belgium' data-language='German'>Belgium(German)</option>
                                <option value='Bolivia' data-language='Spanish'>Bolivia(Spanish)</option>
                                <option value='Brazil' data-language='Portuguese'>Brazil(Portuguese)</option>
                                <option value='Bulgaria' data-language='Bulgarian'>Bulgaria(Bulgarian)</option>
                                <option value='Myanmar (Burma)' data-language='English'>Myanmar (Burma)(English)</option>
                                <option value='Cambodia' data-language='English'>Cambodia(English)</option>
                                <option value='Canada' data-language='English'>Canada(English)</option>
                                <option value='Canada' data-language='French'>Canada(French)</option>
                                <option value='Sri Lanka' data-language='English'>Sri Lanka(English)</option>
                                <option value='Chile' data-language='Spanish'>Chile(Spanish)</option>
                                <option value='Taiwan' data-language='Chinese (Traditional)'>Taiwan(Chinese (Traditional))
                                </option>
                                <option value='Colombia' data-language='Spanish'>Colombia(Spanish)</option>
                                <option value='Costa Rica' data-language='Spanish'>Costa Rica(Spanish)</option>
                                <option value='Croatia' data-language='Croatian'>Croatia(Croatian)</option>
                                <option value='Cyprus' data-language='Greek'>Cyprus(Greek)</option>
                                <option value='Cyprus' data-language='English'>Cyprus(English)</option>
                                <option value='Czechia' data-language='Czech'>Czechia(Czech)</option>
                                <option value='Denmark' data-language='Danish'>Denmark(Danish)</option>
                                <option value='Ecuador' data-language='Spanish'>Ecuador(Spanish)</option>
                                <option value='El Salvador' data-language='Spanish'>El Salvador(Spanish)</option>
                                <option value='Estonia' data-language='Estonian'>Estonia(Estonian)</option>
                                <option value='Finland' data-language='Finnish'>Finland(Finnish)</option>
                                <option value='France' data-language='French'>France(French)</option>
                                <option value='Germany' data-language='German'>Germany(German)</option>
                                <option value='Greece' data-language='Greek'>Greece(Greek)</option>
                                <option value='Guatemala' data-language='Spanish'>Guatemala(Spanish)</option>
                                <option value='Hong Kong' data-language='English'>Hong Kong(English)</option>
                                <option value='Hong Kong' data-language='Chinese (Traditional)'>Hong Kong(Chinese
                                    (Traditional))
                                </option>
                                <option value='Hungary' data-language='Hungarian'>Hungary(Hungarian)</option>
                                <option value='India' data-language='English'>India(English)</option>
                                <option value='Indonesia' data-language='English'>Indonesia(English)</option>
                                <option value='Indonesia' data-language='Indonesian'>Indonesia(Indonesian)</option>
                                <option value='Ireland' data-language='English'>Ireland(English)</option>
                                <option value='Israel' data-language='Hebrew'>Israel(Hebrew)</option>
                                <option value='Israel' data-language='Arabic'>Israel(Arabic)</option>
                                <option value='Italy' data-language='Italian'>Italy(Italian)</option>
                                <option value='Japan' data-language='Japanese'>Japan(Japanese)</option>
                                <option value='Jordan' data-language='Arabic'>Jordan(Arabic)</option>
                                <option value='Kenya' data-language='English'>Kenya(English)</option>
                                <option value='South Korea' data-language='Korean'>South Korea(Korean)</option>
                                <option value='Latvia' data-language='Latvian'>Latvia(Latvian)</option>
                                <option value='Lithuania' data-language='Lithuanian'>Lithuania(Lithuanian)</option>
                                <option value='Malaysia' data-language='English'>Malaysia(English)</option>
                                <option value='Malaysia' data-language='Malay'>Malaysia(Malay)</option>
                                <option value='Malta' data-language='English'>Malta(English)</option>
                                <option value='Mexico' data-language='Spanish'>Mexico(Spanish)</option>
                                <option value='Morocco' data-language='Arabic'>Morocco(Arabic)</option>
                                <option value='Netherlands' data-language='Dutch'>Netherlands(Dutch)</option>
                                <option value='New Zealand' data-language='English'>New Zealand(English)</option>
                                <option value='Nicaragua' data-language='Spanish'>Nicaragua(Spanish)</option>
                                <option value='Nigeria' data-language='English'>Nigeria(English)</option>
                                <option value='Norway' data-language='Norwegian (Bokmål)'>Norway(Norwegian (Bokmål))</option>
                                <option value='Pakistan' data-language='English'>Pakistan(English)</option>
                                <option value='Pakistan' data-language='Urdu'>Pakistan(Urdu)</option>
                                <option value='Paraguay' data-language='Spanish'>Paraguay(Spanish)</option>
                                <option value='Peru' data-language='Spanish'>Peru(Spanish)</option>
                                <option value='Philippines' data-language='English'>Philippines(English)</option>
                                <option value='Philippines' data-language='Tagalog'>Philippines(Tagalog)</option>
                                <option value='Poland' data-language='Polish'>Poland(Polish)</option>
                                <option value='Portugal' data-language='Portuguese'>Portugal(Portuguese)</option>
                                <option value='Romania' data-language='Romanian'>Romania(Romanian)</option>
                                <option value='Russia' data-language='Russian'>Russia(Russian)</option>
                                <option value='Saudi Arabia' data-language='Arabic'>Saudi Arabia(Arabic)</option>
                                <option value='Serbia' data-language='Serbian'>Serbia(Serbian)</option>
                                <option value='Singapore' data-language='English'>Singapore(English)</option>
                                <option value='Singapore' data-language='Chinese (Simplified)'>Singapore(Chinese (Simplified))
                                </option>
                                <option value='Slovakia' data-language='Slovak'>Slovakia(Slovak)</option>
                                <option value='Vietnam' data-language='English'>Vietnam(English)</option>
                                <option value='Vietnam' data-language='Vietnamese'>Vietnam(Vietnamese)</option>
                                <option value='Slovenia' data-language='Slovenian'>Slovenia(Slovenian)</option>
                                <option value='South Africa' data-language='English'>South Africa(English)</option>
                                <option value='Spain' data-language='Spanish'>Spain(Spanish)</option>
                                <option value='Sweden' data-language='Swedish'>Sweden(Swedish)</option>
                                <option value='Switzerland' data-language='German'>Switzerland(German)</option>
                                <option value='Switzerland' data-language='French'>Switzerland(French)</option>
                                <option value='Thailand' data-language='Thai'>Thailand(Thai)</option>
                                <option value='United Arab Emirates' data-language='Arabic'>United Arab Emirates(Arabic)
                                </option>
                                <option value='United Arab Emirates' data-language='English'>United Arab Emirates(English)
                                </option>
                                <option value='Tunisia' data-language='Arabic'>Tunisia(Arabic)</option>
                                <option value='Turkey' data-language='Turkish'>Turkey(Turkish)</option>
                                <option value='Ukraine' data-language='Ukrainian'>Ukraine(Ukrainian)</option>
                                <option value='Ukraine' data-language='Russian'>Ukraine(Russian)</option>
                                <option value='Egypt' data-language='Arabic'>Egypt(Arabic)</option>
                                <option value='Egypt' data-language='English'>Egypt(English)</option>
                                <option value='United Kingdom' data-language='English'>United Kingdom(English)</option>
                                <option value='United States' data-language='English' selected>United States(English)</option>
                                <option value='Uruguay' data-language='Spanish'>Uruguay(Spanish)</option>
                                <option value='Venezuela' data-language='Spanish'>Venezuela(Spanish)</option>
                            </select>
                        </div>
                        <div>
                            <h3 class="pop">Exclude Keywords</h3>
                            <input type="text" class="xagio-input-text-mini" name="filters[keyword_exclude]"
                                    placeholder="eg. your keyword" value="">
                        </div>


                    </div>

                    <div class="sliders-filters xagio-margin-top-medium">
                        <div>
                            <h3 class="pop">Enter Volume</h3>
                            <div class="hunter-range-container">

                                <div class="hunter-slider-container">
                                    <div class="price-slider"></div>
                                </div>

                                <!-- Slider -->
                                <div class="range-input">
                                    <input type="range" class="min-range" name="gms-min" min="0" max="10000" value="0"
                                            step="100">
                                    <input type="range" class="max-range" name="gms-max" min="0" max="10000" value="10000"
                                            step="100">
                                </div>

                                <div class="xagio-slider-input">
                                    <input type="number" class="gms-min min-input hunter-min-number" value="0">
                                    <input type="number" class="gms-max max-input hunter-max-number" value="10000">
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="pop">Cost Per Click</h3>

                            <div class="hunter-range-container">

                                <div class="hunter-slider-container">
                                    <div class="price-slider"></div>
                                </div>

                                <!-- Slider -->
                                <div class="range-input">
                                    <input type="range" class="min-range" name="cpc-min" min="0" max="100" value="0" step="1">
                                    <input type="range" class="max-range" name="cpc-max" min="0" max="100" value="100" step="1">
                                </div>

                                <div class="xagio-slider-input">
                                    <input type="number" class="cpc-min min-input hunter-min-number" value="0">
                                    <input type="number" class="cpc-max max-input hunter-max-number" value="100">
                                </div>
                            </div>


                        </div>

                        <div>
                            <h3 class="pop">Competition</h3>
                            <div class="hunter-range-container">

                                <div class="hunter-slider-container">
                                    <div class="price-slider"></div>
                                </div>

                                <!-- Slider -->
                                <div class="range-input">
                                    <input type="range" class="min-range" name="cpm-min" min="0" max="1" value="0" step="0.1">
                                    <input type="range" class="max-range" name="cpm-max" min="0" max="1" value="1" step="0.1">
                                </div>

                                <div class="xagio-slider-input">
                                    <input type="number" class="cpm-min min-input hunter-min-number" value="0" step="0.1">
                                    <input type="number" class="cpm-max max-input hunter-max-number" value="1" step="0.1">
                                </div>
                            </div>
                        </div>

                        <div>
                            <button type="submit" class="xagio-button xagio-button-primary ">
                                <i class="xagio-icon xagio-icon-search"></i> Generate
                            </button>
                        </div>
                    </div>

                </form>

            </div>

            <div class="xagio-panel xagio-margin-bottom-medium niche-hunter-table-holder">
                <h3 class="xagio-panel-title xagio-flex xagio-flex-gap-large">
                    <div>
                        <span class="niche-selected-keywords"></span> Keywords
                    </div>
                    <button class="xagio-button xagio-button-primary copy-keywords-to-project" style="display: none"><i class="xagio-icon xagio-icon-copy"></i> <span>Copy To Project (<span class="niche-selected-keywords"></span>)</span></button>
                </h3>

                <div class="xagio-table-responsive niche-hunter-table">
                    <table class="xagio-table results-table">
                        <thead>
                        <tr>
                            <th class="xagio-text-center"><input type="checkbox" data-id="" class="xagio-input-checkbox select-all-niche-keywords"></th>
                            <th>Keyword</th>
                            <th>Volume</th>
                            <th>CPC</th>
                            <th>Competition</th>
                            <th style="width: 100px">Research It</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="xagio-accordion xagio-accordion-opened xagio-margin-bottom-medium">
                <h3 class="xagio-accordion-title xagio-accordion-panel-title"><span>History</span>
                    <i class="xagio-icon xagio-icon-arrow-down"></i></h3>
                <div class="xagio-accordion-content">
                    <div>
                        <div class="xagio-accordion-panel">
                            <div class="hunter-history-holder">
                                Loading ...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Settings -->
        <div class="xagio-tab-content">

            <div class="xagio-2-column-grid">
                <div class="xagio-column-1">
                    <div class="xagio-panel xagio-margin-bottom-medium">
                        <h3 class="xagio-panel-title">Google Search Windows To Open</h3>

                        <form class="niche-settings">
                            <!-- Enable/Disable Sitemaps -->
                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_GOOGLE_SEARCH_WINDOW_BROAD" id="XAGIO_GOOGLE_SEARCH_WINDOW_BROAD" value="<?php echo  (XAGIO_GOOGLE_SEARCH_WINDOW_BROAD == TRUE) ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button xagio-slider-button-settings <?php echo  (XAGIO_GOOGLE_SEARCH_WINDOW_BROAD == TRUE) ? 'on' : ''; ?>" data-element="XAGIO_GOOGLE_SEARCH_WINDOW_BROAD"></span>
                                </div>
                                <p class="xagio-slider-label">Broad</p>
                            </div>

                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_GOOGLE_SEARCH_WINDOW_PHRASE" id="XAGIO_GOOGLE_SEARCH_WINDOW_PHRASE" value="<?php echo  (XAGIO_GOOGLE_SEARCH_WINDOW_PHRASE == TRUE) ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button xagio-slider-button-settings <?php echo  (XAGIO_GOOGLE_SEARCH_WINDOW_PHRASE == TRUE) ? 'on' : ''; ?>" data-element="XAGIO_GOOGLE_SEARCH_WINDOW_PHRASE"></span>
                                </div>
                                <p class="xagio-slider-label">Phrase</p>
                            </div>

                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_GOOGLE_SEARCH_WINDOW_INTITLE" id="XAGIO_GOOGLE_SEARCH_WINDOW_INTITLE" value="<?php echo  (XAGIO_GOOGLE_SEARCH_WINDOW_INTITLE == TRUE) ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button xagio-slider-button-settings <?php echo  (XAGIO_GOOGLE_SEARCH_WINDOW_INTITLE == TRUE) ? 'on' : ''; ?>" data-element="XAGIO_GOOGLE_SEARCH_WINDOW_INTITLE"></span>
                                </div>
                                <p class="xagio-slider-label">inTitle</p>
                            </div>

                            <div class="xagio-slider-container">
                                <input type="hidden" name="XAGIO_GOOGLE_SEARCH_WINDOW_INURL" id="XAGIO_GOOGLE_SEARCH_WINDOW_INURL" value="<?php echo  (XAGIO_GOOGLE_SEARCH_WINDOW_INURL == TRUE) ? 1 : 0; ?>"/>
                                <div class="xagio-slider-frame">
                                    <span class="xagio-slider-button xagio-slider-button-settings <?php echo  (XAGIO_GOOGLE_SEARCH_WINDOW_INURL == TRUE) ? 'on' : ''; ?>" data-element="XAGIO_GOOGLE_SEARCH_WINDOW_INURL"></span>
                                </div>
                                <p class="xagio-slider-label">inURL</p>
                            </div>

                        </form>

                    </div>
                </div>
                <div class="xagio-column-2">
                    <div class="xagio-panel xagio-margin-bottom-medium">
                        <h3 class="xagio-panel-title">Domain Availability TLDs</h3>
                        <form class="niche-tlds-settings">
                            <input type="hidden" name="action" value="xagio_niche_hunter_save_tld">

                            <select id="mytld" name="mytld[]" multiple="true" class="xagio-input-select xagio-input-select-gray" data-tags="<?php echo XAGIO_NICHE_HUNTER_TLDS ? esc_html(implode(',', XAGIO_NICHE_HUNTER_TLDS)) : '' ?>" style="width: 100%;">
                                <option value=".ac">.ac</option>
                                <option value=".ad">.ad</option>
                                <option value=".ae">.ae</option>
                                <option value=".af">.af</option>
                                <option value=".ag">.ag</option>
                                <option value=".ai">.ai</option>
                                <option value=".al">.al</option>
                                <option value=".am">.am</option>
                                <option value=".an">.an</option>
                                <option value=".ao">.ao</option>
                                <option value=".aq">.aq</option>
                                <option value=".ar">.ar</option>
                                <option value=".as">.as</option>
                                <option value=".asia">.asia</option>
                                <option value=".at">.at</option>
                                <option value=".au">.au</option>
                                <option value=".aw">.aw</option>
                                <option value=".ax">.ax</option>
                                <option value=".az">.az</option>
                                <option value=".ba">.ba</option>
                                <option value=".bb">.bb</option>
                                <option value=".be">.be</option>
                                <option value=".bf">.bf</option>
                                <option value=".bg">.bg</option>
                                <option value=".bh">.bh</option>
                                <option value=".bi">.bi</option>
                                <option value=".biz">.biz</option>
                                <option value=".bj">.bj</option>
                                <option value=".bm">.bm</option>
                                <option value=".bo">.bo</option>
                                <option value=".br">.br</option>
                                <option value=".bs">.bs</option>
                                <option value=".bt">.bt</option>
                                <option value=".bv">.bv</option>
                                <option value=".bw">.bw</option>
                                <option value=".by">.by</option>
                                <option value=".bz">.bz</option>
                                <option value=".ca">.ca</option>
                                <option value=".cat">.cat</option>
                                <option value=".cc">.cc</option>
                                <option value=".cd">.cd</option>
                                <option value=".cf">.cf</option>
                                <option value=".cg">.cg</option>
                                <option value=".ch">.ch</option>
                                <option value=".ci">.ci</option>
                                <option value=".cl">.cl</option>
                                <option value=".cm">.cm</option>
                                <option value=".cn">.cn</option>
                                <option value=".co">.co</option>
                                <option value=".com" selected>.com</option>
                                <option value=".cr">.cr</option>
                                <option value=".cu">.cu</option>
                                <option value=".cv">.cv</option>
                                <option value=".cw">.cw</option>
                                <option value=".cx">.cx</option>
                                <option value=".cz">.cz</option>
                                <option value=".de">.de</option>
                                <option value=".dj">.dj</option>
                                <option value=".dk">.dk</option>
                                <option value=".dm">.dm</option>
                                <option value=".do">.do</option>
                                <option value=".dz">.dz</option>
                                <option value=".ec">.ec</option>
                                <option value=".edu">.edu</option>
                                <option value=".ee">.ee</option>
                                <option value=".eg">.eg</option>
                                <option value=".es">.es</option>
                                <option value=".et">.et</option>
                                <option value=".eu">.eu</option>
                                <option value=".fi">.fi</option>
                                <option value=".fm">.fm</option>
                                <option value=".fo">.fo</option>
                                <option value=".fr">.fr</option>
                                <option value=".ga">.ga</option>
                                <option value=".gb">.gb</option>
                                <option value=".gd">.gd</option>
                                <option value=".ge">.ge</option>
                                <option value=".gf">.gf</option>
                                <option value=".gg">.gg</option>
                                <option value=".gh">.gh</option>
                                <option value=".gi">.gi</option>
                                <option value=".gl">.gl</option>
                                <option value=".gm">.gm</option>
                                <option value=".gn">.gn</option>
                                <option value=".gov">.gov</option>
                                <option value=".gp">.gp</option>
                                <option value=".gq">.gq</option>
                                <option value=".gr">.gr</option>
                                <option value=".gs">.gs</option>
                                <option value=".gt">.gt</option>
                                <option value=".gw">.gw</option>
                                <option value=".gy">.gy</option>
                                <option value=".hk">.hk</option>
                                <option value=".hm">.hm</option>
                                <option value=".hn">.hn</option>
                                <option value=".hr">.hr</option>
                                <option value=".ht">.ht</option>
                                <option value=".hu">.hu</option>
                                <option value=".id">.id</option>
                                <option value=".ie">.ie</option>
                                <option value=".im">.im</option>
                                <option value=".in">.in</option>
                                <option value=".info">.info</option>
                                <option value=".int">.int</option>
                                <option value=".io">.io</option>
                                <option value=".iq">.iq</option>
                                <option value=".ir">.ir</option>
                                <option value=".is">.is</option>
                                <option value=".it">.it</option>
                                <option value=".je">.je</option>
                                <option value=".jo">.jo</option>
                                <option value=".jp">.jp</option>
                                <option value=".kg">.kg</option>
                                <option value=".ki">.ki</option>
                                <option value=".km">.km</option>
                                <option value=".kn">.kn</option>
                                <option value=".kp">.kp</option>
                                <option value=".kr">.kr</option>
                                <option value=".ky">.ky</option>
                                <option value=".kz">.kz</option>
                                <option value=".la">.la</option>
                                <option value=".lb">.lb</option>
                                <option value=".lc">.lc</option>
                                <option value=".li">.li</option>
                                <option value=".lk">.lk</option>
                                <option value=".lr">.lr</option>
                                <option value=".ls">.ls</option>
                                <option value=".lt">.lt</option>
                                <option value=".lu">.lu</option>
                                <option value=".lv">.lv</option>
                                <option value=".ly">.ly</option>
                                <option value=".ma">.ma</option>
                                <option value=".mc">.mc</option>
                                <option value=".md">.md</option>
                                <option value=".me">.me</option>
                                <option value=".mg">.mg</option>
                                <option value=".mh">.mh</option>
                                <option value=".mk">.mk</option>
                                <option value=".ml">.ml</option>
                                <option value=".mn">.mn</option>
                                <option value=".mo">.mo</option>
                                <option value=".mp">.mp</option>
                                <option value=".mq">.mq</option>
                                <option value=".mr">.mr</option>
                                <option value=".ms">.ms</option>
                                <option value=".mt">.mt</option>
                                <option value=".mu">.mu</option>
                                <option value=".mv">.mv</option>
                                <option value=".mw">.mw</option>
                                <option value=".mx">.mx</option>
                                <option value=".my">.my</option>
                                <option value=".na">.na</option>
                                <option value=".nc">.nc</option>
                                <option value=".ne">.ne</option>
                                <option value=".net" selected>.net</option>
                                <option value=".nf">.nf</option>
                                <option value=".ng">.ng</option>
                                <option value=".nl">.nl</option>
                                <option value=".no">.no</option>
                                <option value=".nr">.nr</option>
                                <option value=".nu">.nu</option>
                                <option value=".nz">.nz</option>
                                <option value=".om">.om</option>
                                <option value=".org" selected>.org</option>
                                <option value=".pa">.pa</option>
                                <option value=".pe">.pe</option>
                                <option value=".pf">.pf</option>
                                <option value=".ph">.ph</option>
                                <option value=".pk">.pk</option>
                                <option value=".pl">.pl</option>
                                <option value=".pm">.pm</option>
                                <option value=".pn">.pn</option>
                                <option value=".pr">.pr</option>
                                <option value=".pro">.pro</option>
                                <option value=".ps">.ps</option>
                                <option value=".pt">.pt</option>
                                <option value=".pw">.pw</option>
                                <option value=".py">.py</option>
                                <option value=".qa">.qa</option>
                                <option value=".re">.re</option>
                                <option value=".ro">.ro</option>
                                <option value=".rs">.rs</option>
                                <option value=".ru">.ru</option>
                                <option value=".rw">.rw</option>
                                <option value=".sa">.sa</option>
                                <option value=".sb">.sb</option>
                                <option value=".sc">.sc</option>
                                <option value=".sd">.sd</option>
                                <option value=".se">.se</option>
                                <option value=".sg">.sg</option>
                                <option value=".sh">.sh</option>
                                <option value=".si">.si</option>
                                <option value=".sj">.sj</option>
                                <option value=".sk">.sk</option>
                                <option value=".sl">.sl</option>
                                <option value=".sm">.sm</option>
                                <option value=".sn">.sn</option>
                                <option value=".so">.so</option>
                                <option value=".sr">.sr</option>
                                <option value=".st">.st</option>
                                <option value=".su">.su</option>
                                <option value=".sv">.sv</option>
                                <option value=".sx">.sx</option>
                                <option value=".sy">.sy</option>
                                <option value=".sz">.sz</option>
                                <option value=".tc">.tc</option>
                                <option value=".td">.td</option>
                                <option value=".tf">.tf</option>
                                <option value=".tg">.tg</option>
                                <option value=".th">.th</option>
                                <option value=".tj">.tj</option>
                                <option value=".tk">.tk</option>
                                <option value=".tl">.tl</option>
                                <option value=".tm">.tm</option>
                                <option value=".tn">.tn</option>
                                <option value=".to">.to</option>
                                <option value=".tp">.tp</option>
                                <option value=".tr">.tr</option>
                                <option value=".tt">.tt</option>
                                <option value=".tv">.tv</option>
                                <option value=".tw">.tw</option>
                                <option value=".tz">.tz</option>
                                <option value=".ua">.ua</option>
                                <option value=".ug">.ug</option>
                                <option value=".uk">.uk</option>
                                <option value=".us">.us</option>
                                <option value=".uy">.uy</option>
                                <option value=".uz">.uz</option>
                                <option value=".va">.va</option>
                                <option value=".vc">.vc</option>
                                <option value=".ve">.ve</option>
                                <option value=".vg">.vg</option>
                                <option value=".vi">.vi</option>
                                <option value=".vn">.vn</option>
                                <option value=".vu">.vu</option>
                                <option value=".wf">.wf</option>
                                <option value=".ws">.ws</option>
                                <option value=".yt">.yt</option>
                                <option value=".co.ae">.co.ae</option>
                                <option value=".co.ag">.co.ag</option>
                                <option value=".co.ao">.co.ao</option>
                                <option value=".co.at">.co.at</option>
                                <option value=".co.ba">.co.ba</option>
                                <option value=".co.bb">.co.bb</option>
                                <option value=".co.bi">.co.bi</option>
                                <option value=".co.bw">.co.bw</option>
                                <option value=".co.ci">.co.ci</option>
                                <option value=".co.cl">.co.cl</option>
                                <option value=".co.cm">.co.cm</option>
                                <option value=".co.cr">.co.cr</option>
                                <option value=".co.gg">.co.gg</option>
                                <option value=".co.gl">.co.gl</option>
                                <option value=".co.gy">.co.gy</option>
                                <option value=".co.hu">.co.hu</option>
                                <option value=".co.id">.co.id</option>
                                <option value=".co.im">.co.im</option>
                                <option value=".co.in">.co.in</option>
                                <option value=".co.ir">.co.ir</option>
                                <option value=".co.it">.co.it</option>
                                <option value=".co.je">.co.je</option>
                                <option value=".co.jp">.co.jp</option>
                                <option value=".co.kr">.co.kr</option>
                                <option value=".co.lc">.co.lc</option>
                                <option value=".co.ls">.co.ls</option>
                                <option value=".co.ma">.co.ma</option>
                                <option value=".co.me">.co.me</option>
                                <option value=".co.mu">.co.mu</option>
                                <option value=".co.mw">.co.mw</option>
                                <option value=".co.na">.co.na</option>
                                <option value=".co.nz">.co.nz</option>
                                <option value=".co.om">.co.om</option>
                                <option value=".co.pn">.co.pn</option>
                                <option value=".co.pw">.co.pw</option>
                                <option value=".co.rs">.co.rs</option>
                                <option value=".co.rw">.co.rw</option>
                                <option value=".co.st">.co.st</option>
                                <option value=".co.sz">.co.sz</option>
                                <option value=".co.th">.co.th</option>
                                <option value=".co.tj">.co.tj</option>
                                <option value=".co.tm">.co.tm</option>
                                <option value=".co.tt">.co.tt</option>
                                <option value=".co.tz">.co.tz</option>
                                <option value=".co.ua">.co.ua</option>
                                <option value=".co.ug">.co.ug</option>
                                <option value=".co.uk">.co.uk</option>
                                <option value=".co.us">.co.us</option>
                                <option value=".co.uz">.co.uz</option>
                                <option value=".co.ve">.co.ve</option>
                                <option value=".co.vi">.co.vi</option>
                                <option value=".co.za">.co.za</option>
                                <option value=".com.ac">.com.ac</option>
                                <option value=".com.af">.com.af</option>
                                <option value=".com.ag">.com.ag</option>
                                <option value=".com.ai">.com.ai</option>
                                <option value=".com.al">.com.al</option>
                                <option value=".com.an">.com.an</option>
                                <option value=".com.ar">.com.ar</option>
                                <option value=".com.au">.com.au</option>
                                <option value=".com.aw">.com.aw</option>
                                <option value=".com.az">.com.az</option>
                                <option value=".com.ba">.com.ba</option>
                                <option value=".com.bb">.com.bb</option>
                                <option value=".com.bh">.com.bh</option>
                                <option value=".com.bi">.com.bi</option>
                                <option value=".com.bm">.com.bm</option>
                                <option value=".com.bo">.com.bo</option>
                                <option value=".com.br">.com.br</option>
                                <option value=".com.bs">.com.bs</option>
                                <option value=".com.bt">.com.bt</option>
                                <option value=".com.by">.com.by</option>
                                <option value=".com.bz">.com.bz</option>
                                <option value=".com.ci">.com.ci</option>
                                <option value=".com.cm">.com.cm</option>
                                <option value=".com.cn">.com.cn</option>
                                <option value=".com.co">.com.co</option>
                                <option value=".com.cu">.com.cu</option>
                                <option value=".com.cw">.com.cw</option>
                                <option value=".com.cy">.com.cy</option>
                                <option value=".com.dm">.com.dm</option>
                                <option value=".com.do">.com.do</option>
                                <option value=".com.dz">.com.dz</option>
                                <option value=".com.ec">.com.ec</option>
                                <option value=".com.ee">.com.ee</option>
                                <option value=".com.eg">.com.eg</option>
                                <option value=".com.es">.com.es</option>
                                <option value=".com.et">.com.et</option>
                                <option value=".com.fr">.com.fr</option>
                                <option value=".com.ge">.com.ge</option>
                                <option value=".com.gh">.com.gh</option>
                                <option value=".com.gi">.com.gi</option>
                                <option value=".com.gl">.com.gl</option>
                                <option value=".com.gn">.com.gn</option>
                                <option value=".com.gp">.com.gp</option>
                                <option value=".com.gr">.com.gr</option>
                                <option value=".com.gt">.com.gt</option>
                                <option value=".com.gy">.com.gy</option>
                                <option value=".com.hk">.com.hk</option>
                                <option value=".com.hn">.com.hn</option>
                                <option value=".com.hr">.com.hr</option>
                                <option value=".com.ht">.com.ht</option>
                                <option value=".com.im">.com.im</option>
                                <option value=".com.io">.com.io</option>
                                <option value=".com.iq">.com.iq</option>
                                <option value=".com.is">.com.is</option>
                                <option value=".com.jo">.com.jo</option>
                                <option value=".com.kg">.com.kg</option>
                                <option value=".com.ki">.com.ki</option>
                                <option value=".com.km">.com.km</option>
                                <option value=".com.kp">.com.kp</option>
                                <option value=".com.ky">.com.ky</option>
                                <option value=".com.kz">.com.kz</option>
                                <option value=".com.la">.com.la</option>
                                <option value=".com.lb">.com.lb</option>
                                <option value=".com.lc">.com.lc</option>
                                <option value=".com.lk">.com.lk</option>
                                <option value=".com.lr">.com.lr</option>
                                <option value=".com.lv">.com.lv</option>
                                <option value=".com.ly">.com.ly</option>
                                <option value=".com.mg">.com.mg</option>
                                <option value=".com.mk">.com.mk</option>
                                <option value=".com.ml">.com.ml</option>
                                <option value=".com.mo">.com.mo</option>
                                <option value=".com.ms">.com.ms</option>
                                <option value=".com.mt">.com.mt</option>
                                <option value=".com.mu">.com.mu</option>
                                <option value=".com.mv">.com.mv</option>
                                <option value=".com.mw">.com.mw</option>
                                <option value=".com.mx">.com.mx</option>
                                <option value=".com.my">.com.my</option>
                                <option value=".com.na">.com.na</option>
                                <option value=".com.nf">.com.nf</option>
                                <option value=".com.ng">.com.ng</option>
                                <option value=".com.nr">.com.nr</option>
                                <option value=".com.om">.com.om</option>
                                <option value=".com.pa">.com.pa</option>
                                <option value=".com.pe">.com.pe</option>
                                <option value=".com.pf">.com.pf</option>
                                <option value=".com.ph">.com.ph</option>
                                <option value=".com.pk">.com.pk</option>
                                <option value=".com.pl">.com.pl</option>
                                <option value=".com.pr">.com.pr</option>
                                <option value=".com.ps">.com.ps</option>
                                <option value=".com.pt">.com.pt</option>
                                <option value=".com.py">.com.py</option>
                                <option value=".com.qa">.com.qa</option>
                                <option value=".com.re">.com.re</option>
                                <option value=".com.ro">.com.ro</option>
                                <option value=".com.ru">.com.ru</option>
                                <option value=".com.rw">.com.rw</option>
                                <option value=".com.sa">.com.sa</option>
                                <option value=".com.sb">.com.sb</option>
                                <option value=".com.sc">.com.sc</option>
                                <option value=".com.sd">.com.sd</option>
                                <option value=".com.sg">.com.sg</option>
                                <option value=".com.sh">.com.sh</option>
                                <option value=".com.sl">.com.sl</option>
                                <option value=".com.sn">.com.sn</option>
                                <option value=".com.so">.com.so</option>
                                <option value=".com.st">.com.st</option>
                                <option value=".com.sv">.com.sv</option>
                                <option value=".com.sy">.com.sy</option>
                                <option value=".com.tj">.com.tj</option>
                                <option value=".com.tm">.com.tm</option>
                                <option value=".com.tn">.com.tn</option>
                                <option value=".com.to">.com.to</option>
                                <option value=".com.tr">.com.tr</option>
                                <option value=".com.tt">.com.tt</option>
                                <option value=".com.tw">.com.tw</option>
                                <option value=".com.ua">.com.ua</option>
                                <option value=".com.ug">.com.ug</option>
                                <option value=".com.uy">.com.uy</option>
                                <option value=".com.uz">.com.uz</option>
                                <option value=".com.vc">.com.vc</option>
                                <option value=".com.ve">.com.ve</option>
                                <option value=".com.vi">.com.vi</option>
                                <option value=".com.vn">.com.vn</option>
                                <option value=".com.vu">.com.vu</option>
                                <option value=".com.ws">.com.ws</option>
                                <option value=".xyz">.xyz</option>
                            </select>

                            <div class="xagio-flex-right xagio-margin-top-large">
                                <button type="submit" class="xagio-button xagio-button-primary"><i class="xagio-icon xagio-icon-check"></i> Save Changes</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>


        </div>
    </div>

    <dialog id="checkDomainModal" class="xagio-modal">
        <div class="xagio-modal-header">
            <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-travel-explore"></i> Domain Availability Checker</h3>
            <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
        </div>

        <div class="xagio-modal-body">
            <div class="table-responsive">
                <table class="xagio-table text-center">
                    <thead>
                    <tr>
                        <th class="text-center">Domain Name</th>
<!--                        <th class="text-center">Available</th>-->
                        <th class="text-center">Action</th>
                    </tr>
                    </thead>
                    <tbody class="checkDomainTable">

                    </tbody>
                </table>
            </div>
        </div>
    </dialog>

    <!-- View Google Trends-->
    <dialog id="googleTrendsModal" class="xagio-modal">
        <div class="xagio-modal-header">
            <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-history"></i> Competition and Trends</h3>
            <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
        </div>

        <div class="xagio-modal-body">
            <div id="googleTrendsContainer"></div>
        </div>

    </dialog>

    <!-- View History-->
    <dialog id="historyModal" class="xagio-modal">

        <div class="xagio-modal-header">
            <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-history"></i> Search Volume History</h3>
            <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
        </div>

        <div class="xagio-modal-body" id="sv_history_body">
            <canvas id="history_graph" width="300" height="150"></canvas>
        </div>

    </dialog>

    <!-- Copy keywords to Project Planner -->
    <dialog id="copyToProjectPlannerModal" class="xagio-modal">

        <div class="xagio-modal-header">
            <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-copy"></i> Copy Keywords To Project Planner</h3>
            <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
        </div>

        <div class="xagio-modal-body">
            <div class="xagio-alert xagio-alert-primary xagio-margin-bottom-medium">
                <i class="xagio-icon xagio-icon-info"></i> You can leave both options empty, in that case we will make new project and new group with selected keywords.
                You can also select only project and leave group empty, we will insert selected keywords to selected project but we will make a new group. Also you can select any group, or give name to a new group (This also works with project)
            </div>

            <div class="modal-label" for="moveToProjectInput">Select Project / Create Project</div>
            <select id="moveToProjectInput" name="project_id" class="xagio-input-select xagio-input-select-gray" required>

            </select>

            <div class="modal-label xagio-margin-top-medium" for="moveToGroupInput">Select Group / Create Group</div>
            <select id="moveToGroupInput" name="group" class="xagio-input-select xagio-input-select-gray">
                <option value=""></option>
            </select>

            <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium">
                <button type="button" class="xagio-button xagio-button-outline" data-xagio-close-modal><i class="xagio-icon xagio-icon-close"></i> Close</button>
                <button type="button" class="xagio-button xagio-button-primary copy-niche-keywords"><i class="xagio-icon xagio-icon-copy"></i> Copy To Project</button>
            </div>
        </div>

    </dialog>

</div> <!-- .wrap -->

