<?php

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
 * Started/created at the beginning of a function declaration.
 * Is only useful since __destruct() is called when that function ends,
 * which in turn calls LinfoTimer and saves result
 */
class LinfoTimerStart {

	// Store stuff here
	protected
		$_id,
		$_start;
	
	/**
	 * Initiate timer and set the name
	 * 
	 * @param string $id name of caller
	 * @access public
	 */
	public function __construct($id) {
		$this->_id = $id;
		$this->_start = microtime(true);
	}
	
	/**
	 * Runs when it ends. As in, each bit of linfo's info fetching is done
	 * in its own function. And when that function ends, any inner created
	 * classes dies, thus calling the following destructor
	 */
	public function __destruct() {
		LinfoTimer::Singleton()->save($this->_id, microtime(true) - $this->_start);
	}
}

