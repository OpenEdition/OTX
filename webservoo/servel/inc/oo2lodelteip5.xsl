<?xml version="1.0" encoding="UTF-8"?>
<!--
 #  The Contents of this file are made available subject to the terms of
 # the GNU Lesser General Public License Version 2.1

 # Sebastian Rahtz / University of Oxford
 # copyright 2005

 # This stylesheet is derived from the OpenOffice to Docbook conversion
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
 #
-->
<!--
    oototeip5.lodel.xsl
    @author Nicolas Barts
    @copyright 2008-2009, CLEO/Revues.org
    @licence http://www.gnu.org/copyleft/gpl.html
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
        name="headchildren"
        match=" text:p | text:alphabetical-index | table:table | text:span
            | office:annotation | text:ordered-list | text:list |
            text:note | text:a | text:list-item | draw:plugin |
            draw:text-box | text:note-body | text:section" 
        use="generate-id(
            preceding-sibling::text:h[@text:outline-level][1])"/>

    <xsl:key
        name="onlychildren"
        match=" text:p | text:alphabetical-index | table:table | text:span
            | office:annotation | text:ordered-list | text:list |
            text:note | text:a | text:list-item | draw:plugin |
            draw:text-box | text:note-body | text:section" 
        use="generate-id(parent::office:text)"/>

    <!-- did have ..| -->

    <xsl:key match="text:h[@text:outline-level='2']" name="children1"
        use="generate-id(preceding-sibling::text:h[@text:outline-level='1'][1])"/>

    <xsl:key match="text:h[@text:outline-level='3']" name="children2"
        use="generate-id(preceding-sibling::text:h[@text:outline-level='2' 
            or @text:outline-level='1'][1])"/>

    <xsl:key match="text:h[@text:outline-level='4']" name="children3"
        use="generate-id(preceding-sibling::text:h[@text:outline-level='3' 
            or @text:outline-level='2' 
            or @text:outline-level='1'][1])"/>

    <xsl:key match="text:h[@text:outline-level='5']" name="children4"
        use="generate-id(preceding-sibling::text:h[@text:outline-level='4' 
            or @text:outline-level='3' 
            or @text:outline-level='2' 
            or @text:outline-level='1'][1])"/>

    <xsl:key match="text:h[@text:outline-level='6']" name="children5"
        use="generate-id(preceding-sibling::text:h[@text:outline-level='5'
            or @text:outline-level='4' 
            or @text:outline-level='3' 
            or @text:outline-level='2' 
            or @text:outline-level='1'][1])"/>

    <xsl:key match="text:h[@text:outline-level='7']" name="children6"
        use="generate-id(preceding-sibling::text:h[@text:outline-level='6'
            or @text:outline-level='5'
            or @text:outline-level='4' 
            or @text:outline-level='3' 
            or @text:outline-level='2' 
            or @text:outline-level='1'][1])"/>

    <xsl:key match="text:h[@text:outline-level='8']" name="children7"
        use="generate-id(preceding-sibling::text:h[@text:outline-level='7'
            or @text:outline-level='5'
            or @text:outline-level='6'
            or @text:outline-level='4' 
            or @text:outline-level='3' 
            or @text:outline-level='2' 
            or @text:outline-level='1'][1])"/>

    <xsl:key match="text:h[@text:outline-level='9']" name="children8"
        use="generate-id(preceding-sibling::text:h[@text:outline-level='8'
            or @text:outline-level='7'
            or @text:outline-level='6'
            or @text:outline-level='5'
            or @text:outline-level='4' 
            or @text:outline-level='3' 
            or @text:outline-level='2' 
            or @text:outline-level='1'][1])"/>

    <xsl:key match="text:h[@text:outline-level='10']" name="children9"
        use="generate-id(preceding-sibling::text:h[@text:outline-level='9'
            or @text:outline-level='8'
            or @text:outline-level='7'
            or @text:outline-level='6'
            or @text:outline-level='5'
            or @text:outline-level='4' 
            or @text:outline-level='3' 
            or @text:outline-level='2' 
            or @text:outline-level='1'][1])"/>

    <xsl:key match="text:h[@text:outline-level='11']" name="children10"
        use="generate-id(preceding-sibling::text:h[@text:outline-level='10'
            or @text:outline-level='9'
            or @text:outline-level='8'
            or @text:outline-level='7'
            or @text:outline-level='6'
            or @text:outline-level='5'
            or @text:outline-level='4' 
            or @text:outline-level='3' 
            or @text:outline-level='2' 
            or @text:outline-level='1'][1])"/>

    <xsl:key match="text:p[@text:style-name='Index 2']" name="secondary_children"
        use="generate-id(preceding-sibling::text:p[@text:style-name='Index 1'][1])"/>

    <xsl:key match="style:style" name="STYLES" use="@style:name"/>

    <xsl:key match="text:h" name="Headings" use="text:outline-level"/>

    <xsl:param name="META" select="/"/>

<xsl:output encoding="utf-8" indent="yes"/>
<!--
<xsl:strip-space elements="text:span"/>
<xsl:preserve-space elements="*"/>
-->



  <xsl:template match="text:variable-set|text:variable-get">
    <xsl:choose>
      <xsl:when test="contains(@text:style-name,'entitydecl')">
        <xsl:text disable-output-escaping="yes">&amp;</xsl:text>
        <xsl:value-of select="substring-after(@text:style-name,'entitydecl_')"/>
        <xsl:text disable-output-escaping="yes">;</xsl:text>
      </xsl:when>
    </xsl:choose>
  </xsl:template>



<!--
    office:document
-->
<xsl:template match="/office:document">
    <xsl:for-each select="descendant::text:variable-decl">
    <xsl:variable name="name">
        <xsl:value-of select="@text:name"/>
    </xsl:variable>
    <xsl:if test="contains(@text:name,'entitydecl')">
        <xsl:text disable-output-escaping="yes">&lt;!DOCTYPE TEI [</xsl:text>
        <xsl:text disable-output-escaping="yes">&lt;!ENTITY </xsl:text>
        <xsl:value-of select="substring-after(@text:name,'entitydecl_')"/>
        <xsl:text> &quot;</xsl:text>
        <xsl:value-of select="/descendant::text:variable-set[@text:name= $name][1]"/>
        <xsl:text disable-output-escaping="yes">&quot;&gt;</xsl:text>
        <xsl:text disable-output-escaping="yes">]&gt;</xsl:text>
    </xsl:if>
    </xsl:for-each>

    <TEI xsi:schemaLocation="http://www.tei-c.org/ns/1.0 http://www.tei-c.org/release/xml/tei/custom/schema/xsd/tei_all.xsd">
        <xsl:call-template name="teiHeader"/>
        <xsl:apply-templates/>
    </TEI>
</xsl:template>



<!-- 
    teiHeader
-->
    <xsl:template match="text:*[@text:style-name]" mode="header">
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="office:body/office:text/text:*[@text:style-name='author']" mode="author">
        <author>
            <name>
                <xsl:value-of select="."/>
            </name>
            <xsl:if test="following-sibling::text:*[@text:style-name='author-description' and position()=1]">
            <affiliation>
                <xsl:apply-templates select="following-sibling::text:*[@text:style-name='author-description' and position()=1]" mode="header"/>
            </affiliation>
            </xsl:if>
        </author>
    </xsl:template>

    <xsl:template match="office:body/office:text/text:*[@text:style-name='scientificeditor']" mode="author">
        <editor role="scientificeditor">
            <name>
                <xsl:value-of select="."/>
            </name>
            <xsl:if test="following-sibling::text:*[@text:style-name='author-description' and position()=1]">
            <affiliation>
                <xsl:apply-templates select="following-sibling::text:*[@text:style-name='author-description' and position()=1]" mode="header"/>
            </affiliation>
            </xsl:if>
        </editor>
    </xsl:template>

    <xsl:template match="office:body/office:text/text:*[@text:style-name='translator']" mode="author">
        <editor role="translator">
            <name>
                <xsl:value-of select="."/>
            </name>
            <xsl:if test="following-sibling::text:*[@text:style-name='author-description' and position()=1]">
            <affiliation>
                <xsl:apply-templates select="following-sibling::text:*[@text:style-name='author-description' and position()=1]" mode="header"/>
            </affiliation>
            </xsl:if>
        </editor>
    </xsl:template>


