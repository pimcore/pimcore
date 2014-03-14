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
 * Get info on a usual linux system
 * Works by exclusively looking around /proc and /sys
 * Totally ignores CallExt class, very deliberately
 * Also deliberately ignores trying to find out the distro. 
 */
class OS_Linux {

	// Keep these tucked away
	protected
		$settings, $error;

	/**
	 * Constructor. Localizes settings
	 * 
	 * @param array $settings of linfo settings
	 * @access public
	 */
	public function __construct($settings) {

		// Localize settings
		$this->settings = $settings;

		// Localize error handler
		$this->error = LinfoError::Fledging();

		// Make sure we have what we need
		if (!is_dir('/sys') || !is_dir('/proc'))
			throw new GetInfoException('This needs access to /proc and /sys to work.');
	}

	/**
	 * getAll 
	 * 
	 * @access public
	 * @return array the info
	 */
	public function getAll() {

		// Return everything, whilst obeying display permissions
		return array(
			'OS' => empty($this->settings['show']['os']) ? '' : $this->getOS(),
			'Kernel' => empty($this->settings['show']['kernel']) ? '' : $this->getKernel(),
			'Distro' => empty($this->settings['show']['distro']) ? '' : $this->getDistro(),
			'RAM' => empty($this->settings['show']['ram']) ? array() : $this->getRam(),
			'HD' => empty($this->settings['show']['hd']) ? '' : $this->getHD(),
			'Mounts' => empty($this->settings['show']['mounts']) ? array() : $this->getMounts(),
			'Load' => empty($this->settings['show']['load']) ? array() : $this->getLoad(),
			'HostName' => empty($this->settings['show']['hostname']) ? '' : $this->getHostName(),
			'UpTime' => empty($this->settings['show']['uptime']) ? '' : $this->getUpTime(),
			'CPU' => empty($this->settings['show']['cpu']) ? array() : $this->getCPU(),
			'CPUArchitecture' => empty($this->settings['show']['cpu']) ? array() : $this->getCPUArchitecture(),
			'Network Devices' => empty($this->settings['show']['network']) ? array() : $this->getNet(),
			'Devices' => empty($this->settings['show']['devices']) ? array() : $this->getDevs(),
			'Temps' => empty($this->settings['show']['temps']) ? array(): $this->getTemps(),
			'Battery' => empty($this->settings['show']['battery']) ? array(): $this->getBattery(),
			'Raid' => empty($this->settings['show']['raid']) ? array(): $this->getRAID(),
			'Wifi' => empty($this->settings['show']['wifi']) ? array(): $this->getWifi(),
			'SoundCards' => empty($this->settings['show']['sound']) ? array(): $this->getSoundCards(),
			'processStats' => empty($this->settings['show']['process_stats']) ? array() : $this->getProcessStats(),
			'services' => empty($this->settings['show']['process_stats']) ? array() : $this->getServices(),
			'numLoggedIn' => empty($this->settings['show']['numLoggedIn']) ? array() : $this->getNumLoggedIn()
		);
	}

	/**
	 * getOS 
	 * 
	 * @access private
	 * @return string Linux
	 */
	private function getOS() {
		
		// Linux, obviously
		return 'Linux';
	}

	/**
	 * getKernel 
	 * 
	 * @access private
	 * @return string kernel version
	 */
	private function getKernel() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Kernel');

		// File containing info
		$file = '/proc/version';

		// Make sure we can use it
		if (!is_file($file) || !is_readable($file)) {
			$this->error->add('Linfo Core', '/proc/version not found');
			return 'Unknown';
		}

		// Get it
		$contents = getContents($file);

		// Parse it
		if (preg_match('/^Linux version (\S+).+$/', $contents, $match) != 1) {
			$this->error->add('Linfo Core', 'Error parsing /proc/version');
			return 'Unknown';
		}

