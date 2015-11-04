<?php

/*

This impliments a CUPS printer queue status parser.

Installation: 
 - The following lines must be added to your config.inc.php:
   $settings['extensions']['cups'] = true; 

*/

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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Linfo. If not, see <http://www.gnu.org/licenses/>.
 * 
*/

defined('IN_LINFO') or exit; 

/*
 * Get info on a cups install by running lpq
 */
class ext_cups implements LinfoExtension {

	// Store these tucked away here
	private
		$_CallExt,
		$_LinfoError,
		$_res;

	// Localize important classes
	public function __construct(Linfo $linfo) {
		$this->_LinfoError = LinfoError::Singleton();
		$this->_CallExt = new CallExt;
		$this->_CallExt->setSearchPaths(array('/usr/bin', '/usr/local/bin', '/sbin', '/usr/local/sbin'));
	}

	// call lpq and parse it
	private function _call() {
		
		// Time this
		$t = new LinfoTimerStart('CUPS extension');

		// Deal with calling it
		try {
			$result = $this->_CallExt->exec('lpstat', '-p -o -l');
		}
		catch (CallExtException $e) {
			// messed up somehow
			$this->_LinfoError->add('CUPS Extension', $e->getMessage());
			$this->_res = false;

			// Don't bother going any further
			return false;
		}

		// Split it into lines
		$lines = explode("\n", $result);

		// Hold temporarily values here
		$printers = array();
		$queue = array();
		$begin_queue_list = false;

		// Go through it line by line
		for ($i = 0, $num = count($lines); $i < $num; $i++) {

			// So regexes don't break on endlines
			$lines[$i] = trim($lines[$i]);

			// If there are no entries, don't waste time and end here
			if ($lines[$i] == 'no entries') {
				break;	
			}

			elseif (preg_match('/^printer (.+) is idle\. (.+)$/', $lines[$i], $printers_match) == 1) {
				$printers[] = array(
					'name' => str_replace('_', ' ', $printers_match[1]),
					'status' => $printers_match[2]
				);
			}

			// A printer entry
			elseif (preg_match('/^(.+)+ is (ready|ready and printing|not ready)$/', $lines[$i], $printers_match) == 1) {
				$printers[] = array(
					'name' => str_replace('_', ' ', $printers_match[1]),
					'status' => $printers_match[2]
				);
			}

			// The beginning of the queue list
			elseif (preg_match('/^Rank\s+Owner\s+Job\s+File\(s\)\s+Total Size$/', $lines[$i])) {
				$begin_queue_list = true;
			}

			// A job in the queue
			elseif ($begin_queue_list && preg_match('/^([a-z0-9]+)\s+(\S+)\s+(\d+)\s+(.+)\s+(\d+) bytes$/', $lines[$i], $queue_match)) {
				$queue[] = array(
					'rank' => $queue_match[1],
					'owner' => $queue_match[2],
					'job' => $queue_match[3],
					'files' => $queue_match[4],
					'size' => LinfoCommon::byteConvert($queue_match[5])
				);
			}
		}
		
		// Save result lset
		$this->_res = array(
			'printers' => $printers,
			'queue' => $queue
		);

		// Apparent success
		return true;
	}

	// Called to get working
	public function work() {
		$this->_call();
	}

	// Get result. Essentially take results and make it usable by the LinfoCommon::createTable function
	public function result() {

		// Don't bother if it didn't go well
		if ($this->_res == false)
			return false;

		// it did; continue
		else {

			// Store rows here
			$rows = array();

			// start off printers list
			$rows[] = array (
				'type' => 'header',
				'columns' => array(
					array(5, 'Printers')
				)
			);
			$rows[] = array (
				'type' => 'header',
				'columns' => array(
					'Name',
					array(4, 'Status')
				)
			);
			
			// show printers if we have them
			if (count($this->_res['printers']) == 0)
				$rows[] = array('type' => 'none', 'columns' => array(array(5, 'None found')));
			else {
				foreach ($this->_res['printers'] as $printer)
					$rows[] = array(
						'type' => 'values',
						'columns' => array(
							$printer['name'],
							array(4, $printer['status'])
						)
					);
			}

			// show printer queue list
			$rows[] = array(
				'type' => 'header',
				'columns' => array(
					array(5, 'Queue')
				)
			);
			
			$rows[] = array (
				'type' => 'header',
				'columns' => array(
					'Rank',
					'Owner',
					'Job',
					'Files',
					'Size',
				)
			);

			// Go through each item in the lsit
			if (count($this->_res['queue']) == 0)
				$rows[] = array('type' => 'none', 'columns' => array(array(5, 'Empty')));
			else {
				foreach ($this->_res['queue'] as $job)
					$rows[] = array(
						'type' => 'values',
						'columns' => array(
							$job['rank'],
							$job['owner'],
							$job['job'],
							$job['files'],
							$job['size'],	
						)
					);
			}



			// give info
			return array(
				'root_title' => 'CUPS Printer Status',
				'rows' => $rows
			);
		}
	}
}
