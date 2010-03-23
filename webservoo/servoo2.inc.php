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
ini_set('soap.wsdl_cache_ttl', "3600");
ini_set('soap.wsdl_cache_limit', "3600");


# --- webservoo --------------------------------------------------------------

// OTX URI Location
if (! defined('__OTX_URI__'))   define('__OTX_URI__',    "http://ccsdrv10.in2p3.fr/otx/");
// OTX ROOT PATH Location
if (! defined('__OTX_PWD__'))   define('__OTX_PWD__',    "/data/www/otx/");

define('__WEBSERVOO_LOCATION__',    __OTX_URI__);
define('__WEBSERVOO_WSDL__',        __OTX_URI__."?wsdl");
define('__WEBSERVOO_LOG__',         __OTX_PWD__."CACHE/tmp/otx.log");
define('__WEBSERVOO_ERRORLOG__',    __OTX_PWD__."CACHE/tmp/otx.error.log");
define('__WEBSERVOO_ATTACHMENT__',  "/tmp/document.source");
define('__WEBSERVOO_SCHEMA__',      "/tmp/model.xml");
define('__WEBSERVOO_LOCK__',        "/tmp/webservoo.lock");

# --- servel  ----------------------------------------------------------------

define('__SERVEL_SERVER__',     __OTX_URI__);
define('__SERVEL_PORT__',       ":80");
define('__SERVEL_INC__',        __OTX_PWD__."webservoo/servel/inc/");
define('__SERVEL_LIB__',        __OTX_PWD__."webservoo/servel/lib/");
define('__SERVEL_TMP__',        __OTX_PWD__."CACHE/tmp/"); 
define('__SERVEL_CACHE__',      __OTX_PWD__."CACHE/");
define('__SERVEL_CACHETIME__',  3600*24*28);

# --- soofice ----------------------------------------------------------------
define("__SOFFICE_PYTHONPATH__",    "/opt/openoffice.org3/program/python");


# debug
define('__DEBUG__', __OTX_PWD__."CACHE/tmp/otx.debug.xml");
# dump
define('__DUMP__', __OTX_PWD__."CACHE/tmp/otx.dump.txt");
?>