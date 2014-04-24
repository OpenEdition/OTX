<?php
/**
 * @package OTX
 * @copyright Centre pour L'édition Électronique Ouverte
 * @licence http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 **/

if (php_sapi_name() !== 'cli') die("Execution not allowed");

if(sizeof($argv) !== 3) die("usage : php install/create_user.php [username] [password]\n");

ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR );
require_once('OTXConfig.class.php');

$username = $argv[1];
$password = $argv[2];

$config = OTXConfig::singleton();
$db = new PDO($config->db['dsn'], $config->db['user'], $config->db['password']);

$password_crypted = crypt($password);

$ok = $db->query('INSERT INTO users (username, password) VALUES(' . $db->quote($username) . ', ' . $db->quote($password_crypted) . ')');
if (!$ok) {
	$error = var_export($db->errorInfo(), true);
	die("ERREUR:\n$error\n");
} else {
	die("User created successfully\n");
}
