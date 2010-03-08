<?xml version="1.0" encoding="UTF-8"?>
<!--
 # The Contents of this file are made available subject to the terms of
 # the GNU Lesser General Public License Version 2.1

 # Nicolas Barts, Cléo/Revue.org
 # copyright 2009, 2010

 # OpenOffice v3.x
-->
<!--
 # This stylesheet is derived from the OpenOffice to TEIP5 conversion
 # Sebastian Rahtz / University of Oxford, copyright 2005
 #  derived from the OpenOffice to Docbook conversion
 #  Sun Microsystems Inc., October, 2000

 #  GNU Lesser General Public License Version 2.1
 #  =============================================
 #  Copyright 2000 by Sun Microsystems, Inc.
 #  901 San Antonio Road, Palo Alto, CA 94303, USA
 #
 #  This library is free software; you can redistribute it and/or
 #  modify it under the terms of the GNU Lesser General Public
 #  License version 2.1, as published by the Free Software Foundation.
 #
 #  This library is distributed in the hope that it will be useful,
 #  but WITHOUT ANY WARRANTY; without even the implied warranty of
 #  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 #  Lesser General Public License for more details.
 #
 #  You should have received a copy of the GNU Lesser General Public
 #  License along with this library; if not, write to the Free Software
 #  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
 #  MA  02111-1307  USA
 #
-->
<xsl:stylesheet
  exclude-result-prefixes="office style text table draw fo xlink dc meta number svg chart dr3d math form script ooo ooow oooc dom xforms xsd xsi"
  office:version="1.0" version="1.0" xmlns="http://www.tei-c.org/ns/1.0"
  xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dom="http://www.w3.org/2001/xml-events"
  xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0"
  xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
  xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
  xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0"
  xmlns:math="http://www.w3.org/1998/Math/MathML"
  xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"
  xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0"
  xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
  xmlns:ooo="http://openoffice.org/2004/office"
  xmlns:oooc="http://openoffice.org/2004/calc"
  xmlns:ooow="http://openoffice.org/2004/writer"
  xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0"
  xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
  xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"
  xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
  xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
  xmlns:xforms="http://www.w3.org/2002/xforms"
  xmlns:xlink="http://www.w3.org/1999/xlink"
  xmlns:xsd="http://www.w3.org/2001/XMLSchema"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:key
        name="STYLES"
        match="style:style"
        use="@style:name"/>

    <xsl:param name="META" select="/"/>
    <xsl:output encoding="utf-8" indent="yes"/>
    <!-- <xsl:strip-space elements="text:span"/> -->
    <xsl:preserve-space elements="*" />


<!--
    office:document
-->
    <xsl:template match="/office:document">
    <TEI xmlns="http://www.tei-c.org/ns/1.0">
        <xsl:call-template name="teiHeader"/>
        <xsl:apply-templates/>
    </TEI>
    </xsl:template>

    <xsl:template name="teiHeader">
        <teiHeader xml:lang="en">
            <fileDesc>
                <titleStmt>
                    <title>
                        <xsl:value-of select="/office:document/office:meta/dc:title"/>
                    </title>
                    <author>
                        <xsl:value-of select="/office:document/office:meta/dc:creator"/>
                    </author>
                </titleStmt>
                <publicationStmt>
                    <publisher>Revues.org</publisher>
                    <availability status="free">
                        <p>Open Access</p>
                    </availability>
                    <date>
                        <xsl:value-of select="/office:document/office:meta/dc:date"/>
                    </date>
                </publicationStmt>
                <sourceDesc>
                    <biblFull>
                        <titleStmt>
                            <title>
                                <xsl:value-of select="/office:document/office:meta/dc:title"/>
                            </title>
                            <author>
                                <xsl:value-of select="/office:document/office:meta/meta:initial-creator"/>
                            </author>
                        </titleStmt>
                        <publicationStmt>
                            <date>
                                <xsl:value-of select="/office:document/office:meta/meta:creation-date"/>
                            </date>
                        </publicationStmt>
                    </biblFull>
                </sourceDesc>
            </fileDesc>
            <encodingDesc>
                <projectDesc>
                    <p>Revues.org: centre for open electronic publishing</p>
                    <p>Revues.org is the platform for journals in the humanities and social sciences, open to quality periodicals looking to publish full-text articles online.</p>
                </projectDesc>
                <appInfo>
                    <application version="2.32" ident="OTX">
                        <label>Opentext - CLEO / Revues.org</label>
                        <desc>
                            <ref target="http://www.tei-c.org/">Powered by TEI</ref>
                        </desc>
                    </application>
                </appInfo>
            </encodingDesc>
            <profileDesc>
                <langUsage>
                    <language ident="fr"/>
                </langUsage>
                <textClass>
                    <keywords scheme="lodel">
                        <term>Lodel</term>
                        <term>Opentext</term>
                        <term>TEI P5</term>
                    </keywords>
                </textClass>
            </profileDesc>
        </teiHeader>
    </xsl:template>

