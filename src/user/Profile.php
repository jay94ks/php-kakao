<?php

namespace jay94ks\kakao\user;
use jay94ks\kakao\Kakao;
use jay94ks\kakao\base\Curl;
use jay94ks\kakao\base\Base;
use jay94ks\kakao\auth\Api as AuthApi;
use jay94ks\kakao\InvalidStateException;

/**
 * Description of Profile
 *
 * @author jay94
 */
class Profile extends Base {
    private $_User;
    private $_Profile;
    
    /**
     * Initialize a profile object for the user.
     */
    function __construct(Kakao $kakao, User $User) {
        parent::__construct($kakao);
        $this->_User = $User;
        $this->_Profile = $User->getInfoAttribute('profile');
    }
    
    /**
     * Generate an agreement uri.
     */
    function generateAgreement($RedirectUri, $Scopes = [], $State = null) {
        if (count($Scopes)) {
            return AuthApi::OAUTH_URL . AuthApi::OAUTH_PATH_AUTHORIZE .
                "?client_id=" . $this->getKakao()->getAppKey().
                "&redirect_uri=" . urlencode($RedirectUri) . 
                "&scope=" . urlencode(join(',', $Scopes)) . ($State ?
                "&state={$State}" : '');
        }
        
        throw new \InvalidArgumentException('Scopes can not be empty!');
    }
    
    /**
     * Agree the given code provided from Kakao OAuth.
     */
    function agree($Code, $RedirectUri) {
        if (!is_string($Code)) {
            throw new \InvalidArgumentException(
                "Invalid Code specified. it should be String!");
        }
        
        else if (!$RequestUri) {
            throw new \InvalidArgumentException(
                "Invalid Request URL specified. ".
                "Acting with Agreement Code requires Redirect URL!");
        }
        
        $Kakao = $this->getKakao();
        $Result = Curl::post(AuthApi::OAUTH_URL . AuthApi::OAUTH_PATH_TOKEN)
            ->setData([
                'grant_type' => 'authorization_code',
                'client_id' => $Kakao->getAppKey(),
                'redirect_uri' => $RequestUri,
                'code' => $Code
            ])
            ->exec();
        
        if ($Result->succeed) {
            $Token = $this->_User->getToken();
            $Token->update(urldecode($Result->content));
        }
        
        return $Result->succeed;
    }
    
    /**
     * Get user instance which associated with this profile.
     */
    function getUser() : User {
        return $this->_User;
    }
    
    /**
     * Determines user agreed to show profile or not.
     */
    function hasAgreed() {
        return !$this->_Profile['profile_needs_agreement'];
    }
    
    /**
     * Determines user agreed to show user's email address or not.
     */
    function hasEmailAgreed() {
        return !$this->_User->getInfoAttribute('email_needs_agreement', true);
    }
    
    /**
     * Determines user agreed to show user's age range or not.
     * Note: this means not ACTUAL AGE, just begining and ending of it.
     */
    function hasAgeRangeAgreed() {
        return !$this->_User->getInfoAttribute('age_range_needs_agreement', true);
    }
    
    /**
     * Determines user agreed to show user's birthday or not.
     * Note: this means not ACTUAL BIRTHDAY, just day and month only.
     */
    function hasBirthdayAgreed() {
        return !$this->_User->getInfoAttribute('birthday_needs_agreement', true);
    }
    
    /**
     * Determines user agreed to show user's gender or not.
     */
    function hasGenderAgreed() {
        return !$this->_User->getInfoAttribute('gender_needs_agreement', true);
    }
    
    /**
     * Determines user's email address is valid or not.
     */
    function isEmailValid() {
        return $this->_User->getInfoAttribute('is_email_valid', false);
    }
    
    /**
     * Determines user's email address verified or not.
     */
    function isEmailVerified() {
        return $this->_User->getInfoAttribute('is_email_verified', false);
    }
    
    /**
     * Get user's nickname.
     */
    function getNickName() {
        if ($this->hasAgreed()) {
            return $this->_Profile['nickname'];
        }
        
        return null;
    }
    
    /**
     * Get user's profile image.
     */
    function getImageUrl() {
        if ($this->hasAgreed()) {
            return $this->_Profile['profile_image_url'];
        }
        
        return null;
    }
    
    /**
     * Get user's thumbnail image.
     */
    function getThumbnailUrl() {
        if ($this->hasAgreed()) {
            return $this->_Profile['thumbnail_image_url'];
        }
        
        return null;
    }
    
    /**
     * Get user's email address.
     */
    function getEmailAddress() {
        if ($this->hasEmailAgreed() && $this->isEmailValid()) {
            return $this->_User->getInfoAttribute('email');
        }
        
        return null;
    }
    
    /**
     * Get user's age range in array.
     * e.g. [ begining, ending ]
     */
    function getAgeRange() : ?array {
        if ($this->hasAgeRangeAgreed()) {
            return array_map(function($x) { return intval(trim($x)); },
                explode('~', $this->_User->getInfoAttribute('age_range')));
        }
        
        return null;
    }
    
    /**
     * Get user's birthday in DateTime instance.
     * This will return birthday of current year.
     */
    function getBirthday() : ?\DateTime {
        if ($this->hasBirthdayAgreed()) {
            $MMDD = $this->_User->getInfoAttribute('birthday');
            
            $Year = intval(date('y'));
            $Month = intval(substr($MMDD, 0, 2));
            $Day = intval(substr($MMDD, 2));
            
            return \DateTime::createFromFormat(
                "y-m-d", "{$Year}-{$Month}-{$Day}");
        }
        
        return null;
    }
    
    /**
     * Get user's gender in one charactor.
     * Female for F, Male for M. And nothing for null.
     */
    function getGender() {
        if ($this->hasGenderAgreed()) {
            $Gender = $this->_User->getInfoAttribute('gender');
            return $Gender == 'female' ? 'F' : (
                $Gender == 'male' ? 'M' : null);
        }
        
        return null;
    }
}
