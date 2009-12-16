<?php
//header("content-type: application/xml");
error_reporting(E_ALL | E_STRICT);
if(file_exists("../servoo2.inc.devel.php"))
    include_once('../servoo2.inc.devel.php');
else 
    include_once('../servoo2.inc.php');
require_once('../Servel/servel.class.php');

$sourcepath = "tests/otxtest/";
$sourcename = "Tous_les_styles_article08.doc";
$model = "tests/sapiens/sapiens.model.xml";
$revue = "otxtest";

$request = <<<EOD
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/">
    <dc:source>$sourcename</dc:source>
    <prism:publicationName>$revue</prism:publicationName>
    <dc:identifier>http://$revue.revues.org</dc:identifier>
</rdf:RDF>
EOD;

//$mode = 'soffice';
$mode = 'lodel';
//$mode = 'cairn';

$request = <<<EOD
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/">
    <dc:source>$sourcename</dc:source>
    <prism:publicationName>$revue</prism:publicationName>
    <dc:identifier>http://$revue.revues.org</dc:identifier>
</rdf:RDF>
EOD;


$entitypath = "/tmp/document.source";
$modelpath = "/tmp/model.xml";

copy($sourcepath.$sourcename, $entitypath);
//copy($model, $modelpath);


try {
    $testServel = Servel::singleton($request, $mode, $modelpath, $entitypath);
} 
catch(Exception $e) {
    echo "<h3>". $e->getMessage() ."</h3>";
    echo $testServel;
    die();
}

    echo "<ul>";
    echo "<li>CAHETIME : {$testServel->CACHETIME}</li>";
    echo "<li>CAHETIME : {$testServel->CACHEPATH}</li>";
    echo "<li>CAHETIME : {$testServel->TMPPATH}</li>";
    echo "<li>CAHETIME : {$testServel->LIBPATH}</li>";
    echo "<li>CAHETIME : {$testServel->SERVERURI}</li>";
    echo "<li>CAHETIME : {$testServel->SERVERPORT}</li>";
    echo "</ul>";
$testServel->CACHETIME = 1;
$testServel->CACHEPATH = "/home/barts/public_html/nicOO/_SERVEL/CACHE/";
$testServel->TMPPATH = "/home/barts/public_html/nicOO/_SERVEL/tmp/";
$testServel->LIBPATH = "/home/barts/public_html/nicOO/xto/webservoo/Servel/lib/";
$testServel->SERVERURI =  "http://devel.revues.org/";
$testServel->SERVERPORT =  ":80";
    echo "<ul>";
    echo "<li>CAHETIME : {$testServel->CACHETIME}</li>";
    echo "<li>CAHETIME : {$testServel->CACHEPATH}</li>";
    echo "<li>CAHETIME : {$testServel->TMPPATH}</li>";
    echo "<li>CAHETIME : {$testServel->LIBPATH}</li>";
    echo "<li>CAHETIME : {$testServel->SERVERURI}</li>";
    echo "<li>CAHETIME : {$testServel->SERVERPORT}</li>";
    echo "<li></li>";
    echo "<li><pre>{$testServel->request}</pre></li>";
    echo "</ul>";

//$testServel->mode = "toto";
try {
    $return = $testServel->run();
}
catch(Exception $e) {
    echo "<h3>". $e->getMessage() ."</h3>";
    echo $testServel;
    die();
}

echo "<ul>";
echo $return['status'];
echo "</ul>";
echo "<ul>";
echo $return['report'];
echo "<ul>";
echo $return['xml'];
echo "</ul>";

/*
// This will issue an E_USER_ERROR.
try {
    $bug = clone $testServel;
} 
catch(Exception $e) {
    echo "<h3>". $e->getMessage() ."</h3>";
    echo $testServel;
    die();
}
*/

?>