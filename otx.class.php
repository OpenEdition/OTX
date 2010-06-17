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
        $login = htmlspecialchars($_SERVER['PHP_AUTH_USER'], ENT_COMPAT, 'UTF-8');
        $password = htmlspecialchars($_SERVER['PHP_AUTH_PW'], ENT_COMPAT, 'UTF-8');
        /*
            $_db = new MySQLi('localhost', 'servoo', 'servoo', 'servoo');
            if($_db->connect_error)
                return $this->otxAuthResponse(false);
            if ($stmt = $_db->Prepare('SELECT id, passwd FROM users WHERE username=? AND status>0 LIMIT 1')) {
                $user = array();
                $stmt->bind_param('s', $input->login);
                $stmt->bind_result($id, $passwd);
                if (!$stmt->execute())
                    return false;
                $stmt->fetch();
            } else {
                return false;
            }
        */
        if ($login==="otx" and $password==="5e41921ba44c61090abef994fff2cc0d")
        return true;
    }

    return false;
}


function otx_query() {

    if (isset($_GET['wsdl'])) {
        header("content-type: application/xml; charset=UTF-8",true);
        readfile('./soap/otx.wsdl');
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

    $load = sys_getloadavg();
    if ($load[0] > 80) {
        header('HTTP/1.1 503 Too busy, try again later');
        die('Server too busy. Please try again later.');
    }
    $ps['load'] = $load;

    return $ps;
}

#EOF