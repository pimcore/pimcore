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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Linfo. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Keep out hackers...
 */
defined('IN_LINFO') or exit;

/**
 * Get info on a usual linux system
 * Works by exclusively looking around /proc and /sys
 * Totally ignores CallExt class, very deliberately
 * Also deliberately ignores trying to find out the distro. 
 */
class OS_Linux extends OS_Unix_Common {

	// Keep these tucked away
	protected
		$settings, $error;

	// Generally disabled as it's slowww
	protected
		$cpu_percent = array('overall' => false, 'cpus' => array());

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
		$this->error = LinfoError::Singleton();

		// Make sure we have what we need
		if (!is_dir('/sys') || !is_dir('/proc'))
			throw new LinfoFatalException('This needs access to /proc and /sys to work.');
	}

	public function init() {
		if (isset($this->settings['cpu_usage']) && !empty($this->settings['cpu_usage']))
			$this->determineCPUPercentage();
	}

	/**
	 * getOS 
	 * 
	 * @access private
	 * @return string Linux
	 */
	public function getOS() {
		
		// Linux, obviously
		return 'Linux';
	}

	/**
	 * getKernel 
	 * 
	 * @access private
	 * @return string kernel version
	 */
	public function getKernel() {
		
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
		$contents = LinfoCommon::getContents($file);

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
	public function getHostName() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Hostname');

		// File containing info
		$file = '/proc/sys/kernel/hostname';
		
		// Get it
		$hostname = LinfoCommon::getContents($file, false);

		// Failed?
		if ($hostname === false) {
			$this->error->add('Linfo Core', 'Error getting /proc/sys/kernel/hostname');
			return 'Unknown';
		}

		// Didn't fail; return it
		return $this->ensureFQDN($hostname);
	}

	/**
	 * getRam 
	 * 
	 * @access private
	 * @return array the memory information
	 */
	public function getRam(){
		
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
		@preg_match_all('/^([^:]+)\:\s+(\d+)\s*(?:k[bB])?\s*/m', LinfoCommon::getContents($procFileMem), $matches, PREG_SET_ORDER);

		// Deal with it
		foreach ((array)$matches as $memInfo)
			$memVals[$memInfo[1]] = $memInfo[2];

		// Get swapContents
		@preg_match_all('/^(\S+)\s+(\S+)\s+(\d+)\s(\d+)/m', LinfoCommon::getContents($procFileSwap), $matches, PREG_SET_ORDER);
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
	public function getCPU() {
		
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
		$contents = LinfoCommon::getContents($file);

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

				// ID. Corresponds to percentage if enabled below
				case 'processor':
					if (isset($this->cpu_percent['cpus'][$value]))
						$cur_cpu['usage_percentage'] = $this->cpu_percent['cpus'][$value];
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
	public function getUpTime () {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Uptime');

		// Get contents
		$contents = LinfoCommon::getContents('/proc/uptime', false);

		// eh?
		if ($contents === false) {
			$this->error->add('Linfo Core', '/proc/uptime does not exist.');
			return 'Unknown';
		}

		// Seconds
		list($seconds) = explode(' ', $contents, 1);

		// Get it textual, as in days/minutes/hours/etc
		$uptime = LinfoCommon::secondsConvert(ceil($seconds));

		// Now find out when the system was booted
		$contents = LinfoCommon::getContents('/proc/stat', false);

		// Ugh
		if ($contents === false)
			return $uptime; // Settle for just uptime

		// Get date of boot
		if (preg_match('/^btime (\d+)$/m', $contents, $boot) != 1)
			return $uptime;

		// Okay?
		list(, $boot) = $boot;

		return array(
			'text' => $uptime,
			'bootedTimestamp' => $boot
		);
	}

	/**
	 * getHD 
	 * 
	 * @access private
	 * @return array the hard drive info
	 */
	public function getHD() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Drives');

		// Get partitions
		$partitions = array();
		$partitions_contents = LinfoCommon::getContents('/proc/partitions');
		if (@preg_match_all('/(\d+)\s+([a-z]{3})(\d+)$/m', $partitions_contents, $partitions_match, PREG_SET_ORDER) > 0) {
			// Go through each match
			foreach ($partitions_match as $partition)
				$partitions[$partition[2]][] = array(
					'size' => $partition[1] * 1024,
					'number' => $partition[3]
				);
		}
		
		// Store drives here
		$drives = array();
		
		// Get actual drives
		foreach ((array) @glob('/sys/block/*/device/model', GLOB_NOSORT) as $path) {

			// Dirname of the drive's sys entry
			$dirname = dirname(dirname($path));

			// Parts of the path
			$parts = explode('/', $path);

			// Attempt getting read/write stats
			if (preg_match('/^(\d+)\s+\d+\s+\d+\s+\d+\s+(\d+)\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+$/', LinfoCommon::getContents(dirname(dirname($path)).'/stat'), $statMatches) !== 1) {
				// Didn't get it
				$reads = false;
				$writes = false;
			}
			else
				// Got it, save it
				list(, $reads, $writes) = $statMatches;

			// Append this drive on
			$drives[] = array(
				'name' => LinfoCommon::getContents($path, 'Unknown').(LinfoCommon::getContents(dirname(dirname($path)).'/queue/rotational') == 0 ? ' (SSD)' : ''),
				'vendor' => LinfoCommon::getContents(dirname($path).'/vendor', 'Unknown'),
				'device' => '/dev/'.$parts[3],
				'reads' => $reads,
				'writes' => $writes,
				'size' => LinfoCommon::getContents(dirname(dirname($path)).'/size', 0) * 512,
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
	public function getTemps() {
	
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Temperature');

		// Hold them here
		$return = array();

		// hddtemp?
		if (array_key_exists('hddtemp', (array)$this->settings['temps']) && !empty($this->settings['temps']['hddtemp']) && isset($this->settings['hddtemp'])) {
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
		if (array_key_exists('mbmon', (array)$this->settings['temps']) && !empty($this->settings['temps']['mbmon']) && isset($this->settings['mbmon'])) {
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
			foreach ((array) @glob('/sys/class/hwmon/hwmon*/{,device/}*_input', GLOB_NOSORT | GLOB_BRACE) as $path) {

				$initpath = rtrim($path, 'input');
				$value = LinfoCommon::getContents($path);
				$base = basename($path);
				$labelpath = $initpath.'label';
				$showemptyfans = isset($this->settings['temps_show0rpmfans']) ? $this->settings['temps_show0rpmfans'] : false;
				$drivername = @basename(@readlink(dirname($path).'/driver')) ?: false;

				// Temperatures
				if (is_file($labelpath) && strpos($base, 'temp') === 0) {
					$label = LinfoCommon::getContents($labelpath);
					$value /= $value > 10000 ? 1000 : 1;
					$unit = 'C'; // I don't think this is ever going to be in F
				}

				// Fan RPMs
				elseif (preg_match('/^fan(\d+)_/', $base, $m)) {
					$label = 'fan'.$m[1];
					$unit = 'RPM';

					if ($value == 0 && !$showemptyfans)
						continue;
				}

				// Volts
				elseif (preg_match('/^in(\d+)_/', $base, $m)) {
					$unit = 'V';
					$value /= 1000;
					$label = LinfoCommon::getContents($labelpath) ?: 'in'.$m[1];
				}
				else
					continue;

				// Append values
				$hwmon_vals[] = array(
					'path' => '',
					'name' => $label.($drivername ? ' <span class="faded">('.$drivername.')</span>' : ''),
					'temp' => $value,
					'unit' => $unit
				);
			}
			
			// Save any if we have any
			if (count($hwmon_vals) > 0)
				$return = array_merge($return, $hwmon_vals);
		}

		// Laptop backlight percentage
		foreach ((array) @glob('/sys/{devices/virtual,class}/backlight/*/max_brightness', GLOB_NOSORT | GLOB_BRACE) as $bl) {
			$dir = dirname($bl);
			if (!is_file($dir.'/actual_brightness'))
				continue;
			$max = LinfoCommon::getIntFromFile($bl);
			$cur = LinfoCommon::getIntFromFile($dir.'/actual_brightness');
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
	public function getMounts() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Mounted file systems');

		// File
		$contents = LinfoCommon::getContents('/proc/mounts', false);

		// Can't?
		if ($contents == false)
			$this->error->add('Linfo Core', '/proc/mounts does not exist');

		// Parse
		if (@preg_match_all('/^(\S+) (\S+) (\S+) (.+) \d \d$/m', $contents, $match, PREG_SET_ORDER) === false)
			$this->error->add('Linfo Core', 'Error parsing /proc/mounts');

		// Return these
		$mounts = array();

		// Populate
		foreach ($match as $mount) {
			
			// Should we not show this?
			if (in_array($mount[1], $this->settings['hide']['storage_devices']) || in_array($mount[3], $this->settings['hide']['filesystems']))
				continue;

			// Should we not show this? (regex)
			if (isset($this->settings['hide']['mountpoints_regex']) && is_array($this->settings['hide']['mountpoints_regex'])) {
				foreach ($this->settings['hide']['mountpoints_regex'] as $regex) {
					if (@preg_match($regex, $mount[2])) 
						continue 2;
				}
			}
			
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
	public function getDevs() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Hardware Devices');

		// Location of useful paths
		$pci_ids = LinfoCommon::locateActualPath(array(
			'/usr/share/misc/pci.ids',	// debian/ubuntu
			'/usr/share/pci.ids',		// opensuse
			'/usr/share/hwdata/pci.ids',	// centos. maybe also redhat/fedora
		));
		$usb_ids = LinfoCommon::locateActualPath(array(
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
	public function getRAID() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('RAID');
		
		// Store it here
		$raidinfo = array();

		// mdadm?
		if (array_key_exists('mdadm', (array)$this->settings['raid']) && !empty($this->settings['raid']['mdadm'])) {

			// Try getting contents
			$mdadm_contents = LinfoCommon::getContents('/proc/mdstat', false);

			// No?
			if ($mdadm_contents === false)
				$this->error->add('Linux softraid mdstat parser', '/proc/mdstat does not exist.');

			// Parse
			@preg_match_all('/(\S+)\s*:\s*(\w+)\s*raid(\d+)\s*([\w+\[\d+\] (\(\w\))?]+)\n\s+(\d+) blocks[^[]+\[(\d\/\d)\] \[([U\_]+)\]/mi', (string) $mdadm_contents, $match, PREG_SET_ORDER);

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
					'size' => LinfoCommon::byteConvert($array[5]*1024),
					'count' => $array[6],
					'chart' => $array[7]
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
	public function getLoad() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Load Averages');

		// File that has it
		$file = '/proc/loadavg';

		// Get contents
		$contents = LinfoCommon::getContents($file, false);

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
	public function getNet() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Network Devices');

		// Hold our return values
		$return = array();

		// Get values for each device
		foreach ((array) @glob('/sys/class/net/*', GLOB_NOSORT) as $path) {

			$nic = basename($path);

			// States
			$operstate_contents = LinfoCommon::getContents($path.'/operstate');
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

			if ($state = 'unknown' && file_exists($path.'/carrier')) {
				 $carrier = LinfoCommon::getContents($path.'/carrier', false);
				if (!empty($carrier)) 
					$state = 'up'; 
				else
					$state = 'down'; 
			}

			// Try the weird ways of getting type (https://stackoverflow.com/a/16060638)
			$type = false;
			$typeCode = LinfoCommon::getIntFromFile($path.'/type');

			if ($typeCode == 772)
				$type = 'Loopback';
			elseif ($typeCode == 65534)
				$type = 'Tunnel';
			elseif ($typeCode == 776)
				$type = 'IPv6 in IPv4';

			if (!$type) {
				$type_contents = strtoupper(LinfoCommon::getContents($path.'/device/modalias'));
				list($type_match) = explode(':', $type_contents, 2);

				if (in_array($type_match, array('PCI', 'USB'))) {
					$type = 'Ethernet ('.$type_match.')';

					// Driver maybe?
					if (($uevent_contents = @parse_ini_file($path.'/device/uevent')) && isset($uevent_contents['DRIVER']))
						$type .= ' ('.$uevent_contents['DRIVER'].')';
				}
				elseif ($type_match == 'VIRTIO')
					$type = 'VirtIO';
				elseif ($type_contents == 'XEN:VIF')
					$type = 'Xen (VIF)';
				elseif ($type_contents == 'XEN-BACKEND:VIF')
					$type = 'Xen Backend (VIF)';
				elseif (is_dir($path.'/bridge'))
					$type = 'Bridge';
				elseif (is_dir($path.'/bonding'))
					$type = 'Bond';

				// TODO find some way of finding out what provides the virt-specific kvm vnet devices
			}

			$speed = LinfoCommon::getIntFromFile($path.'/speed');

			// Save and get info for each
			$return[$nic] = array(

				// Stats are stored in simple files just containing the number
				'recieved' => array(
					'bytes' => LinfoCommon::getIntFromFile($path.'/statistics/rx_bytes'),
					'errors' => LinfoCommon::getIntFromFile($path.'/statistics/rx_errors'),
					'packets' => LinfoCommon::getIntFromFile($path.'/statistics/rx_packets')
				),
				'sent' => array(
					'bytes' => LinfoCommon::getIntFromFile($path.'/statistics/tx_bytes'),
					'errors' => LinfoCommon::getIntFromFile($path.'/statistics/tx_errors'),
					'packets' => LinfoCommon::getIntFromFile($path.'/statistics/rx_packets')
				),

				// These were determined above
				'state' => $state,
				'type' => $type ?: 'N/A',
				'port_speed' => $speed > 0 ? $speed : false
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
	public function getBattery() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Batteries');
		
		// Return values
		$return = array();

		// Here they should be
		$bats = (array) @glob('/sys/class/power_supply/BAT*', GLOB_NOSORT);
	
		// Get vals for each battery
		foreach ($bats as $b) {

			foreach(array($b.'/manufacturer', $b.'/status') as $f)
				if (!is_file($f))
					continue 2;

			// Get these from the simple text files
			switch (true) {
				case is_file($b.'/energy_full'):
					$charge_full = LinfoCommon::getIntFromFile($b.'/energy_full');
					$charge_now = LinfoCommon::getIntFromFile($b.'/energy_now');
					break;
				case is_file($b.'/charge_full'):
					$charge_full = LinfoCommon::getIntFromFile($b.'/charge_full');
					$charge_now = LinfoCommon::getIntFromFile($b.'/charge_now');
					break;
				default:
					continue;
					break;
			}

			// Alleged percentage
			$percentage = $charge_now != 0 && $charge_full != 0 ? (round($charge_now / $charge_full, 4) * 100) : '?';

			// Save result set
			$return[] = array(
				'charge_full' => $charge_full,
				'charge_now' => $charge_now,
				'percentage' => (is_numeric($percentage) && $percentage > 100 ? 100 : $percentage),
				'device' => LinfoCommon::getContents($b.'/manufacturer') . ' ' . LinfoCommon::getContents($b.'/model_name', 'Unknown'),
				'state' => LinfoCommon::getContents($b.'/status', 'Unknown')
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
	public function getWifi() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Wifi');

		// Return these
		$return = array();

		// In here
		$contents = LinfoCommon::getContents('/proc/net/wireless');

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
	public function getSoundCards() {
		
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
		$contents = LinfoCommon::getContents($file);

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
	public function getProcessStats() {
		
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
		foreach ($processes as $process) {
			
			// Don't waste time if we can't use it
			if (!is_readable($process))
				continue;
			
			// Get that file's contents
			$status_contents = LinfoCommon::getContents($process);

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
	public function getServices() {
		
		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Services');

		// We allowed?
		if (empty($this->settings['show']['services']) || !is_array($this->settings['services']) || count($this->settings['services']) == 0)
			return array();

		// Temporarily keep statuses here
		$statuses = array();

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
				$cmdline_cache[$i] = explode("\x00", LinfoCommon::getContents($potential_paths[$i]));
			
			// Go through the list of executables to search for
			foreach ($this->settings['services']['executables'] as $service => $exec) {
				// Go through pid file list. for loops are faster than foreach
				for ($i = 0; $i < $num_paths; $i++) {
					$cmdline = $cmdline_cache[$i];
					$match = false;
					if (is_array($exec)) {
						$match = true;
						foreach ($exec as $argn => $argv) {
							if(isset($cmdline[$argn]) && $cmdline[$argn] != $argv)
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
			$pid = LinfoCommon::getContents($file, false);
			if ($pid != false && is_numeric($pid))
				$pids[$service] = $pid;
		}

		// Deal with PIDs
		foreach ($pids as $service => $pid) {
			$path = '/proc/'.$pid.'/status';
			$status_contents = LinfoCommon::getContents($path, false);
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
	public function getDistro() {
		
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

		$contents_distros = array(
			array(
				'file' => '/etc/redhat-release',
				'regex' => '/^CentOS.+release (?P<version>[\d\.]+) \((?P<codename>[^)]+)\)$/i',
				'distro' => 'CentOS'
			),
			array(
				'file' => '/etc/redhat-release',
				'regex' => '/^Red Hat.+release (?P<version>\S+) \((?P<codename>[^)]+)\)$/i',
				'distro' => 'RedHat'
			),
			array(
				'file' => '/etc/lsb-release',
				'closure' => create_function('$ini', '
					return ($info = @parse_ini_string($ini)) &&
						isset($info["DISTRIB_ID"]) &&
						isset($info["DISTRIB_RELEASE"]) &&
						isset($info["DISTRIB_CODENAME"]) ? array(
							"distro" => $info["DISTRIB_ID"],
							"version" => $info["DISTRIB_RELEASE"],
							"codename" => $info["DISTRIB_CODENAME"],
						) : false;')
			),
			array(
				'file' => '/etc/os-release',
				'closure' => create_function('$ini', '
					return ($info = @parse_ini_string($ini)) &&
						isset($info["ID"]) &&
						isset($info["VERSION"]) ? array(
							"distro" => $info["ID"],
							"version" => $info["VERSION"]
						) : false;')
			),
			array(
				'file' => '/etc/fedora-release',
				'regex' => '/^Fedora(?: Core)? release (?P<version>\d+) \((?P<codename>[^)]+)\)$/',
				'distro' => 'Fedora'
			),
			array(
				'file' => '/etc/gentoo-release',
				'regex' => '/(?P<version>[\d\.]+)$/',
				'distro' => 'Gentoo'
			),
			array(
				'file' => '/etc/SuSE-release',
				'regex' => '/^VERSION = (?P<version>[\d\.]+)$/m',
				'distro' => 'openSUSE'
			),
			array(
				'file' => '/etc/slackware-version',
				'regex' => '/(?P<version>[\d\.]+)$/',
				'distro' => 'Slackware'
			),
			array(
				'file' => '/etc/debian_version',
				'distro' => 'Debian'
			)
		);

		foreach ($contents_distros as $distro) {
			if (!($contents = LinfoCommon::getContents($distro['file'], false)))
				continue;
			if (isset($distro['closure']) && ($info = $distro['closure']($contents))) {
				return array(
					'name' => ucfirst($info['distro']),
					'version' => $info['version'].(isset($info['codename']) ? ' ('.ucfirst($info['codename']).')' : '')
				);
			}
			elseif (isset($distro['regex']) && preg_match($distro['regex'], $contents, $info)) {
				return array(
					'name' => $distro['distro'],
					'version' => $info['version'].(isset($info['codename']) ? ' ('.ucfirst($info['codename']).')' : '')
				);
			}
			elseif (isset($distro['distro'])) {
				return array(
					'name' => $distro['distro'],
					'version' => $contents
				);
			}
		}

		$existence_distros = array(
			'/etc/arch-release' => 'Arch',
			'/etc/mklinux-release' => 'MkLinux',
			'/etc/tinysofa-release ' => 'TinySofa',
			'/etc/turbolinux-release ' => 'TurboLinux',
			'/etc/yellowdog-release ' => 'YellowDog',
			'/etc/annvix-release ' => 'Annvix',
			'/etc/arklinux-release ' => 'Arklinux',
			'/etc/aurox-release ' => 'AuroxLinux',
			'/etc/blackcat-release ' => 'BlackCat',
			'/etc/cobalt-release ' => 'Cobalt',
			'/etc/immunix-release ' => 'Immunix',
			'/etc/lfs-release ' => 'Linux-From-Scratch',
			'/etc/linuxppc-release ' => 'Linux-PPC',
			'/etc/mklinux-release ' => 'MkLinux',
			'/etc/nld-release ' => 'NovellLinuxDesktop',
		);

		foreach ($existence_distros as $file => $distro) {
			if (is_file($file)) {
				return array(
					'name' => $distro,
					'version' => false
				);
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
	public function getCPUArchitecture() {
		return php_uname('m');
	}

	/**
	 * getNumLoggedIn
	 * 
	 * @access private
	 * @return number of logged in users with shells
	 */
	 public function getNumLoggedIn() {

		// Snag command line of every process in system
		$procs = glob('/proc/*/cmdline', GLOB_NOSORT);
		
		// Store unqiue users here
		$users = array();

		// Each process
		foreach ($procs as $proc) {

			// Does the process match a popular shell, such as bash, csh, etc?
			if (preg_match('/(?:bash|csh|zsh|ksh)$/', LinfoCommon::getContents($proc, ''))) {

				// Who owns it, anyway? 
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

	/**
	 * getVirtualization. Potentially not very accurate especially since you can virtualize hypervisors,
	 * kernel module names change frequently, you can load (some of) these modules if you aren't a host/guest, etc
	 *
	 * @access private
	 * @return array('type' => 'guest', 'method' => kvm or vmware or xen or openvz) or array('type' => 'host', 'methods' = ['intel', 'amd'])
	 */
	 public function getVirtualization() {

		// Time?
		if (!empty($this->settings['timer']))
			$t = new LinfoTimerStart('Determining virtualization type');

		// OpenVZ host?
		if (is_file('/proc/vz/version'))
			return array('type' => 'host', 'method' => 'OpenVZ');

		// OpenVZ guest?
		elseif (is_file('/proc/vz/veinfo'))
			return array('type' => 'guest', 'method' => 'OpenVZ');

		// Try getting kernel modules
		$modules = array();
		if (preg_match_all('/^(\S+)/m', LinfoCommon::getContents('/proc/modules', ''), $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match)	
				$modules[] = $match[1];
		}

		// VMware guest. Tested on debian under vmware fusion for mac...
		if (LinfoCommon::anyInArray(array('vmw_balloon', 'vmwgfx', 'vmw_vmci'), $modules))
			return array('type' => 'guest', 'method' => 'VMWare');

		// VMware Host! tested on rhel6 running vmware..workstation?
		if (LinfoCommon::anyInArray(array('vmnet', 'vmci', 'vmmon'), $modules))
			return array('type' => 'host', 'method' => 'VMWare');

		// Looks like it might be xen...
		if (LinfoCommon::anyInArray(array('xenfs', 'xen_gntdev', 'xen_evtchn', 'xen_blkfront', 'xen_netfront'), $modules) || is_dir('/proc/xen')) {

			// Guest or host?
			if (LinfoCommon::anyInArray(array('xen-netback', 'xen_blkback'), $modules) || strpos('control_d', LinfoCommon::getContents('/proc/xen/capabilities', '')) !== false)
				return array('type' => 'host', 'method' => 'Xen');
			else
				return array('type' => 'guest', 'method' => 'Xen');
		}

		// VirtualBox Host! Tested on lucid running vbox..
		if (in_array('vboxdrv', $modules))
			return array('type' => 'host', 'method' => 'VirtualBox');

		// VirtualBox Guest! Tested on wheezy under mac vbox
		if (in_array('vboxguest', $modules))
			return array('type' => 'guest', 'method' => 'VirtualBox');

		// Looks like it might be KVM HOST!
		if (in_array('kvm', $modules))
			return array('type' => 'host', 'method' => 'KVM');

		// Looks like it might be a KVM or QEMU guest! This is a bit lame since Xen can also use virtio but its less likely (?)
		if (LinfoCommon::anyInArray(array('virtio', 'virtio_balloon', 'virtio_pci', 'virtio_blk', 'virtio_net'), $modules))
			return array('type' => 'guest', 'method' => 'Qemu/KVM');

		// idk
		return false;
	 }

	/**
	 * Get overall CPU usage. Depends on determineCPUPercentage() being called prior
	 *
	 * @access private
	 */
	 public function getCPUUsage() {
		 return $this->cpu_percent['overall'] === false ? false : $this->cpu_percent['overall'];
	 }

	/**
	 * Parse lines from /proc/stat. Used by determineCPUPercentage function
	 *
	 * @access protected
	 */
	 private function cpuPercent($key, $line) {

		 // With each iteration we compare what we got to last time's version
		 // as the file changes every milisecond or something
		 static $prev = array();

		 // Using regex/explode is excessive here, not unlike rest of linfo :/
		 $ret = sscanf($line, '%Lu %Lu %Lu %Lu %Lu %Lu %Lu %Lu');

		 // Negative? That's crazy talk now
		 foreach ($ret as $k => $v) {
			 if ($v < 0)
				 $ret[$k] = 0;
		 }

		 // First time; set our vals
		 if (!isset($prev[$key]))
			 $prev[$key] = $ret;

		 // Subsequent time; difference with last time
		 else {
			 $orig = $ret;
			 foreach ($ret as $k => $v)
				 $ret[$k] -= $prev[$key][$k];
			 $prev[$key] = $orig;
		 }

		 // Refer back to top.c for the reasoning here. I just copied the algorithm without
		 // trying to understand why.
		 $scale = 100.0 / (float)array_sum($ret);
		 $cpu_percent = $ret[0] * $scale;

		 return round($cpu_percent, 2);
	 }

	/**
	 * Most controersial and different function in linfo. Updates $this->cpu_percent array. Sleeps 1 second
	 * to do this which is how it gets accurate details. Code stolen from procps' source for the Linux top command
	 *
	 * @access private
	 * @void
	 */
	 public function determineCPUPercentage() {
		 // Time?
		 if (!empty($this->settings['timer']))
			 $t = new LinfoTimerStart('Determining CPU usage');

		 $iterations = 2;

		 // Probably only inline function here. Only used once so it makes sense.

		 for ($i = 0; $i < $iterations; $i++) {
			 $contents = LinfoCommon::getContents('/proc/stat', false);

			 // Yay we can't read it so we won't sleep below!
			 if (!$contents)
				 continue;

			 // Overall system CPU usage
			 if (preg_match('/^cpu\s+(.+)/', $contents, $m))
				 $this->cpu_percent['overall'] = $this->cpuPercent('overall', $m[1]);

			 // CPU usage per CPU
			 if (preg_match_all('/^cpu(\d+)\s+(.+)/m', $contents, $cpus, PREG_SET_ORDER)) {
				 foreach ($cpus as $cpu)
					 $this->cpu_percent['cpus'][$cpu[1]] = $this->cpuPercent('c'.$cpu[1], $cpu[2]);
			 }

			 // Following two lines make me want to puke as they go against everything linfo stands for
			 // this functionality will always be disabled by default
			 // Sleep *between* iterations and only if we're doing at least two of them
			 if ($iterations > 1 && $i != $iterations - 1)
				 sleep(1);
		 }
	 }

	/**
	 * Get brand/name of motherboard/server through /sys' interface to dmidecode
	 *
	 * @access public
	 */
	 public function getModel() {
		$info = array();
		$vendor = LinfoCommon::getContents('/sys/devices/virtual/dmi/id/board_vendor', false);
		$name = LinfoCommon::getContents('/sys/devices/virtual/dmi/id/board_name', false);
		$product = LinfoCommon::getContents('/sys/devices/virtual/dmi/id/product_name', false);

		if (!$name)
			return false;
		
		// Don't add vendor to the mix if the name starts with it
		if ($vendor && strpos($name, $vendor) !== 0)
			$info[] = $vendor;

		$info[] = $name;

		$infostr = implode(' ', $info);

		// product name is usually bullshit, but *occasionally* it's a useful name of the computer, such as
		// dell latitude e6500 or hp z260
		if ($product && strpos($name, $product) === false && strpos($product, 'Filled') === false)
			return $product . ' ('.$infostr.')';
		else
			return $infostr;
	 }
 }
