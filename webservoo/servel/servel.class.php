<?php
/**
 * servel.class.php
 * 
 * PHP >= 5.2
 *
 * @author Nicolas Barts
 * @copyright 2008-2009, CLEO/Revues.org
 * @licence http://www.gnu.org/copyleft/gpl.html
**/
include_once('inc/utils.inc.php');


/**
 * Singleton class
-*/
class Servel
{
    // Hold an instance of the Singleton class
    private static $_instance_;

    // Inputs
    protected $input = array('request'=>"", 'mode'=>"", 'modelpath'=>"", 'entitypath'=>"");
    // Outputs
    protected $output = array('status'=>"", 'xml'=>"", 'report'=>"", 'contentpath'=>"", 'lodelxml'=>"");

    protected $meta;
    protected $EModel = array();
    private $EMotx = array();

    private $dom = array();
    private $automatic = array();
    private $rend = array();
    private $rendition = array();
    private $Pnum = 0;
    private $Tnum = 0;

    private $_param = array();
    private $_data = array();
    private $_status="";
    private $_trace="";
    private $_iserror=false;
    private $_isdebug=true;
    private $oostyle = array();
    private $_dbg = 1;

    const _WEBSERVOO_MODELPATH_     = __WEBSERVOO_SCHEMA__;
    const _WEBSERVOO_ENTITYPATH_    = __WEBSERVOO_ATTACHMENT__;
    const _WEBSERVOO_LOCKFILE_      = __WEBSERVOO_LOCK__;
    const _DEBUGFILE_               = __DEBUG__;
    const _SERVEL_CACHETIME_    = __SERVEL_CACHETIME__;
    const _SERVEL_CACHE_        = __SERVEL_CACHE__;
    const _SERVEL_TMP_          = __SERVEL_TMP__;
    const _SERVEL_INC_          = __SERVEL_INC__;
    const _SERVEL_LIB_          = __SERVEL_LIB__;
    const _SERVEL_SERVER_       = __SERVEL_SERVER__;
    const _SERVEL_PORT_         = __SERVEL_PORT__;
    const _SOFFICE_PYTHONPATH_  = __SOFFICE_PYTHONPATH__;


    /** A private constructor; prevents direct creation of object (singleton because) **/
    private function __construct($request="", $mode="", $modelpath="", $entitypath="") {
    error_log("<ul id=\"".date("Y-m-d H:i:s")."\">\n<h3>__construct()</h3>\n", 3, self::_DEBUGFILE_);
        touch(self::_WEBSERVOO_LOCKFILE_);
        $this->input['request'] = $request;
        $this->input['mode'] = $mode;
        $this->input['modelpath'] = $modelpath;
        $this->input['entitypath'] = $entitypath;

        $this->_param['request'] = $request;
        $this->_param['mode'] = $mode;
        $this->_param['modelpath'] = $modelpath;
        $this->_param['sourcepath'] = $entitypath;
        $this->_param['mime'] = "";
        $this->_param['prefix'] = "";
        $this->_param['sufix'] = "";
        $this->_param['odtpath'] = "";
        $this->_param['xmlodt'] = "";
        $this->_param['xmlreport'] = "";
        $this->_param['EMreport'] = array();
        $this->_param['CACHETIME'] = self::_SERVEL_CACHETIME_;
        $this->_param['CACHEPATH'] = self::_SERVEL_CACHE_;
        $this->_param['TMPPATH'] = self::_SERVEL_TMP_;
        $this->_param['INCPATH'] = self::_SERVEL_INC_;
        $this->_param['LIBPATH'] = self::_SERVEL_LIB_;
        $this->_param['SERVERURI'] =  self::_SERVEL_SERVER_;
        $this->_param['SERVERPORT'] = self::_SERVEL_PORT_;
        $this->_param['DEBUGPATH'] = self::_DEBUGFILE_;
    }
    /** Prevent users to clone the instance (singleton because) **/
    public function __clone() {
        $this->_status="Cannot duplicate a singleton !";$this->_iserror=true;
        throw new Exception($this->_status);
    }
    public function __destruct() {
    error_log("\n<h3>__destruct</h3></ul>", 3, self::_DEBUGFILE_);
        @unlink($this->_param['sourcepath']);
/*
        //@unlink($this->_param['DEBUGPATH']);
        $handle=fopen($this->_param['DEBUGPATH'],"a+");
        fwrite($handle,"<ul id=\"".date("Y-m-d H:i:s")."\">\n".$this->_trace."</ul>\n");fflush($handle);
        fclose($handle); @chmod($this->_param['DEBUGPATH'], 0660);

        // TODO if debug mode
        @unlink("/tmp/otx.debug.log");
        $handle=fopen("/tmp/otx.debug.log","w+");
        fwrite($handle,"\n".date("Y-m-d H:i:s")."\n");fflush($handle);
        fwrite($handle, print_r( debug_backtrace(),true));fflush($handle);
        fclose($handle);
*/
        unlink(self::_WEBSERVOO_LOCKFILE_);
    }
    public function __wakeup(){
       if (!self::$_instance_) {
            self::$_instance_ = $this;
       } else {
            trigger_error("Unserializing this instance while another exists voilates the Singleton pattern",
                E_USER_ERROR);
            return null;
        }
    }
    public function __toString() {
        return $this->_status;
    }
    public function __set($key, $value) {
    error_log("<li>__set($key,$value)</li>\n\n", 3, self::_DEBUGFILE_);
        if ( array_key_exists($key, $this->_param)) {
            $this->_param[$key] = $value;
        } else {
            $trace = debug_backtrace();
            trigger_error('Undefined property via __set(): '.$key.' : '.$value.' in '.$trace[0]['file'].' on line '.$trace[0]['line'], E_USER_NOTICE);
            return null;
        }
    }
    public function __get($name) {
    error_log("<li>__get($name)</li>\n\n", 3, self::_DEBUGFILE_);
        if ( array_key_exists($name, $this->_param)) {
            return $this->_param[$name];
        }
        $trace = debug_backtrace();
        trigger_error('Undefined property via __get(): '.$name.' in '.$trace[0]['file'].' on line '. $trace[0]['line'], E_USER_NOTICE);
        return null;
    }

