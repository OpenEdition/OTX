<?php
/**
 * @package OTX
 * @copyright Centre pour L'édition Électronique Ouverte
 * @licence http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 **/

require_once 'Config.php';

class OTXConfig {

	private static $_instance_;
	private $_configfile = "otx.config.xml";
	private $_config;
	private $_root;
	private $_array;
    
    private function __construct() {
    	$this->_config = new Config();
    	$this->_root   = @$this->_config->parseConfig($this->_configfile, 'XML')
							or error_log("Warning: $php_errormsg");
    	$this->_array  = $this->_root->toArray();
    }
    
    public function __get($property){
    	if(isset($this->_array['root']['config'][$property]))
    		return $this->_array['root']['config'][$property];
    }
    
    public static function singleton(){
        if (!isset(self::$_instance_)) {
            // First invocation only.
            $class = __CLASS__;
            self::$_instance_ = new $class();
            return self::$_instance_;
        }
        else {
            return self::$_instance_;
        }
    }
}
?>