<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace jay94ks\kakao\user;
use jay94ks\kakao\Kakao;
use jay94ks\kakao\base\Curl;
use jay94ks\kakao\base\Base;
use jay94ks\kakao\InvalidStateException;

/**
 * User token from Authentication module.
 *
 * @author jay94
 */
class Token extends Base {
    private $_Data;
    private $_User;
    private $_Validity;
    private $_Info;
    
    /**
     * Initialize a new authorization token
     * using Kakao instance and its data.
     */
    function __construct(Kakao $Kakao, array $Data) {
        parent::__construct($Kakao);
        $this->_Data = $Data;
        
        if (isset($this->_Data['scope']) &&
            is_string($this->_Data['scope'])) 
        {
            $this->_Data['scope'] 
                = explode(' ', $this->_Data['scope']);
        }
        
        $this->_Info = null;
        $this->_Validity = 'UNKNOWN';
        
        if (isset($Data['no-revalidate'])) {
            $this->_Validity = 'VALID';
        }
        
        if (!isset($Data['access_token'])) {
            $this->_Validity = 'INVALID';
        }
    }
    
    /**
     * Load and create a token object.
     */
    static function loadFrom(Kakao $Kakao, array $Data) {
        if (isset ($Data['no-revalidate'])) {
            unset($Data['no-revalidate']);
        }
        
        return new Token($Kakao, $Data);
    }
    
    /**
     * Determines this token is valid or not.
     * @param bool $deep if set true, this will test a token using refreshing.
     * @return bool represents token's validity.
     */
    function isValid($deep = false) : bool {
        if ($this->_Validity == 'UNKNOWN') {
            if (!$deep) {
                /**
                 * No deep-test if $deep set false.
                 */
                return true;
            }
            
            if (is_array($this->getInfo())) {
                return true;
            }
            
            /**
             * After calling refresh() method,
             * the validity has exact value.
             */
            else if ($this->refresh()) {
                $this->_Validity = 'VALID';
                return true;
            }
        }
        
        return $this->_Validity == 'VALID';
    }
    
    /**
     * Get raw data which represents a token.
     * Implementator can store this data and use it for restoring token state.
     */
    function getData() : array {
        return $this->_Data;
    }
    
    /**
     * Deassociate Kakao account.
     * @return bool represents succeed or not.
     */
    function unlink() : bool {
        return $this->deauthorize(true);
    }
    
    /**
     * De-Authorize this token.
     * @param bool $unlink If set true, this will deassociate the Kakao User.
     * @return bool represents succeed or not.
     */
    function deauthorize($unlink = false) : bool {
        if ($this->_Validity != 'INVALID') {
            $this->_Validity = 'INVALID';
            $SucceedAnyway = false;
            
            if ($unlink) {
                $Result = Curl::post(Api::TOKEN_URL . Api::TOKEN_PATH_UNLINK)
                    ->setHeader('Authorization', "bearer {$this->getAccessToken()}")
                    ->exec();

                if ($Result->succeed) {
                    $SucceedAnyway = true;
                }
            }
            
            $Result = Curl::post(Api::TOKEN_URL . Api::TOKEN_PATH_LOGOUT)
                ->setHeader('Authorization', "bearer {$this->getAccessToken()}")
                ->exec();

            $this->_Data = [];
            return $SucceedAnyway || $Result->succeed;
        }
        
        return false;
    }
    
    /**
     * Refresh access-token and this returns true only if access-token updated.
     * Otherwise, Return-Value of this method doesn't represents succeed or failure exactly.
     * For checking succeed or not, use isValid(false) method instead.
     */
    function refresh() : bool {
        if ($this->isValid(false)) {
            $Payload = [
                'grant_type' => 'refresh_token',
                'client_id' => $this->getKakao()->getAppKey(),
                'refresh_token' => $this->getRefreshToken()
            ];
            
            /**
             * If client-secret key specified,
             * Append it to payload.
             */
            if (!is_null($Key = $this->getKakao()->getClientSecretKey())) {
                $Payload['client_secret'] = $Key;
            }
            
            $Result = Curl::post(Api::OAUTH_URL . Api::OAUTH_PATH_TOKEN)
                ->setData($Payload)->exec();
            
            if ($Result->succeed) {
                $Response = json_decode($Result->content, true);
                
                $this->_Validity = 'VALID';
                return $this->update($Response);
            }
            
            $this->_Validity = 'INVALID';
        }
        
        return false;
    }
    
