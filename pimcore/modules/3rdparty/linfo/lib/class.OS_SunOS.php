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


class OS_SunOS {
	
	// Encapsulate these
	protected
		$settings,
		$exec,
		$kstat = array(),
		$error;

	// Start us off
	public function __construct($settings) {
		
		// Localize settings
		$this->settings = $settings;
		
		// External exec runnign
		$this->exec = new CallExt;

		// We search these folders for our commands
		$this->exec->setSearchPaths(array('/sbin', '/bin', '/usr/bin', '/usr/local/bin', '/usr/sbin'));
		
		// Used multpile times so might as well just get it once. here
		$this->release = php_uname('r');

		// Get multiple kstat values at once and store them here. It seems kstat is SunOS' version of BSD's sysctl
		$this->loadkstat(array(
			
			// unix time stamp of system boot
			'unix:0:system_misc:boot_time',

			// usual 3 system load values
			'unix:0:system_misc:avenrun_1min',
			'unix:0:system_misc:avenrun_5min',
			'unix:0:system_misc:avenrun_15min',

			// physical ram info
			'unix:0:seg_cache:slab_size',
			'unix:0:system_pages:pagestotal',
			'unix:0:system_pages:pagesfree',
		));
	}
	
	// This function will likely be shared among all the info classes
	public function getAll() {

		// Return everything, whilst obeying display permissions
		return array(
			'OS' => empty($this->settings['show']) ? '' : $this->getOS(), 			# done
			'Kernel' => empty($this->settings['show']) ? '' : $this->getKernel(), 		# done
			'HostName' => empty($this->settings['show']) ? '' : $this->getHostName(), 	# done
			'Mounts' => empty($this->settings['show']) ? array() : $this->getMounts(), 	# done
			'processStats' => empty($this->settings['show']['process_stats']) ? array() : $this->getProcessStats(), # done
			'UpTime' => empty($this->settings['show']) ? '' : $this->getUpTime(), 		# done
			'Load' => empty($this->settings['show']) ? array() : $this->getLoad(), 		# done
			'RAM' => empty($this->settings['show']) ? array() : $this->getRam(), 		# todo
			/*
			'Devices' => empty($this->settings['show']) ? array() : $this->getDevs(), 	# todo
			'HD' => empty($this->settings['show']) ? '' : $this->getHD(), 			# todo
			'Network Devices' => empty($this->settings['show']) ? array() : $this->getNet(),# todo 
			'RAID' => empty($this->settings['show']) ? '' : $this->getRAID(),	 	# todo 
			'Battery' => empty($this->settings['show']) ? array(): $this->getBattery(),  	# todo
			'CPU' => empty($this->settings['show']) ? array() : $this->getCPU(), 		# todo
			'Temps' => empty($this->settings['show']) ? array(): $this->getTemps(), 	# TODO
			*/
		);
	}

	// Get kstat values. *extremely* similar in practice to the sysctl nature of the bsd's
	// - 
	// Use kstat to get something, and cache result.
	// Also allow getting multiple keys at once, in which case sysctl 
	// will only be called once instead of multiple times (assuming it doesn't break)
	protected function loadkstat($keys ) {

		// Get the keys as an array, so we can treat it as an array of keys
		$keys = (array) $keys;

		// Store the results of which here
		$results = array();

		// Go through each
		foreach ($keys as $k => $v) {
			// unfuck evil shit, such as malicious shell injection
			$keys[$k] = escapeshellarg($v);
			
			// Check and see if we have any of these already. If so, use previous 
			// values and don't retrive them again
			if (array_key_exists($v, $this->kstat)) {
				unset($keys[$k]);
				$results[$v] = $this->kstat[$v];
			}
		}

		// Try running kstat to get all the values together
		try {
			// Result of kstat
			$command = $this->exec->exec('kstat', ' -p '.implode(' ', $keys));

			// Place holder
			$current_key = false;

			// Go through each line
			foreach (explode("\n", $command) as $line) {

				// If this is the beginning of one of the keys' values
				if (preg_match('/^(\S+)\s+(.+)/', $line, $line_match) == 1) {
					if ($line_match[1] != $current_key) {
						$current_key = $line_match[1];
						$results[$line_match[1]] = trim($line_match[2]);
					}
				}

				// If this line is a continuation of one of the keys' values
				elseif($current_key != false) {
					$results[$current_key] .= "\n".trim($line);
				}
			}
		}

		// Something broke with that kstat call; try getting
		// all the values separately (slower)
		catch(CallExtException $e) {

			// Go through each
			foreach ($keys as $v) {

				// Try it
				try {
					$results[$v] = $this->exec->exec('kstat', ' -p '.$v);
				}

				// Didn't work again... just forget it and set value to empty string
				catch (CallExtException $e) {
					$results[$v] = '';
				}
			}
		}

		// Cache these incase they're called upon again
		$this->kstat = array_merge($results, $this->kstat);
	}

