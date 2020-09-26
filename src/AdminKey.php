<?php

namespace jay94ks\kakao;

/**
 * Administrator Key from Kakao.
 *
 * @author jay94ks
 */
class AdminKey extends base\Key {
    function __construct($Key) {
        parent::__construct($Key);
    }
    
    /**
     * Encode this key for Http Authorization header.
     */
    function encode() {
        return "KakaoAK {$this}";
    }
}
