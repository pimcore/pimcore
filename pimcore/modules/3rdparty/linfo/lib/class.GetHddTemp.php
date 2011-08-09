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
 * Exception
 */
class GetHddTempException extends Exception{}

/*
 * Deal with hddtemp
 */
class GetHddTemp {

	// Store these
	protected $mode, $host, $port, $settings;

	// Default socket connect timeout
	const timeout = 3;

	// Start us off
	public function __construct($settings) {
		$this->settings = $settings;
	}

	// Localize mode
	public function setMode($mode) {
		$this->mode = $mode;
	}

	/*
	 *  For connecting to HDDTemp daemon
	 */

	// Localize host and port
	public function setAddress($host, $port = 7634) {
		$this->host = $host;
		$this->port = $port;
	}

	// Connect to host/port and get info
	private function getSock() {
		// Try connecting
		if (!($sock = @fsockopen($this->host, $this->port, $errno, $errstr, self::timeout)))
			throw new GetHddTempException('Error connecting');

		// Try getting stuff
		$buffer = '';
		while ($mid = @fgets($sock))
			$buffer .= $mid;

		// Quit
		@fclose($sock);

		// Output:
		return $buffer;
	}

	// Parse and return info from daemon socket
	private function parseSockData($data) {
		
		// Kill surounding ||'s and split it by pipes
		$drives = explode('||', trim($data, '|'));

		// Return oour stuff here
		$return = array();

		// Go through each
		$num_drives = count($drives);
		for($i = 0; $i < $num_drives; $i++) {

			// This drive
			$drive = $drives[$i];

			// Extract stuff from it
			list($path, $name, $temp, $unit) = explode('|', trim($drive));

			// Ignore /dev/sg? 
			if (!empty($this->settings['hide']['sg']) && substr($path, 0, 7) == '/dev/sg')
				continue;

			// Ignore no longer existant devices?
			if (!file_exists($path) && is_readable('/dev'))
				continue;
			
			// Save it
			$return[] = array(
				'path' => $path,
				'name' => $name,
				'temp' => $temp,
				'unit' => strtoupper($unit)
			);
		}

		// Give off results
		return $return;
	}

	/*
	 * For parsing the syslog looking for hddtemp entries
	 * POTENTIALLY BUGGY -- only tested on debian/ubuntu flavored syslogs
	 * Also slow as balls as it parses the entire syslog instead of
	 * using something like tail
	 */
	private function parseSysLogData() {
		$file = '/var/log/syslog';
		if (!is_file($file) || !is_readable($file)) {
			return array();
		}
		$devices = array();
		foreach (getLines($file) as $line) {
			if (preg_match('/\w+\s*\d+ \d{2}:\d{2}:\d{2} \w+ hddtemp\[\d+\]: (.+): (.+): (\d+) ([CF])/i', trim($line), $match) == 1) {
				// Replace current record of dev with updated temp
				$devices[$match[1]] = array($match[2], $match[3], $match[4]);
			}
		}
		$return = array();
		foreach ($devices as $dev => $stat)
			$return[] = array(
				'path' => $dev,
				'name' => $stat[0],
				'temp' => $stat[1],
				'unit' => strtoupper($stat[2])
			);
		return $return;
	}

	/*
	 * Wrapper function around the private ones here which do the
	 * actual work, and returns temps
	 */

	// Use supplied mode, and optionally host/port, to get temps and return them
	public function work() {

		// Deal with differences in mode
		switch ($this->mode) {

			// Connect to daemon mode
			case 'daemon':
				$sockResult = $this->getSock();
				$temps = $this->parseSockData($sockResult);
				return $temps;
			break;

			// Syslog every n seconds
			case 'syslog':
				$temps = $this->parseSysLogData();
				return $temps;
			break;

			// Some other mode
			default:
				throw new GetHddTempException('Not supported mode');
			break;
		}
	}
}
