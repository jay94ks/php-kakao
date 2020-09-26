<?php

namespace jay94ks\kakao\base;

/**
 * Base class of all keys from Kakao.
 *
 * @author jay94ks
 */
abstract class Key {
    private $_Value;
    
    /**
     * Initialize a new key from String or clone instanciated key .
     */
    function __construct($Key) {
        if (is_string ($Key) || ($Key instanceof Key)) {
            $this->_Value = "{$Key}";
        } else {
            throw new \Exception(
                "Key must be initialized " . 
                "with String or instantiated Token!");
        }
    }
    
    /**
     * Stringify this key.
     */
    function __toString() {
        return $this->_Value;
    }
    
    /**
     * Encode this key for Http Authorization header.
     */
    function encode() {
        return "bearer {$this}";
    }
}