<!-- office:body -->
    <xsl:template match="/office:document/office:body">
        <text>
            <front></front>
            <xsl:apply-templates/>
            <back></back>
        </text>
    </xsl:template>

<!-- office:text -->
    <xsl:template match="office:text">
        <body>
        <xsl:apply-templates/>
        </body>
    </xsl:template>


    <!-- special case paragraphs -->
    <xsl:template match="text:p[@text:style-name='XMLComment']">
        <xsl:comment>
            <xsl:value-of select="."/>
        </xsl:comment>
    </xsl:template>

    <!-- paragraphs -->
    <xsl:template match="text:p[@text:style-name]">
        <xsl:variable name="Style">
            <xsl:value-of select="@text:style-name"/>
        </xsl:variable>
        <xsl:choose>
            <xsl:when test="parent::table:table-cell">
                <xsl:choose>
                    <xsl:when test="$Style='standard'">
                        <s rendition="#standard"><xsl:apply-templates/></s>
                    </xsl:when>
                    <xsl:when test="starts-with($Style,'P')">
                        <s rendition="#{$Style}"><xsl:apply-templates/></s>
                    </xsl:when>
                    <xsl:otherwise>
                        <s rend="{$Style}"><xsl:apply-templates/></s>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:when>
            <xsl:when test="$Style='standard'">
                <p rendition="#standard">
                <xsl:apply-templates/>
                </p>
            </xsl:when>
            <xsl:when test="starts-with($Style,'P')">
                <p rendition="#{$Style}">
                <xsl:apply-templates/>
                </p>
            </xsl:when>
            <xsl:otherwise>
                <p rend="{$Style}">
                <xsl:apply-templates/>
                </p>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

