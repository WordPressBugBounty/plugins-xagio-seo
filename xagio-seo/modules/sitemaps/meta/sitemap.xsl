<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns:html="https://www.w3.org/TR/html40/"
                xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
    <xsl:template match="/">
        <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <title>Xagio Sitemaps</title>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            </head>
            <body>
                <xsl:apply-templates></xsl:apply-templates>
                <div id="footer">
                    Generated with <a rel="external" href="https://xagio.com" title="Xagio SEO Plugin for WordPress">Xagio SEO Plugin for WordPress</a>.
                </div>
            </body>
        </html>
    </xsl:template>
    <xsl:template match="sitemap:urlset">
        <h1>Xagio Sitemaps</h1>
        <div id="intro">
            <p>
                <!-- write something about sitemaps -->
                <b>What are sitemaps?</b>
                <br/>
                Sitemaps are a way for webmasters to inform search engines about pages on their sites that are available for crawling. In its simplest form, a Sitemap is an XML file that lists URLs for a site along with additional metadata about each URL (when it was last updated, how often it usually changes, and how important it is, relative to other URLs in the site) so that search engines can more intelligently crawl the site.
                <br/>
                <br/>
                <b>What is this page?</b>
                <br/>
                This page shows all the URLs that are included in the sitemap. You can use this page to check if the sitemap is correct and to see if all your pages are included.
                <br/>
                <br/>
                <b>How to use this page?</b>
                <br/>
                You can use this page to check if the sitemap is correct and to see if all your pages are included. If you have a lot of URLs in your sitemap you can use the search box to filter the URLs.
            </p>
        </div>
        <div id="content">
            <table cellpadding="5">
                <tr style="border-bottom:1px black solid;">
                    <th>URL</th>
                    <th>Priority</th>
                    <th>Change frequency</th>
                    <th>Last modified (GMT)</th>
                </tr>
                <xsl:variable name="lower" select="'abcdefghijklmnopqrstuvwxyz'"/>
                <xsl:variable name="upper" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'"/>
                <xsl:for-each select="./sitemap:url">
                    <tr>
                        <xsl:if test="position() mod 2 != 1">
                            <xsl:attribute name="class">high</xsl:attribute>
                        </xsl:if>
                        <td>
                            <xsl:variable name="itemURL">
                                <xsl:value-of select="sitemap:loc"/>
                            </xsl:variable>
                            <a href="{$itemURL}">
                                <xsl:value-of select="sitemap:loc"/>
                            </a>
                        </td>
                        <td>
                            <xsl:value-of select="concat(sitemap:priority*100,'%')"/>
                        </td>
                        <td>
                            <xsl:value-of select="concat(translate(substring(sitemap:changefreq, 1, 1),concat($lower, $upper),concat($upper, $lower)),substring(sitemap:changefreq, 2))"/>
                        </td>
                        <td>
                            <xsl:value-of select="concat(substring(sitemap:lastmod,0,11),concat(' ', substring(sitemap:lastmod,12,5)))"/>
                        </td>
                    </tr>
                </xsl:for-each>
            </table>
        </div>
    </xsl:template>
    <xsl:template match="sitemap:sitemapindex">
        <h1>Xagio Sitemap Index</h1>
        <div id="intro">
            <p>
                <!-- write something about sitemaps -->
                <b>What are sitemaps?</b>
                <br/>
                Sitemaps are a way for webmasters to inform search engines about pages on their sites that are available for crawling. In its simplest form, a Sitemap is an XML file that lists URLs for a site along with additional metadata about each URL (when it was last updated, how often it usually changes, and how important it is, relative to other URLs in the site) so that search engines can more intelligently crawl the site.
                <br/>
                <br/>
                <b>What is this page?</b>
                <br/>
                This page shows all the sitemaps that are included in the sitemap index. You can use this page to check if the sitemap index is correct and to see if all your sitemaps are included.
                <br/>
                <br/>
                <b>How to use this page?</b>
                <br/>
                You can use this page to check if the sitemap index is correct and to see if all your sitemaps are included. If you have a lot of sitemaps in your sitemap index you can use the search box to filter the sitemaps.
            </p>
        </div>
        <div id="content">
            <table cellpadding="5">
                <tr style="border-bottom:1px black solid;">
                    <th>URL of sub-sitemap</th>
                    <th>Last modified (GMT)</th>
                </tr>
                <xsl:for-each select="./sitemap:sitemap">
                    <tr>
                        <xsl:if test="position() mod 2 != 1">
                            <xsl:attribute name="class">high</xsl:attribute>
                        </xsl:if>
                        <td>
                            <xsl:variable name="itemURL">
                                <xsl:value-of select="sitemap:loc"/>
                            </xsl:variable>
                            <a href="{$itemURL}">
                                <xsl:value-of select="sitemap:loc"/>
                            </a>
                        </td>
                        <td>
                            <xsl:value-of select="concat(substring(sitemap:lastmod,0,11),concat(' ', substring(sitemap:lastmod,12,5)))"/>
                        </td>
                    </tr>
                </xsl:for-each>
            </table>
        </div>
    </xsl:template>
</xsl:stylesheet>