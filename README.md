detectr
====
PHP 5.5+ complex event processor

[![Build Status](https://travis-ci.org/jgswift/detectr.png?branch=master)](https://travis-ci.org/jgswift/detectr)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jgswift/detectr/badges/quality-score.png?s=4c1433cd4686440e0a8a2eb2a0d3aac9d2a62337)](https://scrutinizer-ci.com/g/jgswift/detectr/)

## Description

Detectr is a lightweight event processing package built on top of 
[jgswift/observr](http://github.com/jgswift/observr) to detect events sequences 
or aggregate event data in a domain-agnostic non-intrusive way.

## Installation

Install via cli using [composer](https://getcomposer.org/):
```sh
php composer.phar require jgswift/detectr:0.1.*
```

Install via composer.json using [composer](https://getcomposer.org/):
```json
{
    "require": {
        "jgswift/detectr": "0.1.*"
    }
}
```

## Dependency

* php 5.5+
* [jgswift/observr](http://github.com/jgswift/observr) - observer pattern using traits

## Usage

### After

Called after a certain event is detected a certain number of times

```php
class User {
    use observr\Subject;
}

$user = new User;

$detector = new detectr\Aggregate\After(3,'login', function($sender,$e) {
    // called after the login event is triggered 3 times
});

$detector->watch($user); // instruct detector to watch specific user events

$detector->open(); // open event stream

// trigger 'login' event 3 times
$user->setState('login',new observr\Event($user));
$user->setState('login',new observr\Event($user));
$user->setState('login',new observr\Event($user));

$detector->close(); // close event stream
```

### Any

Called after a certain event is detected

```php
class Price {
    use observr\Subject;

    public $value = 0;
}

$price = new Price;

$detector = new detectr\Aggregate\Any(
    'receive',
    new detectr\Stats('value',detectr\Stats::SUM)
);

$detector->watch($price);

$e = new observr\Event($price);

$detector->open();

// change prices and notify listener
$price->value = 1;
$price->setState('receive',$e);

$price->value = 2;
$price->setState('receive',$e);

$price->value = 4;
$price->setState('receive',$e);

$price->value = 8;
$price->setState('receive',$e);

$detector->close();

// the observr\Event holds the final value
var_dump($e['sum']); // 15
```

### Every

Called at a given time interval

```php
class Price {
    use observr\Subject;

    public $value = 0;
}

$price = new detectr\Tests\Mock\Price;

$c=0;
$detector = new detectr\Aggregate\Every(
    5,
    function() {
        // triggered every 5 seconds
    }
);

$detector->addStream('receive'); // stream is subordinate to timer and must be added separately

$detector->watch($price);

$detector->open();

$e = new observr\Event($price);

for($i=0;$i<10000;$i++) { // do something that takes a lot of time
    $price->setState('receive',$e);
}

$detector->close();
```

### Sequence

Called if a sequence of events is detected

```php
class User {
    use observr\Subject;
}

$user = new User;

$c = 0;

$detector = new detectr\Stream(
    ['login','logout'],
    function($sender,$e)use(&$c) {
        $c++; // called twice
    }
);

$detector->watch($user);

$detector->open();

// FIRST SEQUENCE
$user->setState('login',new observr\Event($user));
$user->setState('logout',new observr\Event($user));

// SECOND SEQUENCE
$user->setState('login',new observr\Event($user));
$user->setState('logout',new observr\Event($user));

$detector->close();

var_dump($c); // 2
```

### Within

Limits detection to a certain time-span.  This limiter may be applied to any aggregator.

```php
class User {
    use observr\Subject;
}

$user = new User;

$c = 0;

$detector = new detectr\Stream(
    ['login','logout'],
    function($sender,$e)use(&$c) {
        $c++; // called once
    }
);

$detector->within(1,'s'); // only called if sequences occur within timespan

$detector->watch($user);

$detector->open();

// FIRST SEQUENCE
$user->setState('login',new observr\Event($user));
$user->setState('logout',new observr\Event($user));

// block processing for a second
// this block prevent the detection sequence from emitting for the second sequence
// because it doesn't occur within enough time
sleep(1); 

// SECOND SEQUENCE
$user->setState('login',new observr\Event($user));
$user->setState('logout',new observr\Event($user));

$detector->close();

var_dump($c); // 1
```

### Statistics

Helper methods to accumulate statistics on subjects

* COUNT
* SUM
* MIN
* MAX
* MEAN
* VARIANCE
* STDEV
* ALL

```php
class Price {
    use observr\Subject;

    public $value = 0;
}

$price = new Price;

$detector = new detectr\Aggregate\Any(
    'receive',
    new detectr\Stats('value',detectr\Stats::ALL)
);

$detector->watch($price);

$detector->open();

$e = new observr\Event($price);

$price->value = 1;
$price->setState('receive',$e);

$price->value = 4;
$price->setState('receive',$e);

$detector->close();

var_dump($e['count']);      // 2
var_dump($e['sum']);        // 5
var_dump($e['min']);        // 1
var_dump($e['max']);        // 4
var_dump($e['mean']);       // 3
var_dump($e['variance']);   // 8
var_dump($e['stdev']);      // -1.5
```

