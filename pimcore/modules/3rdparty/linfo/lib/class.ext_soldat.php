<?php

/*

This impliments a soldat (soldat.pl) dedicated server gamestat.txt parser

Installation: 
 - Copy/move the class.ext.soldat.php into the lib/ folder
 - The following lines must be added to your config.inc.php:
   $settings['extensions']['soldat'] = true;  

   // paths to the gamestat.txt files
   $settings['soldat_servers'] = array(
	//'CTF #1' => '/home/soldat/ctf/logs/gamestat.txt' # example usage
   );

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
 * Get status on soldat game servers
 */
class ext_soldat implements LinfoExtension {

	// Store these tucked away here
	private
		$_LinfoError,
		$_res,
		$_servers;

	// Localize important classes
	public function __construct(Linfo $linfo) {
		$settings = $linfo->getSettings();
		$this->_LinfoError = LinfoError::Singleton();
		$this->_servers = (array) $settings['soldat_servers'];
	}

	// work it
	private function _call() {
		$this->_res = array();
		foreach ($this->_servers as $name => $path) {
			$lines = LinfoCommon::getLines($path);
			if (count($lines) == 0)
				continue;
			$info = self::readgamestat($lines);
			$this->_res[] = array('name' => $name, 'info' => $info);
		}
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

		// Store rows here
		$rows = array();
		
		// Table header
		$rows[] = array (
			'type' => 'header',
			'columns' => array(
				'Name',
				'Map',
				'Time Left',
				'Gametype',
				'Players',
			)
		);

		// Have we?
		if (count($this->_res) == 0)
			$rows[] = array('type' => 'none', 'columns' => array(array(5, 'None found')));

		// We do
		else {
			
			// Go through each server
			foreach ($this->_res as $server) {

				// No players? Set player column to 'none'
				if ($server['info']['num_players'] == 0)
					$players = 'None';

				// We do; populate a mini players table
				else {

					// Show team column?
					$show_team = in_array($server['info']['mode'], array(
								'Infiltration',
								'Capture the Flag',
								'Teammatch'
					));
					
					// Start table
					$players = '
					<table class="mini" style="text-align: center;">
						<tr>
							<th>Name</th>'.(
							$show_team ? '
							<th>Team</th>' : '').'
							<th>Score</th>
							<th>Deaths</th>
							<th>Ping</th>
						</tr>';
					
					// Add each player to it
					foreach ($server['info']['players'] as $player)
						$players .= '
						<tr'.( $show_team ?  (' style="color: '.(
						array_key_exists($player['team'], self::$team2color) ?
							self::$team2color[$player['team']] : 'purple'
						).';"') : '').'>
							<td>'.htmlspecialchars($player['name']).'</td>'.(
							$show_team ? '
							<td>'.(array_key_exists($player['team'], self::$team2name) ?
								self::$team2name[$player['team']] : 'None').'</td>' : '').'
							<td>'.$player['kills'].'</td>
							<td>'.$player['deaths'].'</td>
							<td>'.$player['ping'].'</td>
						</tr>';

					// End table
					$players .= '
					</table>
					';
				}

				// Save result in master table
				$rows[] = array(
					'type' => 'values',
					'columns' => array(
						$server['name'],
						$server['info']['map'],
						$server['info']['timeleft'],
						$server['info']['mode'],
						$players
					)
				);
			}
		}

		// Give info
		return array(
			'root_title' => 'Soldat Servers',
			'rows' => $rows
		);
	}

	// Deal with team color 
	static $team2color = array(
		0 => '#333',
		1 => 'red',
		2 => 'blue',
		3 => '#006600',
		4 => '#FFD700'
	);
	
	// Deal with team name
	static $team2name = array(
		0 => 'None',
		1 => 'Alpha',
		2 => 'Bravo',
		3 => 'Charlie',
		4 => 'Delta'
	);

	/*
	    gamestat.txt parser
	    Copyright (C) 2007 JRG Productions
	    http://soldat.jrgp.org/programs/gstp/gstp.phps
	*/
	static function readgamestat($i)
	{
		//this array contains the info that will be returned
		$info = array(
				'num_players' => trim(str_replace('Players: ','',$i[1])),
				'map' => trim(str_replace('Map: ','',$i[2])),
				'mode'=> trim(str_replace('Gamemode: ','',$i[3])),
				'timeleft' => trim(str_replace('Timeleft: ','',$i[4]))
			     );
		//support for the teambased gamemodes
		if ($info['mode'] == 'Capture the Flag' || $info['mode'] == 'Infiltration' || $info['mode'] == 'Teammatch')
		{

			$info['teams']['alpha'] = trim(str_replace('Team 1: ','',$i[5]));
			$info['teams']['bravo'] = trim(str_replace('Team 2: ','',$i[6]));
			$info['teams']['charlie'] = trim(str_replace('Team 3: ','',$i[7]));
			$info['teams']['delta'] = trim(str_replace('Team 4: ','',$i[8]));
			$players_line = 10;
		}
		else
			$players_line = 6;
		//get the player info a string
		for ($l = $players_line; $line = $i[$l] ,$l < count($i); $l++)
			$pla .= $line;
		//explode then chunk that string
		$players_info = array_chunk(explode("\n",$pla), 5);
		//kill the last element since its empty    
		array_pop($players_info);
		//add each player to the new array
		foreach($players_info as $p)
		{
			$info['players'][] = array(
					'name' => $p[0],
					'kills' => $p[1],
					'deaths' => $p[2],
					'team' => $p[3],
					'ping' => $p[4],
					);
		}
		//return the info
		return $info;
	}
}
