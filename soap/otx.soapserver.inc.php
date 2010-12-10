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
ini_set('soap.wsdl_cache_dir', "/tmp");
ini_set('soap.wsdl_cache', "WSDL_CACHE_BOTH");
ini_set('soap.wsdl_cache_ttl', 1);
ini_set('soap.wsdl_cache_limit', 1);

#EOF