<xsl:template name="teiHeader">
<teiHeader>
    <fileDesc>
        <titleStmt>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='uptitle']">
            <title type="top">
                <xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='uptitle']" mode="header"/>
            </title>
            </xsl:if>
            <title>
                <xsl:apply-templates select="office:body/office:text/text:*[@text:style-name='title']" mode="header"/>
            </title>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='subtitle']">
            <title type="sub">
                <xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='subtitle']" mode="header" />
            </title>
            </xsl:if>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='altertitle-en']">
            <title type="alt" xml:lang="en">
                <xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='altertitle-en']" mode="header"/>
            </title>
            </xsl:if>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='altertitle-de']">
            <title type="alt" xml:lang="de">
                <xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='altertitle-de']" mode="header"/>
            </title>
            </xsl:if>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='altertitle-es']">
            <title type="alt" xml:lang="es">
                <xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='altertitle-es']" mode="header"/>
            </title>
            </xsl:if>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='altertitle-it']">
            <title type="alt" xml:lang="it">
                <xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='altertitle-it']" mode="header"/>
            </title>
            </xsl:if>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='altertitle-fr']">
            <title type="alt" xml:lang="fr">
                <xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='altertitle-fr']" mode="header"/>
            </title>
            </xsl:if>
            <xsl:apply-templates select="office:body/office:text/text:*[@text:style-name='author']" mode="author"/>
            <xsl:apply-templates select="office:body/office:text/text:*[@text:style-name='translator']" mode="author"/>
            <xsl:apply-templates select="office:body/office:text/text:*[@text:style-name='scientificeditor']" mode="author"/>
        </titleStmt>
        <publicationStmt>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='editor']">
            <publisher>
                <xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='editor']"/>
            </publisher>
            </xsl:if>
            <date>
                <xsl:attribute name="when">
		  <xsl:value-of select="/office:document/office:meta/dc:date"/>
		</xsl:attribute>
                <xsl:if test="/office:document/office:body/office:text/text:p[@text:style-name='date']">
                    <xsl:apply-templates select="office:body/office:text/text:p[@text:style-name='date']" mode="author"/>
                </xsl:if>
            </date>
            <xsl:if test="/office:document/office:body/office:text/text:p[@text:style-name='license']">
                <availability status="free">
                    <p><xsl:apply-templates select="/office:document/office:body/office:text/text:p[@text:style-name='license']" mode="header"/></p>
                </availability>
            </xsl:if>
            <idno type="url"></idno>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='documentnumber']">
            <idno type="document">
                <xsl:value-of select="/office:document/office:body/office:text/text:*[@text:style-name='documentnumber']"/>
            </idno>
            </xsl:if>
            <idno type="DOI"/>
            <idno type="EISSN"/>
            <authority>Cléo / Revues.org</authority>
        </publicationStmt>
        <seriesStmt>
            <title type="issue"/>
            <xsl:if test="/office:document/office:meta/meta:creation-date">
            <idno type="creationdate">
                <xsl:value-of select="/office:document/office:meta/meta:creation-date"/>
            </idno>
            </xsl:if>
            <idno type="vol"/>
            <idno type="issue"/>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='pagenumber']">
            <idno type="pp">
                <xsl:value-of select="/office:document/office:body/office:text/text:*[@text:style-name='pagenumber']"/>
            </idno>
            </xsl:if>
            <idno type="ISBN"/>
            <idno type="ISSN"/>
        </seriesStmt>
        <notesStmt>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='bibliographicreference']">
            <note type="bibl">
                <xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='bibliographicreference']" mode="header"/>
            </note>
            </xsl:if>
        </notesStmt>
        <sourceDesc>
            <biblStruct>
                <analytic>
                    <title>
                        <xsl:value-of select="office:body/office:text/text:*[@text:style-name='title']"/>
                    </title>
                    <xsl:for-each select="office:body/office:text/text:*[@text:style-name='author']">
                    <author>
                        <name>
                            <xsl:value-of select="."/>
                        </name>
                    </author>
                    </xsl:for-each>
                    <xsl:for-each select="office:body/office:text/text:*[@text:style-name='scientificeditor']">
                    <editor role="scientificeditor">
                        <name>
                            <xsl:value-of select="."/>
                        </name>
                    </editor>
                    </xsl:for-each>
                    <xsl:for-each select="office:body/office:text/text:*[@text:style-name='translator']">
                    <editor role="translator">
                        <name>
                            <xsl:value-of select="."/>
                        </name>
                    </editor>
                    </xsl:for-each>
                </analytic>
                <monogr>
                    <imprint>
                        <xsl:if test="/office:document/office:meta/meta:creation-date">
                        <date>
                            <xsl:attribute name="when">
                                <xsl:value-of select="/office:document/office:meta/meta:creation-date"/>
                            </xsl:attribute>
                            <xsl:if test="/office:document/office:body/office:text/text:p[@text:style-name='creationdate']">
                                <xsl:apply-templates select="office:body/office:text/text:p[@text:style-name='creationdate']" mode="author"/>
                            </xsl:if>
                        </date>
                        </xsl:if>
                        <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='pagenumber']">
                        <biblScope type="pp">
                            <xsl:value-of select="/office:document/office:body/office:text/text:*[@text:style-name='pagenumber']"/>
                        </biblScope>
                        </xsl:if>
                        <publisher/>
                    </imprint>
                </monogr>
                <series>
                    <title/>
                    <biblScope type="ISSN"/>
                </series>
            </biblStruct>
        </sourceDesc>
    </fileDesc>
    <encodingDesc>
        <appInfo>
            <application version="1.0" ident="OTX">
                <label>OpenText XML convertion server - Cleo / Revues.org</label>
                <desc>
                    <ref target="http://www.tei-c.org/"><figure><head>Powered by TEI</head><figDesc>A button indicating that this project is powered by the TEI Guidelines.</figDesc><graphic url="http://www.tei-c.org/About/Badges/powered-by-TEI.png" /></figure></ref>
                </desc>
                <ptr target="otx.revues.org"/>
            </application>
        </appInfo>
        <tagsDecl>
<!--
            <rendition xml:id="style1" scheme="css">... description of one default rendition here ...</rendition>
            <rendition xml:id="style2" scheme="css">... description of another default rendition here ...</rendition>
