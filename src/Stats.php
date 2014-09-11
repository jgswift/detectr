<?php
namespace detectr {
    class Stats {
        const COUNT = 'count';
        const SUM = 'sum';
        const MIN = 'min';
        const MAX = 'max';
        const MEAN = 'mean';
        const VARIANCE = 'variance';
        const STDEV = 'stdev';
        const ALL = 'all';
        
        private $target;
        private $fn;
        private $v_count = 0;
        private $s_count = 0;
        private $s_m = 0;
        
        /**
         * Create stat tracker
         * @param string $ask
         * @param string $fn
         */
        public function __construct($ask,$fn=null) {
            if(is_null($fn)) {
                $fn = $ask;
                $ask = null;
            }
            $this->target = $ask;
            $this->fn = $fn;
        }
        
        /**
         * Clear stat tracker
         */
        public function clear() {
            $this->v_count = 0;
            $this->s_count = 0;
            $this->s_m = 0;
        }
        
        /**
         * Get ask property or method
         * @param mixed $sender
         * @return mixed
         */
        protected function target($sender) {
            if(property_exists($sender,$this->target)) {
                return $sender->{$this->target};
            } elseif(method_exists($sender, $this->target)) {
                return $sender->{$this->target}();
            }
            
            return null;
        } 
        
        /**
         * Compute count
         * @param mixed $sender
         * @param observr\Event $e
         */
        public function count($sender,$e) {
            if(!array_key_exists(__FUNCTION__, $e)) {
                $e[__FUNCTION__] = 0;
            }
            $e[__FUNCTION__]++;
        }
        
        /**
         * Compute sum
         * @param mixed $sender
         * @param observr\Event $e
         */
        public function sum($sender,$e) {
            $value = $this->target($sender);
            
            if(!array_key_exists(__FUNCTION__, $e)) {
                $e[__FUNCTION__] = 0;
            }
            
            $e[__FUNCTION__] += $value;
        }
        
        /**
         * Compute min
         * @param mixed $sender
         * @param observr\Event $e
         */
        public function min($sender,$e) {
            $value = $this->target($sender);
            
            if(!array_key_exists(__FUNCTION__, $e)) {
                $e[__FUNCTION__] = $value;
            } else {
                if($value < $e[__FUNCTION__]) {
                    $e[__FUNCTION__] = $value;
                }
            }
        }
        
        /**
         * Compute max
         * @param mixed $sender
         * @param observr\Event $e
         */
        public function max($sender,$e) {
            $value = $this->target($sender);
            
            if(!array_key_exists(__FUNCTION__, $e)) {
                $e[__FUNCTION__] = $value;
            } else {
                if($value > $e[__FUNCTION__]) {
                    $e[__FUNCTION__] = $value;
                }
            }
        }
        
        /**
         * Compute mean
         * @param mixed $sender
         * @param observr\Event $e
         */
        public function mean($sender,$e) {
            $value = $this->target($sender);
            
            if(!array_key_exists(__FUNCTION__, $e)) {
                $e[__FUNCTION__] = $value;
            } else {
                $e[__FUNCTION__] = $e[__FUNCTION__] + $value / 2;
            }
        }
        
        /**
         * Compute variance
         * @param mixed $sender
         * @param observr\Event $e
         */
        public function variance($sender,$e) {
            $value = $this->target($sender);
            
            $this->v_count++;
            
            if(!array_key_exists(__FUNCTION__, $e)) {
                $e[__FUNCTION__] = 0;
            }
            
            $e[__FUNCTION__] = $e[__FUNCTION__] + $value * ($value-$this->v_count);
        }
        
        /**
         * Compute standard deviation
         * @param mixed $sender
         * @param observr\Event $e
         */
        public function stdev($sender,$e) {
            $value = $this->target($sender);
            
            $this->s_count++;
            $d = $value - $this->s_m;
            $this->s_m += $d/$this->s_count;
            
            if(!array_key_exists(__FUNCTION__, $e)) {
                $e[__FUNCTION__] = 0;
            }
            
            $e[__FUNCTION__] = $e[__FUNCTION__] * $d - ($value-$this->s_m);
        }
        
        /**
         * Compute all stats
         * @param mixed $sender
         * @param observr\Event $e
         */
        public function all($sender,$e) {
            $this->count($sender,$e);
            $this->sum($sender,$e);
            $this->min($sender,$e);
            $this->max($sender,$e);
            $this->mean($sender,$e);
            $this->variance($sender, $e);
            $this->stdev($sender, $e);
        }
        
        /**
         * Stat object is invoked as callback
         * @param mixed $sender
         * @param observr\Event $e
         * @return mixed
         */
        public function __invoke($sender,$e) {
            if(method_exists($this,$this->fn)) {
                return call_user_func_array([$this,$this->fn],[$sender,$e]);
            }
            
            return null;
        }
    }
}
