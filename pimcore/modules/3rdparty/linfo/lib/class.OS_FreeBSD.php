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
 * Mostly complete FreeBSD info class.
 *
 * Note: When Linux compatibility is enabled and /proc is mounted, it only
 * contains process info; none of the hardware/system/network status that Linux /proc has.
 */

class OS_FreeBSD extends OS_BSD_Common{
	
	// Encapsulate these
	protected
		$settings,
		$exec,
		$error,
		$dmesg,
		$version;

	// Start us off
	public function __construct($settings) {

		// Initiate parent
		parent::__construct($settings);

		// We search these folders for our commands
		$this->exec->setSearchPaths(array('/sbin', '/bin', '/usr/bin', '/usr/local/bin', '/usr/sbin'));
		
		// sysctl values we'll access below
		$this->GetSysCTL(array(

			// Has unix timestamp of boot time
			'kern.boottime',

			// Ram stuff
			'vm.vmtotal',
			'vm.loadavg',

			// CPU related
			'hw.model',
			'hw.ncpu',
			'hw.clockrate'
		), false);

		// Save version
		if (preg_match('/^([\d\.]+)/', php_uname('r'), $vm) != 0)
			$this->version = (float) $vm[1];
		
	//	var_dump($this->version); exit;
	}
	
	// This function will likely be shared among all the info classes
	public function getAll() {

		// Return everything, whilst obeying display permissions
		return array(
			'OS' => empty($this->settings['show']['os']) ? '' : $this->getOS(), 			# done
			'Kernel' => empty($this->settings['show']['kernel']) ? '' : $this->getKernel(), 		# done
			'HostName' => empty($this->settings['show']['hostname']) ? '' : $this->getHostName(), 	# done
			'Mounts' => empty($this->settings['show']['mounts']) ? array() : $this->getMounts(), 	# done
			'RAM' => empty($this->settings['show']['ram']) ? array() : $this->getRam(), 		# done
			'Load' => empty($this->settings['show']['load']) ? array() : $this->getLoad(), 		# done
			'Devices' => empty($this->settings['show']['devices']) ? array() : $this->getDevs(), 	# done
			'HD' => empty($this->settings['show']['hd']) ? '' : $this->getHD(), 			# done
			'UpTime' => empty($this->settings['show']['uptime']) ? '' : $this->getUpTime(), 		# done
			'Network Devices' => empty($this->settings['show']['network']) ? array() : $this->getNet(),# done 
			'Raid' => empty($this->settings['show']['raid']) ? '' : $this->getRAID(),	 	# done (gmirror only)
			'processStats' => empty($this->settings['show']['process_stats']) ? array() : $this->getProcessStats(), # lacks thread stats
			'Battery' => empty($this->settings['show']['battery']) ? array(): $this->getBattery(),  	# works
			'CPUArchitecture' => empty($this->settings['show']['cpu']) ? array() : $this->getCPUArchitecture(), # done
			'CPU' => empty($this->settings['show']['cpu']) ? array() : $this->getCPU(), 		# works
			'Temps' => empty($this->settings['show']['temps']) ? array(): $this->getTemps(), 	# TODO,

			// Columns we should leave out. (because finding them out is either impossible or requires root access)
			'contains' => array(
				'drives_rw_stats' => false
			)
		);
	}

	// Return OS type
	private function getOS() {

		// Obviously
		return 'FreeBSD';	
	}
	
	// Get kernel version
	private function getKernel() {
		
		// hmm. PHP has a native function for this
		return php_uname('r');
	}

	// Get host name
	private function getHostName() {
		
		// Take advantage of that function again
		return php_uname('n');
	}

	// Get mounted file systems
	private function getMounts() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Mounted file systems');
		
		// Get result of mount command
		try {
			$res = $this->exec->exec('mount');
		}
		catch (CallExtException $e) {
			$this->error->add('Linfo Core', 'Error running `mount` command');
			return array();
		}
		
		// Parse it
		if (preg_match_all('/^(\S+) on (\S+) \((\w+)(?:, (.+))?\)/m', $res, $m, PREG_SET_ORDER) == 0)
			return array();
		
		// Store them here
		$mounts = array();
		
		// Deal with each entry
		foreach ($m as $mount) {

			// Should we not show this?
			if (in_array($mount[1], $this->settings['hide']['storage_devices']) || in_array($mount[3], $this->settings['hide']['filesystems']))
				continue;
			
			// Get these
			$size = @disk_total_space($mount[2]);
			$free = @disk_free_space($mount[2]);
			$used = $size - $free;
			
			// Optionally get mount options
			if (
				$this->settings['show']['mounts_options'] &&
				!in_array($mount[3], (array) $this->settings['hide']['fs_mount_options']) &&
				isset($mount[4])
			) 
				$mount_options = explode(', ', $mount[4]);
			else 
				$mount_options = array();

			// Might be good, go for it
			$mounts[] = array(
				'device' => $mount[1],
				'mount' => $mount[2],
				'type' => $mount[3],
				'size' => $size ,
				'used' => $used,
				'free' => $free,
				'free_percent' => ((bool)$free != false && (bool)$size != false ? round($free / $size, 2) * 100 : false),
				'used_percent' => ((bool)$used != false && (bool)$size != false ? round($used / $size, 2) * 100 : false),
				'options' => $mount_options
			);
		}