<!-- normal paragraphs
    <xsl:template match="text:p">
        <xsl:choose>
            <xsl:when test="parent::text:list-item">
                <xsl:apply-templates/>
            </xsl:when>
            <xsl:when test="parent::table:table-cell">
                <xsl:call-template name="applyStyle"/>
            </xsl:when>
            <xsl:when test="@text:style-name='Table'"/>
            <xsl:when test="normalize-space(.)=''"/>
            <xsl:when test="text:span[@text:style-name = 'XrefLabel']"/>
            <xsl:when test="@text:style-name='Speech'">
                <sp>
                <speaker/>
                <p>
                    <xsl:call-template name="id.attribute"/>
                    <xsl:call-template name="applyStyle"/>
                </p>
                </sp>
            </xsl:when>
            <xsl:otherwise>
                <p>
                <xsl:call-template name="id.attribute"/>
                <xsl:call-template name="applyStyle"/>
                </p>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
 -->

    <xsl:template match="office:annotation/text:p">
        <note>
        <xsl:apply-templates/>
        </note>
    </xsl:template>


    <!-- lists -->

    <!-- headings -->
    <xsl:template match="text:list[@text:style-name='outline']">
        <xsl:if test="descendant::text:h[@text:outline-level]">
            <xsl:apply-templates/>
        </xsl:if>
    </xsl:template>

    <xsl:template match="text:list[@text:continue-numbering]">
        <xsl:if test="descendant::text:h[@text:outline-level]">
            <xsl:apply-templates/>
        </xsl:if>
    </xsl:template>

    <xsl:template match="text:h[@text:outline-level]">
        <xsl:variable name="rend">
            <xsl:value-of select="concat('heading',@text:outline-level)"/>
        </xsl:variable>
        <p rend="{$rend}">
            <xsl:apply-templates/>
        </p>
    </xsl:template>

    <!-- list-item -->
    <xsl:template match="text:list">
        <xsl:choose>
            <xsl:when test="descendant::text:h[@text:outline-level]">
                <xsl:apply-templates/>
            </xsl:when>
            <!--
            <xsl:when test="text:list-item/text:p">
                <xsl:for-each select="text:list-item">
                    <xsl:apply-templates/>
                </xsl:for-each>
            </xsl:when>
            -->
            <xsl:when test="@text:style-name='Var List'">
                <list><xsl:apply-templates/></list>
            </xsl:when>
            <xsl:when test="starts-with(@text:style-name,'ordered')">
                <list type="ordered">
                    <xsl:apply-templates/>
                </list>
            </xsl:when>
            <xsl:otherwise>
                <list type="unordered">
                    <xsl:apply-templates/>
                </list>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="text:list-header">
        <head><xsl:apply-templates/></head>
    </xsl:template>

    <xsl:template match="text:list-item">
        <xsl:choose>
            <xsl:when test="descendant::text:h[@text:outline-level]">
                <xsl:apply-templates/>
            </xsl:when>
            <xsl:when test="ancestor::text:list[@text:style-name='outline']">
                <xsl:apply-templates/>
            </xsl:when>
            <xsl:when test="ancestor::text:list[@text:continue-numbering='true']">
                <xsl:apply-templates/>
            </xsl:when>
            <xsl:when test="parent::text:list/@text:style-name='Var List'">
                <item>
                <xsl:for-each select="text:p[@text:style-name='VarList Term']">
                    <xsl:apply-templates select="."/>
                </xsl:for-each>
                </item>
            </xsl:when>
            <xsl:otherwise>
                <item>
                <xsl:apply-templates/>
                </item>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>


  <!-- inline -->

    <xsl:template match="text:span[@text:style-name]">
        <xsl:variable name="Style">
            <xsl:value-of select="@text:style-name"/>
        </xsl:variable>
        <xsl:choose>
            <xsl:when test="../text:h">
                <xsl:apply-templates/>
            </xsl:when>
            <!-- <xsl:when test="normalize-space(.)=''"/> -->
            <xsl:when test="starts-with($Style,'T')">
                <hi rendition="#{$Style}"><xsl:apply-templates/></hi>
            </xsl:when>
            <xsl:otherwise>
                <hi rend="{$Style}"><xsl:apply-templates/></hi>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>


<!--
    <xsl:template name="applyStyle">
        <xsl:param name="tag">TODO</xsl:param>
        <xsl:param name="level"/>

<xsl:element name="{$tag}">

        <xsl:if test="$tag='ab'">
            <xsl:attribute name="type">
                <xsl:text>header</xsl:text>
            </xsl:attribute>
            <xsl:attribute name="level">
                <xsl:value-of select="$level"/>
            </xsl:attribute>
        </xsl:if>

        <xsl:variable name="name">
            <xsl:value-of select="@text:style-name"/>
        </xsl:variable>
        <xsl:choose>
            <xsl:when test="string-length(.)=0"/>
            <xsl:when test="text:note">
                # - TODO -
                <xsl:attribute name="rendition">
                    <xsl:text>#footnotesymbol</xsl:text>
                </xsl:attribute>
                <xsl:apply-templates/>
            </xsl:when>

            <xsl:when test="key('STYLES',$name)">
                <xsl:variable name="contents">
                    <xsl:apply-templates/>
                </xsl:variable>
