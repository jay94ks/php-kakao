<?php

namespace jay94ks\kakao;

/**
 * Kakao object.
 *
 * @author jay94ks
 */
class Kakao {
    private $_AppKey, $_AdminKey;
    private $_ClientSecretKey;
    private $_Auth;
        
    /**
     * Initialize a new Kakao object 
     * using Application Key and Administrator Key (Optional)
     */
    function __construct(... $Args) {
        //AppKey $AppKey, ?AdminKey $AdminKey = null
        while (count ($Args)) {
            $Each = array_shift($Args);
            
            if ($Each instanceof AppKey) {
                $this->_AppKey = $Each;
            }
            
            else if ($Each instanceof AdminKey) {
                $this->_AdminKey = $Each;
            }
            
            else if ($Each instanceof ClientSecretKey) {
                $this->_ClientSecretKey = $Each;
            }
        }
        
        if (!$this->_AppKey) {
            throw new \InvalidArgumentException(
                "Kakao object requires Application Key!");
        }
    }
    
    /**
     * Get application key which specified from constructor.
     * @return AppKey Application Key for REST API.
     */
    function getAppKey() : AppKey {
        return $this->_AppKey;
    }
    
    /**
     * Get administrator key which specified from constructor.
     * If never set, this returns nothing.
     * @return AdminKey|null Administrator Key.
     */
    function getAdminKey() : ?AdminKey {
        return $this->_AdminKey;
    }
    
    /**
     * Get client secret key which specified from constructor.
     * If never set, this returns nothing.
     * @return ClientSecretKey|null Client Secret Key.
     */
    function getClientSecretKey() : ?ClientSecretKey {
        return $this->_ClientSecretKey;
    }
    
    /**
     * Get authentication agent.
     */
    function auth() : auth\Auth {
        if (!$this->_Auth) {
            $this->_Auth = new auth\Auth($this);
        }
        
        return $this->_Auth;
    }
}
