<?php
/**
 * @package OTX
 * @copyright Centre pour L'édition Électronique Ouverte
 * @licence http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
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
    protected $input 	= array('request'=>"", 'mode'=>"", 'modelpath'=>"", 'entitypath'=>"");
    // Outputs
    protected $output 	= array('status'=>"", 'xml'=>"", 'report'=>"", 'contentpath'=>"", 'lodelxml'=>"");

    private $report 	= array();

    protected $meta 	= array();
    // correspondance entre les noms de style et la rendition attendu
    protected $EModel 	= array();
    private $EMotx 		= array();
    // tableau en dur correspondant au fichier inc/EM.odd.php. Fait correspondre le champ 'otx' du ME lodel à la rendition attendue
    private $EMTEI 		= array();
    private $EMandatory = array();
    private $LodelImg 	= array();

    private $dom 		= array(); // fourre tout
    private $automatic 	= array();
    private $rend 		= array();
    private $rendition 	= array(); // tableau des styles du document, puis de ceux à appliquer
    private $Pnum 		= 0;
    private $Tnum 		= 0;
    private $tagsDecl 	= array();

    private $log 		= array();

    private $_data 		= array();
    private $_status	= "";
    private $_trace		= "";
    private $_iserror	= false;
    private $_isdebug	= true;
    private $oostyle 	= array();
    private $_dbg 		= 1;
    private $_db; 		// db instance
    private $_config;
    
    private $_keywords = array();
    
    private $_usedfiles = array();

    /** A private constructor; prevents direct creation of object (singleton because) **/
    private function __construct($site="", $sourceoriginale="", $mode="", $modelpath="", $entitypath="") {
        $this->_config = OTXConfig::singleton();
        mb_internal_encoding("UTF-8");

        $this->input['site'] = preg_replace("/[^\w\d]+/", "", $site);
        $this->input['sourceoriginale'] =  $sourceoriginale;
        $this->input['mode'] 		= $mode;
        $this->input['modelpath'] 	= $modelpath;
        $this->input['entitypath'] 	= $entitypath;

        $this->_usedfiles = array($modelpath, $entitypath);

        $this->_param['EMreport']	= array();
        $this->_param['mime'] 		= "";
        $this->_param['prefix'] 	= "";
        $this->_param['sufix'] 		= "";
        $this->_param['odtpath'] 	= "";
        $this->_param['xmlodt'] 	= "";
        $this->_param['xmlreport'] 	= "";
        $this->_param['LIBPATH'] 	= "server/lib/";

        $this->_param['tmppath'] = $this->_config->cachepath . DIRECTORY_SEPARATOR . $this->input['site'] . DIRECTORY_SEPARATOR . uniqid("convert");
        @mkdir($this->_param['tmppath'], 0755, true);

        $this->log['warning'] = array();

        $this->_db = new PDO($this->_config->db->dsn, $this->_config->db->user, $this->_config->db->password);
    }

    /** Prevent users to clone the instance (singleton because) **/
    public function __clone() {
        $this->_status = "Cannot duplicate a singleton";
        $this->_iserror = true;
        throw new Exception($this->_status,E_ERROR);
    }

    public function __toString() {
        return $this->_status;
    }

    /**
    * The singleton method
    **/
    public static function singleton($site="", $sourceoriginale="", $mode="", $modelpath="", $entitypath="") {
        if (!isset(self::$_instance_)) {
            // First invocation only.
            $class = __CLASS__;
            self::$_instance_ = new $class($site, $sourceoriginale, $mode, $modelpath, $entitypath);
            return self::$_instance_;
        }
        else {
            return self::$_instance_;
        }
    }



/**
 * just do it !
**/
    public function run() {
        $suffix = "odt";
        if (false !== strpos($this->input['mode'], ":")) {
            $action = explode(":", $this->input['mode']);
            switch($action[0]) {
                case 'soffice':
                    $suffix = $action[1];
                    break;
                case 'lodel':
                    $this->_param['type'] = $action[1];
                    break;
                case 'plugin':
                    $this->_param['type'] = $action[2];
                    break;
            }
        }
        else {
            $action = array($this->input['mode'], );
            $suffix = "odt";
        }

        if ($action !== "hello") {
            $this->params();
        }

        switch ($action[0]) {
            case 'soffice':
                $this->soffice($suffix);
                $this->output['contentpath'] = $this->_param['outputpath'];
                if ($suffix=="odt") {
                    $this->oo2report('soffice', $this->_param['odtpath']);
                }
                break;
            case 'lodel':
                $this->soffice();
                $this->oo2report('soffice', $this->_param['odtpath']);
                $this->Schema2OO(); // Création de EMTEI[], EMotx[] et EModel[]
                $this->lodelodt(); // créer une sortie du fichier .odt en TEI simple dans dom['teifodt']
                $this->oo2lodelxml(); // de dom['teifodt'] à _param['lodelTEI'] TEI lodel
                $this->output['lodelxml'] = null;
                $this->oo2report('lodel', $this->_param['lodelodtpath']);
                $this->output['contentpath'] = $this->_param['lodelodtpath'];
                $this->loodxml2xml(); // de _param['lodelTEI'] à _param['TEI'] TEI lodel complète
                $this->output['xml'] = _windobclean($this->_param['TEI']);
                $this->output['report'] = json_encode($this->report);

                if(file_exists($this->_param['outputpath'])){
                    $this->output['odt'] = base64_encode(file_get_contents($this->_param['lodelodtpath']));
                }

                break;
            case 'tei-c':
                // Conversion simple à partir de la feuille du consortium TEI
                $this->soffice($suffix);
                $this->to_teip5($this->_param['odtpath']);
                break;
            case 'plugin':
                $this->plugin($action[1]);
                break;
            case 'hello':
                $this->hello();
                return $this->output;
                break;
            default:
                $this->_status="error: unknown action ($action)";$this->_iserror=true;
                throw new Exception($this->_status,E_USER_ERROR);
        }

        $this->_status = $this->_config->servicename;
        $this->output['status'] = $this->_status;

        return $this->output;
    }

    private function to_teip5($odt_path)
    {
        $odt_file = new ZipArchive();
        if( $odt_file->open($odt_path) === TRUE ){
            $odt_file->extractTo($this->_param['tmppath']);
            $odt_file->close();

            $content = $this->_param['tmppath'] . DIRECTORY_SEPARATOR . 'content.xml';
            $odt_dom = new DOMDocument();
            $odt_dom->load($content);

            $xslfilter = "server/inc/oo2lodeltei.xsl";
            $xsl = new DOMDocument();
            if (! $xsl->load($xslfilter)) {
                throw new Exception("error load xsl ($xslfilter)",E_ERROR);
            }

            $proc = new XSLTProcessor();
            $proc->importStyleSheet($xsl);
            if (! $tei = $proc->transformToXML($odt_dom)) {
                throw new Exception("error transform xslt ($xslfilter)",E_ERROR);
            }
            error_log(var_export($tei,true));
        }else{
            throw new Exception("File can't be converted");
        }

    }

