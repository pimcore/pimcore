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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Linfo.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Keep out hackers...
 */
defined('IN_INFO') or exit;

/**
 * Use this class for all error handling
 */
class LinfoError {
	
	/**
	 * Store singleton instance here 
	 * 
	 * @var object
	 * @static
	 * @access protected
	 */
	protected static $_fledging;

	/**
	 * Fledging. Get singleton instance
	 * 
	 * @param array $settings linfo settings
	 * @access public
	 * @return object LinfoError instance
	 */
	static public function Fledging($settings = null) {
		$c = __CLASS__;
		if (!isset(self::$_fledging))
			self::$_fledging = new $c($settings);
		return self::$_fledging;
	}
	
	/**
	 * Store error messages here
	 *
	 * @var array
	 * @access private
	 */
	private $_errors = array();

	/**
	 * Add an error message
	 *
	 * @access public
	 * @param string $whence name of error message source
	 * @param string $message error message text
	 */
	public function add($whence, $message) {
		$this->_errors[] = array($whence, $message);
	}

	/**
	 * Get all error messages
	 *
	 * @access public
	 * @return array of errors
	 */
	public function show() {
		return $this->_errors;
	}

	/**
	 * How many are there?
	 *
	 * @access public
	 * @return int number of errors
	 */
	public function num() {
		return count($this->_errors);
	}
}
