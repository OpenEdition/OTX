<?php
/**
 * index.php
 * PHP >= 5.2
 * @author Nicolas Barts
 * @copyright 2010, CLEO/Revues.org
 * @licence http://www.gnu.org/copyleft/gpl.html
**/
ini_set("max_execution_time", "180");
ini_set("max_input_time", "180");
ini_set("post_max_size", "32M");
ini_set("upload_max_filesize", "32M");
ini_set("memory_limit", "256M");
set_time_limit(3600);
ini_set("session.auto_start", 0);

include_once('otxconfig.inc.php');
include_once('soap/otx.soapserver.inc.php');
require_once('otx.class.php');
require_once('soap/otx.soapserver.class.php');


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (! otx_auth()) {
        header('WWW-Authenticate: Basic realm="OTX Realm"');
        header('HTTP/1.0 401 Unauthorized');
    exit(1);
    }

    // for persistent session
    session_start();
    session_name(uniqid('OTXSID'));

    # create the server instantiation
    try {
        $options = array();
        $options['trace'] = TRUE;
        $options['soap_version'] = SOAP_1_2;
        $options['exceptions'] = TRUE;
        $options['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 5;
        $options['encoding'] = SOAP_LITERAL;
        $wsdl = __SOAP_WSDL__;
        // service
        $SoapServer = new SoapServer($wsdl, $options);
        $SoapServer->setClass('OTXSoapServer');
        // 
        $SoapServer->setPersistence(SOAP_PERSISTENCE_SESSION);
        $SoapServer->handle();
    } 
    catch (SoapFault $fault) {
        echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        echo "\n<otx type=\"SoapFault\">";
        echo "\n<faultcode><![CDATA[".$fault->faultcode."]]></faultcode>";
        echo "\n<faultstring><![CDATA[".$fault->faultstring."]]></faultstring>";
        echo "\n<error>" .$Return['status'] ."</error>";
        echo "\n</otx>";
    exit(1);
    }

    return true;
}


if (! empty($_GET)) {

    if (! otx_query()) {
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found",true,404);
    exit(1);
    }

    return true;
}


$_status = ""; $oo = _soffice($_status);
$load = $oo['load'];
/*
header("content-type: application/xml; charset=UTF-8");
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
echo "\n<otx type=\"welcome\">\n";
echo "\n<soffice>";
echo "\n<status>" .$_status ."</status>";
echo "\n<loadaverage>";
//Returns three samples representing the average system load (the number of processes in the system run queue) over the last 1, 5 and 15 minutes, respectively. 
echo "\n<last minutes='1'>" .$load[0] ."</last>";
echo "\n<last minutes='5'>" .$load[1] ."</last>";
echo "\n<last minutes='15'>" .$load[2]. "</last>";
echo "\n</loadaverage>";
echo "\n<pid>" .$oo['pid']. "</pid>";
echo "\n<mem>" .$oo['mem']. "</mem>";
echo "\n</soffice>";
echo "\n</otx>";
*/
include('soap/tpl/otx.html');
return true;

#EOF