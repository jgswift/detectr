<?php
namespace detectr\Aggregate {
    use detectr;
    
    class Every extends detectr\Aggregate {
        /**
         * Helper class to default periodic processing
         * @param integer $seconds
         * @param callable $callable
         */
        function __construct($seconds, callable $callable) {
            parent::__construct();
            
            $this->every($seconds, $callable);
        }
    }
}