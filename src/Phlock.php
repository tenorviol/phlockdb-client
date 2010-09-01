<?php

require_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';

require_once $GLOBALS['THRIFT_ROOT'].'/packages/FlockDB/FlockDB.php';

class Phlock {
	
	private $servers;
	private $graphs;
	private $options;
	private $client;
	
	public function __construct(array $servers, array $options = array()) {
		$this->servers = $servers;
		$this->graphs = isset($options['graphs']) ? $options['graphs'] : array();
		unset($options['graphs']);
		$this->options = $options;
	}
	
	public function client() {
		if (!isset($this->client)) {
			list($client, $port) = explode(':', $this->servers[0]);
			
			$socket = new TSocket($client, $port);
			$protocol = new TBinaryProtocol($socket);
			$this->client = new FlockDBClient($protocol);
			
			$socket->open();
		}
		return $this->client;
	}
	
	public function add($source_id, $graph, $destination_ids) {
		$term = $this->createQueryTerm($source_id, $graph, $destination_ids);
		$this->update(Flock_ExecuteOperationType::Add, $term);
	}
	
	public function remove($source_id, $graph, $destination_ids) {
		$term = $this->createQueryTerm($source_id, $graph, $destination_ids);
		$this->update(Flock_ExecuteOperationType::Remove, $term);
	}
	
	public function archive($source_id, $graph, $destination_ids) {
		$term = $this->createQueryTerm($source_id, $graph, $destination_ids);
		$this->update(Flock_ExecuteOperationType::Archive, $term);
	}
	
	public function update($method, Phlock_QueryTerm $term, $priority = Flock_Priority::High) {
		$operations = new Phlock_ExecuteOperations($this->client());
		$operations->setPriority($priority);
		$operations->addOperation(new Phlock_ExecuteOperation($method, $term));
		$operations->apply();
	}
	
	public function contains($source_id, $graph, $destination_id) {
		$graph_id = is_int($graph) ? $graph : $this->graphs[$graph];
		return $this->client()->contains($source_id, $graph_id, $destination_id);
	}
	
	public function size($source_id, $graph, $destination_ids) {
		$cursor = $this->select($source_id, $graph, $destination_ids);
		return $cursor->size();
	}
	
	public function select($source_id, $graph, $destination_ids) {
		$term = $this->createQueryTerm($source_id, $graph, $destination_ids);
		return new Phlock_Cursor($this->client(), $term);
	}
	
	public function createQueryTerm($source, $graph, $destination) {
		$graph_id = is_int($graph) ? $graph : $this->graphs[$graph];
		return new Phlock_QueryTerm($source, $graph_id, $destination);
	}
}
