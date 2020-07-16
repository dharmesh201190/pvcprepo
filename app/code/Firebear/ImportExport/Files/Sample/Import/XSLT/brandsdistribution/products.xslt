<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    version="1.0">
    <xsl:output method="xml"
                version="1.0"
                encoding="UTF-8"
                indent="yes"/>
    <xsl:variable name="attributeSetCode">
        <xsl:text>Default</xsl:text>
    </xsl:variable>
    <xsl:variable name="multiValueSeparator">
        <xsl:text>,</xsl:text>
    </xsl:variable>
    <xsl:variable name="websites">
        <xsl:text>base</xsl:text>
    </xsl:variable>
    <xsl:variable name="product_type_simple">
        <xsl:text>simple</xsl:text>
    </xsl:variable>
    <xsl:variable name="product_type_configurable">
        <xsl:text>configurable</xsl:text>
    </xsl:variable>
    <xsl:variable name="baseImageURL">
        <xsl:text>https://www.brandsdistribution.com</xsl:text>
    </xsl:variable>
    <xsl:template match="page/items">
        <xsl:element name="Items">
            <xsl:for-each select="item">
                <xsl:variable name="config"
                              select="count(models/model)"/>
                <xsl:choose>
                    <xsl:when test="$config > 1">
                        <xsl:for-each select="models/model">
                            <xsl:element name="product">
                                <xsl:variable name="brand">
                                    <xsl:value-of select="ancestor-or-self::node()/brand"/>
                                </xsl:variable>
                                <xsl:variable name="category">
                                    <xsl:value-of select="ancestor-or-self::node()/tags/tag[name='category']/value/translations/translation/description"/>
                                </xsl:variable>
                                <xsl:variable name="subcategory">
                                    <xsl:value-of select="ancestor-or-self::node()/tags/tag[name='subcategory']/value/translations/translation/description"/>
                                </xsl:variable>
                                <xsl:variable name="gender">
                                    <xsl:value-of select="ancestor-or-self::node()/tags/tag[name='gender']/value/translations/translation/description"/>
                                </xsl:variable>
                                <xsl:variable name="name">
                                    <xsl:value-of select="ancestor-or-self::node()/name"/>
                                </xsl:variable>
                                <xsl:variable name="color">
                                    <xsl:value-of select="color"/>
                                </xsl:variable>
                                <xsl:variable name="size">
                                    <xsl:value-of select="size"/>
                                </xsl:variable>
                                <xsl:element name="sku">
                                    <xsl:value-of select="concat(ancestor-or-self::node()/id,'-',id)"/>
                                </xsl:element>
                                <xsl:element name="group">
                                    <xsl:value-of select="ancestor-or-self::node()/id"/>
                                </xsl:element>
                                <xsl:element name="url_key">
                                    <xsl:value-of select="translate(concat($brand,'-',$subcategory, '-', $gender, '-', $name, '-', $color, '-', $size), ' ', '-')"/>
                                </xsl:element>
                                <xsl:element name="name">
                                    <xsl:value-of select="concat($brand, ' ', $subcategory, ' ', $gender, ' ', $name, ' ', $color, ' ', $size)"/>
                                </xsl:element>
                                <xsl:element name="description">
                                    <xsl:value-of select="ancestor-or-self::node()/description"/>
                                </xsl:element>
                                <xsl:element name="qty">
                                    <xsl:value-of select="availability"/>
                                </xsl:element>
                                <xsl:element name="price">
                                    <xsl:value-of select="streetPrice"/>
                                </xsl:element>
                                <xsl:element name="msrp">
                                    <xsl:value-of select="suggestedPrice"/>
                                </xsl:element>
                                <xsl:element name="color">
                                    <xsl:value-of select="$color"/>
                                </xsl:element>
                                <xsl:element name="length">
                                    <xsl:value-of select="ancestor-or-self::node()/tags/tag[name='lenght']/value/translations/translation/description"/>
                                </xsl:element>
                                <xsl:element name="size">
                                    <xsl:value-of select="$size"/>
                                </xsl:element>
                                <xsl:element name="categories">
                                    <xsl:value-of select="concat($category, '/', $subcategory)"/>
                                </xsl:element>
                                <xsl:element name="product_online">
                                    <xsl:choose>
                                        <xsl:when test="ancestor-or-self::node()/online = 'true'">
                                            <xsl:text>1</xsl:text>
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <xsl:text>0</xsl:text>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:element>
                                <xsl:element name="product_websites">
                                    <xsl:value-of select="$websites"/>
                                </xsl:element>
                                <xsl:element name="attribute_set_code">
                                    <xsl:value-of select="$attributeSetCode"/>
                                </xsl:element>
                                <xsl:element name="product_type">
                                    <xsl:value-of select="$product_type_simple"/>
                                </xsl:element>
                                <xsl:element name="image">
                                    <xsl:value-of select="concat($baseImageURL,ancestor-or-self::node()/pictures/image/url)"/>
                                </xsl:element>
                                <xsl:element name="additional_images">
                                    <xsl:for-each select="ancestor-or-self::node()/pictures/image">
                                        <xsl:variable name="count"
                                                      select="position()"/>
                                        <xsl:if test="$count > 1">
                                            <xsl:value-of select="$multiValueSeparator"/>
                                        </xsl:if>
                                        <xsl:value-of select="concat($baseImageURL,url)"/>
                                    </xsl:for-each>
                                </xsl:element>
                            </xsl:element>
                        </xsl:for-each>
                        <!-- Create Config Product -->
                        <xsl:element name="product">
                            <xsl:variable name="brand">
                                <xsl:value-of select="ancestor-or-self::node()/brand"/>
                            </xsl:variable>
                            <xsl:variable name="category">
                                <xsl:value-of select="ancestor-or-self::node()/tags/tag[name='category']/value/translations/translation/description"/>
                            </xsl:variable>
                            <xsl:variable name="subcategory">
                                <xsl:value-of select="ancestor-or-self::node()/tags/tag[name='subcategory']/value/translations/translation/description"/>
                            </xsl:variable>
                            <xsl:variable name="gender">
                                <xsl:value-of select="ancestor-or-self::node()/tags/tag[name='gender']/value/translations/translation/description"/>
                            </xsl:variable>
                            <xsl:variable name="name">
                                <xsl:value-of select="ancestor-or-self::node()/name"/>
                            </xsl:variable>
                            <xsl:element name="sku">
                                <xsl:value-of select="id" />
                            </xsl:element>
                            <xsl:element name="attribute_set_code">
                                <xsl:value-of select="$attributeSetCode"/>
                            </xsl:element>
                            <xsl:element name="product_type">
                                <xsl:value-of select="$product_type_configurable" />
                            </xsl:element>
                            <xsl:element name="product_websites">
                                <xsl:value-of select="$websites" />
                            </xsl:element>
                            <xsl:element name="url_key">
                                <xsl:value-of select="translate(concat($brand,'-',$subcategory, '-', $gender, '-', $name), ' ', '-')"/>
                            </xsl:element>
                            <xsl:element name="name">
                                <xsl:value-of select="concat($brand, ' ', $subcategory, ' ', $gender, ' ', $name)"/>
                            </xsl:element>
                            <xsl:element name="categories">
                                <xsl:value-of select="concat($category, '/', $subcategory)"/>
                            </xsl:element>
                            <xsl:element name="product_online">
                                <xsl:choose>
                                    <xsl:when test="ancestor-or-self::node()/online = 'true'">
                                        <xsl:text>1</xsl:text>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:text>0</xsl:text>
                                    </xsl:otherwise>
                                </xsl:choose>
                            </xsl:element>
                        </xsl:element>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:element name="product">
                            <xsl:variable name="brand">
                                <xsl:value-of select="ancestor-or-self::node()/brand"/>
                            </xsl:variable>
                            <xsl:variable name="category">
                                <xsl:value-of select="ancestor-or-self::node()/tags/tag[name='category']/value/translations/translation/description"/>
                            </xsl:variable>
                            <xsl:variable name="subcategory">
                                <xsl:value-of select="ancestor-or-self::node()/tags/tag[name='subcategory']/value/translations/translation/description"/>
                            </xsl:variable>
                            <xsl:variable name="gender">
                                <xsl:value-of select="ancestor-or-self::node()/tags/tag[name='gender']/value/translations/translation/description"/>
                            </xsl:variable>
                            <xsl:variable name="name">
                                <xsl:value-of select="ancestor-or-self::node()/name"/>
                            </xsl:variable>
                            <xsl:element name="sku">
                                <xsl:value-of select="id" />
                            </xsl:element>
                            <xsl:element name="attribute_set_code">
                                <xsl:value-of select="$attributeSetCode"/>
                            </xsl:element>
                            <xsl:element name="product_type">
                                <xsl:value-of select="$product_type_simple" />
                            </xsl:element>
                            <xsl:element name="product_websites">
                                <xsl:value-of select="$websites" />
                            </xsl:element>
                            <xsl:element name="url_key">
                                <xsl:value-of select="translate(concat($brand,'-',$subcategory, '-', $gender, '-', $name), ' ', '-')"/>
                            </xsl:element>
                            <xsl:element name="name">
                                <xsl:value-of select="concat($brand, ' ', $subcategory, ' ', $gender, ' ', $name)"/>
                            </xsl:element>
                            <xsl:element name="categories">
                                <xsl:value-of select="concat($category, '/', $subcategory)"/>
                            </xsl:element>
                            <xsl:element name="product_online">
                                <xsl:choose>
                                    <xsl:when test="ancestor-or-self::node()/online = 'true'">
                                        <xsl:text>1</xsl:text>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:text>0</xsl:text>
                                    </xsl:otherwise>
                                </xsl:choose>
                            </xsl:element>
                        </xsl:element>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:for-each>
        </xsl:element>
    </xsl:template>
</xsl:stylesheet>