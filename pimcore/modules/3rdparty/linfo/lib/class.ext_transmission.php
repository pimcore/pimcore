<?php

/*

This implements a transmission-remote parsing extension which displays status of running torrents

Installation: 
 - The following lines must be added to your config.inc.php:
   $settings['extensions']['transmission'] = true; 
   $settings['transmission_auth'] = array(
	//'user' => 'jim', # Both of these must exist if you wish to use auth
	//'pass' => 'pwnz!'
   );
   $settings['transmission_host'] = array(
   	// 'server' => 'localhost',	# uncomment to set a specific host
	// 'port' => 9091		# uncomment to set a specific port
   ); 


   // If you want download/upload/ratio/duration stats, make sure the web server user can
   // read this folder, which is in the home directory of hteu ser that transmission is
   // running as
   $settings['transmission_folder'] = '/home/user/.config/transmission/';

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
 * Get status on transmission torrents
 */
class ext_transmission implements LinfoExtension {
	
	// Store these tucked away here
	private
		$_CallExt,
		$_LinfoError,
		$_res,
		$_torrents = array(),
		$_stats = false,
		$_auth,
		$_host;

	/**
	 * localize important stuff
	 * 
	 * @access public
	 */
	public function __construct(Linfo $linfo) {
		$settings = $linfo->getSettings();

		// Classes we need
		$this->_CallExt = new CallExt;
		$this->_CallExt->setSearchPaths(array('/usr/bin', '/usr/local/bin'));
		$this->_LinfoError = LinfoError::Singleton();

		// Transmission specific settings
		$this->_auth = array_key_exists('transmission_auth', $settings) ? (array) $settings['transmission_auth'] : array();
		$this->_host = array_key_exists('transmission_host', $settings) ? (array) $settings['transmission_host'] : array();

		// Path to home dir folder
		$this->_folder = array_key_exists('transmission_folder', $settings) && is_dir($settings['transmission_folder']) && is_readable($settings['transmission_folder']) ? $settings['transmission_folder'] : false;
	}

