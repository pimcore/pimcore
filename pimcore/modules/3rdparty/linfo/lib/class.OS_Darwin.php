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
 * Alpha osx class
 * Differs very slightly from the FreeBSD, especially in the fact that
 * only root can access dmesg
 */

class OS_Darwin extends OS_BSD_Common{
	
	// Encapsulate these
	protected
		$settings,
		$exec,
		$error,
		$dmesg;

	// Start us off
	public function __construct($settings) {

		// Instantiate parent
		parent::__construct($settings);

		// We search these folders for our commands
		$this->exec->setSearchPaths(array('/sbin', '/bin', '/usr/bin', '/usr/sbin'));

		// We need these sysctl values
		$this->GetSysCTL(array(
			'machdep.cpu.vendor',
			'machdep.cpu.brand_string',
			'hw.cpufrequency',
			'hw.ncpu',
			'vm.swapusage',
			'hw.memsize',
			'hw.usermem',
			'kern.boottime',
			'vm.loadavg',
			'hw.model'
		),false);
	}
	
	// This function will likely be shared among all the info classes
	public function getAll() {

		// Return everything, whilst obeying display permissions
		return array(
			'OS' => empty($this->settings['show']) ? '' : $this->getOS(), 			# done
			'Kernel' => empty($this->settings['show']) ? '' : $this->getKernel(), 		# done
			'HostName' => empty($this->settings['show']) ? '' : $this->getHostName(), 	# done
			'Mounts' => empty($this->settings['show']) ? array() : $this->getMounts(), 	# done
			'Network Devices' => empty($this->settings['show']) ? array() : $this->getNet(),# done (possibly missing nics)
			'UpTime' => empty($this->settings['show']) ? '' : $this->getUpTime(), 		# done
			'Load' => empty($this->settings['show']) ? array() : $this->getLoad(), 		# done
			'processStats' => empty($this->settings['show']['process_stats']) ? array() : $this->getProcessStats(), # lacks thread stats
			'CPU' => empty($this->settings['show']) ? array() : $this->getCPU(), 		# done
			'RAM' => empty($this->settings['show']) ? array() : $this->getRam(), 		# done
			'Model' => empty($this->settings['show']) ? false : $this->getModel(), 		# done
			'Battery' => empty($this->settings['show']['battery']) ? array(): $this->getBattery(), # done
			'HD' => empty($this->settings['show']['hd']) ? '' : $this->getHD(),
			/*
			'Devices' => empty($this->settings['show']) ? array() : $this->getDevs(), 	# todo
			'RAID' => empty($this->settings['show']) ? '' : $this->getRAID(),	 	# todo(
			'Battery' => empty($this->settings['show']) ? array(): $this->getBattery(),  	# todo
			'Temps' => empty($this->settings['show']) ? array(): $this->getTemps(), 	# TODO
			*/
			
			// Columns we should leave out.
			'contains' => array(
				'hw_vendor' => false,
				'drives_rw_stats' => false,
				'drives_vendor' => false
			)
		);
	}

	// Operating system
	public function getOS() {
		return 'Darwin (Mac OS X)';
	}

	// Kernel version
	public function getKernel() {
		return php_uname('r');
	}

	// Hostname
	public function getHostname() {
		return php_uname('n');
	}

	// Get mounted file systems
	public function getMounts() {
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
		if (preg_match_all('/(.+)\s+on\s+(.+)\s+\((\w+).*\)\n/i', $res, $m, PREG_SET_ORDER) == 0)
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
			
			// Might be good, go for it
			$mounts[] = array(
				'device' => $mount[1],
				'mount' => $mount[2],
				'type' => $mount[3],
				'size' => $size ,
				'used' => $used,
				'free' => $free,
				'free_percent' => ((bool)$free != false && (bool)$size != false ? round($free / $size, 2) * 100 : false),
				'used_percent' => ((bool)$used != false && (bool)$size != false ? round($used / $size, 2) * 100 : false)
			);
		}

