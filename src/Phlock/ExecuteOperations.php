<?php

class Phlock_ExecuteOperations {
	
	private $priority = Flock_Priority::High;
	private $operations = array();
	
	public function setPriority($priority) {
		$this->priority = $priority;
	}
	
	public function addOperation(Phlock_ExecuteOperation $operation) {
		$this->operations[] = $operation;
	}
	
	public function toThrift() {
		$operations = array();
		foreach ($this->operations as $operation) {
			$operations[] = $operation->toThrift();
		}
		$execute = new Flock_ExecuteOperations(array(
			'operations' => $operations,
			'priority' => $this->priority
		));
		return $execute;
	}
}
