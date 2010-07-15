<?php
/**
 * Class OTXSoapServer : OTX WebService SoapServer Class
 * PHP >= 5.2
 * @author Nicolas Barts
 * @author Pierre-Alain Mignot
 * @copyright 2010, CLEO/Revues.org
 * @licence http://www.gnu.org/copyleft/gpl.html
**/
if (! class_exists('OTXserver', FALSE))
    require_once('server/otxserver.class.php');


/**
 * OTX SoapServer Class
**/ 
class OTXSoapServer
{
    public $status = "";    // return status
    public $xml = "";       // return xml (TEI)
    public $report = "";    // return report
    public $odt = null;     // return odt document
    public $lodelxml = "";  // return lodel xml

    protected $mode;
    // authed user informations
    private $_user;
    // are we logged in ?
    private $_isLogged;
    // session token
    private $_sessionToken;

    private $Server = null;

    const __ATTACHMENTPATH__    = __SOAP_ATTACHMENT__;
    const __SCHEMAPATH__        = __SOAP_SCHEMA__;
    const __LOGFILE__           = __SOAP_LOG__;


    public function __construct() {
    error_log(date("Y-m-d H:i:s")." WebServoo SoapServer\n", 3, self::__LOGFILE__);
        $this->_isLogged = false;
        $this->_user = null;
    }
    public function __destruct() {
    error_log("---\n", 3, self::__LOGFILE__);
    }
    public function __toString() {
        return $this->status;
    }


    /**
     * @access public
     * @return SoapVar $sessionToken : the token for the hash of the password
    **/
    public final function otxToken()
    {
        error_log(date("Y-m-d H:i:s")." Token()\n", 3, self::__LOGFILE__);
        if (defined('__DEBUG__')) error_log(date("Y-m-d H:i:s")." Token()\n",3,__DEBUG__);
	$this->_sessionToken = md5(uniqid(mt_rand(),true));

	return array('sessionToken' => $this->_sessionToken);
    }

    /**
      * @access public
      * @param string $input->login the login
      * @param string $input->password the hash
      * @param string $input->lodel_user name of the lodel user
      * @param string $input->lodel_site site of the lodel user
      * @return otxAuthResponse()
    **/
    public final function otxAuth($input)
    {
        error_log(date("Y-m-d H:i:s")." {$input->login} ; {$input->password} ; {$input->lodel_user} ; {$input->lodel_site} ?\n", 3, self::__LOGFILE__);
        if (defined('__DEBUG__')) error_log(date("Y-m-d H:i:s")." Auth()\n",3,__DEBUG__);
/*
	if ($this->_isLogged) {
            // simple check
            return ($input->password === $this->_user['passwd'] ? $this->otxAuthReponse(true) : $this->otxAuthResponse(false));
	}

	if (is_null($this->_db)) {
	   $this->_db = new MySQLi('localhost', 'servoo', 'servoo', 'servoo');
	   if($this->_db->connect_error)
	       return $this->otxAuthResponse(false);
	}

	if ($stmt = $this->_db->Prepare('SELECT id, passwd FROM users WHERE username=? AND status>0 LIMIT 1')) {
           $user = array();
	   $stmt->bind_param('s', $input->login);
	   $stmt->bind_result($id, $passwd);

           if (!$stmt->execute())
	       return $this->otxAuthResponse(false);
	   $stmt->fetch();
	}
	else {
            return $this->otxAuthResponse(false);
        }

	if (!$id) {
            return $this->otxAuthResponse(false);
        }

	if (($this->_passwd = md5($passwd.$this->_sessionToken)) !== $input->password) {
            return $this->otxAuthResponse(false);
        }
*/
        $this->_user['login'] = $input->login;
	$this->_user['id'] = $id;
	$this->_user['lodel_user'] = $input->lodel_user;
	$this->_user['lodel_site'] = $input->lodel_site;
	unset($input, $passwd, $id);
	$this->_isLogged = true;

	return $this->otxAuthResponse(true);
    }

    /**
      * @access public
      * @param boolean $result
      * @return SoapVar array('AuthStatus'=>(true|false))
    **/
    public final function otxAuthResponse($result) 
    {
        if (defined('__DEBUG__')) error_log(date("Y-m-d H:i:s")." AuthResponse()\n",3,__DEBUG__);

	if (!$result) {
            error_log(date("Y-m-d H:i:s")." authentication FALSE\n", 3, self::__LOGFILE__);
            // reset auth informations
            $this->_user = null;
            $this->_isLogged = false;
	}
        else {
            error_log(date("Y-m-d H:i:s")." authentication TRUE (id={$this->_user['id']})\n", 3, self::__LOGFILE__);
        }

	return new SoapVar( array('AuthStatus'=>$result), SOAP_ENC_OBJECT);
    }

