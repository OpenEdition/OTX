<?php
/**
 * otx.php
 * @author Nicolas Barts
 * @copyright 2010, CLEO/Revues.org
 * @licence http://www.gnu.org/copyleft/gpl.html
**/

register_shutdown_function('otx_shutdown');



/** OTX Authentication **/
function otx_check($login, $password) {

    if ($login!=="otx" or $password!=="5e41921ba44c61090abef994fff2cc0d") {
        return false;
    }

    return true;
}

/** OTX register_shutdown_function callback **/
function otx_shutdown() {
    // logout 
    $_SESSION['auth'] = null;
    $_SESSION = array();
    unset($_COOKIE[session_name()]);
    @session_destroy();
}





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