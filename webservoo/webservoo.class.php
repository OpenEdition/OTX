<?php
/**
 * Class WebServOO : OTX WebService SoapServer Class
 * PHP >= 5.2
 * @author Nicolas Barts
 * @author Pierre-Alain Mignot
 * @copyright 2008-2009, CLEO/Revues.org
 * @licence http://www.gnu.org/copyleft/gpl.html
**/

error_reporting(E_ALL | E_STRICT);
if (! class_exists('Servel', FALSE))
    require_once('servel/servel.class.php');


/**
 * WebServoo SoapServer Class
**/ 
class WebServoo
{
    public $status = "";    // return status
    public $xml = "";       // return xml (TEI)
    public $report = "";    // return report
    public $odt = null;     // return odt document
    public $lodelxml = "";  // return lodel xml

//    protected $singleton;
    protected $mode;
    // authed user informations
    private $_user;
    // are we logged in ?
    private $_isLogged;
    // session token
    private $_sessionToken;

    const __ATTACHMENTPATH__    = __WEBSERVOO_ATTACHMENT__;
    const __SCHEMAPATH__        = __WEBSERVOO_SCHEMA__;
    const __LOGFILE__           = __WEBSERVOO_LOG__;


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
    public final function webservooToken()
    {
        error_log(date("Y-m-d H:i:s")." Token()\n", 3, self::__LOGFILE__);
	$this->_sessionToken = md5(uniqid(mt_rand(),true));

        //return new SoapVar( array('sessionToken' => $this->_sessionToken), SOAP_ENC_OBJECT);
	return array('sessionToken' => $this->_sessionToken);
    }

    /**
      * @access public
      * @param string $input->login the login
      * @param string $input->password the hash
      * @param string $input->lodel_user name of the lodel user
      * @param string $input->lodel_site site of the lodel user
      * @return webservooAuthResponse()
    **/
    public final function webservooAuth($input) 
    {
        error_log(date("Y-m-d H:i:s")." {$input->login} ; {$input->password} ; {$input->lodel_user} ; {$input->lodel_site} ?\n", 3, self::__LOGFILE__);
# TODO !!!
/*
	if ($this->_isLogged) {
            // simple check
            return ($input->password === $this->_user['passwd'] ? $this->webservooAuthReponse(true) : $this->webservooAuthResponse(false));
	}

	if (is_null($this->_db)) {
	   $this->_db = new MySQLi('localhost', 'servoo', 'servoo', 'servoo');
	   if($this->_db->connect_error)
	       return $this->webservooAuthResponse(false);
	}

	if ($stmt = $this->_db->Prepare('SELECT id, passwd FROM users WHERE username=? AND status>0 LIMIT 1')) {
           $user = array();
	   $stmt->bind_param('s', $input->login);
	   $stmt->bind_result($id, $passwd);

           if (!$stmt->execute())
	       return $this->webservooAuthResponse(false);
	   $stmt->fetch();
	}
	else 
            return $this->webservooAuthResponse(false);

	if (!$id) 
            return $this->webservooAuthResponse(false);

	if (($this->_passwd = md5($passwd.$this->_sessionToken)) !== $input->password) 
            return $this->webservooAuthResponse(false);
*/	
        $this->_user['login'] = $input->login;
	$this->_user['id'] = $id;
	$this->_user['lodel_user'] = $input->lodel_user;
	$this->_user['lodel_site'] = $input->lodel_site;
	unset($input, $passwd, $id);
	$this->_isLogged = true;

	return $this->webservooAuthResponse(true);
    }

