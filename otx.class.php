<?php

/** OTX Authentication **/
function otx_auth() {
    if (isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW'])) {
        $login = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];


        $config = OTXConfig::singleton();
        $db = ADONewConnection("sqlite3");

        $db->connect($config->dbpath);

        $user_password = $db->GetOne("SELECT password FROM users WHERE username='" . $db->escape($login)."'"); 

        if (crypt($password, $user_password) != $user_password)
        {
            header('WWW-Authenticate: Basic realm="OTX Realm"');
            header('HTTP/1.0 401 Unauthorized');
            die();
        }
    }
}
