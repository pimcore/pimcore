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
 * Exception for CallExt class
 */
class CallExtException extends Exception {}

/**
 * Class used to call external programs 
 */
class CallExt {

	/**
	 * Maintain a count of how many external programs we call
	 * 
	 * @var int
	 * @access public
	 */
	public static $callCount = 0;
	
	/**
	 * Store results of commands here to avoid calling them more than once
	 * 
	 * @var array
	 * @access protected
	 */
	protected $cliCache = array();

	/**
	 * Store paths to look for executables here
	 * 
	 * @var array
	 * @access protected
	 */
	protected $searchPaths = array();

	/**
	 * Say where we'll search for execs
	 *
	 * @param array $paths list of paths
	 */
	public function setSearchPaths($paths) {
		
		// Gain access to settings
		global $settings;
		
		// Merge in possible custom paths
		if (is_array($settings['additional_paths']) && count($settings['additional_paths']) > 0)
			$paths = array_merge($settings['additional_paths'], $paths);

		// Make sure they all have a trailing slash
		foreach ($paths as $k => $v)
			$paths[$k] .= substr($v, -1) == '/' ? '' : '/';

		// Save them
		$this->searchPaths = $paths;
	}
	
	/**
	 * Run a command and cache its output for later
	 *
	 * @throws CallExtException
	 * @param string $name name of executable to call
	 * @param string $switches command arguments
	 */
	public function exec($name, $switches = '') {
		
		// Need settings 
		global $settings;

		// Sometimes it is necessary to call a program with sudo 
		$attempt_sudo = array_key_exists('sudo_apps', $settings) && in_array($name, $settings['sudo_apps']);
		
		// Have we gotten it before?
		if (array_key_exists($name.$switches, $this->cliCache))
			return $this->cliCache[$name.$switches];
		
		// Try finding the exec
		foreach ($this->searchPaths as $path) {

			// Found it; run it
			if (is_file($path.$name) && is_executable($path.$name)) {

				// Complete command, path switches and all
				$command = "$path$name $switches";

				// Sudoing?
				$command = $attempt_sudo ? locate_actual_path(array_append_string($this->searchPaths, 'sudo', '%2s%1s')) . ' ' . $command : $command;

				// Result of command
				$result = `$command`;

				// Increment call count
				self::$callCount++;

				// Cache that
				$this->cliCache[$name.$switches] = $result;

				// Give result
				return $result;

				// Redundancy
				break;
			}
		}

		// Never got it
		throw new CallExtException('Exec `'.$name.'\' not found');
	}
}
