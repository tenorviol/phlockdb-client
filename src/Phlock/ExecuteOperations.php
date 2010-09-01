<?php

class Phlock_ExecuteOperations {
	
	private $client;
	private $priority = Flock_Priority::High;
	private $operations = array();
	
	public function __construct(FlockDBClient $client) {
		$this->client = $client;
	}
	
	public function setPriority($priority) {
		$this->priority = $priority;
	}
	
	public function addOperation(Phlock_ExecuteOperation $operation) {
		$this->operations[] = $operation;
	}
	
	public function apply() {
		$this->client->execute($this->toThrift());
	}
	
	public function toThrift() {
		$operations = array_map(function($o) { return $o->toThrift(); }, $this->operations);
		$execute = new Flock_ExecuteOperations(array(
			'operations' => $operations,
			'priority' => $this->priority
		));
		return $execute;
	}
}
