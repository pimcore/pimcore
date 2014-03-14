<?php

/*
 * This file is part of Linfo (c) 2011 Joseph Gillotti.
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


class OS_DragonFly extends OS_BSD_Common{
	
	// Encapsulate these
	protected
		$settings,
		$exec,
		$error,
		$dmesg;

	// Start us off
	public function __construct($settings) {

		// Initiate parent
		parent::__construct($settings);

		// We search these folders for our commands
		$this->exec->setSearchPaths(array('/sbin', '/bin', '/usr/bin', '/usr/local/bin', '/usr/sbin'));
		
		// sysctl values we'll access below
		$this->GetSysCTL(array(
			'kern.boottime',
			'vm.loadavg',
			'hw.model',
			'hw.ncpu',
			'hw.clockrate'
		), false);
	}
	
	// This function will likely be shared among all the info classes
	public function getAll() {

		// Return everything, whilst obeying display permissions
		return array(
			'OS' => empty($this->settings['show']['os']) ? '' : $this->getOS(), 			
			'Kernel' => empty($this->settings['show']['kernel']) ? '' : $this->getKernel(), 		
			'HostName' => empty($this->settings['show']['hostname']) ? '' : $this->getHostName(), 	
			'Mounts' => empty($this->settings['show']['mounts']) ? array() : $this->getMounts(), 	
			'RAM' => empty($this->settings['show']['ram']) ? array() : $this->getRam(), 		
			'Load' => empty($this->settings['show']['load']) ? array() : $this->getLoad(), 		
			'Devices' => empty($this->settings['show']['devices']) ? array() : $this->getDevs(), 	
			'HD' => empty($this->settings['show']['hd']) ? '' : $this->getHD(), 			
			'UpTime' => empty($this->settings['show']['uptime']) ? '' : $this->getUpTime(), 		
			'Network Devices' => empty($this->settings['show']['network']) ? array() : $this->getNet(), 
			'processStats' => empty($this->settings['show']['process_stats']) ? array() : $this->getProcessStats(), 
			'CPUArchitecture' => empty($this->settings['show']['cpu']) ? array() : $this->getCPUArchitecture(), 
			'CPU' => empty($this->settings['show']['cpu']) ? array() : $this->getCPU(), 		
			'Temps' => empty($this->settings['show']['temps']) ? array(): $this->getTemps(), 	

			// Columns we should leave out. (because finding them out is either impossible or requires root access)
			'contains' => array (
				'drives_rw_stats' => false,
				'nic_type' => false
			)
		);
	}

	// Return OS type
	private function getOS() {

		// Obviously
		return 'DragonFly BSD';	
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

		// Get swap

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
	}

	// Done
	private function getNet() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Network Devices');
		
		// Use netstat to get nic names and stats
		try {
			$netstat = $this->exec->exec('netstat', '-nibd');
		}
		catch (CallExtException $e) {
			$this->error->add('Linfo Core', 'error using netstat');
			return array();
		}

		// Store nics here
		$nets = array();

		// Match that up
		if (!preg_match_all('/^([\da-z]+\*?)\s+\d+\s+<Link#\d+>(?:\s+[a-z0-9:]+)?\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)$/m', $netstat, $netstat_m, PREG_SET_ORDER)) 
			return array();

		// Go through each match
		foreach ($netstat_m as $m) 
			$nets[$m[1]] = array(
				'recieved' => array(
					'bytes' => $m[4],
					'errors' => $m[3],
					'packets' => $m[2] 
				),
				'sent' => array(
					'bytes' => $m[7],
					'errors' =>  $m[6],
					'packets' => $m[5] 
				),
				'state' => 'unknown',
				'type' => 'N/A'
			);

		// Try getting the statuses with ifconfig
		try {
			
			// Store current nic here
			$current_nic = false;

			// Run teh shit
			$ifconfig = $this->exec->exec('ifconfig', '-a');

			// Go through each line
			foreach (explode("\n", $ifconfig) as $line) {

				// Approaching new nic?
				if (preg_match('/^([a-z0-9]+):/', $line, $m)) {

					// Only give a shit about nics we detected above
					if (array_key_exists($m[1], $nets))
						$current_nic = $m[1];
					else
						$current_nic = false;
				}

				// In a nick and found a status entry
				elseif ($current_nic && preg_match('/^\s+status: ([^$]+)$/', $line, $m)) {
					
					// Decide what it is and save it
					switch ($m[1]) {
						case 'active':
							$nets[$current_nic]['state'] = 'up';
						break;
						case 'inactive':
						case 'no carrier':
							$nets[$current_nic]['state'] = 'down';
						break;
						default:
							$nets[$current_nic]['state'] = 'unknown';
						break;
					}

					// Don't waste further time until we find another nic entry
					$current_nic = false; 
				}
			}
		}
		catch (CallExtException $e) {
			$this->error->add('Linfo Core', 'error using ifconfig to get nic statuses');
		}

		// Give nets
		return $nets;
	}

	// Get CPU's
	// I still don't really like how this is done
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
	private function getHD() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Drives');
		
		return array();
	}
	
	// Parse dmesg boot log
	private function getDevs() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Hardware Devices');

		$hw = new HW_IDS($usb_ids, '/usr/share/misc/pci_vendors');
		$hw->work('dragonfly');
		return $hw->result();
	}
		
	// APM? Seems to only support either one battery of them all collectively
	private function getBattery() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Batteries');

		return array();
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
