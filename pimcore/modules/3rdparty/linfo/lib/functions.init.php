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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Linfo.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Keep out hackers...
 */
defined('IN_INFO') or exit;

/**
 * Set up class auto loading
 * @param string $class the name of the class being searched fro
 */
function __autoload($class) {
	
	// Path to where it should be
	$file = LOCAL_PATH . 'lib/class.'.$class.'.php';

	// Load it if it does
	if (is_file($file)) 
		require_once $file;
	else
		exit('File for '.$file.' not found');
	
	// Make sure we have it
	if (!class_exists($class)) {
		if ($class == 'COM')
			exit('You need to enable PHP\'s COM extension');
		else
			exit('Class '.$class.' not found in '.$file);
	}
}


/**
 * Exception for info classes
 */
class GetInfoException extends Exception{}

/**
 * Determine the OS
 * @return string|false if the OS is found, returns the name; Otherwise false
 */
function determineOS() {


	list($os) = explode('_', PHP_OS, 2);

	// This magical constant knows all
	switch ($os) {

		// These are supported
		case 'Linux':
		case 'FreeBSD':
		case 'DragonFly':
		case 'OpenBSD':
		case 'NetBSD':
		case 'Minix':
		case 'Darwin':
		case 'SunOS':
			return PHP_OS;
		break;
		case 'WINNT':
			define('IS_WINDOWS', true);
			return 'Windows';
		break;
		case 'CYGWIN':
			define('IS_CYGWIN', true);
			return 'CYGWIN';
		break;

		// So anything else isn't
		default:
			return false;	
		break;
	}
}

/**
 * Start up class based on result of determineOS
 * @param string $type the name of the operating system
 * @param array $settings linfo settings
 * @return array the system information
 */
function parseSystem($type, $settings) {
	$class = 'OS_'.$type;
	try {
		$info =  new $class($settings);
	}
	catch (GetInfoException $e) {
		exit($e->getMessage());
	}

	return $info;
}

/**
 * Deal with extra extensions
 * @param array $info the system information
 * @param array $settings linfo settings
 */
function runExtensions(&$info, $settings) {

	// Info array is passed by reference so we can edit it directly
	$info['extensions'] = array();
	
	// Are there any extensions configured?
	if(!array_key_exists('extensions', $settings) || count($settings['extensions']) == 0) 
		return;

	// Go through each enabled extension
	foreach((array)$settings['extensions'] as $ext => $enabled) {

		// Is it really enabled?
		if (empty($enabled)) 
			continue;

		// Does the file exist? load it then
		if (file_exists(LOCAL_PATH . 'lib/class.ext.'.$ext.'.php'))
			require_once LOCAL_PATH . 'lib/class.ext.'.$ext.'.php';
		else {
			
			// Issue an error and skip this thing otheriwse
			LinfoError::Fledging()->add('Extension Loader', 'Cannot find file for "'.$ext.'" extension.');
			continue;
		}

		// Name of its class
		$class = 'ext_'.$ext;

		// Make sure it exists
		if (!class_exists($class)) {
			LinfoError::Fledging()->add('Extension Loader', 'Cannot find class for "'.$ext.'" extension.');
			continue;
		}

		// Handle version checking
		$min_version = defined($class.'::LINFO_MIN_VERSION') ? constant($class.'::LINFO_MIN_VERSION') : false; 
		if ($min_version !== false && strtolower(VERSION) != 'svn' && !version_compare(VERSION, $min_version, '>=')) {
			LinfoError::Fledging()->add('Extension Loader', '"'.$ext.'" extension requires at least Linfo v'.$min_version);
			continue;
		}

		// Load it
		$ext_class = new $class();

		// Deal with it
		$ext_class->work();
		
		// Does this edit the $info directly, instead of creating a separate output table type thing?
		if (!defined($class.'::LINFO_INTEGRATE')) {

			// Result
			$result = $ext_class->result();

			// Save result if it's good
			if ($result != false)
				$info['extensions'][$ext] = $result;
		}
	}
}
