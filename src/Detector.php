<?php
namespace detectr {
    interface Detector {
        /**
         * Detect sequence of events
         */
        function sequence(array $events, callable $callable);
        
        /**
         * Detect any event
         */
        function any($event, callable $callable);
        
        /**
         * Detect a number of events (not necessarily in sequence)
         */
        function after($amount, $event, callable $callable);
        
        /**
         * Limit detector to certain time span
         */
        function within($increment,$period);
        
        /**
         * Add periodic callback timer
         */
        function every($interval,callable $callable);
    }
}
