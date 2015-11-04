<?php

/*
 * This file is part of Linfo (c) 2010-2015 Joseph Gillotti.
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


/*######### PIMCORE MODIFICATION #########*/
$workingDirectory = getcwd();
include("../../../cli/startup.php");
chdir($workingDirectory);

// only for logged in users
$user = \Pimcore\Tool\Authentication::authenticateSession();
if(!$user instanceof User) {
    die("Authentication failed!");
}

if(!$user->isAdmin()) {
    die("Permission denied");
}

@ini_set("display_errors", "Off");

/*######### /PIMCORE MODIFICATION #########*/


// Load libs
require_once dirname(__FILE__).'/init.php';

// Begin
try {

  // Load settings and language
	$linfo = new Linfo;

  // Run through /proc or wherever and build our list of settings
	$linfo->scan();

  // Give it off in html/json/whatever
	$linfo->output();
}

// No more inline exit's in any of Linfo's core code!
catch (LinfoFatalException $e) {
	echo $e->getMessage()."\n";
	exit(1);
}

// Developers:
// if you include init.php as above and instantiate a $linfo
// object, you can get an associative array of all of the 
// system info with $linfo->getInfo() after running $linfo->scan();
// Just catch the LinfoFatalException for fatal errors
