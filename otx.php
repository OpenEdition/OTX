<?php
/**
 * otx.php
 * @author Nicolas Barts
 * @copyright 2010, CLEO/Revues.org
 * @licence http://www.gnu.org/copyleft/gpl.html
**/
register_shutdown_function('otx_shutdown');



/** OTX Authentication **/
function otx_auth() {

    if (isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW'])) {
        $login = htmlspecialchars($_SERVER['PHP_AUTH_USER']);
        $password = htmlspecialchars($_SERVER['PHP_AUTH_PW']);

        if ($login==="otx" and $password==="5e41921ba44c61090abef994fff2cc0d")
        return true;
    }

    return false;
}


function otx_query() {

    if (isset($_GET['wsdl'])) {
        header("content-type: application/xml; charset=UTF-8",true);
        readfile('./webservoo/webservoo.wsdl');
        return true;
    }

    if ( isset($_GET['admin'])) {
        $admin = htmlspecialchars($_GET['admin']);
        switch ($admin) {
            case 'log':
                header("Content-Type: text/plain; charset=UTF-8",true);
                readfile(__OTX_PWD__."CACHE/tmp/otx.log");
                return true;
            case 'report':
                header("content-type: application/xml; charset=UTF-8",true);
                readfile(__OTX_PWD__."CACHE/report.up0.xml");
                return true;
            case 'debug':
                header("Content-Type: text/html; charset=UTF-8",true);
                readfile(__DEBUG__);
                return true;
            case 'dump':
                header("Content-Type: text/plain; charset=UTF-8",true);
                readfile(__DUMP__);
                break;
            default:
                return false;
        }
    }

    return false;
}


/** OTX register_shutdown_function callback **/
function otx_shutdown() {
    // logout 
    $_SESSION['auth'] = null;
    $_SESSION = array();
    unset($_COOKIE[session_name()]);
    @session_destroy();
}





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


// fonction pour analyser l'en-tête http auth
function http_digest_parse($txt) {
    // protection contre les données manquantes
    $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
    $data = array();
    $keys = implode('|', array_keys($needed_parts));
 
    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $data[$m[1]] = $m[3] ? $m[3] : $m[4];
        unset($needed_parts[$m[1]]);
    }

    return $needed_parts ? false : $data;
}
*/



function _soffice(&$status) {
    $ps = array();
    $_output = array(); $_returnvar = -1;
    $result = "" .exec("ps aux | grep soffice.bin | grep -v grep | grep -v su 2>&1", $_ouput, $_returnvar);
    if ($result=="" OR $_returnvar==1) {
        $status = "down";
    }
    else {
        $status = "running";
    }

    $ps['pid'] = exec("ps aux | grep soffice.bin | grep -v grep | grep -v su | awk {'print $2'}");
    $ps['cpu'] = exec("ps aux | grep soffice.bin | grep -v grep | grep -v su | awk {'print $3'}");
    $ps['mem'] = exec("ps aux | grep soffice.bin | grep -v grep | grep -v su | awk {'print $4'}");
    if ($ps['mem'] > 75) {
        $status = "to be restarted";
    }

    return $ps;
}


?>