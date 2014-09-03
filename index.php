<?php
/**
 * @package OTX
 * @copyright Centre pour L'édition Électronique Ouverte
 * @licence http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 **/

require_once('server/otxserver.class.php');
require_once('otx.func.php');
require_once('OTXConfig.class.php');

otx_auth();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    # create the server instantiation
    try {
        $config = OTXConfig::singleton();
        $plugin = array();

        foreach( array('mode', 'site', 'sourceoriginale') as $arg ){
            if(!isset($_POST[$arg])) throw new Exception('Argument missing');
        }

        if(isset($_FILES['attachment'])){
            $attachment = $_FILES['attachment']['tmp_name'];
        }elseif(isset($_POST['attachment'])){
            $attachment = tempnam($config->cachepath, "attachment");
            file_put_contents($attachment, base64_decode($_POST['attachment']));
        }else
            throw new Exception('File missing');

        $schemapath = tempnam(sys_get_temp_dir(), 'otx');
        file_put_contents($schemapath, $_POST['schema']);


        $server = OTXserver::singleton($_POST['site'], $_POST['sourceoriginale'], $_POST['mode'], $schemapath, $attachment);

        $return = $server->run();

        $response = array(
            'status'     => $return['status'],
            'xml'        => $return['xml'],
            'report'     => $return['report'],
            'odt'        => $return['odt'],
            'lodelxml'   => $return['lodelxml'],
        );

        if (preg_match("/^plugin:(?P<plugin>\w+)/", $_POST['mode'], $match)){
            $plugin[$match['plugin']] = $return[$match['plugin']];
        }

        if(!empty($plugin)){
            $pluginname              = current(array_keys($plugin));
            $response[$pluginname]   = base64_encode(serialize($plugin[$pluginname]));
        }else{
            $server->cleanup();
        }

        $json = json_encode($response);
        if ($json === False || json_last_error() === JSON_ERROR_UTF8) // TODO: better error check, wait for php 5.5 for json_last_error_msg(), to have better description
            throw new Exception("Could not encode response. Conversion problem !");

        header('Content-type: application/json');
        echo $json;
        exit;
    }
    catch (Exception $fault) {
        /*
         * TODO
         * Catch exceptions
         */
        header("HTTP/1.0 500 Exception");
        echo($fault->getMessage());
        die();
    }
}

header("HTTP/1.0 400 Bad Request");
