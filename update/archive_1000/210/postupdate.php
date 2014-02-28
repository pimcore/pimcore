<?php

// empty document cache
Pimcore_Model_Cache::clearAll();

// empty cache directory
$files = scandir(PIMCORE_CACHE_DIRECTORY);
foreach ($files as $file) {
	if(is_file(PIMCORE_CACHE_DIRECTORY."/".$file)) {
		unlink(PIMCORE_CACHE_DIRECTORY."/".$file);
	}
}

?>