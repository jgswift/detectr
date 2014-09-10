<?php
namespace detectr\Tests\Mock {
    use observr;
    class Price {
        use observr\Subject;
        
        public $value = 0;
    }
}