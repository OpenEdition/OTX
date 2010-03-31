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
    if(file_exists("Devel/otix/devel.inc.php"))include_once('Devel/otix/devel.inc.php');else// DEVEL(debug) MODE
include_once('webservoo/servoo2.inc.php');
require_once('otx.php');
require_once('webservoo/webservoo.class.php');


/*
$realm = 'Restricted area';
//utilisateur => mot de passe
$users = array('otx'=>"opentext", 'guest'=>'guest');

if ( empty($_SERVER['PHP_AUTH_DIGEST'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Digest realm="'.$realm.'",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');

    die("Auhtentication failed! Logout.");
}
// analyse la variable PHP_AUTH_DIGEST
if ( !($data=http_digest_parse($_SERVER['PHP_AUTH_DIGEST'])) || !isset($users[$data['username']]))
    die('Mauvaise Pièce d\'identité!');

// Génération de réponse valide
$A1 = md5($data['username'] . ':' . $realm . ':' . $users[$data['username']]);
$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);

if ($data['response'] != $valid_response)
    die('Mauvaise Pièce d\'identitée!');

// ok, utilisateur & mot de passe valide
//echo 'Vous êtes identifié en tant que : ' . $data['username'];
*/

if (!empty($_GET)) {
    if (isset($_GET['wsdl'])) {
        header("content-type: application/xml; charset=UTF-8",true);
        readfile('./webservoo/webservoo.wsdl');
        die();
    }
    if ( isset($_GET['admin'])) {
        $admin = $_GET['admin'];
        switch ($admin) {
            // TODO !
            case 'log':
                header("Content-Type: text/plain; charset=UTF-8",true); 
                readfile(__OTX_PWD__."CACHE/tmp/otx.log");
                break;
            case 'report':
                header("content-type: application/xml; charset=UTF-8",true);
                readfile(__OTX_PWD__."CACHE/tmp/report.xml");
                break;
            case 'debug':
                header("Content-Type: text/html; charset=UTF-8",true); 
                readfile(__DEBUG__);
                break;
            case 'dump':
                header("Content-Type: text/plain; charset=UTF-8",true); 
                readfile(__DUMP__);
                break;
            default:
                header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found",true,404);
                header("Content-Type: text/plain; charset=UTF-8"); 
                break;
        }
        die();
    }
}
else {

# create the server instantiation
    try {
        $options = array();
        $options['trace'] = TRUE;
        $options['soap_version'] = SOAP_1_2;
        $options['exceptions'] = TRUE;
        $options['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 5;
        $options['encoding'] = SOAP_LITERAL;
        $wsdl = __WEBSERVOO_WSDL__;

        //for persistent session
        session_start();

        //service
        $WebServOO = new SoapServer($wsdl, $options);
        # Définit la classe qui gère les requêtes SOAP
        $WebServOO->setClass('WebServoo');

	if ($_SERVER["REQUEST_METHOD"] == "POST") {

            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                header('WWW-Authenticate: Basic realm="OTX Realm"');
                header('HTTP/1.0 401 Unauthorized');
            exit();
            }

            //otx_check
            $login = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];

            if (! otx_check($login, $password)) {
                header('WWW-Authenticate: Basic realm="OTX Realm"');
                header('HTTP/1.0 401 Unauthorized');
            exit();
            }

            $WebServOO->setPersistence(SOAP_PERSISTENCE_SESSION);
            $WebServOO->handle();
        }
        else {
            header("content-type: application/xml; charset=UTF-8");
            echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
            echo "\n<servoo type=\"welcome\">\n";
            echo "\n<handle>";
            $functions = $WebServOO->getFunctions();
            foreach($functions as $func) {
                echo "\n<function>". $func ."</function>";
            }
            echo "\n</handle>";
            //echo "<soapfault><![CDATA[" .$WebServOO->fault('Client', 'Invalid Request') ."]]></soapfault>";
            $_status = ""; $ooo = _soffice($_status);
            echo "\n<soffice>";
            echo "\n<status>" .$_status ."</status>";
            echo "\n<pid>" .$ooo['pid'] ."</pid>";
            echo "\n<cpu>" .$ooo['cpu'] ."</cpu>";
            echo "\n<mem>" .$ooo['mem'] ."</mem>";
            echo "\n</soffice>";
            echo "\n</servoo>";
	}
    } 
    catch (SoapFault $fault) {
            echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
            echo "\n<servoo type=\"SoapFault\">";
            echo "\n<faultcode><![CDATA[".$fault->faultcode."]]></faultcode>";
            echo "\n<faultstring><![CDATA[".$fault->faultstring."]]></faultstring>";
            /*
            echo "<lastrequest><![CDATA[" .$WebClient->__getLastRequest() ."]]></lastrequest>\n";
            echo "<lastresponse><![CDATA[" .$WebClient->__getLastResponse() ."]]></lastresponse>\n";
            echo "<vardump><![CDATA["; var_dump($fault); echo "]]></vardump>";
            */
            echo "\n<error>" .$Return['status'] ."</error>";
            echo "\n</servoo>";
            die(FALSE);
    }

    return TRUE;
}


?>