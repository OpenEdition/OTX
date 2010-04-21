<?php
/**
 * index.php
 * PHP >= 5.2
 * @author Nicolas Barts
 * @copyright 2008-2009, CLEO/Revues.org
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
include_once('webservoo/servoo2.inc.php');
if(file_exists("Devel/otix/devel.inc.php"))include_once('Devel/otix/devel.inc.php');

require_once('otx.php');
require_once('webservoo/webservoo.class.php');


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
        $wsdl = __WEBSERVOO_WSDL__;
        // service
        $WebServOO = new SoapServer($wsdl, $options);
        $WebServOO->setClass('WebServoo');
        // 
        $WebServOO->setPersistence(SOAP_PERSISTENCE_SESSION);
        $WebServOO->handle();
    } 
    catch (SoapFault $fault) {
        echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        echo "\n<servoo type=\"SoapFault\">";
        echo "\n<faultcode><![CDATA[".$fault->faultcode."]]></faultcode>";
        echo "\n<faultstring><![CDATA[".$fault->faultstring."]]></faultstring>";
        echo "\n<error>" .$Return['status'] ."</error>";
        echo "\n</servoo>";
    exit(1);
    }

    return TRUE;
}


if (! empty($_GET)) {

    if (! otx_query()) {
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found",true,404);
    exit(1);
    }

    return TRUE;
}


$_status = ""; $ooo = _soffice($_status);
header("content-type: application/xml; charset=UTF-8");
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
echo "\n<servoo type=\"welcome\">\n";
echo "\n<soffice>";
echo "\n<status>" .$_status ."</status>";
echo "\n<pid>" .$ooo['pid'] ."</pid>";
echo "\n<cpu>" .$ooo['cpu'] ."</cpu>";
echo "\n<mem>" .$ooo['mem'] ."</mem>";
echo "\n</soffice>";
echo "\n</servoo>";

return TRUE;
?>