/**
 * «dynamic mapping» (sic) of Lodel EM
 * Création des tableaux de correspondance entre le ME et les attributs rendition
 * EMTEI[] : tableau en dur du fichier inc/EM.odd.php
 * EMotx[] : liens entre rendition et place dans la TEI (css whitelist aussi)
 * EModel[] : liens entre style du ME et rendition
 * _keywords[] : tableau de nom des types de mots-clefs
**/
    protected function Schema2OO() {

        $this->EMTEI = _em2tei();
        $domxml = new DOMDocument;
        $domxml->encoding = "UTF-8";
        $domxml->recover = true;
        $domxml->strictErrorChecking = false;
        $domxml->resolveExternals = false;
        $domxml->preserveWhiteSpace = true;
        $domxml->formatOutput = false;
        if (! $domxml->load($this->input['modelpath'])) {
            $this->_status="error load model.xml";
            throw new Exception($this->_status,E_ERROR);
        }

        # OTX EM test
        if (! strstr($domxml->saveXML(), "<col name=\"otx\">")) {
            // TODO : warning and load a default OTX EM ?!
            $this->_status="error: EM not OTX compliant";
            throw new Exception($this->_status,E_USER_ERROR);
        }

        $domxpath = new DOMXPath($domxml);

        $Model = array();
        $OOTX = array();
        $nbEmStyle = $nbOtxStyle = 0;

        // lecture du ME
        // Création du tableau EMotx[nom_rendition] = array('key'=> head|front|body|text, 'allowedstyles' => whitelist CSS, 'surround' => qui ne sert à rien)
        foreach($domxpath->query('//row') as $node){
            $value = $keys = $g_otx = $lang = '';
            $row      = array();
            $otxvalue = null;
            $bstyle   = false;
            foreach($domxpath->query('./col', $node) as $col){
                if($col->hasAttribute('name')){
                    $attr = $col->getAttribute('name');
                    switch($attr){
                        case "classtype":
                            $row['classtype'] = $col->nodeValue;
                            break;
                        case "type":
                            if($col->nodeValue == "entries"){
                                $entrytype = $domxpath->query("//row[col[@name='type' and text() = '{$row['name']}']]")->item(0);
                                if($entrytype){
                                    $lang   = $domxpath->query("./col[@name='lang']",$entrytype)->item(0);
                                    $styles = $domxpath->query("./col[@name='style']",$entrytype)->item(0);
                                    $xpath  = $domxpath->query("./col[@name='otx']",$entrytype)->item(0);
                                    $styles = preg_split("/[ ,]+/", $styles->nodeValue);
                                    
                                    $this->_keywords[$row['name']] = array(
                                                                        "lang"   => $lang->nodeValue,
                                                                        "scheme"  => $this->parse_scheme($xpath->nodeValue, $row['name']),
                                                                        "styles" => $styles,
                                                                    );
                                }
                            }
                            break;
                        case "name":
                            if (! isset($row['name'])) $row['name'] = trim($col->nodeValue);
                            break;
                        case "style":
                            $row[$attr] = trim($col->nodeValue);
                            $style = trim($col->nodeValue);
                            if ($style == '') { // empty : no style defined !
                                //continue 3;
                            }
                            $bstyle = true;
                            break;
                        case "g_type":
                        case "g_name":
                            $gvalue = trim($col->nodeValue);
                            $row['gname'] = trim($col->nodeValue);
                            break;
                        case 'surrounding':
                            $row[$attr] = trim($col->nodeValue);
                            break;
                        case 'allowedtags': // hack of allowedtags to create csswhitelist
                            $allowedstyles = $this->list_allowedstyles(trim($col->nodeValue));
                            if ($allowedstyles) {
                                $row['allowedstyles'] = $allowedstyles;
                            }
                            break;
                        case "lang":
                            $lang = trim($col->nodeValue);
                            $row[$attr] = trim($col->nodeValue);
                            break;
                        case "otx":
                            $nodevalue = trim($col->nodeValue);
                            if ($nodevalue == '') {
                                //continue 3;
                            }
                            $row[$attr] = trim($col->nodeValue);
                            break;
                        default:
                            break;
                    }
                }
            }

            // EM otx style definition
            if ( isset($row['otx'])) {
                if (! isset($row['style'])) {
                    continue;
                }

                $emotx = $row['otx'];
                if (! isset($this->EMTEI[$emotx])) {
                    // TODO ? haha, ben oui TODO : rendre le mapping dynamique et non pas se baser sur un tableau en dur
                    continue;
                }else {
                    $nbOtxStyle++;
                    $otxkey = $otxvalue = '';
                    $emotx = $this->EMTEI[$emotx];

                    if ( isset($row['lang']) ) {
                        $emotx .= "-".$row['lang'];
                    }

                    if ( strstr($emotx, ":")) {
                        list($otxkey, $otxvalue) = explode(":", $emotx);
                        $this->EMotx[$otxvalue]['key'] = $otxkey;
                        if (isset($row['allowedstyles']))
                            $this->EMotx[$otxvalue]['allowedstyles'] = $row['allowedstyles'];
                    } else {
                        $otxvalue = $emotx;
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

        $this->_param['EMreport']['nbLodelStyle'] = $nbEmStyle;
        $this->_param['EMreport']['nbOTXStyle'] = $nbEmStyle;

        // construction de EModel
        // pour chaque style défini dans le ME, donner un nom canonique qui sera utilisé comme attribut rendition
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
            else { // ceci n'arrive jamais !
                if ($newkey!='' and $lang!='')
                    $this->EModel[$newkey] = $value."-$lang";
                else 
                    $this->EModel[$key] = $value;
            }
        }
        unset($Model);
        unset($OOTX);

        # surrounding
        // le surrounding n'est utilisé nul par ailleur (évacué car inutile et faiseur de bug)
        $xpath = new DOMXPath($domxml);
        $query = '/lodelEM/table[@name="#_TP_internalstyles"]/datas/row';
        $entries = $xpath->query($query);
        foreach ($entries as $item) {
            if ($item->hasChildNodes()) {
                $value = $otxkey = $otxvalue = "";
                foreach ($item->childNodes as $child) {
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

        unset($domxml);
        return true;
    }


    private function parse_scheme($xpath, $default)
    {
        if(preg_match("/@scheme=[\'\"](?P<scheme>\w+)[\'\"]/", $xpath, $matches)){
            return $matches['scheme'];
        }else{
            return $default;
        }
        
    }

    // Construct csswhitelist for a given field in the document
    private function list_allowedstyles($allowedtags) {
        $allowedstyles = array();
        if ($allowedtags) {
            if (preg_match('/style:(.*)(;|$)/', $allowedtags, $m)) {
                $allowedstyles[$m[1]] = true;
            }
        }
        return empty($allowedstyles) ? false :  $allowedstyles;
    }

/**
 * transformation d'un odt en lodel-odt : format pivot de travail
 * dezipe l'odt
 * création de rendition[] et automatic[]
 * whitelist des css
 * simplification XML du fichier odt 
 * création du fichier TEI
**/
    protected function lodelodt() {

//
// Traitement des fichiers (dézipage, etc…)
//
        $cleanup = array('/_20_Car/', '/_20_/', '/_28_/', '/_29_/', '/_5f_/', '/_5b_/', '/_5d_/', '/_32_/', '/WW-/' );

        $odtfile = $this->_param['odtpath'];
        $this->_param['lodelodtpath'] = $this->_param['tmppath']. DIRECTORY_SEPARATOR .$this->_param['prefix'].".lodel.odt";
        $lodelodtfile = $this->_param['lodelodtpath'];
        
        if (! copy($odtfile, $lodelodtfile)) {
            $this->_status="error copy file; ".$lodelodtfile;
            throw new Exception($this->_status,E_ERROR);
        }
        # odt...
        $za = new ZipArchive();
        if (! $za->open($lodelodtfile)) {
            $this->_status="error open ziparchive; ".$lodelodtfile;
            throw new Exception($this->_status,E_ERROR);
        }
        # ----- office:meta ----------------------------------------------------------
        if (! $OOmeta=$za->getFromName('meta.xml')) {
            $this->_status="error get meta.xml";
            throw new Exception($this->_status,E_ERROR);
        }
        $dommeta = new DOMDocument;
        $dommeta->encoding = "UTF-8";
        $dommeta->resolveExternals = false;
        $dommeta->preserveWhiteSpace = true;
        $dommeta->formatOutput = false;
        if (! $dommeta->loadXML($OOmeta)) {
            $this->_status="error load meta.xml";
            throw new Exception($this->_status,E_ERROR);
        }

        # cleanup
        $lodelmeta = _windobclean($OOmeta);
        # lodel
        $domlodelmeta = new DOMDocument;
        $domlodelmeta->encoding = "UTF-8";
        $domlodelmeta->resolveExternals = false;
        $domlodelmeta->preserveWhiteSpace = true;
        $domlodelmeta->formatOutput = false;
        if (! $domlodelmeta->loadXML($lodelmeta)) {
            $this->_status="error load lodel-meta.xml";
            throw new Exception($this->_status,E_ERROR);
        }
        $domlodelmeta->normalizeDocument();

        # ----- office:settings ----------------------------------------------------------
        if (! $OOsettings=$za->getFromName('settings.xml')) {
            $this->_status="error get settings.xml";
            throw new Exception($this->_status,E_ERROR);
        }
        $domsettings = new DOMDocument;
        $domsettings->encoding = "UTF-8";
        $domsettings->resolveExternals = false;
        $domsettings->preserveWhiteSpace = true;
        $domsettings->formatOutput = false;
        if (! $domsettings->loadXML($OOsettings)) {
            $this->_status="error load settings.xml";
            throw new Exception($this->_status,E_ERROR);
        }
        # cleanup
        $lodelsettings = _windobclean($OOsettings);
        # lodel
        $domlodelsettings = new DOMDocument;
        $domlodelsettings->encoding = "UTF-8";
        $domlodelsettings->resolveExternals = false;
        $domlodelsettings->preserveWhiteSpace = true;
        $domlodelsettings->formatOutput = false;
        if (! $domlodelsettings->loadXML($lodelsettings)) {
            $this->_status="error load lodel-settings.xml";
            throw new Exception($this->_status,E_ERROR);
        }
        $domlodelsettings->normalizeDocument();

        # ----- office:styles ---------------------------------------
        if (! $OOstyles=$za->getFromName('styles.xml')) {
            $this->_status="error get styles.xml";
            throw new Exception($this->_status,E_ERROR);
        }

        # cleanup
        $lodelstyles = preg_replace($cleanup, "", _windobclean($OOstyles));
        # lodel
        $domlodelstyles = new DOMDocument;
        $domlodelstyles->encoding = "UTF-8";
        $domlodelstyles->resolveExternals = false;
        $domlodelstyles->preserveWhiteSpace = true;
        $domlodelstyles->formatOutput = false;
        if (! $domlodelstyles->loadXML($lodelstyles)) {
            $this->_status="error load lodel-styles.xml";
            throw new Exception($this->_status,E_ERROR);
        }
        // lodel-cleanup++
        $this->lodelcleanup($domlodelstyles);
        $domlodelstyles->normalizeDocument(); 

        # ----- office:content -------------------------------------------------------
        if (! $OOcontent=$za->getFromName('content.xml')) {
            $this->_status="error get content.xml";
            throw new Exception($this->_status,E_ERROR);
        }

        # cleanup
        $lodelcontent = preg_replace($cleanup, "", _windobclean($OOcontent));
        # lodel
        $domlodelcontent = new DOMDocument;
        $domlodelcontent->encoding = "UTF-8";
        $domlodelcontent->resolveExternals = false;
        $domlodelcontent->preserveWhiteSpace = true;
        $domlodelcontent->formatOutput = false;
        if (! $domlodelcontent->loadXML($lodelcontent)) {
            $this->_status="error load lodel-content.xml";
            throw new Exception($this->_status,E_ERROR);
        }

        // lodel-cleanup++
        $this->lodelcleanup($domlodelcontent);

        $this->lodelpictures($domlodelcontent, $za);
        $domlodelcontent->normalizeDocument();

//
// Traitement des styles
//
        // 1. office:automatic-styles : rendition[] et automatic[]
        $this->ooautomaticstyles($domlodelcontent);
        // 2. office:styles : rendition[] et automatic[]
        $this->oostyles($domlodelstyles);
        // 3. (document) meta : meta[]
        $this->oolodel2meta($domlodelcontent);
        // 4. meta[] de meta.xml
        $this->meta2lodelodt($domlodelmeta);
        # LodelODT
        if (! $za->addFromString('meta.xml', $domlodelmeta->saveXML())) {
            $this->_status="error ZA addFromString lodelmeta";
            throw new Exception($this->_status,E_ERROR);
        }
        if (! $za->addFromString('settings.xml', $domlodelsettings->saveXML())) {
            $this->_status="error ZA addFromString lodelsettings";
            throw new Exception($this->_status,E_ERROR);
        }
        if (! $za->addFromString('styles.xml', $domlodelstyles->saveXML())) {
            $this->_status="error ZA addFromString lodelstyles";
            throw new Exception($this->_status,E_ERROR);
        }
        if (! $za->addFromString('content.xml', $domlodelcontent->saveXML())) {
            $this->_status="error ZA addFromString lodelcontent";
            throw new Exception($this->_status,E_ERROR);
        }
        $za->close();

        $newdom = new DOMDocument('1.0', 'UTF-8');
        $newdom->loadXML('<office:document
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
    office:version="1.2" office:mimetype="application/vnd.oasis.opendocument.text"></office:document>');

        function insertNodesFromXpaths($xpaths, $sourcenode, &$destnode, &$destdoc){
            $xpath = new DOMXpath($sourcenode);
            foreach($xpaths as $path){
                $item = $xpath->query($path)->item(0);
                if(isset($item)){
                    $node = $destdoc->importNode($item->cloneNode(true),true);
                    $destnode->appendChild($node);
                }
            }
        }

        $newdomdocument = $newdom->getElementsByTagName('document')->item(0);
        insertNodesFromXpaths(array(
                                    '/office:document-content/office:body',
                                    '/office:document-content/office:automatic-styles'
                                    ),
                             $domlodelcontent,
                             $newdomdocument,
                             $newdom
                            );

        insertNodesFromXpaths(array('/office:document-meta/office:meta'), $domlodelmeta, $newdomdocument, $newdom);
        insertNodesFromXpaths(array('/office:document-settings/office:settings'), $domlodelsettings, $newdomdocument, $newdom);
        insertNodesFromXpaths(array(
                                    '/office:document-styles/office:master-styles',
                                    '/office:document-styles/office:styles',
                                    '/office:document-styles/office:font-face-decls',
                                    ), $domlodelstyles, $newdomdocument, $newdom);


        $this->_param['xmlLodelODT'] = $newdom->saveXML();

        // fodt xml
        $domfodt = new DOMDocument;
        $domfodt->encoding = "UTF-8";
        $domfodt->resolveExternals = false;
        $domfodt->preserveWhiteSpace = true;
        $domfodt->formatOutput = false;
        if (! @$domfodt->loadXML($this->_param['xmlLodelODT'])) {
            $this->_status="error load fodt xml";
            throw new Exception($this->_status,E_ERROR);
        }
        $domfodt->normalizeDocument();

        // normaliser les xml:id des listes (le but est de toujours sortir le même document, les id données par ooo étant changeantes)
        $xpath = new DOMXpath($domfodt);
        $items = $xpath->query("//*[@xml:id[starts-with(., 'list')]]");
        $lists = array();
        $i = 0;
        foreach ($items as $item) {
            $id = $item->getAttribute('xml:id');
            if (isset($lists[$id])) {
                $id = $lists[$id];
            } else {
                $id = $lists[$id] = sprintf("list%010d", ++$i);
            }
            $item->setAttribute('xml:id', $id);
        }
        unset($lists);

//
// Traitement par xsl
//
        # add xml:id (otxid.xsl)
        $xslfilter = "server/inc/otxid.xsl";
        $xsl = new DOMDocument;
        if (! $xsl->load($xslfilter)) {
            $this->_status="error load xsl ($xslfilter)";
            throw new Exception($this->_status,E_ERROR);
        }
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);
        if (! $idfodt=$proc->transformToXML($domfodt)) {
            $this->_status="error transform xslt ($xslfilter)";
            throw new Exception($this->_status,E_ERROR);
        }
        $domidfodt = new DOMDocument;
        $domidfodt->encoding = "UTF-8";
        $domidfodt->resolveExternals = false;
        $domidfodt->preserveWhiteSpace = true;
        $domidfodt->formatOutput = false;
        if (! $domidfodt->loadXML($idfodt)) {
            $this->_status="error load idfodt xml";
            throw new Exception($this->_status,E_ERROR);
        }

        $domidfodt->normalizeDocument();

        // oo to lodeltei xslt [oo2lodeltei.xsl]
        // change les styles ODT en rendition (style == ^p | ^t | standard) et en rend (style == heading et les autres)
        $xslfilter = "server/inc/oo2lodeltei.xsl";
        $xsl = new DOMDocument;
        if (! $xsl->load($xslfilter)) {
            $this->_status="error load xsl ($xslfilter)";
            throw new Exception($this->_status,E_ERROR);
        }
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);
        if (! $teifodt = $proc->transformToXML($domidfodt)) {
            $this->_status="error transform xslt ($xslfilter)";
            throw new Exception($this->_status,E_ERROR);
        }

        $domteifodt = new DOMDocument;
        $domteifodt->encoding = "UTF-8";
        $domteifodt->resolveExternals = false;
        $domteifodt->preserveWhiteSpace = true;
        $domteifodt->formatOutput = false;
        if (! $domteifodt->loadXML($teifodt)) {
            $this->_status="error load teifodt xml";
            throw new Exception($this->_status,E_ERROR);
        }

        $domteifodt->normalizeDocument();

        $this->dom['teifodt'] = $domteifodt;
        //$this->lodeltei($domteifodt);
        return true;
    }



/**
 * transformation d'un lodel-odt en lodel-xml ( flat TEI... [raw mode] )
 * utilise dom['teifodt'] (TEI simple)
 * traite les styles
 * ajoute les éléments <rendition> à la TEI
**/
    protected function oo2lodelxml() {

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

        // traiter le style des lists
        $entries = $xpath->query("//tei:list[@rendition]");
        foreach ($entries as $item) {
            $this->liststyles($item);
        }

        // $entries = $xpath->query("//tei:p[@rendition] or //tei:s[@rendition]");
        $entries = $xpath->query("//tei:*[@rendition]");
        foreach ($entries as $item) {
            $rend = '';
            $nodename = $item->nodeName;
            if ($nodename=="p" or $nodename=="s" or $nodename=="cell" or $nodename=="ab" or $nodename=="table") {
                if ( $value=$item->getAttribute("rendition")) {
                    if ($nodename=="cell" or $nodename=="table") {
                        $value = "#".str_replace('.','_',$value);
                    }
                    // rend ?
                    if ( isset($this->automatic[$value]) && $this->automatic[$value]!="standard") {
                        $rend = $this->automatic[$value];
                        $item->setAttribute("rend", $rend);
                    }

                    // rendition ?
                    if ( isset($this->rendition["$rend$value"])) {
                        // xml:lang ?
                        if ( !empty($this->rendition[$rend . $value]['lang']) ) {
                            $lang = $this->rendition[$rend . $value]['lang'];
                            $item->setAttribute("xml:lang", $lang);
                        }
                        // css style
                        if ( ! empty( $this->rendition[$rend . $value]['rendition'] ) ) {
                            $rendition = $this->rendition[$rend . $value]['rendition'];
                            if(strpos( $rend, '#') !== 0 && !empty($rend)) $rend = "#$rend";

                            $item->setAttribute("rendition", $rend . $value);
                            $tagsdecl[ $rend . $value] = $rendition;
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
            if($value == "footnotesymbol"){
              $item->removeAttribute('rend');
              continue;
            } 
            
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
                    if ( array_key_exists( $rendition, $this->rendition[$rendition] ) && $this->rendition[$rendition]['rendition']!='') {
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
            if ($item->hasAttribute("rendition")) {
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
                if (array_key_exists( $rendition, $this->rendition[$rendition] ) && $this->rendition[$rendition]['rendition']!='') {
                    $tagsdecl[$key] = $this->rendition[$rendition]['rendition'];
                    $item->setAttribute("rendition", $key);
                } else {
                    $item->removeAttribute("rendition");
                }
            }
        }

        
        $this->tagsDecl = array_merge($this->tagsDecl ,$tagsdecl);
        foreach ($this->tagsDecl as $key=>$value) {
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
        
        if(isset($Pdecl)){
        	ksort($Pdecl);
	        
	        foreach ($Pdecl as $key=>$value) {
	            $newnode = $dom->createElement("rendition", $value);
	            $rendition = $tagsDecl->appendChild($newnode);
	            $rendition->setAttribute('xml:id', "P".$key);
	            $rendition->setAttribute('scheme', 'css');
	        }
    	}
    	
   		if(isset($Tdecl)){
        	ksort($Tdecl);
    	
	        foreach ($Tdecl as $key=>$value) {
	            $newnode = $dom->createElement("rendition", $value);
	            $rendition = $tagsDecl->appendChild($newnode);
	            $rendition->setAttribute('xml:id', "T".$key);
	            $rendition->setAttribute('scheme', 'css');
	        }
   		}
   		
	   	if(isset($decl)){
        	ksort($decl);

	        foreach ($decl as $key=>$value) {
	            $newnode = $dom->createElement("rendition", $value);
	            $rendition = $tagsDecl->appendChild($newnode);
	            $rendition->setAttribute('xml:id', $key);
	            $rendition->setAttribute('scheme', 'css');
	        }
	   	}

        # surrounding internalstyles
        $entries = $xpath->query("//tei:front"); $front = $entries->item(0);
        $entries = $xpath->query("//tei:body"); $body = $entries->item(0);
        $entries = $xpath->query("//tei:back"); $back = $entries->item(0);

        $entries = $xpath->query("//tei:body/tei:*");
        $current = $previtem = $nextitem = array();
        $section = $newsection = "";
        $newbacksection = $backsection = "";
        foreach ($entries as $item) {
            // current
            $this->greedy($item, $current);
            if (isset($current)) {

                if ( isset($current['surround']) ) {
                    $surround = $current['surround'];
                    switch($surround) {
                        case "-*":
                            // prev
                            if(!isset($prev)){
                                do {
                                    $prev = $item->previousSibling;
                                } while ( is_object($prev) && get_class($prev) !== "DOMElement" );
                            }

                            if ($prev)
                                $this->greedy($prev, $previtem);

                            if ( isset($previtem['section'])) {
                                $newsection = $previtem['section'];
                                if ($newsection=="back" and isset($previtem['rend'])) {
                                    $newbacksection = preg_replace('/-.*/','',$previtem['rend']); // copy only the name of the section !
                                }
                            } else {
                                if ( isset($current['section'])) {
                                    $newsection = $current['section'];
                                }
                            }
                            break;
                        case "*-":
                            // next
                            if (is_object($item) === true) {
                                $next = $item;
                                do{
                                    do{
                                        $next = $next->nextSibling;
                                    }while( is_object($next) && get_class($next) !== "DOMElement" );

                                    if ($next)
                                        $this->greedy($next, $nextitem);
                                }while( preg_match('/^(heading|frame|figure)/', $nextitem['rend']) );
                            }

                            if ( isset($nextitem['section'])) {
                                $newsection = $nextitem['section'];
                                if ($newsection=="back" and isset($nextitem['rend'])) {
                                    $newbacksection = preg_replace('/-.*/','',$nextitem['rend']); // copy only the name of the section !
                                }
                            } else {
                                if ( isset($current['section'])) {
                                    $newsection = $current['section'];
                                }
                            }
                            break;
                        default:
                            break;
                    }
                } else {
                    if ( isset($current['section']) ) {
                        $newsection = $current['section'];
                        if ($newsection == "back") {
                            if ( isset($current['rend']) ) {
                                $newbacksection = $current['rend'];
                            }
                        }
                    } else {
                        $newsection = $section;
                    }
                }
            } else {
                $newsection = "body";
            }

            if ( $section!==$newsection or $backsection!==$newbacksection ) { // new section
                if ($section!==$newsection) {
                    $section = $newsection;
                } 
                elseif ($backsection!==$newbacksection) {
                    $section = "back";
                }

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
                        }
                        switch($backsection) {
                            case 'appendix':
                                $div = $dom->createElement("div");
                                $div->setAttribute('rend', "LodelAppendix");
                                $back->appendChild($div);
                                break;
                            case 'bibliographie':
                                $div = $dom->createElement("div");
                                $div->setAttribute('rend', "LodelBibliography");
                                $back->appendChild($div);
                                break;
                            default:
                                break;
                            
                        }
                        break;
                    default:
                        break;
                }
            }
            if ($backsection and $backsection!=$current['rend']) {
                $item->setAttribute('rend', "$backsection-{$current['rend']}");
            }
            $prev = $item;
            if(isset($div)) $div->appendChild($item);
        }
        
        # <hi> cleanup (tag hi with no attribute)
        $this->hicleanup($dom, $xpath); // <hi> to <nop> ...
        $search = array("<nop>", "</nop>");
        $lodeltei = "". str_replace($search, "", $dom->saveXML()); // ... and delete <nop>

        /** TODO Warning **/
        //$lodeltei = preg_replace("/([[[UNTRANSLATED.*]]])/s", "<!-- \1 -->", $lodeltei);

        $dom->encoding = "UTF-8";
        $dom->resolveExternals = false;
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        @$dom->loadXML($lodeltei);
        $dom->normalizeDocument();
        $this->_param['xmloutputpath'] = $this->_param['tmppath'] . DIRECTORY_SEPARATOR .$this->_param['prefix'].".lodeltei.xml";
        $dom->save($this->_param['xmloutputpath']);
        $this->_usedfiles[] = $this->_param['xmloutputpath'];

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('tei', 'http://www.tei-c.org/ns/1.0');

        # Warnings : recommended
        $mandatory = array();
        foreach ($this->EMandatory as $key=>$value) {
            if ( preg_match("/^dc\.(.+)$/", $key, $match)) {
                //list($section, $element) = explode(":", $value);
                $query = "//tei:p[starts-with(@rend,'$value')]";
                $entries = $xpath->query($query);
                if (! $entries->length) {
                    $this->_status = "dc:{$match[1]} not found";
                    array_push($mandatory, $this->_status);
                    array_push($this->log['warning'], $this->_status);
                }
            }
        }
        $this->report['warning'] = $mandatory;

        $this->_param['lodelTEI'] = "". $dom->saveXML();
        return true;
    }

	// process du style des list
	private function liststyles($list, $level = 1, $rendition = null){
		if(!isset($rendition)){
			$rendname  = $list->getAttribute('rendition') or $list->getAttribute('text:style-name');
			$rendition = $this->rendition[$rendname];
		}

		if(isset($rendition['levels']))
			$list->setAttribute('type', $rendition['levels'][$level]);

		if(isset($rendition['type'][$level]) && !empty($rendition['type'][$level])){
			$this->tagsDecl[$rendname] = "list-style-type: {$rendition['type'][$level]};";
			$list->setAttribute('rend', $rendname);
		} else {
			$list->removeAttribute('rendition');
		}

		/* Items parsing */
		foreach($list->childNodes as $childitem){
			$newlevel = $level + 1;
			
			/* Sub-list parsing */
			foreach($childitem->childNodes as $childlist){
				if($childlist->nodeName == "list")
					$this->liststyles($childlist, $newlevel, $rendition);
			}
		}
	}

    private function hicleanup(&$dom, &$xpath) {
        $bool = false;
        $entries = $xpath->query("//tei:hi", $dom); 
        foreach ($entries as $item) {
            if (! $item->hasAttributes()) {
                $parent = $item->parentNode;
                $newitem = $dom->createElement("nop");
                $this->copyNode($item, $newitem);
                if (! $parent->replaceChild($newitem, $item)) {
                    $this->_status="error replaceChild";
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
 * utilise _param['lodelTEI'] (TEI lodel)
 * traite en dur les éléments qui doivent aller dans <head>, <front> et <back>
 * traite les mots-clefs en utilisant _keywords
 * enregistre le document final dans _param['TEI']
**/
    protected function loodxml2xml() {
        $lodelmeta = array();

        # domloodxml to domxml
        $dom = new DOMDocument;
        $dom->encoding = "UTF-8";
        $dom->resolveExternals = false;
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        if (! @$dom->loadXML($this->_param['lodelTEI'])) {
            $this->_status="error load lodel.tei.xml";
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
            if ($rendition=$entry->getAttribute('rendition')) { $new->setAttribute('rendition', $rendition); }

            $this->copyNode($entry, $new);

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
                if ($rendition=$entry->getAttribute('rendition')) { $new->setAttribute('rendition', $rendition); }

                $this->copyNode($entry, $new);

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
                if ($rendition=$entry->getAttribute('rendition')) { $new->setAttribute('rendition', $rendition); }

                $this->copyNode($entry, $new);

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
            if ($rendition=$entry->getAttribute('rendition')) { $new->setAttribute('rendition', $rendition); }

            $this->copyNode($entry, $new);

            $titlestmt->appendChild($new);
            $parent->removeChild($entry);
        }
        # lodelME:Auteurs
        # /tei/teiHeader/fileDesc/titleStmt/author
        $entries = $xpath->query("//tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:author");
        foreach ($entries as $entry) {
            $titlestmt->removeChild($entry);
        }

        $entries = $xpath->query("//tei:p[@rend='author' or @rend='translator' or @rend='scientificeditor' or @rend='excavationsdirector' or @rend='collaborator']");
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
                    case 'excavationsdirector':
                    case 'collaborator':
                        $author = $dom->createElement('editor');
                        break;
                }
                if ($rend == "translator") {
                    $author->setAttribute('role', "translator");
                } elseif ($rend == "excavationsdirector") {
                    $author->setAttribute('role', "excavationsdirector");
                } elseif ($rend == "collaborator") {
                    $author->setAttribute('role', "collaborator");
                }
                 
                $titlestmt->appendChild($author);
                $name = $dom->createElement('name', $item);
                if ($lang=$entry->getAttribute('xml:lang')) { $name->setAttribute('xml:lang', $lang); }
                if ($rendition=$entry->getAttribute('rendition')) { $name->setAttribute('rendition', $rendition); }
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
                            $s = $dom->createElement('s');
                            foreach($next->childNodes as $child){
                                $s->appendChild($child->cloneNode(true));
                            }
                            
                            $desc->appendChild($s);
                            $author->appendChild($desc);
                            if ($lang=$next->getAttribute('xml:lang')) { $desc->setAttribute('xml:lang', $lang); }
                            if ($id=$next->getAttribute('xml:id')) { $desc->setAttribute('xml:id', $id); }
                            if ( $rendition=$next->getAttribute('rendition')) { $desc->setAttribute('rendition', $rendition); }

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
                                                    $element->setAttribute('type', "function");
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
            error_log("<li>? [Warning] no date defined</li>\n");
        }

        # mathematics formula, group formula and create nodes for it
        include_once('inc/html_entities.php');
        foreach (array('hi', 'p') as $elem ) { // inline and block, then mathml and latex
            foreach(array('mathml'=>'mathml', 'mathlatex'=>'latex') as $style => $notation) {
                // On ne veut pas grouper les balise 'hi'
                if ($elem === 'p') {
                    $query = "//tei:{$elem}[@rend='{$style}'][not(preceding-sibling::tei:{$elem}[1][@rend='{$style}'])]";
                } else {
                    $query = "//tei:{$elem}[@rend='{$style}']";
                }
                $entries = $xpath->query($query);
                foreach($entries as $entry){
                    $formula = $entry->textContent;
                    $parent  = $entry->parentNode;
                    if ($elem === 'p') { // block: group siblings together
                        while ($sibling = $entry->nextSibling) {
                            if (!($sibling->getAttribute('rend') === $style))
                                break;
                            $formula .= $sibling->textContent;
                            $parent->removeChild($entry);
                            $entry = $sibling;
                        }
                    }
                    if ($style === 'mathlatex') // latex formula can contain &,<,>
                        $formula = "<![CDATA[" . $formula . "]]>";
                    else
                        $formula = html_convert_entities($formula); // convert mathml entities to xml entities
                    $df = $dom->createDocumentFragment();
                    $df->appendXML($formula);
                    if ($style === 'mathml') {
                        $math = $df->firstChild;
                        if ($math && $math->nodeName == 'math') {
                            $math->setAttribute('display', $elem == 'hi' ? 'inline' : 'block');
                        }
                    }

                    $el = $dom->createElement($elem);
                    $el->setAttribute('rend', $notation);
                    $parent->replaceChild($el, $entry);
                    $f = $dom->createElement('formula');
                    $f->setAttribute('notation', $notation);
                    $el->appendChild($f);
                    $f->appendChild($df);
                }
            }
        }
        // be sure math tags have a namespace
        foreach($dom->getElementsByTagName('math') as $node) {
            $node->setAttribute('xmlns','http://www.w3.org/1998/Math/MathML');
        }

        # citations
        $entries = $xpath->query("//tei:p[contains(@rend,'citation') or contains(@rend,'quotation')]");
        foreach($entries as $entry){
            $parent  = $entry->parentNode;
//            $element = $dom->createElement("q", $entry->nodeValue);
            $element = $dom->createElement("q");
            foreach($entry->childNodes as $child){
                $element->appendChild($child->cloneNode(true));
            }

            foreach($entry->attributes as $attribute){
                $element->setAttribute($attribute->nodeName,$attribute->nodeValue);
            }
            $parent->replaceChild($element, $entry);
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
        if(array_key_exists('author', $lodelmeta))
	        foreach ($lodelmeta["author"] as $author) {
	            $respstmt = $dom->createElement('respStmt');
	            $titlestmt->appendChild($respstmt);
	            $resp = $dom->createElement('resp', "author");
	            $respstmt->appendChild($resp);
	            $name = $dom->createElement('name', $author);
	            $respstmt->appendChild($name);
	        }
	    if(array_key_exists('editor', $lodelmeta))
	        foreach ($lodelmeta["editor"] as $editor) {
	            $respstmt = $dom->createElement('respStmt');
	            $titlestmt->appendChild($respstmt);
	            $resp = $dom->createElement('resp', "editor");
	            $respstmt->appendChild($resp);
	            $name = $dom->createElement('name', $editor);
	            $respstmt->appendChild($name);
	        }
	    if(array_key_exists('translator', $lodelmeta))
	        foreach ($lodelmeta["translator"] as $translator) {
	            $respstmt = $dom->createElement('respStmt');
	            $titlestmt->appendChild($respstmt);
	            $resp = $dom->createElement('resp', "translator");
	            $respstmt->appendChild($resp);
	            $name = $dom->createElement('name', $translator);
	            $respstmt->appendChild($name);
	        }
            if(array_key_exists('excavationsdirector', $lodelmeta)){
                foreach ($lodelmeta["excavationsdirector"] as $excavationsdirector) {
                    $respstmt = $dom->createElement('respStmt');
                    $titlestmt->appendChild($respstmt);
                    $resp = $dom->createElement('resp', "excavationsdirector");
                    $respstmt->appendChild($resp);
                    $name = $dom->createElement('name', $excavationsdirector);
                    $respstmt->appendChild($name);
                }
            }
            if(array_key_exists('collaborator', $lodelmeta)){
                foreach($lodelmeta["collaborator"] as $collaborator) {
                    $respstmt = $dom->createElement('respStmt');
                    $titlestmt->appendChild($respstmt);
                    $resp = $dom->createElement('resp', "collaborator");
                    $respstmt->appendChild($resp);
                    $name = $dom->createElement('name', $collaborator);
                    $respstmt->appendChild($name);
                }
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
            $newnode = $dom->createElement('note');

            foreach($entry->childNodes as $child){
                $newnode->appendChild($child->cloneNode(true));
            }

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
            $newnode = $dom->createElement('language', mb_strtolower($entry->nodeValue)); // Le code de langue doit être en minuscule
            $newnode->setAttribute('ident', mb_strtolower($entry->nodeValue));
            if ( $id=$entry->getAttribute('xml:id')) { $newnode->setAttribute('xml:id', $id); }
            $langUsage->appendChild($newnode);
            $parent->removeChild($entry);
        }
        else {
            // TODO : warning no lang defined
            error_log("<li>? [Warning] no lang defined</li>\n");
        }

        # /tei/teiHeader/profileDesc/textClass
        $textclass = $xpath->query("//tei:textClass")->item(0);
        foreach($this->_keywords as $keyword => $attributes){
            foreach($attributes['styles'] as $style){
                $entries = $xpath->query("//tei:p[@rend='{$style}']");
                foreach($entries as $entry){
                    $keyword_node = $dom->createElement('keywords');
                    $keyword_node->setAttribute('scheme', $attributes['scheme']);

                    if(isset($attributes['lang']))
                        $keyword_node->setAttribute('xml:lang', $attributes['lang']);

                    $keyword_node->setAttribute('xml:id', $entry->getAttribute('xml:id'));
                    $textclass->appendChild($keyword_node);
                    
                    $keyword_list = $dom->createElement('list');
                    $keyword_node->appendChild($keyword_list);

                    foreach(explode(',', $entry->nodeValue) as $word)
                    {
                        $word_node = $dom->createElement('item', trim($word));
                        $keyword_list->appendChild($word_node);
                    }
                    $entry->parentNode->removeChild($entry);
                }
            }
        }
        # Personcited
        $entries = $xpath->query("//tei:p[@rend='personcited']");
        $keyword_node = $dom->createElement('keywords');
        $keyword_node->setAttribute('scheme', 'personcited');
        $keyword_list = $dom->createElement('list');
        foreach($entries as $entry) {
            foreach(explode(',',$entry->nodeValue) as $word) {
                $word_node = $dom->createElement('item');
                $name = $dom->createElement('name', trim($word));
                $word_node->appendChild($name);
                $keyword_list->appendChild($word_node);
            }
            $entry->parentNode->removeChild($entry);
        }
        $keyword_node->appendChild($keyword_list);
        $textclass->appendChild($keyword_node);

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
            if ($item->hasAttribute('rendition')) {
              $clone->setAttribute('rendition', $item->getAttribute("rendition"));
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
       # /tei/text/front/ack
        $entries = $xpath->query("//tei:p[@rend='acknowledgments']");
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
            if ($item->hasAttribute('rendition')) {
              $clone->setAttribute('rendition', $item->getAttribute("rendition"));
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
            if ($item->hasAttribute('rendition')) {
              $clone->setAttribute('rendition', $item->getAttribute("rendition"));
            }
            $div->appendChild($clone);
            $front->appendChild($div);
            $parent->removeChild($item);
        }

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
        $entries = $xpath->query("//tei:back");
        $back = $entries->item(0);
        # Bibliography
        $entries = $xpath->query("//tei:div[@rend='LodelBibliography']");
        if ($entries->length) {
            // Les bibliographies vont être dans un élément <div type="bibliography"></div>
            // On crée cet élément, vide, qu'on va coller dans le document à la suite de l'ancienne biblio.
            $bibliography = $dom->createElement("div");
            $bibliography->setAttribute('type', "bibliography");
            $back->appendChild($bibliography);

            // Pour chaque <div rend="LodelBibliography"> (l'ancienne biblio)
            foreach($entries as $entry){
                // On parcourt toutes les entrées biblio et les titres de biblio
                foreach ($entry->childNodes as $tag) {
                    // Pour chaque titre on crée un nouvel élément titre <head> qu'on colle dans la nouvelle biblio.
                    if (preg_match("/^bibliograph.+\-(.+)/", $tag->getAttribute("rend"), $matches)) {
                        if ( preg_match("/^heading(\d+)$/", $matches[1], $match)) {
                            $head = $dom->createElement("head");
                            // On remplit le nouvel élément <head>
                            $this->copyNode($tag, $head);
                            $head->setAttribute('subtype', "level".$match[1]);
                            $bibliography->appendChild($head);
                            if($tag->hasAttribute('xml:id')) {
                                $head->setAttribute('xml:id', $tag->getAttribute('xml:id'));
                            }
                            continue;
                        }
                    }

                    // Pour chaque entrée bibliographique on crée un nouvel élément <bibl> qu'on colle dans la nouvelle biblio
                    $bibl = $dom->createElement("bibl");

                    if($tag->hasAttribute('rendition')) {
                        $bibl->setAttribute('rendition', $tag->getAttribute('rendition'));
                    }

                    if($tag->hasAttribute('xml:id')) {
                        $bibl->setAttribute('xml:id', $tag->getAttribute('xml:id'));
                    }

                    if($tag->nodeName == "list") {
                        $bibl->appendChild(clone($tag));
                    }

                    // On remplit le nouvel élément <bibl>
                    else {
                        $this->copyNode($tag, $bibl);
                    }

                    // On colle le nouvel élément <bibl> dans la nouvelle biblio.
                    $bibliography->appendChild($bibl);
                }

                // Suppression de l'ancienne biblio maintenant qu'on a mis son contenu dans la nouvelle.
                $entry->parentNode->removeChild($entry);
            }

            /*
             * Structuration de la biblio dans des éléments <listBibl>
             * Les éléments <listBibl> fonctionnent comme les <div> qui structurent le corps du texte et sont imbriqués entre eux :
             *	<div type="bibliography">
             *		<listBibl>
             *			<head subtype="level1"/>
             *			<bibl/>
             *		</listBibl>
             *		<listBibl>
             *			<head subtype="level1"/>
             *			<bibl>
             *		</listBibl>
             *	</div>
             */
            // Pour chaque niveau de titre dans la nouvelle biblio, en commençant par les plus petits puis en les englobant ensuite dans les niveaux supérieurs.
            for ($i = 9 ; $i > 0 ; $i--) {
                // Pour chaque entrée (titre <head> ou référence biblio <bibl>)
                for ($j = 0, $c = $bibliography->childNodes->length ; $j < $c ; $j++) {
                    $tag = $bibliography->childNodes->item($j);

                    // Si on trouve un titre du niveau voulu à traiter
                    if ($tag->nodeName == "head"
                        && $tag->attributes->getNamedItem('subtype') != null
                        && strstr($tag->attributes->getNamedItem('subtype')->nodeValue, 'level' . $i) != false) {
                        // Dans le XPath on ne peut pas utiliser current(), alors on va marquer provisoirement le noeud courant avec un id unique
                        $uniqidAttribute = $dom->createAttribute('uniqid');
                        $uniqidAttribute->value = uniqid();
                        $tag->setAttributeNode($uniqidAttribute);

                        // Requête de tous les éléments concernés par le titre
                        $query = "following-sibling::*[not(self::head[substring(@subtype, 6) <= " . $i . "])][not(preceding-sibling::head[substring(@subtype, 6) <= " . $i . "][preceding-sibling::head[@uniqid = '" . $uniqidAttribute->value . "']])]";
                        $sameLevelNodes = $xpath->query($query, $tag);

                        // Création de l'élément <listBibl>
                        $newlistBibl = $dom->createElement('listBibl');

                        // Remplissage du <listBibl>
                        $tag->removeAttribute("uniqid");
                        $newlistBibl->appendChild($tag->cloneNode(true));
                        foreach($sameLevelNodes as $subNode) {
                            $newlistBibl->appendChild($subNode);
                        }

                        // Insertion du <listBibl> dans l'arbre source (remplace le head, préalablement cloné dans le <listBibl>)
                        $tag->parentNode->replaceChild($newlistBibl, $tag);

                        // Réduire le nombre d'itérations, du nombre d'éléments insérés dans le <listBibl>
                        $c -= $sameLevelNodes->length;
                    }
                }
            }

            /*
             * Si la bibliographie ne contient pas de titre et n'est pas structurée on a ça :
             *	<div type="bibliography">
             *		<bibl/>
             *		<bibl/>
             *	</div>
             *
             * Dans ce cas on veut quand même mettre tous les éléments <bibl> dans un élément <listBibl>
             *	<div type="bibliography">
             *		<listBibl>
             *			<bibl/>
             *			<bibl/>
             *		</listBibl>
             *	</div>
             */
            if ($bibliography->firstChild->nodeName != "listBibl") {
                // On crée l'élément <listBibl> qui va englober les <bibl>, et on le met à la suite de tous les <bibl>
                $listbibl = $dom->createElement("listBibl");
                $bibliography->appendChild($listbibl);

                // Boucle sur tous les éléments <bibl> fils de <div type="bibliography"> pour les déplacer dans <listBibl>
                // On ne boucle pas sur le dernier élément fils, qui est <listBibl> lui-même ($i < $c-1)
                for ($i = 0, $c = $bibliography->childNodes->length ; $i < $c-1 ; $i ++) {
                    $node = $bibliography->firstChild;
                    $listbibl->appendChild($node);
                }
            }
        }



        # Appendix
        $entries = $xpath->query("//tei:div[@rend='LodelAppendix']");
        if ($entries->length) {
            // Les annexes vont être dans un élément <div type="appendix"></div>
            // On crée cet élément, vide, qu'on va coller dans le document à la suite de l'ancienne annexe
            $lodel = $entries->item(0);
            $parent = $lodel->parentNode;
            $appendix = $dom->createElement("div");
            $appendix->setAttribute('type', "appendix");
            $back->appendChild($appendix);

            // Pour chaque <div type="appendix"> (l'ancienne annexe)
            foreach($entries as $entry){
                // On parcourt tous les paragraphes d'annexe et les titres d'annexe
                foreach ($entry->childNodes as $tag) {
                    // Pour chaque titre on crée un nouvel élément titre <head> qu'on colle dans la nouvelle annexe.
                    if (preg_match("/^appendix-(.+)$/", $tag->getAttribute("rend"), $matches)) {
                        if (preg_match("/^heading(\d+)$/", $matches[1], $match)) {
                            $head = $dom->createElement("head");
                            // On remplit le nouvel élément <head>
                            $this->copyNode($tag, $head);
                            $head->setAttribute('subtype', "level".$match[1]);
                            $appendix->appendChild($head);
                            if($tag->hasAttribute('xml:id')) {
                                $head->setAttribute('xml:id', $tag->getAttribute('xml:id'));
                            }
                            continue;
                        }
                    }

                    // Pour chaque paragraphe d'annexe on crée un nouvel élément <p> qu'on colle dans la nouvelle annexe
                    $p = $tag->cloneNode(true);

                    $appendix->appendChild($p);
                }

                // Suppression de l'ancienne annexe maintenant qu'on a mis son contenu dans la nouvelle.
                $parent->removeChild($entry);
            }

            // Structuration des annexes dans des <div>, comme pour le corps de texte.
            for ($i = 9 ; $i > 0 ; $i--) {
                // Pour chaque entrée (titre <head> ou paragraphe <p>)
                for ($j = 0, $c = $appendix->childNodes->length ; $j < $c ; $j++) {
                    $tag = $appendix->childNodes->item($j);

                    // Si on trouve un titre du niveau voulu à traiter
                    if ($tag->nodeName == "head"
                        && $tag->attributes->getNamedItem('subtype') != null
                        && strstr($tag->attributes->getNamedItem('subtype')->nodeValue, 'level' . $i) != false) {
                        // Dans le XPath on ne peut pas utiliser current(), alors on va marquer provisoirement le noeud courant avec un id unique
                        $uniqidAttribute = $dom->createAttribute('uniqid');
                        $uniqidAttribute->value = uniqid();
                        $tag->setAttributeNode($uniqidAttribute);

                        // Requête de tous les éléments concernés par le titre
                        $query = "following-sibling::*[not(self::head[substring(@subtype, 6) <= " . $i . "])][not(preceding-sibling::head[substring(@subtype, 6) <= " . $i . "][preceding-sibling::head[@uniqid = '" . $uniqidAttribute->value . "']])]";
                        $sameLevelNodes = $xpath->query($query, $tag);

                        // Création de l'élément <div>
                        $newDiv = $dom->createElement('div');
                        $divAttribute = $dom->createAttribute('type');
                        $divAttribute->value = "div" . $i;
                        $newDiv->setAttributeNode($divAttribute);

                        // Remplissage du <div>
                        $tag->removeAttribute("uniqid");
                        $newDiv->appendChild($tag->cloneNode(true));
                        foreach($sameLevelNodes as $subNode) {
                            $newDiv->appendChild($subNode);
                        }

                        // Insertion du <div> dans l'arbre source (remplace le head, préalablement cloné dans le <div>)
                        $tag->parentNode->replaceChild($newDiv, $tag);

                        // Réduire le nombre d'itérations, du nombre d'éléments insérés dans le <div>
                        $c -= $sameLevelNodes->length;
                    }
                }
            }
        }

        // clean Lodel sections

        $entries = $xpath->query("//tei:div[@rend='LodelMeta']");
        if ($entries->length) {
            $lodelmeta = $entries->item(0);
            if (! $lodelmeta->hasChildNodes()) {
                $parent = $lodelmeta->parentNode;
                $parent->removeChild($lodelmeta);
            } else {
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
                $this->_status = "? Warning : appendix misspelling";
                array_push($this->log['warning'], $this->_status);
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
        }

        // clean++
        //$otxml = str_replace("<pb/>", "<!-- <pb/> -->", $dom->saveXML());
        $otxml = $dom->saveXML();
        $search = array('xmlns="http://www.tei-c.org/ns/1.0"', 'xmlns:default="http://www.tei-c.org/ns/1.0"');
        $otxml = str_replace($search, '', $otxml);
        @$dom->loadXML($otxml);

        $dom->normalizeDocument();
        $this->_param['xmloutputpath'] = $this->_param['tmppath'] . DIRECTORY_SEPARATOR  .$this->_param['prefix'].".otx.tei.xml";
        $dom->save($this->_param['xmloutputpath']);
        $this->_usedfiles[] = $this->_param['xmloutputpath'];

        $dom->resolveExternals   = false;
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput       = false;

        $otxml = $dom->saveXML();
        $otxml = str_replace('<TEI>', '<TEI xmlns="http://www.tei-c.org/ns/1.0">', $otxml);
        @$dom->loadXML($otxml);
        $this->_param['xmloutputpath'] = $this->_param['tmppath'] . DIRECTORY_SEPARATOR . $this->_param['prefix'].".otx.tei.xml";
        $dom->save($this->_param['xmloutputpath']);

        $this->_param['TEI'] = $otxml;
        return true;
    }

        // création du sommaire
        // retourne la profondeur maximale des titres
        private function summary(&$dom, &$xpath) {
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

        // transforme les <ab> en <div type="divLEVEL"><head subtype="levelLEVEL">, en entourant les paragraphes qui suivent
        private function heading2div(&$dom, &$xpath, $level) {
           if ($level == 0) return;
            $entries = $xpath->query("//tei:text/tei:body/tei:ab[@rend='heading$level']", $dom);
            foreach ($entries as $item) {
                $parent = $item->parentNode;
                $div = $dom->createElement("div");
                $div->setAttribute("type", "div$level");
                $head = $dom->createElement("head");
                $head->setAttribute("subtype", "level$level");
                foreach (array('xml:id','rendition') as $attribut) {
                    if ($valeur = $item->getAttribute($attribut))
                        $head->setAttribute($attribut, $valeur);
                }
                $this->copyNode($item, $head);

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
                    $this->_status="error replaceChild";
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
        # get the mime type
        $this->getmime();
        $sourcepath = $this->_param['sourcepath'];

        $targetpath = dirname($sourcepath);

        if ( $this->_param['mime'] !== "application/vnd.oasis.opendocument.text" ) {
            $in = escapeshellarg($sourcepath);
            $out = escapeshellarg($targetpath);

            /* Création de répertoire temporaire pour le profile */
            $temp_profile = $this->_param['tmppath'] . DIRECTORY_SEPARATOR . uniqid('OTX');
            mkdir($temp_profile, 0755, true);

            $command = "{$this->_config->soffice->officepath} --norestore --headless -env:UserInstallation=file://{$temp_profile} --convert-to odt:writer8 -outdir {$out} {$in}";

            $returnvar = 0;
            $result    = '';

            $output = exec($command, $result, $returnvar);

            if ($returnvar) {
                $this->_status = "error soffice";
                error_log("$command returned " . var_export($returnvar,true));
                throw new Exception($this->_status, E_USER_ERROR);
            }else{
                $fileinfos = pathinfo($sourcepath);
                $this->_param['outputpath'] = $targetpath . DIRECTORY_SEPARATOR . $fileinfos['filename'] . ".odt" ;
            }
        }else{
            $this->_param['outputpath'] = $sourcepath;
        }
        $this->_param['odtpath'] = $this->_usedfiles[] = $this->_param['outputpath'];

        return true;
    }

    private function rmdir( $path )
    {
        $files = glob( $path . DIRECTORY_SEPARATOR . '*', GLOB_MARK );

        foreach( $files as $file ){
            if( substr( $file, -1 ) == '/' )
                $this->rmdir( $file );
        	else
        		unlink( $file );
        }

	rmdir($path);
    }

    private function getmime() {
        $sourcepath = $this->_param['sourcepath'];

        $this->_param['mime'] = mime_content_type($sourcepath);

        $this->_param['sourcepath'] = $sourcepath;
        $this->_usedfiles[]         = $this->_param['sourcepath']; 

        return true;
    }

    /** lodel-cleanup **/
    private function lodelcleanup(&$dom) {
        $patterns = array('/\s+/', '/\(/', '/\)/', '/\[/', '/\]/');

        $xpath = new DOMXPath($dom);
        $entries = $xpath->query("//@*");
        foreach ($entries as $entry) {
            switch ($entry->nodeName) {
                case 'text:citation-style-name':
                case 'text:citation-body-style-name':
                case 'style:name':
                case 'style:display-name':
                case 'style:parent-style-name':
                case 'style:next-style-name':
                case 'style:master-page-name':
                case 'text:note-class':
                case 'text:style-name':
                case 'table:style-name':
                    
                    if (! preg_match("/^[TP]\d+$/", $entry->nodeValue)) {
                        $nodevalue = _makeSortKey( preg_replace($patterns, "", $entry->nodeValue));
                        if ( isset( $this->EModel[$nodevalue]) 
                             && !$this->is_keyword($nodevalue ) ) {
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

    private function is_keyword($style)
    {
        foreach( $this->_keywords as $keyword)
        {
            foreach($keyword['styles'] as $kstyle){
                if($kstyle == $style) return true;
            }
        }
        return false;
    }

    private function lodelpictures(&$dom, &$za) {
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
                        $basename = pathinfo($attribute->nodeValue, PATHINFO_BASENAME);
                        if (! isset($this->LodelImg[$basename])) {
                            $imgindex++;
                            $this->LodelImg[$basename] = $imgindex;
                        }
                    }
                    else {

                        $this->_status = "{$attribute->nodeValue} skipped";
                        array_push($this->log['warning'], $this->_status);
                        // TODO Warning !
                    }
                }
            }
        }
        return true;
    }

    // utilise content.xml du fichier ODT
    // créer les tableaux rendition[] et automatic[], filtrer le css selon le tableau EMotx[]
    private function ooautomaticstyles(&$dom) {
        $xpath = new DOMXPath($dom);
        
        // Listes locales
        $entries = $xpath->query("//text:list-style[@style:name]");
        foreach ($entries as $item){
        	$listkey = "#" . $item->getAttribute('style:name');
        	$levelstyles = $xpath->evaluate("*[@text:level]", $item);
       		foreach($levelstyles as $style){
       			$level = $style->getAttribute('text:level');
       			$order = ($style->nodeName == "text:list-level-style-number") ? "ordered" : "unordered";
       			$this->rendition[$listkey]['levels'][$level] = $order;
       			
       			if($order == "ordered"){
       				$type  = $style->hasAttribute("style:num-format") ? $style->getAttribute('style:num-format') : "1" ;
       				$this->rendition[$listkey]['type'][$level] = $this->get_list_order_style($type);
       			}
       		}
        }

        $entries = $xpath->query("//style:style");
        foreach ($entries as $item) {
            $key = $name = $family = $parent = '';
            $properties = array();

            $attributes = $item->attributes;
            //style:family
            if ($attrname = $attributes->getNamedItem("family")) {
                $family = $attrname->nodeValue;
            }

            //style:name
            if ($attrname = $attributes->getNamedItem("name")) {
                $name = $attrname->nodeValue;
                if (false !== strpos($name, "table")) {
                    $key = "#".str_replace('.','_',$name);
                } else {
                    $key = "#".$name;
                    if ( preg_match("/^T(\d+)$/", $name, $match)) {
                        $this->Tnum = $match[1];
                    }
                }
            }

            if ($attrparent = $attributes->getNamedItem("parent-style-name")) {
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
                        case 'style:table-properties':
                        case 'style:table-cell-properties':
                            foreach ($child->attributes as $childattr) {
                                if (! (strstr($childattr->name, '-asian') or strstr($childattr->name, '-complex'))) { // black list
                                    $value = ''. "{$childattr->name}:{$childattr->value}";
                                    array_push($properties, $value);
                                } else if ($childattr->name == 'language-complex') {
                                    array_push($properties,"language:{$childattr->value}");
                                }
                            }
                            break;
                        default:
                            error_log("Non used style : {$child->nodeName}");
                            break;
                    }
                }

                list($lang, $rendition) = $this->styles2csswhitelist($properties, $name); // white list

                if ($lang == "") $lang = null;
                $this->rendition[$key]['lang'] = $lang;
                $this->rendition[$key]['rendition'] = $rendition;
                $this->rendition[$key]['family'] = $family;
            }
        }

        return true;
    }

    private function get_list_order_style( $style ){
        $styles = array(
                ""  => "none",
                "1" => "decimal",
                "i" => "lower-roman",
                "I" => "upper-roman",
                "a" => "lower-alpha",
                "A" => "upper-alpha",
        );

        if(isset($styles[$style])){
            return $styles[$style];
        }else{
            return "decimal";
        }
    }

    // utilise styles.xml du fichier ODT
    // créer les tableaux rendition[] et automatic[], filtrer le css selon le tableau EMotx[]
    private function oostyles(&$dom) {
        $xpath = new DOMXPath($dom);

        // listes
        $entries = $xpath->query("//text:list-style[@style:name]");
        foreach ($entries as $item){
        	$listkey = "#" . $item->getAttribute('style:name');
        	$levelstyles = $xpath->evaluate("*[@text:level]", $item);
       		foreach($levelstyles as $style){
       			$level = $style->getAttribute('text:level');
       			$order = ($style->nodeName == "text:list-level-style-number") ? "ordered" : "unordered";
       			$this->rendition[$listkey]['levels'][$level] = $order;
       			
       			if($order == "ordered"){
           			$type  = $style->hasAttribute("style:num-format") ? $style->getAttribute('style:num-format') : "1" ;
           			$this->rendition[$listkey]['type'][$level] = $this->get_list_order_style($type);
       			}
       		}
        }

        $entries = $xpath->query("//style:style[@style:name]");
        foreach ($entries as $item) {
            $properties = array();
            $key        = '';
            
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
                list($lang, $rendition) = $this->styles2csswhitelist($properties, $name);

                if ( isset($this->rendition[$key])) { // from automaticstyle
                    // TODO : merge ?
                    if ( array_key_exists('lang', $this->rendition[$key] ) && $this->rendition[$key]['lang']=='')
                        $this->rendition[$key]['lang'] = $lang;
                    if ( array_key_exists('rendition', $this->rendition[$key]) && $this->rendition[$key]['rendition']=='')
                        $this->rendition[$key]['rendition'] = $rendition;

                    if ( array_key_exists('family', $this->rendition[$key]) &&  $this->rendition[$key]['family']=='') {
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
    // Transforme les style ODT en CSS
    private function styles2csswhitelist(&$properties, $name=false) {
        $lang = $rendition = "";
        $csswhitelist = array();

        // default : strict mode
        foreach ($properties as $prop) {
            // xhtml:sup
            if ( preg_match("/^text-position:(-?\d+|super)%?/", $prop, $matches)) {
                if(is_int((int)$matches[1])){
                    if($matches[1] < 0)
                        $type = "sub";
                    else
                        $type = "super";
                }else{
                    $type = $matches[1];
                }

                array_push($csswhitelist, "vertical-align:$type");
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
                default:
                    break;
            }
            $type = $this->_param['type'];
            if ($type==="extended") {
                // no extended for fields that have allowedstyles set to strict
                if ($name && isset($this->EMotx[$name]['allowedstyles']) && isset($this->EMotx[$name]['allowedstyles']['strict'])) {
                    continue;
                }
                if ( preg_match("/^font-size:/", $prop)) {
                    array_push($csswhitelist, $prop);
                    continue;
                }
                if ( preg_match("/^font-name:(.*)$/", $prop, $match)) {
                    array_push($csswhitelist, "font-family:'{$match[1]}'");
                }

                if ( preg_match("/^(color|background-color)\:/", $prop)) {
                	array_push($csswhitelist, $prop);
                	continue;
                }
                
                if( preg_match("/^border.*:/", $prop) ){
                    array_push($csswhitelist, $prop);
                }
                
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
                        break;
                }
            }else{
                // table border
                if ($name && strpos($name, 'table') === 0 && preg_match("/^(border.*):((.+)\s+(solid|double)\s+(#\d+)|none)$/", $prop, $match)) {
                    if($match[2] == "none") {
                        $border = "{$match[1]}:none";
                    }else{
                        $border = "{$match[1]}:1px solid {$match[5]}";
                    }
                    array_push($csswhitelist, $border);
                    // TODO raw as cell !
                    continue;
                }
            }
        }
        $rendition = implode(";", $csswhitelist);

        // no style for fields that have allowedstyles set to none (at the end because of $lang)
        if ($name && isset($this->EMotx[$name]['allowedstyles']) && isset($this->EMotx[$name]['allowedstyles']['none'])) {
            $rendition = "";
        }

        return array($lang, $rendition);
    }

    /** array('rend'=>, 'key'=>, 'surround'=>, 'section'=>) **/
    private function greedy(&$node, &$greedy) {
        $section = $surround = $key = $rend = null;
        $greedy = null; $status = true;
        $rend = $node->getAttribute("rend");
        if ( in_array(get_class($node), array("DOMDocument","DOMElement"))) {

            if (strpos($rend, "bibliograph") !== false || strpos($rend, "appendix") !== false ) {
                $section = "back";
            }

            if ( isset($this->EMotx[$rend]['surround'])) {
                $surround = $this->EMotx[$rend]['surround'];
            }
            elseif (in_array($node->nodeName, array("ab", "list"))) { 
                $surround = "-*";
            }elseif(in_array($node->nodeName, array("table"))) {
                $surround = "*-";
            }

            if ($surround=="*-" or $surround=="-*") {
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
                        $section = "body";
                        break;
                }
            }
            elseif ($node->nodeName=="ab") {
                $section = "body"; // heading > 6 !
            }

        }
        
        if (empty($section)) {
            $section = "body"; // heading > 6 !
        }

        $greedy = array('rend'=>$rend, 'key'=>$key, 'surround'=>$surround, 'section'=>$section);
        return $status;
    }

    /** css tagsDecl to tei:hi rendition ! **/
    // fonction non appelée
    private function tagsdecl2rendition($tagdeclid, &$rendition /*$type="strict"*/) {

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
    // utilise content.xml du fichier ODT
    private function oolodel2meta(&$dom) {
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
    // utilise meta.xml du fichier ODT
    private function meta2lodelodt(&$dommeta) {
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
                    $item->nodeValue = $date;
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
        switch ($step) {
            case 'soffice':
            case 'lodel':
                $za = new ZipArchive();
                if (! $za->open($filepath)) {
                    $this->_status="error open ziparchive ($filepath)";
                    throw new Exception($this->_status,E_ERROR);
                }
                if (! $meta=$za->getFromName('meta.xml')) {
                    $this->_status="error get meta.xml";
                    throw new Exception($this->_status,E_ERROR);
                }
                $dommeta = new DOMDocument;
                $dommeta->encoding = "UTF-8";
                $dommeta->resolveExternals = false;
                $dommeta->preserveWhiteSpace = true;
                $dommeta->formatOutput = false;
                if (! $dommeta->loadXML($meta)) {
                    $this->_status="error load meta.xml";
                    throw new Exception($this->_status,E_ERROR);
                }
                $xmlmeta = str_replace('<?xml version="1.0" encoding="UTF-8"?>', "", $dommeta->saveXML());
                $za->close();
                $this->log['report'][$step] = $xmlmeta;

                # json
                $prop = array();
                $xpath = new DOMXPath($dommeta);
                $entries = $xpath->query("//office:meta/*");
                foreach ($entries as $entry) {
                    if ($entry->nodeName == "meta:document-statistic") {
                        foreach ($entry->attributes as $attr) {
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

        $mode = $this->input['mode'];
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

        $this->_param['xmlreport'] = $xmlreport;
    }


    // fonction non appelée
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

	private function copyNode($source, &$destination){
		if ($source->hasChildNodes()) {
			foreach ($source->childNodes as $child) {
				$clone = $child->cloneNode(true);
				$destination->appendChild($clone);
			}
		}else{
			$destination->nodeValue = $source->nodeValue;
		}
	}

    private function params() {
        $mode = $this->input['mode'];

        $this->_param['extension'] = pathinfo($this->input['sourceoriginale'], PATHINFO_EXTENSION);
        $this->_param['prefix'] = pathinfo($this->input['sourceoriginale'], PATHINFO_FILENAME);

        $this->_param['sourcepath'] = $this->_param['tmppath'] . DIRECTORY_SEPARATOR . $this->input['sourceoriginale'];

        if (! rename($this->input['entitypath'], $this->_param['sourcepath'])) {
            $this->_status="error: failed copy {$this->input['entitypath']} to {$this->_param['sourcepath']}";
            throw new Exception($this->_status, E_ERROR);
        }
    }


    /** pdf document to xml **/
    // fonction non appelée
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

            return date("Y-m-d", strtotime( preg_replace($patterns, $replace, $ladatepubli) ) );
        }

        private function _ootitle($title) {
            list($ootitle) = preg_split("/\*/", $title);
            return trim($ootitle);
        }

        public function cleanup() {
        	foreach($this->_usedfiles as $file){
        		@unlink($file);
        	}
		$this->rmdir($this->_param['tmppath']);
        }


    /** Hello world! **/
    protected function Hello() {
        $this->output['status'] = "HTTP/1.0 200 OK";
        $this->output['xml'] = "";
        $this->output['report'] = "Hello world!";
        $this->output['contentpath'] = '';
        $this->output['lodelxml'] = "";
		return true;
    }

    private function plugin( $name )
    {
        try
        {
            $plugin = Plugin::get($name, $this->_db, $this->_param);
            $plugin->run();
            $this->output[$name] = $plugin->output[$name];
        }
        catch(Exception $e)
        {
            error_log($e->getMessage());
        }
    }


// end of OTXserver class.
}

abstract class Plugin
{

        static private $_instances = array();
        private $_status;

        protected function __construct( $db, $param ){}

        static public function get( $plugin, $db, $param )
        {
                if(!isset(self::$_instances[$plugin]))
                {
                	$plugin_class_file = "plugins/{$plugin}/{$plugin}.class.php";
                	if(file_exists($plugin_class_file));
                        require_once $plugin_class_file;
                        self::$_instances[$plugin] = new $plugin( $db, $param );
                }

                return self::$_instances[$plugin];
        }

        abstract public function run();

}
