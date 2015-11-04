<?php

/*

This implements a ddhcpd.leases parser for dhcp3 servers. 

Installation: 
 - The following lines must be added to your config.inc.php:
   $settings['extensions']['dhcpd3_leases'] = true; 
   $settings['dhcpd3_hide_mac'] = true;  // set to false to show mac addresses

*/

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
 * Get status on dhcp3 leases
 */
class ext_dhcpd3_leases implements LinfoExtension {
	
	// How dates should look
	const
		DATE_FORMAT = 'm/d/y h:i A';
	
	// Store these tucked away here
	private
		$_LinfoError,
		$_hide_mac,
		$_res,
		$_leases = array();

	/**
	 * localize important stuff
	 * 
	 * @access public
	 */
	public function __construct(Linfo $linfo) {

    $settings = $linfo->getSettings();

		// Localize error handler
		$this->_LinfoError = LinfoError::Singleton();

		// Should we hide mac addresses, to prevent stuff like mac address spoofing?
		$this->_hide_mac = array_key_exists('dhcpd3_hide_mac', $settings) ? (bool) $settings['dhcpd3_hide_mac'] : false;

		// Find leases file
		$this->_leases_file = LinfoCommon::locateActualPath(array(
			'/var/lib/dhcp/dhcpd.leases', // modern-er debian
			'/var/lib/dhcp3/dhcpd.leases',	// debian/ubuntu/others probably
			'/var/lib/dhcpd/dhcpd.leases',	// Possibly redhatish distros and others
			'/var/state/dhcp/dhcpd.leases',	// Arch linux, maybe others
			'/var/db/dhcpd/dhcpd.leases',	// FreeBSD 
			'/var/db/dhcpd.leases',		// OpenBSD/NetBSD/Darwin(lol)/DragonFLY afaik
		));
	}

	/**
	 * Deal with it
	 * 
	 * @access private
	 */
	private function _call () {
		// Time this
		$t = new LinfoTimerStart('dhcpd3 leases extension');

		// We couldn't find leases file?
		if ($this->_leases_file === false) {
			$this->_LinfoError->add('dhcpd3 leases extension', 'couldn\'t find leases file');
			$this->_res = false;
			return false;
		}

		// Get contents
		$contents = LinfoCommon::getContents($this->_leases_file, false);

		// Couldn't?
		if ($contents === false) {
			$this->_LinfoError->add('dhcpd3 leases extension', 'Error getting contents of leases file');
			$this->_res = false;
			return false;
		}

		// All dates in the file are in UTC format. Attempt finding out local time zone to convert UTC to local.
		// This prevents confusing the hell out of people.
		$do_date_conversion = false; 
		$local_timezone = false;

		// Make sure we have what we need. Stuff this requires doesn't exist on certain php installations
		if (function_exists('date_default_timezone_get') && class_exists('DateTime') && class_exists('DateTimeZone')) {
			// I only want this called once, hence value stored here. It also might fail
			$local_timezone = @date_default_timezone_get(); 

			// Make sure it didn't fail
			if ($local_timezone !== false && is_string($local_timezone))
				$do_date_conversion = true; // Say we'll allow conversion later on
		}

		// Get it into lines
		$lines = explode("\n", $contents);
		
		// Store temp entries here
		$curr = false;

		// Parse each line, while ignoring certain useless'ish values
		// I'd do a single preg_match_all() using multiline regex, but the values in each lease block are inconsistent. :-/
		for ($i = 0, $num_lines = count($lines); $i < $num_lines; $i++) {
			
			// Kill padding whitespace
			$lines[$i] = trim($lines[$i]);

			// Last line in entry
			if ($lines[$i] == '}') {
				// Have we a current entry to save?
				if (is_array($curr))
					$this->_leases[] = $curr;

				// Make it empty for next time
				$curr = false;
			}

			// First line in entry. Save IP
			elseif (preg_match('/^lease (\d+\.\d+\.\d+\.\d+) \{$/', $lines[$i], $m)) {
				$curr = array('ip' => $m[1]);
			}

			// Line with lease start
			elseif ($curr && preg_match('/^starts \d+ (\d+\/\d+\/\d+ \d+:\d+:\d+);$/', $lines[$i], $m)) {

				// Get it in unix time stamp for prettier formatting later and easier tz offset conversion
				$curr['lease_start'] = strtotime($m[1]);

				// Handle offset conversion
				if ($do_date_conversion) {
					
					// This handy class helps out with timezone offsets. Pass it original date, not unix timestamp
					$d = new DateTime($m[1], new DateTimeZone($local_timezone));
					$offset = $d->getOffset();

					// If ofset looks good, deal with it
					if (is_numeric($offset) && $offset != 0)
						$curr['lease_start'] += $offset;
				}
			}
			
			// Line with lease end
			elseif ($curr && preg_match('/^ends \d+ (\d+\/\d+\/\d+ \d+:\d+:\d+);$/', $lines[$i], $m)) {

				// Get it in unix time stamp for prettier formatting later and easier tz offset conversion
				$curr['lease_end'] = strtotime($m[1]);

				// Handle offset conversion
				if ($do_date_conversion) {
					
					// This handy class helps out with timezone offsets. Pass it original date, not unix timestamp
					$d = new DateTime($m[1], new DateTimeZone($local_timezone));
					$offset = $d->getOffset();

					// If ofset looks good, deal with it
					if (is_numeric($offset) && $offset != 0)
						$curr['lease_end'] += $offset;
				}

				// Is this old?
				// The file seems to contain all leases since the dhcpd server was started for the first time
				if (time() > $curr['lease_end']) {

					// Kill current entry and ignore any following parts of this lease 
					$curr = false;

					// Jump out right now
					continue;
				}
			}
			
			// Line with MAC address
			elseif (!$this->_hide_mac && $curr && preg_match('/^hardware ethernet (\w+:\w+:\w+:\w+:\w+:\w+);$/', $lines[$i], $m)) {
				$curr['mac'] = $m[1];
			}
			
			// [optional] Line with hostname
			elseif ($curr && preg_match('/^client\-hostname "([^"]+)";$/', $lines[$i], $m)) {
				$curr['hostname'] = $m[1];
			}
		}
	}
	