-->
        </tagsDecl>
    </encodingDesc>
    <profileDesc>
        <langUsage>
            <language>
                <xsl:attribute name="ident">
                    <xsl:value-of select="/office:document/office:meta/dc:language"/>
                </xsl:attribute>
                <xsl:value-of select="/office:document/office:meta/dc:language"/>
            </language>
        </langUsage>
        <textClass>
            <xsl:if test="/office:document/office:meta/meta:user-defined[@meta:name='keywords-fr']">
            <keywords xml:lang="fr" scheme="keyword">
                <list>
                <xsl:for-each select="/office:document/office:meta/meta:user-defined[@meta:name='keywords-fr']">
                    <item>
                        <xsl:apply-templates select="." mode="header"/>
                    </item>
                </xsl:for-each>
                </list>
            </keywords>
            </xsl:if>
            <xsl:if test="/office:document/office:meta/meta:user-defined[@meta:name='keywords-en']">
            <keywords xml:lang="en" scheme="keyword">
                <list>
                <xsl:for-each select="/office:document/office:meta/meta:user-defined[@meta:name='keywords-en']">
                    <item>
                        <xsl:apply-templates select="." mode="header"/>
                    </item>
                </xsl:for-each>
                </list>
            </keywords>
            </xsl:if>
            <xsl:if test="/office:document/office:meta/meta:user-defined[@meta:name='keywords-es']">
            <keywords xml:lang="es" scheme="keyword">
                <list>
                <xsl:for-each select="/office:document/office:meta/meta:user-defined[@meta:name='keywords-es']">
                    <item>
                        <xsl:apply-templates select="." mode="header"/>
                    </item>
                </xsl:for-each>
                </list>
            </keywords>
            </xsl:if>
            <xsl:if test="/office:document/office:meta/meta:user-defined[@meta:name='keywords-de']">
            <keywords xml:lang="de" scheme="keyword">
                <list>
                <xsl:for-each select="/office:document/office:meta/meta:user-defined[@meta:name='keywords-de']">
                    <item>
                        <xsl:apply-templates select="." mode="header"/>
                    </item>
                </xsl:for-each>
                </list>
            </keywords>
            </xsl:if>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='subject']">
            <keywords scheme="subject">
                <list>
                <xsl:for-each select="/office:document/office:body/office:text/text:*[@text:style-name='subject']">
                    <item>
                        <xsl:apply-templates select="." mode="header"/>
                    </item>
                </xsl:for-each>
                </list>
            </keywords>
            </xsl:if>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='chronological']">
            <keywords scheme="chronological">
                <list>
                <xsl:for-each select="/office:document/office:body/office:text/text:*[@text:style-name='chronological']">
                    <item>
                        <xsl:apply-templates select="." mode="header"/>
                    </item>
                </xsl:for-each>
                </list>
            </keywords>
            </xsl:if>
            <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='geographical']">
            <keywords scheme="geographical">
                <list>
                <xsl:for-each select="/office:document/office:body/office:text/text:*[@text:style-name='geographical']">
                    <item>
                        <xsl:apply-templates select="." mode="header"/>
                    </item>
                </xsl:for-each>
                </list>
            </keywords>
            </xsl:if>
        </textClass>
    </profileDesc>
    <revisionDesc>
        <change/>
    </revisionDesc>
</teiHeader>
</xsl:template>


<!-- 
    teiFront
    contient tout texte liminaire (en-tête, page de titre, préfaces, dédicaces, etc.) se trouvant avant le début du texte proprement dit 
-->
<xsl:template name="teiFront">
<front>
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='abstract-fr']">
    <div type="abstract" xml:lang="fr">
        <xsl:for-each select="/office:document/office:body/office:text/text:*[@text:style-name='abstract-fr']">
        <p><xsl:apply-templates/></p>
        </xsl:for-each>
    </div>
    </xsl:if>
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='abstract-en']">
    <div type="abstract" xml:lang="en">
        <xsl:for-each select="/office:document/office:body/office:text/text:*[@text:style-name='abstract-en']">
        <p><xsl:apply-templates/></p>
        </xsl:for-each>
    </div>
    </xsl:if>
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='abstract-es']">
    <div type="abstract" xml:lang="es">
        <xsl:for-each select="/office:document/office:body/office:text/text:*[@text:style-name='abstract-es']">
        <p><xsl:apply-templates/></p>
        </xsl:for-each>
    </div>
    </xsl:if>
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='abstract-it']">
    <div type="abstract" xml:lang="it">
        <xsl:for-each select="/office:document/office:body/office:text/text:*[@text:style-name='abstract-it']">
        <p><xsl:apply-templates/></p>
        </xsl:for-each>
    </div>
    </xsl:if>
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='abstract-de']">
    <div type="abstract" xml:lang="de">
        <xsl:for-each select="/office:document/office:body/office:text/text:*[@text:style-name='abstract-de']">
        <p><xsl:apply-templates/></p>
        </xsl:for-each>
    </div>
    </xsl:if>
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='acknowledgment']">
    <div type="ack">
        <p><xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='acknowledgment']" mode="header"/></p>
    </div>
    </xsl:if>
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='dedication']">
    <div type="dedication">
        <p><xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='dedication']" mode="header"/></p>
    </div>
    </xsl:if>
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='correction']">
    <div type="correction">
        <p><xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='correction']" mode="header"/></p>
    </div>
    </xsl:if>
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='editornote']">
    <note resp="editor">
        <p><xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='editornote']" mode="header"/></p>
    </note>
    </xsl:if>
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='authornote']">
    <note resp="author">
        <p><xsl:apply-templates select="/office:document/office:body/office:text/text:*[@text:style-name='authornote']" mode="header"/></p>
    </note>
    </xsl:if>
    <!--lodel oeuvre commentee -->
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='review-title']">
    <div type="review-title">
        <xsl:for-each select="/office:document/office:body/office:text/text:*[@text:style-name='review-title']">
        <p><xsl:apply-templates/></p>
        </xsl:for-each>
    </div>
    </xsl:if>
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='review-author']">
    <div type="review-author">
        <xsl:for-each select="/office:document/office:body/office:text/text:*[@text:style-name='review-author']">
        <p><xsl:apply-templates/></p>
        </xsl:for-each>
    </div>
    </xsl:if>
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='review-date']">
    <div type="review-date">
        <xsl:for-each select="/office:document/office:body/office:text/text:*[@text:style-name='review-date']">
        <p><xsl:apply-templates/></p>
        </xsl:for-each>
    </div>
    </xsl:if>
    <xsl:if test="/office:document/office:body/office:text/text:*[@text:style-name='review-bibliography']">
    <div type="review-bibliography">
        <xsl:for-each select="/office:document/office:body/office:text/text:*[@text:style-name='review-bibliography']">
        <p><xsl:apply-templates/></p>
        </xsl:for-each>
    </div>
    </xsl:if>
</front>
</xsl:template>


<!--
    teiBack
-->
    <xsl:template match="text:*[starts-with(@text:style-name,'bibliography')]" mode="back">
        <xsl:choose>
            <xsl:when test="@text:style-name='bibliography'">
                <bibl><xsl:apply-templates/></bibl>
            </xsl:when>
            <xsl:when test="starts-with(@text:style-name, 'bibliography-head')">
                <xsl:variable name="headX">
                    <xsl:value-of select="concat('heading', substring-after(@text:style-name, 'bibliography-head'))"/>
                </xsl:variable>
                <head>
                        <xsl:attribute name="rend">
                            <xsl:value-of select="$headX"/>
                        </xsl:attribute>
                        <xsl:apply-templates/>
                </head>
            </xsl:when>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="text:*[starts-with(@text:style-name,'appendix')]" mode="back">
        <xsl:choose>
            <xsl:when test="@text:style-name='appendix'">
                <item><xsl:apply-templates/></item>
            </xsl:when>
            <xsl:when test="starts-with(@text:style-name, 'appendix-head')">
                <xsl:variable name="headX">
                    <xsl:value-of select="concat('heading', substring-after(@text:style-name, 'appendix-head'))"/>
                </xsl:variable>
                <head>
                        <xsl:attribute name="rend">
                            <xsl:value-of select="$headX"/>
                        </xsl:attribute>
                        <xsl:apply-templates/>
                </head>
            </xsl:when>
        </xsl:choose>
    </xsl:template>

<xsl:template name="teiBack">
<back>
    <xsl:if test="/office:document/office:body/office:text//text:*[starts-with(@text:style-name,'appendix')]">
    <div type="appendix">
        <list>
            <xsl:apply-templates select="/office:document/office:body/office:text//text:*[starts-with(@text:style-name,'appendix')]" mode="back"/>
        </list>
    </div>
    </xsl:if>
    <xsl:if test="/office:document/office:body/office:text//text:*[starts-with(@text:style-name,'bibliography')]">
    <div type="bibliogr">
        <listBibl>
            <xsl:apply-templates select="/office:document/office:body/office:text//text:*[starts-with(@text:style-name,'bibliography')]" mode="back"/>
        </listBibl>
    </div>
    </xsl:if>