	// Return OS type
	private function getOS() {

		// Get SunOS version
		$v = reset(explode('.', $this->release, 2));

		// Stuff 4 and under is SunOS. 5 and up is Solaris
		switch ($v) {
			case ($v > 4):
				return 'Solaris';
			break;
			default:
				return 'SunOS';
			break;
		}
		
		// What's next is determining what variant of Solaris,
		// eg: opensolaris (R.I.P.), nexenta, illumos, etc
	}
	
	// Get kernel version
	private function getKernel() {
		
		// hmm. PHP has a native function for this
		return $this->release;
	}

	// Get host name
	private function getHostName() {
		
		// Take advantage of that function again
		return php_uname('n');
	}

	// Mounted file systems
	private function getMounts() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Mounted file systems');

		// Run mount command
		try {
			$res = $this->exec->exec('mount', '-p');
		}
		catch (CallExtException $e){
			$this->error->add('Linfo Core', 'Error running `mount` command');
			return array();
		}
		
		// Parse it
		if (!preg_match_all('/^(\S+) - (\S+) (\w+).+/m', $res, $mount_matches, PREG_SET_ORDER))
			return array();

		// Store them here
		$mounts = array();
		
		// Deal with each entry
		foreach ($mount_matches as $mount) {

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

	// Get ram stats
	private function getRAM() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Memory');
		
		// Give
		return array(
			'type' => 'Physical',
			'total' => $this->kstat['unix:0:system_pages:pagestotal'] * $this->kstat['unix:0:seg_cache:slab_size'],
			'free' => $this->kstat['unix:0:system_pages:pagesfree'] * $this->kstat['unix:0:seg_cache:slab_size'],
			'swapInfo' => array()
		);
	}

	function getProcessStats() {
		
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
			$ps = $this->exec->exec('ps', '-fe -o s');
			
			// Go through it
			foreach (explode("\n", trim($ps)) as $process) {

				// Decide what this is
				switch ($process) {
					case 'S':
						$result['totals']['sleeping']++;
					break;
					case 'Z':
						$result['totals']['zombie']++;
					break;
					case 'R':
					case 'O':
						$result['totals']['running']++;
					break;
					case 'T':
						$result['totals']['stopped']++;
					break;
				}

				// Increment total
				$result['proc_total']++;
			}
		}

		// Something bad happened
		catch (CallExtException $e) {
			$this->error->add('Linfo Core', 'Error using `ps` to get process info');
		}

		// Give
		return $result;
	}

	// uptime
	private function getUpTime() {
		$booted = $this->kstat['unix:0:system_misc:boot_time'];
		return seconds_convert(time() - $booted) . '; booted ' . date('m/d/y h:i A', $booted);
	}

	// load
	private function getLoad() {
		// Give
		return array(
			'now' => round($this->kstat['unix:0:system_misc:avenrun_1min'] / 256, 2),
			'5min' => round($this->kstat['unix:0:system_misc:avenrun_5min'] / 256, 2),
			'15min' => round($this->kstat['unix:0:system_misc:avenrun_10min'] / 256, 2)
		);
	}
}