	/**
	 * Do the job
	 * 
	 * @access public
	 */
	public function work() {
		$this->_call();
	}

	/**
	 * Return result
	 * 
	 * @access public
	 * @return false on failure|array of the leases
	 */
	public function result() {
		// Don't bother if it didn't go well
		if ($this->_res === false) {
			return false;
		}

		// Store rows here
		$rows = array();

		// Start showing connections
		$rows[] = array(
			'type' => 'header',
			'columns' =>

			// Not hiding mac address?
			!$this->_hide_mac ? array(
				'IP Address',
				'MAC Address',
				'Hostname',
				'Lease Start',
				'Lease End'
			) :

			// Hiding it indeed
				array(
				'IP Address',
				'Hostname',
				'Lease Start',
				'Lease End'
			)
		);

		// Append each lease
		for ($i = 0, $num_leases = count($this->_leases); $i < $num_leases; $i++)
			$rows[] = array(
				'type' => 'values',
				'columns' =>
				
				// Not hiding mac addresses?
				!$this->_hide_mac ? array(
					$this->_leases[$i]['ip'],
					$this->_leases[$i]['mac'],
					array_key_exists('hostname', $this->_leases[$i]) ?
						$this->_leases[$i]['hostname'] : '<em>unknown</em>',
					date(self::DATE_FORMAT, $this->_leases[$i]['lease_start']),
					date(self::DATE_FORMAT, $this->_leases[$i]['lease_end'])
				):

				// Hiding them indeed
				array(
					$this->_leases[$i]['ip'],
					array_key_exists('hostname', $this->_leases[$i]) ?
						$this->_leases[$i]['hostname'] : '<em>unknown</em>',
					date(self::DATE_FORMAT, $this->_leases[$i]['lease_start']),
					date(self::DATE_FORMAT, $this->_leases[$i]['lease_end'])
				)
			);
		
		// Give it off
		return array(
			'root_title' => 'DHCPD IP Leases',
			'rows' => $rows
		);
	}
}