</back>
</xsl:template>



<!-- 
    office:body 
-->
<xsl:template match="/office:document/office:body">
    <text>
        <xsl:call-template name="teiFront"/>
        <xsl:apply-templates/>
        <xsl:call-template name="teiBack"/>
    </text>
</xsl:template>


<!-- office:text -->
<xsl:template match="office:text">
    <body>
        <!--
        <xsl:choose>
            <xsl:when test="text:h">
            <xsl:call-template name="aSection"/>
            </xsl:when>
            <xsl:otherwise>
            <xsl:apply-templates/>
            </xsl:otherwise>
        </xsl:choose>
        -->
        <xsl:apply-templates/>
    </body>
</xsl:template>


<!--
    <xsl:template name="aSection">
      <xsl:apply-templates select="key('headchildren',
				   generate-id())"/>
      <xsl:choose>
        <xsl:when test="text:h[@text:outline-level='1']">
          <xsl:apply-templates select="text:h[@text:outline-level='1']"/>
        </xsl:when>
        <xsl:when test="text:h[@text:outline-level='2']">
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
          <xsl:apply-templates select="text:h[@text:outline-level='2']"/>
        </xsl:when>
        <xsl:when test="text:h[@text:outline-level='3']">
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
          <xsl:apply-templates select="text:h[@text:outline-level='3']"/>
        </xsl:when>
        <xsl:when test="text:h[@text:outline-level='4']">
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
          <xsl:apply-templates select="text:h[@text:outline-level='4']"/>
        </xsl:when>
        <xsl:when test="text:h[@text:outline-level='5']">
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
          <xsl:apply-templates select="text:h[@text:outline-level='5']"/>
        </xsl:when>
        <xsl:when test="text:h[@text:outline-level='6']">
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
          <xsl:apply-templates select="text:h[@text:outline-level='6']"/>
        </xsl:when>
        <xsl:when test="text:h[@text:outline-level='7']">
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
	  <xsl:text disable-output-escaping="yes">&lt;div&gt;</xsl:text>
          <xsl:apply-templates select="text:h[@text:outline-level='7']"/>
        </xsl:when>
      </xsl:choose>

      <xsl:call-template name="closedivloop">
        <xsl:with-param name="start">
	  <xsl:value-of
	      select="text:h[@text:outline-level][last()]/@text:outline-level"/>
	</xsl:with-param>
        <xsl:with-param name="repeat">
	  <xsl:value-of
	      select="text:h[@text:outline-level][last()]/@text:outline-level"/>
	</xsl:with-param>
      </xsl:call-template>
    </xsl:template>
-->

<!-- sections 
  <xsl:template match="text:h">
    <xsl:choose>
      <xsl:when test="@text:style-name='ArticleInfo'"> </xsl:when>
      <xsl:when test="@text:style-name='Abstract'">
        <div type="abstract">
          <xsl:apply-templates/>
        </div>
      </xsl:when>
      <xsl:when test="@text:style-name='Appendix'">
        <div type="appendix">
          <xsl:apply-templates/>
        </div>
      </xsl:when>
      <xsl:otherwise>
        <xsl:variable name="sectvar">
          <xsl:text>div</xsl:text>
        </xsl:variable>
        <xsl:variable name="idvar">
          <xsl:text> id=&quot;</xsl:text>
          <xsl:value-of select="@text:style-name"/>
          <xsl:text>&quot;</xsl:text>
        </xsl:variable>
        <xsl:text disable-output-escaping="yes">&lt;</xsl:text>
        <xsl:value-of select="$sectvar"/>
        <xsl:value-of select="$idvar"/>
        <xsl:text disable-output-escaping="yes">&gt;</xsl:text>
        <xsl:apply-templates/>
        <xsl:text disable-output-escaping="yes">&lt;/</xsl:text>
        <xsl:value-of select="$sectvar"/>
        <xsl:text disable-output-escaping="yes">&gt;</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template match="text:h[@text:outline-level='1']">
    <xsl:choose>
      <xsl:when test=".='Abstract'">
        <div type="abstract">
          <xsl:apply-templates select="key('headchildren', generate-id())"/>
          <xsl:apply-templates select="key('children1', generate-id())"/>
        </div>
      </xsl:when>
      <xsl:otherwise>
	<xsl:variable name="level">
	  <xsl:value-of select="@text:outline-level"/>
	</xsl:variable>
	<xsl:choose>
	<xsl:when test="preceding-sibling::text:h">
	<xsl:variable name="prelevel">
	  <xsl:value-of select="preceding-sibling::text:h[1]/@text:outline-level "/>
	</xsl:variable>
	  <xsl:call-template name="closedivloop">
	    <xsl:with-param name="start">
	      <xsl:value-of select="$prelevel"/>
	    </xsl:with-param>
	    <xsl:with-param name="repeat" select="$prelevel - $level + 1"/>
	  </xsl:call-template>
	</xsl:when>
	<xsl:when
	    test="parent::text:list-item/parent::text:list/preceding-sibling::text:h">
	<xsl:variable name="prelevel">
	  <xsl:value-of select="parent::text:list-item/parent::text:list/preceding-sibling::text:h[1]/@text:outline-level "/>
	</xsl:variable>
	  <xsl:call-template name="closedivloop">
	    <xsl:with-param name="start">
	      <xsl:value-of select="$prelevel"/>
	    </xsl:with-param>
	    <xsl:with-param name="repeat" select="$prelevel - $level + 1"/>
	  </xsl:call-template>
	</xsl:when>
	</xsl:choose>
        <xsl:call-template name="make-section">
          <xsl:with-param name="current">
	    <xsl:value-of select="@text:outline-level"/>
	  </xsl:with-param>
          <xsl:with-param name="prev">
	    <xsl:value-of select="preceding-sibling::text:h[1]/@text:outline-level "/>
	  </xsl:with-param>
	</xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template
    match="text:h[@text:outline-level='2'] |
	   text:h[@text:outline-level='3'] | 
	   text:h[@text:outline-level='4'] | 
	   text:h[@text:outline-level='5'] | 
	   text:h[@text:outline-level='6'] | 
	   text:h[@text:outline-level='7'] | 
	   text:h[@text:outline-level='8'] | 
	   text:h[@text:outline-level='9'] | 
	   text:h[@text:outline-level='10']">
    <xsl:variable name="level">
      <xsl:value-of select="@text:outline-level"/>
    </xsl:variable>
    <xsl:variable name="prelevel">
      <xsl:value-of select="preceding::text:h[1]/@text:outline-level "/>
    </xsl:variable>
    <xsl:if test="not($level &gt; $prelevel)">
      <xsl:call-template name="closedivloop">
        <xsl:with-param name="start">
	  <xsl:value-of select="$prelevel"/>
	</xsl:with-param>
        <xsl:with-param name="repeat" select="$prelevel - $level + 1"/>
      </xsl:call-template>
    </xsl:if>
    <xsl:if test="not(normalize-space(.)='')">
      <xsl:call-template name="make-section">
        <xsl:with-param name="current">
	  <xsl:value-of select="$level"/>
	</xsl:with-param>
        <xsl:with-param name="prev">
	  <xsl:value-of
          select="preceding-sibling::text:h[@text:outline-level &lt;
		  $level][1]/@text:outline-level "        />
	</xsl:with-param>
      </xsl:call-template>
    </xsl:if>
  </xsl:template>


  <xsl:template name="closedivloop">
    <xsl:param name="repeat"/>
    <xsl:param name="start"/>
    <xsl:if test="$repeat >= 1">
      <xsl:text disable-output-escaping="yes">&lt;/div</xsl:text>
      <xsl:text disable-output-escaping="yes">&gt;</xsl:text>
      <xsl:call-template name="closedivloop">
        <xsl:with-param name="start">
	  <xsl:value-of select="$start - 1"/>
	</xsl:with-param>
        <xsl:with-param name="repeat">
	  <xsl:value-of select="$repeat - 1"/>
	</xsl:with-param>
      </xsl:call-template>
    </xsl:if>
  </xsl:template>



  <xsl:template name="make-section">
    <xsl:param name="current"/>
    <xsl:param name="prev"/>
    <xsl:text disable-output-escaping="yes">&lt;div</xsl:text>
    <xsl:text> type=&quot;div</xsl:text>
    <xsl:value-of select="$current"/>
    <xsl:text>&quot;</xsl:text>
    <xsl:call-template name="id.attribute.literal"/>
    <xsl:text disable-output-escaping="yes">&gt;</xsl:text>

    <xsl:choose>
      <xsl:when test="$current &gt; $prev+1">
        <head/>
        <xsl:call-template name="make-section">
          <xsl:with-param name="current" select="$current"/>
          <xsl:with-param name="prev" select="$prev+1"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <head>
          <xsl:apply-templates/>
        </head>
	    <xsl:variable name="this">
	      <xsl:value-of select="generate-id()"/>
	    </xsl:variable>
	    
	    <xsl:for-each select="key('headchildren', $this)">
	      <xsl:if test="not(parent::text:h)">
		<xsl:apply-templates select="."/>
	      </xsl:if>
	    </xsl:for-each>

	    <xsl:choose>
	      <xsl:when test="$current=1">
		<xsl:apply-templates select="key('children1',
					     generate-id())"/>
	      </xsl:when>
	      <xsl:when test="$current=2">
		<xsl:apply-templates select="key('children2',
					     generate-id())"/>
	      </xsl:when>
	      <xsl:when test="$current=3">
		<xsl:apply-templates select="key('children3',
					     generate-id())"/>
	      </xsl:when>
	      <xsl:when test="$current=4">
		<xsl:apply-templates select="key('children4',
					     generate-id())"/>
	      </xsl:when>
	      <xsl:when test="$current=5">
		<xsl:apply-templates select="key('children5',
					     generate-id())"/>
	      </xsl:when>
	      <xsl:when test="$current=6">
	    <xsl:apply-templates select="key('children6',
					 generate-id())"/>
	  </xsl:when>
	  <xsl:when test="$current=7">
	    <xsl:apply-templates select="key('children7',
					 generate-id())"/>
	  </xsl:when>
	  <xsl:when test="$current=8">
	    <xsl:apply-templates select="key('children8',
					 generate-id())"/>
	  </xsl:when>
	  <xsl:when test="$current=9">
	    <xsl:apply-templates select="key('children9',
					 generate-id())"/>
	  </xsl:when>
	  <xsl:when test="$current=10">
	    <xsl:apply-templates select="key('children10',
					 generate-id())"/>
	  </xsl:when>
	</xsl:choose>
      </xsl:otherwise>
    </xsl:choose>

  </xsl:template>
