<?xml version="1.0" encoding="UTF-8"?>
<p:declare-step xmlns:p="http://www.w3.org/ns/xproc" xmlns:c="http://www.w3.org/ns/xproc-step" xmlns:cx="http://xmlcalabash.com/ns/extensions"
    xmlns:html="http://www.w3.org/1999/xhtml" xmlns:xd="http://www.daisy.org/pipeline2/docgen" xmlns:nlb="http://www.nlb.no/2010/XSL" version="1.0">

    <!--p:serialization port="result" indent="true" encoding="UTF-8"/-->

    <p:option name="shared-book" required="true"/>
    <p:option name="personal-book" required="true"/>
    <p:option name="python-log" required="true"/>

    <p:import href="library-1.0.xpl"/>
    <!-- cached http://xmlcalabash.com/extension/steps/library-1.0.xpl -->
    <p:import href="JsonML.xpl"/>

    <p:variable name="sharedBook" select="replace(replace(resolve-uri(resolve-uri('.',concat($shared-book,'/'))),'^file:',''),'[^/]+$','')"/>
    <p:variable name="personalBook" select="replace(resolve-uri(resolve-uri('.',concat($personal-book,'/'))),'[^/]+$','')"/>
    <p:variable name="tempDir"
        select="resolve-uri(concat('temp/',
                                              year-from-dateTime(current-dateTime()),
                                              month-from-dateTime(current-dateTime()),
                                              day-from-dateTime(current-dateTime()),
                                              hours-from-dateTime(current-dateTime()),
                                              minutes-from-dateTime(current-dateTime()),
                                              seconds-from-dateTime(current-dateTime())),base-uri())">
        <p:inline>
            <x/>
        </p:inline>
    </p:variable>

    <p:documentation>
        <xd:short>Loads the ncc as XHTML.</xd:short>
    </p:documentation>
    <p:group name="ncc-xhtml">
        <p:output port="result"/>
        <p:try>
            <p:group>
                <p:identity>
                    <p:input port="source">
                        <p:inline>
                            <c:request method="GET" detailed="true" override-content-type="text/html; charset=utf-8"/>
                        </p:inline>
                    </p:input>
                </p:identity>
                <p:add-attribute match="c:request" attribute-name="href">
                    <p:with-option name="attribute-value" select="concat($sharedBook,'ncc.html')"/>
                </p:add-attribute>
                <cx:message>
                    <p:with-option name="message" select="concat('HTTP-REQUEST: ',/c:request/@href)"/>
                </cx:message>
                <p:http-request cx:timeout="20"/>
            </p:group>
            <p:catch>
                <p:identity>
                    <p:input port="source">
                        <p:inline>
                            <c:request method="GET" detailed="true" override-content-type="text/html; charset=utf-8"/>
                        </p:inline>
                    </p:input>
                </p:identity>
                <p:add-attribute match="c:request" attribute-name="href">
                    <p:with-option name="attribute-value" select="concat($sharedBook,'ncc.xhtml')"/>
                </p:add-attribute>
                <cx:message>
                    <p:with-option name="message" select="concat('HTTP-REQUEST: ',/c:request/@href)"/>
                </cx:message>
                <p:http-request cx:timeout="20"/>
            </p:catch>
        </p:try>
        <p:unescape-markup content-type="text/html"/>
        <p:unwrap match="c:body"/>
    </p:group>

    <p:group name="ncc-json">
        <p:output port="result"/>
        <p:identity>
            <p:input port="source">
                <p:pipe port="result" step="ncc-xhtml"/>
            </p:input>
        </p:identity>
        <nlb:xml-to-json/>
        <p:store name="store-ncc-json">
            <p:input port="source" select="/c:data"/>
            <p:with-option name="href" select="concat($tempDir,'/ncc.json')"/>
            <p:with-option name="media-type" select="'text'"/>
            <p:with-option name="method" select="'text'"/>
        </p:store>
        <p:identity>
            <p:input port="source">
                <p:pipe port="result" step="store-ncc-json"/>
            </p:input>
        </p:identity>
    </p:group>

    <p:documentation>
        <xd:short>Loads, minifies and stores the SMIL-files.</xd:short>
    </p:documentation>
    <p:group name="smil-json">
        <p:output port="result"/>
        <p:xslt>
            <p:input port="source">
                <p:pipe port="result" step="ncc-xhtml"/>
            </p:input>
            <p:input port="parameters">
                <p:empty/>
            </p:input>
            <p:input port="stylesheet">
                <p:inline>
                    <xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="2.0">
                        <xsl:output method="xml" encoding="UTF-8" indent="no"/>
                        <xsl:template match="@*|node()">
                            <xsl:copy>
                                <xsl:apply-templates select="@*|node()"/>
                            </xsl:copy>
                        </xsl:template>
                        <xsl:template match="html:html">
                            <flow>
                                <xsl:for-each select="//html:a">
                                    <xsl:variable name="href" select="tokenize(@href,'#')[1]"/>
                                    <xsl:if test="not(preceding::html:a[tokenize(@href,'#')[1]=$href])">
                                        <flow smil="{$href}"/>
                                    </xsl:if>
                                </xsl:for-each>
                            </flow>
                        </xsl:template>
                    </xsl:stylesheet>
                </p:inline>
            </p:input>
        </p:xslt>
        <p:for-each>
            <p:iteration-source select="/flow/flow"/>
            <p:variable name="smil" select="/flow/@smil"/>
            <p:try>
                <p:group>
                    <cx:message>
                        <p:with-option name="message" select="concat('LOAD: ',concat($sharedBook,$smil))"/>
                    </cx:message>
                    <p:load>
                        <p:with-option name="href" select="concat($sharedBook,$smil)"/>
                    </p:load>
                </p:group>
                <p:catch>
                    <p:identity>
                        <p:input port="source">
                            <p:inline>
                                <c:request method="GET" detailed="true" override-content-type="application/xml; charset=utf-8"/>
                            </p:inline>
                        </p:input>
                    </p:identity>
                    <p:add-attribute match="c:request" attribute-name="href">
                        <p:with-option name="attribute-value" select="concat($sharedBook,$smil)"/>
                    </p:add-attribute>
                    <cx:message>
                        <p:with-option name="message" select="concat('HTTP-REQUEST: ',/c:request/@href)"/>
                    </cx:message>
                    <p:http-request cx:timeout="20"/>
                </p:catch>
            </p:try>
            <p:xslt>
                <p:input port="parameters">
                    <p:empty/>
                </p:input>
                <p:input port="stylesheet">
                    <p:document href="stripSmil.xslt"/>
                </p:input>
            </p:xslt>
            <nlb:xml-to-json/>
            <p:store name="store-smil-json">
                <p:input port="source" select="/c:data"/>
                <p:with-option name="href" select="concat($tempDir,'/',$smil,'.json')"/>
                <p:with-option name="media-type" select="'text'"/>
                <p:with-option name="method" select="'text'"/>
            </p:store>
            <p:identity>
                <p:input port="source">
                    <p:pipe port="result" step="store-smil-json"/>
                </p:input>
            </p:identity>
        </p:for-each>
        <p:wrap-sequence wrapper="c:result"/>
    </p:group>

    <p:exec name="prepare">
        <p:input port="source">
            <p:empty/>
        </p:input>
        <p:with-option name="command" select="'python'">
            <p:pipe port="result" step="ncc-json"/>
        </p:with-option>
        <p:with-option name="args" select="concat('prepare.py ',replace($tempDir,'^file:',''),' ',$python-log)">
            <p:pipe port="result" step="smil-json"/>
        </p:with-option>
        <p:with-option name="result-is-xml" select="'false'"/>
    </p:exec>

    <p:documentation>
        <xd:short>Stored metadata.json, toc.json, pagelist.json and smil.json in the users profile area.</xd:short>
        <xd:detail>This method is not thread-safe. However, Calabash is single-threaded (as of 2010) and runs all steps in sequence, so that a dependency on
            "prepare" can be introduced.</xd:detail>
    </p:documentation>
    <p:sink>
        <p:input port="source">
            <p:pipe port="result" step="prepare"/>
            <p:pipe port="result" step="ncc-json"/>
            <p:pipe port="result" step="smil-json"/>
        </p:input>
    </p:sink>
    <p:group>
        <p:identity>
            <p:input port="source">
                <p:inline>
                    <c:request method="GET" detailed="true"
                        override-content-type="text/plain; charset=utf-8"/>
                </p:inline>
            </p:input>
        </p:identity>
        <p:add-attribute match="c:request" attribute-name="href">
            <p:with-option name="attribute-value" select="concat($tempDir,'/metadata.json')">
                <p:pipe port="result" step="prepare"/>
            </p:with-option>
        </p:add-attribute>
        <cx:message>
            <p:with-option name="message" select="concat('HTTP-REQUEST: ',/c:request/@href)"/>
        </cx:message>
        <p:http-request cx:timeout="20"/>
        <p:store>
            <p:input port="source" select="/c:body"/>
            <p:with-option name="href" select="concat($personalBook,'metadata.json')"/>
            <p:with-option name="media-type" select="'text'"/>
            <p:with-option name="method" select="'text'"/>
        </p:store>

        <p:identity>
            <p:input port="source">
                <p:inline>
                    <c:request method="GET" detailed="true"
                        override-content-type="text/plain; charset=utf-8"/>
                </p:inline>
            </p:input>
        </p:identity>
        <p:add-attribute match="c:request" attribute-name="href">
            <p:with-option name="attribute-value" select="concat($tempDir,'/toc.json')">
                <p:pipe port="result" step="prepare"/>
            </p:with-option>
        </p:add-attribute>
        <cx:message>
            <p:with-option name="message" select="concat('HTTP-REQUEST: ',/c:request/@href)"/>
        </cx:message>
        <p:http-request cx:timeout="20"/>
        <p:store>
            <p:input port="source" select="/c:body"/>
            <p:with-option name="href" select="concat($personalBook,'toc.json')"/>
            <p:with-option name="media-type" select="'text'"/>
            <p:with-option name="method" select="'text'"/>
        </p:store>

        <p:identity>
            <p:input port="source">
                <p:inline>
                    <c:request method="GET" detailed="true"
                        override-content-type="text/plain; charset=utf-8"/>
                </p:inline>
            </p:input>
        </p:identity>
        <p:add-attribute match="c:request" attribute-name="href">
            <p:with-option name="attribute-value" select="concat($tempDir,'/pagelist.json')">
                <p:pipe port="result" step="prepare"/>
            </p:with-option>
        </p:add-attribute>
        <cx:message>
            <p:with-option name="message" select="concat('HTTP-REQUEST: ',/c:request/@href)"/>
        </cx:message>
        <p:http-request cx:timeout="20"/>
        <p:store>
            <p:input port="source" select="/c:body"/>
            <p:with-option name="href" select="concat($personalBook,'pagelist.json')"/>
            <p:with-option name="media-type" select="'text'"/>
            <p:with-option name="method" select="'text'"/>
        </p:store>

        <p:identity>
            <p:input port="source">
                <p:inline>
                    <c:request method="GET" detailed="true"
                        override-content-type="text/plain; charset=utf-8"/>
                </p:inline>
            </p:input>
        </p:identity>
        <p:add-attribute match="c:request" attribute-name="href">
            <p:with-option name="attribute-value" select="concat($tempDir,'/smil.json')">
                <p:pipe port="result" step="prepare"/>
            </p:with-option>
        </p:add-attribute>
        <cx:message>
            <p:with-option name="message" select="concat('HTTP-REQUEST: ',/c:request/@href)"/>
        </cx:message>
        <p:http-request cx:timeout="20"/>
        <p:store>
            <p:input port="source" select="/c:body"/>
            <p:with-option name="href" select="concat($personalBook,'smil.json')"/>
            <p:with-option name="media-type" select="'text'"/>
            <p:with-option name="method" select="'text'"/>
        </p:store>
    </p:group>

</p:declare-step>
