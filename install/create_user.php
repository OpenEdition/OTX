<?php

if (php_sapi_name() !== 'cli') die("Execution not allowed");

if(sizeof($argv) !== 3) die("usage : php install/create_user.php [username] [password]\n");

ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR );
require_once('adodb/adodb.inc.php');
require_once('OTXConfig.class.php');

$username = $argv[1];
$password = $argv[2];

$config = OTXConfig::singleton();
$db = ADONewConnection("pdo");

$dbh = new PDO($config->db);

$db->connect($config->db);

$password_crypted = crypt($password);

$db->execute('INSERT INTO users (username, password) VALUES(' . $db->quote($username) . ', ' . $db->quote($password_crypted) . ')');

die("User created successfully\n");
