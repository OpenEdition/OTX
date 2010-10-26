<?php
/**
 * EM.odd.php
 * @author Nicolas Barts
 * @copyright 2010, CLEO/Revues.org
 * @licence http://www.gnu.org/copyleft/gpl.html
**/

function _em2tei($schema="revorg") {

    return array(
# dc:title
"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type='main']"
    => 'header:title',
// surtitre
"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type='sup']" 
    => 'header:uptitle',
// soustitre
"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type='sub']"
    => 'header:subtitle',
# dc:date
"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:date"
    => 'header:date',
// 
"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:publicationStmt/tei:date"
    => 'header:creationdate',
# dc:language
"/tei:TEI/tei:teiHeader/tei:profileDesc/tei:langUsage/tei:language"
    => 'header:language',
# dc:creator
"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:author"
    => 'header:author',
# dc:contributor
"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:editor[@role='translator']"
    => 'header:translator',
// editeurscientifique
"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:editor[not(@role)]"
    => 'header:scientificeditor',

// descriptionauteur
"//tei:affiliation"
    => 'header:author-description',
// prefixe, .prefixe
"//tei:roleName[@type='honorific']"
    => 'header:author-prefix',
// affiliation, .affiliation
"//tei:orgName"
    => 'header:author-affiliation',
// fonction, .fonction
"//tei:roleName[@type='function']"
    => 'header:author-function',
// courriel, .courriel
"//tei:email"
    => 'header:author-email',
// site
"//tei:ref[@type='website']"
    => 'header:author-website',

// titretraduitfr:fr,titrefr:fr, titretraduiten:en,titleen:en,titreen:en, titretraduites:es,tituloes:es,titrees:es, titretraduitit:it,titoloit:it,titreit:it, titretraduitde:de,titelde:de,titrede:de, titretraduitpt:pt,titrept:pt, titretraduitru:ru,titreru:ru
"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type='alt']"
    => 'header:altertitle',

# dc:subject
"/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='keyword']"
    => 'header:keywords',

// keywords,motclesen
"/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='keyword'][@xml:lang='en']"
    => 'header:keywords-en',
// palabrasclaves, .palabrasclaves, motscleses
"/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='keyword'][@xml:lang='es']"
    => 'header:keywords-es',
// schlusselworter, .schlusselworter, motsclesde, schlagworter, .schlagworter
"/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='keyword'][@xml:lang='de']"
    => 'header:keywords-de',
// periode, .periode, priode
"/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='chronological']"
    => 'header:chronological',
// geographie, gographie,.geographie
"/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='geographical']"
    => 'header:geographical',
// themes,thmes,.themes
"/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='subject']"
    => 'header:subject',
// numerodocument,numrodudocument
"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:idno[@type='documentnumber']"
    => 'header:documentnumber',
//
"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:publicationStmt/tei:idno[@type='pp']"
    => 'header:pagenumber',
// licence, droitsauteur
"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:availability"
    => 'header:license',

// rsum:fr,resume:fr,resumefr:fr, abstract:en,resumeen:en,extracto:es,resumen:es, resumees:es,resumo:pt,resumept:pt, riassunto:it,resumeit:it, zusammenfassung:de,resumede:de, resumeru:ru
"/tei:TEI/tei:text/tei:front/tei:div[@type='abstract']"
    => 'front:abstract',
// erratum, addendum
"/tei:TEI/tei:text/tei:front/tei:div[@type='correction']"
    => 'front:correction',
// ndlr
"/tei:TEI/tei:text/tei:front/tei:note[@resp='editor']/tei:p"
    => 'front:editornote',
// ndla
"/tei:TEI/tei:text/tei:front/tei:note[@resp='author']/tei:p"
    => 'front:authornote',
// dedicace
"/tei:TEI/tei:text/tei:front/tei:div[@type='dedication']"
    => 'front:dedication',

// titreoeuvre
"/tei:TEI/tei:text/tei:front/tei:div[@type='review']/tei:p[@rend='review-title']"
    => 'front:review-title',
// auteuroeuvre
"/tei:TEI/tei:text/tei:front/tei:div[@type='review']/tei:p[@rend='review-author']"
    => 'front:review-author',
// noticebibliooeuvre
"/tei:TEI/tei:text/tei:front/tei:div[@type='review']/tei:p[@rend='review-bibliography']"
    => 'front:review-bibliography',
// datepublioeuvre
"/tei:TEI/tei:text/tei:front/tei:div[@type='review']/tei:p[@rend='review-date']"
    => 'front:review-date',

// notesbaspage
"/tei:TEI/tei:text/tei:body/tei:*/tei:note[@place='foot']"  =>  'text:footnote',
// notefin
"/tei:TEI/tei:text/tei:body/tei:*/tei:note[@place='end']"   =>  'text:endnote',

// paragraphesansretrait
"/tei:TEI/tei:text/tei:*/tei:p[@rend='noindent']"
    => 'text:noindent',
// epigraphe, pigraphe
"/tei:TEI/tei:text/tei:*/tei:p[@rend='epigraph']"
    => 'body:epigraph',
//
"/tei:TEI/tei:text/tei:*/tei:p[@rend='quotation']"
    => 'text:quotation',
"/tei:TEI/tei:text/tei:*/tei:p[@rend='reference']"
    => 'text:reference',
"/tei:TEI/teitext/tei:*/tei:p[@rend='quotation2']"
    => 'text:quotation2',
"/tei:TEI/teitext/tei:*/tei:p[@rend='quotation3']"
    => 'text:quotation3',
//
"/tei:TEI/tei:text/tei:*/tei:hi[rend='code']"
    => 'text:code',
"/tei:TEI/tei:text/tei:*/tei:p[@rend='question']"
    => 'text:question',
"/tei:TEI/tei:text/tei:*/tei:p[@rend='answer']"
    => 'text:answer',
"/tei:TEI/tei:text/tei:*/tei:p[@rend='break']"
    => 'text:break',

// titreillustration
"/tei:TEI/tei:text/tei:*/tei:figure/tei:head"
    => 'text:figure-title',
// legendeillustration
"/tei:TEI/tei:text/tei:*/tei:figure-title"
    => 'text:figure-legend',
// creditillustration,crditillustration,creditsillustration,crditsillustration
"/tei:TEI/tei:text/tei:*/tei:figure/tei:note[@type='license']"
    => 'text:figure-license',

// remerciements,acknowledgment !!! TODO - ? EM-BUG ? - TODO !!! 
"/tei:TEI/tei:text/tei:front/tei:div[@type='ack']"
    => 'front:acknowledgment',

// bibliographiereference
"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:notesStmt/tei:note[@type='bibl']"
    => 'header:bibl',

//
"/tei:TEI/tei:text/tei:back/tei:div[@type='appendix']"
    => 'back:appendix',
//
"/tei:TEI/tei:text/tei:back/tei:div[@type='bibliogr']"
    => 'back:bibliography',


// citation
"//*[@rend='quotation']"                => 'text:quotation',
//quotations
"//*[@rend='reference']"                => 'text:reference',
//citationbis
"//*[@rend='quotation2']"               => 'text:quotation2',
//citationter
"//*[@rend='quotation3']"               => 'text:quotation3',
//titreillustration
"//*[@rend='figure-title']"             => 'text:figure-title',
//legendeillustration
"//*[@rend='figure-legend']"            => 'text:figure-legend',
// code
"//*[@rend='code']"                     => 'text:code',
// question
"//*[@rend='question']"                 => 'text:question',
//reponse
"//*[@rend='answer']"                   => 'text:answer',
//separateur
"//*[@rend='break']"                    => 'text:break',
//paragraphesansretrait
"//*[@rend='noindent']"                 => 'text:noindent',
//epigraphe
"//*[@rend='epigraph']"                 => 'text:epigraph',
//quotation
"//*[@rend='quotation']"                => 'text:quotation',
//bibliographiereference
"//*[@rend='bibliographicreference']"   => 'text:bibliographicreference',
//creditillustration,crditillustration,creditsillustration,crditsillustration
"//*[@rend='figure-license']"           => 'text:figure-license',

//remerciements,acknowledgment  => TODO !!!
"/tei:TEI/tei:text/tei:front/tei:div[@type='ack']"  => 'front:acknowledgment',

//sections
"//tei:head[@subtype='level1']" =>  'text:heading1',
"//tei:head[@subtype='level2']" =>  'text:heading2',
"//tei:head[@subtype='level3']" =>  'text:heading3',
"//tei:head[@subtype='level4']" =>  'text:heading4',
"//tei:head[@subtype='level5']" =>  'text:heading5',
"//tei:head[@subtype='level6']" =>  'text:heading6',

//            'header:scientificeditor'       => "/TEI/teiHeader/fileDesc/titleStmt/editor",
//            'text:item'                     => "/TEI/text/*/item",

//Texte du document
"/tei:TEI/tei:text/tei:body/child::*"   =>  'text:standard',    // texte, standard, normal, textbody

// accroche
"//*[@rend='pitch']"    => 'text:pitch',
// encadre
"//*[@rend='box']"      => 'text:box'
#"//tei:floatingText/tei:body/tei:p[@rend='box']"  => 'text:box'   // <floatingText type="box">

);

}

#EOF