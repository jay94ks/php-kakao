<?php

namespace jay94ks\kakao\base;
use jay94ks\kakao\InvalidStateException;

/**
 * Description of Curl
 *
 * @author jay94ks
 */
class Curl {
    private $_Curl;
    private $_Method;
    private $_Exec;
    private $_Headers;
    private $_Data;
    
    public $succeed;
    public $status;
    public $content;

    const DATA_JSON_ENCODED = "application/json";
    const DATA_FORM_URLENCODED = "application/x-www-form-urlencoded";

    /**
     * Create a new POST request for Url.
     * This returns Curl instance.
     */
    static function post($Url) : Curl {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $Url);
        curl_setopt($ch, CURLOPT_POST, true);
        
        return new Curl($ch, 'POST');
    }
    
    /**
     * Create a new POST request for Url.
     * This returns Curl instance.
     */
    static function get($Url) : Curl {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $Url);        
        return new Curl($ch, 'GET');
    }
    
    /**
     * Initialize a new Curl Request builder.
     */
    private function __construct($Curl, $Method) {
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);
        
        $this->_Curl = $Curl;
        $this->_Method = $Method;
        $this->_Exec = false;
        $this->_Headers = [];
        $this->_Data = null;
    }
    
    /**
     * Set request header with its value.
     * @param string $Key Header-Name as a key.
     * @param string $Value Value of header.
     */
    function setHeader($Key, $Value) : Curl {
        if ($this->_Exec) {
            throw new InvalidStateException(
                "This CURL request instance has been sent!");
        }
        
        $this->_Headers[$Key] = $Value;
        return $this;
    }
    
    /**
     * Set request data with its type.
     * Default type is Curl::DATA_FORM_URLENCODED.
     * 
     * @param array|string $Data Data to send as content body.
     * @param string $DataType Content encoding.
     */
    function setData($Data, $DataType = Curl::DATA_FORM_URLENCODED) : Curl {
        if ($this->_Exec) {
            throw new InvalidStateException(
                "This CURL request instance has been sent!");
        }
        
        if (is_array($Data)) {
            if ($DataType == Curl::DATA_FORM_URLENCODED) {
                $Data = http_build_query($Data, null, '&');
            } else if ($DataType == Curl::DATA_JSON_ENCODED) {
                $Data = json_encode($Data, JSON_UNESCAPED_SLASHES);
            } else {
                throw new \InvalidArgumentException("{$DataType} isn't supported!");
            }
            
            $this->_Headers['Content-Type'] = $DataType;
        }
        
        $this->_Data = $Data;
        return $this;
    }
    
    /**
     * Execute a built CURL request.
     * This method fill succeed, status, content fields.
     */
    function exec() : Curl {
        if ($this->_Exec) {
            throw new InvalidStateException(
                "This CURL request instance has been sent!");
        }
        
        $this->_Exec = true;
        if ($this->_Data) {
            curl_setopt($this->_Curl, CURLOPT_POSTFIELDS, $this->_Data);
        }
        
        if (count($this->_Headers)) {
            $Headers = [];

            foreach ($this->_Headers as $key => $value) {
                $Headers[] = "{$key}: {$value}";
            }
            
            curl_setopt($this->_Curl, CURLOPT_HTTPHEADER, $Headers);
        }
        
        $this->content = curl_exec($this->_Curl);
        $this->status = curl_getinfo($this->_Curl, CURLINFO_HTTP_CODE);
        $this->succeed = ($this->status >= 200 && $this->status < 300);
        return $this;
    }
}
