<?php
/**
 * otxserver.class.php
 * PHP >= 5.2
 * @author Nicolas Barts
 * @copyright 20010, CLEO/Revues.org
 * @licence http://www.gnu.org/copyleft/gpl.html
**/
include_once('inc/utils.inc.php');
include_once('inc/EM.odd.php');


/**
 * Singleton class
**/
class OTXserver
{
    // Hold an instance of the Singleton class
    private static $_instance_;

    // Inputs
    protected $input = array('request'=>"", 'mode'=>"", 'modelpath'=>"", 'entitypath'=>"");
    // Outputs
    protected $output = array('status'=>"", 'xml'=>"", 'report'=>"", 'contentpath'=>"", 'lodelxml'=>"");

    private $report = array();

    protected $meta = array();
    protected $EModel = array();
    private $EMotx = array();
    private $EMTEI = array();
    private $EMandatory = array();
    private $LodelImg = array();

    private $dom = array();
    private $automatic = array();
    private $rend = array();
    private $rendition = array();
    private $Pnum = 0;
    private $Tnum = 0;
    private $tagsDecl = array();

    private $log = array();

    private $_param = array();
    private $_data = array();
    private $_status="";
    private $_trace="";
    private $_iserror=false;
    private $_isdebug=true;
    private $oostyle = array();
    private $_dbg = 1;
    private $_db; // adodb instance

    const _SOAP_MODELPATH_  = __SOAP_SCHEMA__;
    const _SOAP_ENTITYPATH_ = __SOAP_ATTACHMENT__;
    const _SOAP_LOCKFILE_   = __SOAP_LOCK__;
    const _DEBUGFILE_       = __DEBUG__;
    const _SERVER_CACHETIME_    = __SERVER_CACHETIME__;
    const _SERVER_CACHE_        = __SERVER_CACHE__;
    const _SERVER_TMP_          = __SERVER_TMP__;
    const _SERVER_INC_          = __SERVER_INC__;
    const _SERVER_LIB_          = __SERVER_LIB__;
    const _SERVER_SERVER_       = __SERVER_SERVER__;
    const _SERVER_PORT_         = __SERVER_PORT__;
    const _SOFFICE_PYTHONPATH_      = __SOFFICE_PYTHONPATH__;
    const _DB_DRIVER_               = __DB_DRIVER__;
    const _DB_PATH_                 = __DB_PATH__;


    /** A private constructor; prevents direct creation of object (singleton because) **/
    private function __construct($request="", $mode="", $modelpath="", $entitypath="") {
    error_log("<ul id=\"".date("Y-m-d H:i:s")."\">\n<h3>__construct()</h3>\n",3,self::_DEBUGFILE_);
        touch(self::_SOAP_LOCKFILE_);
        @unlink(self::_DEBUGFILE_);

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
        $this->_param['CACHETIME'] = self::_SERVER_CACHETIME_;
        $this->_param['CACHEPATH'] = self::_SERVER_CACHE_;
        $this->_param['TMPPATH'] = self::_SERVER_TMP_;
        $this->_param['INCPATH'] = self::_SERVER_INC_;
        $this->_param['LIBPATH'] = self::_SERVER_LIB_;
        $this->_param['SERVERURI'] =  self::_SERVER_SERVER_;
        $this->_param['SERVERPORT'] = self::_SERVER_PORT_;
        $this->_param['DEBUGPATH'] = self::_DEBUGFILE_;

        $this->log['warning'] = array();

        require self::_SERVER_LIB_.'adodb5/adodb.inc.php';
        $this->_db = ADONewConnection(self::_DB_DRIVER_);
        $this->_db->connect(self::_DB_PATH_);
    }
    /** Prevent users to clone the instance (singleton because) **/
    public function __clone() {
        $this->_status="Cannot duplicate a singleton !";$this->_iserror=true;
        throw new Exception($this->_status,E_ERROR);
    }
    public function __destruct() {
    error_log("\n<h3>__destruct</h3></ul>",3,self::_DEBUGFILE_);
        //@unlink($this->_param['sourcepath']);
        $this->oo2report('otx');

        unlink(self::_SOAP_LOCKFILE_);
    }
    public function __wakeup(){
       if (!self::$_instance_) {
            self::$_instance_ = $this;
       } else {
            trigger_error("Unserializing this instance while another exists voilates the Singleton pattern", E_USER_ERROR);
            return null;
        }
    }
    public function __toString() {
        return $this->_status;
    }
    public function __set($key, $value) {
    error_log("<li>__set($key,$value)</li>\n",3,self::_DEBUGFILE_);
        if ( array_key_exists($key, $this->_param)) {
            $this->_param[$key] = $value;
        } else {
            $trace = debug_backtrace();
            trigger_error('Undefined property via __set(): '.$key.' : '.$value.' in '.$trace[0]['file'].' on line '.$trace[0]['line'], E_USER_NOTICE);
            return null;
        }
    }
    public function __get($name) {
    error_log("<li>__get($name)</li>\n",3,self::_DEBUGFILE_);
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
            error_log("<li>Singleton: First invocation only !</li>\n",3,self::_DEBUGFILE_);
            return self::$_instance_;
        }
        else {
            error_log("<li>Singleton: return instance</li>\n",3,self::_DEBUGFILE_);
            return self::$_instance_;
        }
    }



/**
 * just do it !
**/
    public function run() {
    error_log("<h2>run()</h2>\n",3,self::_DEBUGFILE_);

        $suffix = "odt";
        if (false !== strpos($this->_param['mode'], ":")) {
            list($action, $tmp) = explode(":", $this->_param['mode']);
            switch($action) {
                case 'soffice':
                    $suffix = $tmp;
                    break;
                case 'lodel':
                    $this->_param['type'] = $tmp;
                    break;
            }
        }
        else {
            $action = $this->_param['mode'];
            $suffix = "odt";
        }

        if ($action !== "hello") {
            $this->params();
        }
        $this->_status = "todo: $action";

        switch ($action) {
            case 'soffice':
            error_log("<li>case soffice</li>\n",3,self::_DEBUGFILE_);
                $this->soffice($suffix);
                $this->output['contentpath'] = $this->_param['outputpath'];
                if ($suffix=="odt") {
                    $this->oo2report('soffice', $this->_param['odtpath']);
                }
                break;
            case 'lodel':
            error_log("<li>case lodel</li>\n",3,self::_DEBUGFILE_);
                $this->soffice();
                $this->oo2report('soffice', $this->_param['odtpath']);
                $this->Schema2OO();
                $this->lodelodt();
                $this->oo2lodelxml();
                $this->output['lodelxml'] = null;
                $this->oo2report('lodel', $this->_param['lodelodtpath']);
                $this->output['contentpath'] = $this->_param['lodelodtpath'];
                $this->loodxml2xml();
                $this->output['xml'] = _windobclean($this->_param['TEI']);
                $jsonreport = json_encode($this->report);
                $debugfile=$this->_param['TMPPATH']."report.json";@file_put_contents($debugfile, $jsonreport);
                $this->output['report'] = $jsonreport;
                break;
            case 'partners':
            case 'cairn':
                $this->_status = "todo: partners/cairn";
                break;
            case 'hello':
                $this->hello();
                return $this->output;
                break;
            default:
                $this->_status="error: unknown action ($action)";$this->_iserror=true;
                error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
                throw new Exception($this->_status,E_USER_ERROR);
        }

        $this->_status = __OTX_NAME__;
        $this->output['status'] = $this->_status;

        return $this->output;
    }