	/**
	 * Deal with it
	 * 
	 * @access private
	 */
	private function _call () {
		// Time this
		$t = new LinfoTimerStart('Transmission extension');

		// Deal with stats, if possible 
		if ($this->_folder && ($stats_contents = LinfoCommon::getContents($this->_folder.'stats.json', false)) && $stats_contents != false) {
			$stats_vals = @json_decode($stats_contents, true);
			if (is_array($stats_vals))
				$this->_stats = $stats_vals;
		}

		// Deal with calling it
		try {
			// Start up args
			$args = '';
			
			// Specifc host/port?
			if (array_key_exists('server', $this->_host) && array_key_exists('port', $this->_host) && is_numeric($this->_host['port']))
				$args .= ' \''.$this->_host['server'].'\':'.$this->_host['port'];

			// We need some auth?
			if (array_key_exists('user', $this->_auth) && array_key_exists('pass', $this->_auth))
				$args .= ' --auth=\''.$this->_auth['user'].'\':\''.$this->_auth['pass'].'\'';

			// Rest of it, including result
			$result = $this->_CallExt->exec('transmission-remote', $args . ' -l');
		}
		catch (CallExtException $e) {
			// messed up somehow
			$this->_LinfoError->add('Transmission extension: ', $e->getMessage());
			$this->_res = false;

			// Don't bother going any further
			return false;
		}
			
		$this->_res = true;

		// Get first line
		$first_line = reset(explode("\n", $result, 1));
		
		// Invalid host?
		if (strpos($first_line, 'Couldn\'t resolve host name') !== false) {
			$this->_LinfoError->add('Transmission extension: Invalid Host');
			$this->_res = false;
			return false;
		}

		// Invalid auth?
		if (strpos($first_line, '401: Unauthorized') !== false) {
			$this->_LinfoError->add('Transmission extension: Invalid Authentication');
			$this->_res = false;
			return false;
		}

		// Match teh torrents!
		if (preg_match_all('/^\s+(\d+)\*?\s+(\d+)\%\s+(\d+\.\d+ \w+|None)\s+((?:\d+ )?\w+)\s+(\d+\.\d+)\s+(\d+\.\d+)\s+(\d+\.\d+|None)\s+(Up & Down|Seeding|Idle|Stopped)\s+(.+)$/m', $result, $matches, PREG_SET_ORDER) > 0) {

			// Use this to sort them
			$sort_done = array();
			$sort_ratio = array();
			$sort_name = array();

			// Save the matches
			for($i = 0, $num = count($matches); $i < $num; $i++) {

				// Save this one
				$this->_torrents[$i] = array(
					'id' => $matches[$i][1],
					'done' => $matches[$i][2], 
					'have' => $matches[$i][3], 
					'eta' => $matches[$i][4],
					'up' => $matches[$i][5] * 1024, // always in KIB 
					'down' => $matches[$i][6] * 1024, // ^
					'ratio' => $matches[$i][7],
					'state' => $matches[$i][8],
					'torrent' => $matches[$i][9]
				);

				// Use this for sorting
				$sort_done[$i] = (int) $matches[$i][2];
				$sort_ratio[$i] = (float) $matches[$i][7];
				$sort_name[$i] = $matches[$i][9];
			}

			// Sort
			array_multisort($sort_done, SORT_DESC, $sort_ratio, SORT_DESC, $sort_name, SORT_ASC, $this->_torrents);
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
	 * @return false on failure|array of the torrents
	 */
	public function result() {
		// Don't bother if it didn't go well
		if ($this->_res === false) {
			return false;
		}
		// it did; continue

		// Store rows here
		$rows = array();

		// Start showing connections
		$rows[] = array(
			'type' => 'header',
			'columns' => array(
				'Torrent',
				array(1, 'Done', '10%'),
				'State',
				'Have',
				'Uploaded',
				'Time Left',
				'Ratio',
				'Up',
				'Down'
			)
		);

		// No torrents?
		if (count($this->_torrents) == 0)  {
			$rows[] = array(
				'type' => 'none',
				'columns' => array(
					array(9, 'None found')
				)
			);
		}
		else {
			
			// Store a total amount of certain torrents here:
			$status_tally = array();

			// As well as uploaded/downloaded
			$status_tally['Downloaded'] = 0;
			$status_tally['Uploaded'] = 0;
			$status_tally['Ratio'] = '';

			// Go through each torrent
			foreach ($this->_torrents as $torrent) {
			
				// Status count tally
				$status_tally[$torrent['state']] = !array_key_exists($torrent['state'], $status_tally) ? 1 : $status_tally[$torrent['state']] + 1;

				// Make some sense of the have so we can get it into bytes, which we can then have fun with
				$have_bytes = false;
				if ($torrent['have'] != 'None') {
					$have_parts = explode(' ', $torrent['have'], 2);
					if (is_numeric($have_parts[0]) && $have_parts[0] > 0) {
						switch ($have_parts[1]) {
							case 'TiB':
								$have_bytes = (float) $have_parts[0] * 1099511627776;
							break;
							case 'GiB':
								$have_bytes = (float) $have_parts[0] * 1073741824;
							break;
							case 'MiB':
								$have_bytes = (float) $have_parts[0] * 1048576;
							break;
							case 'KiB':
								$have_bytes = (float) $have_parts[0] * 1024;
							break;
						}
					}
				}

				// Try getting amount uploaded, based upon ratio and exact amount downloaded above
				$uploaded_bytes = false;
				if (is_numeric($have_bytes) && $have_bytes > 0 && is_numeric($torrent['ratio']) && $torrent['ratio'] > 0) 
					$uploaded_bytes = $torrent['ratio'] * $have_bytes;
				
				// Save amount uploaded/downloaded tally
				if (is_numeric($have_bytes) && $have_bytes > 0 && is_numeric($uploaded_bytes) && $uploaded_bytes > 0) {
					$status_tally['Downloaded'] += $have_bytes;
					$status_tally['Uploaded'] += $uploaded_bytes;
				}
				
				// Save result
				$rows[] = array(
					'type' => 'values',
					'columns' => array (
						wordwrap(htmlspecialchars($torrent['torrent']), 50, ' ', true),
						'<div class="bar_chart">
							<div class="bar_inner" style="width: '.(int) $torrent['done'].'%;">
								<div class="bar_text">
									'.($torrent['done'] ? $torrent['done'].'%' : '0%').'
								</div>
							</div>
						</div>
						',
						$torrent['state'],
						$have_bytes !== false ? LinfoCommon::byteConvert($have_bytes) : $torrent['have'],
						$uploaded_bytes !== false ? LinfoCommon::byteConvert($uploaded_bytes) : 'None',
						$torrent['eta'],
						$torrent['ratio'],
						LinfoCommon::byteConvert($torrent['up']) . '/s',
						LinfoCommon::byteConvert($torrent['down']) . '/s'
					)
				);
			}

			// Finish the size totals
			$status_tally['Ratio'] = $status_tally['Downloaded'] > 0 && $status_tally['Uploaded'] > 0 ? round($status_tally['Uploaded'] / $status_tally['Downloaded'], 2) : 'N/A';
			$status_tally['Downloaded'] = $status_tally['Downloaded'] > 0 ? LinfoCommon::byteConvert($status_tally['Downloaded']) : 'None';
			$status_tally['Uploaded'] = $status_tally['Uploaded'] > 0 ? LinfoCommon::byteConvert($status_tally['Uploaded']) : 'None';

			// Create a row for the tally of statuses
			if (count($status_tally) > 0) {

				// Store list of k: v'ish values here
				$tally_contents = array();

				// Populate that
				foreach ($status_tally as $state => $tally)
					$tally_contents[] = "$state: $tally";

				// Save this final row
				$rows[] = array(
					'type' => 'values',
					'columns' => array(
						array(9, implode(', ', $tally_contents))
					)
				);
			}
		}

		// Handle stats which might not exist
		if (
			is_array($this->_stats) &&
			array_key_exists('downloaded-bytes', $this->_stats) &&
			array_key_exists('uploaded-bytes', $this->_stats) &&
			array_key_exists('seconds-active', $this->_stats
		)) {
			$extra_vals = array(
				'title' => 'Transmission Stats',
				'values' => array(
					array('Total Downloaded', LinfoCommon::byteConvert($this->_stats['downloaded-bytes'])),
					array('Total Uploaded', LinfoCommon::byteConvert($this->_stats['uploaded-bytes'])),
					$this->_stats['uploaded-bytes'] > 0 && $this->_stats['downloaded-bytes'] > 0 ? array('Total Ratio', round($this->_stats['uploaded-bytes'] / $this->_stats['downloaded-bytes'], 3)) : false,
					array('Duration', LinfoCommon::secondsConvert($this->_stats['seconds-active']))
				)
			);
		}
		else
			$extra_vals = false;
		
		// Give it off
		return array(
			'root_title' => 'Transmission Torrents',
			'rows' => $rows,
			'extra_type' => 'k->v',
			'extra_vals' => $extra_vals
		);
	}
}
