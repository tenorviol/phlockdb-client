<?php

$GLOBALS['THRIFT_ROOT'] = '/usr/local/thrift-0.2.0/lib/php/src';
require_once 'src/autoload.php';
require_once 'src/Phlock.php';

class Phlock_QueryTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * NOTE: The FlockDB client uses the ruby array method 'pack'
	 * to encode the binary destination ids of the QueryTerm data.
	 * Rather than constructing test expectations manually,
	 * the following uses ruby to do it automatically. The relevant
	 * code in flockdb-client (lib/flock/operations/query_term.rb):
	 *
	 *		term.destination_ids = Array(destination).pack("Q*") if destination
	 */
	public function packDestinationIdsProvider() {
		$id_sets = array(
			array(0),
			array(1),
			array(-1),
			array(0xff),
			array(0xffff),
			array(0xfffffffffffffff),
			array(0xfffffffffffffff),
			array(1,2,3),
			array(0xfffffffffffffff,0xfffffffffffffff,0xfffffffffffffff),
			array(240693764, 392892270, 1802284055, 2012028426),
			array(2141762312, 603720273, 576448750, 1374213717),
		);
		for ($i = 0; $i < 8; $i++) {
			$id_sets[] = array(mt_rand(), mt_rand(), mt_rand(), mt_rand());
		}
		$tests = array();
		foreach ($id_sets as $ids) {
			$cmd = "ruby -e 'print [".implode(',', $ids)."].pack(\"Q*\")'";
			ob_start();
			passthru($cmd);
			$output = ob_get_contents();
			ob_end_clean();
			$tests[] = array($ids, $output);
		}
		return $tests;
	}
	
	/**
	 * @dataProvider packDestinationIdsProvider
	 */
	public function testPackDestinationIdsShouldMatchFlockDBClientMethodOfPackingDestinationIds(array $ids, $expected_pack) {
		$term = new Phlock_QueryTerm(1,1,$ids);
		$pack = $term->toThrift()->destination_ids;
		$this->assertEquals($expected_pack, $pack, 'expected='.urlencode($expected_pack).' actual='.urlencode($pack));
	}
	
	/**
	 * @dataProvider packDestinationIdsProvider
	 */
	public function testUnpackResultIdsShouldMatchFlockDBClientMethod(array $expected_ids, $pack) {
		$client = new FlockDBClient(null);
		$term = new Phlock_QueryTerm(1,1,1);
		$cursor = new Phlock_Cursor($client, $term);
		$unpack = $cursor->unpackResultIds($pack);
		$this->assertEquals($expected_ids, $unpack);
	}
}
