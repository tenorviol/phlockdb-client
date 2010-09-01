<?php

$GLOBALS['THRIFT_ROOT'] = '/usr/local/thrift-0.2.0/lib/php/src';
require_once 'src/autoload.php';

class PhlockTest extends PHPUnit_Framework_TestCase {
	
	private function getPhlock() {
		return new Phlock(array('127.0.0.1:7915'), array('graphs'=>array('follows'=>1, 'blocks'=>2)));
	}
	
	public function setUp() {
		sleep(1);
		$flock = $this->getPhlock();
		$flock->add(1,1,2);
		$flock->add(1,1,3);
		$flock->add(2,1,1);
		$flock->add(4,1,1);
		
		// TODO: unarchive bugs
		//$flock->add(1,1,null);
		//$flock->add(2,1,null);
		//$flock->add(4,1,null);
		
		sleep(1);
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
		$this->assertEqualsRetry(true, function() use ($flock) { return $flock->contains(1,1,2); });
		$flock->remove(1,1,2);
		$this->assertEqualsRetry(false, function() use ($flock) { return $flock->contains(1,1,2); });
	}
	
/*	public function testArchiveOneForward() {
		$this->markTestIncomplete('Unarchiving is buggy in the current version of flockdb');
		
		$flock = $this->getPhlock();
		$this->assertEqualsRetry(true, function() use ($flock) { return $flock->contains(1,1,2); });
		$flock->archive(1,1,2);
		$this->assertEqualsRetry(false, function() use ($flock) { return $flock->contains(1,1,2); });
	}
	
	public function testArchiveManyForward() {
		$this->markTestIncomplete('Unarchiving is buggy in the current version of flockdb');
		
		$flock = $this->getPhlock();
		$this->assertEqualsRetry(true, function() use ($flock) { return $flock->contains(1,1,2); });
		$this->assertEqualsRetry(true, function() use ($flock) { return $flock->contains(1,1,3); });
		$flock->archive(1,1,array(2,3));
		$this->assertEqualsRetry(false, function() use ($flock) { return $flock->contains(1,1,2); });
		$this->assertEqualsRetry(false, function() use ($flock) { return $flock->contains(1,1,3); });
	}
	
	public function testArchiveAllForward() {
		$this->markTestIncomplete('Unarchiving is buggy in the current version of flockdb');
		
		$flock = $this->getPhlock();
		$this->assertEqualsRetry(true, function() use ($flock) { return $flock->contains(1,1,2); });
		$this->assertEqualsRetry(true, function() use ($flock) { return $flock->contains(1,1,3); });
		$flock->archive(1,1,array(2,3));
		$this->assertEqualsRetry(false, function() use ($flock) { return $flock->contains(1,1,2); });
		$this->assertEqualsRetry(false, function() use ($flock) { return $flock->contains(1,1,3); });
	}
	
	public function testArchiveManyBackwards() {
		$this->markTestIncomplete('Unarchiving is buggy in the current version of flockdb');
		
		$flock = $this->getPhlock();
		$this->assertEqualsRetry(true, function() use ($flock) { return $flock->contains(2,1,1); });
		$this->assertEqualsRetry(true, function() use ($flock) { return $flock->contains(4,1,1); });
		$flock->archive(array(2,4),1,1);
		$this->assertEqualsRetry(false, function() use ($flock) { return $flock->contains(2,1,1); });
		$this->assertEqualsRetry(false, function() use ($flock) { return $flock->contains(4,1,1); });
	}
	
	public function testArchiveAllBackwards() {
		$this->markTestIncomplete('Unarchiving is buggy in the current version of flockdb');
		
		$flock = $this->getPhlock();
		$this->assertEqualsRetry(true, function() use ($flock) { return $flock->contains(2,1,1); });
		$this->assertEqualsRetry(true, function() use ($flock) { return $flock->contains(4,1,1); });
		$flock->archive(null,1,1);
		$this->assertEqualsRetry(false, function() use ($flock) { return $flock->contains(2,1,1); });
		$this->assertEqualsRetry(false, function() use ($flock) { return $flock->contains(4,1,1); });
	}*/
	
	public function testSelectAll() {
		$flock = $this->getPhlock();
		$result = $flock->select(1,1,null)->currentPage();
		sort($result);
		$this->assertEquals(array(2,3), $result);
	}
	
	public function testSelectMultiple() {
		$flock = $this->getPhlock();
		$result = $flock->select(1,1,array(2,3))->currentPage();
		sort($result);
		$this->assertEquals(array(2,3), $result);
	}
}
