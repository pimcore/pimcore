<?php

/*

This connects to the web ui provided by utorrent headless for Linux. It works
by forging the HTTP requests, essentially pretending it's a web browser.

Requires libcurl extension. (apt-get install php5-curl)

To enable this extension, add/tweak the following to your config.inc.php

$settings['extensions']['utorrent'] = true;
$settings['utorrent_connection'] = array(
				'host' => 'localhost',
				'port' => 8080,
				'user' => 'admin',
				'pass' => ''
);

Optionally, you can add multiple regexes to filter torrents. Use something
like the following to strip out torrents with XXX in their name:

$settings['utorrent_filter'] = array(
				'/XXX/i'
);

Set the following to not show torrent names and just show the hashes

$settings['utorrent_hide_name'] = true;

*/

/*

Known to work with this verson of uTorrent:
 Product Version 3.3
 Source Revision 30235
 Build Date 2013-10-14 10:42:53 -0700
 UI Revision 30235

*/

/**
 * This file is part of Linfo (c) 2014 Joseph Gillotti.
 * 
 * Linfo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Linfo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Linfo.	If not, see <http://www.gnu.org/licenses/>.
 * 
*/

/**
 * Keep out hackers...
 */
defined('IN_LINFO') or exit;

/**
 * Get status on torrents running under uTorrent
 */
class ext_utorrent implements LinfoExtension {
	
	private
		$LinfoError,
		$torrents = array(),
		$connectionSettings = array(),
		$stats = array('uploaded' => 0, 'downloaded' => 0),
		$cookiefile = false,
		$res = false;

	// Keys corresponding to json array returned by utorrent.
	// Ripped from utorrent/web/js/webui/constants.js. If this extension stops working
	// they probably changed the keys in that file. Kindly fix this dictionary using that file plox and submit a patch ;)
	protected static $torrent_keys = array(
		'TORRENT_HASH' => 0,
		'TORRENT_STATUS' => 1,
		'TORRENT_NAME' => 2,
		'TORRENT_SIZE' => 3, // bytes
		'TORRENT_PROGRESS' => 4,
		'TORRENT_DOWNLOADED' => 5, // bytes out of size
		'TORRENT_UPLOADED' => 6,
		'TORRENT_RATIO' => 7,
		'TORRENT_UPSPEED' => 8,
		'TORRENT_DOWNSPEED' => 9,
		'TORRENT_ETA' => 10,
		'TORRENT_LABEL' => 11,
		'TORRENT_PEERS_CONNECTED' => 12,
		'TORRENT_PEERS_SWARM' => 13,
		'TORRENT_SEEDS_CONNECTED' => 14,
		'TORRENT_SEEDS_SWARM' => 15,
		'TORRENT_AVAILABILITY' => 16,
		'TORRENT_QUEUE_POSITION' => 17,
		'TORRENT_REMAINING' => 18,
		'TORRENT_DOWNLOAD_URL' => 19,
		'TORRENT_RSS_FEED_URL' => 20,
		'TORRENT_STATUS_MESSAGE' => 21,
		'TORRENT_STREAM_ID' => 22,
		'TORRENT_DATE_ADDED' => 23,
		'TORRENT_DATE_COMPLETED' => 24,
		'TORRENT_APP_UPDATE_URL' => 25,
		'TORRENT_SAVE_PATH' => 26,
	);


	// First we log in to token.html using our admin/password. This gives us a token hash and
	// cookie used for subsequent requests. Then we use these details to access the json list of torrents
	const
		TOKEN_URL = 'http://%s:%s/gui/token.html',
		LIST_URL = 'http://%s:%s/gui/?token=%s&list=%s';

	public function __construct(Linfo $linfo) {
		$settings = $linfo->getSettings();
		$this->LinfoError = LinfoError::Singleton();
		$this->connectionSettings = $settings['utorrent_connection'];
		$this->regexFilters = isset($settings['utorrent_filter']) && is_array($settings['utorrent_filter']) ? $settings['utorrent_filter'] : array();
		$this->hideName = isset($settings['utorrent_hide_name']) ? !empty($settings['utorrent_hide_name'])  : false;
	}