# -
                <xsl:variable name="rend">
                </xsl:variable>
# -
                <xsl:for-each select="key('STYLES',$name)">
                    <xsl:if test="@style:parent-style-name">
                        <xsl:if test="not(starts-with($name,'T'))"> 
                            <xsl:attribute name="rendition">
                                <xsl:choose>
                                    <xsl:when test="starts-with($name,'P')">
                                        <xsl:value-of select="concat('#',@style:parent-style-name)"/>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:value-of select="concat('#',$name)"/>
                                    </xsl:otherwise>
                                </xsl:choose>
                            </xsl:attribute>
                        </xsl:if>
                    </xsl:if>
                    <xsl:if test="style:text-properties[@fo:language]">
                        <xsl:attribute name="xml:lang">
                            <xsl:value-of select="style:text-properties/@fo:language"/>
                        </xsl:attribute>
                    </xsl:if>
                    <xsl:attribute name="rend">
# -
<style:paragraph-properties fo:text-align="end" style:justify-single-word="false"/>
</style:style> 
! text-align:right;

<style:style style:name="P4" style:family="paragraph" style:parent-style-name="standard">
<style:paragraph-properties fo:text-align="justify" 
! text-align:justify;
# -
                        <xsl:for-each select="style:text-properties/@*">
                            # -[[[<xsl:value-of select="name(.)"/>:<xsl:value-of select="."/>]]]-
                            <xsl:variable name="value">
                                <xsl:value-of select="."/>
                            </xsl:variable>
                            <xsl:choose>
# -TODO dir: ltr / rtl -
                                <xsl:when test="contains(name(.),'asian')"/>
                                <xsl:when test="contains(name(.),'complex')"/>
                                <xsl:when test="name(.)='fo:language'"/>
                                <xsl:when test="name(.)='fo:country'"/>
                                <xsl:when test="name(.)='style:font-name'"/>

                                <xsl:when test="name(.)='style:text-position'">
                                    <xsl:choose>
                                        <xsl:when test="starts-with($value,'super')">
                                            <xsl:text>sup </xsl:text>
                                        </xsl:when>
                                        <xsl:when test="starts-with($value,'sub')">
                                            <xsl:text>sub </xsl:text>
                                        </xsl:when>
                                        <xsl:otherwise>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:when>
                                <xsl:when test="name(.)='fo:font-weight'">
                                    <xsl:choose>
                                        <xsl:when test="$value='bold'">
                                            <xsl:text>bold </xsl:text>
                                        </xsl:when>
                                        <xsl:otherwise>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:when>
                                <xsl:when test="name(.)='fo:font-style'">
                                    <xsl:choose>
                                        <xsl:when test="$value='italic'">
                                            <xsl:text>italic </xsl:text>
                                        </xsl:when>
                                        <xsl:otherwise>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:when>
                                <xsl:when test="name(.)='style:text-underline-style'">
                                    <xsl:choose>
                                        <xsl:when test="$value='solid'">
                                            <xsl:text>underline </xsl:text>
                                        </xsl:when>
                                        <xsl:otherwise>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:when>
                                <xsl:when test="name(.)='fo:font-variant'">
                                    <xsl:choose>
                                        <xsl:when test="$value='small-caps'">
                                            <xsl:text>small-caps </xsl:text>
                                        </xsl:when>
                                        <xsl:otherwise>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:when>
                                <xsl:when test="name(.)='fo:text-transform'">
                                    <xsl:choose>
                                        <xsl:when test="$value='uppercase'">
                                            <xsl:text>uppercase </xsl:text>
                                        </xsl:when>
                                        <xsl:when test="$value='lowercase'">
                                            <xsl:text>lowercase </xsl:text>
                                        </xsl:when>
                                        <xsl:otherwise>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:when>
                                <xsl:when test="name(.)='fo:text-align'">
                                    <xsl:choose>
                                        <xsl:when test="$value='center'">
                                            <xsl:text>center </xsl:text>
                                        </xsl:when>
                                        <xsl:otherwise>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:when>

                                <xsl:otherwise>

                                    <xsl:message>
                                        <xsl:copy-of select="name(.)"/>:<xsl:copy-of select="$value"/>;
                                    </xsl:message>

                                </xsl:otherwise>

                            </xsl:choose>
                        </xsl:for-each>
                    </xsl:attribute>
                </xsl:for-each>
                <xsl:apply-templates/>
            </xsl:when>
            <xsl:otherwise>
                <DBG><xsl:apply-templates/></DBG>
            </xsl:otherwise>
        </xsl:choose>
