<?php
/**
 * @package OTX
 * @copyright Centre pour L'édition Électronique Ouverte
 * @licence http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 **/


class OTXConfig {

    private static $_instance_;
    private $_configfile = "otx.config.xml";
    private $_config;
    
    public function __construct() {
	is_readable($this->_configfile) || die("{$this->_configfile} is not readable!\n");
	$this->_config = simplexml_load_file($this->_configfile);
	FALSE !== $this->_config || die("{$this->_configfile} is not xml parsable!\n");
	return $this->_config;
    }
    
    public function __get($property){
    	if(isset($this->_config->$property))
    		return $this->_config->$property;
	return NULL;
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