    /**
      * @access public
      * @param string $input->request
      * @return see otxResponse()
    **/
    public final function otxRequest($input)
    {
        if (defined('__DEBUG__')) error_log(date("Y-m-d H:i:s")." otxRequest()\n",3,__DEBUG__);
        // $server = null;

	if (!$this->_isLogged) {
            throw new SoapFault('E_USER_ERROR', //faultcode
                                'You need to be logged in to access this service.', //faultstring
                                'OTXSoapServer', // faultactor, TODO ?
                                "Soap authentification",  // detail
                                "UTF-8" // faultname
                                /*$headerfault // headerfault */ );
	}

        $this->mode = $input->mode;
        error_log(date("Y-m-d H:i:s")." {$this->mode}\n", 3, self::__LOGFILE__);

        // XML schema (lodel EM)
        if ($input->schema != '') {
            @unlink(self::__SCHEMAPATH__); 
            if (! file_put_contents(self::__SCHEMAPATH__, $input->schema)) {
                throw new SoapFault('E_ERROR',
                                    "file_put_contents(schema)",
                                    'OTXSoapServer',
                                    self::__SCHEMAPATH__,
                                    "UTF-8"
                                    /*$headerfault // headerfault */ );
            }
        }
        // source document (entity lodel)
        if ($input->attachment != '') {
            @unlink(self::__ATTACHMENTPATH__); 
            if (! file_put_contents(self::__ATTACHMENTPATH__, $input->attachment)) {
                throw new SoapFault('E_ERROR',
                                    "file_put_contents(attachment)",
                                    'OTXSoapServer',
                                    self::__ATTACHMENTPATH__,
                                    "UTF-8"
                                    /*$headerfault // headerfault */ );
            }
        }

        // singleton pattern
        try {
            $this->Server = OTXserver::singleton($input->request, $input->mode, self::__SCHEMAPATH__, self::__ATTACHMENTPATH__);
        } 
        catch(Exception $e) {
            throw new SoapFault('E_ERROR',
                                $e->getMessage(),
                                'OTXSoapServer',
                                'singleton()',
                                "UTF-8"
                                /*$headerfault // headerfault */ );

        }

        // do it !
        try {
            $return = $this->Server->run();
        }
        catch(Exception $e) {
            throw new SoapFault($e->getCode(),
                                $e->getMessage(),
                                'OTXSoapServer',
                                'run()',
                                "UTF-8"
                                /*$headerfault // headerfault */ );
        }

        $this->status = $return['status'];
        $this->xml = $return['xml'];
        $this->report = $return['report'];

        if ( preg_match("/^soffice/", $this->mode) or preg_match("/^lodel/", $this->mode) ) {
            error_log(date("Y-m-d H:i:s")." contentpath = {$return['contentpath']}\n", 3, self::__LOGFILE__);
            if (! $this->odt = file_get_contents($return['contentpath'])) {
                throw new SoapFault('E_ERROR',
                                    'file_get_contents()Error',
                                    $return['contentpath'],
                                    'OTXSoaServer',
                                    "UTF-8"
                                    /*$headerfault // headerfault */ );
            }
        }

        if (preg_match("/^lodel/", $this->mode)) {
            $this->lodelxml = $return['lodelxml'];
        }

        return $this->otxResponse();
    }

    /**
     * @access public
     * @return SoapVar array [ $xml (tei contents) and $status (cached or not) and $report (checkbalisage) ]
    **/
    public final function otxResponse()
    {
        error_log(date("Y-m-d H:i:s")." status: {$this->status}\n", 3, self::__LOGFILE__);
        if (defined('__DEBUG__')) error_log(date("Y-m-d H:i:s")." Response()\n",3,__DEBUG__);

        if ( defined('__DUMP__')) { // debug/dump
            ob_start();
            var_dump($this->Server);
            file_put_contents(__DUMP__, ob_get_contents());
            ob_end_clean();
        }

	return array(  'status'     => $this->status,
                       'xml'        => $this->xml,
                       'report'     => $this->report,
                       'odt'        => $this->odt,
                       'lodelxml'   => $this->lodelxml,
                    );
    }

// End of OTX SoapServer Class
}

#EOF