</xsl:element>
    </xsl:template>
-->

    <!-- tables -->
    <xsl:template match="table:table">
        <table rend="frame">
        <xsl:if test="@table:name and not(@table:name = 'local-table')">
            <xsl:attribute name="xml:id">
                <xsl:value-of select="@table:name"/>
            </xsl:attribute>
        </xsl:if>
        <xsl:if test="following-sibling::text:p[@text:style-name='table']">
            <head>
                <xsl:value-of select="following-sibling::text:p[@text:style-name='table']"/>
            </head>
        </xsl:if>
        <xsl:call-template name="generictable"/>
        </table>
    </xsl:template>

    <xsl:template name="generictable">
        <xsl:variable name="cells" select="count(descendant::table:table-cell)"/>
        <xsl:variable name="rows">
            <xsl:value-of select="count(descendant::table:table-row) "/>
        </xsl:variable>
        <xsl:variable name="cols">
            <xsl:value-of select="$cells div $rows"/>
        </xsl:variable>
        <xsl:variable name="numcols">
        <xsl:choose>
            <xsl:when test="child::table:table-column/@table:number-columns-repeated">
                <xsl:value-of select="number(table:table-column/@table:number-columns-repeated+1)"/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$cols"/>
            </xsl:otherwise>
        </xsl:choose>
        </xsl:variable>
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template name="colspec">
        <xsl:param name="left"/>
        <xsl:if test="number($left &lt; ( table:table-column/@table:number-columns-repeated +2)  )">
        <colspec>
            <xsl:attribute name="colnum">
                <xsl:value-of select="$left"/>
            </xsl:attribute>
            <xsl:attribute name="colname">
                <xsl:text>c</xsl:text>
                <xsl:value-of select="$left"/>
            </xsl:attribute>
        </colspec>
        <xsl:call-template name="colspec">
            <xsl:with-param name="left" select="$left+1"/>
        </xsl:call-template>
        </xsl:if>
    </xsl:template>

    <xsl:template match="table:table-column">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="table:table-header-rows">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="table:table-header-rows/table:table-row">
        <row role="label">
        <xsl:apply-templates/>
        </row>
    </xsl:template>

    <xsl:template match="table:table/table:table-row">
        <row>
        <xsl:apply-templates/>
        </row>
    </xsl:template>

    <xsl:template match="table:table-cell/text:h">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="table:table-cell">
        <cell>
        <xsl:if test="@table:style-name">
            <xsl:attribute name="rendition">
            <xsl:value-of select="@table:style-name"/>
            </xsl:attribute>
        </xsl:if>
        <xsl:if test="@table:number-columns-spanned">
            <xsl:attribute name="cols">
            <xsl:value-of select="@table:number-columns-spanned"/>
            </xsl:attribute>
        </xsl:if>
        <xsl:if test="@table:number-rows-spanned">
            <xsl:attribute name="rows">
            <xsl:value-of select="@table:number-rows-spanned"/>
            </xsl:attribute>
        </xsl:if>
        <xsl:if test="text:h">
            <xsl:attribute name="role">label</xsl:attribute>
        </xsl:if>
        <xsl:apply-templates/>
        </cell>
    </xsl:template>


    <!-- notes -->
        <!-- we don't need the footnotesymbol style 
        <xsl:template match="text:span[contains(@text:style-name,'footnotesymbol')]">
            <xsl:apply-templates/>
        </xsl:template>
        -->
    <xsl:template match="text:note-citation">
    </xsl:template>

    <xsl:template match="text:note-body">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="text:note">
    <note>
        <xsl:choose>
            <xsl:when test="@text:note-class='endnote'">
                <xsl:attribute name="place">end</xsl:attribute>
            </xsl:when>
            <xsl:when test="@text:note-class='footnote'">
                <xsl:attribute name="place">foot</xsl:attribute>
            </xsl:when>
        </xsl:choose>
        <xsl:if test="text:note-citation">
            <xsl:attribute name="n">
                <xsl:value-of select="text:note-citation"/>
            </xsl:attribute>
        </xsl:if>
        <xsl:apply-templates/>
    </note>
    </xsl:template>

    <!-- sxw notes -->
    <xsl:template match="text:footnote-citation"/>
    <xsl:template match="text:footnote-body">
        <xsl:apply-templates/>
    </xsl:template>
    <xsl:template match="text:footnote">
    <note>
        <xsl:choose>
            <xsl:when test="@text:note-class='endnote'">
                <xsl:attribute name="place">end</xsl:attribute>
            </xsl:when>
            <xsl:when test="@text:note-class='footnote'">
                <xsl:attribute name="place">foot</xsl:attribute>
            </xsl:when>
        </xsl:choose>
        <xsl:if test="text:footnote-citation">
            <xsl:attribute name="n">
                <xsl:value-of select="text:note-citation"/>
            </xsl:attribute>
        </xsl:if>
        <xsl:apply-templates/>
    </note>
    </xsl:template>


    <!-- images -->
    <xsl:template match="draw:frame/draw:image[@xlink:href]">
        <xsl:if test="not(starts-with(@xlink:href, './Object'))"> <!-- TODO OLE -->
            <graphic>
            <xsl:attribute name="url">
                <xsl:value-of select="@xlink:href"/>
            </xsl:attribute>
            </graphic>
        </xsl:if>
    </xsl:template>

    <!-- drawing -->
    <xsl:template match="draw:plugin">
        <ptr target="{@xlink:href}"/>
    </xsl:template>

    <xsl:template match="draw:text-box">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="draw:frame">
        <xsl:choose>
        <xsl:when test="ancestor::draw:frame">
            <xsl:apply-templates/>
        </xsl:when>
        <xsl:otherwise>
            <figure>
            <xsl:apply-templates/>
            </figure>
        </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="draw:image">
        <xsl:choose>
        <xsl:when test="ancestor::draw:text-box">
            <xsl:call-template name="findGraphic"/>
        </xsl:when>
        <xsl:when test="parent::text:p[@text:style-name='Mediaobject']">
            <figure>
            <xsl:call-template name="findGraphic"/>
            <head>
                <xsl:value-of select="."/>
            </head>
            </figure>
        </xsl:when>
        <xsl:otherwise>
            <figure>
            <xsl:call-template name="findGraphic"/>
            </figure>
        </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="findGraphic">
        <xsl:choose>
        <xsl:when test="office:binary-data">
            <xsl:apply-templates/>
        </xsl:when>
        <xsl:when test="@xlink:href">
            <graphic>
            <xsl:attribute name="url">
                <xsl:value-of select="@xlink:href"/>
            </xsl:attribute>
            </graphic>
        </xsl:when>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="office:binary-data">    
        <binaryObject mimeType="image/jpg">
            <xsl:value-of select="."/>
        </binaryObject>
    </xsl:template>


    <!-- linking -->
    <xsl:template match="text:a">
        <xsl:choose>
        <xsl:when test="starts-with(@xlink:href,'mailto:')">
            <xsl:choose>
            <xsl:when test=".=@xlink:href">
                <ptr target="{substring-after(@xlink:href,'mailto:')}"/>
            </xsl:when>
            <xsl:otherwise>
                <ref target="{@xlink:href}"><xsl:apply-templates/></ref>
            </xsl:otherwise>
            </xsl:choose>
        </xsl:when>
        <xsl:when test="contains(@xlink:href,'://')">
            <xsl:choose>
            <xsl:when test=".=@xlink:href">
                <ptr target="{@xlink:href}"/>
            </xsl:when>
            <xsl:otherwise>
                <ref target="{@xlink:href}"><xsl:apply-templates/></ref>
            </xsl:otherwise>
            </xsl:choose>
        </xsl:when>
        <xsl:when test="not(contains(@xlink:href,'#'))">
            <ref target="{@xlink:href}"><xsl:apply-templates/></ref>
        </xsl:when>
        <xsl:otherwise>
            <xsl:variable name="linkvar" select="@xlink:href"/>
            <xsl:choose>
            <xsl:when test=".=$linkvar">
                <ptr target="{$linkvar}"/>
            </xsl:when>
            <xsl:otherwise>
                <ref target="{$linkvar}"><xsl:apply-templates/></ref>
            </xsl:otherwise>
            </xsl:choose>
        </xsl:otherwise>
        </xsl:choose>
    </xsl:template>


    <!-- break -->
    <xsl:template match="text:soft-page-break">
        <xsl:if test="not(parent::text:span[@text:style-name='l'])">
        <pb/>
        </xsl:if>
    </xsl:template>

    <xsl:template match="text:line-break">
        <xsl:if test="not(parent::text:span[@text:style-name='l'])">
        <lb/>
        </xsl:if>
    </xsl:template>

    <xsl:template match="text:tab">
        <xsl:text>  </xsl:text>
    </xsl:template>


  <xsl:template match="text:reference-ref">
    <ptr target="#id_{@text:ref-name}"/>
  </xsl:template>

  <xsl:template match="text:reference-mark-start"/>

  <xsl:template match="text:reference-mark-end"/>

  <xsl:template match="comment">
    <xsl:comment>
      <xsl:value-of select="."/>
    </xsl:comment>
  </xsl:template>

  <xsl:template match="text:user-index-mark">
    <index indexName="{@text:index-name}">
      <term><xsl:value-of select="@text:string-value"/></term>
    </index>
  </xsl:template>

  <xsl:template match="text:alphabetical-index-mark">
    <index>
      <xsl:if test="@text:id">
	<xsl:attribute name="xml:id">
	  <xsl:value-of select="@text:id"/>
	</xsl:attribute>
      </xsl:if>
      <xsl:choose>
	<xsl:when test="@text:key1">
	  <term><xsl:value-of select="@text:key1"/></term>
	  <index>
	    <term><xsl:value-of select="@text:string-value"/></term>
	  </index>
	</xsl:when>
	<xsl:otherwise>
	  <term><xsl:value-of select="@text:string-value"/></term>
	</xsl:otherwise>
      </xsl:choose>
    </index>
  </xsl:template>

  <xsl:template match="text:alphabetical-index">
    <index>
      <xsl:apply-templates select="text:index-body"/>
    </index>
  </xsl:template>

  <xsl:template match="text:index-body">
    <xsl:for-each select="text:p[@text:style-name = 'Index 1']">
      <index>
        <term><xsl:value-of select="."/></term>
        <xsl:if test="key('secondary_children', generate-id())">
          <index>
	    <term><xsl:value-of select="key('secondary_children', generate-id())"/></term>
	  </index>
        </xsl:if>
      </index>
    </xsl:for-each>
  </xsl:template>

  <xsl:template match="text:bookmark-ref">
    <ref target="#id_{@text:ref-name}" type="{@text:reference-format}"><xsl:apply-templates/></ref>
  </xsl:template>

  <xsl:template match="text:bookmark-start">
    <anchor type="bookmark-start">
      <xsl:attribute name="xml:id">
	<xsl:text>id_</xsl:text>
	<xsl:value-of select="@text:name"/>
      </xsl:attribute>
    </anchor>
  </xsl:template>

  <xsl:template match="text:bookmark-end">
    <anchor type="bookmark-end">
      <xsl:attribute name="corresp">
	<xsl:text>#id_</xsl:text>
	<xsl:value-of select="@text:name"/>
      </xsl:attribute>
    </anchor>
  </xsl:template>

  <xsl:template match="text:bookmark">
    <anchor>
      <xsl:attribute name="xml:id">
	<xsl:text>id_</xsl:text>
	<xsl:value-of select="@text:name"/>
      </xsl:attribute>
    </anchor>
  </xsl:template>