		// Return it
		return $match[1];
	}

	/**
	 * getHostName 
	 * 
	 * @access private
	 * @return string the host name
	 */
	private function getHostName() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Hostname');

		// File containing info
		$file = '/proc/sys/kernel/hostname';
		
		// Get it
		$hostname = getContents($file, false);

		// Failed?
		if ($hostname === false) {
			$this->error->add('Linfo Core', 'Error getting /proc/sys/kernel/hostname');
			return 'Unknown';
		}
		else {

			// Didn't fail; return it
			return $hostname;
		}
	}

	/**
	 * getRam 
	 * 
	 * @access private
	 * @return array the memory information
	 */
	private function getRam(){
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Memory');

		// We'll return the contents of this
		$return = array();

		// Files containing juicy info
		$procFileSwap = '/proc/swaps';
		$procFileMem = '/proc/meminfo';

		// First off, these need to exist..
		if (!is_readable($procFileSwap) || !is_readable($procFileMem)) {
			$this->error->add('Linfo Core', '/proc/swaps and/or /proc/meminfo are not readable');
			return array();
		}

		// To hold their values
		$memVals = array();
		$swapVals = array();

		// Get memContents
		@preg_match_all('/^([^:]+)\:\s+(\d+)\s*(?:k[bB])?\s*/m', getContents($procFileMem), $matches, PREG_SET_ORDER);

		// Deal with it
		foreach ((array)$matches as $memInfo)
			$memVals[$memInfo[1]] = $memInfo[2];

		// Get swapContents
		@preg_match_all('/^(\S+)\s+(\S+)\s+(\d+)\s(\d+)/m', getContents($procFileSwap), $matches, PREG_SET_ORDER);
		foreach ((array)$matches as $swapDevice) {
			
			// Append each swap device
			$swapVals[] = array (
				'device' => $swapDevice[1],
				'type' => $swapDevice[2],
				'size' => $swapDevice[3]*1024,
				'used' => $swapDevice[4]*1024
			);
		}

		// Get individual vals
		$return['type'] = 'Physical';
		$return['total'] = $memVals['MemTotal']*1024;
		$return['free'] = $memVals['MemFree']*1024 + $memVals['Cached']*1024+ $memVals['Buffers']*1024;
		$return['swapTotal'] = $memVals['SwapTotal']*1024;
		$return['swapFree'] = $memVals['SwapFree']*1024 + $memVals['SwapCached']*1024;
		$return['swapCached'] = $memVals['SwapCached']*1024;
		$return['swapInfo'] = $swapVals;

		// Return it
		return $return;
	}

	/**
	 * getCPU 
	 * 
	 * @access private
	 * @return array of cpu info
	 */
	private function getCPU() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('CPUs');

		// File that has it
		$file = '/proc/cpuinfo';

		// Not there?
		if (!is_file($file) || !is_readable($file)) {
			$this->error->add('Linfo Core', '/proc/cpuinfo not readable');
			return array();
		}

		/*
		 * Get all info for all CPUs from the cpuinfo file
		 */

		// Get contents
		$contents = trim(@file_get_contents($file));

		// Lines
		$lines = explode("\n", $contents);

		// Store CPUs here
		$cpus = array();

		// Holder for current CPU info
		$cur_cpu = array();

		// Go through lines in file
		$num_lines = count($lines);
		
		// We use the key of the first line to separate CPUs
		$first_line = substr($lines[0], 0, strpos($lines[0], ' '));
		
		for ($i = 0; $i < $num_lines; $i++) {
			
			// Approaching new CPU? Save current and start new info for this
			if (strpos($lines[$i], $first_line) === 0 && count($cur_cpu) > 0) {
				$cpus[] = $cur_cpu;
				$cur_cpu = array();
				
				// Default to unknown
				$cur_cpu['Model'] = 'Unknown';
			}

			// Info here
			$line = explode(':', $lines[$i], 2);

			if (!array_key_exists(1, $line))
				continue;

			$key = trim($line[0]);
			$value = trim($line[1]);

			
			// What we want are MHZ, Vendor, and Model.
			switch ($key) {
				
				// CPU model
				case 'model name':
				case 'cpu':
				case 'Processor':
					$cur_cpu['Model'] = $value;
				break;

				// Speed in MHz
				case 'cpu MHz':
					$cur_cpu['MHz'] = $value;
				break;

				case 'Cpu0ClkTck': // Old sun boxes
					$cur_cpu['MHz'] = hexdec($value) / 1000000;
				break;

				// Brand/vendor
				case 'vendor_id':
					$cur_cpu['Vendor'] = $value;
				break;
			}

		}

		// Save remaining one
		if (count($cur_cpu) > 0)
			$cpus[] = $cur_cpu;

		// Return them
		return $cpus;
	}

	// Famously interesting uptime
	private function getUpTime () {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Uptime');

		// Get contents
		$contents = getContents('/proc/uptime', false);

		// eh?
		if ($contents === false) {
			$this->error->add('Linfo Core', '/proc/uptime does not exist.');
			return 'Unknown';
		}

		// Seconds
		list($seconds) = explode(' ', $contents, 1);

		// Get it textual, as in days/minutes/hours/etc
		$uptime = seconds_convert(ceil($seconds));

		// Now find out when the system was booted
		$contents = getContents('/proc/stat', false);

		// Ugh
		if ($contents === false)
			return $uptime; // Settle for just uptime

		// Get date of boot
		if (preg_match('/^btime (\d+)$/m', $contents, $boot) != 1)
			return $uptime;

		// Okay?
		list(, $boot) = $boot;

		// Return
		return $uptime . '; booted '.date($this->settings['dates'], $boot);
	}

	/**
	 * getHD 
	 * 
	 * @access private
	 * @return array the hard drive info
	 */
	private function getHD() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Drives');

		// Get partitions
		$partitions = array();
		$partitions_contents = getContents('/proc/partitions');
		if (@preg_match_all('/(\d+)\s+([a-z]{3})(\d+)$/m', $partitions_contents, $partitions_match, PREG_SET_ORDER) > 0) {
			// Go through each match
			$num_partitions = count($partitions_match);
			for ($i = 0; $i < $num_partitions; $i++) {
				$partition = $partitions_match[$i];
				$partitions[$partition[2]][] = array(
					'size' => $partition[1] * 1024,
					'number' => $partition[3]
				);
			}
		}
		
		// Store drives here
		$drives = array();
		
		// Get actual drives
		$drive_paths = (array) @glob('/sys/block/*/device/model', GLOB_NOSORT);
		$num_drives = count($drive_paths);
		for ($i = 0; $i < $num_drives; $i++) {
			
			// Path
			$path = $drive_paths[$i];

			// Dirname of the drive's sys entry
			$dirname = dirname(dirname($path));

			// Parts of the path
			$parts = explode('/', $path);

			// Attempt getting read/write stats
			if (preg_match('/^(\d+)\s+\d+\s+\d+\s+\d+\s+(\d+)\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+$/', getContents(dirname(dirname($path)).'/stat'), $statMatches) !== 1) {
				// Didn't get it
				$reads = false;
				$writes = false;
			}
			else
				// Got it, save it
				list(, $reads, $writes) = $statMatches;

			// Append this drive on
			$drives[] = array(
				'name' =>  getContents($path, 'Unknown'),
				'vendor' => getContents(dirname($path).'/vendor', 'Unknown'),
				'device' => '/dev/'.$parts[3],
				'reads' => $reads,
				'writes' => $writes,
				'size' => getContents(dirname(dirname($path)).'/size', 0) * 512,
				'partitions' => array_key_exists($parts[3], $partitions) && is_array($partitions[$parts[3]]) ? $partitions[$parts[3]] : false 
			);
		}

		// Return drives
		return $drives;
	}

	/**
	 * getTemps 
	 * 
	 * @access private
	 * @return array the temps
	 */
	private function getTemps() {
	
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Temperature');

		// Hold them here
		$return = array();

		// hddtemp?
		if (array_key_exists('hddtemp', (array)$this->settings['temps']) && !empty($this->settings['temps']['hddtemp'])) {
			try {
				// Initiate class
				$hddtemp = new GetHddTemp($this->settings);

				// Set mode, as in either daemon or syslog
				$hddtemp->setMode($this->settings['hddtemp']['mode']);

				// If we're daemon, save host and port
				if ($this->settings['hddtemp']['mode'] == 'daemon') {
					$hddtemp->setAddress(
						$this->settings['hddtemp']['address']['host'],
						$this->settings['hddtemp']['address']['port']);
				}

				// Result after working it
				$hddtemp_res = $hddtemp->work();

				// If it's an array, it worked
				if (is_array($hddtemp_res))
					// Save result
					$return = array_merge($return, $hddtemp_res);

			}

			// There was an issue
			catch (GetHddTempException $e) {
				$this->error->add('hddtemp parser', $e->getMessage());
			}
		}

		// mbmon?
		if (array_key_exists('mbmon', (array)$this->settings['temps']) && !empty($this->settings['temps']['mbmon'])) {
			try {
				// Initiate class
				$mbmon = new GetMbMon;

				// Set host and port
				$mbmon->setAddress(
					$this->settings['mbmon']['address']['host'],
					$this->settings['mbmon']['address']['port']);

				// Get result after working it
				$mbmon_res = $mbmon->work();

				// If it's an array, it worked
				if (is_array($mbmon_res))
					// Save result
					$return = array_merge($return, $mbmon_res);
			}
			catch (GetMbMonException $e) {
				$this->error->add('mbmon parser', $e->getMessage());
			}
		}

		// sensord? (part of lm-sensors)
		if (array_key_exists('sensord', (array)$this->settings['temps']) && !empty($this->settings['temps']['sensord'])) {
			try {
				// Iniatate class
				$sensord = new GetSensord;

				// Work it
				$sensord_res = $sensord->work();

				// If it's an array, it worked
				if (is_array($sensord_res))
					// Save result
					$return = array_merge($return, $sensord_res);
			}
			catch (GetSensordException $e) {
				$this->error->add('sensord parser', $e->getMessage());
			}
		}

		// hwmon? (probably the fastest of what's here)
		// too simple to be in its own class
		if (array_key_exists('hwmon', (array)$this->settings['temps']) && !empty($this->settings['temps']['hwmon'])) {

			// Store them here
			$hwmon_vals = array();

			// Wacky location
			$hwmon_paths = (array) @glob('/sys/class/hwmon/hwmon*/*_label', GLOB_NOSORT);
			$num_paths = count($hwmon_paths);
			for ($i = 0; $i < $num_paths; $i++) {

				// The path
				$path = $hwmon_paths[$i];

				// Get info here
				$section = rtrim($path, 'label');
				$filename = basename($path);
				$label = getContents($path);
				$value = getContents($section.'input');

				// Determine units and possibly fix values
				if (strpos($filename, 'fan') !== false)
					$unit = 'RPM';
				elseif (strpos($filename, 'temp') !== false) {
					$unit = 'C';  // Always seems to be in celsius
					$value = strlen($value) == 5 ? substr($value, 0, 2) : $value;  // Pointless extra 0's
				}
				elseif (preg_match('/^in\d_label$/', $filename)) {
					$unit = 'v'; 
				}
				else 
					$unit = ''; // Not sure if there's a temp

				// Append values
				$hwmon_vals[] = array(
					'path' => 'N/A',
					'name' => $label,
					'temp' => $value,
					'unit' => $unit
				);
			}
			
			// Save any if we have any
			if (count($hwmon_vals) > 0)
				$return = array_merge($return, $hwmon_vals);
		}

		// Additional weird bullshit? In this case, laptop backlight percentage. lolwtf, right?
		foreach ((array) @glob('/sys/{devices/virtual,class}/backlight/*/max_brightness', GLOB_NOSORT | GLOB_BRACE) as $bl) {
			$dir = dirname($bl);
			if (!is_file($dir.'/actual_brightness'))
				continue;
			$max = get_int_from_file($bl);
			$cur = get_int_from_file($dir.'/actual_brightness');
			if ($max < 0 || $cur < 0)
				continue;
			$return[] = array(
				'name' => 'Backlight brightness',
				'temp' => round($cur/$max, 2)*100,
				'unit' => '%',
				'path' => 'N/A',
				'bar' => true
			);
		}

		// Done
		return $return;
	}

	/**
	 * getMounts 
	 * 
	 * @access private
	 * @return array the mounted the file systems
	 */
	private function getMounts() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Mounted file systems');

		// File
		$contents = getContents('/proc/mounts', false);

		// Can't?
		if ($contents == false)
			$this->error->add('Linfo Core', '/proc/mounts does not exist');

		// Parse
		if (@preg_match_all('/^(\S+) (\S+) (\S+) (.+) \d \d$/m', $contents, $match, PREG_SET_ORDER) === false)
			$this->error->add('Linfo Core', 'Error parsing /proc/mounts');

		// Return these
		$mounts = array();

		// Populate
		$num_matches = count($match);
		for ($i = 0; $i < $num_matches; $i++) {

			// This mount
			$mount = $match[$i];
			
			// Should we not show this?
			if (in_array($mount[1], $this->settings['hide']['storage_devices']) || in_array($mount[3], $this->settings['hide']['filesystems']))
				continue;
			
			// Spaces and other things in the mount path are escaped C style. Fix that.
			$mount[2] = stripcslashes($mount[2]);
			
			// Get these
			$size = @disk_total_space($mount[2]);
			$free = @disk_free_space($mount[2]);
			$used = $size != false && $free != false ? $size - $free : false;

			// If it's a symlink, find out where it really goes.
			// (using realpath instead of readlink because the former gives absolute paths)
			$symlink = is_link($mount[1]) ? realpath($mount[1]) : false;
			
			// Optionally get mount options
			if ($this->settings['show']['mounts_options'] && !in_array($mount[3], (array) $this->settings['hide']['fs_mount_options'])) 
				$mount_options = explode(',', $mount[4]);
			else 
				$mount_options = array();

			// Might be good, go for it
			$mounts[] = array(
				'device' => $symlink != false ? $symlink : $mount[1],
				'mount' => $mount[2],
				'type' => $mount[3],
				'size' => $size,
				'used' => $used,
				'free' => $free,
				'free_percent' => ((bool)$free != false && (bool)$size != false ? round($free / $size, 2) * 100 : false),
				'used_percent' => ((bool)$used != false && (bool)$size != false ? round($used / $size, 2) * 100 : false),
				'options' => $mount_options
			);
		}

		// Return
		return $mounts;
	}

	/**
	 * getDevs 
	 * 
	 * @access private
	 * @return array of devices
	 */
	private function getDevs() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Hardware Devices');

		// Location of useful paths
		$pci_ids = locate_actual_path(array(
			'/usr/share/misc/pci.ids',	// debian/ubuntu
			'/usr/share/pci.ids',		// opensuse
			'/usr/share/hwdata/pci.ids',	// centos. maybe also redhat/fedora
		));
		$usb_ids = locate_actual_path(array(
			'/usr/share/misc/usb.ids',	// debian/ubuntu
			'/usr/share/usb.ids',		// opensuse
			'/usr/share/hwdata/usb.ids',	// centos. maybe also redhat/fedora
		));

		// Did we not get them?
		$pci_ids || $this->error->add('Linux Device Finder', 'Cannot find pci.ids; ensure pciutils is installed.');
		$usb_ids || $this->error->add('Linux Device Finder', 'Cannot find usb.ids; ensure usbutils is installed.');

		// Class that does it
		$hw = new HW_IDS($usb_ids, $pci_ids);
		$hw->work('linux');
		return $hw->result();
	}

	/**
	 * getRAID 
	 * 
	 * @access private
	 * @return array of raid arrays
	 */
	private function getRAID() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('RAID');
		
		// Store it here
		$raidinfo = array();

		// mdadm?
		if (array_key_exists('mdadm', (array)$this->settings['raid']) && !empty($this->settings['raid']['mdadm'])) {

			// Try getting contents
			$mdadm_contents = getContents('/proc/mdstat', false);

			// No?
			if ($mdadm_contents === false)
				$this->error->add('Linux softraid mdstat parser', '/proc/mdstat does not exist.');

			// Parse
			@preg_match_all('/(\S+)\s*:\s*(\w+)\s*raid(\d+)\s*([\w+\[\d+\] (\(\w\))?]+)\n\s+(\d+) blocks\s*(?:super \d\.\d\s*)?(level \d\, [\w\d]+ chunk\, algorithm \d\s*)?\[(\d\/\d)\] \[([U\_]+)\]/mi', (string) $mdadm_contents, $match, PREG_SET_ORDER);

			// Store them here
			$mdadm_arrays = array();

			// Deal with entries
			foreach ((array) $match as $array) {
				
				// Temporarily store drives here
				$drives = array();

				// Parse drives
				foreach (explode(' ', $array[4]) as $drive) {

					// Parse?
					if(preg_match('/([\w\d]+)\[\d+\](\(\w\))?/', $drive, $match_drive) == 1) {

						// Determine a status other than normal, like if it failed or is a spare
						if (array_key_exists(2, $match_drive)) {
							switch ($match_drive[2]) {
								case '(S)':
									$drive_state = 'spare';
								break;
								case '(F)':
									$drive_state = 'failed';
								break;
								case null:
									$drive_state = 'normal';
								break;

								// I'm not sure if there are status codes other than the above
								default:
									$drive_state = 'unknown';
								break;
							}
						}
						else
							$drive_state = 'normal';

						// Append this drive to the temp drives array
						$drives[] = array(
							'drive' => '/dev/'.$match_drive[1],
							'state' => $drive_state
						);
					}
				}

				// Add record of this array to arrays list
				$mdadm_arrays[] = array(
					'device' => '/dev/'.$array[1],
					'status' => $array[2],
					'level' => $array[3],
					'drives' => $drives,
					'size' =>  byte_convert($array[5]*1024),
					'algorithm' => $array[6],
					'count' => $array[7],
					'chart' => $array[8]
				);
			}

			// Append MD arrays to main raidinfo if it's good
			if (is_array($mdadm_arrays) && count($mdadm_arrays) > 0 )
				$raidinfo = array_merge($raidinfo, $mdadm_arrays);
		}

		// Return info
		return $raidinfo;
	}

	/**
	 * getLoad 
	 * 
	 * @access private
	 * @return array of current system load values
	 */
	private function getLoad() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Load Averages');

		// File that has it
		$file = '/proc/loadavg';

		// Get contents
		$contents = getContents($file, false);

		// ugh
		if ($contents === false) {
			$this->error->add('Linfo Core', '/proc/loadavg unreadable');
			return array();
		}

		// Parts
		$parts = explode(' ', $contents);

		// Return array of info
		return array(
			'now' => $parts[0],
			'5min' => $parts[1],
			'15min' => $parts[2]
		);
	}

	/**
	 * getNet 
	 * 
	 * @access private
	 * @return array of network devices
	 */
	private function getNet() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Network Devices');

		// Hold our return values
		$return = array();

		// Use glob to get paths
		$nets = (array) @glob('/sys/class/net/*', GLOB_NOSORT);

		// Get values for each device
		$num_nets = count($nets);
		for ($i = 0; $i < $num_nets; $i++) {
			
			// Path
			$path = $nets[$i];

			// States
			$operstate_contents = getContents($path.'/operstate');
			switch ($operstate_contents) {
				case 'down':
				case 'up':
				case 'unknown':
					$state = $operstate_contents;
				break;

				default:
					$state = 'unknown';
				break;
			}

			// motherfucker
			if ($state = 'unknown' && file_exists($path.'/carrier')) {
				 $carrier = getContents($path.'/carrier', false);
				if (!empty($carrier)) 
					$state = 'up'; 
				else
					$state = 'down'; 
			}

			// Type
			$type_contents = strtoupper(getContents($path.'/device/modalias'));
			list($type) = explode(':', $type_contents, 2);
			$type = $type != 'USB' && $type != 'PCI' ? 'N/A' : $type;

			// Save and get info for each
			$return[basename($path)] = array(

				// Stats are stored in simple files just containing the number
				'recieved' => array(
					'bytes' => get_int_from_file($path.'/statistics/rx_bytes'),
					'errors' => get_int_from_file($path.'/statistics/rx_errors'),
					'packets' => get_int_from_file($path.'/statistics/rx_packets')
				),
				'sent' => array(
					'bytes' => get_int_from_file($path.'/statistics/tx_bytes'),
					'errors' => get_int_from_file($path.'/statistics/tx_errors'),
					'packets' => get_int_from_file($path.'/statistics/rx_packets')
				),

				// These were determined above
				'state' => $state,
				'type' => $type
			);
		}

		// Return array of info
		return $return;
	}

	/**
	 * getBattery 
	 * 
	 * @access private
	 * @return array of battery status
	 */
	private function getBattery() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Batteries');
		
		// Return values
		$return = array();

		// Here they should be
		$bats = (array) @glob('/sys/class/power_supply/BAT*', GLOB_NOSORT);
	
		// Get vals for each battery
		foreach ($bats as $b) {

			$go_for_it = true;

			// Fuck pointless cuntshit
			foreach(array($b.'/manufacturer', $b.'/status', $b.'/charge_now') as $f)
				if (!is_file($f))
					$go_for_it = false; // Continue out of two nested loops

			if (!$go_for_it) 
				continue;

			// Get these from the simple text files
			$charge_full = get_int_from_file($b.'/charge_full');
			$charge_now = get_int_from_file($b.'/charge_now');

			// Alleged percentage
			$percentage = $charge_now != 0 && $charge_full != 0 ? (round($charge_now / $charge_full, 4) * 100) : '?';

			// Save result set
			$return[] = array(
				'charge_full' => $charge_full,
				'charge_now' => $charge_now,
				'percentage' => (is_numeric($percentage) && $percentage > 100 ? 100 : $percentage ).'%',
				'device' => getContents($b.'/manufacturer') . ' ' . getContents($b.'/model_name', 'Unknown'),
				'state' => getContents($b.'/status', 'Unknown')
			);
		}

		// Give it
		return $return;
	}

	/**
	 * getWifi 
	 * 
	 * @access private
	 * @return array of wifi devices
	 */
	private function getWifi() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Wifi');

		// Return these
		$return = array();

		// In here
		$contents = getContents('/proc/net/wireless');

		// Oi
		if ($contents == false) {
			$this->error->add('Linux WiFi info parser', '/proc/net/wireless does not exist');
			return $return;
		}

		// Parse
		@preg_match_all('/^ (\S+)\:\s*(\d+)\s*(\S+)\s*(\S+)\s*(\S+)\s*(\d+)\s*(\d+)\s*(\d+)\s*(\d+)\s*(\d+)\s*(\d+)\s*$/m', $contents, $match, PREG_SET_ORDER);
		
		// Match
		foreach ($match as $wlan) {
			$return[] = array(
				'device' => $wlan[1],
				'status' => $wlan[2],
				'quality_link' => $wlan[3],
				'quality_level' => $wlan[4],
				'quality_noise' => $wlan[5],
				'dis_nwid' => $wlan[6],
				'dis_crypt' => $wlan[7],
				'dis_frag' => $wlan[8],
				'dis_retry' => $wlan[9],
				'dis_misc' => $wlan[10],
				'mis_beac' => $wlan[11]
			);
		}

		// Done
		return $return;
	}

	/**
	 * getSoundCards 
	 * 
	 * @access private
	 * @return array of soundcards
	 */
	private function getSoundCards() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Sound cards');

		// This should be it
		$file = '/proc/asound/cards';

		// eh?
		if (!is_file($file)) {
			$this->error->add('Linux sound card detector', '/proc/asound/cards does not exist');
		}

		// Get contents and parse
		$contents = getContents($file);

		// Parse
		if (preg_match_all('/^\s*(\d+)\s\[[\s\w]+\]:\s(.+)$/m', $contents, $matches, PREG_SET_ORDER) == 0)
			return array();

		// eh?
		$cards = array();

		// Deal with results
		foreach ($matches as $card)	
			$cards[] = array(
				'number' => $card[1],
				'card' => $card[2],
			);

		// Give cards
		return $cards;
	}

	/**
	 * getProcessStats 
	 * 
	 * @access private
	 * @return array of process stats
	 */
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
			),
			'proc_total' => 0,
			'threads' => 0
		);
		
		// Get all the paths to each process' status file
		$processes = (array) @glob('/proc/*/status', GLOB_NOSORT);

		// Total
		$result['proc_total'] = count($processes);

		// Go through each
		for ($i = 0; $i < $result['proc_total']; $i++) {
			
			// Don't waste time if we can't use it
			if (!is_readable($processes[$i]))
				continue;
			
			// Get that file's contents
			$status_contents = getContents($processes[$i]);

			// Try getting state
			@preg_match('/^State:\s+(\w)/m', $status_contents, $state_match);

			// Well? Determine state
			switch ($state_match[1]) {
				case 'D': // disk sleep? wtf?
				case 'S':
					$result['totals']['sleeping']++;
				break;
				case 'Z':
					$result['totals']['zombie']++;
				break;
				case 'R':
					$result['totals']['running']++;
				break;
				case 'T':
					$result['totals']['stopped']++;
				break;
			}

			// Try getting number of threads
			@preg_match('/^Threads:\s+(\d+)/m', $status_contents, $threads_match);

			// Well?
			if ($threads_match)
				list(, $threads) = $threads_match;

			// Append it on if it's good
			if (is_numeric($threads))
				$result['threads'] = $result['threads'] + $threads;
		}

		// Give off result
		return $result;
	}

	/**
	 * getServices 
	 * 
	 * @access private
	 * @return array the services
	 */
	private function getServices() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Services');

		// We allowed?
		if (!empty($settings['show']['services']) || !is_array($this->settings['services']) || count($this->settings['services']) == 0)
			return array();

		// Temporarily keep statuses here
		$statuses = array();

		// A bit of unfucking potential missing values in config file
		$this->settings['services']['executables'] = (array) $this->settings['services']['executables'];
		$this->settings['services']['pidFiles'] = (array) $this->settings['services']['pidFiles'];

		// Convert paths of executables to PID files
		$pids = array();
		$do_process_search = false;
		if (count($this->settings['services']['executables']) > 0) {
			$potential_paths = @glob('/proc/*/cmdline');
			if (is_array($potential_paths)) {
				$num_paths = count($potential_paths);
				$do_process_search = true;
			}
		}
			
		// Should we go ahead and do the PID search based on executables?
		if ($do_process_search) {
			// Precache all process cmdlines
			for ($i = 0; $i < $num_paths; $i++)
				$cmdline_cache[$i] = explode("\x00", getContents($potential_paths[$i]));
			
			// Go through the list of executables to search for
			foreach ($this->settings['services']['executables'] as $service => $exec) {
				// Go through pid file list. for loops are faster than foreach
				for ($i = 0; $i < $num_paths; $i++) {
					$cmdline = $cmdline_cache[$i];
					$match = false;
					if (is_array($exec)) {
						$match = true;
						foreach ($exec as $argn => $argv) {
							if($cmdline[$argn] != $argv)
								$match = false;
						}
					}
					else if ($cmdline[0] == $exec) {
						$match = true;
					}
					// If this one matches, stop here and save it
					if ($match) {
						// Get pid out of path to cmdline file
						$pids[$service] = substr($potential_paths[$i], 6 /*strlen('/proc/')*/,
												strpos($potential_paths[$i], '/', 7)-6);
						break;
					}
				}
			}
		}

		// PID files
		foreach ($this->settings['services']['pidFiles'] as $service => $file) {
			$pid = getContents($file, false);
			if ($pid != false && is_numeric($pid))
				$pids[$service] = $pid;
		}

		// Deal with PIDs
		foreach ($pids as $service => $pid) {
			$path = '/proc/'.$pid.'/status';
			$status_contents = getContents($path, false);
			if ($status_contents == false) {
				$statuses[$service] = array('state' => 'Down', 'threads' => 'N/A', 'pid' => $pid);
				continue;
			}

			
			// Attempt getting info out of it
			if (!preg_match_all('/^(\w+):\s+(\w+)/m', $status_contents, $status_matches, PREG_SET_ORDER))
				continue;

			// Initially set these as pointless
			$state = false;
			$threads = false;
			$mem = false;

			// Go through
			//foreach ($status_matches as $status_match) {
			for ($i = 0, $num = count($status_matches); $i < $num; $i++) {

				// What have we here?
				switch ($status_matches[$i][1]) {

					// State section
					case 'State':
						switch ($status_matches[$i][2]) {
							case 'D': // disk sleep? wtf?
							case 'S':
								$state = 'Up (Sleeping)';
							break;
							case 'Z':
								$state = 'Zombie';
							break;
							// running
							case 'R':
								$state = 'Up (Running)';
							break;
							// stopped
							case 'T':
								$state = 'Up (Stopped)';
							break;
							default:
								continue;
							break;
						}
					break;

					// Mem usage
					case 'VmRSS':
						if (is_numeric($status_matches[$i][2]))
							$mem = $status_matches[$i][2] * 1024; // Measured in kilobytes; we want bytes
					break;
					
					// Thread count
					case 'Threads':
						if (is_numeric($status_matches[$i][2]))
							$threads = $status_matches[$i][2];

						// Thread count should be last. Stop here to possibly save time assuming we have the other values
						if ($state !== false && $mem !== false && $threads !== false)
							break;
					break;
				}
			}


			// Save info
			$statuses[$service] = array(
				'state' => $state ? $state : '?',
				'threads' => $threads,
				'pid' => $pid,
				'memory_usage' => $mem
			);
		}

		return $statuses;
	}
	
	/**
	 * getDistro
	 * 
	 * @access private
	 * @return array the distro,version or false
	 */
	private function getDistro() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Determining Distrobution');

		// Seems the best way of doing it, as opposed to calling 'lsb_release -a', parsing /etc/issue, or 
		// just checking if distro specific version files exist without actually parsing them: 
		// - Allows multiple files of the same name for different distros/versions of distros, provided each
		// - uses different regular expression syntax.
		// - Also permits files that contain only the distro release version and nothing else,
		// - in which case passing false instead of a regex string snags the contents.
		// - And even also supports empty files, and just uses said file to identify the distro and ignore version

		// Store the distribution's files we check for, optional regex parsing string, and name of said distro here:
		$distros = array(
			
			// This snags ubuntu and other distros which use the lsb method of identifying themselves
			array('/etc/lsb-release','/^DISTRIB_ID=([^$]+)$\n^DISTRIB_RELEASE=([^$]+)$\n^DISTRIB_CODENAME=([^$]+)$\n/m', false),
			
			// These working snag versions
			array('/etc/redhat-release', '/^CentOS release ([\d\.]+) \(([^)]+)\)$/', 'CentOS'),
			array('/etc/redhat-release', '/^Red Hat.+release (\S+) \(([^)]+)\)$/', 'RedHat'),
			array('/etc/fedora-release', '/^Fedora(?: Core)? release (\d+) \(([^)]+)\)$/', 'Fedora'),
			array('/etc/gentoo-release', '/([\d\.]+)$/', 'Gentoo'),
			array('/etc/SuSE-release', '/^VERSION = ([\d\.]+)$/m', 'openSUSE'),
			array('/etc/slackware-version', '/([\d\.]+)$/', 'Slackware'),

			// These don't because they're empty 
			array('/etc/arch-release', '', 'Arch'),

			// I'm unaware of the structure of these files, so versions are not picked up
			array('/etc/mklinux-release', '', 'MkLinux'),
			array('/etc/tinysofa-release ', '', 'TinySofa'),
			array('/etc/turbolinux-release ', '', 'TurboLinux'),
			array('/etc/yellowdog-release ', '', 'YellowDog'),
			array('/etc/annvix-release ', '', 'Annvix'),
			array('/etc/arklinux-release ', '', 'Arklinux'),
			array('/etc/aurox-release ', '', 'AuroxLinux'),
			array('/etc/blackcat-release ', '', 'BlackCat'),
			array('/etc/cobalt-release ', '', 'Cobalt'),
			array('/etc/immunix-release ', '', 'Immunix'),
			array('/etc/lfs-release ', '', 'Linux-From-Scratch'),
			array('/etc/linuxppc-release ', '', 'Linux-PPC'),
			array('/etc/mklinux-release ', '', 'MkLinux'),
			array('/etc/nld-release ', '', 'NovellLinuxDesktop'),

			// Leave this since debian derivitives might have it in addition to their own file
			// If it's last it ensures nothing else has it and thus it should be normal debian
			array('/etc/debian_version', false, 'Debian'),
		);

		// Hunt
		foreach ($distros as $distro) {

			// File we're checking for exists and is readable
			if (file_exists($distro[0]) && is_readable($distro[0])) {

				// Get it
				$contents = $distro[1] !== '' ? getContents($distro[0], '') : '';

				// Don't use regex, this is enough; say version is the file's contents
				if ($distro[1] === false) {
					return array(
						'name' => $distro[2],
						'version' => $contents == '' ? false : $contents
					);
				}
				
				// No fucking idea what the version is. Don't use the file's contents for anything
				elseif($distro[1] === '') {
					return array(
						'name' => $distro[2],
						'version' => false
					);
				}

				// Get the distro out of the regex as well?
				elseif($distro[2] === false && preg_match($distro[1], $contents, $m)) {
					return array(
						'name' => $m[1],
						'version' => $m[2] . (isset($m[3]) ? ' ('.$m[3].')' : '')
					);
				}

				// Our regex match it?
				elseif(preg_match($distro[1], $contents, $m)) {
					return array(
						'name' => $distro[2],
						'version' => $m[1] . (isset($m[2]) ? ' ('.$m[2].')' : '')
					);
				}
			}
		}

		// Return lack of result if we didn't find it
		return false;
	}

	/**
	 * getCPUArchitecture
	 * 
	 * @access private
	 * @return string the arch and bits
	 */
	private function getCPUArchitecture() {
		return php_uname('m');
	}

	/**
	 * getNumLoggedIn
	 * 
	 * @access private
	 * @return number of logged in users with shells
	 */
	 private function getNumLoggedIn() {

		// Snag command line of every process in system
		$procs = glob('/proc/*/cmdline', GLOB_NOSORT);
		
		// Store unqiue users here
		$users = array();

		// Each process
		foreach ($procs as $proc) {

			// Does the process match a popular shell, such as bash, csh, etc?
			if (preg_match('/(?:bash|csh|zsh|ksh)$/', getContents($proc, ''))) {

				// Who the fuck owns it, anyway? 
				$owner = fileowner(dirname($proc));

				// Careful..
				if (!is_numeric($owner))
					continue;

				// Have we not seen this user before?
				if (!in_array($owner, $users))
					$users[] = $owner;
			}
		}
		
		// Give number of unique users with shells running
		return count($users);
	}
}
