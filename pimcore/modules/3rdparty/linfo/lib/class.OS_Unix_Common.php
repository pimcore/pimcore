<?php

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

/*
 * The Unix os's are largely similar and thus draw from this class.
*/
abstract class OS_Unix_Common extends OS {

	public function ensureFQDN($hostname) {

		$parts = explode('.', $hostname);
		$num_parts = count($parts);

		// Already FQDN, like a boss..
		if ($num_parts >= 2)
			return $hostname;

		// Don't bother trying to expand on .local
		if ($num_parts > 0 && $parts[$num_parts - 1] == '.local')
			return $hostname;

		// This relies on reading /etc/hosts. 
		if (!($contents = LinfoCommon::getContents('/etc/hosts', false)))
			return $hostname;

		preg_match_all('/^[^\s#]+\s+(.+)/m', $contents, $matches, PREG_SET_ORDER);

		// Lets see if we can do some magic with /etc/hosts..
		foreach ($matches as $match) {

			if (!preg_match_all('/(\S+)/', $match[1], $hosts, PREG_SET_ORDER))
				continue;

			foreach ($hosts as $host) {

				// I don't want to expand on localhost as it's pointlesss
				if (strpos('localhost', $host[1]) !== false) 
					continue;

				$entry_parts = explode('.', $host[1]);
				if (count($entry_parts) > 1 && $entry_parts[0] == $hostname) 
					return $host[1];
			}
		}

		// Couldn't make it better :/
		return $hostname;
	}
}
