<?php

$files = array(
	'../src/ExtOverride.js',
	'../src/NS.js',
	'../src/AbstractTreeStore.js',
	'../src/AdjacencyListStore.js ',
	'../src/NestedSetStore.js',
	'../src/GridView.js',
	'../src/GridPanel.js',
	'../src/EditorGridPanel.js',
	'../src/PagingToolbar.js',
	'../src/XType.js'
);

$yui_path = "C:\Program Files\yuicompressor\build\yuicompressor-2.4.2.jar";

$output = '';

foreach ($files as $file) {
	$output .= file_get_contents($file) . PHP_EOL . PHP_EOL;
}

file_put_contents('../TreeGrid.js', $output);

if (isset($yui_path)) {
	exec(
		'java -jar "'.$yui_path.'" --type js --charset utf-8 --nomunge -o ..\TreeGrid.packed.js ..\TreeGrid.js'
	);
}

?>