		// Give it
		return $mounts;
		
	}

	// Get network interfaces
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
		//
		// Example output:
		// Name  Mtu   Network       Address            Ipkts Ierrs     Ibytes    Opkts Oerrs     Obytes  Coll Drop
		// lo0   16384 <Link#1>                          1945     0     429565     1945     0     429565     0 
		// en0   1500  <Link#4>    58:b0:35:f9:fd:2b        0     0          0        0     0      59166     0 
		// fw0   4078  <Link#6>    d8:30:62:ff:fe:f5:c8:9c        0     0          0        0     0        346     0 
		if (preg_match_all(
			'/^
			([a-z0-9*]+)\s*  # Name
			\w+\s+           # Mtu
			<Link\#\w+>      # Network
			(?:\D+|\s+\w+:\w+:\w+:\w+:\w+:\w+\s+)  # MAC address
			(\w+)\s+  # Ipkts
			(\w+)\s+  # Ierrs
			(\w+)\s+  # Ibytes
			(\w+)\s+  # Opkts
			(\w+)\s+  # Oerrs
			(\w+)\s+  # Obytes
			(\w+)\s+  # Coll
			(\w+)?\s*  # Drop
			$/mx', $netstat, $netstat_match, PREG_SET_ORDER) == 0)
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
				elseif ($current_nic && preg_match('/status: (\w+)$/', $line, $m) == 1) {
					$statuses[$current_nic] = $m[1];
					$current_nic = false;
				}
			}
		}
		catch(CallExtException $e) {}


		// Save info
		foreach ($netstat_match as $net) {

			// Determine status
			switch (array_key_exists($net[1], $statuses) ? $statuses[$net[1]] : 'unknown') {

				case 'active':
					$state = 'up';
				break;
				
				case 'inactive':
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
					'bytes' => $net[4],
					'errors' => $net[3],
					'packets' => $net[2] 
				),
				'sent' => array(
					'bytes' => $net[7],
					'errors' =>  $net[6],
					'packets' => $net[5] 
				),

				// This came from ifconfig -a
				'state' => $state,

				// Not sure where to get his
				'type' => '?'
			);
		}

		// Return it
		return $return;
	
	}

	// Get uptime 
	private function getUpTime() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Uptime');
		
		// Extract boot part of it
		if (preg_match('/^\{ sec \= (\d+).+$/', $this->sysctl['kern.boottime'], $m) == 0)
			return '';
		
		// Boot unix timestamp
		$booted = $m[1];

		// Get it textual, as in days/minutes/hours/etc
		return seconds_convert(time() - $booted) . '; booted ' . date('m/d/y h:i A', $booted);
	}
	
	// Get system load
	private function getLoad() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Load Averages');

		// Parse it
		if (preg_match('/([\d\.]+) ([\d\.]+) ([\d\.]+)/', $this->sysctl['vm.loadavg'], $m) == 0)
			return array();
		
		// Give
		return array(
			'now' => $m[1],
			'5min' => $m[2],
			'15min' => $m[3]
		);
	
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

	// Get cpus
	public function getCPU() {
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('CPUs');

		// Store them here
		$cpus = array();
		
		// The same one multiple times
		for ($i = 0; $i < $this->sysctl['hw.ncpu']; $i++)
			$cpus[] = array(
				'Model' => $this->sysctl['machdep.cpu.brand_string'],
				'MHz' => $this->sysctl['hw.cpufrequency'] / 1000000,
				'Vendor' => $this->sysctl['machdep.cpu.vendor']
				
			);

		return $cpus;
	}
	
	// Get ram usage
	private function getRam(){
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Memory');

		// Start us off
		$return = array();
		$return['type'] = 'Physical';
		$return['total'] = $this->sysctl['hw.memsize'];
		$return['free'] =  $this->sysctl['hw.memsize'] - $this->sysctl['hw.usermem'];
		$return['swapTotal'] = 0;
		$return['swapFree'] = 0;
		$return['swapInfo'] = array();


		// Sort out swap
		if (preg_match('/total = ([\d\.]+)M\s+used = ([\d\.]+)M\s+free = ([\d\.]+)M/', $this->sysctl['vm.swapusage'], $swap_match)) {
			list(, $swap_total, $swap_used, $swap_free) = $swap_match;
			$return['swapTotal'] = $swap_total * 1000000;
			$return['swapFree'] = $swap_free * 1000000;
		}

		
		// Return ram info
		return $return;
	
	}

	// Model of mac
	private function getModel() {
		if (preg_match('/^([a-zA-Z]+)/', $this->sysctl['hw.model'], $m))
			return $m[1];
		else
			return $this->sysctl['hw.model'];
	}
	
	// Battery
	private function getBattery() {
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Battery');
		
		// Store any we find here
		$batteries = array();
		
		// Use system profiler to get info
		try {
			$res = $this->exec->exec('system_profiler', ' SPPowerDataType');
		}
		catch(CallExtException $e) {
			$this->error->add('Linfo Batteries', 'Error using `system_profiler SPPowerDataType` to get battery info');
			return array();
		}

		// Lines
		$lines = explode("\n", $res);

		// Hunt
		$bat = array();
		$in_bat_field = false;

		// Parse teh fucka
		for ($i = 0, $num_lines = count($lines); $i < $num_lines; $i++) {
			if (preg_match('/^\s+Battery Information/', $lines[$i])) {
				$in_bat_field = true;
				continue;
			}
			elseif(preg_match('/^\s+System Power Settings/', $m)) {
				$in_bat_field = false;
				break;
			}
			elseif ($in_bat_field && preg_match('/^\s+Fully charged: ([a-zA-Z]+)/', $lines[$i], $m)) 
				$bat['charged'] = $m[1] == 'Yes';
			elseif ($in_bat_field && preg_match('/^\s+Charging: ([a-zA-Z]+)/', $lines[$i], $m)) 
				$bat['charging'] = $m[1] == 'Yes';
			elseif($in_bat_field && preg_match('/^\s+Charge remaining \(mAh\): (\d+)/', $lines[$i], $m)) 
				$bat['charge_now'] = (int) $m[1];
			elseif($in_bat_field && preg_match('/^\s+Full charge capacity \(mAh\): (\d+)/', $lines[$i], $m)) 
				$bat['charge_full'] = (int) $m[1];
			elseif($in_bat_field && preg_match('/^\s+Serial Number: ([A-Z0-9]+)/', $lines[$i], $m)) 
				$bat['serial'] = $m[1];
			elseif($in_bat_field && preg_match('/^\s+Manufacturer: (\w+)/', $lines[$i], $m)) 
				$bat['vendor'] = $m[1];
			elseif($in_bat_field && preg_match('/^\s+Device name: (\w+)/', $lines[$i], $m)) 
				$bat['name'] = $m[1];
		}

		// If we have what we need, append
		if (isset($bat['charge_full']) && isset($bat['charge_now']) && isset($bat['charged']) && isset($bat['charging'])) 
			$batteries[] = array(
				'charge_full' => $bat['charge_full'],
				'charge_now' => $bat['charge_now'],
				'percentage' => $bat['charge_full'] > 0 && $bat['charge_now'] > 0 ? round($bat['charge_now'] / $bat['charge_full'], 4) * 100 . '%' : '?',
				'device' => $bat['vendor'].' - '.$bat['name'],
				'state' => $bat['charging'] ? 'Charging' : ($bat['charged'] ? 'Fully Charged' : 'Discharging, probably')
			);
		
		// Give
		return $batteries;
	}

	// drives
	private function getHD() {
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Drives');
		
		// Store disks here
		$disks = array();
		
		// Use system profiler to get info
		try {
			$res = $this->exec->exec('diskutil', ' list');
		}
		catch(CallExtException $e) {
			$this->error->add('Linfo drives', 'Error using `diskutil list` to get drives');
			return array();
		}

		// Get it into lines
		$lines = explode("\n", $res);

		// Keep drives here
		$drives = array();

		// Work on tmp drive here
		$tmp = false;
		
		// Parse teh fucka
		for ($i = 0, $num_lines = count($lines); $i < $num_lines; $i++) {

			// A drive or partition entry
			if(preg_match('/^\s+(\d+):\s+([a-zA-Z0-9\_]+)\s+([\s\w]*) \*?(\d+(?:\.\d+)? [A-Z])B\s+([a-z0-9]+)/', $lines[$i], $m)) {

				// Get size sorted out
				$size_parts = explode(' ', $m[4]);
				switch($size_parts[1]) {
					case 'K':
						$size = $size_parts[0] * 1000;
					break;
					case 'M':
						$size = $size_parts[0] * 1000000;
					break;
					case 'G':
						$size = $size_parts[0] * 1000000000;
					break;
					case 'T':
						$size = $size_parts[0] * 1000000000000;
					break;
					case 'P':
						$size = $size_parts[0] * 1000000000000000;
					break;
					default:
						$size = false;
					break;
				}

				// A drive?
				if ($m[1] == 0) {

					// Finish prior drive
					if (is_array($tmp))
						$drives[] = $tmp;

					// Try getting the name
					$drive_name = false; // I'm fucking pessimistic
	//			/*	
					try {
						$drive_res = $this->exec->exec('diskutil', ' info /dev/'.$m[5]); 
						if (preg_match('/^\s+Device \/ Media Name:\s+(.+)/m', $drive_res, $drive_m))
							$drive_name = $drive_m[1];
					}
					catch(CallExtException $e) {
					}
	//			*/

					// Start this one off
					$tmp = array(
						'name' =>  $drive_name,
						'vendor' => 'Unknown',
						'device' => '/dev/'.$m[5],
						'reads' => false,
						'writes' => false,
						'size' => $size,
						'partitions' =>  array()
					);
				}

				// Or a partition
				elseif($m[1] > 0) {

					// Save it
					$tmp['partitions'][] = array(
						'size' => $size,
						'name' => '/dev/'.$m[5]
					);
				}
			}
		}
		
		// Save a drive
		if (is_array($tmp))
			$drives[] = $tmp;

		// Give
		return $drives;
	}
}
