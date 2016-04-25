<?php
/**
 * @package OTX
 * @copyright Centre pour L'édition Électronique Ouverte
 * @licence http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 **/

/** OTX Authentication **/
function otx_auth() {
    if (isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW'])) {
        $login = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];


        $config = OTXConfig::singleton();
        $db = new PDO($config->db->dsn, $config->db->user, $config->db->password);

        $row = $db->query("SELECT password FROM users WHERE username=" . $db->quote($login))->fetch(); 

	$user_password = $row['password'];

        if (crypt($password, $user_password) !== $user_password)
        {
            header('WWW-Authenticate: Basic realm="OTX Realm"');
            header('HTTP/1.0 401 Unauthorized');
            die();
        }
    }
}
