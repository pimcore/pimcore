<?php

/**
 * This file is part of Linfo (c) 2010 Joseph Gillotti.
 * 
 * Linfo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Linfo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Linfo.	If not, see <http://www.gnu.org/licenses/>.
 */

// Constants used for paths and so on...

// Anti hack, as in allow included files to ensure they were included
defined('IN_LINFO') or define('IN_LINFO', true);
defined('IN_INFO') or define('IN_INFO', true); // support old config files

// Are we running from the CLI?
if ((isset($argc) && is_array($argv)) || defined('LINFO_TESTING'))
	defined('LINFO_CLI') or define('LINFO_CLI', true);

// Configure absolute path to local directory
defined('LINFO_LOCAL_PATH') or define('LINFO_LOCAL_PATH', dirname(__FILE__) . '/');

defined('LINFO_CACHE_PATH') or define('LINFO_CACHE_PATH',
	is_writable(dirname(__FILE__) . '/cache/') ? dirname(__FILE__) . '/cache/' : sys_get_temp_dir() . DIRECTORY_SEPARATOR
);

/**
 * Set up class and interface auto loading
 * @param string $class the name of the class being searched fro
 */
function linfoAutoloader($class) {
	// Asuming this is a class
	$class_file = LINFO_LOCAL_PATH . 'lib/class.'.$class.'.php';

	if (is_file($class_file)) {
		require_once $class_file;
		if (class_exists($class))
			return;
		exit('Class '.$class.' not found in '.$class_file."\n");
	}

	// But maybe it's really an interface?
	$interface_file = LINFO_LOCAL_PATH . 'lib/interface.'.$class.'.php';

	if (is_file($interface_file)) {
		require_once $interface_file;
		if (interface_exists($class))
			return;
		exit('Interface '.$interface.' not found in '.$interface_file."\n");
	}
}

// Opt for spl_autoload_register if we have it. Ancient installations
// might not
if (function_exists('spl_autoload_register')) {
	spl_autoload_register('linfoAutoloader');
}
else {
	function __autoload($class) {
		linfoAutoloader($class);
	}
}