		// Give it
		return $mounts;
	}

	// Get ram usage
	private function getRam(){
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Memory');
		
		// We'll return the contents of this
		$return = array();

		// Start us off at zilch
		$return['type'] = 'Virtual';
		$return['total'] = 0;
		$return['free'] = 0;
		$return['swapTotal'] = 0;
		$return['swapFree'] = 0;
		$return['swapInfo'] = array();

		// Parse the vm.vmtotal sysctl entry
		if (!preg_match_all('/([a-z\ ]+):\s*\(Total: (\d+)\w,? Active:? (\d+)\w\)\n/i', $this->sysctl['vm.vmtotal'], $rm, PREG_SET_ORDER))
			return $return;

		// Parse each entry	
		foreach ($rm as $r) {
			if ($r[1] == 'Real Memory') {
					$return['total'] = $r[2]  * 1024;
					$return['free'] = ($r[2] - $r[3]) * 1024;
			}
		}
		
		// Swap info
		try {
			$swapinfo = $this->exec->exec('swapinfo', '-k');
			// Parse swap info
			@preg_match_all('/^(\S+)\s+(\d+)\s+(\d+)\s+(\d+)/m', $swapinfo, $sm, PREG_SET_ORDER);
			foreach ($sm as $swap) {
				$return['swapTotal'] += $swap[2]*1024;
				$return['swapFree'] += (($swap[2] - $swap[3])*1024);
				$ft = @filetype($swap[1]); // TODO: I'd rather it be Partition or File
				$return['swapInfo'][] = array(
					'device' => $swap[1],
					'size' => $swap[2]*1024,
					'used' => $swap[3]*1024,
					'type' => ucfirst($ft) 
				);
			}
		}
		catch (CallExtException $e) {
			$this->error->add('Linfo Core', 'Error using `swapinfo` to get swap usage');
			// meh
		}

		// Return it
		return $return;
	}
	
	// Get system load
	private function getLoad() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Load Averages');
		
		// Parse sysctl value for system load
		$m = explode(' ', $this->sysctl['vm.loadavg']);
		
		// Give
		return array(
			'now' => $m[1],
			'5min' => $m[2],
			'15min' => $m[3]
		);
	
	}
	
	// Get uptime
	private function getUpTime() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Uptime');
		
		// Use sysctl to get unix timestamp of boot. Very elegant!
		if (preg_match('/^\{ sec \= (\d+).+$/', $this->sysctl['kern.boottime'], $m) == 0)
			return '';
		
		// Boot unix timestamp
		$booted = $m[1];

		// Get it textual, as in days/minutes/hours/etc
		return seconds_convert(time() - $booted) . '; booted ' . date($this->settings['dates'], $booted);
	}

	// RAID Stats
	private function getRAID() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('RAID');
		
		// Store raid arrays here
		$return = array();

		// Counter for each raid array
		$i = 0;
		
		// Gmirror?
		if (array_key_exists('gmirror', $this->settings['raid']) && !empty($this->settings['raid']['gmirror'])) {
			
			try {
				// Run gmirror status program to get raid array status
				$res = $this->exec->exec('gmirror', 'status');

				// Divide that into lines
				$lines = explode("\n", $res);

				// First is worthless
				unset ($lines[0]);

				// Parse the remaining ones
				foreach ($lines as $line => $content) {
					
					// Hitting a new raid definition
					if (preg_match('/^(\w+)\/(\w+)\s+(\w+)\s+(\w+)$/', $content, $m)) {
						$i++;

						switch ($m[1]) {
							case 'mirror':
								$m[1] = 1;
								break;
							case 'stripe':
								$m[1] = 0;
								break;
							default:
								$m[1] = 'unknown';
								break;
						}

						switch ($m[3]) {
						    case 'COMPLETE':
							$m[3] = 'normal';
							break;
						    case 'DEGRADED':
							$m[3] = 'failed';
							break;
						    default:
							$m[3] = 'unknown';
							break;
						}

						// Save result set
						$return[$i] = array(
							'device' => $m[2],
							'level' => $m[1],
							'status' => $m[3],
							'drives' => array(array('drive' => $m[4], 'state' => 'unknown')),
							'size' => 'unknown',
							'count' => '?/?'
						);
					}

					// Hitting a new device in a raid definition
					elseif (preg_match('/^                      (\w+)$/', $content, $m)) {

						// This migh be part of a raid dev; save it if it is
						if (array_key_exists($i, $return))
							$return[$i]['drives'][] = array('drive' => $m[1], 'state' => 'unknown');
					}
				}
			}
			catch (CallExtException $e) {
				$this->error->add('RAID', 'Error using gmirror to get raid info');
				// Don't jump out; allow potential more raid array
				// mechanisms to be gathered and outputted
			}
		}
		
		// Give off raid info
		return $return;
	}

	// Done
	private function getNet() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Network Devices');

		// Store return vals here
		$return = array();
		
		// Use netstat to get info
		try {
			$netstat = $this->exec->exec('netstat', '-nbdi');
		}
		catch(CallExtException $e) {
			$this->error->add('Linfo Core', 'Error using `netstat` to get network info');
			return $return;
		}
		
		// Initially get interfaces themselves along with numerical stats
		if (preg_match_all('/^(\w+\w)\*?\s*\w+\s+<Link\#\w+>(?:\D+|\s+\w+:\w+:\w+:\w+:\w+:\w+\s+)(\w+)\s+(\w+)\s+(\w+)\s+(\w+)\s+(\w+)\s+(\w+)\s+(\w+)\s+(\w+)\s+/m', $netstat, $netstat_match, PREG_SET_ORDER) == 0)
			return $return;

		// Try using ifconfig to get states of the network interfaces
		$statuses = array();
		try {
			// Output of ifconfig command
			$ifconfig = $this->exec->exec('ifconfig', '-a');

			// Set this to false to prevent wasted regexes
			$current_nic = false;

			// Go through each line
			foreach ((array) explode("\n", $ifconfig) as $line) {

				// Approachign new nic def
				if (preg_match('/^(\w+):/', $line, $m) == 1)
					$current_nic = $m[1];

				// Hopefully match its status
				elseif ($current_nic && preg_match('/^\s+status: ([^\$]+)/', $line, $m) == 1) {
					$statuses[$current_nic] = $m[1];
					$current_nic = false;
				}
			}
		}
		catch(CallExtException $e) {}

		// Get type from dmesg boot
		$type = array();
		$type_nics = array();

		// Store the to-be detected nics here
		foreach ($netstat_match as $net)
			$type_nics[] = $net[1];

		// Go through dmesg looking for them
		if (preg_match_all('/^(\w+): <.+>.+on ([a-z]+)\d+/m', $this->dmesg, $type_match, PREG_SET_ORDER)) {
			
			// Go through each
			foreach ($type_match as $type_nic_match) 

				// Is this one of our detected nics?
				if (in_array($type_nic_match[1], $type_nics))

					// Yes; save status
					$type[$type_nic_match[1]] = $type_nic_match[2];
		}


		// Save info
		foreach ($netstat_match as $net) {

			// Determine status
			switch (array_key_exists($net[1], $statuses) ? $statuses[$net[1]] : 'unknown') {

				case 'active':
					$state = 'up';
				break;
				
				case 'inactive':
				case 'no carrier':
					$state = 'down';
				break;

				default:
					$state = 'unknown';
				break;
			}

			// Save info
			$return[$net[1]] = array(

				
				// These came from netstat
				'recieved' => array(
					'bytes' => (int) $net[$this->version >= 8 ? 5 : 4],
					'errors' => $net[3],
					'packets' => $net[2] 
				),
				'sent' => array(
					'bytes' => (int)$net[$this->version >= 8 ? 8 : 7],
					'errors' =>  $net[6],
					'packets' => $net[5] 
				),

				// This came from ifconfig -a
				'state' => $state,

				// And this came from dmeg.
				// TODO: Value for following is usually vague
				'type' => array_key_exists($net[1], $type) ? strtoupper($type[$net[1]]) : 'N/A'
			);
		}

		// Return it
		return $return;
	}

	// Get CPU's
	// I still don't really like how this is done
	// todo: support multiple non-identical cpu's
	private function getCPU() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('CPUs');

		// Store them here
		$cpus = array();
		
		// Stuff it with identical cpus
		for ($i = 0; $i < $this->sysctl['hw.ncpu']; $i++)
			
			// Save each
			$cpus[] = array(
				'Model' => $this->sysctl['hw.model'],
				'MHz' => $this->sysctl['hw.clockrate']
			);
		
		// Return
		return $cpus;
	}
	
	// TODO: Get reads/writes and partitions for the drives
	private function getHD(){
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Drives');

		// Keep them here
		$drives = array();
		
		// Must they change the format of everything with each fucking release?!?!?!?!
		switch ($this->version) {

			case 8.2:
				$cur = false;

				// Each line of dmesg boot log
				foreach ((array) explode("\n", $this->dmesg) as $line) {
					
					// Start of a drive entry which spans multiple lines
					if (preg_match('/^((?:ad|da|acd|cd)\d+) at/', $line, $m)) {
						$cur = array('device' => '/dev/'.$m[1]);	
					}

					// Branding of this drive
					elseif ($cur && preg_match('/^((?:ad|da|acd|cd)\d+): \<([^>]+)\>/', $line, $m)) {
						if ('/dev/'.$m[1] != $cur['device'])
							continue;
						$halves = explode(' ', $m[2]);
						if (count($halves) > 1) {
							$cur['vendor'] = $halves[0];
							$cur['name'] = $halves[1];
						}
						else {
							$cur['vendor'] = false;
							$cur['name'] = $m[1];
						}
					}

					// Lastly the size; gather it and save it
					elseif ($cur && preg_match('/^((?:ad|da|acd|cd)\d+): (\d+)MB/', $line, $m)) {
						if ('/dev/'.$m[1] != $cur['device']) {
							$cur = false;
							continue;
						}	
						$cur['size'] = $m[2] * 1048576;
						$drives[] = $cur;
						$cur = false;
					}
				}
			break;

			default:
				if (preg_match_all('/^((?:ad|da|acd|cd)\d+)\: ((?:\w+|\d+\w+)) \<(\S+)\s+([^>]+)\>/m', $this->dmesg, $m, PREG_SET_ORDER) > 0)
					foreach ($m as $drive) 
						$drives[] = array(
							'name' => $drive[4],
							'vendor' => $drive[3],
							'device' => '/dev/'.$drive[1],
							'size' => preg_match('/^(\d+)MB$/', $drive[2], $m) == 1 ? $m[1] * 1048576 : false,
							'reads' => false,
							'writes' => false
						);
			break;
		}

		// Return
		return $drives;
	}
	
	// Parse dmesg boot log
	private function getDevs() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Hardware Devices');
		
		// Class that does it
		$hw = new HW_IDS(false, '/usr/share/misc/pci_vendors');
		$hw->work('freebsd');
		return $hw->result();
	}
		
	// APM? Seems to only support either one battery of them all collectively
	private function getBattery() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Batteries');

		// Store them here
		$batts = array();
		
		// Get result of program
		try {
			$res = $this->exec->exec('apm', '-abl');
		}
		catch (CallExtException $e) {
			$this->error->add('Linfo Core', 'Error using `apm` battery info');
			return $batts;
		}
		
		// Values from program
		list(, $bat_status, $percentage) = explode("\n", $res);
		
		// Interpret status code
		switch ($bat_status) {
			case 0:
				$status = 'High';
			break;
			case 1:
				$status = 'Low';
			break;
			case 2:
				$status = 'Critical';
			break;	
			case 3:
				$status = 'Charging';
			break;
			default:
				$status = 'Unknown';
			break;	
		}
		
		// Save battery
		$batts[] = array(
			'percentage' => $percentage.'%',
			'state' => $status,
			'device' => 'battery'
		);
			
		// Return
		return $batts;
	}
	
	// Get stats on processes
	private function getProcessStats() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Process Stats');

		// We'll return this after stuffing it with useful info
		$result = array(
			'exists' => true, 
			'totals' => array(
				'running' => 0,
				'zombie' => 0,
				'sleeping' => 0,
				'stopped' => 0,
				'idle' => 0
			),
			'proc_total' => 0,
			'threads' => false // I'm not sure how to get this
		);

		// Use ps
		try {
			// Get it
			$ps = $this->exec->exec('ps', 'ax');

			// Match them
			preg_match_all('/^\s*\d+\s+[\w?]+\s+([A-Z])\S*\s+.+$/m', $ps, $processes, PREG_SET_ORDER);
			
			// Get total
			$result['proc_total'] = count($processes);
			
			// Go through
			foreach ($processes as $process) {
				switch ($process[1]) {
					case 'S':
					case 'I':
						$result['totals']['sleeping']++;
					break;
					case 'Z':
						$result['totals']['zombie']++;
					break;
					case 'R':
					case 'D':
						$result['totals']['running']++;
					break;
					case 'T':
						$result['totals']['stopped']++;
					break;
					case 'W':
						$result['totals']['idle']++;
					break;
				}
			}
		}
		catch (CallExtException $e) {
			$this->error->add('Linfo Core', 'Error using `ps` to get process info');
		}

		// Give
		return $result;
	}
	
	// idk
	private function getTemps() {
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Temperature');
	
	}
	
	/**
	 * getCPUArchitecture
	 * 
	 * @access private
	 * @return string the arch
	 */
	private function getCPUArchitecture() {
		return php_uname('m');
	}
}
