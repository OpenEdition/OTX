<?php
/**
 * index.php
 * PHP >= 5.2
 * @author Nicolas Barts
 * @copyright 2008-2009, CLEO/Revues.org
 * @licence http://www.gnu.org/copyleft/gpl.html
**/
header("content-type: application/xml");
ini_set("max_execution_time", "180");
ini_set("max_input_time", "180");
ini_set("post_max_size", "32M");
ini_set("upload_max_filesize", "32M");
ini_set("memory_limit", "256M");
set_time_limit(0);

if(file_exists("Devel/otix/devel.inc.php"))
    include_once('Devel/otix/devel.inc.php');  // DEVEL(debug) MODE
else 
    include_once('webservoo/servoo2.inc.php');

if(!class_exists('WebServoo', FALSE))
    require_once('webservoo/webservoo.class.php');


if (!empty($_GET)) {
    if (isset($_GET['wsdl'])) {
        readfile('./webservoo.wsdl');
        die();
    }
    if (isset($_GET['debug'])) {
        header("content-type: application/xml");
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<ul class="Servel">';
        readfile(__DEBUG__);
        echo '</ul>';
        die();
    }
}
else {
# create the server instantiation
    try {
        $options = array();
        $options['trace'] = true;
        $options['soap_version'] = SOAP_1_2;
        $options['exceptions'] = true;
        $options['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 5;
        $options['encoding'] = SOAP_LITERAL;
        $wsdl = __WEBSERVOO_WSDL__;

        $WebServOO = new SoapServer($wsdl, $options);
        # Définit la classe qui gère les requêtes SOAP
        $WebServOO->setClass('WebServoo');


	# In order to avoid using PHP-SOAP's default no HTTP_RAW_POST_DATA fault,
	# we will expressly return this fault for no request (eg from a browser)
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		$WebServOO->setPersistence(SOAP_PERSISTENCE_SESSION);
		$WebServOO->handle();
	} 
        else {
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


# ---
function _soffice(&$status) {
    $ps = array();
    $_output = array(); $_returnvar = -1;
    $result = "" .exec("ps aux | grep soffice.bin | grep -v grep | grep -v su 2>&1", $_ouput, $_returnvar);
    if ($result=="" OR $_returnvar==1) {
        $status = "down";
/*
        $command = '/opt/openoffice.org2.4/program/soffice -headless -accept="socket,host=127.0.0.1,port=8100;urp;" -nofirststartwizard';
        $result = shell_exec("$command >/tmp/soffice.log &");
        echo "<result>$result</result>";
        echo "<returnvar>$_returnvar</returnvar>";
        echo "<output><![CDATA["; var_dump($_output); echo "]]></output>";
        $status = "start";
*/
    }
    else {
        $status = "running";
    }

    $ps['cpu'] = exec("ps aux | grep soffice.bin | grep -v grep | grep -v su | awk {'print $3'}");
    $ps['mem'] = exec("ps aux | grep soffice.bin | grep -v grep | grep -v su | awk {'print $4'}");
    $ps['pid'] = $pid = exec("ps aux | grep soffice.bin | grep -v grep | grep -v su | awk {'print $2'}");
    if (/*$ps['cpu'] < 5 AND*/ $ps['mem'] > 75) {
        $status = "to be restarted";
/*
        $status = "kill";
        $result = "" .exec("kill $pid 2>&1", $_ouput, $_returnvar);
        echo "<result>$result</result>";
        echo "<returnvar>$_returnvar</returnvar>";
        echo "<output><![CDATA["; var_dump($_output); echo "]]></output>";
*/
    }

    return $ps;
}

?>