    /**
      * @access public
      * @param boolean $result
      * @return SoapVar array('AuthStatus'=>(true|false))
    **/
    public final function webservooAuthResponse($result) 
    {
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
      * @return see webservooResponse()
    **/
    public final function webservooRequest($input)
    {
//        $Servel = null;

	if (!$this->_isLogged) {
            throw new SoapFault("WebServOO FaultError", //faultcode
                                'You need to be logged in to access this service.', //faultstring
                                '', // faultactor, TODO ?
                                "Soap authentification",  // detail
                                "UTF-8" // faultname
                                /*$headerfault // headerfault */ );
	}

        $is_locked = false;
        do {
            $is_locked = file_exists("/tmp/otx.lock"); 
            error_log(date("Y-m-d H:i:s")." waiting...\n", 3, self::__LOGFILE__);
            sleep(1);
        } while ($is_locked);

        $this->mode = $input->mode;
        error_log(date("Y-m-d H:i:s")." {$this->mode}\n", 3, self::__LOGFILE__);

        // XML schema (lodel EM)
        if ($input->schema != '') {
            @unlink(self::__SCHEMAPATH__); 
            if (! file_put_contents(self::__SCHEMAPATH__, $input->schema)) {
                throw new SoapFault("WebServOO FaultError",
                                    "file_put_contents(schema)",
                                    $this->_user,
                                    self::__SCHEMAPATH__,
                                    "UTF-8"
                                    /*$headerfault // headerfault */ );
            }
        } //@chmod(self::__SCHEMAPATH__, 0660);@chgrp(self::__SCHEMAPATH__, "www-data");
        // source document (entity lodel)
        if ($input->attachment != '') {
            @unlink(self::__ATTACHMENTPATH__); 
            if (! file_put_contents(self::__ATTACHMENTPATH__, $input->attachment)) {
                throw new SoapFault("WebServOO FaultError",
                                    "file_put_contents(attachment)",
                                    $this->_user,
                                    self::__ATTACHMENTPATH__,
                                    "UTF-8"
                                    /*$headerfault // headerfault */ );
            }
        } //@chmod(self::__ATTACHMENTPATH__, 0660);@chgrp(self::__ATTACHMENTPATH__, "www-data");


        // singleton pattern
        try {
            $Servel = Servel::singleton($input->request, $input->mode, self::__SCHEMAPATH__, self::__ATTACHMENTPATH__);
        } 
        catch(Exception $e) {
            throw new SoapFault("singleton()Error",
                                $e->getMessage(),
                                //$this->_user,
                                //$e->getMessage(),
                                "UTF-8"
                                /*$headerfault // headerfault */ );

        }

        // do it !
        try {
            $return = $Servel->run();
        }
        catch(Exception $e) {
            throw new SoapFault("WebServOO run()Error",
                                $e->getMessage(),
                                "",
                                "",
                                "UTF-8"
                                /*$headerfault // headerfault */ );
        }

        $this->status = $return['status'];
        $this->xml = $return['xml'];
        $this->report = $return['report'];

        if ($this->mode === "soffice" or $this->mode === "lodel") {
            error_log(date("Y-m-d H:i:s")." contentpath = {$return['contentpath']}\n", 3, self::__LOGFILE__);
            if (! $this->odt = file_get_contents($return['contentpath'])) {
                throw new SoapFault("WebServOO file_get_contents()Error",
                                    $return['contentpath'],
                                    "",
                                    "",
                                    "UTF-8"
                                    /*$headerfault // headerfault */ );
            }
        }

        if ($this->mode === "lodel") {
            $this->lodelxml = $return['lodelxml'];
        }

        return $this->webservooResponse();
    }


    /**
     * @access public
     * @return SoapVar array [ $xml (tei contents) and $status (cached or not) and $report (checkbalisage) ]
    **/
    public final function webservooResponse()
    {
        error_log(date("Y-m-d H:i:s")." status: {$this->status}\n", 3, self::__LOGFILE__);

	return array(  'status'     => $this->status,
                       'xml'        => $this->xml,
                       'report'     => $this->report,
                       'odt'        => $this->odt,
                       'lodelxml'   => $this->lodelxml 
                    );
    }


// End of WebServoo SoapServer Class
}


?>