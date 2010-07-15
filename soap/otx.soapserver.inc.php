<?php
/**
 * servoo2.inc.php
 * PHP >= 5.2
 * @author Nicolas Barts
 * @copyright 2010, CLEO/Revues.org
 * @licence http://www.gnu.org/copyleft/gpl.html
**/

    error_reporting(-1);

# --- soap cache--------------------------------------------------------------
// cf http://fr.php.net/manual/fr/soap.configuration.php
ini_set('soap.wsdl_cache_enabled', false);
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_dir', "/tmp");
ini_set('soap.wsdl_cache', "WSDL_CACHE_BOTH");
ini_set('soap.wsdl_cache_ttl', 1);
ini_set('soap.wsdl_cache_limit', 1);

# --- SOAP -------------------------------------------------------------------
// OTX URI Location
if (! defined('__OTX_URI__'))   define('__OTX_URI__',    "http://up0.in.revues.org/nicOO/otx/");
// OTX ROOT PATH Location
if (! defined('__OTX_PWD__'))   define('__OTX_PWD__',    "/data/www/nicOO/otx/");

define('__SOAP_LOCATION__',    __OTX_URI__);
define('__SOAP_WSDL__',        __OTX_URI__."?wsdl");
define('__SOAP_LOG__',         __OTX_PWD__."CACHE/tmp/otx.log");
define('__SOAP_ERRORLOG__',    __OTX_PWD__."CACHE/tmp/otx.error.log");
define('__SOAP_ATTACHMENT__',  "/tmp/document.source");
define('__SOAP_SCHEMA__',      "/tmp/model.xml");
define('__SOAP_LOCK__',        "/tmp/otx.lock");

# --- server  ----------------------------------------------------------------
define('__SERVER_URI__',     __OTX_URI__);
define('__SERVER_PORT__',       ":80");
define('__SERVER_INC__',        __OTX_PWD__."soap/server/inc/");
define('__SERVER_LIB__',        __OTX_PWD__."soap/server/lib/");
define('__SERVER_TMP__',        __OTX_PWD__."CACHE/tmp/");
define('__SERVER_CACHE__',      __OTX_PWD__."CACHE/");
define('__SERVER_CACHETIME__',  3600*24*28);

# --- soffice ----------------------------------------------------------------
define("__SOFFICE_PYTHONPATH__",    "/opt/openoffice.org3/program/python");

# debug
define('__DEBUG__', __OTX_PWD__."CACHE/tmp/otx.debug.xml");
# dump
define('__DUMP__',  __OTX_PWD__."CACHE/tmp/otx.dump.txt");

# ADODB
define('__DB_DRIVER__', 'sqlite');
define('__DB_PATH__',   __OTX_PWD__."soap/server/db/servel.db");

#EOF