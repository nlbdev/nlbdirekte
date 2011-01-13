<?xml version="1.0" encoding="UTF-8"?>
<p:declare-step xmlns:p="http://www.w3.org/ns/xproc"
    xmlns:c="http://www.w3.org/ns/xproc-step" xmlns:nlb="http://www.nlb.no/2010/XSL" type="nlb:xml-to-json" version="1.0">
    <p:input port="source"/>
    <p:output port="result"/>
    
    <p:xslt>
        <p:input port="parameters">
            <p:empty/>
        </p:input>
        <p:input port="stylesheet">
            <p:inline>
                <xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="2.0">
                    <xsl:import href="JsonML.xslt"/>
                    
                    <!-- wrap root-node -->
                    <xsl:template match="/">
                        <c:data>
                            <xsl:apply-templates select="*"/>
                        </c:data>
                    </xsl:template>
                    
                    <!-- elements -->
                    <xsl:template match="*">
                        <xsl:value-of select="$START_ELEM"/>
                        
                        <!-- tag-name string -->
                        <xsl:value-of select="$STRING_DELIM"/>
                        <xsl:choose>
                            <xsl:when test="namespace-uri()=$XHTML">
                                <xsl:value-of select="local-name()"/>
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:value-of select="name()"/>
                            </xsl:otherwise>
                        </xsl:choose>
                        <xsl:value-of select="$STRING_DELIM"/>
                        
                        <!-- attribute object -->
                        <xsl:if test="count(@*)>0">
                            <xsl:value-of select="$VALUE_DELIM"/>
                            <xsl:value-of select="$START_ATTRIB"/>
                            <xsl:for-each select="@*">
                                <xsl:if test="position()>1">
                                    <xsl:value-of select="$VALUE_DELIM"/>
                                </xsl:if>
                                <xsl:apply-templates select="."/>
                            </xsl:for-each>
                            <xsl:value-of select="$END_ATTRIB"/>
                        </xsl:if>
                        
                        <!-- child elements and text-nodes -->
                        <xsl:for-each select="*|text()">
                            <xsl:if
                                test="not(self::text()) or string-length(normalize-space(.))>0">
                                <xsl:value-of select="$VALUE_DELIM"/>
                                <xsl:apply-templates select="."/>
                            </xsl:if>
                        </xsl:for-each>
                        
                        <xsl:value-of select="$END_ELEM"/>
                    </xsl:template>
                    
                    <!-- skip empty text nodes -->
                    <xsl:template match="text()">
                        <xsl:call-template name="escape-string">
                            <xsl:with-param name="value" select="normalize-space(.)"/>
                        </xsl:call-template>
                    </xsl:template>
                </xsl:stylesheet>
            </p:inline>
        </p:input>
    </p:xslt>
    
</p:declare-step>