<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
        xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
        xmlns:xlink="http://www.w3.org/1999/xlink"
        version="1.0">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
    <!-- Default Config -->
    <xsl:variable name="languageId">
        <xsl:text>3</xsl:text>
    </xsl:variable>
    <xsl:variable name="attributeSetCode">
        <xsl:text>Default</xsl:text>
    </xsl:variable>
    <xsl:variable name="rootCategory">
        <xsl:text>Default Category</xsl:text>
    </xsl:variable>
    <xsl:variable name="multiValueSeparator">
        <xsl:text>|</xsl:text>
    </xsl:variable>
    <xsl:variable name="excludeImportCategories">
        <xsl:text>false</xsl:text>
    </xsl:variable>
    <!-- End Default Configs -->
    <xsl:template match="prestashop/products">
        <xsl:element name="Items">
            <xsl:for-each select="product">
                <xsl:variable name="config">
                    <xsl:for-each select="associations/combinations/combination">
                        <xsl:value-of select="position()"/>
                    </xsl:for-each>
                </xsl:variable>
                <xsl:choose>
                    <xsl:when test="$config > 1">
                        <xsl:for-each select="associations/combinations/combination">
                            <xsl:element name="product">
                                <xsl:element name="sku">
                                    <xsl:value-of select="normalize-space(../../../reference)"/>
                                </xsl:element>
                                <xsl:element name="excludeImportCategories">
                                    <xsl:value-of select="$excludeImportCategories"/>
                                </xsl:element>
                                <xsl:element name="_root_category">
                                    <xsl:value-of select="$rootCategory" />
                                </xsl:element>
                                <xsl:element name="config_product_id">
                                    <xsl:value-of select="normalize-space(id)" />
                                </xsl:element>
                                <xsl:element name="group">
                                    <xsl:value-of select="normalize-space(../../../id)" />
                                </xsl:element>
                                <xsl:element name="name">
                                    <xsl:for-each select="../../../name/language">
                                        <xsl:if test="@id = $languageId">
                                            <xsl:value-of select="normalize-space(string())"/>
                                        </xsl:if>
                                    </xsl:for-each>
                                </xsl:element>
                                <xsl:element name="price">
                                    <xsl:value-of select="normalize-space(../../../price)"/>
                                </xsl:element>
                                <xsl:element name="manufacturer">
                                    <xsl:value-of select="normalize-space(../../../manufacturer_name)"/>
                                </xsl:element>
                                <xsl:element name="qty">
                                    <xsl:value-of select="normalize-space(../../../quantity)"/>
                                </xsl:element>
                                <xsl:element name="width">
                                    <xsl:value-of select="normalize-space(../../../width)"/>
                                </xsl:element>
                                <xsl:element name="height">
                                    <xsl:value-of select="normalize-space(../../../height)"/>
                                </xsl:element>
                                <xsl:element name="depth">
                                    <xsl:value-of select="normalize-space(../../../depth)"/>
                                </xsl:element>
                                <xsl:element name="weight">
                                    <xsl:value-of select="normalize-space(../../../weight)"/>
                                </xsl:element>
                                <xsl:element name="created_at">
                                    <xsl:value-of select="normalize-space(../../../date_add)"/>
                                </xsl:element>
                                <xsl:element name="ean13">
                                    <xsl:value-of select="normalize-space(../../../ean13)"/>
                                </xsl:element>
                                <xsl:element name="description">
                                    <xsl:for-each select="../../../description/language">
                                        <xsl:if test="@id = $languageId">
                                            <xsl:value-of select="normalize-space(string())"/>
                                        </xsl:if>
                                    </xsl:for-each>
                                </xsl:element>
                                <xsl:element name="short_description">
                                    <xsl:for-each select="../../../description_short/language">
                                        <xsl:if test="@id = $languageId">
                                            <xsl:value-of select="normalize-space(string())"/>
                                        </xsl:if>
                                    </xsl:for-each>
                                </xsl:element>
                                <xsl:element name="meta_description">
                                    <xsl:for-each select="../../../meta_description/language">
                                        <xsl:if test="@id = $languageId">
                                            <xsl:value-of select="normalize-space(string())"/>
                                        </xsl:if>
                                    </xsl:for-each>
                                </xsl:element>
                                <xsl:element name="meta_keywords">
                                    <xsl:for-each select="../../../meta_keywords/language">
                                        <xsl:if test="@id = $languageId">
                                            <xsl:value-of select="normalize-space(string())"/>
                                        </xsl:if>
                                    </xsl:for-each>
                                </xsl:element>
                                <xsl:element name="meta_title">
                                    <xsl:for-each select="../../../meta_title/language">
                                        <xsl:if test="@id = $languageId">
                                            <xsl:value-of select="normalize-space(string())"/>
                                        </xsl:if>
                                    </xsl:for-each>
                                </xsl:element>
                                <xsl:choose>
                                    <xsl:when test="$config > 1">
                                        <xsl:element name="additional_images">
                                        </xsl:element>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:element name="additional_images">
                                            <xsl:for-each select="../../../associations/images/image">
                                                <xsl:variable name="count" select="position()"/>
                                                <xsl:if test="$count &gt; 1"><xsl:value-of select="$multiValueSeparator"/></xsl:if>
                                                <xsl:value-of select="normalize-space(@xlink:href)"/>
                                            </xsl:for-each>
                                        </xsl:element>
                                        <xsl:element name="additional_image_labels">
                                            <xsl:for-each select="associations/images/image">
                                                <xsl:variable name="count" select="position()"/>
                                                <xsl:if test="$count &gt; 1"><xsl:value-of select="$multiValueSeparator"/></xsl:if>
                                                <xsl:value-of select="normalize-space(id)"/>
                                            </xsl:for-each>
                                        </xsl:element>
                                    </xsl:otherwise>
                                </xsl:choose>
                                <xsl:element name="image">
                                    <xsl:value-of select="normalize-space(../../../id_default_image/@xlink:href)"/>
                                </xsl:element>
                                <xsl:element name="attribute_set_code">
                                    <xsl:value-of select="$attributeSetCode"/>
                                </xsl:element>

                                <xsl:element name="categories_link">
                                    <xsl:for-each select="../../../associations/categories/category">
                                        <xsl:variable name="count" select="position()"/>
                                        <xsl:if test="$count &gt; 1"><xsl:value-of select="$multiValueSeparator"/></xsl:if>
                                        <xsl:value-of select="normalize-space(id)"/>
                                    </xsl:for-each>
                                </xsl:element>
                            </xsl:element>
                        </xsl:for-each>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:element name="product">
                            <xsl:element name="sku">
                                <xsl:value-of select="normalize-space(reference)"/>
                            </xsl:element>
                            <xsl:element name="excludeImportCategories">
                                <xsl:value-of select="$excludeImportCategories"/>
                            </xsl:element>
                            <xsl:element name="_root_category">
                                <xsl:value-of select="$rootCategory" />
                            </xsl:element>
                            <xsl:element name="name">
                                <xsl:for-each select="name/language">
                                    <xsl:if test="@id = $languageId">
                                        <xsl:value-of select="normalize-space(string())"/>
                                    </xsl:if>
                                </xsl:for-each>
                            </xsl:element>
                            <xsl:element name="product_type">
                                <xsl:value-of select="normalize-space(type)"/>
                            </xsl:element>
                            <xsl:element name="price">
                                <xsl:value-of select="normalize-space(price)"/>
                            </xsl:element>
                            <xsl:element name="manufacturer">
                                <xsl:value-of select="normalize-space(manufacturer_name)"/>
                            </xsl:element>
                            <xsl:element name="qty">
                                <xsl:value-of select="normalize-space(quantity)"/>
                            </xsl:element>
                            <xsl:element name="width">
                                <xsl:value-of select="normalize-space(width)"/>
                            </xsl:element>
                            <xsl:element name="height">
                                <xsl:value-of select="normalize-space(height)"/>
                            </xsl:element>
                            <xsl:element name="depth">
                                <xsl:value-of select="normalize-space(depth)"/>
                            </xsl:element>
                            <xsl:element name="weight">
                                <xsl:value-of select="normalize-space(weight)"/>
                            </xsl:element>
                            <xsl:element name="created_at">
                                <xsl:value-of select="normalize-space(date_add)"/>
                            </xsl:element>
                            <xsl:element name="ean13">
                                <xsl:value-of select="normalize-space(ean13)"/>
                            </xsl:element>
                            <xsl:element name="description">
                                <xsl:for-each select="description/language">
                                    <xsl:if test="@id = $languageId">
                                        <xsl:value-of select="normalize-space(string())"/>
                                    </xsl:if>
                                </xsl:for-each>
                            </xsl:element>
                            <xsl:element name="short_description">
                                <xsl:for-each select="description_short/language">
                                    <xsl:if test="@id = $languageId">
                                        <xsl:value-of select="normalize-space(string())"/>
                                    </xsl:if>
                                </xsl:for-each>
                            </xsl:element>
                            <xsl:element name="meta_description">
                                <xsl:for-each select="meta_description/language">
                                    <xsl:if test="@id = $languageId">
                                        <xsl:value-of select="normalize-space(string())"/>
                                    </xsl:if>
                                </xsl:for-each>
                            </xsl:element>
                            <xsl:element name="meta_keywords">
                                <xsl:for-each select="meta_keywords/language">
                                    <xsl:if test="@id = $languageId">
                                        <xsl:value-of select="normalize-space(string())"/>
                                    </xsl:if>
                                </xsl:for-each>
                            </xsl:element>
                            <xsl:element name="meta_title">
                                <xsl:for-each select="meta_title/language">
                                    <xsl:if test="@id = $languageId">
                                        <xsl:value-of select="normalize-space(string())"/>
                                    </xsl:if>
                                </xsl:for-each>
                            </xsl:element>
                            <!--<xsl:element name="url_key">-->
                            <!--<xsl:value-of select="normalize-space(link_rewrite/language)"/>-->
                            <!--</xsl:element>-->
                            <xsl:choose>
                                <xsl:when test="$config > 1">
                                    <xsl:element name="additional_images">
                                    </xsl:element>
                                </xsl:when>
                                <xsl:otherwise>
                                    <xsl:element name="additional_images">
                                        <xsl:for-each select="associations/images/image">
                                            <xsl:variable name="count" select="position()"/>
                                            <xsl:if test="$count &gt; 1"><xsl:value-of select="$multiValueSeparator"/></xsl:if>
                                            <xsl:value-of select="normalize-space(@xlink:href)"/>
                                        </xsl:for-each>
                                    </xsl:element>
                                    <xsl:element name="additional_image_labels">
                                        <xsl:for-each select="associations/images/image">
                                            <xsl:variable name="count" select="position()"/>
                                            <xsl:if test="$count &gt; 1"><xsl:value-of select="$multiValueSeparator"/></xsl:if>
                                            <xsl:value-of select="normalize-space(id)"/>
                                        </xsl:for-each>
                                    </xsl:element>
                                </xsl:otherwise>
                            </xsl:choose>
                            <xsl:element name="image">
                                <xsl:value-of select="normalize-space(id_default_image/@xlink:href)"/>
                            </xsl:element>
                            <xsl:element name="attribute_set_code">
                                <xsl:value-of select="$attributeSetCode"/>
                            </xsl:element>

                            <xsl:element name="categories_link">
                                <xsl:for-each select="associations/categories/category">
                                    <xsl:variable name="count" select="position()"/>
                                    <xsl:if test="$count &gt; 1"><xsl:value-of select="$multiValueSeparator"/></xsl:if>
                                    <xsl:value-of select="normalize-space(id)"/>
                                </xsl:for-each>
                            </xsl:element>
                        </xsl:element>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:for-each>
        </xsl:element>
    </xsl:template>
</xsl:stylesheet>
