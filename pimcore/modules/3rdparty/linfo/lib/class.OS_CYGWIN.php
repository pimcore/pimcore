<?php


class OS_CYGWIN {
	
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
		if (!is_dir('/proc'))
			throw new GetInfoException('This needs access to /proc to work.');
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
			'RAM' => empty($this->settings['show']['ram']) ? array() : $this->getRam(),
			'HD' => empty($this->settings['show']['hd']) ? '' : $this->getHD(),
			'Mounts' => empty($this->settings['show']['mounts']) ? array() : $this->getMounts(),
			'Load' => empty($this->settings['show']['load']) ? array() : $this->getLoad(),
			'HostName' => empty($this->settings['show']['hostname']) ? '' : $this->getHostName(),
			'UpTime' => empty($this->settings['show']['uptime']) ? '' : $this->getUpTime(),
			'CPU' => empty($this->settings['show']['cpu']) ? array() : $this->getCPU(),
			'CPUArchitecture' => empty($this->settings['show']['cpu']) ? array() : $this->getCPUArchitecture(),
			'processStats' => empty($this->settings['show']['process_stats']) ? array() : $this->getProcessStats(),
			'services' => empty($this->settings['show']['process_stats']) ? array() : $this->getServices()
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
		return 'Cygwin';
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

		// Return it
		return $contents;
	}

	/**
	 * getHostName 
	 * 
	 * @access private
	 * @return string the host name
	 */
	private function getHostName() {
		return php_uname('n');
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
		@preg_match_all('/^(\S+)\s+(\S+)\s+(\d+)\s(\d+)[^$]*$/m', getContents($procFileSwap), $matches, PREG_SET_ORDER);
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
		$return['free'] = $memVals['MemFree']*1024;
		$return['swapTotal'] = $memVals['SwapTotal']*1024;
		$return['swapFree'] = $memVals['SwapFree']*1024;
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
			$return[end(explode('/', $path))] = array(

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

			// Get these from the simple text files
			$charge_full = get_int_from_file($b.'/charge_full');
			$charge_now = get_int_from_file($b.'/charge_now');

			// Save result set
			$return[] = array(
				'charge_full' => $charge_full,
				'charge_now' => $charge_now,
				'percentage' => ($charge_now != 0 && $charge_full != 0 ? (round($charge_now / $charge_full, 4) * 100) : '?').'%',
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

		$cards = array();

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
	 * getCPUArchitecture
	 * 
	 * @access private
	 * @return string the arch and bits
	 */
	private function getCPUArchitecture() {
		return php_uname('m');
	}

}
