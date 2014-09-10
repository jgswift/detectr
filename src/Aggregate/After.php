<?php
namespace detectr\Aggregate {
    use detectr;
    
    class After extends detectr\Aggregate {
        /**
         * Helper class to default after event processing
         * @param integer $amount
         * @param mixed $stream
         * @param callable $callable
         */
        function __construct($amount, $stream, callable $callable) {
            parent::__construct($stream);
            
            $this->after($amount, $stream, $callable);
        }
    }
}