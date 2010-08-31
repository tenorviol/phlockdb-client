<?php

class Phlock_SelectOperation {
	
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
	
	public function toThrift() {
		$operation = new Flock_SelectOperation(array(
			'operation_type'=>Flock_SelectOperationType::SimpleQuery,
			'term'=>$this->term->toThrift()
		));
		return array($operation);
	}
}
