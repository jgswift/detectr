<?php
namespace detectr {
    abstract class Aggregate extends Stream {
        /**
         * Default aggregate helper
         * @param mixed $stream
         */
        function __construct($stream=null) {
            parent::__construct($stream);
        }
    }
}