/**
 * dynamic mapping of Lodel EM
**/
    protected function Schema2OO() {
    error_log("<h2>Schema2OO()</h2>\n",3,self::_DEBUGFILE_);

        $modelpath = $this->_param['modelpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/"."model.xml";
        error_log("<li>EM: $modelpath</li>\n",3,self::_DEBUGFILE_);

        $this->EMTEI = _em2tei();

        $domxml = new DOMDocument;
        $domxml->encoding = "UTF-8";
        $domxml->recover = true;
        $domxml->strictErrorChecking = false;
        $domxml->resolveExternals = false;
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        if (! $domxml->load($this->_param['modelpath'])) {
            $this->_status="error load model.xml";error_log("<li>{$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }

        # OTX EM test
        if (! strstr($domxml->saveXML(), "<col name=\"otx\">")) {
            // TODO : warning and load a default OTX EM ?!
            $this->_status="error: EM not OTX compliant";error_log("<h1>{$this->_status}</h1>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_USER_ERROR);
        }

        $Model = array();
        $OOTX = array();
        $nbEmStyle = $nbOtxStyle = 0;
        foreach ($domxml->getElementsByTagName('row') as $node) {
            $value = $keys = $g_otx = $lang = '';
            if ($node->hasChildNodes()) {
                $row=array(); $otxvalue=null; $bstyle=false;
                foreach ($node->childNodes as $tag) {
                    if ($tag->hasAttributes()) {
                        foreach ($tag->attributes as $attr) {
                            if ($attr->name === "name") {
                                switch ($attr->value) {
                                    case "name":
                                    case "type":
                                        if (! isset($row['name'])) $row['name'] = trim($tag->nodeValue);
                                      break;
                                    case "style":
                                        $row[$attr->value] = trim($tag->nodeValue);
                                        $style = trim($tag->nodeValue);
                                        if ($style == '') { // empty : no style defined !
                                            continue 4;
                                        }
                                        $bstyle = true; 
                                        break;
                                    case "g_type":
                                    case "g_name":
                                        $gvalue = trim($tag->nodeValue);
                                        $row['gname'] = trim($tag->nodeValue);
                                        break;
                                    case 'surrounding':
                                        $row[$attr->value] = trim($tag->nodeValue);
                                        break;
                                    case "lang":
                                        $lang = trim($tag->nodeValue);
                                        $row[$attr->value] = trim($tag->nodeValue);
                                        break;
                                    case "otx":
                                        $nodevalue = trim($tag->nodeValue);
                                        if ($nodevalue == '') {
                                            continue 4;
                                        }
                                        $row[$attr->value] = trim($tag->nodeValue);
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                    }
                }

                // EM otx style definition
                if ( isset($row['otx'])) {
                    if (! isset($row['style'])) {
                    error_log("\n<h1>OUPS..!? (EMstyle)</h1>\n",3,self::_DEBUGFILE_);
                    continue;
                    }

                    $emotx = $row['otx'];
                    if (! isset($this->EMTEI[$emotx])) {
                    error_log("<li>?? OTX: $emotx ??</li>\n",3,self::_DEBUGFILE_);
                        // TODO ?
                        continue;
                    } 
                    else {
                        $nbOtxStyle++;
                        $otxkey = $otxvalue = '';
                        $emotx = $this->EMTEI[$emotx];
                        if ( isset($row['lang']) and $emotx==="header:keywords") {  // TODO !?!
                            $emotx .= "-".$row['lang'];
                        }
                        if ( strstr($emotx, ":")) {
                            list($otxkey, $otxvalue) = explode(":", $emotx);
                            $this->EMotx[$otxvalue]['key'] = $otxkey;
                        } else {
                            $otxvalue = $emotx;
                            error_log("<h1>??? otx: $emotx ???</li>\n",3,self::_DEBUGFILE_);
                            continue;
                        }
                    }

                    $style = $row['style']; $nbEmStyle++;
                    if (! strstr($style, ",")) {
                        $OOTX[$style] = $otxvalue;
                        isset($row['name']) ? $Model[$style]=$row['name'] : $Model[$style]=$style;
                    } else {
                        foreach ( explode(",", $style) as $stl) {
                            $stl = trim($stl);
                            $OOTX[$stl] = $otxvalue;
                            isset($row['name']) ? $Model[$stl]=$row['name'] : $Model[$stl]=$style;
                        }
                    }

                    if ( isset($row['gname']) and $emotx!='') {
                        $gvalue = $row['gname'];
                        $this->EMandatory[$gvalue] = $otxvalue;
                    }
                }

            }
        }
        error_log("<li>parse EM ok</li>\n",3,self::_DEBUGFILE_);

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
                if ($newkey!='' and $lang!='')
                    $this->EModel[$newkey] = $value."-$lang";
                else 
                    $this->EModel[$key] = $value;
            }
        }
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
                                if (! isset($this->EMTEI[$value])) {
                                error_log("<li>EMTEI: $value ???</li>\n",3,self::_DEBUGFILE_);
                                    continue;
                                }
                                $value = $this->EMTEI[$value];
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

        error_log("<li>DONE.</li>\n",3,self::_DEBUGFILE_);
        unset($domxml);
        return true;
    }



/**
 * transformation d'un odt en lodel-odt : format pivot de travail
**/
    protected function lodelodt() {
    error_log("<h3>lodelodt</h3>\n",3,self::_DEBUGFILE_);

        $cleanup = array('/_20_Car/', '/_20_/', '/_28_/', '/_29_/', '/_5f_/', '/_5b_/', '/_5d_/', '/_32_/', '/WW-/' );

        $odtfile = $this->_param['odtpath'];
        $this->_param['lodelodtpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/".$this->_param['prefix'].".lodel.odt";
        $lodelodtfile = $this->_param['lodelodtpath'];
        if (! copy($odtfile, $lodelodtfile)) {
            $this->_status="error copy file; ".$lodelodtfile;error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        # odt...
        $za = new ZipArchive();
        if (! $za->open($lodelodtfile)) {
            $this->_status="error open ziparchive; ".$lodelodtfile;error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        # ----- office:meta ----------------------------------------------------------
        error_log("<li>office:meta</li>\n\n", 3, self::_DEBUGFILE_);
        if (! $OOmeta=$za->getFromName('meta.xml')) {
            $this->_status="error get meta.xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $dommeta = new DOMDocument;
        $dommeta->encoding = "UTF-8";
        $dommeta->resolveExternals = false;
        $dommeta->preserveWhiteSpace = false;
        $dommeta->formatOutput = true;
        if (! $dommeta->loadXML($OOmeta)) {
            $this->_status="error load meta.xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-meta.xml";@$dommeta->save($debugfile);
        # cleanup
        $lodelmeta = _windobclean($OOmeta);
        # lodel
        $domlodelmeta = new DOMDocument;
        $domlodelmeta->encoding = "UTF-8";
        $domlodelmeta->resolveExternals = false;
        $domlodelmeta->preserveWhiteSpace = false;
        $domlodelmeta->formatOutput = true;
        if (! $domlodelmeta->loadXML($lodelmeta)) {
            $this->_status="error load lodel-meta.xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $domlodelmeta->normalizeDocument();
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-meta.lodel.xml";@$domlodelmeta->save($debugfile);

        # ----- office:settings ----------------------------------------------------------
        error_log("<li>office:settings</li>\n\n", 3, self::_DEBUGFILE_);
        if (! $OOsettings=$za->getFromName('settings.xml')) {
            $this->_status="error get settings.xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $domsettings = new DOMDocument;
        $domsettings->encoding = "UTF-8";
        $domsettings->resolveExternals = false;
        $domsettings->preserveWhiteSpace = false;
        $domsettings->formatOutput = true;
        if (! $domsettings->loadXML($OOsettings)) {
            $this->_status="error load settings.xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-settings.xml";@$domsettings->save($debugfile);
        # cleanup
        $lodelsettings = _windobclean($OOsettings);
        # lodel
        $domlodelsettings = new DOMDocument;
        $domlodelsettings->encoding = "UTF-8";
        $domlodelsettings->resolveExternals = false;
        $domlodelsettings->preserveWhiteSpace = false;
        $domlodelsettings->formatOutput = true;
        if (! $domlodelsettings->loadXML($lodelsettings)) {
            $this->_status="error load lodel-settings.xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $domlodelsettings->normalizeDocument();
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-settings.lodel.xml";@$domlodelsettings->save($debugfile);

        # ----- office:styles ---------------------------------------
        error_log("<li>office:styles</li>\n",3,self::_DEBUGFILE_);
        if (! $OOstyles=$za->getFromName('styles.xml')) {
            $this->_status="error get styles.xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $domstyles = new DOMDocument;
        $domstyles->encoding = "UTF-8";
        $domstyles->resolveExternals = false;
        $domstyles->preserveWhiteSpace = false;
        $domstyles->formatOutput = true;
        if (! $domstyles->loadXML($OOstyles)) {
            $this->_status="error load styles.xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-styles.xml";@$domstyles->save($debugfile);
        # cleanup
        $lodelstyles = preg_replace($cleanup, "", _windobclean($OOstyles));
        # lodel
        $domlodelstyles = new DOMDocument;
        $domlodelstyles->encoding = "UTF-8";
        $domlodelstyles->resolveExternals = false;
        $domlodelstyles->preserveWhiteSpace = false;
        $domlodelstyles->formatOutput = true;
        if (! $domlodelstyles->loadXML($lodelstyles)) {
            $this->_status="error load lodel-styles.xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        // lodel-cleanup++
        $this->lodelcleanup($domlodelstyles);
        $domlodelstyles->normalizeDocument(); 
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-styles.lodel.xml";@$domlodelstyles->save($debugfile);

        # ----- office:content -------------------------------------------------------
        error_log("<li>office:content</li>\n",3,self::_DEBUGFILE_);
        if (! $OOcontent=$za->getFromName('content.xml')) {
            $this->_status="error get content.xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $domcontent = new DOMDocument;
        $domcontent->encoding = "UTF-8";
        $domcontent->resolveExternals = false;
        $domcontent->preserveWhiteSpace = false;
        $domcontent->formatOutput = true;
        if (! $domcontent->loadXML($OOcontent)) {
            $this->_status="error load content.xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-content.xml";@$domcontent->save($debugfile);
        # cleanup
        $lodelcontent = preg_replace($cleanup, "", _windobclean($OOcontent));
        # lodel
        $domlodelcontent = new DOMDocument;
        $domlodelcontent->encoding = "UTF-8";
        $domlodelcontent->resolveExternals = false;
        $domlodelcontent->preserveWhiteSpace = false;
        $domlodelcontent->formatOutput = true;
        if (! $domlodelcontent->loadXML($lodelcontent)) {
            $this->_status="error load lodel-content.xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
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
        // 3. (document) meta
        $this->oolodel2meta($domlodelcontent);

        $this->meta2lodelodt($domlodelmeta);
        # LodelODT
        if (! $za->addFromString('meta.xml', $domlodelmeta->saveXML())) {
            $this->_status="error ZA addFromString lodelmeta";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        if (! $za->addFromString('settings.xml', $domlodelsettings->saveXML())) {
            $this->_status="error ZA addFromString lodelsettings";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        if (! $za->addFromString('styles.xml', $domlodelstyles->saveXML())) {
            $this->_status="error ZA addFromString lodelstyles";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        if (! $za->addFromString('content.xml', $domlodelcontent->saveXML())) {
            $this->_status="error ZA addFromString lodelcontent";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $za->close();

        # lodel fodt (Flat ODT)
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
    office:version="1.2" office:mimetype="application/vnd.oasis.opendocument.text">
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
        $domfodt->resolveExternals = false;
        $domfodt->preserveWhiteSpace = false;
        $domfodt->formatOutput = true;
        if (! $domfodt->loadXML($xmlfodt)) {
            $this->_status="error load fodt xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $domfodt->normalizeDocument();
$debugFile=$this->_param['TMPPATH'].$this->_dbg++."-lodel.fodt.xml";@$domfodt->save($debugFile);

        # add xml:id (otxid.xsl)
        $xslfilter = $this->_param['INCPATH']."otxid.xsl";
        $xsl = new DOMDocument;
        if (! $xsl->load($xslfilter)) {
            $this->_status="error load xsl ($xslfilter)";error_log("<li>{$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);
        if (! $idfodt=$proc->transformToXML($domfodt)) {
            $this->_status="error transform xslt ($xslfilter)";error_log("<li>{$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $domidfodt = new DOMDocument;
        $domidfodt->encoding = "UTF-8";
        $domidfodt->resolveExternals = false;
        $domidfodt->preserveWhiteSpace = false;
        $domidfodt->formatOutput = true;
        if (! $domidfodt->loadXML($idfodt)) {
            $this->_status="error load idfodt xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $domidfodt->normalizeDocument();
$debugfile=$this->_param['TMPPATH'].$this->_dbg++."-fodt.id.xml";@$domidfodt->save($debugfile);

        # oo to lodeltei xslt [oo2lodeltei.xsl]
        $xslfilter = $this->_param['INCPATH']."oo2lodeltei.xsl";
        $xsl = new DOMDocument;
        if (! $xsl->load($xslfilter)) {
            $this->_status="error load xsl ($xslfilter)";error_log("<li>{$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);
        if (! $teifodt=$proc->transformToXML($domidfodt)) {
            $this->_status="error transform xslt ($xslfilter)";error_log("<li>{$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }

        $domteifodt = new DOMDocument;
        $domteifodt->encoding = "UTF-8";
        $domteifodt->resolveExternals = false;
        $domteifodt->preserveWhiteSpace = false;
        $domteifodt->formatOutput = true;
        if (! $domteifodt->loadXML($teifodt)) {
            $this->_status="error load teifodt xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $domteifodt->normalizeDocument();
$debugfile=$this->_param['TMPPATH'].$this->_dbg++."-fodt.teilodel.xml";@$domteifodt->save($debugfile);

        $this->dom['teifodt'] = $domteifodt;
        //$this->lodeltei($domteifodt);
        return true;
    }



/**
 * transformation d'un lodel-odt en lodel-xml ( flat TEI... [raw mode] )
**/
    protected function oo2lodelxml() {
    error_log("\n<h3>oo2lodelxml()</h3>\n",3,self::_DEBUGFILE_);

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

        $entries = $xpath->query("//tei:list[@rendition]");
        foreach ($entries as $item) {
            $rendition = $item->getAttribute("rendition");
            if ( isset($this->rendition[$rendition])) {
                if ($this->rendition[$rendition]['rendition']=="ordered") {
                    $item->setAttribute("type", "ordered");
                }
            }
            else {
                $item->setAttribute("type", "unordered");
            }
            $item->removeAttribute("rendition");
        }

        // $entries = $xpath->query("//tei:p[@rendition] or //tei:s[@rendition]");
        $entries = $xpath->query("//tei:*[@rendition]");
        foreach ($entries as $item) {
            $nodename = $item->nodeName;
            if ($nodename=="p" or $nodename=="s" or $nodename=="cell") {
                if ( $value=$item->getAttribute("rendition")) {
                    if ($nodename=="cell") {
                        $name = $value;
                        $id = ''; list($table, $id) = explode(".", $name);
                        $value = "#td".$table[strlen($table)-1].$id;
                    }
                    // rend ?
                    if ( isset($this->automatic[$value]) && $this->automatic[$value]!="standard") {
                        $rend = $this->automatic[$value];
                        $item->setAttribute("rend", $rend);
                    }
                    // rendition ?
                    if ( isset($this->rendition[$value])) {
                        // xml:lang ?
                        if ($this->rendition[$value]['lang']!='') {
                            $lang = $this->rendition[$value]['lang'];
                            $item->setAttribute("xml:lang", $lang);
                        }
                        // css style
                        if ($this->rendition[$value]['rendition']!='') {
                            $rendition = $this->rendition[$value]['rendition'];
                            $item->setAttribute("rendition", $value);
                            $tagsdecl[$value] = $rendition;
                        } else {
                            $item->removeAttribute("rendition");
                        }
                    } else {
                        $item->removeAttribute("rendition");
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
            if ( isset($this->EMotx[$rend])) {
                continue;  // lodel style : skip !
            }

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

        $this->tagsDecl = $tagsdecl;
        foreach ($tagsdecl as $key=>$value) {
            if ( preg_match("/^#P(\d+)$/", $key, $match)) {
                $Pdecl[$match[1]] = $value;
                continue;
            }
            if ( preg_match("/^#T(\d+)$/", $key, $match)) {
                $Tdecl[$match[1]] = $value;
                continue;
            }
            if ( preg_match("/^#(.+)$/", $key, $match)) {
                $decl[$match[1]] = $value;
                continue;
            }
        }
        ksort($Pdecl); ksort($Tdecl); ksort($decl);

        $header = $dom->getElementsByTagName('teiHeader')->item(0);
        $entries = $xpath->query("//tei:teiHeader/tei:encodingDesc");
        if ($entries->length) {
            $encodingDesc = $entries->item(0);
        } else {
            $newnode = $dom->createElement("encodingDesc");
            $encodingDesc = $header->appendChild($newnode);
        }
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
        foreach ($decl as $key=>$value) {
            $newnode = $dom->createElement("rendition", $value);
            $rendition = $tagsDecl->appendChild($newnode);
            $rendition->setAttribute('xml:id', $key);
            $rendition->setAttribute('scheme', 'css');
        }

        # surrounding internalstyles
        error_log("<li># surrounding internalstyles</li>\n",3,self::_DEBUGFILE_);

        $entries = $xpath->query("//tei:front"); $front = $entries->item(0);
        $entries = $xpath->query("//tei:body"); $body = $entries->item(0);
        $entries = $xpath->query("//tei:back"); $back = $entries->item(0);

        $entries = $xpath->query("//tei:body/tei:*");
        $current = $previtem = $nextitem = array();
        $section = $newsection = "";
        $newbacksection = $backsection = "";
        foreach ($entries as $item) {

error_log("\n\n<li>ITEM {$item->nodeName} : {$item->nodeValue}</li>\n",3,self::_DEBUGFILE_);
if ($rend=$item->getAttribute("rend")) error_log("<li>@rend = $rend</li>\n",3,self::_DEBUGFILE_);

            // current
            $this->greedy($item, $current);
$dbg="<li>CURRENT = <pre>\n".print_r($current,true)."</pre></li>\n";error_log($dbg,3,self::_DEBUGFILE_);

/*
            do {
                $prev = $prev->previousSibling;
                error_log("<li>prev {$prev->nodeName} : {$prev->nodeValue}</li>\n",3,self::_DEBUGFILE_);
            } while ($prev and !$this->greedy($prev, $previtem));
$dbg="<li>PREV = <pre>\n".print_r($previtem,true)."</pre></li>\n";error_log($dbg,3,self::_DEBUGFILE_);
*/
/*
                            $next = $item;
                            do {
                                $next = $next->nextSibling;
                                error_log("<li>next {$next->nodeName} : {$next->nodeValue}</li>\n",3,self::_DEBUGFILE_);
                            } while ($next and !$this->greedy($next, $nextitem));
$dbg="<li>NEXT = <pre>\n".print_r($nextitem,true)."</pre></li>\n";error_log($dbg,3,self::_DEBUGFILE_);
*/

            if ($current!=null) {
                if ( isset($current['surround'])) {
                    $surround = $current['surround'];
                    switch($surround) {
                        case "-*":
                        error_log("<li>case -*</li>\n",3,self::_DEBUGFILE_);
                            // prev
                            $prev = $item;
                                $prev = $prev->previousSibling;
                                if ($prev) {
                                error_log("<li>prev {$prev->nodeName} : {$prev->nodeValue}</li>\n",3,self::_DEBUGFILE_);
                                $this->greedy($prev, $previtem);
$dbg="<li>PREV = <pre>\n".print_r($previtem,true)."</pre></li>\n";error_log($dbg,3,self::_DEBUGFILE_);
                                }
                            if ( isset($previtem['section'])) {
                                $newsection = $previtem['section'];
                                if ($newsection=="back" and isset($previtem['rend'])) {
                                    $newbacksection = $previtem['rend'];
                                }
                            } else {
                                if ( isset($current['section'])) {
                                    $newsection = $current['section'];
                                }
                            }
                            break;
                        case "*-":
                        error_log("<li>case *-</li>\n",3,self::_DEBUGFILE_);
                            // next
                            $next = $item;
                                $next = $next->nextSibling;
                                if ($next) {
                                error_log("<li>next {$next->nodeName} : {$next->nodeValue}</li>\n",3,self::_DEBUGFILE_);
                                $this->greedy($next, $nextitem);
$dbg="<li>NEXT = <pre>\n".print_r($nextitem,true)."</pre></li>\n";error_log($dbg,3,self::_DEBUGFILE_);
                                }
                            if ( isset($nextitem['section'])) {
                                $newsection = $nextitem['section'];
                                if ($newsection=="back" and isset($nextitem['rend'])) {
                                    $newbacksection = $nextitem['rend'];
                                }
                            } else {
                                if ( isset($current['section'])) {
                                    $newsection = $current['section'];
                                }
                            }
                            break;
                        default:
                        error_log("<li>case default ???</li>\n",3,self::_DEBUGFILE_);
                            break;
                    }
                } else {
                    if ( isset($current['section'])) {
                        $newsection = $current['section'];
                        if ($newsection == "back") {
                            if ( isset($current['rend'])) {
                                $newbacksection = $current['rend'];
                            }
                        }
                    } else {
                        $newsection = $section;
                    }
                }
            } else {
                $newsection = $section;
error_log("<li>??? newsection = $section</li>\n",3,self::_DEBUGFILE_);
            }

error_log("<li>=> section = $section</li>\n",3,self::_DEBUGFILE_);
error_log("<li>=> newsection = $newsection</li>\n",3,self::_DEBUGFILE_);
error_log("<li>=> backsection = $backsection</li>\n",3,self::_DEBUGFILE_);
error_log("<li>=> newbacksection = $newbacksection</li>\n",3,self::_DEBUGFILE_);

            if ($section!==$newsection or $backsection!==$newbacksection) { // new section
                error_log("<li>newsection: $newsection or $newbacksection</li>\n",3,self::_DEBUGFILE_);
                if ($section!==$newsection) {
                    error_log("<li>=> newsection: $newsection</li>\n",3,self::_DEBUGFILE_);
                    $section = $newsection;
                } 
                elseif ($backsection!==$newbacksection) {
                error_log("<li>=> newbacksection: $newbacksection</li>\n",3,self::_DEBUGFILE_);
                    $section = "back";
                }

                switch ($section) {
                    case 'head';
                    error_log("<li>case head</li>\n",3,self::_DEBUGFILE_);
                        $div = $dom->createElement("div");
                        $div->setAttribute('rend', "LodelMeta");
                        $front->appendChild($div);
                        break;
                    case 'body';
                    error_log("<li>case body</li>\n",3,self::_DEBUGFILE_);
                        $div = $body;
                        break;
                    case 'back';
                    error_log("<li>case back</li>\n",3,self::_DEBUGFILE_);
                        if ($backsection !== $newbacksection) {
                            $backsection = $newbacksection;
                            switch($backsection) {
                                case 'appendix':
                                error_log("<li>case appendix</li>\n",3,self::_DEBUGFILE_);
                                    $div = $dom->createElement("div");
                                    $div->setAttribute('rend', "LodelAppendix");
                                    $back->appendChild($div);
                                    break;
                                case 'bibliography':
                                error_log("<li>case bibliography</li>\n",3,self::_DEBUGFILE_);
                                    $div = $dom->createElement("div");
                                    $div->setAttribute('rend', "LodelBibliography");
                                    $back->appendChild($div);
                                    break;
                                default:
                                error_log("<li>case default back ???</li>\n",3,self::_DEBUGFILE_);
                                    break;
                            }
                        }
                        break;
                    default:
                    error_log("<li>case default ??</li>\n",3,self::_DEBUGFILE_);
                        break;
                }
            }
            if ($backsection and $backsection!=$current['rend']) {
            error_log("\n<li>$backsection != {$current['rend']}</li>\n",3,self::_DEBUGFILE_);
                $item->setAttribute('rend', "$backsection-{$current['rend']}");
            }

            $div->appendChild($item);
        }

        # <hi> cleanup (tag hi with no attribute)
        $this->hicleanup($dom, $xpath); // <hi> to <nop> ...
        $search = array("<nop>", "</nop>");
        $lodeltei = "". str_replace($search, "", $dom->saveXML()); // ... and delete <nop>

        /** TODO Warning **/
        //$lodeltei = preg_replace("/([[[UNTRANSLATED.*]]])/s", "<!-- \1 -->", $lodeltei);

        $dom->encoding = "UTF-8";
        $dom->resolveExternals = false;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($lodeltei);
        $dom->normalizeDocument();
        $debugfile=$this->_param['TMPPATH']."lodeltei.xml";@$dom->save($debugfile);
        $this->_param['xmloutputpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/".$this->_param['prefix'].".lodeltei.xml";
        $dom->save($this->_param['xmloutputpath']);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('tei', 'http://www.tei-c.org/ns/1.0');

        # Warnings : recommended
        $mandatory = array();
        foreach ($this->EMandatory as $key=>$value) {
            if ( preg_match("/^dc\.(.+)$/", $key, $match)) {
                list($section, $element) = explode(":", $value);
                $query = "//tei:p[starts-with(@rend,'$element')]";
                $entries = $xpath->query($query);
                if (! $entries->length) {
                    $this->_status = "dc:{$match[1]} not found";
                    array_push($mandatory, $this->_status);
                    array_push($this->log['warning'], $this->_status);
                    error_log("<li>? {$this->_status}</li>\n",3,self::_DEBUGFILE_);
                }
            }
        }
        $this->report['warning'] = $mandatory;
/*
        $dom->resolveExternals = false;
        //$dom->validateOnParse = true;
        if (! $dom->validate()) {
            $this->_status = "Warning: Lodel TEI-Lite is not valid !";
            array_push($this->log['warning'], $this->_status);
            error_log("\n<li>? {$this->_status}</li>\n",3,self::_DEBUGFILE_);
        } else {
            $this->_status = "Lodel TEI-Lite is valid.";
            error_log("\n<li>{$this->_status}</li>\n",3,self::_DEBUGFILE_);
        }
        $this->log['status']['lodeltei'] = $this->_status;
*/
        $this->_param['lodelTEI'] = "". $dom->saveXML();
        return true;
    }

        private function hicleanup(&$dom, &$xpath) {
        error_log("<h4>hicleanup()</h4>\n",3,self::_DEBUGFILE_);
            $bool = false;
            $entries = $xpath->query("//tei:hi", $dom); 
            foreach ($entries as $item) {
                if (! $item->hasAttributes()) {
                    $parent = $item->parentNode;
                    $newitem = $dom->createElement("nop");
                    if ($item->hasChildNodes()) {
                        foreach ($item->childNodes as $child) {
                            $clone = $child->cloneNode(true);
                            $newitem->appendChild($clone);
                        }
                    }
                    else {
                        $newitem->nodeValue = $item->nodeValue;
                    }
                    if (! $parent->replaceChild($newitem, $item)) {
                        $this->_status="error replaceChild";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
                        throw new Exception($this->_status,E_ERROR);
                    }
                    $bool = true;
                }
            }
            if ($bool) {
                $this->hicleanup($dom, $xpath);
            }
        }


/**
 * transformation d'un lodel-xml en xml (TEI P5)
**/
    protected function loodxml2xml() {
    error_log("\n<h2>loodxml2xml()</h2>\n",3,self::_DEBUGFILE_);
        $lodelmeta = array();

        # domloodxml to domxml
        $dom = new DOMDocument;
        $dom->encoding = "UTF-8";
        $dom->resolveExternals = false;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if (! $dom->loadXML($this->_param['lodelTEI'])) {
            $this->_status="error load lodel.tei.xml";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
        }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('tei', 'http://www.tei-c.org/ns/1.0');

        # /tei/teiHeader
        $entries = $xpath->query("//tei:teiHeader"); $header = $entries->item(0);
        # /tei/teiHeader/fileDesc/titleStmt
        $entries = $xpath->query("//tei:teiHeader/tei:fileDesc/tei:titleStmt"); $titlestmt = $entries->item(0);
        # /tei/teiHeader/fileDesc/titleStmt/title
        $entries = $xpath->query("//tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title");
        foreach ($entries as $entry) { $titlestmt->removeChild($entry); }

        # lodel:uptitle
        $entries = $xpath->query("//tei:p[@rend='uptitle']");
        foreach ($entries as $entry) {
            $parent = $entry->parentNode;
            $new = $dom->createElement('title');
            $new->setAttribute('type', "sup");
            if ( $lang=$entry->getAttribute('xml:lang')) { $new->setAttribute('xml:lang', $lang); }
            if ( $id=$entry->getAttribute('xml:id')) { $new->setAttribute('xml:id', $id); }
            if ($entry->hasChildNodes()) {
                foreach ($entry->childNodes as $child) {
                    $clone = $child->cloneNode(true);
                    $new->appendChild($clone);
                }
            }
            else {
                $new->nodeValue = $p->nodeValue;
            }
            $titlestmt->appendChild($new);
            $parent->removeChild($entry);
        }
        # lodel:title
        $entries = $xpath->query("//tei:p[@rend='title']");
        if ($entries->length) {
            foreach ($entries as $entry) {
                $parent = $entry->parentNode;
                $new = $dom->createElement('title');
                $new->setAttribute('level', "a");       // TODO ! document type
                $new->setAttribute('type', "main");
                if ( $lang=$entry->getAttribute('xml:lang')) { $new->setAttribute('xml:lang', $lang); }
                if ( $id=$entry->getAttribute('xml:id')) { $new->setAttribute('xml:id', $id); }
                if ($entry->hasChildNodes()) {
                    foreach ($entry->childNodes as $child) {
                        $clone = $child->cloneNode(true);
                        $new->appendChild($clone);
                    }
                }
                else {
                    $new->nodeValue = $p->nodeValue;
                }
                $titlestmt->appendChild($new);
                $lodelmeta['title'] = $new->nodeValue;
                $parent->removeChild($entry);
            }
        }
        else {
            // TODO : warning no title defined
            $new = $dom->createElement('title'); // car si pas de balise <title>, TEI invalide
            $new->setAttribute('type', "main");
            $titlestmt->appendChild($new);
        error_log("<li>? [Warning] no title defined</li>\n",3,self::_DEBUGFILE_);
        }
        # lodel:subtitle
        $entries = $xpath->query("//tei:p[@rend='subtitle']");
        if ($entries->length) {
            foreach ($entries as $entry) {
                $parent = $entry->parentNode;
                $new = $dom->createElement('title');
                $new->setAttribute('type', "sub");
                if ( $lang=$entry->getAttribute('xml:lang')) { $new->setAttribute('xml:lang', $lang); }
                if ( $id=$entry->getAttribute('xml:id')) { $new->setAttribute('xml:id', $id); }
                if ($entry->hasChildNodes()) {
                    foreach ($entry->childNodes as $child) {
                        $clone = $child->cloneNode(true);
                        $new->appendChild($clone);
                    }
                }
                else {
                    $new->nodeValue = $entry->nodeValue;
                }
                $titlestmt->appendChild($new);
                $parent->removeChild($entry);
            }
        }
        # lodel:altertitle
        $entries = $xpath->query("//tei:p[starts-with(@rend,'altertitle-')]");
        foreach ($entries as $entry) {
            $parent = $entry->parentNode;
            $new = $dom->createElement('title');
            $new->setAttribute('type', "alt");
            $rend = $entry->getAttribute("rend");
            list($alter, $lang) = explode("-", $rend);
            $new->setAttribute('xml:lang', $lang);
            if ($id=$entry->getAttribute('xml:id')) { $new->setAttribute('xml:id', $id);}
            if ($entry->hasChildNodes()) {
                foreach ($entry->childNodes as $child) {
                    $clone = $child->cloneNode(true);
                    $new->appendChild($clone);
                }
            }
            else {
                $new->nodeValue = $p->nodeValue;
            }
            $titlestmt->appendChild($new);
            $parent->removeChild($entry);
        }
        # lodelME:Auteurs
        # /tei/teiHeader/fileDesc/titleStmt/author
        $entries = $xpath->query("//tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:author");
        foreach ($entries as $entry) {
            $titlestmt->removeChild($entry);
        }
        $entries = $xpath->query("//tei:p[@rend='author' or @rend='translator' or @rend='scientificeditor']");
        foreach ($entries as $entry) {
            $parent = $entry->parentNode;
            $items = array(); 
            if ( preg_match("/,/", $entry->nodeValue)) {
                $items = explode(",", $entry->nodeValue);
                $uid = 1;
            } else {
                array_push($items, $entry->nodeValue);
                $uid = 0;
            }

            foreach ($items as $item) {
                $item = trim($item);

                $rend = $entry->getAttribute('rend');
                switch ($rend) {
                    case 'author':
                        $author = $dom->createElement('author');
                        break;
                    case 'scientificeditor':
                    case 'translator':
                        $author = $dom->createElement('editor');
                        break;
                }
                if ($rend == "translator") {
                    $author->setAttribute('role', "translator");
                }
                $titlestmt->appendChild($author);
                $name = $dom->createElement('name', $item);
                if ($lang=$entry->getAttribute('xml:lang')) { $name->setAttribute('xml:lang', $lang); }
                if ($id=$entry->getAttribute('xml:id')) { 
                    if ($uid) $id .= ".".$uid++;
                    $name->setAttribute('xml:id', $id); 
                }
                $author->appendChild($name);
                if (! isset($lodelmeta[$rend])) { $lodelmeta[$rend] = array(); }
                array_push($lodelmeta[$rend], $name->nodeValue);
                // author-description ==> affiliation
                while ($next=$entry->nextSibling) {
                    if ($rend=$next->getAttribute('rend')) {
                        if ($rend==="author-description") {
                            $desc = $dom->createElement('affiliation');
                            $s = $dom->createElement('s', $next->nodeValue);
                            $desc->appendChild($s);
                            $author->appendChild($desc);
                            if ($lang=$next->getAttribute('xml:lang')) { $desc->setAttribute('xml:lang', $lang); }
                            if ($id=$next->getAttribute('xml:id')) { $desc->setAttribute('xml:id', $id); }

                            if ($next->hasChildNodes()) {
                                foreach ($next->childNodes as $child) {
                                    if ($child->hasAttributes() AND $attr=$child->getAttribute('rend')) {
                                        if ( preg_match("/^author-(.+)$/", $attr, $match)) {
                                            switch ($match[1]) {
                                                case 'prefix':
                                                    $element = $dom->createElement('roleName');
                                                    $element->setAttribute('type', "honorific");
                                                    $s = $dom->createElement('s', $child->nodeValue);
                                                    $element->appendChild($s);
                                                    $author->appendChild($element);
                                                    break;
                                                case 'function':
                                                    $element = $dom->createElement('roleName');
                                                    $s = $dom->createElement('s', $child->nodeValue);
                                                    $element->appendChild($s);
                                                    $author->appendChild($element);
                                                    break;
                                                case 'affiliation':
                                                    $element = $dom->createElement('orgName');
                                                    $s = $dom->createElement('s', $child->nodeValue);
                                                    $element->appendChild($s);
                                                    $author->appendChild($element);
                                                    break;
                                                case 'email':
                                                    $element = $dom->createElement('email');
                                                    $s = $dom->createElement('s', $child->nodeValue);
                                                    $element->appendChild($s);
                                                    $author->appendChild($element);
                                                    break;
                                                case 'website':
                                                    $element = $dom->createElement('ref', $child->nodeValue);
                                                    $element->setAttribute('target', $child->nodeValue);
                                                    $element->setAttribute('type', "website");
                                                    $author->appendChild($element);
                                                    break;
                                            }
                                        }/*
                                        else {
                                            error_log("<li>rend clone</li>\n",3,self::_DEBUGFILE_);
                                            $clone = $child->cloneNode(true);
                                            $desc->appendChild($clone);
                                        }*/
                                    }/*
                                    else {
                                        $clone = $child->cloneNode(true);
                                        //error_log("<li>clone : {$clone->nodeValue}</li>\n",3,self::_DEBUGFILE_);
                                        $desc->appendChild($clone);
                                    }*/
                                }
                            }
                            $parent->removeChild($next);
                        } else break;
                    } else break;
                }
            }
            $parent->removeChild($entry);
        }
        $debugfile=$this->_param['TMPPATH'].$this->_dbg++."-dbgtei.xml";@$dom->save($debugfile);

        # /tei/teiHeader/publicationStmt
        $entries = $xpath->query("//tei:teiHeader/tei:fileDesc/tei:publicationStmt"); $pubstmt = $entries->item(0);
        # /tei/teiHeader/publicationStmt/date
        $entries = $xpath->query("//tei:p[@rend='date']");
        if ($entries->length) {
            $tmp=$xpath->query("//tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:date");
            if ($tmp->length) {
                $pubstmt->removeChild($tmp->item(0));
            }
            $entry = $entries->item(0);
            $parent = $entry->parentNode;
            $newnode = $dom->createElement('date', $entry->nodeValue);
            $newnode->setAttribute('when', "");
            if ($id=$entry->getAttribute('xml:id')) { $newnode->setAttribute('xml:id', $id); }
            $pubstmt->appendChild($newnode);
            $parent->removeChild($entry);
        }
        else {
            // TODO : warning no date defined
            error_log("<li>? [Warning] no date defined</li>\n",3,self::_DEBUGFILE_);
        }

        # /tei/teiHeader/publicationStmt/availability [lodel:license]
        $entries=$xpath->query("//tei:p[@rend='license']");
        if ($entries->length) {
            $tmp=$xpath->query("//tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:availability");
            if ($tmp->length) {
                $pubstmt->removeChild($tmp->item(0));
            }
            $entry = $entries->item(0);
            $parent = $entry->parentNode;
            $newnode = $dom->createElement('availability');
            $newnode->setAttribute('status', "free");
            $newp = $dom->createElement('p', $entry->nodeValue);
            if ($id=$entry->getAttribute('xml:id')) { $newp->setAttribute('xml:id', $id); }
            $newnode->appendChild($newp);
            $pubstmt->appendChild($newnode);
            $parent->removeChild($entry);
        }
        # /tei/teiHeader/publicationStmt/idno@documentnumber [lodel:documentnumber]
        $entries=$xpath->query("//tei:p[@rend='documentnumber']");
        if ($entries->length) {
            $tmp=$xpath->query("//tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:idno[@documentnumber]");
            if ($tmp->length) {
                $pubstmt->removeChild($tmp->item(0));
            }
            $entry = $entries->item(0);
            $parent = $entry->parentNode;
            $newnode = $dom->createElement('idno', $entry->nodeValue);
            $newnode->setAttribute('type', "documentnumber");
            if ($id=$entry->getAttribute('xml:id')) { $newnode->setAttribute('xml:id', $id); }
            $pubstmt->appendChild($newnode);
            $parent->removeChild($entry);
        }
        // TODO : idno@uri
        // TODO : idno@doi

    $dbg="\n<li><pre>".print_r($lodelmeta,true)."</pre></li>";error_log($dbg,3,self::_DEBUGFILE_);

        # /tei/teiHeader/sourceDesc
        $entries = $xpath->query("//tei:teiHeader/tei:fileDesc/tei:sourceDesc"); $srcdesc = $entries->item(0);
        $entries = $xpath->query("//tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull"); $biblfull = $entries->item(0);
        $entries = $xpath->query("//tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:titleStmt"); $titlestmt = $entries->item(0);
        $entries=$xpath->query("//tei:p[@rend='title']");
        if ($entries->length) {
            $tmp=$xpath->query("//tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:titleStmt/tei:title");
            if ($tmp->length) { $titlestmt->removeChild($tmp->item(0)); }
            $entry = $entries->item(0);
            $parent = $entry->parentNode;
            $new = $dom->createElement('title', $lodelmeta['title']);
            $titlestmt->appendChild($new);
        }
        // Lodel:auteurs as tei:respStmt
        $tmp=$xpath->query("//tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:titleStmt/tei:author");
        if ($tmp->length) { $titlestmt->removeChild($tmp->item(0)); }
        foreach ($lodelmeta["author"] as $author) {
            $respstmt = $dom->createElement('respStmt');
            $titlestmt->appendChild($respstmt);
            $resp = $dom->createElement('resp', "author");
            $respstmt->appendChild($resp);
            $name = $dom->createElement('name', $author);
            $respstmt->appendChild($name);
        }
        foreach ($lodelmeta["editor"] as $editor) {
            $respstmt = $dom->createElement('respStmt');
            $titlestmt->appendChild($respstmt);
            $resp = $dom->createElement('resp', "editor");
            $respstmt->appendChild($resp);
            $name = $dom->createElement('name', $editor);
            $respstmt->appendChild($name);
        }
        foreach ($lodelmeta["translator"] as $translator) {
            $respstmt = $dom->createElement('respStmt');
            $titlestmt->appendChild($respstmt);
            $resp = $dom->createElement('resp', "translator");
            $respstmt->appendChild($resp);
            $name = $dom->createElement('name', $translator);
            $respstmt->appendChild($name);
        }
        # /tei/teiHeader/sourceDesc/biblFull/publicationStmt
        $entries = $xpath->query("//tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:publicationStmt"); $pubstmt = $entries->item(0);
        # LodelEM:creationdate
        $entries=$xpath->query("//tei:p[@rend='creationdate']");
        if ($entries->length) {
            $tmp=$xpath->query("//tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:publicationStmt/tei:date");
            if ($tmp->length) {
                $pubstmt->removeChild($tmp->item(0));
            }
            $entry = $entries->item(0);
            $parent = $entry->parentNode;
            $new = $dom->createElement('date', $entry->nodeValue);
            $new->setAttribute('when', "");
            if ( $id=$entry->getAttribute('xml:id')) { $new->setAttribute('xml:id', $id); }
            $pubstmt->appendChild($new);
            $parent->removeChild($entry);
        }
        # LodelEM:pagenumber
        $entries=$xpath->query("//tei:p[@rend='pagenumber']");
        if ($entries->length) {
            $tmp=$xpath->query("//tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:publicationStmt/tei:idno[@type='pp']");
            if ($tmp->length) {
                $pubstmt->removeChild($tmp->item(0));
            }
            $entry = $entries->item(0);
            $parent = $entry->parentNode;
            $new = $dom->createElement('idno', $entry->nodeValue);
            $new->setAttribute('type', "pp");
            if ($id=$entry->getAttribute('xml:id')) { $new->setAttribute('xml:id', $id); }
            $pubstmt->appendChild($new);
            $parent->removeChild($entry);
        }
        # /tei/teiHeader/sourceDesc/biblFull/notesStmt
        $entries=$xpath->query("//tei:p[@rend='bibl']");
        if ($entries->length) {
            $entry = $entries->item(0);
            $parent = $entry->parentNode;
            $tmp=$xpath->query("//tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:notesStmt");
            if ($tmp->length) {
                $biblfull->removeChild($tmp->item(0));
            }
            $notesstmt = $dom->createElement('notesStmt');
            $biblfull->appendChild($notesstmt);
            $newnode = $dom->createElement('note', $entry->nodeValue);
            $newnode->setAttribute('type', "bibl");
            if ($lang=$entry->getAttribute('xml:lang')) { $newnode->setAttribute('xml:lang', $lang); }
            if ($id=$entry->getAttribute('xml:id')) { $newnode->setAttribute('xml:id', $id); }
            $notesstmt->appendChild($newnode);
            $parent->removeChild($entry);
        }

        # /tei/teiHeader/profileDesc
        $entries = $xpath->query("//tei:teiHeader/tei:profileDesc"); $profiledesc = $entries->item(0);
        $entries = $xpath->query("//tei:teiHeader/tei:profileDesc/tei:langUsage"); $langUsage = $entries->item(0);
        $entries=$xpath->query("//tei:p[@rend='language']");
        if ($entries->length) {
            $entry = $entries->item(0);
            $parent = $entry->parentNode;
            $tmp=$xpath->query("//tei:teiHeader/tei:profileDesc/tei:langUsage/tei:language");
            if ($tmp->length) {
                $langUsage->removeChild($tmp->item(0));
            }
            $newnode = $dom->createElement('language', $entry->nodeValue);
            $newnode->setAttribute('ident', $entry->nodeValue);
            if ( $id=$entry->getAttribute('xml:id')) { $newnode->setAttribute('xml:id', $id); }
            $langUsage->appendChild($newnode);
            $parent->removeChild($entry);
        }
        else {
            // TODO : warning no date defined
            error_log("<li>? [Warning] no date defined</li>\n",3,self::_DEBUGFILE_);
        }

        # /tei/teiHeader/profileDesc/textClass
        $entries = $xpath->query("//tei:textClass"); $textclass = $entries->item(0);
        # [lodel:keyword] /tei/teiHeader/profileDesc/textClass/keywords...
        $entries = $xpath->query("//tei:p[starts-with(@rend,'keywords-')]");
        foreach ($entries as $item) {
            $parent = $item->parentNode;
            $rend = $item->getAttribute("rend");
            if ( preg_match("/keywords-(.+)/", $rend, $match)) {
                $lang = $match[1];
            } else {
                $lang = null;
            }
            $newnode = $dom->createElement("keywords");
            $newnode->setAttribute('scheme', "keyword");
            if ( isset($lang)) {
                $newnode->setAttribute('xml:lang', $lang);
            }
            if ($id=$item->getAttribute('xml:id')) { $newnode->setAttribute('xml:id', $id); }
            $textclass->appendChild($newnode);
            $newlist = $dom->createElement("list");
            $newnode->appendChild($newlist);
            if (! preg_match("/,/", $item->nodeValue)) {
                $newitem = $dom->createElement("item", $item->nodeValue);
                $newlist->appendChild($newitem);
            }
            else {
                $index = explode(",", $item->nodeValue);
                foreach ($index as $ndx) {
                    $ndx = trim($ndx);
                    $newitem = $dom->createElement("item", $ndx);
                    $newlist->appendChild($newitem);
                }
            }
            $parent->removeChild($item);
        }
        # LodelME : Index thématique
        $entries = $xpath->query("//tei:p[@rend='subject']");
        foreach ($entries as $item) {
            $parent = $item->parentNode;
            $rend = $item->getAttribute("rend");
            $newnode = $dom->createElement("keywords");
            $newnode->setAttribute('scheme', "subject");
            //if ($lang=$item->getAttribute('xml:lang')) { $newnode->setAttribute('xml:lang', $lang); }
            if ($id=$item->getAttribute('xml:id')) { $newnode->setAttribute('xml:id', $id); }
            $textclass->appendChild($newnode);
            $newlist = $dom->createElement("list");
            $newnode->appendChild($newlist);
            if (! preg_match("/,/", $item->nodeValue)) {
                $newitem = $dom->createElement("item", $item->nodeValue);
                $newlist->appendChild($newitem);
            }
            else {
                $index = explode(",", $item->nodeValue);
                foreach ($index as $ndx) {
                    $ndx = trim($ndx);
                    $newitem = $dom->createElement("item", $ndx);
                    $newlist->appendChild($newitem);
                }
            }
            $parent->removeChild($item);
        }
        # lodelME : Index chronologique
        $entries = $xpath->query("//tei:p[@rend='chronological']");
        foreach ($entries as $item) {
            $parent = $item->parentNode;
            $rend = $item->getAttribute("rend");
            $newnode = $dom->createElement("keywords");
            $newnode->setAttribute('scheme', "chronological");
            if ($id=$item->getAttribute('xml:id')) { $newnode->setAttribute('xml:id', $id); }
            $textclass->appendChild($newnode);
            $newlist = $dom->createElement("list");
            $newnode->appendChild($newlist);
            if (! preg_match("/,/", $item->nodeValue)) {
                $newitem = $dom->createElement("item", $item->nodeValue);
                $newlist->appendChild($newitem);
            }
            else {
                $index = explode(",", $item->nodeValue);
                foreach ($index as $ndx) {
                    $ndx = trim($ndx);
                    $newitem = $dom->createElement("item", $ndx);
                    $newlist->appendChild($newitem);
                }
            }
            $parent->removeChild($item);
        }
        # LodelME : Index géographique
        $entries = $xpath->query("//tei:p[@rend='geographical']");
        foreach ($entries as $item) {
            $parent = $item->parentNode;
            $rend = $item->getAttribute("rend");
            $newnode = $dom->createElement("keywords");
            $newnode->setAttribute('scheme', "geographical");
            if ($id=$item->getAttribute('xml:id')) { $newnode->setAttribute('xml:id', $id); }
            $textclass->appendChild($newnode);
            $newlist = $dom->createElement("list");
            $newnode->appendChild($newlist);
            if (! preg_match("/,/", $item->nodeValue)) {
                $newitem = $dom->createElement("item", $item->nodeValue);
                $newlist->appendChild($newitem);
            }
            else {
                $index = explode(",", $item->nodeValue);
                foreach ($index as $ndx) {
                    $ndx = trim($ndx);
                    $newitem = $dom->createElement("item", $ndx);
                    $newlist->appendChild($newitem);
                }
            }
            $parent->removeChild($item);
        }

        # /tei/text/front
        $entries = $xpath->query("//tei:front"); $front = $entries->item(0);
        # /tei/text/front/abstract
        $entries = $xpath->query("//tei:p[starts-with(@rend,'abstract')]");
        foreach ($entries as $item) {
            $parent = $item->parentNode;
            $rend = $item->getAttribute("rend");
            if ( preg_match("/abstract-(.+)/", $rend, $match)) {
                $lang = $match[1];
            } else {
                $lang = null;
            }
            $div = $dom->createElement("div");
            $div->setAttribute('type', "abstract");
            if ( isset($lang)) {
                $div->setAttribute('xml:lang', $lang);
            }
            $clone = $item->cloneNode(true);
            if ($clone->hasAttributes()) {
                foreach ($clone->attributes as $attr) {
                    $clone->removeAttribute($attr->name);
                }
            }
            $div->appendChild($clone);
            $front->appendChild($div);
            $parent->removeChild($item);
        }
        # /tei/text/front/ack
        $entries = $xpath->query("//tei:p[@rend='acknowledgment']");
        foreach ($entries as $item) {
            $parent = $item->parentNode;
            $rend = $item->getAttribute("rend");
                $lang = null;
            $div = $dom->createElement("div");
            $div->setAttribute('type', "ack");
            if ( isset($lang)) {
                $div->setAttribute('xml:lang', $lang);
            }
            $clone = $item->cloneNode(true);
            if ($clone->hasAttributes()) {
                foreach ($clone->attributes as $attr) {
                    $clone->removeAttribute($attr->name);
                }
            }
            $div->appendChild($clone);
            $front->appendChild($div);
            $parent->removeChild($item);
        }
        # /tei/text/front/dedication
        $entries = $xpath->query("//tei:p[@rend='dedication']");
        foreach ($entries as $item) {
            $parent = $item->parentNode;
            $rend = $item->getAttribute("rend");
                $lang = null;
            $div = $dom->createElement("div");
            $div->setAttribute('type', "dedication");
            if ( isset($lang)) {
                $div->setAttribute('xml:lang', $lang);
            }
            $clone = $item->cloneNode(true);
            if ($clone->hasAttributes()) {
                foreach ($clone->attributes as $attr) {
                    $clone->removeAttribute($attr->name);
                }
            }
            $div->appendChild($clone);
            $front->appendChild($div);
            $parent->removeChild($item);
        }
        # /tei/text/front/correction
        $entries = $xpath->query("//tei:p[@rend='correction']");
        foreach ($entries as $item) {
            $parent = $item->parentNode;
            $rend = $item->getAttribute("rend");
                $lang = null;
            $div = $dom->createElement("div");
            $div->setAttribute('type', "correction");
            if ( isset($lang)) {
                $div->setAttribute('xml:lang', $lang);
            }
            $clone = $item->cloneNode(true);
            if ($clone->hasAttributes()) {
                foreach ($clone->attributes as $attr) {
                    $clone->removeAttribute($attr->name);
                }
            }
            $div->appendChild($clone);
            $front->appendChild($div);
            $parent->removeChild($item);
        }
        # /tei/text/front/review
        $entries = $xpath->query("//tei:p[starts-with(@rend,'review-')]");
        if ($entries->length) {
            $div = $dom->createElement("div");
            $div->setAttribute('type', "review");
            foreach ($entries as $item) {
                $parent = $item->parentNode;
                $clone = $item->cloneNode(true);
                $div->appendChild($clone);
                $parent->removeChild($item);
            }
            $front->appendChild($div);
        }
        # /tei/text/front/note@resp=editor
        $entries = $xpath->query("//tei:p[@rend='editornote']");
        foreach ($entries as $item) {
            $parent = $item->parentNode;
            $rend = $item->getAttribute("rend");
                $lang = null;
            $div = $dom->createElement("note");
            $div->setAttribute('resp', "editor");
            if ( isset($lang)) {
                $div->setAttribute('xml:lang', $lang);
            }
            $clone = $item->cloneNode(true);
            if ($clone->hasAttributes()) {
                foreach ($clone->attributes as $attr) {
                    $clone->removeAttribute($attr->name);
                }
            }
            $div->appendChild($clone);
            $front->appendChild($div);
            $parent->removeChild($item);
        }
        # /tei/text/front/note@resp=author
        $entries = $xpath->query("//tei:p[@rend='authornote']");
        foreach ($entries as $item) {
            $parent = $item->parentNode;
            $rend = $item->getAttribute("rend");
                $lang = null;
            $div = $dom->createElement("note");
            $div->setAttribute('resp', "author");
            if ( isset($lang)) {
                $div->setAttribute('xml:lang', $lang);
            }
            $clone = $item->cloneNode(true);
            if ($clone->hasAttributes()) {
                foreach ($clone->attributes as $attr) {
                    $clone->removeAttribute($attr->name);
                }
            }
            $div->appendChild($clone);
            $front->appendChild($div);
            $parent->removeChild($item);
        }
        # ...
        $entries = $xpath->query("//tei:div[@rend='LodelMeta']");
        if ($entries->length) {
            foreach ($entries as $entry) {
                if ($entry->hasChildNodes()) {
                    // TODO warnings ?
                    foreach ($entry->childNodes as $child) {
                        $div = $dom->createElement("div");
                        if ($id=$child->getAttribute('xml:id')) { $div->setAttribute('xml:id', $id); $child->removeAttribute('xml:id'); }
                        if ( $lang=$child->getAttribute('xml:lang')) { $div->setAttribute('xml:lang', $lang);  $child->removeAttribute('xml:lang'); }
                        /*
                        if ($child->hasAttributes()) {
                            foreach ($child->attributes as $attr) {
                                $div->setAttribute($attr->name, $attr->value);
                            }
                        }
                        */
                        $clone = $child->cloneNode(true);
                        $div->appendChild($clone);
                        $front->appendChild($div);
                    }
                }
                $parent = $entry->parentNode;
                $parent->removeChild($entry);
            }
        }

        # /tei/text/back
        error_log("\n<li>tei:back</li>\n",3,self::_DEBUGFILE_);
        $entries = $xpath->query("//tei:back"); $back = $entries->item(0);

        # Bibliography
        $entries = $xpath->query("//tei:div[@rend='LodelBibliography']");
        if ($entries->length) {
            $lodel = $entries->item(0);
            $parent = $lodel->parentNode;
            $bibliography = $dom->createElement("div");
            $bibliography->setAttribute('type', "bibliography");
            $back->appendChild($bibliography);
            $listbibl = $dom->createElement("listBibl");
            $bibliography->appendChild($listbibl);
            $tags = $lodel->childNodes;
            foreach ($tags as $tag) {
                if ( preg_match("/^bibliography-(.+)$/", $tag->getAttribute("rend"), $matches)) {
                    if ( preg_match("/^heading(\d+)$/", $matches[1], $match)) {
                        $bibl = $dom->createElement("bibl", $tag->nodeValue);
                        $bibl->setAttribute('type', "head");
                        $bibl->setAttribute('subtype', "level".$match[1]);
                        $listbibl->appendChild($bibl);
                        continue;
                    }
                }
                $bibl = $dom->createElement("bibl");
                if ($tag->hasChildNodes()) {
                    foreach ($tag->childNodes as $child) {
                        $clone = $child->cloneNode(true);
                        $bibl->appendChild($clone);
                    }
                } 
                else {
                    $bibl->nodeValue = $tag->nodeValue;
                }
                $listbibl->appendChild($bibl);
            }
            $parent->removeChild($lodel);
        }

        # Appendix
        error_log("\n<li>tei:div[@rend='appendix']</li>\n",3,self::_DEBUGFILE_);
        $entries = $xpath->query("//tei:div[@rend='LodelAppendix']");
        if ($entries->length) {
            $lodel = $entries->item(0);
            $parent = $lodel->parentNode;
            $appendix = $dom->createElement("div");
            $appendix->setAttribute('type', "appendix");
            $back->appendChild($appendix);
            $tags = $lodel->childNodes;
            foreach ($tags as $tag) {
                $clone = $tag->cloneNode(true);
                if ( preg_match("/^appendix-(.+)$/", $clone->getAttribute("rend"), $matches)) {
                    if ( preg_match("/^heading(\d+)$/", $matches[1], $match)) {
                        $clone->setAttribute('type', "head");
                        $clone->setAttribute('subtype', "level".$match[1]);
                        $clone->setAttribute('rend', "appendix");
                    }
                }
                $appendix->appendChild($clone);
            }
            $parent->removeChild($lodel);
        }

        // clean Lodel sections
        error_log("\n<li>clean Lodel sections</li>\n",3,self::_DEBUGFILE_);

        $entries = $xpath->query("//tei:div[@rend='LodelMeta']");
        if ($entries->length) {
            $lodelmeta = $entries->item(0);
            if (! $lodelmeta->hasChildNodes()) {
                $parent = $lodelmeta->parentNode;
                $parent->removeChild($lodelmeta);
            } else {
                error_log("<li>? [Warning] /text/front/div[@rend='LodelMeta'] not empty !</li>\n",3,self::_DEBUGFILE_);
                $this->_status = "Warning : metadata misspelling";
                array_push($this->log['warning'], $this->_status);
            }
        }
        $entries = $xpath->query("//tei:div[@rend='LodelBibliography']");
        if ($entries->length) {
            $lodelbiblgr = $entries->item(0);
            if (! $lodelbiblgr->hasChildNodes()) {
                $parent = $lodelbiblgr->parentNode;
                $parent->removeChild($lodelbiblgr);
            } else {
                error_log("<li>? [Warning] /back/div[@rend=LodelBibliography] not empty !</li>\n",3,self::_DEBUGFILE_);
                $this->_status = "? Warning : bibliograpy misspelling";
                array_push($this->log['warning'], $this->_status);
            }
        }
        $entries = $xpath->query("//tei:div[@rend='LodelAppendix']");
        if ($entries->length) {
            $lodelappdx = $entries->item(0);
            if (! $lodelappdx->hasChildNodes()) {
                $parent = $lodelappdx->parentNode;
                $parent->removeChild($lodelappdx);
            } else {
                error_log("<li>? [Warning] /back/div[@rend='LodelAppendix'] not empty !</li>\n",3,self::_DEBUGFILE_);
                $this->_status = "? Warning : appendix misspelling";
                array_push($this->log['warning'], $this->_status);
            }
        }

        # 
        error_log("\n<li>renditions</li>\n",3,self::_DEBUGFILE_);
        $entries = $xpath->query("//@rendition");
        foreach ($entries as $attr) {
            $element = $attr->ownerElement;
            $tagdeclid = $element->getAttribute("rendition");
            $rend = $this->tagsdecl2rendition($tagdeclid, $rendition);
            if ( isset($rend)) {
                if ( isset($rendition)) {
                    $element->removeAttribute("rendition");
                }
                $element->setAttribute("rend", $rend);
                list($tmp, $id) = explode("#", $tagdeclid);
                $query = "//tei:rendition[@xml:id='$id']";
                $entry = $xpath->query($query); 
                if ($entry->length) {
                    $node = $entry->item(0);
                    if ( strlen($rendition)) {
                        $node->nodeValue = $rendition;
                    } else {
                        $parent = $node->parentNode;
                        $parent->removeChild($node);
                    }
                }
            } else {
                //error_log("<li>{$element->nodeName} : $tagdeclid => rend = ???</li>\n",3,self::_DEBUGFILE_);
            }
        }
        $entries = $xpath->query("//tei:tagsDecl");
        if ($entries->length) {
            $tagsDecl = $entries->item(0);
            if (! $tagsDecl->hasChildNodes()) {
                $parent = $tagsDecl->parentNode;
                $parent->removeChild($tagsDecl);
            }
        }
        $entries = $xpath->query("//tei:encodingDesc");
        if ($entries->length) {
            $encodingDesc = $entries->item(0);
            if (! $encodingDesc->hasChildNodes()) {
                $parent = $encodingDesc->parentNode;
                $parent->removeChild($encodingDesc);
            }
        }

# TODO ! <floatingText><body><p rend="box">...</p></body></floatingText>
//        $entries = $xpath->query("//tei:body/tei:p[@rend='box']");
//        foreach ($entries as $entry) {
//            $parent = $entry->parentNode;
//            $clone = $entry->cloneNode(true);
//            $floatingText = $dom->createElement("floatingText");
//            $floatingBody = $dom->createElement("body");
//            $floatingText->appendChild($floatingBody);
//            $floatingBody->appendChild($clone);
//            $parent->replaceChild($floatingText, $entry);
//        }


        if ( $headlevel=$this->summary($dom, $xpath)) {
            $this->heading2div($dom, $xpath, $headlevel);
$debugfile=$this->_param['TMPPATH']."div.xml";@$dom->save($debugfile);
        }

        // clean++
        //$otxml = str_replace("<pb/>", "<!-- <pb/> -->", $dom->saveXML());
        $otxml = $dom->saveXML();
        $search = array('xmlns="http://www.tei-c.org/ns/1.0"', 'xmlns:default="http://www.tei-c.org/ns/1.0"');
        $otxml = str_replace($search, '', $otxml);
        $dom->loadXML($otxml);

        $dom->normalizeDocument();
$debugfile=$this->_param['TMPPATH'].$this->_dbg++."-otxtei.xml";@$dom->save($debugfile);
        $this->_param['xmloutputpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/".$this->_param['prefix'].".otx.tei.xml";
        $dom->save($this->_param['xmloutputpath']);

        $dom->resolveExternals = false;
/*
        $dom->validateOnParse = true;
        if (! $dom->validate()) {
            $this->_status = "Warning: OTX TEI-P5 is not valid !";
            array_push($this->log['warning'], $this->_status);
            error_log("\n<li>? {$this->_status}</li>\n",3,self::_DEBUGFILE_);
        } else {
            $this->_status = "OTX TEI-P5 is valid.";
            error_log("<li>{$this->_status}</li>\n",3,self::_DEBUGFILE_);
        }
        $this->log['status']['otxtei'] = $this->_status;
*/
        $otxml = $dom->saveXML();
        $otxml = str_replace('<TEI>', '<TEI xmlns="http://www.tei-c.org/ns/1.0">', $otxml);

        $dom->loadXML($otxml);
$debugfile=$this->_param['TMPPATH']."otxtei.xml";@$dom->save($debugfile);
        $this->_param['xmloutputpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/".$this->_param['prefix'].".otx.tei.xml";
        $dom->save($this->_param['xmloutputpath']);

        $this->_param['TEI'] = $otxml;
        return true;
    }


        private function summary(&$dom, &$xpath) {
        error_log("<h4>summary()</h4>\n",3,self::_DEBUGFILE_);
            $max = 0;
            $summary = array();
            $entries = $xpath->query("//tei:text/tei:body/tei:ab", $dom);
            foreach ($entries as $entry) {
                if ($heading=$entry->getAttribute("rend")) {
                    if ( preg_match("/^heading(\d+)$/", $heading, $match)) {
                        $n = $match[1]; 
                        if ($n > $max) $max = $n;
                        //$clone = $entry->cloneNode(false);
                        $head = array("heading$n" => $entry->nodeValue);
                        array_push($summary, $head);
                    }
                }
            }
            $this->report['summary'] = $summary;
            return $max;
        }

        private function heading2div(&$dom, &$xpath, $level) {
        error_log("<h4>heading2div(level=$level)</h4>\n",3,self::_DEBUGFILE_);
           if ($level == 0) return;
            $entries = $xpath->query("//tei:text/tei:body/tei:ab[@rend='heading$level']", $dom);
            foreach ($entries as $item) {
                $parent = $item->parentNode;
                $div = $dom->createElement("div");
                $div->setAttribute("type", "div$level");
                $head = $dom->createElement("head");
                $head->setAttribute("subtype", "level$level");
                if ($id=$item->getAttribute('xml:id')) {
                    $head->setAttribute('xml:id', $id);
                }
                if ($item->hasChildNodes()) {
                    foreach ($item->childNodes as $child) {
                        $clone = $child->cloneNode(true);
                        $head->appendChild($clone);
                    }
                }
                else {
                    $head->nodeValue = $item->nodeValue;
                }
                $div->appendChild($head);

                $nodetoremove = array();
                $next = $item;
                while ($next = $next->nextSibling) {
                    if ($next->nodeName == "ab") break;
                    $clone = $next->cloneNode(true);
                    $div->appendChild($clone);
                    array_push($nodetoremove, $next);
                }
                foreach ($nodetoremove as $node) {
                    $parent->removeChild($node);
                }
                if (! $parent->replaceChild($div, $item)) {
                    $this->_status="error replaceChild";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
                    throw new Exception($this->_status,E_ERROR);
                }
            }
            if (--$level == 0) return;
            $this->heading2div($dom, $xpath, $level);
        }


    /**
    * transformation d'un document (txt, rtf, xhtml, tei, pdf, ...) en (odt, ...)
    * ! system call inside (soffice)
    **/
    protected function soffice($suffix="odt") {
    error_log("<h2>soffice()</h2>\n",3,self::_DEBUGFILE_);

        # get the mime type
        $this->getmime();
        $sourcepath = $this->_param['sourcepath'];
        $extension = $this->_param['extension'];

        switch($suffix) {
            case 'docx': //$suffix = 'doc'; break;
            case 'odt':
            case 'pdf':
            case 'doc':
            case 'rtf':
            case 'txt':
            //case 'html': //TODO ?
            case "xhtml":
            case "teixml":
            case "tei.xml":
            case "tei":
                break;
            default:
                $this->_status = "error mime type: unknown output file type";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
                throw new Exception($this->_status,E_USER_ERROR);
        }
        //$odtpath = $this->_param['odtpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/".$this->_param['prefix'].".$suffix";
        $targetpath = $this->_param['sourcepath'];
        if ($this->_param['mime'] !== "OpenDocument Text" OR $suffix !== 'odt') {
            $targetpath .= ".$suffix";

            $in = escapeshellarg($sourcepath);
            $out = escapeshellarg($targetpath);
            $command = self::_SOFFICE_PYTHONPATH_." {$this->_param['LIBPATH']}DocumentConverter.py $in $out";
            /*  //TODO : tetster la presence du lien symbolique jodconverter-cli.jar !!
                //$command = "java -jar ". $this->_param['LIBPATH'] ."jodconverter3.jar -f odt ". $sourcepath;  */
            error_log("<li>command : $command</li>\n",3,self::_DEBUGFILE_);
            /*
            $output = array(); $returnvar=0;
            $result = ''. exec($command, $output, $returnvar);
            */
            $returnvar=0;$result='';
            ob_start();
            passthru($command, $returnvar); sleep(1);
            $result = ob_get_contents();
            ob_end_clean();
            if ($returnvar /*or $result!=''*/) {
                @copy($sourcepath, $sourcepath.".error");@unlink($sourcepath);
                $this->_status = "error soffice";error_log("<li>! {$this->_status}</li>\n",3,self::_DEBUGFILE_);
                throw new Exception($this->_status,E_USER_ERROR);
            }
        }
        $odtpath = $this->_param['odtpath'] = $targetpath;

        $this->_param['outputpath'] = $targetpath;
        return true;
    }

        private function getmime() {
        error_log("<li>getmime()</li>\n",3,self::_DEBUGFILE_);
            $sourcepath = $this->_param['sourcepath'];

            error_log("<li>[getmime] sourcepath = $sourcepath</li>\n",3,self::_DEBUGFILE_);
            $mime = mime_content_type($sourcepath);
            error_log("<li>[getmime] => mime_content_type() = $mime</li>\n",3,self::_DEBUGFILE_);

            if ($mime === "application/x-zip" OR $mime === "application/zip") {
                $file = escapeshellarg($sourcepath);
                list($mime, $tmp) = explode(",", system("file -b $file"));
                error_log("<li>[getmime] => file -b = $mime</li>\n",3,self::_DEBUGFILE_);
            }

            $extension = ".odt";
            if ( trim($mime) != "OpenDocument Text") {
                switch ($mime) {
                    case "Rich Text Format data":   //, version 1, ANSI   //, version 1, Apple Macintosh
                    case "Rich Text Format data, version 1,":
                    case "Rich Text Format data, version 1, ANSI":
                    case 'application/rtf':
                    case 'application/x-rtf':
                    case 'text/rtf':
                    case 'text/richtext':
                        error_log("<li>Rich Text Format data</li>\n",3,self::_DEBUGFILE_);
                        $extension = ".rtf";
                        break;
                    case "Microsoft Office Document":
                    case 'application/msword':
                    case 'application/doc':
                    case 'appl/text':
                    case 'application/vnd.msword':
                    case 'application/vnd.ms-word':
                    case 'application/winword':
                    case 'application/word':
                    case 'application/x-msw6':
                    case 'application/x-msword':
                        error_log("<li>Microsoft Office Document</li>\n",3,self::_DEBUGFILE_);
                        $extension = ".doc";
                        break;
                    case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
                        error_log("<li>Microsoft Office -docx- Document</li>\n",3,self::_DEBUGFILE_);
                        $extension = ".docx";
                        break;
                    case "OpenOffice.org 1.x Writer document":
                    case 'application/x-soffice':
                    case 'application/vnd.sun.xml.writer':
                    case 'application/x-vnd.oasis.opendocument.text':
                    case 'application/vnd.oasis.opendocument.text':
                    case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                        error_log("<li>OpenOffice.org 1.x Writer document</li>\n",3,self::_DEBUGFILE_);
                        $extension = ".sxw";
                        break;
                    default:
                        error_log("<li>Warning: extension based</li>\n",3,self::_DEBUGFILE_);
                        # the last chance !    // ben'à défaut on se base sur l'extention du fichier...
                        $temp = explode(".", $sourcepath);
                        $ext = trim( array_pop($temp));
                        $this->_status = "Warning : mime detection based on document extension (.$ext)";
                        array_push($this->log['warning'], $this->_status);
                        error_log("<li>{$this->_status}</li>\n",3,self::_DEBUGFILE_);
                        switch ($ext) {
                            case "rtf":
                                error_log("<li>warning: .rtf</li>\n",3,self::_DEBUGFILE_);
                                $extension = ".rtf";
                                break;
                            case "sxw":
                                error_log("<li>warning: .sxw</li>\n",3,self::_DEBUGFILE_);
                                $extension = ".sxw";
                                break;
                            case "doc":
                                error_log("<li>warning: .doc</li>\n",3,self::_DEBUGFILE_);
                                $extension = ".doc";
                                break;
                            case "docx":
                                error_log("<li>warning: .docx</li>\n",3,self::_DEBUGFILE_);
                                $extension = ".docx";
                                break;
                            default:
                                $this->_status="error: unknown mime type: $mime ($sourcepath)";$this->_iserror=true;
                                error_log("<li>! error: {$this->_status}</li>\n",3,self::_DEBUGFILE_);
                                throw new Exception($this->_status,E_USER_ERROR);
                                break;
                        }
                    break;
                }

                if (! rename($sourcepath, $sourcepath.$extension)) {
                    $this->_status="error: rename [$sourcepath]";error_log("<h1>! {$this->_status} </h1>\n",3,self::_DEBUGFILE_);
                    throw new Exception($this->_status,E_ERROR);
                }
                $this->_param['sourcepath'] = $sourcepath.$extension;
            }

            $this->_param['extension'] = $extension;
            $this->_param['mime'] = $mime;

            return true;
        }

        /** lodel-cleanup **/
        private function lodelcleanup(&$dom) {
        error_log("<h3>lodelcleanup()</h3>\n",3,self::_DEBUGFILE_);
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
                    case 'table:style-name':
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
        error_log("<h3>lodelpictures()</h3>\n",3,self::_DEBUGFILE_);
            $imgindex = 0;
            $xpath = new DOMXPath($dom);
            $entries = $xpath->query("//draw:image");
            // TODO : test Pictures !
            foreach ($entries as $item) {
                //  text:anchor-type="as-char"
                $parent = $item->parentNode;
                if ($anchortype=$parent->getAttribute('text:anchor-type')) {
                    if ($anchortype=="as-char") {
                        $attributes = $item->attributes;
                        $attribute = $attributes->getNamedItem("href");
                        if ( preg_match("/^Pictures/", $attribute->nodeValue)) {
                            $match = $attribute->nodeValue;
                            list($imgpre, $imgext) = explode(".", trim($match));
                            list($pictures, $imgname) = explode("/", $imgpre);
                            if (! isset($this->LodelImg[$imgname.$imgext])) {
                                $imgindex++;
                                $this->LodelImg[$imgname.$imgext] = $imgindex;
                                $currentname = "Pictures/$imgname.$imgext";
                                $newname = "Pictures/img-$imgindex.$imgext";
                                if (! $za->renameName($currentname, $newname)) {
                                    $this->_status="error rename files in ziparchive ($currentname => $newname)";error_log("<h1>! {$this->_status} </h1>\n",3,self::_DEBUGFILE_);
                                    throw new Exception($this->_status,E_ERROR);
                                }
                            } else {
                                $imgindex = $this->LodelImg[$imgname.$imgext];
                            }

                            if ($this->_param['mode'] === "lodel") {
                                $picturepath = "Pictures/img-$imgindex.$imgext";
                            } else { // TODO : TEI lodel mode ??!
                                //http://XXX.revues.org/docannexe/image/XXX/img-X.jpg <draw:image xlink:href="Pictures/100002000000021000000121BB042930.png"
                                $picturepath = "http://".$revue.".revues.org/docannexe/image/".$prefix."/img-".$imgindex.".".$imgext;
                            }
                            $attribute->nodeValue = $newname;
                        }
                        else {
                            /*
                            $finfo = finfo_open(FILEINFO_MIME_TYPE); // mimetype extension
                            $mime =  finfo_file( $za->getStream($attribute->nodeValue));
                            finfo_close($finfo);
                            */
                            $this->_status = "{$attribute->nodeValue} skipped";
                            array_push($this->log['warning'], $this->_status);
                            error_log("\n<li>? {$this->_status}</li>\n",3,self::_DEBUGFILE_);
                            // TODO Warning !
                        }
                    }
                }
            }
            return true;
        }

        private function ooautomaticstyles(&$dom) {
        error_log("<h4>ooautomaticstyles()</h4>\n",3,self::_DEBUGFILE_);
            $xpath = new DOMXPath($dom);
            $entries = $xpath->query("//style:style");
            foreach ($entries as $item) {
                $name = $family = $parent = '';
                $properties=array(); $key='';
                $attributes = $item->attributes;
                //style:family
                if ($attrname=$attributes->getNamedItem("family")) {
                    $family = $attrname->nodeValue;
                }
                //style:name
                if ($attrname=$attributes->getNamedItem("name")) {
                    $name = $attrname->nodeValue;
                    if (false !== strpos($name, "table")) {
                        $id = ''; list($table, $id) = explode(".", $name);
                        $key = "#td".$table[strlen($table)-1].$id;
                        //$key = "#".$name;
                    } else {
                        $key = "#".$name;
                        if ( preg_match("/^T(\d+)$/", $name, $match)) {
                            $this->Tnum = $match[1];
                        }
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
                            case 'style:table-cell-properties':
                                $childattributes = $child->attributes;
                                foreach ($childattributes as $childattr) {
                                    if (! (strstr($childattr->name, '-asian') or strstr($childattr->name, '-complex'))) { // black list
                                        $value = ''. "{$childattr->name}:{$childattr->value}";
                                        array_push($properties, $value);
                                    }
                                }
                                break;
                            default:
                                break;
                        }
                    }
                    list($lang, $rendition) = $this->styles2csswhitelist($properties); // white list
                    if ($lang == "") $lang = null;
                    $this->rendition[$key]['lang'] = $lang;
                    $this->rendition[$key]['rendition'] = $rendition;
                    $this->rendition[$key]['family'] = $family;
                }
            }

            return true;
        }

        private function oostyles(&$dom) {
        error_log("<h4>oostyles()</h4>\n",3,self::_DEBUGFILE_);
            $xpath = new DOMXPath($dom);
            // listes
            $entries = $xpath->query("//text:list-style[@style:name]");
            foreach ($entries as $item) {
                $properties=array(); $key='';
                $attributes = $item->attributes;
                if ($attrname=$attributes->getNamedItem("name")) {
                    $name = $attrname->nodeValue;
                    $key = '#'.$name;
                }
                $list = "unordered";
                if ($item->hasChildNodes()) {
                    $first = $item->firstChild;
                    $last = $item->lastChild;
                    if ($first->nodeName=="text:list-level-style-number" and $last->nodeName=="text:list-level-style-number") {
                        $list = "ordered";
                    }
                }
                $this->rendition[$key]['rendition'] = $list;
            }

            $entries = $xpath->query("//style:style[@style:name]");
            foreach ($entries as $item) {
                $properties=array(); $key='';
                $attributes = $item->attributes;
                if ($attrname=$attributes->getNamedItem("name")) {
                    $name = $attrname->nodeValue;
                    $key = $name;
                }
                $family = '';
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
                if ( isset($this->EMotx[$key])) {
                    continue; // Lodel style definition: skip
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
                        if ($family == '') {
                            if ($child->nodeName == 'style:paragraph-properties') {
                                $family = "paragraph";
                            }
                            if ($child->nodeName == 'style:text-properties') {
                                $family = "text";
                            }
                        }
                    }
                    list($lang, $rendition) = $this->styles2csswhitelist($properties);

                    if ( isset($this->rendition[$key])) { // from automaticstyle
                        // TODO : merge ?
                        if ($this->rendition[$key]['lang']=='') {
                            $this->rendition[$key]['lang'] = $lang;
                        }
                        if ($this->rendition[$key]['rendition']=='') {
                            $this->rendition[$key]['rendition'] = $rendition;
                        }
                        if ($this->rendition[$key]['family']=='') {
                            $this->rendition[$key]['family'] = $family;
                        }
                    } else {
                        $this->rendition[$key]['lang'] = $lang;
                        $this->rendition[$key]['family'] = $family;
                        //$this->rendition[$key]['rendition'] = $rendition; // Lodel style
                    }
                }
            }

            return true;
        }

        /** styles to css white list ! **/
        private function styles2csswhitelist(&$properties, $type="strict") {
        //error_log("<h4>styles2csswhitelist() [type=$type]</h4>\n",3,self::_DEBUGFILE_);
            $lang = ""; $rendition = "";
            $csswhitelist = array();
            // default : strict mode
            foreach ($properties as $prop) {
                // xhtml:sup
                if ( preg_match("/^text-position:super/", $prop)) {
                    array_push($csswhitelist, "vertical-align:super");
                    continue;
                }
                // xhtml:sub
                if ( preg_match("/^text-position:sub/", $prop)) {
                    array_push($csswhitelist, "vertical-align:sub");
                    continue;
                }
                if ( preg_match("/^language:(.*)$/", $prop, $match)) {
                    $lang = $match[1];
                    continue;
                }
                switch ($prop) {
                    case 'font-style:italic':
                        // <tei:emph rend="italic"> => <xhtml:em>
                    case 'font-weight:bold':
                        // <tei:hi rend="bold"> => <xhtml:strong>
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
                    case 'text-line-through-style:solid':
                        array_push($csswhitelist, "text-decoration:line-through");
                        break; 
                    case 'writing-mode:lr-tb':
                        array_push($csswhitelist, "direction:ltr");
                        break;
                    case 'writing-mode:rl-tb':
                        array_push($csswhitelist, "direction:rtl");
                        break;
                    // table no-border
                    case 'border-right:none':
                    case 'border-left:none':
                    case 'border-top:none':
                    case 'border-bottom:none':
                        array_push($csswhitelist, $prop);
                        break;
                    default:
                    //error_log("<li><i>TODO: $prop ?! [strict mode]</i></li>\n",3,self::_DEBUGFILE_);
                        break;
                }
                $type = $this->_param['type'];
                if ($type==="extended") {
                    if ( preg_match("/^font-size:/", $prop)) {
                        array_push($csswhitelist, $prop);
                        continue;
                    }
                    if ( preg_match("/^font-name:(.*)$/", $prop, $match)) {
                        array_push($csswhitelist, "font-family:'{$match[1]}'");
                    }
                    // table border
                    if ( preg_match("/^(border.+):.+solid\s+(#\d+)$/", $prop, $match)) {
                        $border = $match[1].":1px solid ".$match[2];
                        array_push($csswhitelist, $border);
                        // TODO raw as cell !
                        continue;
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

        /** array('rend'=>, 'key'=>, 'surround'=>, 'section'=>) **/
        private function greedy(&$node, &$greedy) {
        //error_log("<h4>greedy()</h4>\n",3,self::_DEBUGFILE_);
            $section = $surround = $key = $rend = null;
            $greedy = null; $status = true;

            if ($rend=$node->getAttribute("rend")) {

                if (strpos($rend, "bibliography-") or strpos($rend, "appendix-")) {
                    list($prefix,$rend) = explode("-",$rend);
                    $section = "back";
                }

                if ( isset($this->EMotx[$rend]['surround'])) {
                    $surround = $this->EMotx[$rend]['surround'];
                }
                elseif ($node->nodeName=="ab") { 
                    $surround = "*-"; // heading > 6 !
                }

                if ($surround=="*-" or $surround=="-*") {
error_log("<li>! [greedy] surround = $surround\n",3,self::_DEBUGFILE_); 
                    $status = false;
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
                            $section = "body";
                            break;
                        default:
error_log("<li>? [greedy($rend)] default section = body ({$node->nodeName})</li>\n",3,self::_DEBUGFILE_);
                            $section = "body";
                            break;
                    }
                }
                elseif ($node->nodeName=="ab") {
error_log("<li>?? [greedy($rend)] ({$node->nodeName})</li>\n",3,self::_DEBUGFILE_);
                    $section = "body"; // heading > 6 !
                }

            }
            else {
error_log("<li>??? [greedy($rend)] ({$node->nodeName})</li>\n",3,self::_DEBUGFILE_);
                $section = "body"; // heading > 6 !
                return $status;
            }

            $greedy = array('rend'=>$rend, 'key'=>$key, 'surround'=>$surround, 'section'=>$section);
            return $status;
        }

        /** css tagsDecl to tei:hi rendition ! **/
        private function tagsdecl2rendition($tagdeclid, &$rendition /*$type="strict"*/) {
        //error_log("<h4>tagsdecl2rendition() [tagdeclid=$tagdeclid][type=$type]</h4>\n",3,self::_DEBUGFILE_);
            if (! isset($this->tagsDecl[$tagdeclid])) {
                return null;
            }
            $rend = $rendition = "";
            $renditions = array();
            $tagdecl = $this->tagsDecl[$tagdeclid].";";
            foreach ( explode(";", $tagdecl) as $tgdcl) {
                switch ($tgdcl) {
                    case 'font-style:italic':
                        $rend .= "italic";
                        break;
                    case 'font-weight:bold':
                        $rend .= "bold";
                        break;
                    case 'font-weight:normal':
                        $rend .= "normal";
                        break;
                    case 'text-decoration:underline':
                        $rend .= "underline";
                        break;
                    case 'text-decoration:line-through':
                        $rend .= "strike";
                        break;
                    case 'font-variant:small-caps':
                        $rend .= "small-caps";
                        break;
                    case 'vertical-align:super':
                        $rend .= "sup";
                        break;
                    case 'vertical-align:sub':
                        $rend .= "sub";
                        break;
                    case 'font-size:80%':
                        break;
                    case 'text-transform:uppercase':
                        $rend .= "uppercase";
                        break;
                    case 'text-transform:lowercase':
                        $rend .= "lowercase";
                        break;
                    case 'direction:ltr':
                        $rend .= "direction(ltr)";
                        break;
                    case 'direction:rtl':
                        $rend .= "direction(rtl)";
                        break;
                    default:
                        array_push($renditions, $tgdcl);
                        break;
                }
                $rend .= " ";
            }
            if ( count($renditions)>0) {
                $rendition = implode(";", $renditions);
            } else {
                $rendition = '';
            }

            $rend = trim($rend);
            if ( trim($rend) == "") {
                return null;
            } else {
                return $rend;
            }
        }

        /** get meta from lodel document **/
        private function oolodel2meta(&$dom) {
        error_log("<h4>oolodel2meta()</h4>\n",3,self::_DEBUGFILE_);

            $items = $dom->getElementsByTagName('*');
            foreach ($items as $item) {
                if ($item->nodeName==="text:p" and $item->hasAttributes()) {
                    $attributes = $item->attributes;
                    if ($attr=$attributes->getNamedItem("style-name")) {
                        $stylename = $attr->value;
                        if ( preg_match("/^P\d+$/", $stylename)) {
                            if ( isset($this->automatic["#".$stylename])) {
                                $stylename = $this->automatic["#".$stylename];
                            } else continue;
                        }
                        switch ($stylename) {
                            case "language":
                                $this->meta['dc:language'] = $item->nodeValue;
                                break;
                            case "title":
                                $this->meta['dc:title'] = $this->_ootitle($item->nodeValue);
                                break;
                            case "author":
                                if (! isset($this->meta['meta:initial-creator'])) {
                                    $this->meta['meta:initial-creator'] = $item->nodeValue;
                                }
                                if (! isset($this->meta['dc:creator'])) {
                                    $this->meta['dc:creator'] = array();
                                }
                                array_push($this->meta['dc:creator'], $item->nodeValue);
                                break;
                            case "creationdate":
                                $this->meta['meta:creation-date'] = $this->_oodate($item->nodeValue);
                                break;
                            case "date":
                                $this->meta['dc:date'] = $this->_oodate($item->nodeValue);
                                break;
                        }
                    }
                    else continue;
                }
            }

            return true;
        }

        /** set lodel-document properties */
        private function meta2lodelodt(&$dommeta) {
        error_log("<h4>meta2lodelodt()</h4>\n",3,self::_DEBUGFILE_);
            # office:document-meta/office:meta
            $items = $dommeta->getElementsByTagName('*');
            foreach ($items as $item) {
                switch ($item->nodeName) {
                    case "dc:title":
                        if ( isset($this->meta['dc:title'])) {
                            $item->nodeValue = $this->meta['dc:title'];
                        }
                        break;
                    case "meta:initial-creator":
                        if ( isset($this->meta['meta:initial-creator'])) {
                            $item->nodeValue = $this->meta['meta:initial-creator'];
                        }
                        break;
                    case "meta:creation-date":
                        if ( isset($this->meta['meta:creation-date'])) {
                            $item->nodeValue = $this->meta['meta:creation-date'];
                        }
                        break;
                    case "dc:creator":
                        if ( isset($this->meta['dc:creator'])) {
                            $item->nodeValue = implode(",", $this->meta['dc:creator']);
                        }
                        break;
                    case "dc:date":
                        if ( isset($this->meta['dc:date'])) {
                            $item->nodeValue = $this->meta['dc:date'];
                        }
                        break;
                    case "meta:print-date":
                        $date = date(DATE_ATOM);
                        $this->nodeValue = $date;
                        break;
                    case "meta:editing-cycles":
                        $item->nodeValue = 1;
                        break;
                    case "meta:editing-duration":
                    case "meta:document-statistic":
                    case "meta:generator":
                        // TODO ?
                        break;
                    // TODO...
                    # more
                    case "dc:description":
                        // abstract
                        break;
                    case "meta:keyword":
                        // <keyword> <keyword> ...
                        break;
                    case "dc:subject":
                        // suject [+chronological] [+geographical]
                        break;
                    # meta:user-defined
                    // case meta:name="Document number"
                    // case meta:name="E-Mail"
                    // case meta:name="Editor"
                    // case meta:name="Info"
                    // case meta:name="Language"
                    // case meta:name="Project" 
                    // case meta:name="Publisher
                    // case meta:name="Status"
                    // case meta:name="URL
                }
            }
            return true;
        }

        /** set xml-document properties */
        private function meta2otxml() {
        // otx TEI xml
        // author: name, affiliation, forename, surname
        // idno: URI, DOI, EISSN, ISSN, ISBN, pagination
        // front: abstract
        }


    /**
    * report for checkbalisage
    **/
    protected function oo2report($step, $filepath="") {
    error_log("<h3>oo2report($filepath)</h3>\n",3,self::_DEBUGFILE_);

        switch ($step) {
            case 'soffice':
            case 'lodel':
                $za = new ZipArchive();
                if (! $za->open($filepath)) {
                    $this->_status="error open ziparchive ($filepath)";error_log("<h1>{$this->_status}</h1>\n",3,self::_DEBUGFILE_);
                    throw new Exception($this->_status,E_ERROR);
                }
                if (! $meta=$za->getFromName('meta.xml')) {
                    $this->_status="error get meta.xml";error_log("<h1>{$this->_status}</h1>\n",3,self::_DEBUGFILE_);
                    throw new Exception($this->_status,E_ERROR);
                }
                $dommeta = new DOMDocument;
                $dommeta->encoding = "UTF-8";
                $dommeta->resolveExternals = false;
                $dommeta->preserveWhiteSpace = false;
                $dommeta->formatOutput = true;
                if (! $dommeta->loadXML($meta)) {
                    $this->_status="error load meta.xml";error_log("<h1>{$this->_status}</h1>\n",3,self::_DEBUGFILE_);
                    throw new Exception($this->_status,E_ERROR);
                }
                $xmlmeta = str_replace('<?xml version="1.0" encoding="UTF-8"?>', "", $dommeta->saveXML());
                $za->close();
                $this->log['report'][$step] = $xmlmeta;

                # json
                error_log("<h4>JSON</h4>\n",3,self::_DEBUGFILE_);
                $prop = array();
                $xpath = new DOMXPath($dommeta);
                $entries = $xpath->query("//office:meta/*");
                foreach ($entries as $entry) {
                    //error_log("<li>{$entry->nodeName} : {$entry->nodeValue}</li>\n",3,self::_DEBUGFILE_);
                    if ($entry->nodeName == "meta:document-statistic") {
                        foreach ($entry->attributes as $attr) {
                            //error_log("<li>{$attr->name} : {$attr->value}</li>\n",3,self::_DEBUGFILE_);
                            $prop[$attr->name] = $attr->value;
                        }
                    } 
                    else {
                        $key = $entry->nodeName;
                        $value = $entry->nodeValue;
                        $prop[$key] = $value;
                    }
                }
                $this->report["meta-$step"] = $prop;
                break;
            default:
                break;
        }

        $mode = $this->_param['mode'];
        $xmlreport = ''; //.$this->_param['xmlreport'];

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
EOD;

        if ( isset($this->log['report']['soffice'])) {
            $xmlreport .= <<<EOD
    <item rdf:about="#soffice">
        <title>openoffice document-meta</title>
        <link>http://otx.lodel.org/?soffice</link>
        <description>
        OpenOffice original document properties
        </description>
        {$this->log['report']['soffice']}
    </item>
EOD;
        }
 
        if ( isset($this->log['report']['lodel'])) {
            $xmlreport .= <<<EOD
    <item rdf:about="#lodel">
        <title>openoffice lodel-meta</title>
        <link>http://otx.lodel.org/?lodel</link>
        <description>Lodel ODT document properties</description>
        {$this->log['report']['lodel']}
    </item>
EOD;
        }

        if ( isset($this->log['status'])) {
            $xmlreport .= <<<EOD
    <item rdf:about="#status">
        <title>OTX status</title>
        <link>http://otx.lodel.org/?status</link>
        <description>is TEI valid</description>
        <dc:description rdf:parseType="Literal">
            <ul class="tei">
                <li class="lodel">{$this->log['status']['lodeltei']}</li>
                <li class="otx">{$this->log['status']['otxtei']}</li>
            </ul>
        </dc:description>
    </item>
EOD;
        }

        if ( count($this->log['warning'])) {
            $xmlreport .= <<<EOD
    <item rdf:about="#warning">
        <title>OTX warning</title>
        <link>http://otx.lodel.org/?warning</link>
        <description>Warnings</description>
        <dc:description rdf:parseType="Literal">
            <ul class="warning">
EOD;
            foreach ($this->log['warning'] as $warning) {
                $xmlreport .= "<li>$warning</li>\n";
            }
            $xmlreport .= <<<EOD
            </ul>
        </dc:description>
    </item>
EOD;
        }

        $xmlreport .= <<<EOD
</rdf:RDF>
EOD;

        $tmpfile=$this->_param['TMPPATH']."report.xml";file_put_contents($tmpfile, $xmlreport);
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


    private function params() {
    error_log("<h4>_param()</h4>\n",3,self::_DEBUGFILE_);
        $request = $this->_param['request'];
        $mode = $this->_param['mode'];

        $domrequest = new DOMDocument;
	if (! $domrequest->loadXML($request)) {
            $this->_status="error: can't load xml request";$this->_iserror=true;
            error_log("<li>! {$this->_status}</li>\n", 3, self::_DEBUGFILE_);
            throw new Exception($this->_status,E_ERROR);
	}

        # !! TODO !!
        // document type
        /*
        editorial
        article
        actualite
        compterendu
        notedelecture
        chronique
        informations
        */

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
            throw new Exception($this->_status,E_ERROR);
        }
        error_log("<li>[_params] sourcepath={$this->_param['sourcepath']}</li>\n",3,self::_DEBUGFILE_);

        $this->_param['modelpath'] = $this->_param['CACHEPATH'].$this->_param['revuename']."/"."model.xml";
        if (! copy($this->input['modelpath'], $this->_param['modelpath'])) {
            $this->_status="error: failed copy {$this->_param['modelpath']}";
            throw new Exception($this->_status,E_ERROR);
        }
        error_log("<li>[_params] modelpath={$this->_param['modelpath']}</li>\n",3,self::_DEBUGFILE_);

        // save the rdf request
        $requestfile=$this->_param['TMPPATH'].$this->_param['prefix'].".rdf";@$domrequest->save($requestfile);
    }


    /** pdf document to xml **/
    protected function pdf2tei() {
        $CHARendofpage = "\x0C";
        $TI = array();
        $data = "";

        if (! $data=file_get_contents( str_replace(" ", "%20", $this->pdfsource))) {
            $this->status = "404 Not Found : ".$this->pdfsource;
            return FALSE;
        }
        $pdffile = $this->_SERVER_TMP .array_pop( explode("/", str_replace(" ", "", $this->pdfsource)));
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

        $this->pdf2TEIback = "";
        return true;
    }


        private function _oodate($ladatepubli) {
            $patterns = array ('/janvier/', '/février/', '/mars/', '/avril/', '/mai/', '/juin/', '/juillet/', '/aout/', '/septembre/', '/octobre/', '/novembre/', '/décembre/');
            $replace = array ('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');

            list($date, $T) = explode('T', date("Y-m-d", strtotime( preg_replace($patterns, $replace, $ladatepubli)."T00:00:00")));
            error_log("<li>_oodate: $date</li>\n",3,self::_DEBUGFILE_);
            return $date;
        }

        private function _ootitle($title) {
            list($ootitle, $moretitle) = preg_split("/\*/", $title);
            return trim($ootitle);
        }

        private function _cleanup() {
        error_log("<li>_cleanup()()</li>\n",3,self::_DEBUGFILE_);

            @unlink(self::_SOAP_LOCKFILE_);
            @unlink($this->input['modelpath']);
            @unlink($this->input['entitypath']);

            @unlink($this->_param['modelpath']);
            @unlink($this->_param['sourcepath']);
            @unlink($this->_param['odtpath']);
        }


    /** Hello world! **/
    protected function Hello() {
        if (defined('__DEBUG__')) error_log("<h3>Hello()</h3>\n",3,self::_DEBUGFILE_);
        $this->output['status'] = "HTTP/1.0 200 OK";
        $this->output['xml'] = "";
        $this->output['report'] = "Hello world!";
        $this->output['contentpath'] = '';
        $this->output['lodelxml'] = "";
	return true;
    }


// end of OTXserver class.
}

#EOF