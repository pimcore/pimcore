<?php

/*
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
 * 
*/


defined('IN_INFO') or exit; 


/*
 * Get info on a Minix system
 * --- 
 * Note: the cli tools on minix are so meager that getting real detail
 * out of it (like nic stats / fs types / etc) is either difficult or
 * impossible. Nevertheless, this is my attempt at doing so.
 */

class OS_Minix {

	// Store these here
	protected
		$settings,
		$exec,
		$error;
	
	// Start us off by localizing the settings and initializing the external
	// application running class
	function __construct($settings) {

		// Localize settings
		$this->settings = $settings;
		
		// Start up external app loader
		$this->exec = new CallExt;

		// Have it look in these places
		$this->exec->setSearchPaths(array('/usr/bin', '/usr/local/bin', '/bin'));
	}

	// Get the information
	function getAll() {
		
		// Return everything, whilst obeying display permissions
		return array(
			'OS' => empty($this->settings['show']['os']) ? '' : $this->getOS(),				# done
			'Kernel' => empty($this->settings['show']['kernel']) ? '' : $this->getKernel(),			# done
			'HostName' => empty($this->settings['show']['hostname']) ? '' : $this->getHostName(),		# done
			'Mounts' => empty($this->settings['show']['mounts']) ? array() : $this->getMounts(),		# lacking info
			'Network Devices' => empty($this->settings['show']['network']) ? array() : $this->getNet(),	# lacking info
			'CPU' => array(),
			'Load' => array(),

			// More to follow in further commits
		);
	}

	// Operating system
	private function getOS() {
		return 'Minix';
	}

	// Take advantage of php_uname for kernel
	private function getKernel() {
		return php_uname('r');
	}

	// Use that function again for host name
	private function getHostName() {
		return php_uname('n');
	}

	// Mounted file systems
	// --- 
	// Note: the `mount` command does not have file system type
	// and php's disk_free_space/disk_total_space functions don't seem
	// to work here
	private function getMounts() {

		// Try using the `mount` command to get mounted file systems
		try {
			$res = $this->exec->exec('mount');
		}
		catch (CallExtException $e){
			return array();
		}

		// Try matching up the output
		if (preg_match_all('/^(\S+) is .+ mounted on (\S+) \(.+\)$/m', $res, $mount_matches, PREG_SET_ORDER) == 0)
			return array();

		// Store them here
		$mounts = array();
		
		// Go through each match
		foreach ($mount_matches as $mount) {

			// These might be a waste
			$size = @disk_total_space($mount[2]); 
			$free = @disk_free_space($mount[2]); 
			$used = $size - $free; 

			// Save it
			$mounts[] = array(
				'device' => $mount[1],
				'mount' => $mount[2],
				'type' => '?', // Haven't a clue on how to get this on minix
				'size' => $size,
				'used' => $used,
				'free' => $free,
				'free_percent' => ((bool)$free != false && (bool)$size != false ? round($free / $size, 2) * 100 : false), 
				'used_percent' => ((bool)$used != false && (bool)$size != false ? round($used / $size, 2) * 100 : false) 
			);
		}
		
		// Return them
		return $mounts;
	}

	// Get network interfaces
	// --- 
	// netstat isn't installed by default and ifconfig doesn't have
	// much functionality for viewing status, so I can't seem to get
	// more than just name of interface
	private function getNet() {

		// Try getting it. 
		try {
			$res = $this->exec->exec('ifconfig', '-a');
		}
		catch (CallExtException $e){
			return array();
		}

		// Match up the entries
		if (preg_match_all('/^([^:]+)/m', $res, $net_matches, PREG_SET_ORDER) == 0)
			return array();
		
		// Store them here
		$nets = array();
		
		// Go through each
		foreach ($net_matches as $net) {

			// Save this one
			$nets[$net[1]] = array(
				'state' => '?',
				'type' => '?'
			);
		}
		
		// Give them
		return $nets;
	}
}