    /**
    * The singleton method
    **/
    public static function singleton($request="", $mode="", $modelpath="", $entitypath="") {
        if (!isset(self::$_instance_)) {
            // First invocation only.
            $class = __CLASS__;
            self::$_instance_ = new $class($request, $mode, $modelpath, $entitypath);
            error_log("<li>Singleton: First invocation only !</li>\n\n", 3, self::_DEBUGFILE_);
            return self::$_instance_;
        }
        else {
            error_log("<li>Singleton: return instance</li>\n\n", 3, self::_DEBUGFILE_);
            return self::$_instance_;
        }
    }



/**
 * just do it !
**/
    public function run() {
    error_log("<h3>run()</h3>\n", 3, self::_DEBUGFILE_);
        $action = $this->_param['mode'];

        $this->params();
        $this->_status = "todo: $action";

        switch ($action) {
            case 'soffice':
            error_log("<li>case soffice</li>\n\n", 3, self::_DEBUGFILE_);
                $this->soffice2odt();
                $this->output['contentpath'] = $this->_param['odtpath'];
                $this->oo2report($this->_param['odtpath']);
                $this->output['report'] = $this->_param['xmlreport'];
                break;
            case 'lodel':
            error_log("<li>case lodel</li>\n\n", 3, self::_DEBUGFILE_);
                $this->soffice2odt();
                $this->oo2report($this->_param['odtpath']);
                $this->output['report'] = _windobclean($this->_param['xmlreport']);
                $this->Schema2OO();
            //$this->otix();
                $this->lodelodt();
                $this->oo2lodelxml();
                $this->oo2xml();
                $this->output['xml'] = _windobclean($this->_param['TEI']);
                $this->output['lodelxml'] = _windobclean($this->_param['lodelTEI']);
                $this->oo2report($this->_param['lodelodtpath']);
                $this->output['report'] = _windobclean($this->_param['xmlreport']);
                $this->output['contentpath'] = $this->_param['lodelodtpath'];
                break;
            case 'cairn':
                $this->_status = "todo: cairn";
                break;
            default:
                $this->_status="error: unknown action ($action)";$this->_iserror=true;
                error_log("<li>! error: {$this->_status}</li>\n", 3, self::_DEBUGFILE_);
                throw new Exception($this->_status);
        }

        $this->_status = "done: $action";
        $this->output['status'] = $this->_status;

sleep(1); //TODO
        return $this->output;
    }



/**
 * dynamic mapping of Lodel EM
**/
    protected function Schema2OO() {
    error_log("<h2>Schema2OO()</h2>\n", 3, self::_DEBUGFILE_);

        $modelpath = $this->_param['modelpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/"."model.xml";
        error_log("<li>ME: $modelpath</li>\n",3,self::_DEBUGFILE_);

        $domxml = new DOMDocument;
        $domxml->encoding = "UTF-8";
        $domxml->resolveExternals = true;
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        if (! $domxml->load($this->_param['modelpath'])) {
            $this->_status="error load model.xml";error_log("<li>{$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }

        # OTX EM test
        if (! strstr($domxml->saveXML(), "<col name=\"otx\">")) {
            // TODO : warning and load a default OTX EM ?!
            $this->_status="error: EM not OTX compliant";error_log("<h1>{$this->_status}</h1>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }

        $Model = array();
        $OOTX = array();
        $nbEmStyle = $nbOtxStyle = 0;
        foreach ($domxml->getElementsByTagName('row') as $node) {
            $value = $keys = $g_otx = '';
            if ($node->hasChildNodes()) {
                $row = array(); $bstyle = false;
                foreach ($node->childNodes as $tag) {
                    if ($tag->hasAttributes()) {
                        foreach ($tag->attributes as $attr) {
                            if ($attr->name === "name") {
                                switch ($attr->value) {
                                    case "name":
                                    case "type":
                                        $value = ''.trim($tag->nodeValue);
                                        break;
                                    case "style":
                                        $keys = ''.trim($tag->nodeValue);
                                        if ($keys == '') continue; // empty : no style defined !
                                        $bstyle = true; 
                                        $row[$attr->value] = $tag->nodeValue;
                                        $nbEmStyle++;
                                        if ($value == '') {
                                            if (! strstr($keys, ",")) {
                                                $Model[$keys] = $keys;
                                            } else {
                                                foreach ( explode(",", $keys) as $key) {
                                                    $key = trim($key);
                                                    /* if (array_key_exists($key, $EModel)) { } */
                                                    $Model[$key] = $key;
                                                }
                                            }
                                        } else {
                                            if (! strstr($keys, ",")) {
                                                $Model[$keys] = $value;
                                            } else {
                                                foreach ( explode(",", $keys) as $key) {
                                                    $key = trim($key);
                                                    /* if (array_key_exists($key, $EModel)) { } */
                                                    $Model[$key] = $value;
                                                }
                                            }
                                        }
                                        break;
                                    case "g_type":
                                    case "g_name":
                                    //$row[$attr->value] = $tag->nodeValue;
                                        break;
                                    case 'surrounding':
                                        $row[$attr->value] = $tag->nodeValue;
                                        break;
                                    case "otx":
                                        $row[$attr->value] = $tag->nodeValue;
                                        $nbOtxStyle++;
                                        $otxkey = $otxvalue = '';
                                        if ( strstr( trim($tag->nodeValue), ":")) {
                                            list($otxkey, $otxvalue) = explode(":", $tag->nodeValue);
                                            $this->EMotx[$otxvalue]['key'] = $otxkey;
                                        } else {
                                            $otxvalue = $tag->nodeValue;
                                        }
                                        if ($otxvalue!= '' and $keys!='') {
                                            if (! strstr($keys, ",")) {
                                                $OOTX[$keys] = $otxvalue;
                                            } else {
                                                foreach ( explode(",", $keys) as $key) {
                                                    $key = trim($key);
                                                    $OOTX[$key] = $otxvalue;
                                                }
                                            }
                                        }
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }
        error_log("<li>parse EM ok</li>\n", 3, self::_DEBUGFILE_);

        $this->_param['EMreport']['nbLodelStyle'] = $nbEmStyle;
        $this->_param['EMreport']['nbOTXStyle'] = $nbEmStyle;

        foreach ($Model as $key=>$value) {
            // hack pour les styles traduits, eg. style:lang
            $newkey = ''; $lang='';
            if ( preg_match("/:([a-z][a-z])$/", $key)) {
                list($newkey, $lang) = explode(":", $key);
                if ( array_key_exists($newkey, $this->EModel)) {
                    $newkey = '';
                }
            }
            if ( array_key_exists($key, $OOTX)) {
                $otxvalue = $OOTX[$key];
                if ($newkey!= '' and $lang!='')
                    $this->EModel[$newkey] = $otxvalue."-$lang";
                else 
                    $this->EModel[$key] = $otxvalue;
            }
            else {
                if ($newkey!= '' and $lang!='')
                    $this->EModel[$newkey] = $value."-$lang";
                else 
                    $this->EModel[$key] = $value;
            }
        }

        // more++
        $this->EModel['FootnoteSymbol'] = "footnotesymbol";
        $this->EModel['Standard'] = "standard";
        unset($Model);
        unset($OOTX);

        # surrounding
        error_log("<li>surrounding</li>\n",3,self::_DEBUGFILE_);
        $xpath = new DOMXPath($domxml);
        $query = '/lodelEM/table[@name="#_TP_internalstyles"]/datas/row';
        $entries = $xpath->query($query);
        foreach ($entries as $item) {
            if ($item->hasChildNodes()) {
                foreach ($item->childNodes as $child) {
                    $value = $otxkey = $otxvalue = "";
                    if ($child->hasAttributes()) {
                        $attributes = $child->attributes;
                        $attribute = $attributes->getNamedItem("name");
                        $key = $attribute->value;
                        $value = $child->nodeValue;
                        switch ($key) {
                            case "style":
                                break;
                            case "surrounding":
                                $surrounding = $value;
                                break;
                            case "otx":
                                if ($value == '') continue;
                                list($otxkey,$otxvalue) = explode(":", $value);
                                break;
                        }
                    }
                }
                if ($otxkey and $otxvalue) {
                        $this->EMotx[$otxvalue]['key'] = $otxkey;
                        $this->EMotx[$otxvalue]['surround'] = $surrounding;
                }
            }
        }
        // default
        $this->EMotx['standard']['key'] = "text";
        $this->EMotx['standard']['surround'] = "*-";

        error_log("<li>DONE.</li>\n", 3, self::_DEBUGFILE_);
        unset($domxml);
        return true;
    }



/**
 * transformation d'un odt en lodel.odt : format pivot de travail
**/
    protected function lodelodt() {
    error_log("<h3>lodelodt</h3>\n", 3, self::_DEBUGFILE_);
        $cleanup = array('/_20_/', '/_28_/', '/_29_/', '/_5f_/', '/_5b_/', '/_5d_/', '/_32_/', '/WW-/' );

        $odtfile = $this->_param['odtpath'];
        $this->_param['lodelodtpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/".$this->_param['prefix'].".lodel.odt";
        $lodelodtfile = $this->_param['lodelodtpath'];
        if (! copy($odtfile, $lodelodtfile)) {
            $this->_status="error copy file; ".$lodelodtfile;error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        # odt...
        $za = new ZipArchive();
        if (! $za->open($lodelodtfile)) {
            $this->_status="error open ziparchive; ".$lodelodtfile;error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        # ----- office:meta ----------------------------------------------------------
        error_log("<li>office:meta</li>\n\n", 3, self::_DEBUGFILE_);
        if (! $OOmeta=$za->getFromName('meta.xml')) {
            $this->_status="error get meta.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $dommeta = new DOMDocument;
        $dommeta->encoding = "UTF-8";
        $dommeta->resolveExternals = true;
        $dommeta->preserveWhiteSpace = false;
        $dommeta->formatOutput = true;
        if (! $dommeta->loadXML($OOmeta)) {
            $this->_status="error load meta.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-meta.xml";@$dommeta->save($debugfile);
        # cleanup
        $lodelmeta = _windobclean($OOmeta);
        # lodel
        $domlodelmeta = new DOMDocument;
        $domlodelmeta->encoding = "UTF-8";
        $domlodelmeta->resolveExternals = true;
        $domlodelmeta->preserveWhiteSpace = false;
        $domlodelmeta->formatOutput = true;
        if (! $domlodelmeta->loadXML($lodelmeta)) {
            $this->_status="error load lodel-meta.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domlodelmeta->normalizeDocument();
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-meta.lodel.xml";@$domlodelmeta->save($debugfile);

        # ----- office:settings ----------------------------------------------------------
        error_log("<li>office:settings</li>\n\n", 3, self::_DEBUGFILE_);
        if (! $OOsettings=$za->getFromName('settings.xml')) {
            $this->_status="error get settings.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domsettings = new DOMDocument;
        $domsettings->encoding = "UTF-8";
        $domsettings->resolveExternals = true;
        $domsettings->preserveWhiteSpace = false;
        $domsettings->formatOutput = true;
        if (! $domsettings->loadXML($OOsettings)) {
            $this->_status="error load settings.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-settings.xml";@$domsettings->save($debugfile);
        # cleanup
        $lodelsettings = _windobclean($OOsettings);
        # lodel
        $domlodelsettings = new DOMDocument;
        $domlodelsettings->encoding = "UTF-8";
        $domlodelsettings->resolveExternals = true;
        $domlodelsettings->preserveWhiteSpace = false;
        $domlodelsettings->formatOutput = true;
        if (! $domlodelsettings->loadXML($lodelsettings)) {
            $this->_status="error load lodel-settings.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domlodelsettings->normalizeDocument();
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-settings.lodel.xml";@$domlodelsettings->save($debugfile);

        # ----- office:styles ---------------------------------------
        error_log("<li>office:styles</li>\n\n", 3, self::_DEBUGFILE_);
        if (! $OOstyles=$za->getFromName('styles.xml')) {
            $this->_status="error get styles.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domstyles = new DOMDocument;
        $domstyles->encoding = "UTF-8";
        $domstyles->resolveExternals = true;
        $domstyles->preserveWhiteSpace = false;
        $domstyles->formatOutput = true;
        if (! $domstyles->loadXML($OOstyles)) {
            $this->_status="error load styles.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-styles.xml";@$domstyles->save($debugfile);
        # cleanup
        $lodelstyles = preg_replace($cleanup, "", _windobclean($OOstyles));
        # lodel
        $domlodelstyles = new DOMDocument;
        $domlodelstyles->encoding = "UTF-8";
        $domlodelstyles->resolveExternals = true;
        $domlodelstyles->preserveWhiteSpace = false;
        $domlodelstyles->formatOutput = true;
        if (! $domlodelstyles->loadXML($lodelstyles)) {
            $this->_status="error load lodel-styles.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        // lodel-cleanup++
        $this->lodelcleanup($domlodelstyles);
        $domlodelstyles->normalizeDocument(); 
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-styles.lodel.xml";@$domlodelstyles->save($debugfile);

        # ----- office:content -------------------------------------------------------
        error_log("<li>office:content</li>\n\n", 3, self::_DEBUGFILE_);
        if (! $OOcontent=$za->getFromName('content.xml')) {
            $this->_status="error get content.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domcontent = new DOMDocument;
        $domcontent->encoding = "UTF-8";
        $domcontent->resolveExternals = false;
        $domcontent->preserveWhiteSpace = true;
        $domcontent->formatOutput = true;
        if (! $domcontent->loadXML($OOcontent)) {
            $this->_status="error load conntent.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-content.xml";@$domcontent->save($debugfile);
        # cleanup
        $lodelcontent = preg_replace($cleanup, "", _windobclean($OOcontent));
        # lodel
        $domlodelcontent = new DOMDocument;
        $domlodelcontent->encoding = "UTF-8";
        $domlodelcontent->resolveExternals = false;
        $domlodelcontent->preserveWhiteSpace = true;
        $domlodelcontent->formatOutput = true;
        if (! $domlodelcontent->loadXML($lodelcontent)) {
            $this->_status="error load lodel-content.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        // lodel-cleanup++
        $this->lodelcleanup($domlodelcontent);
        //
        $this->lodelpictures($domlodelcontent, $za);
        $domlodelcontent->normalizeDocument();
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-content.lodel.xml";@$domlodelcontent->save($debugfile);

        // 1. office:automatic-styles
        $this->ooautomaticstyles($domlodelcontent);
        // 2. office:styles
        $this->oostyles($domlodelstyles);

        # LodelODT
        if (! $za->addFromString('meta.xml', $domlodelmeta->saveXML())) {
            $this->_status="error ZA addFromString lodelmeta";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        if (! $za->addFromString('settings.xml', $domlodelsettings->saveXML())) {
            $this->_status="error ZA addFromString lodelsettings";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        if (! $za->addFromString('styles.xml', $domlodelstyles->saveXML())) {
            $this->_status="error ZA addFromString lodelstyles";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        if (! $za->addFromString('content.xml', $domlodelcontent->saveXML())) {
            $this->_status="error ZA addFromString lodelcontent";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $za->close();

        # lodel fodt (flat ODT)
        $xmlfodt = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<office:document 
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" 
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" 
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" 
    xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" 
    xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" 
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" 
    xmlns:xlink="http://www.w3.org/1999/xlink" 
    xmlns:dc="http://purl.org/dc/elements/1.1/" 
    xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" 
    xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" 
    xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" 
    xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0" 
    xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0" 
    xmlns:math="http://www.w3.org/1998/Math/MathML" 
    xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" 
    xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0" 
    xmlns:config="urn:oasis:names:tc:opendocument:xmlns:config:1.0" 
    xmlns:ooo="http://openoffice.org/2004/office" 
    xmlns:ooow="http://openoffice.org/2004/writer" 
    xmlns:oooc="http://openoffice.org/2004/calc" 
    xmlns:dom="http://www.w3.org/2001/xml-events" 
    xmlns:xforms="http://www.w3.org/2002/xforms" 
    xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
    xmlns:field="urn:openoffice:names:experimental:ooxml-odf-interop:xmlns:field:1.0" 
    office:version="1.1" office:mimetype="application/vnd.oasis.opendocument.text">
EOD;
        // fodt:meta
        $xmlmeta = explode("\n", $domlodelmeta->saveXML());
        array_shift($xmlmeta); array_shift($xmlmeta);
        array_pop($xmlmeta); array_pop($xmlmeta);
        // fodt:settings
        $xmlsetttings = explode("\n", $domlodelsettings->saveXML());
        array_shift($xmlsetttings); array_shift($xmlsetttings);
        array_pop($xmlsetttings); array_pop($xmlsetttings);
        // fodt:styles
        $xmlstyles = explode("\n", $domlodelstyles->saveXML());
        array_shift($xmlstyles); array_shift($xmlstyles);
        array_pop($xmlstyles); array_pop($xmlstyles);
        $fodtstyles = preg_replace("/<office:automatic-styles.+?office:automatic-styles>/s", "", implode("\n", $xmlstyles));
        // fodt:content
        $xmlcontent = explode("\n", $domlodelcontent->saveXML());
        array_shift($xmlcontent); array_shift($xmlcontent);
        array_pop($xmlcontent); array_pop($xmlcontent);
        $fodtcontent = preg_replace("/<office:font-face-decls.*?office:font-face-decls>/s", "", implode("\n", $xmlcontent));
        // fodt
        $xmlfodt .= implode("\n", $xmlmeta) ."\n";
        $xmlfodt .= implode("\n", $xmlsetttings) ."\n";
        $xmlfodt .= $fodtstyles ."\n";
        $xmlfodt .= $fodtcontent ."\n";
        $xmlfodt .= "</office:document>";
        $this->_param['xmlLodelODT'] = $xmlfodt;
        // fodt xml
        $domfodt = new DOMDocument;
        $domfodt->encoding = "UTF-8";
        $domfodt->resolveExternals = true;
        $domfodt->preserveWhiteSpace = false;
        $domfodt->formatOutput = true;
        if (! $domfodt->loadXML($xmlfodt)) {
            $this->_status="error load fodt xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domfodt->normalizeDocument();
        $debugFile=$this->_param['TMPPATH'].$this->_dbg++."-lodel.fodt.xml";@$domfodt->save($debugFile);

        # oo to lodeltei xslt
        $xslfilter = $this->_param['INCPATH']."oo2lodeltei.xsl";
        $xsl = new DOMDocument;
        if (! $xsl->load($xslfilter)) {
            $this->_status="error load xsl ($xslfilter)";error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);
        if (! $teifodt=$proc->transformToXML($domfodt)) {
            $this->_status="error transform xslt";error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }

        $domteifodt = new DOMDocument;
        $domteifodt->encoding = "UTF-8";
        $domteifodt->resolveExternals = true;
        $domteifodt->preserveWhiteSpace = false;
        $domteifodt->formatOutput = true;
        if (! $domteifodt->loadXML($teifodt)) {
            $this->_status="error load teifodt xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domteifodt->normalizeDocument();
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-fodt.teilodel.xml";@$domteifodt->save($debugfile);

        $this->dom['teifodt'] = $domteifodt;
//        $this->lodeltei($domteifodt);
        return true;
    }



/**
 * transformation d'un lodel-odt en lodel-xml ( flat TEI... [raw mode] )
**/
    protected function oo2lodelxml() {
    error_log("<h3>oo2lodelxml()</h3>\n", 3, self::_DEBUGFILE_);

        $dom = $this->dom['teifodt'];

        $tagsdecl = array();
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('tei', 'http://www.tei-c.org/ns/1.0');

        $entries = $xpath->query("//tei:hi[@rendition]");
        foreach ($entries as $item) {
            if ( preg_match("/^(#T\d+)$/", $item->getAttribute("rendition"), $match)) {
                $rendition = $match[1];
                if ( isset($this->rendition[$rendition])) {
                    // xml:lang ?
                    if ($this->rendition[$rendition]['lang']!='') {
                        $lang = $this->rendition[$rendition]['lang'];
                        $item->setAttribute("xml:lang", $lang);
                    }
                    // css style
                    if ($this->rendition[$rendition]['rendition']!='') {
                        $tagsdecl[$rendition] = $this->rendition[$rendition]['rendition'];
                    } else {
                        $item->removeAttribute("rendition");
                    }
                }
            }
        }

        $entries = $xpath->query("//tei:p[@rendition] | //tei:s[@rendition]");
        foreach ($entries as $item) {
            if ( preg_match("/^(#P\d+)$/", $item->getAttribute("rendition"), $match)) {
                $value = $match[1];
                // rend ?
                if ( isset($this->automatic[$value]) && $this->automatic[$value]!="standard") {
                    $rend = $this->automatic[$value];
                    $item->setAttribute("rend", $rend);
                }
                else {
                    if ( isset($this->rendition[$value])) {
                        // xml:lang ?
                        if ($this->rendition[$value]['lang']!='') {
                            $lang = $this->rendition[$value]['lang'];
                            $item->setAttribute("xml:lang", $lang);
                        }
                        // css style
                        if ($this->rendition[$value]['rendition']!='') {
                            $tagsdecl[$value] = $this->rendition[$value]['rendition'];
                        } else {
                            $item->removeAttribute("rendition");
                        }
                    }
                }
            }
        }

        $entries = $xpath->query("//tei:hi[@rend]");
        foreach ($entries as $item) {
            $value = $item->getAttribute("rend");
            if ( isset($this->automatic[$value])) {
                $key = $this->automatic[$value];
                $rendition = $value.$key;
                if ( isset($this->rendition[$rendition])) {
                    // xml:lang ?
                    if ($this->rendition[$rendition]['lang']!='') {
                        $lang = $this->rendition[$rendition]['lang'];
                        $item->setAttribute("xml:lang", $lang);
                    }
                    // css style
                    if ($this->rendition[$rendition]['rendition']!='') {
                        $tagsdecl[$key] = $this->rendition[$rendition]['rendition'];
                        $item->setAttribute("rendition", $key);
                    }
                }
            }
        }

        $entries = $xpath->query("//tei:p[@rend]");
        foreach ($entries as $item) {
            $rend = $item->getAttribute("rend");
            $key = '';
            if ($item->getAttribute("rendition")) {
                $key = $item->getAttribute("rendition");
            } else if ( isset($this->automatic[$rend])) {
                $key = $this->automatic[$rend];
            }
            $rendition = $rend.$key;
            if ( isset($this->rendition[$rendition])) {
                // xml:lang ?
                if ($this->rendition[$rendition]['lang']!='') {
                    $lang = $this->rendition[$rendition]['lang'];
                    $item->setAttribute("xml:lang", $lang);
                }
                // css style
                if ($this->rendition[$rendition]['rendition']!='') {
                    $tagsdecl[$key] = $this->rendition[$rendition]['rendition'];
                    $item->setAttribute("rendition", $key);
                } else {
                    $item->removeAttribute("rendition");
                }
            }
        }

        foreach ($tagsdecl as $key=>$value) {
            if ( preg_match("/^#P(\d+)$/", $key, $match)) {
                $Pdecl[$match[1]] = $value;
                continue;
            }
            if ( preg_match("/^#T(\d+)$/", $key, $match)) {
                $Tdecl[$match[1]] = $value;
                continue;
            }
        }
        ksort($Pdecl); ksort($Tdecl);

        $header = $dom->getElementsByTagName('teiHeader')->item(0);
        $newnode = $dom->createElement("encodingDesc");
        $encodingDesc = $header->appendChild($newnode);
        $newnode = $dom->createElement("tagsDecl");
        $tagsDecl = $encodingDesc->appendChild($newnode);
        foreach ($Pdecl as $key=>$value) {
            $newnode = $dom->createElement("rendition", $value);
            $rendition = $tagsDecl->appendChild($newnode);
            $rendition->setAttribute('xml:id', "P".$key);
            $rendition->setAttribute('scheme', 'css');
        }
        foreach ($Tdecl as $key=>$value) {
            $newnode = $dom->createElement("rendition", $value);
            $rendition = $tagsDecl->appendChild($newnode);
            $rendition->setAttribute('xml:id', "T".$key);
            $rendition->setAttribute('scheme', 'css');
        }

        # surrounding internalstyles
        $entries = $xpath->query("//tei:front"); $front = $entries->item(0);
        $entries = $xpath->query("//tei:body"); $body = $entries->item(0);
        $entries = $xpath->query("//tei:back"); $back = $entries->item(0);

        $entries = $xpath->query("//tei:body/tei:*");
        $current = $prev = $next = array();
        $newsection = $section = "";
        $newbacksection = $backsection = "";
        foreach ($entries as $item) {
            // prev
            $item->previousSibling ? $previtem=$this->greedy($item->previousSibling) : $previtem=null;
            // current
            $current = $this->greedy($item);
            // next
            $item->nextSibling ? $nextitem=$this->greedy($item->nextSibling) : $nextitem=null;

            if ($current == null) { // normal paragraph
                $newsection = "body";
            } else {
                if ( isset($current['section'])) {
                    $newsection = $current['section'];
                }
                if ($newsection == "back") {
                    if ( isset($current['rend'])) {
                        $newbacksection = $current['rend'];
                    }
                }
            }
            if ( isset($current['surround'])) { 
                $surround = $current['surround'];
                switch($surround) {
                    case "-*":
                        if ( isset($previtem['section'])) {
                            $newsection = $previtem['section'];
                            if ($newsection=="back" and isset($previtem['rend'])) {
                                $newbacksection = $previtem['rend'];
                            }
                        }
                        break;
                    case "*-":
                        if ( isset($nextitem['section'])) {
                            $newsection = $nextitem['section'];
                            if ($newsection=="back" and isset($nextitem['rend'])) {
                                $newbacksection = $nextitem['rend'];
                            }
                        }
                        break;
                }
            }
            if ($section!==$newsection or $backsection!==$newbacksection) { // new section
                $section = $newsection;
                switch ($section) {
                    case 'head';
                        $div = $dom->createElement("div");
                        $div->setAttribute('rend', "LodelMeta");
                        $front->appendChild($div);
                        break;
                    case 'body';
                        $div = $body;
                        break;
                    case 'back';
                        if ($backsection !== $newbacksection) {
                            $backsection = $newbacksection;
                            switch($backsection) {
                                case 'appendix':
                                    $div = $dom->createElement("div");
                                    $div->setAttribute('rend', "LodelAppendix");
                                    $back->appendChild($div);
                                    break;
                                case 'bibliography':
                                    $div = $dom->createElement("div");
                                    $div->setAttribute('rend', "LodelBibliography");
                                    $back->appendChild($div);
                                    break;
                            }
                        }
                        break;
                }
            }
            if ($backsection and $backsection!=$current['rend']) {
                $item->setAttribute('rend', "$backsection-{$current['rend']}");
                error_log("<li>backsection = $backsection-{$current['rend']}</li>\n",3,self::_DEBUGFILE_);
            }
            $div->appendChild($item);
        }

        # <hi> cleanup (tag hi with no attribute)
            // <hi> to <nop> ...
        $entries = $xpath->query("//tei:hi"); 
        foreach ($entries as $item) {
            if (! $item->hasAttributes()) {
                $parent = $item->parentNode;
                $newitem = $dom->createElement("nop", $item->nodeValue);
                if (! $parent->replaceChild($newitem, $item)) {
                    $this->_status="error replaceChild";error_log("<h1>! {$this->_status}</h1>\n",3,self::_DEBUGFILE_);
                    throw new Exception($this->_status);
                }
            }
        }
            // ... and delete <nop>
        $search = array("<nop>", "</nop>");
        $lodeltei = "". str_replace($search, "", $dom->saveXML());

        $dom->encoding = "UTF-8";
        $dom->resolveExternals = true;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($lodeltei);
        $dom->normalizeDocument();
        $debugfile=$this->_param['TMPPATH']."lodeltei.xml";@$dom->save($debugfile);
        $this->_param['xmloutputpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/".$this->_param['prefix'].".lodeltei.xml";
        $dom->save($this->_param['xmloutputpath']);

        $this->_param['lodelTEI'] = "". $dom->saveXML();
        return true;
    }



/**
 * transformation d'un lodel-odt en xml (TEI P5)
**/
    protected function oo2xml() {
    error_log("<h3>oo2xml()</h3>\n", 3, self::_DEBUGFILE_);
        $teidtd ='xsi:schemaLocation="http://www.tei-c.org/release/xml/tei/custom/schema/xsd/tei_all.xsd';

        $this->_param['xmloutputpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/".$this->_param['prefix'].".teip5.xml";
        $outputFile = $this->_param['xmloutputpath'];

        $xmlLodelODT = $this->_param['xmlLodelODT'];

/*
        # http://revue.revues.org/lodel/sources/entite-XXXX.source");
        $this->uri = str_replace($this->_SERVEL_PORT, "", $this->uri);

        $path = str_replace($this->_SERVEL_SERVER, "", $this->uri, $count);
        if ($count == 1) {
            # barts devel testing
            list($version, $revue, $lodel, $sources, $entite) = explode("/", $path);
            if (! preg_match("/^entite-(\d+).source$/", $entite, $matches)) { 
                $this->status = "error URI syntax; uri=" .$this->uri;
                $this->debuglog .= "<status>" .$this->status ."</status></function>"; $this->_error=TRUE;
                return FALSE;
            }
            $source = trim($matches[1]);
            list($prefix, $ext) = explode(".", $source); trim($prefix);
            $this->outputFile = trim($this->_SERVEL_CACHE.$revue."/".$prefix.".xml");
        }
        else {
            # servoo2 prod
            $path = str_replace("http://", "", $this->uri, $count);
            if ($count != 1) {
                $this->status = "error URI server; uri=" .$this->uri;
                return FALSE;
            }
            list($revurl, $lodel, $sources, $entite) = explode("/", $path);
            if (! preg_match("/^entite-(\d+).source$/", $entite, $matches)) {
                $this->status = "error uri syntax; uri=" .$this->uri;
                return FALSE;
            }
            $source = trim($matches[1]);
            list($revue, $revues, $org) = explode(".", $revurl);
            if ($revue === "www") { // eg. http://www.cybergeo.eu/lodel/sources/entite-XXX.source
                $revue = $revue .$revues .$org;
            }
            list($prefix, $ext) = explode(".", $source); trim($prefix);
            $this->outputFile = trim($this->_SERVEL_CACHE.$revue."/".$prefix.".xml");

            // save the rdf request
            $domRequest = new DOMDocument;
            if (! $domRequest->loadXML($this->request)) {
                $this->status = "error load request"; $this->_error=TRUE;
                return FALSE;
            }
            $requestFile = $this->_SERVEL_TMP.$revue."-".$prefix.".rdf"; @$domRequest->save($requestFile);
        }
*/

        # ----- fichier en cache... ? ----------------------------------------
	if ( $this->_param['mode']!='lodel' AND 
                (is_file($outputFile) AND (time()-filemtime($outputFile) < $this->_param['CACHETIME'])) ) {
            if (! $outputData=file_get_contents($this->_outputFile)) {
                $this->_status="error from cache";error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
                throw new Exception($this->_status);
            }
            $this->_status = "from cache"; // ok!
            $this->TEI = $outputData; // TODO !
	}
	else {
        # ----- ...ou pas ! --------------------------------------------------
	    @mkdir($this->_param['CACHEPATH'], 0755);
	    @mkdir($this->_param['CACHEPATH'].$revue, 0755);

	    $xml = new DOMDocument;
            $xml->encoding = "UTF-8";
            $xml->resolveExternals = true;
            $xml->preserveWhiteSpace = false;
            $xml->formatOutput = true;
	    if (! $xml->loadXML($xmlLodelODT)) {
		$this->_status="error load xml";$this->_iserror=true;error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
                throw new Exception($this->_status);
	    }
            $debugFile=$this->_param['TMPPATH'].$this->_dbg++."-odt.lodel.xml";@$xml->save($debugFile);
            $xml->resolveExternals = true;

            # --------- teioop5 xsl ------------------------------------------
            $xmltei = "";
	    $xslfilter = $this->_param['INCPATH']."oo2lodelteip5.xsl";
	    $xsl = new DOMDocument;
	    if (! $xsl->load($xslfilter)) {
		$this->_status="error load xsl";$this->_iserror=true;error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
                throw new Exception($this->_status);
	    }
	    $proc = new XSLTProcessor;
	    $proc->importStyleSheet($xsl);
	    if (! $xmltei=$proc->transformToXML($xml)) {
		$this->_status="error transform xslt";$this->_iserror=true;error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
                throw new Exception($this->_status);
	    }
            $debugFile=$this->_param['TMPPATH'].$this->_dbg++."-tei.xslt.xml";file_put_contents($debugFile, $xmltei);

            $domTEI = new DOMDocument;
            $domTEI->resolveExternals = true;
            $domTEI->preserveWhiteSpace = false;
            $domTEI->encoding = "UTF-8";
            $domTEI->formatOutput = true;
	    if (! $domTEI->loadXML($xmltei)) {
		$this->_status="error load xml tei";$this->_iserror=true;error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
                throw new Exception($this->_status);
	    }

            $this->_status = "to cache";

            $xslTEI = $this->_param['TEI'] = "". $domTEI->saveXML();
            if (! $domTEI->save($outputFile)) {
		$this->_status="error save TEI";$this->_iserror=true;error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
                throw new Exception($this->_status);
            }
            $debugFile=$this->_param['TMPPATH'].$this->_dbg++."-tei.lodel.xsl.xml";@$domTEI->save($debugFile);

            # --- cleanup ---
            if ( preg_match("/when=\"(.+T.+)\"/", $xslTEI, $match)) {
                $datetime = $match[1];
                list($date, $time) = explode('T', $datetime);
                //error_log("<li>when: $datetime => $date</li>\n\n", 3, self::_DEBUGFILE_);
                $pattern = "/when=\"{$datetime}\"/";
                $replacement = "when=\"{$date}\"";
                $xslTEI = preg_replace($pattern, $replacement, $xslTEI);
            }

            $domTEI = new DOMDocument;
            $domTEI->resolveExternals = true;
            $domTEI->preserveWhiteSpace = true;
            $domTEI->encoding = "UTF-8";
            $domTEI->formatOutput = true;
	    if (! $domTEI->loadXML($xslTEI)) {
		$this->_status="error load xml tei";$this->_iserror=true;error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
                throw new Exception($this->_status);
	    }

            $this->_param['TEI'] = $domTEI->saveXML();
            if (! file_put_contents($outputFile, $this->_param['TEI'])) {
		$this->_status="error save TEI";$this->_iserror=true;error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
                throw new Exception($this->_status);
            }
	}

        error_log("<li>return true; {$this->_status}</li>\n\n", 3, self::_DEBUGFILE_);
        return true;
    }



        /** lodel-cleanup **/
        private function lodelcleanup(&$dom) {
            $patterns = array('/\s+/', '/\(/', '/\)/', '/\[/', '/\]/');

            $xpath = new DOMXPath($dom);
            $entries = $xpath->query("//@*");
            foreach ($entries as $entry) {
                switch ($entry->nodeName) {
                    case 'style:name':
                    case 'style:display-name':
                    case 'style:parent-style-name':
                    case 'style:next-style-name':
                    case 'style:master-page-name':
                    case 'text:note-class':
                    case 'text:citation-style-name':
                    case 'text:citation-body-style-name':
                    case 'text:style-name':
                        if (! preg_match("/^[TP]\d+$/", $entry->nodeValue)) {
                            $nodevalue = _makeSortKey( preg_replace($patterns, "", $entry->nodeValue));
                            if ( isset( $this->EModel[$nodevalue])) {
                                $nodevalue = $this->EModel[$nodevalue];
                                $entry->nodeValue = $nodevalue;
                            }
                            else if ( preg_match("/^(titre|heading)(\d*)$/i", $nodevalue, $match)) {
                                $nodevalue = "heading".$match[2];
                                $entry->nodeValue = $nodevalue;
                            }
                            else { 
                                $entry->nodeValue = $nodevalue;
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
        }

        private function lodelpictures(&$dom, &$za) {
        error_log("<h3>lodelpictures()</h3>\n", 3, self::_DEBUGFILE_);
            $imgindex = 0;
            $xpath = new DOMXPath($dom);
            $entries = $xpath->query("//draw:image");
            // TODO : test Pictures !
            foreach ($entries as $item) {
                $attributes = $item->attributes;
                $attribute = $attributes->getNamedItem("href");
                $match = $attribute->nodeValue;
                error_log("<li>draw:image: $match</li>\n\n", 3, self::_DEBUGFILE_);
                $imgindex++;
                list($imgpre, $imgext) = explode(".", trim($match));
                list($pictures, $imgname) = explode("/", $imgpre);
                if ($this->_param['mode'] === "lodel") {
                    $picturepath = "Pictures/img-$imgindex.$imgext";
                } else { // TODO : TEI lodel mode ??!
                    //http://XXX.revues.org/docannexe/image/XXX/img-X.jpg <draw:image xlink:href="Pictures/100002000000021000000121BB042930.png"
                    $picturepath = "http://".$revue.".revues.org/docannexe/image/".$prefix."/img-".$imgindex.".".$imgext;
                }
                $currentname = "Pictures/$imgname.$imgext";
                $newname = "Pictures/img-$imgindex.$imgext";
                if (! $za->renameName($currentname, $newname)) {
                    $this->_status="error rename files in ziparchive";error_log("<h1>! {$this->_status} </h1>\n", 3, self::_DEBUGFILE_);
                    throw new Exception($this->_status);
                }
                $attribute->nodeValue = $newname;
            }
            return true;
        }

        private function ooautomaticstyles(&$dom) {
        error_log("<h3>ooautomaticstyles()</h3>\n", 3, self::_DEBUGFILE_);
            $xpath = new DOMXPath($dom);
            $entries = $xpath->query("//style:style");
            foreach ($entries as $item) {
                $properties=array(); $key='';
                $attributes = $item->attributes;
                if ($attrname=$attributes->getNamedItem("name")) {
                    $name = $attrname->nodeValue;
                    $key = "#".$name;
                    if ( preg_match("/^T(\d+)$/", $name, $match)) {
                        $this->Tnum = $match[1];
                    }
                }
                if ($attrparent=$attributes->getNamedItem("parent-style-name")) {
                    $parent = $attrparent->nodeValue;
                    if ( preg_match("/^P(\d+)$/", $name, $match) and $parent!="standard") {
                        $this->automatic["#".$name] = $parent;
                        $this->automatic[$parent] = "#".$name;
                        $this->Pnum = $match[1];
                        $key = $parent."#".$name;
                    }
                }
                if ($item->hasChildNodes()) {
                    foreach ($item->childNodes as $child) {
                        switch ($child->nodeName) {
                            case 'style:paragraph-properties':
                            case 'style:text-properties':
                                $childattributes = $child->attributes;
                                foreach ($childattributes as $childattr) {
                                    if (! (strstr($childattr->name, '-asian') or strstr($childattr->name, '-complex'))) {
                                        $value = ''. "{$childattr->name}:{$childattr->value}";
                                        array_push($properties, $value);
                                    }
                                }
                                break;
                            default:
                                break;
                        }
                    }
                    list($lang, $rendition) = $this->styles2csswhitelist($properties);
                    $this->rendition[$key]['lang'] = $lang;
                    $this->rendition[$key]['rendition'] = $rendition;
                }
            }

            return true;
        }

        private function oostyles(&$dom) {
        error_log("<h3>oostyles()</h3>\n", 3, self::_DEBUGFILE_);
            $xpath = new DOMXPath($dom);
            $entries = $xpath->query("//style:style");
            foreach ($entries as $item) {
                $properties=array(); $key='';
                $attributes = $item->attributes;
                if ($attrname=$attributes->getNamedItem("name")) {
                    $name = $attrname->nodeValue;
                    $key = $name;
                }
                if ($attrfamily=$attributes->getNamedItem("family")) {
                    $family = $attrfamily->nodeValue;
                    if (! isset($this->automatic[$name])) {
                        switch ($family) {
                            case "paragraph":
                                $P = "#P".++$this->Pnum;
                                $this->automatic[$name] = $P;
                                break;
                            case "text":
                                $T = "#T".++$this->Tnum;
                                $this->automatic[$name] = $T;
                                break;
                        }
                    }
                    if ( isset($this->automatic[$name])) {
                        $key = $name.$this->automatic[$name];
                    }
                }
                if ($item->hasChildNodes()) {
                    foreach ($item->childNodes as $child) {
                        switch ($child->nodeName) {
                            case 'style:paragraph-properties':
                            case 'style:text-properties':
                                $childattributes = $child->attributes;
                                foreach ($childattributes as $childattr) {
                                    if (! (strstr($childattr->name, '-asian') or strstr($childattr->name, '-complex'))) {
                                        $value = ''. "{$childattr->name}:{$childattr->value}";
                                        array_push($properties, $value);
                                    }
                                }
                                break;
                            default:
                                break;
                        }
                    }
                    list($lang, $rendition) = $this->styles2csswhitelist($properties);
                    if ( isset($this->rendition[$key])) {
                        // TODO : merge ?
                        if ($this->rendition[$key]['lang']=='') {
                            $this->rendition[$key]['lang'] = $lang;
                        }
                        if ($this->rendition[$key]['rendition']=='') {
                            $this->rendition[$key]['rendition'] = $rendition;
                        }
                    } else {
                        $this->rendition[$key]['lang'] = $lang;
                        //$this->rendition[$key]['rendition'] = $rendition;
                    }
                }
            }

            return true;
        }	

        /** styles to css white list ! **/
        private function styles2csswhitelist(&$properties, $type="strict") {
        error_log("<h3>styles2csswhitelist() [type=$type]</h3>\n", 3, self::_DEBUGFILE_);
            $lang = ""; $rendition = "";
            $csswhitelist = array();
            foreach ($properties as $prop) {
                // xhtml:sup
                if ( preg_match("/^text-position:super/", $prop)) {
                    array_push($csswhitelist, "vertical-align:top;font-size:80%");
                    continue;
                }
                // xhtml:sub
                if ( preg_match("/^text-position:sub/", $prop)) {
                    array_push($csswhitelist, "vertical-align:bottom;font-size:80%");
                    continue;
                }
                if ( preg_match("/^language:(.*)$/", $prop, $match)) {
                    $lang = $match[1];
                    continue;
                }
                switch ($prop) {
                    case 'font-style:italic':
                    case 'font-weight:bold':
                    case 'font-weight:normal':
                    case 'font-variant:small-caps':
                    case 'text-transform:uppercase':
                    case 'text-transform:lowercase':
                        array_push($csswhitelist, $prop);
                        break;
                    case 'font-variant:uppercase':
                        array_push($csswhitelist, "text-transform:uppercase");
                        break;
                    case 'font-variant:lowercase':
                        array_push($csswhitelist, "text-transform:lowercase");
                        break;
                    case 'text-underline-style:solid':
                        array_push($csswhitelist, "text-decoration:underline");
                        break;
                    case 'writing-mode:lr-tb':
                        array_push($csswhitelist, "direction:ltr");
                        break;
                    case 'writing-mode:rl-tb':
                        array_push($csswhitelist, "direction:rtl");
                        break;
                    default:
                    //error_log("<li><i>TODO: $prop ?! [strict mode]</i></li>\n",3,self::_DEBUGFILE_);
                        break;
                }
                if ($type==="large") {
                    if ( preg_match("/^font-size:/", $prop)) {
                        array_push($csswhitelist, $prop);
                        continue;
                    }
                    if ( preg_match("/^font-name:(.*)$/", $prop, $match)) {
                        array_push($csswhitelist, "font-family:'{$match[1]}'");
                    }
                    /* TODO ?
                        line-height ?? */
                    switch ($prop) {
                        case 'text-align:center':
                        case 'text-align:justify':
                            array_push($csswhitelist, $prop);
                            break;
                        case 'text-align:start':
                            array_push($csswhitelist, "text-align:left");
                            break;
                        case 'text-align:end':
                            array_push($csswhitelist, "text-align:right");
                            break;
                        default:
                        //error_log("<li><i>TODO: $prop ?! [large mode]</i></li>\n",3,self::_DEBUGFILE_);
                            break;
                    }
                }
            }
            $rendition = implode(";", $csswhitelist);

            return array($lang, $rendition);
        }

        /** @return array('rend'=>, 'key'=>, 'surround'=>, 'section'=>) **/
        private function greedy(&$node) {
        //error_log("<h3>greedy()</h3>\n", 3, self::_DEBUGFILE_);
            $section = $surround = $key = $rend = null;
            if ($rend=$node->getAttribute("rend")) {
                if ( isset($this->EMotx[$rend]['surround'])) {
                    $surround = $this->EMotx[$rend]['surround'];
                }
                if ( isset($this->EMotx[$rend]['key'])) {
                    $key = $this->EMotx[$rend]['key'];
                    switch ($key) {
                        case 'header':
                        case 'front':
                            $section = "head";
                            break;
                        case 'back':
                            $section = "back";
                            break;
                        case 'text':
                        default:
                            $section = "body";
                        break;
                    }
                }
                return array('rend'=>$rend, 'key'=>$key, 'surround'=>$surround, 'section'=>$section);
            }
            else return null;
        }


    /**
    * transformation d'un document en odt 
    * system call inside (soffice)
    **/
    protected function soffice2odt() {
    error_log("<h3>soffice2odt()</h3>\n", 3, self::_DEBUGFILE_);

        // get the mime type
        $this->getmime();
        $sourcepath = $this->_param['sourcepath'];
        $extension = $this->_param['extension'];

        if ($this->_param['mime'] !== "OpenDocument Text") {
            //TODO : tetster la presence du lien symbolique jodconverter-cli.jar !!
            //$command = "python ". $this->SERVOO_LIB ."DocumentConverter.py ". $inputDoc ." ". $inputODT;
            $command = "java -jar ". $this->_param['LIBPATH'] ."jodconverter-cli.jar -f odt ". escapeshellarg($sourcepath);
            error_log("<li>command : $command</li>\n", 3, self::_DEBUGFILE_);
            $output = array(); $returnvar=0;
            $result = ''. exec($command, $output, $returnvar);
            if ($returnvar!=0) {
                @copy($sourcepath, $sourcepath.".error"); @unlink($sourcepath);
                $this->_status = "error soffice : $returnvar"; 
                throw new Exception($this->_status);
            }
        }
        $odtpath = $this->_param['odtpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/".$this->_param['prefix'].".odt";
    }

    /**
    * report for checkbalisage
    **/
    protected function oo2report($filepath) {
    error_log("<h3>oo2report($filepath)</h3>\n", 3, self::_DEBUGFILE_);

        $mode = $this->_param['mode'];
        $xmlreport = ''. $this->_param['xmlreport'];

        $za = new ZipArchive();
        if (! $za->open($filepath)) {
            $this->_status="error open ziparchive ($filepath)";error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        if (! $meta=$za->getFromName('meta.xml')) {
            $this->_status="error get meta.xml";error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $dommeta = new DOMDocument;
        $dommeta->encoding = "UTF-8";
        $dommeta->resolveExternals = true;
        $dommeta->preserveWhiteSpace = false;
        $dommeta->formatOutput = true;
        if (! $dommeta->loadXML($meta)) {
            $this->_status="error load meta.xml";error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $xmlmeta = str_replace('<?xml version="1.0" encoding="UTF-8"?>', "", $dommeta->saveXML());
        $za->close();

        if ($xmlreport === '') {
            $xmlreport = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
    xmlns="http://purl.org/rss/1.0/" 
    xmlns:dc="http://purl.org/dc/elements/1.1/" 
    xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/" 
    xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#" 
    xmlns:xlink="http://www.w3.org/1999/xlink" 
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" 
    xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" 
    xmlns:ooo="http://openoffice.org/2004/office"   
>
    <item rdf:about="http://otx.revues.org/?soffice=document.doc">
        <title>openoffice document-meta</title>
        <link></link>
        <description>
        OpenOffice original document properties
        </description>
        $xmlmeta
    </item>
EOD;
        } else { 
            $xmlreport = str_replace('</rdf:RDF>', "", $this->_param['xmlreport']);
            $xmlreport .= <<<EOD
    <item rdf:about="http://otx.revues.org/?lodel=document.odt">
        <title>openoffice lodel-meta</title>
        <link></link>
        <description>Lodel ODT document properties</description>
        $xmlmeta
    </item>
EOD;
        }
        $xmlreport .= <<<EOD
</rdf:RDF>
EOD;

        $this->_param['xmlreport'] = $xmlreport;
    }


    protected function meta2xml() {

        if ($this->pdfsource != '') { // AND

            if ( array_key_exists('dc:title', $this->metadata)) {
                $this->TEI = preg_replace("/<title\/>/", "<title>{$this->metadata['dc:title']}</title>", $this->TEI);
                    $this->TEI = preg_replace("/<seriesStmt>.*<title>.*<\/title>/sU", "<seriesStmt><title/>", $this->TEI);  // TODO ...
                    $this->TEI = preg_replace("/<series>.*<title>.*<\/title>/sU", "<series><title/>", $this->TEI); // TODO ...
            }

            if ( array_key_exists('dc:language', $this->metadata)) {
                $this->TEI = preg_replace("/<language ident=\"\"\/>/", "<language ident=\"{$this->metadata['dc:language']}\">{$this->metadata['dc:language']}</language>", $this->TEI);
            }

            $this->TEI = preg_replace("/<body.*\/body>/s", $this->pdf2TEIbody, $this->TEI);
            if ( strlen($this->pdf2TEIback) > 0) {
                $this->TEI = preg_replace("/<back\/>/", $this->pdf2TEIback, $this->TEI);
            }
        }

        $dommeta = new DOMDocument;
        if (! $dommeta->loadXML($this->request)) {
            $this->status = "error loadxml request"; $this->_error=TRUE;
            return FALSE;
	}
        $domTEI = new DOMDocument;
	if (! $domTEI->loadXML($this->TEI)) {
            $this->status = "error loadxml TEI"; $this->_error=TRUE;
            return FALSE;
	}

        // 
        $found = FALSE;
        $metas = $dommeta->getElementsByTagName('*');
        foreach ($metas as $meta) {
            switch ($meta->nodeName) {
                case "prism:person":
                    $creator = "". $meta->nodeValue;
                    break;
                case "firstname":
                    $firstname = "". $meta->nodeValue;
                    break;
                case "lastname":
                    $lastname = "". $meta->nodeValue;
                    $authors = $domTEI->getElementsByTagName('author');
                    foreach ($authors as $author) {
                        $parent = $author->parentNode;
                        if ($parent->nodeName == "titleStmt") {
                            foreach ($author->childNodes as $child) {
                                if (trim($creator) == trim($child->nodeValue)) {
                                    $element = $domTEI->createElement('forename', $firstname);
                                    $author->appendChild($element);
                                    $element = $domTEI->createElement('surname', $lastname);
                                    $author->appendChild($element);
                                    $found = TRUE;
                                    continue;
                                }
                            }
                        }
                    }
                    $editors = $domTEI->getElementsByTagName('editor');
                    foreach ($editors as $editor) {
                        $parent = $editor->parentNode;
                        if ($parent->nodeName == "titleStmt") {
                            foreach ($editor->childNodes as $child) {
                                if (trim($creator) == trim($child->nodeValue)) {
                                    $element = $domTEI->createElement('forename', $firstname);
                                    $editor->appendChild($element);
                                    $element = $domTEI->createElement('surname', $lastname);
                                    $editor->appendChild($element);
                                    $found = TRUE;
                                    continue;
                                }
                            }
                        }
                    }
                    break;
                case "dc:date":
                    $publicationStmt = $domTEI->getElementsByTagName('publicationStmt');
                    foreach ($publicationStmt as $publication) {
                        $element = $domTEI->createElement('date', $meta->nodeValue);
                        $publication->appendChild($element);
                        $attribute = $domTEI->createAttribute('when');
                        $attribute->value = $meta->nodeValue; 
                        $element->appendChild($attribute);
                        $found = TRUE;
                        continue;
                    }
                    break;
                case "prism:creationDate":
                    if ($meta->nodevalue == "0000-00-00") continue;
                    $imprintS = $domTEI->getElementsByTagName('imprint');
                    foreach ($imprintS as $imprint) {
                        $element = $domTEI->createElement('date', $meta->nodeValue);
                        $imprint->appendChild($element);
                        $attribute = $domTEI->createAttribute('when');
                        $attribute->value = $meta->nodeValue; 
                        $element->appendChild($attribute);
                        $found = TRUE;
                        continue;
                    }
                    break;
            }
        }
        if ($found) { 
            $this->TEI = $domTEI->saveXML();
        } else {
            $status = "warning: document not match, $author, $title";
            return TRUE; 
        }
        // uri
        if ( array_key_exists('dc:identifier', $this->metadata)) {
            $this->TEI = preg_replace("/<idno type=\"url\"\/>/", "<idno type=\"url\">{$this->metadata['dc:identifier']}</idno>", $this->TEI);
        }

        if (! $domTEI->save($this->_outputFile)) {
            $this->status = "error save TEI"; $this->_error=TRUE;
            return FALSE;
        }

        return TRUE;
    }

    private function getmime() {
    error_log("<li>getmime()</li>\n", 3, self::_DEBUGFILE_);
        $sourcepath = $this->_param['sourcepath'];

        error_log("<li>[getmime] sourcepath = $sourcepath</li>\n\n", 3, self::_DEBUGFILE_);
        $mime = mime_content_type($sourcepath);
        if ($mime === "application/x-zip" OR $mime === "application/zip") {
            $file = escapeshellarg($sourcepath);
            list($mime, $tmp) = explode(",", system("file -b $file"));
        }

        $extension = ".odt";
        if ( trim($mime) != "OpenDocument Text") {
            switch ($mime) {
                case "Rich Text Format data":   //, version 1, ANSI   //, version 1, Apple Macintosh
                case "text/rtf":
                error_log("<li>Rich Text Format data</li>\n", 3, self::_DEBUGFILE_);
                    $extension = ".rtf";
                    break;
                case "Microsoft Office Document":
                case "application/msword":
                error_log("<li>Microsoft Office Document</li>\n", 3, self::_DEBUGFILE_);
                    $extension = ".doc";
                    break;
                case "OpenOffice.org 1.x Writer document":
                case "application/vnd.sun.xml.writer":
                error_log("<li>OpenOffice.org 1.x Writer document</li>\n", 3, self::_DEBUGFILE_);
                    $extension = ".sxw";
                    break;
                default:
                error_log("<li>Warning: extension based</li>\n", 3, self::_DEBUGFILE_);
                    # the last chance !    // ben'à défaut on se base sur l'extention du fichier...
                    $temp = explode(".", $sourcepath);
                    $ext = trim( array_pop($temp));
                    error_log("<li>Warning : mime detection based on document extension ($ext)</li>\n\n", 3, self::_DEBUGFILE_);
                    switch ($ext) {
                        case "rtf":
                            error_log("<li>warning: .rtf</li>\n\n", 3, self::_DEBUGFILE_);
                            $extension = ".rtf";
                            break;
                        case "sxw":
                            error_log("<li>warning: .sxw</li>\n\n", 3, self::_DEBUGFILE_);
                            $extension = ".sxw";
                            break;
                        case "doc":
                            error_log("<li>warning: .doc</li>\n\n", 3, self::_DEBUGFILE_);
                            $extension = ".doc";
                            break;
                        case "docx":
                            $this->_status="error: docx type document not supported yet ($sourcepath)"; $this->_iserror=true;
                            throw new Exception($this->_status);
                            break;
                        default:
                            $this->_status="error: unknown mime type: $mime ($sourcepath)"; $this->_iserror=true;
                            throw new Exception($this->_status);
                            break;
                    }
                break;
            }
        }

        $this->_param['extension'] = $extension;
        $this->_param['mime'] = $mime;
    }

    private function params() {
    error_log("<h4>_param()</h4>\n", 3, self::_DEBUGFILE_);
        $request = $this->_param['request'];
        $mode = $this->_param['mode'];

        $domrequest = new DOMDocument;
	if (! $domrequest->loadXML($request)) {
            $this->_status="error: can't load xml request";$this->_iserror=true;
            throw new Exception($this->_status);
	}

        // parse rdf request
        foreach ($domrequest->getElementsByTagName('*') as $tag) {
            switch ($tag->nodeName) {
                case 'dc:source':
                    if ($mode === "cairn") {
                        $this->_param['uri'] = "". $tag->nodeValue;
                    }
                    else {
                        $this->_param['sourcename'] = $tag->nodeValue;
                        $split = explode(".", $tag->nodeValue);
                        $this->_param['extension'] = array_pop($split);
                        $this->_param['prefix'] = implode('.', $split);
                    }
                    break;
                case 'prism:publicationName':
                    $this->_param['revuename'] = "". $tag->nodeValue;
                    break;
                case 'dc:alternative': 
                    $this->_param['pdfsource'] = "". $tag->nodeValue;
                    break;
                case 'dc:title':
                case 'dc:date':
                case 'dc:identifier':
                case 'dc:type':
                case 'dc:language':
                default:
                    $this->_param[$tag->nodeName] = "" .$tag->nodeValue;
            }
        }

        @mkdir($this->_param['CACHEPATH'],0755);
	@mkdir($this->_param['CACHEPATH'].$this->_param['revuename'],0755);

        $this->_param['sourcepath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/".$this->_param['sourcename'];
        if (! copy($this->input['entitypath'], $this->_param['sourcepath'])) {
            $this->_status="error: failed copy {$this->input['entitypath']} to {$this->_param['sourcepath']}";
            throw new Exception($this->_status);
        }
        error_log("<li>[_params] sourcepath={$this->_param['sourcepath']}</li>\n\n", 3, self::_DEBUGFILE_);

        $this->_param['modelpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/"."model.xml";
        if (! copy($this->input['modelpath'], $this->_param['modelpath'])) {
            $this->_status="error: failed copy {$this->_param['modelpath']}";
            throw new Exception($this->_status);
        }
        error_log("<li>[_params] modelpath={$this->_param['modelpath']}</li>\n\n", 3, self::_DEBUGFILE_);

        // save the rdf request
        $requestfile=$this->_param['TMPPATH'].$this->_param['prefix'].".rdf";@$domrequest->save($requestfile);
    }

    private function _cleanup() {
    error_log("<li>_cleanup()()</li>\n\n", 3, self::_DEBUGFILE_);

        @unlink(self::_WEBSERVOO_LOCKFILE_);
        @unlink($this->input['modelpath']);
        @unlink($this->input['entitypath']);

        @unlink($this->_param['modelpath']);
        @unlink($this->_param['sourcepath']);
        @unlink($this->_param['odtpath']);
    }

    private function _oodate($ladatepubli) {
        $patterns = array ('/janvier/', '/février/', '/mars/', '/avril/', '/mai/', '/juin/', '/juillet/', '/aout/', '/septembre/', '/octobre/', '/novembre/', '/décembre/');
        $replace = array ('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');
    
        list($date, $T) = explode('T', date("Y-m-d", strtotime( preg_replace($patterns, $replace, $ladatepubli)."T00:00:00")));
        error_log("<li>_oodate: $date</li>\n\n", 3, self::_DEBUGFILE_);
        return $date;
    }

    private function _ootitle($title) {
        list($ootitle, $moretitle) = preg_split("/\*/", $title);
        return trim($ootitle);
    }



    /** **/
    protected function pdf2tei() {
        $CHARendofpage = "\x0C";
        $TI = array();
        $data = "";

        if (! $data=file_get_contents( str_replace(" ", "%20", $this->pdfsource))) {
            $this->status = "404 Not Found : ".$this->pdfsource;
            return FALSE;
        }
        $pdffile = $this->_SERVEL_TMP .array_pop( explode("/", str_replace(" ", "", $this->pdfsource)));
        $pdffile = preg_replace("/\.html(.*)$/", ".pdf", $pdffile);

        if (! file_put_contents($pdffile, $data)) {
            $this->status = "error write tmp";
            return FALSE;
        }

        # pdftotext -raw pdffile
        $command = 'pdftotext -raw "'.escapeshellarg($pdffile).'"';
        $output = array(); $returnvar=0;
        $result = ''. exec($command, $output, $returnvar);
        if ($returnvar != 0) {
            // @unlink($pdffile);
            $this->status = "error pdftotext : $command = $returnvar"; 
            return FALSE;
        }
        //@unlink($pdffile);
        $pdftext = preg_replace("/\.pdf$/", ".txt", $pdffile);
        if (! $content=file_get_contents($pdftext)) {
            $this->status = "error file_get_contents: $pdftext";
            return FALSE;
        }

        // UTF-8 encoding
        $encoding = mb_detect_encoding($content."a",'UTF-8, ISO-8859-1');
        if ($encoding != "UTF-8") {
            $content = iconv($encoding, "UTF-8", $content);
        }
        mb_regex_encoding("UTF-8");
        mb_internal_encoding("UTF-8");

        $pages = explode($CHARendofpage, $content);
        foreach ($pages as $page) {
            $lines = explode("\n", $page);
            foreach ($lines as $line) {
                $line = htmlspecialchars( trim($line)) ."<lb/>";
                array_push($TI, $line);
            }
            array_push($TI, "<pb/>");
        }
        # --- pdf 2 text/xml ---
        $pdfxml = "<body>";
        $pdfxml .= '<divGen type="pdftotext"/>';
        $pdfxml .= '<div type="text" subtype="raw">';
        $pdfxml .= '<p>';

        foreach ($TI as $line) {
            $pdfxml .= "$line\n";
        }

        $pdfxml .= "</p>\n";
        $pdfxml .= "</div>\n";
        $pdfxml .= "</body>\n";
        $this->pdf2TEIbody = $pdfxml;

/*
$debugfile = $this->_SERVEL_TMP."body.txt";
@file_put_contents($debugfile,$this->pdf2TEIbody);@chmod($debugfile,0660);@chown($debugfile,"barts");@chgrp($debugfile,"www:data");
*/
        $this->pdf2TEIback = "";
        return TRUE;
    }





    protected function otix() {
    error_log("<h3>otix()</h3>\n", 3, self::_DEBUGFILE_);
        $cleanup = array('/_20_/', '/_28_/', '/_29_/', '/_5f_/', '/_5b_/', '/_5d_/', '/_32_/', '/WW-/' );

        $odtfile = $this->_param['odtpath'];
        $this->_param['lodelodtpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/".$this->_param['prefix'].".lodel.odt";
        $lodelodtfile = $this->_param['lodelodtpath'];
        if (! copy($odtfile, $lodelodtfile)) {
            $this->_status="error copy file; ".$lodelodtfile;error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        # fodt...
        $za = new ZipArchive();
        if (! $za->open($lodelodtfile)) {
            $this->_status="error open ziparchive; ".$lodelodtfile;error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        # ----- office:meta ----------------------------------------------------------
        error_log("<li>office:meta</li>\n\n", 3, self::_DEBUGFILE_);
        if (! $OOmeta=$za->getFromName('meta.xml')) {
            $this->_status="error get meta.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $dommeta = new DOMDocument;
        $dommeta->encoding = "UTF-8";
        $dommeta->resolveExternals = true;
        $dommeta->preserveWhiteSpace = false;
        $dommeta->formatOutput = true;
        if (! $dommeta->loadXML($OOmeta)) {
            $this->_status="error load meta.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-meta.xml";@$dommeta->save($debugfile);
        # cleanup
        $lodelmeta = _windobclean($OOmeta);
        # lodel
        $domlodelmeta = new DOMDocument;
        $domlodelmeta->encoding = "UTF-8";
        $domlodelmeta->resolveExternals = true;
        $domlodelmeta->preserveWhiteSpace = false;
        $domlodelmeta->formatOutput = true;
        if (! $domlodelmeta->loadXML($lodelmeta)) {
            $this->_status="error load lodel-meta.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domlodelmeta->normalizeDocument();
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-meta.lodel.xml";@$domlodelmeta->save($debugfile);

        # ----- office:settings ----------------------------------------------------------
        error_log("<li>office:settings</li>\n\n", 3, self::_DEBUGFILE_);
        if (! $OOsettings=$za->getFromName('settings.xml')) {
            $this->_status="error get settings.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domsettings = new DOMDocument;
        $domsettings->encoding = "UTF-8";
        $domsettings->resolveExternals = true;
        $domsettings->preserveWhiteSpace = false;
        $domsettings->formatOutput = true;
        if (! $domsettings->loadXML($OOsettings)) {
            $this->_status="error load settings.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-settings.xml";@$domsettings->save($debugfile);
        # cleanup
        $lodelsettings = _windobclean($OOsettings);
        # lodel
        $domlodelsettings = new DOMDocument;
        $domlodelsettings->encoding = "UTF-8";
        $domlodelsettings->resolveExternals = true;
        $domlodelsettings->preserveWhiteSpace = false;
        $domlodelsettings->formatOutput = true;
        if (! $domlodelsettings->loadXML($lodelsettings)) {
            $this->_status="error load lodel-settings.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domlodelsettings->normalizeDocument();
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-settings.lodel.xml";@$domlodelsettings->save($debugfile);

        # ----- office:styles ---------------------------------------
        error_log("<li>office:styles</li>\n\n", 3, self::_DEBUGFILE_);
        if (! $OOstyles=$za->getFromName('styles.xml')) {
            $this->_status="error get styles.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domstyles = new DOMDocument;
        $domstyles->encoding = "UTF-8";
        $domstyles->resolveExternals = true;
        $domstyles->preserveWhiteSpace = false;
        $domstyles->formatOutput = true;
        if (! $domstyles->loadXML($OOstyles)) {
            $this->_status="error load styles.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-styles.xml";@$domstyles->save($debugfile);
        # cleanup
        $lodelstyles = preg_replace($cleanup, "", _windobclean($OOstyles));
        # lodel
        $domlodelstyles = new DOMDocument;
        $domlodelstyles->encoding = "UTF-8";
        $domlodelstyles->resolveExternals = true;
        $domlodelstyles->preserveWhiteSpace = false;
        $domlodelstyles->formatOutput = true;
        if (! $domlodelstyles->loadXML($lodelstyles)) {
            $this->_status="error load lodel-styles.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        // lodel-cleanup++
        $this->lodelcleanup($domlodelstyles);
        $domlodelstyles->normalizeDocument(); 
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-styles.lodel.xml";@$domlodelstyles->save($debugfile);

        # ----- office:content -------------------------------------------------------
        error_log("<li>office:content</li>\n\n", 3, self::_DEBUGFILE_);
        if (! $OOcontent=$za->getFromName('content.xml')) {
            $this->_status="error get content.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domcontent = new DOMDocument;
        $domcontent->encoding = "UTF-8";
        $domcontent->resolveExternals = false;
        $domcontent->preserveWhiteSpace = true;
        $domcontent->formatOutput = true;
        if (! $domcontent->loadXML($OOcontent)) {
            $this->_status="error load conntent.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-content.xml";@$domcontent->save($debugfile);
        # cleanup
        $lodelcontent = preg_replace($cleanup, "", _windobclean($OOcontent));
        # lodel
        $domlodelcontent = new DOMDocument;
        $domlodelcontent->encoding = "UTF-8";
        $domlodelcontent->resolveExternals = false;
        $domlodelcontent->preserveWhiteSpace = true;
        $domlodelcontent->formatOutput = true;
        if (! $domlodelcontent->loadXML($lodelcontent)) {
            $this->_status="error load lodel-content.xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        // lodel-cleanup++
        $this->lodelcleanup($domlodelcontent);
        //
        $this->lodelpictures($domlodelcontent, $za);
        $domlodelcontent->normalizeDocument();
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-content.lodel.xml";@$domlodelcontent->save($debugfile);

        // 1. office:automatic-styles
        $this->ooautomaticstyles($domlodelcontent);
        // 2. office:styles
        $this->oostyles($domlodelstyles);

        # LodelODT
        if (! $za->addFromString('meta.xml', $domlodelmeta->saveXML())) {
            $this->_status="error ZA addFromString lodelmeta";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        if (! $za->addFromString('settings.xml', $domlodelsettings->saveXML())) {
            $this->_status="error ZA addFromString lodelsettings";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        if (! $za->addFromString('styles.xml', $domlodelstyles->saveXML())) {
            $this->_status="error ZA addFromString lodelstyles";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        if (! $za->addFromString('content.xml', $domlodelcontent->saveXML())) {
            $this->_status="error ZA addFromString lodelcontent";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $za->close();

        # lodel fodt (flat ODT)
        $xmlfodt = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<office:document 
    xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" 
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" 
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" 
    xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" 
    xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" 
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" 
    xmlns:xlink="http://www.w3.org/1999/xlink" 
    xmlns:dc="http://purl.org/dc/elements/1.1/" 
    xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" 
    xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" 
    xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" 
    xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0" 
    xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0" 
    xmlns:math="http://www.w3.org/1998/Math/MathML" 
    xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" 
    xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0" 
    xmlns:config="urn:oasis:names:tc:opendocument:xmlns:config:1.0" 
    xmlns:ooo="http://openoffice.org/2004/office" 
    xmlns:ooow="http://openoffice.org/2004/writer" 
    xmlns:oooc="http://openoffice.org/2004/calc" 
    xmlns:dom="http://www.w3.org/2001/xml-events" 
    xmlns:xforms="http://www.w3.org/2002/xforms" 
    xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
    xmlns:field="urn:openoffice:names:experimental:ooxml-odf-interop:xmlns:field:1.0" 
    office:version="1.1" office:mimetype="application/vnd.oasis.opendocument.text">
EOD;
        // fodt:meta
        $xmlmeta = explode("\n", $domlodelmeta->saveXML());
        array_shift($xmlmeta); array_shift($xmlmeta);
        array_pop($xmlmeta); array_pop($xmlmeta);
        // fodt:settings
        $xmlsetttings = explode("\n", $domlodelsettings->saveXML());
        array_shift($xmlsetttings); array_shift($xmlsetttings);
        array_pop($xmlsetttings); array_pop($xmlsetttings);
        // fodt:styles
        $xmlstyles = explode("\n", $domlodelstyles->saveXML());
        array_shift($xmlstyles); array_shift($xmlstyles);
        array_pop($xmlstyles); array_pop($xmlstyles);
        $fodtstyles = preg_replace("/<office:automatic-styles.+?office:automatic-styles>/s", "", implode("\n", $xmlstyles));
        // fodt:content
        $xmlcontent = explode("\n", $domlodelcontent->saveXML());
        array_shift($xmlcontent); array_shift($xmlcontent);
        array_pop($xmlcontent); array_pop($xmlcontent);
        $fodtcontent = preg_replace("/<office:font-face-decls.*?office:font-face-decls>/s", "", implode("\n", $xmlcontent));
        // fodt
        $xmlfodt .= implode("\n", $xmlmeta) ."\n";
        $xmlfodt .= implode("\n", $xmlsetttings) ."\n";
        $xmlfodt .= $fodtstyles ."\n";
        $xmlfodt .= $fodtcontent ."\n";
        $xmlfodt .= "</office:document>";
        // fodt xml
        $domfodt = new DOMDocument;
        $domfodt->encoding = "UTF-8";
        $domfodt->resolveExternals = true;
        $domfodt->preserveWhiteSpace = false;
        $domfodt->formatOutput = true;
        if (! $domfodt->loadXML($xmlfodt)) {
            $this->_status="error load fodt xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domfodt->normalizeDocument();
        $debugFile=$this->_param['TMPPATH'].$this->_dbg++."-lodel.fodt.xml";@$domfodt->save($debugFile);

        // oototei xslt
        $xslfilter = $this->_param['INCPATH']."oo2lodeltei.xsl";
        $xsl = new DOMDocument;
        if (! $xsl->load($xslfilter)) {
            $this->_status="error load xsl ($xslfilter)";error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);
        if (! $teifodt=$proc->transformToXML($domfodt)) {
            $this->_status="error transform xslt";error_log("<h1>{$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domteifodt = new DOMDocument;
        $domteifodt->encoding = "UTF-8";
        $domteifodt->resolveExternals = true;
        $domteifodt->preserveWhiteSpace = false;
        $domteifodt->formatOutput = true;
        if (! $domteifodt->loadXML($teifodt)) {
            $this->_status="error load teifodt xml";error_log("<h1>! {$this->_status}</h1>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status);
        }
        $domteifodt->normalizeDocument();
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-fodt.teilodel.xml";@$domteifodt->save($debugfile);

        $this->lodeltei($domteifodt);
    }

    protected function lodeltei(&$dom) {
    error_log("<h3>lodeltei</h3>\n",3,self::_DEBUGFILE_);
        $tagsdecl = array();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('tei', 'http://www.tei-c.org/ns/1.0');

        $entries = $xpath->query("//tei:hi[@rendition]");
        foreach ($entries as $item) {
            if ( preg_match("/^(#T\d+)$/", $item->getAttribute("rendition"), $match)) {
                $rendition = $match[1];
                //echo "<li>hi $rendition</li>";
                if ( isset($this->rendition[$rendition])) {
                    // xml:lang ?
                    if ($this->rendition[$rendition]['lang']!='') {
                        $lang = $this->rendition[$rendition]['lang'];
                        //echo "<li>=> xml:lang=$lang ($rendition)</li>";
                        $item->setAttribute("xml:lang", $lang);
                    }
                    // css style
                    if ($this->rendition[$rendition]['rendition']!='') {
                        $tagsdecl[$rendition] = $this->rendition[$rendition]['rendition'];
                        //echo "<li>=> {$tagsdecl[$rendition]} ($rendition)</li>";
                    } else {
                        $item->removeAttribute("rendition");
                        //echo "<li>=> remove ($rendition)</li>";
                    }
                }
            }
        }

        $entries = $xpath->query("//tei:p[@rendition] | //tei:s[@rendition]");
        foreach ($entries as $item) {
            if ( preg_match("/^(#P\d+)$/", $item->getAttribute("rendition"), $match)) {
                $value = $match[1];
                // rend ?
                if ( isset($this->automatic[$value]) && $this->automatic[$value]!="standard") {
                    $rend = $this->automatic[$value];
                    $item->setAttribute("rend", $rend);
                }
                else {
                    if ( isset($this->rendition[$value])) {
                        // xml:lang ?
                        if ($this->rendition[$value]['lang']!='') {
                            $lang = $this->rendition[$value]['lang'];
                            $item->setAttribute("xml:lang", $lang);
                        }
                        // css style
                        if ($this->rendition[$value]['rendition']!='') {
                            $tagsdecl[$value] = $this->rendition[$value]['rendition'];
                        } else {
                            $item->removeAttribute("rendition");
                        }
                    }
                }
            }
        }

        $entries = $xpath->query("//tei:hi[@rend]");
        foreach ($entries as $item) {
            $value = $item->getAttribute("rend");
            if ( isset($this->automatic[$value])) {
                $key = $this->automatic[$value];
                $rendition = $value.$key;
                if ( isset($this->rendition[$rendition])) {
                    // xml:lang ?
                    if ($this->rendition[$rendition]['lang']!='') {
                        $lang = $this->rendition[$rendition]['lang'];
                        $item->setAttribute("xml:lang", $lang);
                    }
                    // css style
                    if ($this->rendition[$rendition]['rendition']!='') {
                        $tagsdecl[$key] = $this->rendition[$rendition]['rendition'];
                        $item->setAttribute("rendition", $key);
                    }
                }
            }
        }

        $entries = $xpath->query("//tei:p[@rend]");
        foreach ($entries as $item) {
            $rend = $item->getAttribute("rend");
            $key = '';
            if ($item->getAttribute("rendition")) {
                $key = $item->getAttribute("rendition");
            } else if ( isset($this->automatic[$rend])) {
                $key = $this->automatic[$rend];
            }
            $rendition = $rend.$key;
            if ( isset($this->rendition[$rendition])) {
                // xml:lang ?
                if ($this->rendition[$rendition]['lang']!='') {
                    $lang = $this->rendition[$rendition]['lang'];
                    $item->setAttribute("xml:lang", $lang);
                }
                // css style
                if ($this->rendition[$rendition]['rendition']!='') {
                    $tagsdecl[$key] = $this->rendition[$rendition]['rendition'];
                    $item->setAttribute("rendition", $key);
                } else {
                    $item->removeAttribute("rendition");
                }
            }
        }

        foreach ($tagsdecl as $key=>$value) {
            if ( preg_match("/^#P(\d+)$/", $key, $match)) {
                $Pdecl[$match[1]] = $value;
                continue;
            }
            if ( preg_match("/^#T(\d+)$/", $key, $match)) {
                $Tdecl[$match[1]] = $value;
                continue;
            }
        }
        ksort($Pdecl); ksort($Tdecl);

        $header = $dom->getElementsByTagName('teiHeader')->item(0);
        $newnode = $dom->createElement("encodingDesc");
        $encodingDesc = $header->appendChild($newnode);
        $newnode = $dom->createElement("tagsDecl");
        $tagsDecl = $encodingDesc->appendChild($newnode);
        foreach ($Pdecl as $key=>$value) {
            $newnode = $dom->createElement("rendition", $value);
            $rendition = $tagsDecl->appendChild($newnode);
            $rendition->setAttribute('xml:id', "P".$key);
            $rendition->setAttribute('scheme', 'css');
        }
        foreach ($Tdecl as $key=>$value) {
            $newnode = $dom->createElement("rendition", $value);
            $rendition = $tagsDecl->appendChild($newnode);
            $rendition->setAttribute('xml:id', "T".$key);
            $rendition->setAttribute('scheme', 'css');
        }

        # surrounding internalstyles
        error_log("<li>surrounding internalstyles</li>\n", 3, self::_DEBUGFILE_);
        $entries = $xpath->query("//tei:front"); $front = $entries->item(0);
        $entries = $xpath->query("//tei:body"); $body = $entries->item(0);
        $entries = $xpath->query("//tei:back"); $back = $entries->item(0);

        $entries = $xpath->query("//tei:body/tei:*");
        $current = $prev = $next = array();
        $newsection = $section = "";
        $newbacksection = $backsection = "";
        foreach ($entries as $item) {
            // prev
            $item->previousSibling ? $previtem=$this->greedy($item->previousSibling) : $previtem=null;
            // current
            $current = $this->greedy($item);
            // next
            $item->nextSibling ? $nextitem=$this->greedy($item->nextSibling) : $nextitem=null;

            # surrounding internal styles
            if ($current == null) { // normal paragraph
                $newsection = "body";
            } else {
                if ( isset($current['section'])) {
                    $newsection = $current['section'];
                }
                if ($newsection == "back") {
                    if ( isset($current['rend'])) {
                        $newbacksection = $current['rend'];
                    }
                }
            }
            if ( isset($current['surround'])) { 
                $surround = $current['surround'];
                switch($surround) {
                    case "-*":
                        if ( isset($previtem['section'])) {
                            $newsection = $previtem['section'];
                            if ($newsection=="back" and isset($previtem['rend'])) {
                                $newbacksection = $previtem['rend'];
                            }
                        }
                        break;
                    case "*-":
                        if ( isset($nextitem['section'])) {
                            $newsection = $nextitem['section'];
                            if ($newsection=="back" and isset($nextitem['rend'])) {
                                $newbacksection = $nextitem['rend'];
                            }
                        }
                        break;
                }
            }
            if ($section!==$newsection or $backsection!==$newbacksection) { // new section
                $section = $newsection;
                switch ($section) {
                    case 'head';
                        $div = $dom->createElement("div");
                        $div->setAttribute('rend', "LodelMeta");
                        $front->appendChild($div);
                        break;
                    case 'body';
                        $div = $body;
                        break;
                    case 'back';
                        if ($backsection !== $newbacksection) {
                            $backsection = $newbacksection;
                            switch($backsection) {
                                case 'appendix':
                                    $div = $dom->createElement("div");
                                    $div->setAttribute('rend', "LodelAppendix");
                                    $back->appendChild($div);
                                    break;
                                case 'bibliography':
                                    $div = $dom->createElement("div");
                                    $div->setAttribute('rend', "LodelBibliography");
                                    $back->appendChild($div);
                                    break;
                            }
                        }
                        break;
                }
            }
            $div->appendChild($item);

        }

        $dom->encoding = "UTF-8";
        $dom->resolveExternals = true;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->normalizeDocument();
        $debugfile=$this->_param['TMPPATH']."lodeltei.xml";@$dom->save($debugfile);
        $this->_param['xmloutputpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/".$this->_param['prefix'].".lodel.tei.xml";
        $dom->save($this->_param['xmloutputpath']);

        $this->_param['lodelTEI'] = "". $dom->saveXML();

        return true;
    }


// end of Servel class.
}

?>
