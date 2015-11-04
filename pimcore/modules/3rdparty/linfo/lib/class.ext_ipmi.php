<?php

/*

This implements a ipmi status checker for temps/voltages

Installation: 
 - The following lines must be added to your config.inc.php:
   $settings['extensions']['ipmi'] = true; 

 - The ipmitool command most likely needs to be run as root, so, 
   if you don't have php running as root, configure sudo appropriately
   for the user the php scripts are running as, comment out 'Defaults    requiretty' in your sudoers
   file, and add 'ipmitool' to the $settings['sudo_apps'] array in config.inc.php

*/

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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Linfo. If not, see <http://www.gnu.org/licenses/>.
 * 
*/

defined('IN_LINFO') or exit; 

/**
 * IPMI extension for temps/voltages
 * @author Joseph Gillotti
 */
class ext_ipmi implements LinfoExtension {
	
	// Minimum version of Linfo required
	const
		LINFO_INTEGRATE	= true,
		EXTENSION_NAME = 'ipmi';
	
	// Store these tucked away here
	private
		$_CallExt,
		$_LinfoError,
    $linfo;

	// Start us off
	public function __construct(Linfo $linfo) {
    $this->linfo = $linfo;
		$this->_LinfoError = LinfoError::Singleton();
		$this->_CallExt = new CallExt;
		$this->_CallExt->setSearchPaths(array('/usr/bin', '/usr/local/bin', '/sbin', '/usr/local/sbin'));
	}

	// Work it, baby
	public function work() {
		
    $info = &$this->linfo->getInfo();

		// Make sure this is an array
		$info['Temps'] = (array) $info['Temps'];

		// Time this
		$t = new LinfoTimerStart(self::EXTENSION_NAME.' Extension');

		// Deal with calling it
		try {
			$result = $this->_CallExt->exec('ipmitool', ' sdr');
		}
		catch (CallExtException $e) {
			// messed up somehow
			$this->_LinfoError->add(self::EXTENSION_NAME.' Extension', $e->getMessage());
			return false;
		}

		// Match it up
		if (!preg_match_all('/^([^|]+)\| ([\d\.]+ (?:Volts|degrees [CF]))\s+\| ok$/m', $result, $matches, PREG_SET_ORDER))
			return;
		
		// Go through with it
		foreach ($matches as $m) {

			// Separate them by normal spaces
			$v_parts = explode(' ', trim($m[2]));
			
			// Deal with the type of it
			switch ($v_parts[1]) {
				case 'Volts':
					$unit = 'v';
				break;
				case 'degrees':
					$unit = $v_parts[2];
				break;
				default:
					$unit = '';
				break;
			}

			// Save this one
			$info['Temps'][] = array(
				'path' => 'N/A',
				'name' => trim($m[1]),
				'temp' => $v_parts[0],
				'unit' => $unit
			);
		}
	}

	// Not needed
	public function result() {
		return false;
	}
}
