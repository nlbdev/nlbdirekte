<xsl:stylesheet version="2.0" xmlns:nlb="http://www.nlb.no/2010/XSL"
	xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output encoding="utf-8" indent="yes" method="xml" version="1.0"/>

	<!--
	this stylesheet strips a SMIL-file of uninteresting
	information, as well as replaces tag and attribute
	names with shorthand forms to make things easier for
	the web client to handle (bandwidth and processing.)
	
	Elements:
		m: animation
		a: audio
		i: img
		r: ref
		t: text
		x: textstream
		v: video
		p: par
		s: seq
	
	Attributes:
		i: id
		t: type
		s: src
		b: begin
		e: end
		B: clip-begin / clipBegin
		E: clip-end / clipEnd
		d: dur
	
	-->

	<!-- The identity transform -->
	<xsl:template match="/ | @* | node()">
		<xsl:copy>
			<xsl:apply-templates select="@* | node()"/>
		</xsl:copy>
	</xsl:template>

	<!-- remove <head> and its contents -->
	<xsl:template match="head"/>

	<!-- remove <smil> but keep its contents -->
	<xsl:template match="smil">
		<xsl:apply-templates/>
	</xsl:template>

	<!-- treat <body> as a <seq> -->
	<xsl:template match="body">
		<s>
			<xsl:attribute name="i">
				<xsl:choose>
					<xsl:when test="@id">
						<xsl:value-of select="@id"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="tokenize(base-uri(.), '/')[last()]"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:apply-templates/>
		</s>
	</xsl:template>

	<xsl:template match="seq">
		<s>
			<xsl:call-template name="mediaObject"/>
			<xsl:apply-templates/>
		</s>
	</xsl:template>

	<xsl:template match="par">
		<p>
			<xsl:call-template name="mediaObject"/>
			<xsl:apply-templates/>
		</p>
	</xsl:template>

	<xsl:template match="animation">
		<m>
			<xsl:call-template name="mediaObject"/>
			<xsl:apply-templates/>
		</m>
	</xsl:template>

	<xsl:template match="audio">
		<xsl:if test="not(preceding-sibling::audio)">
			<a>
				<xsl:call-template name="mediaObject"/>
				<xsl:if test="following-sibling::audio">
					<xsl:variable name="last" select="following-sibling::audio[last()]"/>
					<xsl:attribute name="E"
						select="if ($last/@clip-end) then $last/@clip-end/nlb:fromSmilTime(.) else $last/@clipEnd/nlb:fromSmilTime(.)"/>
					<xsl:variable name="duration"
						select="max((0,
						if (count(@dur)) then nlb:fromSmilTime(@dur) else (
							  (if (count(@clip-end)) then nlb:fromSmilTime(@clip-end) else (if (count(@clipEnd)) then nlb:fromSmilTime(@clipEnd) else 0))
							- (if (count(@clip-begin)) then nlb:fromSmilTime(@clip-begin) else (if (count(@clipBegin)) then nlb:fromSmilTime(@clipBegin) else 0))
						)))
						+
						sum(following-sibling::audio[count(@dur)]/@dur/nlb:fromSmilTime(.))
						+ sum(following-sibling::audio[count(@dur)=0 and count(@clip-end)]/@clip-end/nlb:fromSmilTime(.))
						+ sum(following-sibling::audio[count(@dur)=0 and count(@clip-end)=0]/@clipEnd/nlb:fromSmilTime(.))
						- sum(following-sibling::audio[count(@dur)=0 and count(@clip-begin)]/@clip-begin/nlb:fromSmilTime(.))
						- sum(following-sibling::audio[count(@dur)=0 and count(@clip-begin)=0]/@clipBegin/nlb:fromSmilTime(.))
						"/>
					<xsl:attribute name="d" select="$duration"/>
					<xsl:choose>
						<xsl:when test="$last/@end">
							<xsl:attribute name="e" select="$last/@end/nlb:fromSmilTime(.)"/>
						</xsl:when>
						<xsl:when test="@begin">
							<xsl:attribute name="e" select="@begin/nlb:fromSmilTime(.)+$duration"/>
						</xsl:when>
					</xsl:choose>
					
					<!--xsl:attribute name="e"
						<select="$last/@end/nlb:fromSmilTime(.) | @begin/nlb:fromSmilTime(.)+$duration"/-->
				</xsl:if>
				<xsl:apply-templates/>
			</a>
		</xsl:if>
	</xsl:template>

	<xsl:template match="img">
		<i>
			<xsl:call-template name="mediaObject"/>
			<xsl:apply-templates/>
		</i>
	</xsl:template>

	<xsl:template match="ref">
		<r>
			<xsl:call-template name="mediaObject"/>
			<xsl:apply-templates/>
		</r>
	</xsl:template>

	<xsl:template match="text">
		<t>
			<xsl:call-template name="mediaObject"/>
			<xsl:apply-templates/>
		</t>
	</xsl:template>

	<xsl:template match="textstream">
		<x>
			<xsl:call-template name="mediaObject"/>
			<xsl:apply-templates/>
		</x>
	</xsl:template>

	<xsl:template match="video">
		<v>
			<xsl:call-template name="mediaObject"/>
			<xsl:apply-templates/>
		</v>
	</xsl:template>

	<xsl:template name="mediaObject">

		<xsl:if test="@type">
			<xsl:attribute name="t">
				<xsl:value-of select="@type"/>
			</xsl:attribute>
		</xsl:if>

		<xsl:if test="@begin">
			<xsl:attribute name="b">
				<xsl:value-of select="nlb:fromSmilTime(@begin)"/>
			</xsl:attribute>
		</xsl:if>

		<xsl:if test="@end">
			<xsl:attribute name="e">
				<xsl:value-of select="nlb:fromSmilTime(@end)"/>
			</xsl:attribute>
		</xsl:if>

		<xsl:choose>
			<xsl:when test="@clip-begin">
				<xsl:attribute name="B">
					<xsl:value-of select="nlb:fromSmilTime(@clip-begin)"/>
				</xsl:attribute>
			</xsl:when>
			<xsl:when test="@clipBegin">
				<xsl:attribute name="B">
					<xsl:value-of select="nlb:fromSmilTime(@clipBegin)"/>
				</xsl:attribute>
			</xsl:when>
		</xsl:choose>

		<xsl:choose>
			<xsl:when test="@clip-end">
				<xsl:attribute name="E">
					<xsl:value-of select="nlb:fromSmilTime(@clip-end)"/>
				</xsl:attribute>
			</xsl:when>
			<xsl:when test="@clipEnd">
				<xsl:attribute name="E">
					<xsl:value-of select="nlb:fromSmilTime(@clipEnd)"/>
				</xsl:attribute>
			</xsl:when>
		</xsl:choose>

		<xsl:if test="@dur">
			<xsl:attribute name="d">
				<xsl:value-of select="nlb:fromSmilTime(@dur)"/>
			</xsl:attribute>
		</xsl:if>

		<xsl:if test="@id">
			<xsl:attribute name="i">
				<xsl:value-of select="@id"/>
			</xsl:attribute>
		</xsl:if>

		<xsl:if test="@src">
			<xsl:attribute name="s">
				<xsl:value-of select="@src"/>
			</xsl:attribute>
		</xsl:if>

	</xsl:template>

	<xsl:function name="nlb:fromSmilTime">
		<xsl:param name="time"/>

		<!-- Fraction ::= DIGIT+ -->
		<xsl:variable name="fraction">
			<xsl:analyze-string regex=".*(\.\d+)\D*" select="$time">
				<xsl:matching-substring>
					<xsl:value-of select="regex-group(1)"/>
				</xsl:matching-substring>
			</xsl:analyze-string>
		</xsl:variable>

		<xsl:choose>

			<!-- Hours : Minutes : Seconds -->
			<xsl:when test="matches($time,&quot;\D*\d{2}:\d{2}:\d{2}.*&quot;)">
				<xsl:analyze-string regex="\D*(\d{2}):(\d{2}):(\d{2}).*" select="$time">
					<xsl:matching-substring>
						<xsl:value-of
							select="number(regex-group(1))*3600 + number(regex-group(2))*60 + number(regex-group(3)) + number($fraction)"
						/>
					</xsl:matching-substring>
				</xsl:analyze-string>
			</xsl:when>

			<!-- Minutes : Seconds -->
			<xsl:when test="matches($time,&quot;\D*\d{2}:\d{2}.*&quot;)">
				<xsl:analyze-string regex="\D*(\d{2}):(\d{2}).*" select="$time">
					<xsl:matching-substring>
						<xsl:value-of
							select="number(regex-group(1))*60 + number(regex-group(2)) + number($fraction)"
						/>
					</xsl:matching-substring>
				</xsl:analyze-string>
			</xsl:when>

			<!-- Timecount -->
			<xsl:otherwise>
				<xsl:analyze-string regex="\D*(\d+).*" select="$time">
					<xsl:matching-substring>
						<xsl:choose>
							<xsl:when test="substring($time,string-length($time)-1,1)='h'">
								<!-- hours -->
								<xsl:value-of
									select="(number(regex-group(1)) + number($fraction))*3600"/>
							</xsl:when>
							<xsl:when test="substring($time,string-length($time)-3,3)='min'">
								<!-- minutes -->
								<xsl:value-of
									select="(number(regex-group(1)) + number($fraction))*60"/>
							</xsl:when>
							<xsl:when test="substring($time,string-length($time)-2,2)='ms'">
								<!-- milliseconds -->
								<xsl:value-of
									select="(number(regex-group(1)) + number($fraction))*0.001"/>
							</xsl:when>
							<xsl:otherwise>
								<!-- seconds -->
								<xsl:value-of select="(number(regex-group(1)) + number($fraction))"
								/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:matching-substring>
				</xsl:analyze-string>
			</xsl:otherwise>
		</xsl:choose>

	</xsl:function>

</xsl:stylesheet>
