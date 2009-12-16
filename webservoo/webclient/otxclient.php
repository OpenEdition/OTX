<?php
ini_set("default_socket_timeout", "360");   // pour que les gros fichiers passent par soap (http)
ini_set("max_execution_time", "360");
ini_set("max_input_time", "360");
ini_set("post_max_size", "32M");
ini_set("upload_max_filesize", "32M");
ini_set("memory_limit", "256M");
set_time_limit(0);  // no limit


if (!empty($_GET)) {
    if (isset($_GET['xml'])) {
        header("content-type: application/xml");
        readfile('/tmp/output.xml');
        die();
    } 
    if (isset($_GET['report'])) {
        header("content-type: application/xml");
        readfile('/tmp/report.xml');
        die();
    } 
    if (isset($_GET['contents'])) {
        $filename="/tmp/output.odt";
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".basename($filename).";");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($filename));
        readfile("$filename");
        die();
    }
}
else {
    require_once('webclient.php');

    //$mode = 'soffice';
    $mode = 'lodel';
    //$mode = 'cairn';

    $sourcepath = "http://127.0.0.1/~barts/otx/client/doc/tests/";
    $sourcename = "marin48.doc";
    $sourcename = rawurlencode($sourcename);

    // lodel 
    $model = "doc/sapiens/sapiens.model.xml";
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
        $input['schema'] = file_get_contents($model);

        $input['servoo.username'] = 'NicolasBarts';
        $input['servoo.passwd'] = '77a90fa633';
/*
        $input['servoo.username'] = 'valentinbrajon';
        $input['servoo.passwd'] = '0f5c4aa022';
*/
        $input['servoo.url'] = "http://otx.revues.org";
        $input['lodel_user'] = 'no login atm';
        $input['lodel_site'] = 'no site atm';

        $status = "";

    if ( ($output=webclient($input, $status)) != FALSE) {
        echo "<ul><h3>otx client</h3><ul>";
        echo "<li>status: {$output->status}</li>";
        if (!file_put_contents("/tmp/output.odt", $output->contents) or 
            !file_put_contents("/tmp/report.xml", $output->report) or 
            !file_put_contents("/tmp/output.xml", $output->xml) ) {
            echo "<li>error: file_put_contents</li>";
        }
        echo '<li>xml (TEI): <a href="?xml">output.xml</a></li>';
        echo '<li>report: <a href="?report">report.xml</a></li>';
        echo '<li>odt returnfile : <a href="?contents">output.odt</a></li>';
        echo "</ul></ul>";
    } else {
        header("content-type: application/xml"); 
        echo $status;
    }

}

?>