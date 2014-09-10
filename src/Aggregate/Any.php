<?php
namespace detectr\Aggregate {
    use detectr;
    
    class Any extends detectr\Aggregate {
        /**
         * Helper class to default any event processing
         * @param mixed $stream
         * @param callable $callable
         */
        function __construct($stream, callable $callable) {
            parent::__construct($stream);
            
            $this->any($stream, $callable);
        }
    }
}