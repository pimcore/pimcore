<?php

include dirname(__FILE__).'/../tools/Tree.php';
include dirname(__FILE__).'/paging.data.php';

$tree = new Tree($data);
$start = 0;
$limit = 20;
$anode = null;

if (isset($_REQUEST['start'])) {
	$start = (integer)$_REQUEST['start'];
}
if (isset($_REQUEST['limit'])) {
	$limit = (integer)$_REQUEST['limit'];
}
if (isset($_REQUEST['anode'])) {
	$anode = (integer)$_REQUEST['anode'];
}

$page_data = null;
if ($anode == null || empty($anode) || $tree->hasNode($anode)) {
	$total = $tree->getChildrenCount($anode);
	$data = $tree->getChildrenPaged($anode, $start, $limit);
	foreach ($data as &$child) {
		unset($child['_parent']);
	}
	$page_data = array(
		'success' => true,
		'total' => $total,
		'data' => $data
	);
}
else {
	$page_data = array(
		'success' => false,
		'error' => 'Unknown node requested.'
	);
}

echo json_encode($page_data);