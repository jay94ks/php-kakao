<?php

namespace jay94ks\kakao\auth;
use jay94ks\kakao\Kakao;
use jay94ks\kakao\base\Base;
use jay94ks\kakao\base\Curl;
use jay94ks\kakao\user\Token;

/**
 * Authentication API module.
 *
 * @author jay94ks
 */
class Auth extends Base {
    function __construct(Kakao $kakao) {
        parent::__construct($kakao);
    }
    
    /**
     * Generate an authorization URL for Kakao OAuth.
     * @return Url Url where should redirect to.
     */
    function generate($RedirectUrl, $State = null, $Reauth = false) {
        $Kakao = $this->getKakao();
        $Redirect = urlencode($RedirectUrl);
        
        return Api::OAUTH_URL . Api::OAUTH_PATH_AUTHORIZE
            . "?response_type=code&client_id={$Kakao->getAppKey()}"
            . "&redirect_uri={$Redirect}" . ($State
            ? "&state=" . urlencode($State) : '') . ($Reauth 
            ? "&auth_type=reauthenticate" : '');
    }
    
    /**
     * Authorize the given code provided from Kakao OAuth.
     * 
     * @param String $Code Code from Kakao OAuth.
     * @param Url $RequestUri Request URI which specified for generate method.
     * @return Token|bool Token from Kakao, but if failure, returns false.
     */
    function authorize($Code, $RequestUri) : ?Token {
        if (!is_string($Code)) {
            throw new \InvalidArgumentException(
                "Invalid Code specified. it should be String!");
        }
        
        else if (!$RequestUri) {
            throw new \InvalidArgumentException(
                "Invalid Request URL specified. ".
                "Acting with Authorization Code requires Redirect URL!");
        }
        
        $Kakao = $this->getKakao();
        $Result = Curl::post(Api::OAUTH_URL . Api::OAUTH_PATH_TOKEN)
            ->setData([
                'grant_type' => 'authorization_code',
                'client_id' => $Kakao->getAppKey(),
                'redirect_uri' => $RequestUri,
                'code' => $Code
            ])
            ->exec();
        
        if ($Result->succeed) {
            $Caches = $Kakao->getRuntimeCache();
            $Data = json_decode($Result->content, true);
            
            if (isset($Data['token_type']) && isset($Data['access_token'])) {
                $Data['no-revalidate'] = true;
                return new Token($Kakao, $Data);
            }
        }
        
        return false;
    }
    
    /**
     * De-Authorize a token.
     * @param Token $Token Kakao user token.
     * @return bool represents succeed or not.
     */
    function deauthorize(Token $Token, $unlink = false) : bool {
        return $Token->deauthorize($unlink);
    }
}