-->



    <xsl:template match="text:h[@text:outline-level]">
        <xsl:if test="not(starts-with(@text:style-name, 'bibliography-'))">
        <p>
            <xsl:attribute name="rend">
                <xsl:value-of select="concat('heading',@text:outline-level)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
        </p>
        </xsl:if>
    </xsl:template>

<!--
    LODEL Styles
-->
<xsl:template name="LODEL_styles">
    <xsl:param name="pStyle"></xsl:param>
    <xsl:choose>
        <!-- Lodel ME styles -->
        <xsl:when test="$pStyle='annexe'"/>
        <xsl:when test="starts-with($pStyle,'appendix')"/>
        <xsl:when test="starts-with($pStyle,'bibliography')"/>

        <xsl:when test="$pStyle='title'"/>
        <xsl:when test="$pStyle='author'"/>
        <xsl:when test="$pStyle='abstract-fr'"/>
        <xsl:when test="$pStyle='abstract-en'"/>
        <xsl:when test="$pStyle='abstract-es'"/>
        <xsl:when test="$pStyle='abstract-it'"/>
        <xsl:when test="$pStyle='abstract-de'"/>
        <xsl:when test="$pStyle='keywords-fr'"/>
        <xsl:when test="$pStyle='keywords-en'"/>
        <xsl:when test="$pStyle='keywords-es'"/>
        <xsl:when test="$pStyle='keywords-de'"/>
        <xsl:when test="$pStyle='subject'"/>
        <xsl:when test="$pStyle='chronological'"/>
        <xsl:when test="$pStyle='geographical'"/>
        <xsl:when test="$pStyle='bibliographicreference'"/>
        <xsl:when test="$pStyle='language'"/>
        <xsl:when test="$pStyle='license'"/>
        <xsl:when test="$pStyle='creationdate'"/>
        <xsl:when test="$pStyle='date'"/>
        <xsl:when test="$pStyle='uptitle'"/>
        <xsl:when test="$pStyle='subtitle'"/>
        <xsl:when test="$pStyle='altertitle-en'"/>
        <xsl:when test="$pStyle='altertitle-es'"/>
        <xsl:when test="$pStyle='altertitle-it'"/>
        <xsl:when test="$pStyle='altertitle-de'"/>
        <xsl:when test="$pStyle='altertitle-fr'"/>
        <xsl:when test="$pStyle='author-description'"/>
        <xsl:when test="$pStyle='author-affiliation'"/>
        <xsl:when test="$pStyle='author-role'"/>
        <xsl:when test="$pStyle='author-email'"/>
        <xsl:when test="$pStyle='author-site'"/>
        <xsl:when test="$pStyle='prefix'"/>
        <xsl:when test="$pStyle='translator'"/>
        <xsl:when test="$pStyle='scientificeditor'"/>
        <xsl:when test="$pStyle='pagenumber'"/>
        <xsl:when test="$pStyle='documentnumber'"/> 
        <xsl:when test="$pStyle='correction'"/>
        <xsl:when test="$pStyle='editornote'"/>
        <xsl:when test="$pStyle='authornote'"/>
        <xsl:when test="$pStyle='acknowledgment'"/>
        <xsl:when test="$pStyle='dedication'"/>
        <xsl:when test="$pStyle='review-title'"/>
        <xsl:when test="$pStyle='review-author'"/>
        <xsl:when test="$pStyle='review-date'"/>
        <xsl:when test="$pStyle='review-bibliography'"/>

        <xsl:when test="$pStyle='footnote'"><xsl:apply-templates/></xsl:when>
	<!-- old -->
        <xsl:when test="$pStyle='type'"/>
        <!-- lodel Styles internes -->
	<xsl:when test="starts-with($pStyle, 'heading') and not(starts-with($pStyle, 'bibliography')) and boolean( number( substring-after($pStyle, 'heading')))">
	    <xsl:variable name="headX">
		<xsl:value-of select="concat('heading', substring-after($pStyle, 'heading'))"/>
	    </xsl:variable>
	    <p>
		    <xsl:attribute name="rend">
		        <xsl:value-of select="$headX"/>
		    </xsl:attribute>
		    <xsl:attribute name="rendition">
		        <xsl:value-of select="concat('#', $headX)"/>
		    </xsl:attribute>
		    <xsl:apply-templates/>
	    </p>
	</xsl:when>
        <!-- citation -->
        <xsl:when test="$pStyle='quotation'">
            <p rend="quotation" rendition="#quotation"><xsl:apply-templates/></p>
        </xsl:when>
        <!-- quotations -->
        <xsl:when test="$pStyle='reference'">
            <p rend="reference" rendition="#reference"><xsl:apply-templates/></p>
        </xsl:when>
        <!-- citationbis -->
        <xsl:when test="$pStyle='quotation2'">
            <p rend="quotation2" rendition="#quotation2"><xsl:apply-templates/></p>
        </xsl:when>
        <!-- citationter -->
        <xsl:when test="$pStyle='quotation3'" rendition="#quotation3">
            <p rend="quotation3"><xsl:apply-templates/></p>
        </xsl:when>
        <!-- titreillustration -->
        <xsl:when test="$pStyle='figure-title'">
            <p rend="figure-title"><xsl:apply-templates/></p>
        </xsl:when>
        <!-- legendeillustration -->
        <xsl:when test="$pStyle='figure-legend'">
            <p rend="figure-legend"><xsl:apply-templates/></p>
        </xsl:when>
        <xsl:when test="$pStyle='figure-license'">
            <p rend="figure-license"><xsl:apply-templates/></p>
        </xsl:when>
        <!-- titredoc -->
        <!-- legendedoc -->
        <!-- puces -->
        <xsl:when test="$pStyle='item'">
            <p rend="item"><xsl:apply-templates/></p>
        </xsl:when>
        <!-- code -->
        <xsl:when test="$pStyle='code'">
            <p rend="code"><xsl:apply-templates/></p>
        </xsl:when>
        <!-- question -->
        <xsl:when test="$pStyle='question'">
            <p rend="question"><xsl:apply-templates/></p>
        </xsl:when>
        <!-- reponse -->
        <xsl:when test="$pStyle='answer'">
            <p rend="answer"><xsl:apply-templates/></p>
        </xsl:when>
        <!-- separateur -->
        <xsl:when test="$pStyle='break'">
            <p rend="break"><xsl:apply-templates/></p>
        </xsl:when>
        <!-- paragraphesansretrait 	*- 	  	-->
        <xsl:when test="$pStyle='no-indent'">
            <p rend="no-indent"><xsl:apply-templates/></p>
        </xsl:when>
        <!-- epigraphe -->
        <xsl:when test="$pStyle='epigraph'">
            <p rend="epigraph"><xsl:apply-templates/></p>
        </xsl:when>
        <!-- section2 -->
        <!-- pigraphe -->
        <!-- sparateur -->
        <!-- quotation -->
        <!-- terme -->
        <!-- definitiondeterme -->
        <!-- bibliographieannee -->
        <!-- bibliographieauteur -->
        <!-- bibliographiereference -->
        <!-- creditillustration,crditillustration,creditsillust... -->
        <xsl:when test="$pStyle='figure-license'">
            <p rend="figure-license"><xsl:apply-templates/></p>
        </xsl:when>

        <xsl:otherwise>
            <xsl:choose>
                <xsl:when test="$pStyle='standard'">
                    <p><xsl:apply-templates/></p>
                </xsl:when>
                <xsl:otherwise>
                    <!-- un autre style du ME -->
                    <p rend="{$pStyle}" rendition="#{$pStyle}"><xsl:apply-templates/></p>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>



    <!-- special case paragraphs -->
    <xsl:template match="text:p[@text:style-name='XMLComment']">
        <xsl:comment>
            <xsl:value-of select="."/>
        </xsl:comment>
    </xsl:template>

    <xsl:template match="text:p[@text:style-name]">
        <xsl:choose>
            <xsl:when test="draw:frame and parent::draw:text-box">
                <xsl:apply-templates select="draw:frame"/>
                <head>
                    <xsl:apply-templates select="text()|*[not(local-name(.)='frame')]"/>
                </head>
            </xsl:when>

            <xsl:when test="draw:frame/draw:image[@xlink:href]">
                <p><xsl:apply-templates/></p>
            </xsl:when>
