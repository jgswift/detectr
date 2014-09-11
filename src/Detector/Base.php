<?php
namespace detectr\Detector {
    use detectr;
    
    abstract class Base implements detectr\Detector{
        /**
         * List of streams
         * @var array
         */
        protected $streams = [];
        
        /**
         * Multidimensional list of listeners, keyed by aggregation type
         * @var array 
         */
        protected $listeners = [];
        
        /**
         * List of all events triggered
         * @var array
         */
        protected $events = [];
        
        /**
         * List of names of all events triggered
         * @var array 
         */
        protected $event_names = [];
        
        /**
         * List of invokation count for every aggregation type
         * @var array
         */
        protected $amounts = [];
        
        /**
         * DateTime to limit aggregation within timespan
         * @var \DateTime
         */
        protected $start_time;
        
        /**
         * DateTime to limit aggregation within timespan
         * @var \DateTime 
         */
        protected $since_start;
        
        /**
         * DateTime to track periodic timer
         * @var \DateTime
         */
        protected $start_elapse;
        
        /**
         * DateTime to track periodic timer
         * @var \DateTime
         */
        protected $elapsed;
        
        /**
         * Tracks offset of last sequence
         * @var integer 
         */
        protected $sequence_start = 0;
        
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
        protected function trigger($sender, $e) {
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
        protected function emit(callable $name,$e=null) {
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
        protected function findSequence(array $needle, array $haystack) {
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
    }
}
