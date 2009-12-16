<?php
error_reporting(E_ALL | E_STRICT);
include_once('../servoo2.inc.php');
/*
ini_set('soap.wsdl_cache_enabled', TRUE);
ini_set('soap.wsdl_cache_enabled', '1');
ini_set('oap.wsdl_cache_ttl', '360');
*/
    /* UNCOMMENT for barts devel testing */
    ini_set('soap.wsdl_cache_enabled', FALSE);
    ini_set('soap.wsdl_cache_enabled', '0');

function webclient($input, &$status) {

    $location = $input['servoo.url'];
    $wsdl = $input['servoo.url']."?wsdl"; 

    $options = array();
    $options['trace'] = true;
    $options['location'] = $location;
    $options['soap_version'] = SOAP_1_2;
    $options['exceptions'] = true;
    $options['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 5;
/*
    $options['encoding'] = SOAP_LITERAL;
    print_r($options);
    $options['login'] = $input['servoo.username'];
    $options['password'] = $input['servoo.passwd'];
*/
    try {
        $WebServooClient = new SoapClient($wsdl, $options);
    }
    catch (SoapFault $fault) {
        echo "<ul>";
        echo "<b>SoapClient</b>";
        echo "<li>faultcode: {$fault->faultcode}</li>";
        echo "<li>faultstring: {$fault->faultstring}</li>";
        echo "</ul>";
        die();
    }

    $passwd = md5($input['servoo.username'].$input['servoo.passwd']);
    $input['servoo.passwd'] = $passwd;

    try {
        // get the token for this session
        $sessionToken = $WebServooClient->webservooToken();
    }
    catch (SoapFault $fault) {
        echo "<ul>";
        echo "<b>webservooToken</b>";
        echo "<li>faultcode: {$fault->faultcode}</li>";
        echo "<li>faultstring: {$fault->faultstring}</li>";
        echo "</ul>";
        die();
    }

    try {

        // add the header for auth
        $header = new SoapVar( array(   'login'         => $input['servoo.username'],
                                        'password'      => md5($passwd.$sessionToken->sessionToken),
                                        'lodel_user'    => $input['lodel_user'],
                                        'lodel_site'    => $input['lodel_site']),
                                SOAP_ENC_OBJECT);
//            unset($options, $passwd, $sessionToken); // cleaning memory

        $WebServooClient->__setSoapHeaders( array( new SoapHeader('urn:webservoo', 'webservooAuth', $header)));
//            unset($header, $webservooHeader); // cleaning memory

        // make the request and get tei result
        $output = $WebServooClient->webservooRequest( array(    'request'       => $input['request'],
                                                                'mode'          => $input['mode'],
                                                                'attachment'    => $input['attachment'],
                                                                'schema'        => $input['schema'])
                                                    );

    }
    catch (SoapFault $fault) {
        $status = "\n<WebServoo type=\"SoapFault\">";
        $status .= "\n<faultcode><![CDATA[" .$fault->faultcode ."]]></faultcode>";
        $status .= "\n<faultstring><![CDATA[" .$fault->faultstring ."]]></faultstring>";
        /*
        echo "<lastrequest><![CDATA[" .$WebServooClient->__getLastRequest() ."]]></lastrequest>\n";
        echo "<lastresponse><![CDATA[" .$WebServooClient->__getLastResponse() ."]]></lastresponse>\n";
        //echo "<vardump><![CDATA["; var_dump($fault); echo "]]></vardump>";
        $status .=  "\n<error>" .$output->status ."</error>";
        */
        $status .=  "\n</WebServoo>";
        return FALSE;
    }

    $status = $output->status;
/*
    if ($Return->status==="from cache" OR $Return->status==="to cache") {
        $status = $Return->status;
        return $Return->tei;
    }
*/
    return $output;
}

?>