<!--
            <xsl:when test="table:table">
                <p><xsl:apply-templates/></p>
            </xsl:when>
            <xsl:when test="text:list">
                <p><xsl:apply-templates/></p>
            </xsl:when>
-->
            <!-- cellules des tableaux -->
            <xsl:when test="parent::table:table-cell">
                <s><xsl:apply-templates/></s>
            </xsl:when>

            <xsl:when test="not(node())"/>
            <xsl:when test="count(parent::text:note-body/text:p)=1">
                <xsl:apply-templates/>
            </xsl:when>
            <xsl:when test="@text:style-name='Document Title'">
                <title>
                    <xsl:apply-templates/>
                </title>
            </xsl:when>
            <xsl:when test="@text:style-name='Author'">
                <author>
                    <xsl:apply-templates/>
                </author>
            </xsl:when>
            <xsl:when test="@text:style-name='lg'">
                <lg>
                    <xsl:apply-templates/>
                </lg>
            </xsl:when>
            <xsl:when test="@text:style-name='Title'">
                <title>
                    <xsl:apply-templates/>
                </title>
            </xsl:when>
            <xsl:when test="@text:style-name='Date'">
                <date>
                    <xsl:apply-templates/>
                </date>
            </xsl:when>
            <xsl:when test="@text:style-name='Section Title'">
                <head>
                    <xsl:apply-templates/>
                </head>
            </xsl:when>
            <xsl:when test="@text:style-name='Appendix Title'">
                <head>
                    <xsl:apply-templates/>
                </head>
            </xsl:when>
            <xsl:when test="@text:style-name='Screen'">
                <Screen>
                    <xsl:apply-templates/>
                </Screen>
            </xsl:when>
            <xsl:when test="@text:style-name='Output'">
                <Output>
                    <xsl:apply-templates/>
                </Output>
            </xsl:when>
            <xsl:when test="normalize-space(.)=''"/>

            <xsl:otherwise>
                <xsl:call-template name="LODEL_styles">
                    <xsl:with-param name="pStyle">
                        <xsl:value-of select="@text:style-name"/>
                    </xsl:with-param>
                </xsl:call-template>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="text:h[@text:style-name]">
        <xsl:call-template name="LODEL_styles">
            <xsl:with-param name="pStyle">
                <xsl:value-of select="@text:style-name"/>
            </xsl:with-param>
        </xsl:call-template>
    </xsl:template>

    <xsl:template match="office:annotation/text:p">
        <note>
            <xsl:apply-templates/>
        </note>
    </xsl:template>


    <!-- normal paragraphs -->
    <xsl:template match="text:p">
        <xsl:choose>
            <xsl:when test="parent::text:list-item">
                <xsl:call-template name="applyStyle"/>
            </xsl:when>
            <xsl:when test="@text:style-name='Table'"/>
            <xsl:when test="normalize-space(.)=''"><xsl:text>&#32;</xsl:text></xsl:when>
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

    <!-- lists -->
    <xsl:template match="text:list">
        <xsl:choose>
            <xsl:when test="@text:style-name='Outline'">
                <xsl:for-each select=".//text:h[1]">
                <xsl:if test="not(starts-with(@text:style-name, 'bibliography-'))">
                <p>
                        <xsl:attribute name="rend">
                            <xsl:value-of select="@text:style-name"/>
                        </xsl:attribute>
                        <xsl:apply-templates/>
                </p>
                </xsl:if> 
                </xsl:for-each>
            </xsl:when>
            <xsl:when test="text:list-item/text:h">
                <xsl:for-each select="text:list-item">
                    <xsl:apply-templates/>
                </xsl:for-each>
            </xsl:when>
            <xsl:when test="@text:style-name='Var List'">
                <list>
                    <xsl:apply-templates/>
                </list>
            </xsl:when>
            <xsl:when test="starts-with(@text:style-name,'ordered-')">
            <p>
                <list type="ordered">
                    <xsl:apply-templates/>
                </list>
            </p>
            </xsl:when>
            <xsl:otherwise>
            <p>
                <list type="unordered">
                    <xsl:apply-templates/>
                </list>
            </p>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="text:list-header">
        <head>
        <xsl:apply-templates/>
        </head>
    </xsl:template>

    <xsl:template match="text:list-item">
        <xsl:choose>
            <xsl:when test="parent::text:list/@text:style-name='Outline'">
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

    <xsl:template match="text:p[@text:style-name='VarList Item' or @text:style-name='List Contents']">
        <xsl:if test="not(preceding-sibling::text:p[@text:style-name='VarList Item'])">
        <xsl:text disable-output-escaping="yes">&lt;item&gt;</xsl:text>
        </xsl:if>
        <xsl:apply-templates/>
        <xsl:if test="not(following-sibling::text:p[@text:style-name='VarList Item'])">
        <xsl:text disable-output-escaping="yes">&lt;/item&gt;</xsl:text>
        </xsl:if>
        <xsl:variable name="next">
        <xsl:for-each select="following-sibling::text:p[1]">
            <xsl:value-of select="@text:style-name"/>
        </xsl:for-each>
        </xsl:variable>
        <xsl:choose>
        <xsl:when test="$next='VarList Term'"/>
        <xsl:when test="$next='List Heading'"/>
        <xsl:when test="$next='VarList Item'"/>
        <xsl:when test="$next='List Contents'"/>
        <xsl:otherwise>
            <xsl:text disable-output-escaping="yes">&lt;/list&gt;</xsl:text>
        </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="text:p[@text:style-name='VarList Term' or @text:style-name='List Heading']">
        <xsl:variable name="prev">
        <xsl:for-each select="preceding-sibling::text:p[1]">
            <xsl:value-of select="@text:style-name"/>
        </xsl:for-each>
        </xsl:variable>
        <xsl:choose>
        <xsl:when test="$prev='VarList Term'"/>
        <xsl:when test="$prev='List Heading'"/>
        <xsl:when test="$prev='VarList Item'"/>
        <xsl:when test="$prev='List Contents'"/>
        <xsl:otherwise>
            <xsl:text disable-output-escaping="yes">&lt;list type="gloss"&gt;</xsl:text>
        </xsl:otherwise>
        </xsl:choose>
        <label>
        <xsl:apply-templates/>
        </label>
    </xsl:template>


    <!-- notes -->
    <xsl:template match="text:note-citation"/>
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
            <xsl:otherwise>
                <xsl:attribute name="place">foot</xsl:attribute>
            </xsl:otherwise>
        </xsl:choose>
        <xsl:if test="text:footnote-citation">
            <xsl:attribute name="n">
                <xsl:value-of select="text:footnote-citation"/>
            </xsl:attribute>
        </xsl:if>
        <xsl:apply-templates/>
    </note>
    </xsl:template>


    <!-- inline -->
    <xsl:template match="text:span">
        <xsl:variable name="Style">
            <xsl:value-of select="@text:style-name"/>
        </xsl:variable>
        <xsl:choose>
            <xsl:when test="starts-with($Style,'T')">
            <hi>
                <xsl:attribute name="rendition">
                    <xsl:value-of select="concat('#', $Style)"/>
                </xsl:attribute>
                <xsl:apply-templates/>
            </hi>
            </xsl:when>
            <xsl:when test="$Style='marquedecommentaire'"> 
                <!-- commentaires genre mode revision -->
            </xsl:when>
            <xsl:when test="$Style='emphasis'">
                <emph><xsl:apply-templates/></emph>
            </xsl:when>
            <xsl:when test="$Style='underline'">
                <hi rend="ul"><xsl:apply-templates/></hi>
            </xsl:when>
            <xsl:when test="$Style='smallCaps'">
                <hi rend="sc"><xsl:apply-templates/></hi>
            </xsl:when>
            <xsl:when test="$Style='emphasisbold'">
                <emph rend="bold"><xsl:apply-templates/></emph>
            </xsl:when>
            <xsl:when test="$Style='highlight'">
                <hi><xsl:apply-templates/></hi>
            </xsl:when>
            <xsl:when test="$Style='q'">
                <q>
                <xsl:choose>
                    <xsl:when test="starts-with(.,'&#x2018;')">
                        <xsl:value-of select="substring-before(substring-after(.,'&#x2018;'),'&#x2019;')"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:apply-templates/>
                    </xsl:otherwise>
                </xsl:choose>
                </q>
            </xsl:when>
            <xsl:when test="$Style='date'">
                <date>
                <xsl:apply-templates/>
                </date>
            </xsl:when>
            <xsl:when test="$Style='l'">
                <l>
                <xsl:apply-templates/>
                </l>
            </xsl:when>
            <xsl:when test="$Style='filespec'">
                <Filespec>
                <xsl:apply-templates/>
                </Filespec>
            </xsl:when>
            <xsl:when test="$Style='gi'">
                <gi>
                <xsl:apply-templates/>
                </gi>
            </xsl:when>
            <xsl:when test="$Style='code'">
                <Code>
                <xsl:apply-templates/>
                </Code>
            </xsl:when>
            <xsl:when test="$Style='input'">
                <Input>
                <xsl:apply-templates/>
                </Input>
            </xsl:when>
            <xsl:when test="$Style='internetlink'">
                <xsl:apply-templates/>
            </xsl:when>
            <xsl:when test="$Style='subscript'">
                <hi rend="sub">
                <xsl:apply-templates/>
                </hi>
            </xsl:when>
            <xsl:when test="$Style='superscript'">
                <hi rend="sup">
                <xsl:apply-templates/>
                </hi>
            </xsl:when>
            <xsl:when test="../text:h">
                <xsl:apply-templates/>
            </xsl:when>
            <!-- <xsl:when test="normalize-space(.)=''"/>-->
                <xsl:when test="$Style='italic'">
                    <hi rend="italic"><xsl:apply-templates/></hi>
                </xsl:when>
                <xsl:when test="$Style='bold'">
                    <hi rend="bold"><xsl:apply-templates/></hi>
                </xsl:when>
                <xsl:when test="$Style='sup'">
                    <xsl:choose>
                        <xsl:when test="not(./text:note)">
                            <hi rend="sup"><xsl:apply-templates/></hi>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:apply-templates/>
                        </xsl:otherwise>
                    </xsl:choose> 
                </xsl:when>
                <xsl:when test="$Style='sub'">
                    <hi rend="sub"><xsl:apply-templates/></hi>
                </xsl:when>
                <xsl:when test="$Style='solid'">
                    <hi rend="underline"><xsl:apply-templates/></hi>
                </xsl:when>
                <xsl:when test="$Style='italic;bold'">
                    <hi rend="emphasis"><xsl:apply-templates/></hi>
                </xsl:when>

            <xsl:otherwise>
                <xsl:call-template name="applyStyle"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="applyStyle">
        <xsl:variable name="name">
            <xsl:value-of select="@text:style-name"/>
        </xsl:variable>
        <xsl:choose>
            <xsl:when test="string-length(.)=0"/>
            <xsl:when test="text:note">
                <xsl:apply-templates/>
            </xsl:when>
