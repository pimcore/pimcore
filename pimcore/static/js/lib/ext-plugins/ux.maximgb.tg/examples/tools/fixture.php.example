<?php

include dirname(__FILE__).'/sample_data.php';
include dirname(__FILE__).'/Tree.php';

$tree = new Tree();
$sample_size = sizeof($sample_data);
$depth_limit = 3;
$fixture_size = 1000;
$fixture_format = 'php';

if (isset($argv[1])) {
	$fixture_size = (integer)$argv[1];
}
if (isset($argv[2])) {
	$depth_limit = (integer)$argv[2];
	if ($depth_limit < 2) {
		$depth_limit = 2;
	}
}
if (isset($argv[3]) && $argv[3] == 'json') {
	$fixture_format = 'json';
}

for ($i = 0; $i < $fixture_size; $i++) {
	$sample = $sample_data[rand(0, $sample_size - 1)];
	$sample['company'] = $i . '. ' . $sample['company'];
	if ($i / $fixture_size < 0.01) {
		$tree->add($sample);
	}
	else {
		$parent = $tree->getRandomNode();
		while ($parent['_level'] > $depth_limit - 1) {
			$parent = $tree->getParent($parent['_id']);
		}
		$tree->add($sample, $parent['_id']);
	}
}

if ($fixture_format == 'json') {
	$nodes = $tree->getNodes();
	if (empty($nodes)) {
		echo '[]';
	}
	else {
		$json_nodes = array();
		echo '[' . PHP_EOL;
		foreach ($nodes as $node) {
			$json_nodes[] = json_encode($node);
		}
		echo implode(','.PHP_EOL, $json_nodes);
		echo PHP_EOL . ']' . PHP_EOL;
	}
}
else {
	echo "<?php".PHP_EOL;
	echo '$data = ' . var_export($tree->getNodes(), true) . ';'.PHP_EOL;
	echo "?>".PHP_EOL;
}

?>