<!--
These seem to have no obvious translation
-->
    <xsl:template match="text:endnotes-configuration"/>
    <xsl:template match="text:file-name"/>
    <xsl:template match="text:footnotes-configuration"/>
    <xsl:template match="text:linenumbering-configuration"/>
    <xsl:template match="text:list-level-style-bullet"/>
    <xsl:template match="text:list-level-style-number"/>
    <xsl:template match="text:list-style"/>
    <xsl:template match="text:outline-level-style"/>
    <xsl:template match="text:outline-style"/>
    <xsl:template match="text:s"/>

    <!-- TODO : warnings ?! -->
    <xsl:template match="draw:rect"/>
    <!-- anchor : mode revision -->
    <xsl:template match='anchor'/>
    <!-- unkwnon tag -->
    <xsl:template match="text:*"> 
        [[[UNTRANSLATED <xsl:value-of select="name(.)"/>: <xsl:apply-templates/>]]]
    </xsl:template>

    <!-- sections of the OO format we don't need at present -->
    <xsl:template match="office:automatic-styles"/>
    <xsl:template match="office:font-decls"/>
    <xsl:template match="office:meta"/>
    <xsl:template match="office:script"/>
    <xsl:template match="office:settings"/>
    <xsl:template match="office:styles"/>
    <xsl:template match="style:*"/>

    <xsl:template match="dc:*">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="meta:creation-date">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="meta:editing-cycles"/>
    <xsl:template match="meta:editing-duration"/>
    <xsl:template match="meta:generator"/>
    <xsl:template match="meta:user-defined"/>

    <!--
    <xsl:template match="text()">
    <xsl:apply-templates select="normalize-space(.)"/>
    </xsl:template>
    -->

    <xsl:template match="text:section">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="text:sequence-decl"/>
    <xsl:template match="text:sequence-decls"/>
    <xsl:template match="text:sequence"/>

    <xsl:template match="text:section-source"/>

    <xsl:template name="stars">
        <xsl:param name="n"/>
        <xsl:if test="$n &gt;0">
            <xsl:text>*</xsl:text>
            <xsl:call-template name="stars">
            <xsl:with-param name="n">
                <xsl:value-of select="$n - 1"/>
            </xsl:with-param>
            </xsl:call-template>
        </xsl:if>
    </xsl:template>

    <xsl:template match="text:change|text:changed-region|text:change-end|text:change-start">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="text:table-of-content"/>
    <xsl:template match="text:index-entry-chapter"/>
    <xsl:template match="text:index-entry-page-number"/>
    <xsl:template match="text:index-entry-tab-stop"/>
    <xsl:template match="text:index-entry-text"/>
    <xsl:template match="text:index-title-template"/>
    <xsl:template match="text:table-of-content-entry-template"/>
    <xsl:template match="text:table-of-content-source"/>

</xsl:stylesheet>