	public function work() {

		$t = new LinfoTimerStart('utorrent extension');

		$this->res = false;

		if (!extension_loaded('curl')) {
			$this->LinfoError->add('utorrent extension', 'Curl PHP extension not installed');
			return false;
		}

		if (!isset($this->connectionSettings['host']) || !isset($this->connectionSettings['port']) || !isset($this->connectionSettings['user'])) {
			$this->LinfoError->add('utorrent extension', 'Missing $setting[\'utorrent_connection\'] details in config..');
			return false;
		}

		$token_url = sprintf(self::TOKEN_URL, $this->connectionSettings['host'], $this->connectionSettings['port']);

		// Start up our curl session to be used for both requests. It is going to store the cookies utorrent 
		// uses
		$curl = curl_init();

		// For curl to actually process cokies we need to give it a filename. This should be filed as a 
		// bug to curl, especially since something like /dev/null works
		$this->cookiefile = tempnam('/tmp', 'linfo_utorrent');

		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERPWD => $this->connectionSettings['user'].':',
			CURLOPT_COOKIEJAR => $this->cookiefile ?: '/dev/null' // If tempnam fails this will fail on Windows
		));

		// Get token
		curl_setopt($curl, CURLOPT_URL, $token_url);
		$result = curl_exec($curl);

		if (preg_match('/\>([^<]+)\</', $result, $m)) {
			$token = $m[1];
		}
		else {
			$this->LinfoError->add('utorrent extension', 'Failed parsing token');
			$this->cleanup();
			return false;
		}

		// Get list of torrents? Do our best to forge this (ajax) request 
		curl_setopt_array($curl, array(
			CURLOPT_HTTPHEADER => array(
			 'X-Requested-With: XMLHttpRequest',
			 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:28.0) Gecko/20100101 Firefox/28.0',
			 'Host: '.$this->connectionSettings['host'].($this->connectionSettings['port'] != 80 ? ':'.$this->connectionSettings['port'] : ''),
			 'Referer: http://'.$this->connectionSettings['host'].($this->connectionSettings['port'] != 80 ? ':'.$this->connectionSettings['port'] : '').'/gui/web/index.html'
			)
		));

		$list_url = sprintf(self::LIST_URL, $this->connectionSettings['host'], $this->connectionSettings['port'], $token, '1');
		curl_setopt($curl, CURLOPT_URL, $list_url);

		$result = curl_exec($curl);

		if (!($response = @json_decode($result, true))) {
			$this->LinfoError->add('utorrent extension', 'Failed parsing json object');
			$this->cleanup();
			return false;
		}

		// Not going to be needing curl again
		curl_close($curl);

		if (!isset($response['torrents']) || !is_array($response['torrents'])) {
			$this->LinfoError->add('utorrent extension', 'torrents array key not found in json response object');
			$this->cleanup();
			return false;
		}

		$torrent_names = array();
		$torrent_states = array();

		foreach ($response['torrents'] as $torrent_src) {
			$torrent = array();
			foreach (self::$torrent_keys as $key => $index) {
				$torrent[$key] = $torrent_src[$index];
			}

			foreach ($this->regexFilters as $regex) {
				if (preg_match($regex, $torrent['TORRENT_NAME']))
					continue 2;
			}

			$this->torrents[] = $torrent;
			$torrent_names[] = $torrent['TORRENT_NAME'];
			$torrent_states[] = $torrent['TORRENT_STATUS_MESSAGE'];

			$this->stats['downloaded'] += $torrent['TORRENT_DOWNLOADED'];
			$this->stats['uploaded'] += $torrent['TORRENT_UPLOADED'];
		}

		// Sort by state and then name ascending (show downloading/etc first)
		array_multisort($torrent_states, SORT_ASC,
			$torrent_names, SORT_ASC, $this->torrents);

		$this->res = true;
		$this->cleanup();
	}

	public function result() {
		if (!$this->res)
			return false;

		$rows[] = array(
			'type' => 'header',
			'columns' =>
				array(
				'Torrent/hash'.($this->hideName ? ' (names hidden)' : ''),
				'Size',
				'Progress',
				'Status',
				'Seeds',
				'Peers',
				'Downloaded',
				'Uploaded',
				'Ratio',
				'Speeds'
			)
		);

		foreach ($this->torrents as $name => $info) {
			$rows[] = array(
				'type' => 'values',
				'columns' => array(
					($this->hideName ? '' : $info['TORRENT_NAME'].'<br />')
						.'<span style="font-size: 80%; font-family: monaco, monospace, courier;">'.$info['TORRENT_HASH'].'</span>',
					LinfoCommon::byteConvert($info['TORRENT_SIZE']),
					LinfoOutput::generateBarChart($info['TORRENT_PROGRESS'] / 10),
					$info['TORRENT_STATUS_MESSAGE'],
					$info['TORRENT_SEEDS_CONNECTED'].'/'.$info['TORRENT_SEEDS_SWARM'],
					$info['TORRENT_SEEDS_CONNECTED'].'/'.$info['TORRENT_PEERS_SWARM'],
					LinfoCommon::byteConvert($info['TORRENT_DOWNLOADED']),
					LinfoCommon::byteConvert($info['TORRENT_UPLOADED']),
					$info['TORRENT_RATIO'] > 0 ? (round($info['TORRENT_RATIO'] / 1000, 2) ?: '0.0') : '0.0',
					LinfoCommon::byteConvert($info['TORRENT_DOWNSPEED']).'/s &darr; '.
					LinfoCommon::byteConvert($info['TORRENT_UPSPEED']).'/s &uarr; '
				)
			);
		}

		// Give it off
		return array(
			'root_title' => '&micro;Torrent <span style="font-size: 80%;">('.LinfoCommon::byteConvert($this->stats['downloaded']).' &darr; '
				.LinfoCommon::byteConvert($this->stats['uploaded']).' &uarr; '.round($this->stats['uploaded'] / $this->stats['downloaded'], 2).' ratio)</span>',
			'rows' => $rows
		);
	}

	private function cleanup() {
		// If we succeeded creating that temp file kill it off
		if ($this->cookiefile && is_file($this->cookiefile))
			@unlink($this->cookiefile);
	}
}
