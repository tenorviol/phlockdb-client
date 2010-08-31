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
		$this->update(Flock_ExecuteOperationType::Add, $source_id, $graph, $destination_ids);
	}
	
	public function remove($source_id, $graph, $destination_ids) {
		$this->update(Flock_ExecuteOperationType::Remove, $source_id, $graph, $destination_ids);
	}
	
	public function update($method, $source_id, $graph, $destination_ids, $priority = Flock_Priority::High) {
		$operations = new Phlock_ExecuteOperations();
		$operations->setPriority($priority);
		$term = $this->createQueryTerm($source_id, $graph, $destination_ids);
		$operations->addOperation(new Phlock_ExecuteOperation($method, $term));
		return $this->client()->execute($operations->toThrift());
	}
	
	public function contains($source_id, $graph, $destination_id) {
		$graph_id = is_int($graph) ? $graph : $this->graphs[$graph];
		return $this->client()->contains($source_id, $graph_id, $destination_id);
	}
	
	public function count($source_id, $graph, $destination_ids) {
		$term = $this->createQueryTerm($source_id, $graph, $destination_ids);
		$operation = new Flock_SelectOperation(array(
			'operation_type'=>Flock_SelectOperationType::SimpleQuery,
			'term'=>$term->toThrift()
		));
		$result = $this->client()->count2(array(array($operation)));
		$unpack = unpack('V*', $result);  // one-based array
		return $unpack[1];
	}
	
	public function select($source_id, $graph, $destination_ids) {
		$term = $this->createQueryTerm($source_id, $graph, $destination_ids);
		$term->state_ids = array(Flock_EdgeState::Positive);
		$operation = new Flock_SelectOperation(array(
			'operation_type'=>Flock_SelectOperationType::SimpleQuery,
			'term'=>$term->toThrift()
		));
		$page = new Flock_Page(array(
			'count'=>10,
			'cursor'=>-1  // IMPORTANT: first page must be -1
		));
		$query = new Flock_SelectQuery(array(
			'operations'=>array($operation),
			'page'=>$page
		));
		$results = $this->client()->select2(array($query));
		return new PhlockdbResult($results[0]);
	}
	
	public function createQueryTerm($source, $graph, $destination) {
		$graph_id = is_int($graph) ? $graph : $this->graphs[$graph];
		return new Phlock_QueryTerm($source, $graph_id, $destination);
	}
}

class PhlockdbResult {
	
}
