<?php

namespace jay94ks\kakao;

/**
 * Description of InvalidStateException
 *
 * @author jay94
 */
class InvalidStateException extends \Exception {
    function __construct(string $message = "", int $code = 0, \Throwable $previous = NULL): \Exception {
        parent::__construct($message, $code, $previous);
    }
}