    /**
     * Update internal datas using Refresh response or Authorization series response.
     * DONT call manually please...!
     */
    function update(array& $Response) {
        $Updated = false;
        
        if (isset($Response['access_token'])) {
            $Updated = $Updated || ($this->_Data['access_token'] != $Response['access_token']);

            $this->_Data['access_token'] = $Response['access_token'];
            $this->_Data['expires_in'] = $Response['expires_in'];
        }

        if (isset($Response['refresh_token'])) {
            $Updated = $Updated || ($this->_Data['refresh_token'] != $Response['refresh_token']);

            $this->_Data['refresh_token'] = $Response['refresh_token'];
            $this->_Data['refresh_token_expires_in'] = $Response['refresh_token_expires_in'];
        }
        
        return $Updated;
    }

    /**
     * Get access token.
     * @return string|bool returns access token or false.
     */
    function getAccessToken() : ?string {
        return isset($this->_Data['access_token']) ?
            $this->_Data['access_token'] : false;
    }
    
    /**
     * Get refresh token.
     * @return string|bool returns refresh token or false.
     */
    function getRefreshToken() : ?string {
        return isset($this->_Data['refresh_token']) ?
            $this->_Data['refresh_token'] : false;
    }
    
    /**
     * Get expiration in seconds.
     * @return string|bool returns expiration or false.
     */
    function getExpiresIn() : ?int {
        return isset($this->_Data['expires_in']) ?
            $this->_Data['expires_in'] : false;
    }

    /**
     * Get refresh token's expiration in.
     * @return string|bool returns expiration or false.
     */
    function getRefreshExpiresIn() : ?int {
        return isset($this->_Data['refresh_token_expires_in']) ?
            $this->_Data['refresh_token_expires_in'] : false;
    }
    
    /**
     * Get all scopes which defined in this token.
     */
    function getScopes() : array {
        return isset($this->_Data['scope']) ?
            $this->_Data['scope'] : [];
    }

    /**
     * Test the scope is defined or not.
     */
    function hasScope($ScopeName) : bool {
        if (isset($this->_Data['scope'])) {
            return array_search($ScopeName, $this->_Data['scope']) !== false;
        }
        
        return false;
    }
    
    /**
     * Get associated user. User object will be cached by token.
     */
    function getUser() : User {
        if (!$this->isValid(true)) {
            throw new InvalidStateException(
                "Invalid Token can't instantiate User!");
        }
        
        if (!$this->_User) {
            $this->_User = new User($this->getKakao(), $this);
        }
        
        return $this->_User;
    }
    
    /**
     * Get token's information.
     * @param bool $refresh If set true, this will retry after refreshing on failure.
     * @return array|null token's information.
     */
    function getInfo($refresh = false) : ?array {
        if (!$this->isValid()) {
            return null;
        }
        
        while (!$this->_Info) {
            $Result = Curl::get(Api::TOKEN_URL . Api::TOKEN_PATH_GETINFO)
                ->setHeader('Authorization', "bearer {$this->getAccessToken()}")
                ->exec();
                
            if (!$Result->succeed) {
                if ($refresh) {
                    $refresh = false;
                    $this->refresh();
                    
                    if ($this->isValid()) {
                        continue;
                    }
                }
                
                return null;
            }
            
            $this->_Info = json_decode($Result->content, true);
            $this->_Validity = true;
        }
        
        return $this->_Info;
    }
}
