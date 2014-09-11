<?php
namespace detectr {
    use observr;
    
    class Stream implements Detector {
        use observr\Subject;
        
        /**
         * List of streams
         * @var array
         */
        private $streams = [];
        
        /**
         * Multidimensional list of listeners, keyed by aggregation type
         * @var array 
         */
        private $listeners = [];
        
        /**
         * List of all events triggered
         * @var array
         */
        private $events = [];
        
        /**
         * List of names of all events triggered
         * @var array 
         */
        private $event_names = [];
        
        /**
         * List of invokation count for every aggregation type
         * @var array
         */
        private $amounts = [];
        
        /**
         * DateTime to limit aggregation within timespan
         * @var \DateTime
         */
        private $start_time;
        
        /**
         * DateTime to limit aggregation within timespan
         * @var \DateTime 
         */
        private $since_start;
        
        /**
         * DateTime to track periodic timer
         * @var \DateTime
         */
        private $start_elapse;
        
        /**
         * DateTime to track periodic timer
         * @var \DateTime
         */
        private $elapsed;
        
        /**
         * Tracks offset of last sequence
         * @var integer 
         */
        private $sequence_start = 0;
        
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
         * Attaches detector triggers
         */
        protected function setup() {
            if(!empty($this->streams)) {
                foreach($this->streams as $stream) {
                    $stream->detach([$this,'trigger']);
                    $stream->attach([$this,'trigger']);
                }
            }
        }
        
        /**
         * Executes detector detection method
         * @param mixed $sender
         * @param \observr\Event $e
         */
        public function trigger($sender, $e) {
            if(is_null($this->start_time)) {
                $this->start_time = new \DateTime;
            } else {
                $this->since_start = new \DateTime;
            }
            $this->events[] = $e;
            $this->event_names[] = $e->name;
            if(!array_key_exists($e->name,$this->amounts)) {
                $this->amounts[$e->name] = 0;
            }
            
            $this->amounts[$e->name]++;
            $this->handle($e);
        }
        
        /**
         * Check if event triggered within timespan
         * @return boolean
         */
        protected function isWithin() {
            if(isset($this->listeners['within']) && 
                $this->start_time instanceof \DateTime &&
                $this->since_start instanceof \DateTime) {
                foreach($this->listeners['within'] as $listener) {
                    list($increment,$period) = $listener;
                    $diff = $this->start_time->diff($this->since_start);
                    if($diff->$period < $increment) {
                        return true;
                    }
                }
            } else {
                return true;
            }
            
            return false;
        }
        
        /**
         * Emit triggered listeners
         * @param callable $name
         * @param observr\Event $e
         */
        private function emit(callable $name,$e=null) {
            if(!is_null($e)) {
                call_user_func_array($name,[$e->sender,$e]);

                $this->setState('emit',$e);
            } else {
                call_user_func($name);
            }
        }
        
        /**
         * Handles listener criteria
         * @param observr\Event $e
         */
        protected function handle($e) {
            foreach($this->listeners as $name => $l) {
                foreach($l as $listener) {
                    switch($name) {
                        case 'any':
                            if($e->name == $listener[0] && $this->isWithin()) {
                                $this->emit($listener[1],$e);
                            }
                            break;
                        case 'after':
                            if($e->name == $listener[1] && $this->amounts[$e->name] == $listener[0] && $this->isWithin()) {
                                $this->amounts[$e->name] = 0;
                                $this->emit($listener[2],$e);
                            }
                            break;
                        case 'sequence':
                            $valid = $this->findSequence($listener[0], array_slice($this->event_names,$this->sequence_start));
                            
                            if(!empty($valid) && $this->isWithin()) {
                                $this->sequence_start = $valid[0][count($valid[0])-1]+1;
                                $this->emit($listener[1],$e);
                            }
                            break;
                        case 'every':
                            if($this->start_elapse instanceof \DateTime) {
                                $this->elapsed = new \DateTime;
                                $diff = $this->start_elapse->diff($this->elapsed);
                                if($diff->s >= $listener[0]) {
                                    $this->emit($listener[1]);
                                    $this->start_elapse = new \DateTime;
                                }
                            } else {
                                $this->start_elapse = new \DateTime;
                            }
                            break;
                        case 'within':
                            continue;
                    }
                }
            }
        }
        
        /**
         * Find sequence in recent history
         * @param array $needle
         * @param array $haystack
         * @return array
         */
        private function findSequence(array $needle, array $haystack) {
            $keys = array_keys($haystack, $needle[0]);
            $out = [];
            foreach($keys as $key) {
                $add = true;
                $result = [];
                foreach($needle as $i => $value) {
                    if(!(isset($haystack[$key + $i]) && $haystack[$key + $i] == $value)) {
                        $add = false;
                        break;
                    }
                    $result[] = $key + $i;
                }
                if($add == true) { 
                    $out[] = $result;
                }
            }
            return $out;
        }
        
        /**
         * Helper method to add listener
         * @param mixed $name
         * @param array $data
         * @return \detectr\Stream
         */
        protected function listen($name,$data) {
            if(is_array($name)) {
                foreach($name as $n) {
                    $this->listen($n,$data);
                }
                
                return $this;
            }
            
            if($name instanceof observr\Stream) {
                $name = $name->name;
            }
            
            if(!array_key_exists($name, $this->listeners)) {
                $this->listeners[$name] = [];
            }
            
            $this->listeners[$name][] = $data;
            
            return $this;
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

