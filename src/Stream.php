<?php
namespace detectr {
    use observr;
    
    class Stream extends Detector\Base {
        use observr\Subject;
        
        /**
         * Default detector constructor
         * Accepts observr\Stream and string arguments in any order
         */
        public function __construct() {
            $argc = func_num_args();
            
            if($argc > 0) {
                $args = func_get_args();
                foreach($args as $arg) {
                    if($arg instanceof observr\Stream) {
                        $this->streams[] = $arg;
                    } elseif(is_string($arg)) {
                        $this->streams[] = new observr\Stream($arg);
                    }
                }
                
                $this->setup();
            }
        }
        
        /**
         * Adds stream to detector
         * @param mixed $stream
         * @return \observr\Stream
         */
        public function addStream($stream) {
            if(is_string($stream)) {
                $stream = new observr\Stream($stream);
            }
            
            $this->streams[] = $stream;
            $this->setup();
            
            return $stream;
        }
        
        /**
         * Removes stream from detector
         * @param observr\Stream $stream
         */
        public function removeStream(observr\Stream $stream) {
            $key = array_search($stream,$this->streams);
            if($key !== false) {
                $stream->detach([$this,'trigger']);
                unset($this->streams[$key]);
            }
        }
        
        /**
         * Listen after
         * @param int $amount
         * @param mixed $event
         * @param callable $callable
         * @return \detectr\Stream
         */
        public function after($amount, $event, callable $callable) {
            return $this->listen('after',[$amount,$event,$callable]);
        }

        /**
         * Listen to any
         * @param mixed $event
         * @param callable $callable
         * @return \detectr\Stream
         */
        public function any($event, callable $callable) {
            return $this->listen('any',[$event,$callable]);
        }

        /**
         * Listen for a sequence
         * @param array $events
         * @param callable $callable
         * @return \detectr\Stream
         */
        public function sequence(array $events, callable $callable) {
            foreach($events as $k => $e) {
                if($e instanceof observr\Stream) {
                    $events[$k] = $e->name;
                }
            }
            
            return $this->listen('sequence',[$events,$callable]);
        }

        /**
         * Listen only if within timespan
         * @param int $increment
         * @param string $period
         * @return \detectr\Stream
         */
        public function within($increment, $period) {
            return $this->listen('within',[$increment, $period]);
        }
        
        /**
         * Add periodic timer
         * @param int $seconds
         * @param callable $callable
         * @return \detectr\Stream
         */
        public function every($seconds, callable $callable) {
            return $this->listen('every',[$seconds, $callable]);
        }
        
        /**
         * Add subject to all observr streams
         * @param mixed $subject
         */
        public function watch($subject) {
            foreach($this->streams as $stream) {
                $stream->watch($subject);
            }
            
            return $this;
        }
        
        /**
         * Remove subject from all observr streams
         * @param mixed $subject
         */
        public function unwatch($subject) {
            foreach($this->streams as $stream) {
                $stream->unwatch($subject);
            }
            
            return $this;
        }
        
        /**
         * Opens all streams
         */
        public function open() {
            foreach($this->streams as $stream) {
                $stream->open();
            }
            
            $this->setState('open');
        }
        
        /**
         * Closes all streams
         */
        public function close() {
            unset($this->start_time);
            foreach($this->streams as $stream) {
                $stream->close();
            }
            
            // CLEAR LISTENER COUNTS
            foreach($this->listeners as $listener) {
                foreach($listener as $v) {
                    if($v instanceof Stats) {
                        $v->clear();
                        break;
                    }
                }
            }
            
            $this->setState('close');
        }
    }
}

