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
class GetMbMonException extends Exception{}

/*
 * Deal with MbMon
 */
class GetMbMon {
	// Store these
	protected $host, $port;

	// Default socket connect timeout
	const timeout = 3;

	// Localize host and port
	public function setAddress($host, $port = 411) {
		$this->host = $host;
		$this->port = $port;
	}

	// Connect to host/port and get info
	private function getSock() {
		// Try connecting
		if (!($sock = @fsockopen($this->host, $this->port, $errno, $errstr, self::timeout)))
			throw new GetMbMonException('Error connecting');

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

		$return = array();

		$lines = (array) explode("\n", trim($data));

		foreach ($lines as $line) {
			if (preg_match('/(\w+)\s*:\s*([-+]?[\d\.]+)/i', $line, $match) == 1)
				$return[] = array(
					'path' => 'N/A',
					'name' => $match[1],
					'temp' => $match[2],
					'unit' => '' // TODO
				);
		}

		return $return;
	}

	// Do work and return temps
	public function work() {
		$sockResult = $this->getSock();
		$temps = $this->parseSockData($sockResult);
		return $temps;
	}
}
