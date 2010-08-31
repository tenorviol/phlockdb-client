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
		$term = $this->createQueryTerm($source_id, $graph, $destination_ids);
		$operation = new Flock_ExecuteOperation(array(
			'operation_type' => $method,
			'term' => $term
		));
		$operations = new Flock_ExecuteOperations(array(
			'operations' => array($operation),
			'priority' => $priority
		));
		return $this->client()->execute($operations);
	}
	
	public function contains($source_id, $graph, $destination_id) {
		$graph_id = is_int($graph) ? $graph : $this->graphs[$graph];
		return $this->client()->contains($source_id, $graph_id, $destination_id);
	}
	
	public function count($source_id, $graph, $destination_ids) {
		$term = $this->createQueryTerm($source_id, $graph, $destination_ids);
		$operation = new Flock_SelectOperation(array(
			'operation_type'=>Flock_SelectOperationType::SimpleQuery,
			'term'=>$term
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
			'term'=>$term
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
	
	public function createQueryTerm($source_id, $graph, $destination_ids) {
		$graph_id = is_int($graph) ? $graph : $this->graphs[$graph];
		if (!empty($destination_ids)) {
			$destination_ids = $this->packDestinationIds(is_array($destination_ids) ? $destination_ids : array($destination_ids));
		} else {
			$destination_ids = null;
		}
		$client = $this->client();
		$term = new Flock_QueryTerm(array(
			'source_id'=>$source_id,
			'graph_id'=>$graph_id,
			'is_forward'=>true,
			'destination_ids'=>$destination_ids
		));
		return $term;
	}
	
	public function packDestinationIds(array $ids) {
		$pack = '';
		foreach ($ids as $id) {
			for ($i = 0; $i < 8; $i++) {
				$pack .= chr($id & 0xff);
				$id = $id >> 8;
			}
		}
		return $pack;
	}
}

class PhlockdbResult {
	
}
