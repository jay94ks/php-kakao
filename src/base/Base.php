<?php

namespace jay94ks\kakao\base;
use jay94ks\kakao\Kakao;

/**
 * Base class of all Kakao related objects.
 *
 * @author jay94
 */
abstract class Base {
    private $_Kakao;
    
    /**
     * Initialize a module.
     */
    function __construct(Kakao $kakao) {
        $this->_Kakao = $kakao;
    }
    
    function getKakao() : Kakao {
        return $this->_Kakao;
    }
    
}