<!--            
            <xsl:when test="key('STYLES',$name)">
                <xsl:variable name="contents">
                    <xsl:apply-templates/>
                </xsl:variable>
                <xsl:for-each select="key('STYLES',$name)">
                    -
                    <xsl:for-each select="style:text-properties/@*">
                    <xsl:value-of select="name(.)"/>:        <xsl:value-of select="."/>&#10;
                    </xsl:for-each>
                    -
                    <xsl:choose>
                        <xsl:when test="style:text-properties[starts-with(@style:text-position,'super')]">
                        <hi rend="sup">
                            <xsl:copy-of select="$contents"/>
                        </hi>
                        </xsl:when>
                        <xsl:when test="style:text-properties[starts-with(@style:text-position,'sub')]">
                        <hi rend="sub">
                            <xsl:copy-of select="$contents"/>
                        </hi>
                        </xsl:when>
                        <xsl:when test="style:text-properties[@fo:font-weight='bold']">
                        <hi rend="bold">
                            <xsl:copy-of select="$contents"/>
                        </hi>
                        </xsl:when>
                        <xsl:when test="style:text-properties[style:text-underline-style='solid']">
                        <hi rend="underline">
                            <xsl:copy-of select="$contents"/>
                        </hi>
                        </xsl:when>
                        <xsl:when test="style:text-properties[@fo:font-style='italic']">
                        <hi rend="italic">
                            <xsl:copy-of select="$contents"/>
                        </hi>
                        </xsl:when>
                        <xsl:otherwise>
                        <xsl:copy-of select="$contents"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:for-each>
            </xsl:when>
