<?php

class Phlock_QueryTerm {
	
	private $source;
	private $graph_id;
	private $destination;
	private $states;
	
	public function __construct($source, $graph_id, $destination, array $states = null) {
		$this->source = $source;
		$this->graph_id = $graph_id;
		$this->destination = $destination;
		$this->states = $states;
	}
	
	public function toThrift() {
		$forward = is_numeric($this->source);
		if ($forward) {
			$source_id = $this->source;
			$destination_ids = $this->destination;
		} else {
			$source_id = $this->destination;
			$destination_ids = $this->source;
		}
		if ($destination_ids) {
			$destination_ids = $this->packDestinationIds(is_array($destination_ids) ? $destination_ids : array($destination_ids));
		}
		$term = new Flock_QueryTerm(array(
			'source_id'=>$source_id,
			'graph_id'=>$this->graph_id,
			'is_forward'=>$forward,
			'destination_ids'=>$destination_ids,
			'states'=>$this->states
		));
		return $term;
	}
	
	private function packDestinationIds(array $ids) {
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
