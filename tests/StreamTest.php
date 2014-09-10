<?php
namespace detectr\Tests {
    use detectr, observr;
    
    /**
     * @package detectr
     */
    class StreamTest extends DetectrTestCase {        
        function testDetectStreamSequence() {
            $user = new detectr\Tests\Mock\User;
            
            $login = new observr\Stream('login');
            $logout = new observr\Stream('logout');
            
            $detector = new detectr\Stream($login,$logout);
            $detector->watch($user);
            
            $c = 0;
            
            $detector->sequence(['login','logout'], function($sender,$e)use(&$c) {
                $c++;
            });
            
            $detector->open();
                        
            $user->setState('login',new observr\Event($user));
            $user->setState('logout',new observr\Event($user));
            $user->setState('login',new observr\Event($user));
            $user->setState('logout',new observr\Event($user));
            
            
            $detector->close();
            
            $this->assertEquals(2,$c);
        }
        
        function testDetectAny() {
            $user = new detectr\Tests\Mock\User;
            
            $login = new observr\Stream('login');
            $logout = new observr\Stream('logout');
            
            $detector = new detectr\Stream($login,$logout);
            $detector->watch($user);
            
            $c = 0;
            
            $detector->any('login',function($sender,$e)use(&$c) {
                $c++;
            });
            
            $detector->open();
                        
            $user->setState('login',new observr\Event($user));
            $user->setState('login',new observr\Event($user));
            $user->setState('logout',new observr\Event($user));
            
            $detector->close();
            
            $this->assertEquals(2,$c);
        }
        
        function testDetectAfter() {
            $user = new detectr\Tests\Mock\User;
            
            $login = new observr\Stream('login');
            
            $detector = new detectr\Stream($login);
            $detector->watch($user);
            
            $c = 0;
            
            $detector->after(2,'login',function($sender,$e)use(&$c) {
                $c++;
            });
            
            $detector->open();
                        
            $user->setState('login',new observr\Event($user));
            $user->setState('login',new observr\Event($user));
            $user->setState('login',new observr\Event($user));
            $user->setState('login',new observr\Event($user));
            
            $detector->close();
            
            $this->assertEquals(2,$c);
        }
        
        function testDetectWithinSequence() {
            $user = new detectr\Tests\Mock\User;
            
            $login = new observr\Stream('login');
            $logout = new observr\Stream('logout');
            
            $detector = new detectr\Stream($login,$logout);
            $detector->watch($user);
            
            $c = 0;
            
            $detector->within(1,'s')->sequence(['login','logout'], function($sender,$e)use(&$c) {
                $c++;
            });
            
            $detector->open();
                        
            $user->setState('login',new observr\Event($user));
            $user->setState('logout',new observr\Event($user));
            sleep(1);
            $user->setState('login',new observr\Event($user));
            $user->setState('logout',new observr\Event($user));
            
            
            $detector->close();
            
            $this->assertEquals(1,$c);
        }
        
        function testDetectSequence() {
            $user = new detectr\Tests\Mock\User;
            
            $login = new observr\Stream('login');
            $logout = new observr\Stream('logout');
            
            $detector = new detectr\Aggregate\Sequence([$login,$logout],function($sender,$e)use(&$c) {
                $c++;
            });
            
            $detector->watch($user);
            
            $c = 0;
            
            $detector->open();
                        
            $user->setState('login',new observr\Event($user));
            $user->setState('logout',new observr\Event($user));
            $user->setState('login',new observr\Event($user));
            $user->setState('logout',new observr\Event($user));
            
            
            $detector->close();
            
            $this->assertEquals(2,$c);
        }
        
        function testExplicitStreamSum() {
            $price = new detectr\Tests\Mock\Price;
            
            $receive = new observr\Stream('receive');
            
            $detector = new detectr\Stream($receive);
            $detector->watch($price);
            
            $detector->any('receive',function($sender,$e) {
                if(empty($e['sum'])) {
                    $e['sum'] = 0;
                }
                $e['sum'] += $sender->value;
            });
            
            $detector->open();
            
            $e = new observr\Event($price);
            
            $price->value = 1;
            $price->setState('receive',$e);
            
            $price->value = 2;
            $price->setState('receive',$e);
            
            $price->value = 4;
            $price->setState('receive',$e);
            
            $price->value = 8;
            $price->setState('receive',$e);
            
            $detector->close();
            
            $this->assertEquals(15,$e['sum']);
        }
        
        function testCustomAggregateAny() {
            $price = new detectr\Tests\Mock\Price;
            
            $detector = new detectr\Aggregate\Any(
                    'receive',
                    function($sender,$e) {
                        if(empty($e['sum'])) {
                            $e['sum'] = 0;
                        }
                        $e['sum'] += $sender->value;
                    }
                );
                
            $detector->watch($price);
            
            $detector->open();
            
            $e = new observr\Event($price);
            
            $price->value = 1;
            $price->setState('receive',$e);
            
            $price->value = 2;
            $price->setState('receive',$e);
            
            $price->value = 4;
            $price->setState('receive',$e);
            
            $price->value = 8;
            $price->setState('receive',$e);
            
            $detector->close();
            
            $this->assertEquals(15,$e['sum']);
        }
                
        function testStatsAggregateAll() {
            $price = new detectr\Tests\Mock\Price;
            
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
            
            $this->assertEquals(2,$e['count']);
            $this->assertEquals(5,$e['sum']);
            $this->assertEquals(1,$e['min']);
            $this->assertEquals(4,$e['max']);
            $this->assertEquals(3,$e['mean']);
            $this->assertEquals(8,$e['variance']);
            $this->assertEquals(-1.5,$e['stdev']);
        }
        
        function testAggregateEvery() {
            $price = new detectr\Tests\Mock\Price;
            
            $c=0;
            $detector = new detectr\Aggregate\Every(1,
                function()use(&$c) {
                    $c+=1;
                }
            );
            
            $detector->addStream('receive');
                
            $detector->watch($price);
            
            $detector->open();
            
            $e = new observr\Event($price);
            
            for($i=0;$i<10000;$i++) {
                $price->setState('receive',$e);
            }

            $detector->close();
            
            $this->assertGreaterThan(0,$c);
        }
    }
}