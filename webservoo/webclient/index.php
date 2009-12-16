<?php
//header("content-type: application/xml");
error_reporting(E_ALL | E_STRICT);
ini_set("default_socket_timeout", "360");   // pour que les gros fichiers passent par soap (http)
ini_set("max_execution_time", "360");
ini_set("max_input_time", "360");
ini_set("post_max_size", "32M");
ini_set("upload_max_filesize", "32M");
ini_set("memory_limit", "256M");
set_time_limit(0);  // no limit
include_once('../servoo2.inc.php');
require_once('webclient.php');


$mode = 'soffice';
//$mode = 'cairn';
//$mode = 'lodel';

$sourcepath = "tests/otxtest/";
$sourcename = "Tous_les_styles_article08.doc";
$modelpath = "tests/sapiens/sapiens.model.xml";
$revue = "otxtest";

$request = <<<EOD
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/">
    <dc:source>$sourcename</dc:source>
    <prism:publicationName>$revue</prism:publicationName>
    <dc:identifier>http://$revue.revues.org</dc:identifier>
</rdf:RDF>
EOD;

    $input['request'] = $request;
    $input['mode'] = $mode;
    $input['attachment'] = file_get_contents($sourcepath.$sourcename);
    $input['schema'] = file_get_contents($modelpath);
/*
    $input['servoo.username'] = 'valentinbrajon';
    $input['servoo.passwd'] = '0f5c4aa022';
*/

    $input['servoo.username'] = "NicolasBarts";
    $input['servoo.passwd'] = "77a90fa633";

    $input['servoo.url'] = __WEBSERVOO_LOCATION__;
    $input['lodel_user'] = 'no login atm';
    $input['lodel_site'] = 'no site atm';

    $status = "";

    if ( ($output=webclient($input, $status)) != FALSE) {
        if (!file_put_contents("/tmp/source.odt", $output->contents)) {
            echo "<li>error: file_put_contents</li>";
        }
    echo "<ul>";
    echo "<li>{$output->status}</li>";
    echo "<li>{$output->xml}</li>";
    echo "<li>{$output->report}</li>";
    echo '<li>odt returnfile : /tmp/source.odt</li>';
    echo "</ul>";
} else {
    echo $status;
}

?>