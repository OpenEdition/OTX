<?php
/**
 * EM.odd.php
 * @author Nicolas Barts
 * @copyright 2010, CLEO/Revues.org
 * @licence http://www.gnu.org/copyleft/gpl.html
**/

function EM2TEI($schema="revorg") {

        return array(
//            'front:correction'              => "/TEI/text/front/div[@type=correction]",
//            'header:altertitle'             => "/TEI/teiHeader/fileDesc/titleStmt/title[@type=alt]@xml:lang",
//            'back:appendix'                 => "/TEI/text/back/div[@type=appendix]",
//            'back:bibliography'             => "/TEI/text/back/div[@type=biliogr]",
            'header:date'                   => "//tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:date",
//            'header:review-date'            => "/TEI/teiHeader/fileDesc/sourceDesc/biblFull/publicationStmt/date@when",
//            'header:creationdate'           => "/TEI/teiHeader/fileDesc/sourceDesc/biblFull/publicationStmt/date@when",
//            'front:dedication'              => "/TEI/text/front/div[@type=dedication]",
            'header:language'               => "//tei:teiHeader/tei:profileDesc/tei:langUsage/tei:language",
//            'header:authornote'             => "/TEI/text/front/note[@resp=author]",
//            'front:editornote'              => "/TEI/text/front/note[@resp=editor]",
//            'text:endnote'                  => "/TEI/text/body/*/[note@place=end]",
//            'text:footnote'                 => "/TEI/text/body/*/[note@place=foot]",
//            'header:bibl'                   => "/TEI/teiHeader/fileDesc/sourceDesc/biblFull/notesStmt/note[@type=bibl]",
//            'header:review-bibliography'    => "/TEI/teiHeader/fileDesc/sourceDesc/biblFull/notesStmt/note[@type=bibl]",
//            'header:documentnumber'         => "/TEI/teiHeader/fileDesc/publicationStmt/idno[@type=documentnumber]",
//            'header:pagenumber'             => "/TEI/teiHeader/fileDesc/sourceDesc/biblFull/publicationStmt/idno[@type=pp]",
            'front:abstract'                => '//tei:text/tei:front/tei:div[@type="abstract"]',
//            'header:subtitle'               => "/TEI/teiHeader/fileDesc/titleStmt/title[@type=sub]",
//            'header:uptitle'                => "/TEI/teiHeader/fileDesc/titleStmt/title[@type=sup]",
//            'text:standard'                 => "/TEI/text/body/*",
            'header:title'                  => '//tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type="main"]',
//            'header:review-title'           => "/TEI/teiHeader/fileDesc/sourceDesc/biblFull/titleStmt/title",
            'header:author'                 => "//tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:author",
//            'header:review-author'          => "/TEI/teiHeader/fileDesc/sourceDesc/biblFull/titleStmt/author",
//            'header:scientificeditor'       => "/TEI/teiHeader/fileDesc/titleStmt/editor",
            'header:translator'             => '//tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:editor[@role="translator"]',
//            'header:author-affiliation'     => "/TEI/teiHeader/fileDesc/titleStmt/author/affiliation/orgName",
//            'header:author-email'           => "/TEI/teiHeader/fileDesc/titleStmt/author/affiliation/email",
//            'header:author-description'     => "/TEI/teiHeader/fileDesc/titleStmt/author/affiliation",
//            'header:author-function'        => "/TEI/teiHeader/fileDesc/titleStmt/author/roleName",
//            'header:author-prefix'          => "/TEI/teiHeader/fileDesc/titleStmt/author/roleName[@type=honorific]",
//            'header:author-role'            => "/TEI/teiHeader/fileDesc/titleStmt/author/roleName",
            'header:license'                => "//tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:availability",
//            'header:chronological'          => "/TEI/teiHeader/profileDesc/textClass/keywords[@scheme=chronological]",
//            'header:geographical'           => "/TEI/teiHeader/profileDesc/textClass/keywords[@scheme=chronological]",
//            'header:keywords-de'            => "/TEI/teiHeader/profileDesc/textClass/keywords[@scheme=keyword][@xml:lang=de]",
//            'header:keywords-en'            => "/TEI/teiHeader/profileDesc/textClass/keywords[@scheme=keyword][@xml:lang=en]",
//            'header:keywords-es'            => "/TEI/teiHeader/profileDesc/textClass/keywords[@scheme=keyword][@xml:lang=es]",
            'header:keywords-fr'            => '//tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme="keyword"]'
//            'header:subject'                => "/TEI/teiHeader/profileDesc/textClass/keywords[@scheme=subject]",
//            'text:quotation'                => "/TEI/text/*/p[@rend=quotation]",
//            'text:reference'                => "/TEI/text/*/p[@rend=reference]",
//            'text:quotation2'               => "/TEI/text/*/p[@rend=quotation2]",
//            'text:quotation3'               => "/TEI/text/*/p[@rend=quotation3]",
//            'text:figure-title'             => "/TEI/text/*/figure/head",
//            'text:figure-legend'            => "text:figure-title",
//            'text:item'                     => "/TEI/text/*/item",
//            'text:code'                     => "/TEI/text/*/hi[rend=code]",
//            'text:question'                 => "/TEI/text/*/p[@rend=question]",
//            'text:answer'                   => "/TEI/text/*/p[@rend=answer]",
//            'text:break'                    => "/TEI/text/*/p[@rend=break]",
//            'text:heading1'                 => "/TEI/text/*/ab[@type=head][@level=1]",
//            'text:heading3'                 => "/TEI/text/*/ab[@type=head][@level=3]",
//            'text:heading4'                 => "/TEI/text/*/ab[@type=head][@level=4]",
//            'text:heading5'                 => "/TEI/text/*/ab[@type=head][@level=5]",
//            'text:heading6'                 => "/TEI/text/*/ab[@type=head][@level=6]",
//            'text:noindent'                 => "/TEI/text/*/p[@rend=noindent]",
//            'body:epigraph'                 => "/TEI/text/*/p[@rend=epigraph]",
//            'text:heading2'                 => "/TEI/text/*/ab[@type=head][@level=2]",
//            'body:epigraph'                 => "/TEI/text/*/p[@rend=epigraph]",
//            'text:break'                    => "/TEI/text/*/p[@rend=break]",
//            'text:quotation'                => "/TEI/text/*/p[@rend=quotation]",
//            'text:figure-license'           => "/TEI/text/*/figure/note[@type=license]",
//            'front:acknowledgment'          => "/TEI/text/*/p[@rend=ack]"
        );

}


//EOF