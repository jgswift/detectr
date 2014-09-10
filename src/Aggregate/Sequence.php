<?php
namespace detectr\Aggregate {
    use detectr;
    
    class Sequence extends detectr\Aggregate {
        
        /**
         * Helper class to default sequence event processing
         * @param array $events
         * @param callable $callable
         */
        function __construct(array $events, callable $callable) {
            parent::__construct();
            
            foreach($events as $e) {
                $this->addStream($e);
            }
                        
            $this->sequence($events, $callable);
        }
    }
}