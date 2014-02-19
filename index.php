<?php

require_once('soap/server/otxserver.class.php');
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
            $attachment = tempnam($config->_config->tmppath, "attachment");
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
        }

        header('Content-type: application/json');
        echo(json_encode($response));
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
