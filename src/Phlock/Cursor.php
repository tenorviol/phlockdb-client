<?php

class Phlock_Cursor {
	
	private $client;
	private $term;
	
	public function __construct(FlockDBClient $client, Phlock_QueryTerm $term) {
		$this->client = $client;
		$this->term = $term;
	}
	
	public function size() {
		$result = $this->client->count2(array($this->toThrift()));
		$unpack = unpack('V*', $result);  // one-based array
		return $unpack[1];
	}
	
	public function currentPage() {
		$page = new Flock_Page(array(
			'count'=>10,
			'cursor'=>-1  // IMPORTANT: first page must be -1
		));
		$query = new Flock_SelectQuery(array(
			'operations'=>$this->toThrift(),
			'page'=>$page
		));
		$results = $this->client->select2(array($query));
		$result = $results[0];
		return $this->unpackResultIds($result->ids);
	}
	
	public function toThrift() {
		$operation = new Flock_SelectOperation(array(
			'operation_type'=>Flock_SelectOperationType::SimpleQuery,
			'term'=>$this->term->toThrift()
		));
		return array($operation);
	}
	
	public function unpackResultIds($ids) {
		$results = array();
		$i = 0;
		$i64 = $shift = 0;
		while (isset($ids[$i])) {
			$i64 += ord($ids[$i]) << $shift;
			$shift += 8;
			if ($shift === 64) {
				$results[] = $i64;
				$i64 = $shift = 0;
			}
			$i++;
		}
		return $results;
	}
}
