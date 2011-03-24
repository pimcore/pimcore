<?php

/**
 * Combined nested set / adjacency list tree.
 */
class Tree
{
	private $data;
	
	public function __construct($data = array())
	{
		$this->data = $data;
	}
	
	public function getNodes()
	{
		return array_values($this->data);
	}
	
	public function hasNode($node_id)
	{
		return isset($this->data[$node_id]);
	}
	
	public function getNode($node_id)
	{
		return $this->data[$node_id];
	}
	
	public function getRandomNode()
	{
		$result = null;
		if (!empty($this->data)) {
			$ids = array_keys($this->data);
			$result = $this->getNode($ids[rand(0, sizeof($ids) - 1)]);
		}
		return $result;
	}
	
	public function add($node, $parent_id = null)
	{
		$node['_id'] = $this->generateId();
		
		if ($parent_id) {
			$parent = &$this->data[$parent_id];
			$this->shiftNodesRight($parent['_rgt']);
			$node['_parent'] = $parent_id;
			$node['_level'] = $parent['_level'] + 1;
			$node['_lft'] = $parent['_rgt'] - 2;
			$node['_rgt'] = $node['_lft'] + 1;
			$parent['_is_leaf'] = false;
			$node['_is_leaf'] = true; 
		}
		else {
			$node['_parent'] = null;
			$node['_level'] = 1;
			$node['_lft'] = $this->getMaxRightPos() + 1;
			$node['_rgt'] = $node['_lft'] + 1;
			$node['_is_leaf'] = true; 
		}
		
		$this->data[$node['_id']] = $node;
	}
	
	public function getParent($node_id)
	{
		$parent = null;
		
		if (
			isset($this->data[$node_id]) && 
			isset($this->data[$this->data[$node_id]['_parent']])
		) {
			$parent = $this->data[$this->data[$node_id]['_parent']];
		}
		
		return $parent;
	}
	
	public function getChildren($node_id = null)
	{
		$children = null;
		
		if ($node_id)
		{
			$children = array();
			foreach ($this->data as $id => $node) {
				if ($node['_parent'] == $node_id) {
					$children[] = $node;
				}
			}
		}
		else {
			$children = $this->getRoots();
		}
		
		return $children;
	}
	
	public function getRoots()
	{
		$roots = array();
		
		foreach ($this->data as $id => $node) {
			if ($node['_parent'] === null) {
				$roots[] = $node;
			}
		}
		
		return $roots;
	}
	
	public function getChildrenPaged($node_id = null, $start = null, $limit = null)
	{
		$children = $this->getChildren($node_id);
		if (!$start) {
			$start = 0;
		}
		if (!$limit) {
			$limit = sizeof($children);
		}
		return array_slice($children, $start, $limit);
	}
	
	public function getChildrenCount($node_id = null)
	{
		$children = $this->getChildren($node_id);
		return sizeof($children);
	}
	
	private function generateId()
	{
		return empty($this->data) ? 1 : max(array_keys($this->data)) + 1;
	}
	
	private function getMinLeftPos()
	{
		$result = 0;
		
		if (!empty($this->data)) {
			$values = array();
			foreach ($this->data as $id => $node) {
				$values[] = $node['_lft'];
			}
			$result = min($values);
		}
		
		return $result;
	}
	
	private function getMaxRightPos()
	{
		$result = 0;
		
		if (!empty($this->data)) {
			$values = array();
			foreach ($this->data as $id => $node) {
				$values[] = $node['_rgt'];
			}
			$result = max($values);
		}
		
		return $result;
	}
	
	private function shiftNodesRight($start_from, $offset = 2)
	{
		foreach ($this->data as $id => &$node) {
			if ($node['_lft'] >= $start_from) {
				$node['_lft'] += $offset;
			}
			if ($node['_rgt'] >= $start_from) {
				$node['_rgt'] += $offset;
			}
		}
	}
}

?>