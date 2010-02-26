<?php
/**
 * servoo2.inc.php
 * PHP >= 5.2
 * @author Nicolas Barts
 * @copyright 2008-2009, CLEO/Revues.org
 * @licence http://www.gnu.org/copyleft/gpl.html
**/
error_reporting(E_ALL);

# --- soap cache--------------------------------------------------------------
// cf http://fr.php.net/manual/fr/soap.configuration.php
ini_set('soap.wsdl_cache_enabled', TRUE);
ini_set('soap.wsdl_cache_enabled', '1');
ini_set('soap.wsdl_cache_dir', "/tmp");
ini_set('soap.wsdl_cache', "WSDL_CACHE_BOTH");
ini_set('soap.wsdl_cache_ttl', "60");
ini_set('soap.wsdl_cache_limit', "60");


# --- webservoo --------------------------------------------------------------

define('__WEBSERVOO_LOCATION__',    "http://ccsdrv10.in2p3.fr/otx/");
define('__WEBSERVOO_WSDL__',        "http://ccsdrv10.in2p3.fr/otx/?wsdl");
define('__WEBSERVOO_ATTACHMENT__',  "/tmp/document.source");
define('__WEBSERVOO_SCHEMA__',      "/tmp/model.xml");
define('__WEBSERVOO_LOG__',         "/data/www/_SERVEL/tmp/otx.log");
define('__WEBSERVOO_ERRORLOG__',    "/data/www/_SERVEL/tmp/otx.error.log");
define('__WEBSERVOO_LOCK__',        "/tmp/webservoo.lock");


# --- servel  ----------------------------------------------------------------

define('__SERVEL_SERVER__',     "http://otx.revues.org/");
define('__SERVEL_PORT__',       ":80");
define('__SERVEL_INC__',        "/data/www/otx/webservoo/servel/inc/");
define('__SERVEL_LIB__',        "/data/www/otx/webservoo/servel/lib/");
define('__SERVEL_TMP__',        "/data/www/_SERVEL/tmp/"); 
define('__SERVEL_CACHE__',      "/data/www/_SERVEL/CACHE/");
define('__SERVEL_CACHETIME__',  60); // TODO


# debug
define('__DEBUG__', "/data/www/_SERVEL/tmp/otx.debug.xml");
define('__DUMP__', "/data/www/_SERVEL/tmp/otx.dump.txt");
?>