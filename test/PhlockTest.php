<?php

$GLOBALS['THRIFT_ROOT'] = '/usr/local/thrift-0.2.0/lib/php/src';
require_once 'src/autoload.php';

class PhlockUtilsTest extends PHPUnit_Framework_TestCase {
	
	private function getPhlock() {
		return new Phlock(array('127.0.0.1:7915'), array('graphs'=>array('follows'=>1, 'blocks'=>2)));
	}
	
	public function setUp() {
		//usleep(500000);
		$flock = $this->getPhlock();
		$flock->add(1,1,2);
		$flock->add(1,1,3);
		$flock->add(2,1,1);
		$flock->add(4,1,1);
		//usleep(500000);
	}
	
	private function assertEqualsRetry($expected, $function) {
		for ($i = 0; $i < 10; $i++) {
			$result = call_user_func($function);
			if ($expected == $result) {
				return;
			}
			usleep(100000);
		}
		$this->assertEquals($expected, $result);
	}
	
	public function testSize() {
		$flock = $this->getPhlock();
		$this->assertEqualsRetry(2, function() use ($flock) {
			return $flock->size(1,1,null);
		});
	}
	
	public function testContains() {
		$flock = $this->getPhlock();
		$this->assertTrue($flock->contains(1, 1, 2));
	}
	
	public function testRemove() {
		$flock = $this->getPhlock();
		$this->assertEqualsRetry(true, function() use ($flock) {
			return $flock->contains(1,1,2);
		});
		$flock->remove(1,1,2);
		$this->assertEqualsRetry(false, function() use ($flock) {
			return $flock->contains(1,1,2);
		});
	}
	
	public function testSelect() {
		$flock = $this->getPhlock();
		$result = $flock->select(1,1,array(2,3));
		$this->assertType('PhlockdbResult', $result);
		
		$this->markTestIncomplete();
		$this->assertEquals(array(2,3), $result->toArray());
	}
}
