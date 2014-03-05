<?xml version="1.0" encoding="UTF-8"?>
<p:declare-step xmlns:html="http://www.w3.org/1999/xhtml" xmlns:p="http://www.w3.org/ns/xproc" xmlns:c="http://www.w3.org/ns/xproc-step" version="1.0">
    <p:output port="result" sequence="true"/>

    <p:option name="href" select="'http://localhost/NLBdirekte/player/browselogs.php?robot'"/>

    <p:group name="logs">
        <p:output port="result"/>
        <p:identity>
            <p:input port="source">
                <p:inline>
                    <c:request method="GET" override-content-type="text/plain; charset=utf-8"/>
                </p:inline>
            </p:input>
        </p:identity>
        <p:add-attribute match="c:request" attribute-name="href">
            <p:with-option name="attribute-value" select="$href"/>
        </p:add-attribute>
        <p:http-request/>
        <p:unescape-markup content-type="text/html"/>
        <p:unwrap match="c:body"/>
        <p:xslt>
            <p:documentation>Removes all namespaces (like the xproc-step namespace) except the XHTML namespace.</p:documentation>
            <p:input port="parameters">
                <p:empty/>
            </p:input>
            <p:input port="stylesheet">
                <p:inline>
                    <xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:c="http://www.w3.org/ns/xproc-step" version="2.0">

                        <xsl:template match="/*">
                            <html xmlns="http://www.w3.org/1999/xhtml">
                                <xsl:for-each select="@*">
                                    <xsl:copy-of select="."/>
                                </xsl:for-each>
                                <xsl:call-template name="recursive"/>
                            </html>
                        </xsl:template>

                        <xsl:template name="recursive">
                            <xsl:for-each select="node()">
                                <xsl:choose>
                                    <xsl:when test="self::text()|self::processing-instruction()|self::comment()">
                                        <xsl:copy-of select="."/>
                                    </xsl:when>
                                    <xsl:when test="self::*">
                                        <xsl:element name="{name()}" namespace="http://www.w3.org/1999/xhtml">
                                            <xsl:for-each select="@*">
                                                <xsl:copy-of select="."/>
                                            </xsl:for-each>
                                            <xsl:call-template name="recursive"/>
                                        </xsl:element>
                                    </xsl:when>
                                </xsl:choose>
                            </xsl:for-each>
                        </xsl:template>

                    </xsl:stylesheet>
                </p:inline>
            </p:input>
        </p:xslt>
        <p:add-attribute match="/*" attribute-name="xml:base">
            <p:with-option name="attribute-value" select="p:resolve-uri($href)">
                <p:inline>
                    <irrelevant/>
                </p:inline>
            </p:with-option>
        </p:add-attribute>
    </p:group>

    <!--p:delete match="//html:head"/>
    <p:for-each>
        <p:iteration-source select="//html:tr"/>
        <p:choose>
            <p:when test="/*/html:td[1]='session'">
                <p:identity/>
            </p:when>
            <p:otherwise>
                <p:identity>
                    <p:input port="source">
                        <p:empty/>
                    </p:input>
                </p:identity>
            </p:otherwise>
        </p:choose>
    </p:for-each-->
    
    <p:group name="result">
        <p:identity>
            <p:input port="source">
                <p:inline>
                    <c:result/>
                </p:inline>
            </p:input>
        </p:identity>
        <p:add-attribute match="/*" attribute-name="unique-users">
            <p:with-option name="attribute-value" select="count(distinct-values(//html:tr/html:td[3][.!='']))">
                <p:pipe port="result" step="logs"/>
            </p:with-option>
        </p:add-attribute>
    </p:group>


</p:declare-step>
