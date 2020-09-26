<?php

namespace jay94ks\kakao\user;
use jay94ks\kakao\Kakao;
use jay94ks\kakao\base\Curl;
use jay94ks\kakao\base\Base;
use jay94ks\kakao\user\mgmt\User as MgmtUser;
use jay94ks\kakao\InvalidStateException;

class User extends Base {
    private $_Token;
    private $_Info;
    private $_Profile;
    
    function __construct(Kakao $Kakao, Token $Token) {
        parent::__construct($Kakao);
        
        $this->_Token = $Token;
        $this->_Info = null;
        
        if (!is_array($Token->getInfo())) {
            throw new InvalidStateException(
                "Invalid Token can't instantiate User!");
        }
    }
    
    /**
     * Get user token.
     */
    function getToken() : Token {
        return $this->_Token;
    }

    /**
     * Determines this user is valid or not.
     */
    function getInfo() {
        if (!$this->_Info) {
            $Result = Curl::post(Api::USER_URL . Api::USER_PATH_GETINFO)
                ->setHeader('Authorization', $this->_Token->getAccessToken())
                ->exec();
            
            if (!$Result->succeed) {
                return false;
            }
            
            $this->_Info = json_decode($Result->content, true);
        }
        
        return $this->_Info;
    }
    
    /**
     * Get user's management id of Kakao.
     */
    function getId() {
        if (!is_array($Info = $this->getInfo())) {
            return null;
        }
        
        return $Info['id'];
    }
    
    /**
     * Get profile of kakao account.
     */
    function getProfile() : ?Profile {
        if (!$this->_Profile) {
            if (!is_array($this->getInfo())) {
                return null;
            }
            
            $this->_Profile = new Profile($this->getKakao(), $this);
        }
        
        return $this->_Profile;
    }
    
    /**
     * Get attribute keys of Kakao Account Information.
     */
    function getInfoAttributeKeys() : array {
        if (!is_array($Info = $this->getInfo())) {
            return [];
        }
        
        return array_keys($Info['kakao_account']);
    }
    
    /**
     * Get attribute of Kakao Account Information.
     */
    function getInfoAttribute($Key, $Default = null) {
        if (!is_array($Info = $this->getInfo())) {
            return null;
        }
        
        if (array_key_exists($Key, $Info['kakao_account'])) {
            return $Info['kakao_account'][$Key];
        }
        
        return $Default;
    }
    
    /**
     * Get property of user.
     * Note: this method will return null if invalid.
     */
    function getProperty($Key, $Default = null) {
        if (!is_array($Info = $this->getInfo())) {
            return null;
        }
        
        if (array_key_exists($Key, $Info['properties'])) {
            return $Info['properties'][$Key];
        }
        
        return $Default;
    }
    
    /** 
     * Set property of user.
     * Note: this method will return true or false for valid,
     *  returns null for invalid.
     */
    function setProperty($Key, $Value, $Enforced = false) {
        if (!is_array($Info = $this->getInfo())) {
            return null;
        }
        
        if (array_key_exists($Key, $Info['properties'])) {
            /**
             * If local cache has same-value,
             * This acts nothing.
             */
            if (!$Enforced && $Info['properties'][$Key] == $Value) {
                return true;
            }
        }
        
        $AccessToken = $this->_Token->getAccessToken();
        $Result = Curl::post(Api::USER_V1_URL . Api::USER_PATH_UPDATEPROP)
            ->setHeader('Authorization', "bearer {$AccessToken}")
            ->setData([
                "properties" => json_encode([
                    $Key => $Value
                ])
            ])
            ->exec();
            
        if ($Result->succeed) {
            $this->_Info['properties'][$Key] = $Value;
            return true;
        }
        
        return false;
    }
    
    /** 
     * Set properties of user.
     * Note: this method will return true or false for valid,
     *  returns null for invalid.
     */
    function setProperties($KeyValues, $Enforced = false) {
        if (!is_array($Info = $this->getInfo())) {
            return null;
        }
        
        $Counter = 0;
        $ExpectedCount = count(array_keys($KeyValues));
        
        foreach ($KeyValues as $Key => $Value) {
            if (array_key_exists($Key, $Info['properties'])) {
                /**
                 * If local cache has same-value,
                 * This acts nothing.
                 */
                if (!$Enforced && $Info['properties'][$Key] == $Value) {
                    ++$Counter;
                }
            } 
        }
        
        if ($ExpectedCount <= $Counter) {
            return true;
        }
        
        $AccessToken = $this->_Token->getAccessToken();
        $Result = Curl::post(Api::USER_V1_URL . Api::USER_PATH_UPDATEPROP)
            ->setHeader('Authorization', "bearer {$AccessToken}")
            ->setData([
                "properties" => json_encode($KeyValues)
            ])
            ->exec();
            
        if ($Result->succeed) {
            foreach ($KeyValues as $Key => $Value) {
                $this->_Info['properties'][$Key] = $Value;
            }
            
            return true;
        }
        
        return false;
    }
}
