<?php
/**
 * EM.odd.php
 * @author Nicolas Barts
 * @copyright 2010, CLEO/Revues.org
 * @licence http://www.gnu.org/copyleft/gpl.html
**/

function EM2TEI($schema="revorg") {

    return array(
# dc:title
'header:title'                  =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type='main']",
'header:subtitle'               =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type='sup']",
'header:uptitle'                =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type='sub']",
# dc:date
'header:date'                   =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:date",
'header:creationdate'           =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:publicationStmt/tei:date", 
# dc:language
'header:language'               =>  "//tei:TEI/tei:teiHeader/tei:profileDesc/tei:langUsage/tei:language",
# dc:creator
'header:author'                 =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:author",
# dc:contributor
'header:translator'             =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:editor[@role='translator']",
// editeurscientifique
'header:editor'                 =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:editor",
// descriptionauteur
'header:author-description'     =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:*/tei:affiliation",
// affiliation, .affiliation
'header:author-affiliation'     =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:*/tei:affiliation/tei:orgName",
// prefixe, .prefixe
'header:author-prefix'          =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:*/tei:roleName[@type='honorific']",
// courriel, .courriel
'header:author-email'           =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:*/tei:affiliation/tei:email",
// role,.role
'header:author-function'        =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:*/tei:roleName",
'header:author-role'            =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:*/tei:roleName",
// titretraduitfr:fr,titrefr:fr, titretraduiten:en,titleen:en,titreen:en, titretraduites:es,tituloes:es,titrees:es, titretraduitit:it,titoloit:it,titreit:it, titretraduitde:de,titelde:de,titrede:de, titretraduitpt:pt,titrept:pt, titretraduitru:ru,titreru:ru
'header:altertitle'             =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type='alt'][@xml:lang]",
# dc:subject
'header:keywords-fr'            =>  "//tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='keyword'][@xml:lang='fr']",
// keywords,motclesen
'header:keywords-en'            =>  "//tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='keyword'][@xml:lang='en']",
// palabrasclaves, .palabrasclaves, motscleses
'header:keywords-es'            =>  "//tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='keyword'][@xml:lang='es']",
// schlusselworter, .schlusselworter, motsclesde, schlagworter, .schlagworter
'header:keywords-de'            =>  "//tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='keyword'][@xml:lang='de']",
// periode, .periode, priode
'header:chronological'          =>  "//tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='chronological']",
// geographie, gographie,.geographie
'header:geographical'           =>  "//tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='geographical']",
// themes,thmes,.themes
'header:subject'                =>  "//tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='subject']",
// numerodocument,numrodudocument
'header:documentnumber'         =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:idno[@type='documentnumber']",
//
'header:pagenumber'             =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:publicationStmt/tei:idno[@type='pp']",
// licence, droitsauteur
'header:license'                =>  "//tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:availability",

// titreoeuvre
'header:review-title'           =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:titleStmt/tei:title",
// auteuroeuvre
'header:review-author'          =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:titleStmt/tei:author",
// noticebibliooeuvre
'header:review-bibliography'    =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:notesStmt/tei:note[@type='bibl']",
// datepublioeuvre
'header:review-date'            =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:publicationStmt/tei:date",

// rsum:fr,resume:fr,resumefr:fr, abstract:en,resumeen:en,extracto:es,resumen:es, resumees:es,resumo:pt,resumept:pt, riassunto:it,resumeit:it, zusammenfassung:de,resumede:de, resumeru:ru
'front:abstract'                =>  "//tei:TEI/tei:text/tei:front/tei:div[@type='abstract'][@xml:lang]",
// erratum, addendum
'front:correction'              =>  "//tei:TEI/tei:text/tei:front/tei:div[@type='correction']",
// ndlr
'front:editornote'              =>  "//tei:TEI/tei:text/tei:front/tei:note[@resp='editor']",
// ndla
'front:authornote'             =>   "//tei:TEI/tei:text/tei:front/tei:note[@resp='author']",
// dedicace
'front:dedication'              =>  "//tei:TEI/tei:text/tei:front/tei:div[@type='dedication']",

//
'body:standard'                 =>  "//tei:TEI/tei:text/tei:body/tei:*",
//
'text:footnote'                 =>  "//tei:TEI/tei:text/tei:body/tei:*/tei:note[@place='foot']",
// notefin
'text:endnote'                  =>  "//tei:TEI/tei:text/tei:body/tei:*/tei:note[@place='end']",
// paragraphesansretrait
'text:noindent'                 =>  "//tei:TEI/tei:text/tei:*/tei:p[@rend='noindent']",
// epigraphe, pigraphe
'body:epigraph'                 =>  "//tei:TEI/tei:text/tei:*/tei:p[@rend='epigraph']",
//
'text:quotation'                =>  "//tei:TEI/tei:text/tei:*/tei:p[@rend='quotation']",
'text:reference'                =>  "//tei:TEI/tei:text/tei:*/tei:p[@rend='reference']",
'text:quotation2'               =>  "//tei:TEI/teitext/tei:*/tei:p[@rend='quotation2']",
'text:quotation3'               =>  "//tei:TEI/teitext/tei:*/tei:p[@rend='quotation3']",
//
'text:code'                     =>  "//tei:TEI/tei:text/tei:*/tei:hi[rend='code']",
'text:question'                 =>  "//tei:TEI/tei:text/tei:*/tei:p[@rend='question']",
'text:answer'                   =>  "//tei:TEI/tei:text/tei:*/tei:p[@rend='answer']",
'text:break'                    =>  "//tei:TEI/tei:text/tei:*/tei:p[@rend='break']",
// titreillustration
'text:figure-title'             =>  "//tei:TEI/tei:text/tei:*/tei:figure/tei:head",
// legendeillustration
'text:figure-legend'            =>  "//tei:TEI/tei:text/tei:*/tei:figure-title",
// creditillustration,crditillustration,creditsillustration,crditsillustration
'text:figure-license'           =>  "//tei:TEI/tei:text/tei:*/tei:figure/tei:note[@type='license']",

// remerciements,acknowledgment
'front:acknowledgment'          =>  "//tei:TEI/tei:text/tei:front/tei:div[@type='ack']",

// bibliographiereference
'header:bibl'                   =>  "//tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:notesStmt/tei:note[@type='bibl']",

//
'back:appendix'                 =>  "//tei:TEI/tei:text/tei:back/tei:div[@type='appendix']",
//
'back:bibliography'             =>  "//tei:TEI/tei:text/tei:back/tei:div[@type=biliogr]"

//            'header:scientificeditor'       => "/TEI/teiHeader/fileDesc/titleStmt/editor",
//            'text:item'                     => "/TEI/text/*/item",
//            'text:heading1'                 => "/TEI/text/*/ab[@type=head][@level=1]",
//            'text:heading3'                 => "/TEI/text/*/ab[@type=head][@level=3]",
//            'text:heading4'                 => "/TEI/text/*/ab[@type=head][@level=4]",
//            'text:heading5'                 => "/TEI/text/*/ab[@type=head][@level=5]",
//            'text:heading6'                 => "/TEI/text/*/ab[@type=head][@level=6]",
//            'text:heading2'                 => "/TEI/text/*/ab[@type=head][@level=2]",
//                      => "/TEI/text/*/p[@rend=ack]"
    );

}

#EOF