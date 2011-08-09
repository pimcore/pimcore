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
class GetSensordException extends Exception{}

/*
 * Main class
 */

class GetSensord {
	
	public function work() {
		$temps = $this->parseSysLog();
		return $temps;
	}

	private function parseSysLog() {

		/*
		 * For parsing the syslog looking for sensord entries
		 * POTENTIALLY BUGGY -- only tested on debian/ubuntu flavored syslogs
		 * Also slow as balls as it parses the entire syslog instead of
		 * using something like tail
		 */
		$file = '/var/log/syslog';
		if (!is_file($file) || !is_readable($file)) {
			return array();
		}
		$devices = array();
		foreach (getLines($file) as $line) {
			if (preg_match('/\w+\s*\d+ \d{2}:\d{2}:\d{2} \w+ sensord:\s*(.+):\s*(.+)/i', trim($line), $match) == 1) {
				// Replace current record of dev with updated temp
				$devices[$match[1]] = $match[2];
			}
		}
		$return = array();
		foreach ($devices as $dev => $stat)
			$return[] = array(
				'path' => 'N/A', // These likely won't have paths
				'name' => $dev,
				'temp' => $stat,
				'unit' => '' // Usually included in above
			);
		return $return;
	}
}
