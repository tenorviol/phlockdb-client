<?php

class Phlock_Cursor implements Iterator {
	
	private $client;
	private $term;
	private $cursor = -1;  // IMPORTANT: starting cursor
	private $result = null;
	
	private $page;
	private $page_size;
	private $page_marker;
	
	public function __construct(FlockDBClient $client, Phlock_QueryTerm $term) {
		$this->client = $client;
		$this->term = $term;
	}
	
	public function size() {
		$result = $this->client->count2(array($this->toThrift()));
		$unpack = unpack('V*', $result);  // one-based array
		return $unpack[1];
	}
	
	public function current() {
		if ($this->page_marker >= $this->page_size) {
			$this->nextPage();
			$this->getResult();
		}
		return $this->page[$this->page_marker];
	}
	
	public function key() {
		return "$this->cursor:$this->page_marker";
	}
	
	public function next() {
		$this->page_marker++;
	}
	
	public function rewind() {
		$cursor = -1;
		$result = null;
	}
	
	public function valid() {
		$this->getResult();
		return $this->page_marker < $this->page_size || $this->hasNextPage();
	}
	
	public function currentPage() {
		$result = $this->getResult();
		return $this->page;
	}
	
	public function hasNextPage() {
		return (bool)$this->getResult()->next_cursor;
	}
	
	public function nextPage() {
		$this->cursor = $this->getResult()->next_cursor;
		$this->result = null;
	}
	
	private function getResult() {
		if ($this->result === null) {
			$this->result = $this->query();
			$this->page = $this->unpackResultIds($this->result->ids);
			$this->page_size = count($this->page);
			$this->page_marker = 0;
		}
		return $this->result;
	}
	
	private function query() {
		$page = new Flock_Page(array(
			'count'=>10,
			'cursor'=>$this->cursor
		));
		$query = new Flock_SelectQuery(array(
			'operations'=>$this->toThrift(),
			'page'=>$page
		));
		$results = $this->client->select2(array($query));
		return $results[0];
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