-->
            <xsl:otherwise>
                <xsl:apply-templates/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>


    <!-- tables -->
    <xsl:template match="table:table">
    <p>
        <table rend="frame">
            <xsl:if test="@table:name and not(@table:name = 'local-table')">
                <xsl:attribute name="xml:id">
                    <xsl:value-of select="@table:name"/>
                </xsl:attribute>
            </xsl:if>
            <xsl:if test="following-sibling::text:p[@text:style-name='Table']">
                <head>
                    <xsl:value-of select="following-sibling::text:p[@text:style-name='Table']"/>
                </head>
            </xsl:if>
            <xsl:call-template name="generictable"/>
        </table>
    </p>
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
        <xsl:if test="@table:number-columns-spanned &gt;'1'">
            <xsl:attribute name="cols">
            <xsl:value-of select="@table:number-columns-spanned"/>
            </xsl:attribute>
        </xsl:if>
        <xsl:if test="text:h">
            <xsl:attribute name="role">label</xsl:attribute>
        </xsl:if>
        <xsl:if test="text:p">
            <xsl:apply-templates/>
        </xsl:if>
        </cell>
    </xsl:template>




    <!-- drawing -->
    <xsl:template match="draw:plugin">
        <ptr target="{@xlink:href}"/>
        <xsl:apply-templates/>
    </xsl:template>

    <xsl:template match="draw:text-box"/>

    <xsl:template match="draw:frame/draw:image[@xlink:href]">
        <xsl:if test="not(starts-with(@xlink:href, './Object'))"> <!-- TODO OLE -->
        <figure>
            <graphic>
            <xsl:attribute name="url">
                <xsl:value-of select="@xlink:href"/>
            </xsl:attribute>
            </graphic>
        </figure>
        </xsl:if>
    </xsl:template>

<!--
    <xsl:template match="draw:frame">
        <xsl:choose>
            <xsl:when test="ancestor::draw:frame">
                <xsl:apply-templates/>
            </xsl:when>
            <xsl:otherwise>
                <xsl:apply-templates/>
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
            <xsl:when test="@xlink:href">
                <graphic>
                <xsl:attribute name="url">
                    <xsl:value-of select="@xlink:href" />
                </xsl:attribute>
                </graphic>
            </xsl:when>
        </xsl:choose>
    </xsl:template>
-->

    <!-- linking -->
    <xsl:template match="text:a">
        <xsl:choose>
        <xsl:when test="starts-with(@xlink:href,'mailto:')">
            <xsl:choose>
            <xsl:when test=".=@xlink:href">
                <ptr target="{substring-after(@xlink:href,'mailto:')}"/>
            </xsl:when>
            <xsl:otherwise>
                <ref target="{@xlink:href}">
                <xsl:apply-templates/>
                </ref>
            </xsl:otherwise>
            </xsl:choose>
        </xsl:when>
        <xsl:when test="contains(@xlink:href,'://')">
            <xsl:choose>
            <xsl:when test=".=@xlink:href">
                <ptr target="{@xlink:href}"/>
            </xsl:when>
            <xsl:otherwise>
                <ref target="{@xlink:href}">
                <xsl:apply-templates/>
                </ref>
            </xsl:otherwise>
            </xsl:choose>
        </xsl:when>
        <xsl:when test="not(contains(@xlink:href,'#'))">
            <ref target="{@xlink:href}">
            <xsl:apply-templates/>
            </ref>
        </xsl:when>
        <xsl:otherwise>
            <xsl:variable name="linkvar" select="@xlink:href"/>
            <xsl:choose>
            <xsl:when test=".=$linkvar">
                <ptr target="{$linkvar}"/>
            </xsl:when>
            <xsl:otherwise>
                <ref target="{$linkvar}">
                <xsl:apply-templates/>
                </ref>
            </xsl:otherwise>
            </xsl:choose>
        </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="text:line-break">
        <xsl:if test="not(parent::text:span[@text:style-name='l'])">
        <lb/>
        </xsl:if>
    </xsl:template>

    <xsl:template match="text:soft-page-break">
        <xsl:if test="not(parent::text:span[@text:style-name='l'])">
        <pb/>
        </xsl:if>
    </xsl:template>

    <xsl:template match="text:tab">
        <xsl:text>	</xsl:text>
    </xsl:template>

    <xsl:template match="text:reference-ref">
        <ptr target="{@text:ref-name}"/>
    </xsl:template>

    <xsl:template name="id.attribute.literal">
        <xsl:if test="child::text:reference-mark-start">
        <xsl:text> xml:id=&quot;</xsl:text>
            <xsl:value-of select="child::text:reference-mark-start/@text:style-name"/>
            <xsl:text>&quot;</xsl:text>
        </xsl:if>
    </xsl:template>

    <xsl:template name="id.attribute">
        <xsl:if test="child::text:reference-mark-start">
        <xsl:attribute name="xml:id">
            <xsl:value-of select="child::text:reference-mark-start/@text:style-name"/>
        </xsl:attribute>
        </xsl:if>
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
        <term>
            <xsl:value-of select="@text:string-value"/>
        </term>
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
            <term>
                <xsl:value-of select="@text:key1"/>
            </term>
            <index>
                <term>
                <xsl:value-of select="@text:string-value"/>
                </term>
            </index>
            </xsl:when>
            <xsl:otherwise>
            <term>
                <xsl:value-of select="@text:string-value"/>
            </term>
            </xsl:otherwise>
        </xsl:choose>
        </index>
    </xsl:template>

    <xsl:template match="text:alphabetical-index">
        <index>
        <title>
            <xsl:value-of select="text:index-body/text:index-title/text:p"/>
        </title>
        <xsl:apply-templates select="text:index-body"/>
        </index>
    </xsl:template>

    <xsl:template match="text:index-body">
        <xsl:for-each select="text:p[@text:style-name = 'Index 1']">
        <index>
            <term>
            <xsl:value-of select="."/>
            </term>
            <xsl:if test="key('secondary_children', generate-id())">
            <index>
                <term>
                <xsl:value-of select="key('secondary_children', generate-id())"/>
                </term>
            </index>
            </xsl:if>
        </index>
        </xsl:for-each>
    </xsl:template>

    <xsl:template match="text:bookmark-ref">
        <ref target="#{@text:ref-name}" type="{@text:reference-format}">
        <xsl:apply-templates/>
        </ref>
    </xsl:template>

    <xsl:template match="text:bookmark-start">
        <xsl:if test="not(starts-with(@text:name, 'OLE'))"> <!-- TODO OLE MATHml -->
            <anchor>
            <xsl:attribute name="xml:id">
                <xsl:value-of select="@text:name"/>
            </xsl:attribute>
            </anchor>
        </xsl:if>
    </xsl:template>


<!--
These seem to have no obvious translation
-->

    <xsl:template match="text:bookmark-end"/>
    
    <xsl:template match="text:bookmark"/>
    
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

    <xsl:template match="text:tracked-changes"/>


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

    <!--
    <xsl:template match="text:section">
    <xsl:choose>
        <xsl:when test="text:h">
        <xsl:call-template name="aSection"/>
        </xsl:when>
        <xsl:otherwise>
        <xsl:apply-templates/>
        </xsl:otherwise>
    </xsl:choose>
    </xsl:template>
    -->

    <xsl:template match="text:sequence-decl">
        <xsl:apply-templates/>
    </xsl:template>
    
    <xsl:template match="text:sequence-decls">
        <xsl:apply-templates/>
    </xsl:template>
    
    
    <xsl:template match="text:sequence">
        <xsl:apply-templates/>
    </xsl:template>
    
    
    <xsl:template match="text:section-source"/>
    
    


</xsl:stylesheet>