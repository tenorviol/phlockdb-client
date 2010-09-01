<?php

$GLOBALS['THRIFT_ROOT'] = '/usr/local/thrift-0.2.0/lib/php/src';
require_once 'src/autoload.php';

class Phlock_CursorTest extends PHPUnit_Framework_TestCase {
	
	private function getPhlock() {
		return new Phlock(array('127.0.0.1:7915'));
	}
	
	private $expected = array();
	
	public function setUp() {
		$flock = $this->getPhlock();
		
		for ($i = 50; $i <= 100; $i++) {
			$flock->add(10,1,$i);
			array_unshift($this->expected, $i);
		}
		
		usleep(500000);
	}
	
	public function testPagination() {
		$flock = $this->getPhlock();
		$cursor = $flock->select(10,1,null);
		
		$total_ids = 0;
		$page = $cursor->currentPage();
		rsort($page);
		$this->assertEquals(array_slice($this->expected, $total_ids, 10), $page);
		$total_ids += count($page);
		while ($cursor->hasNextPage()) {
			$cursor->nextPage();
			$page = $cursor->currentPage();
			rsort($page);
			$this->assertEquals(array_slice($this->expected, $total_ids, 10), $page);
			$total_ids += count($page);
		}
		$this->assertEquals(count($this->expected), $total_ids);
	}
}
