<?php

/*
 * This file is part of Linfo (c) 2010-2011 Joseph Gillotti.
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
 * 
*/

// Configure absolute path to local directory
define('LOCAL_PATH', dirname(__FILE__) . '/');

// Anti hack, as in allow included files to ensure they were included
define('IN_INFO', true);

/*######### PIMCORE MODIFICATION #########*/
$workingDirectory = getcwd();
include("../../../cli/startup.php");
chdir($workingDirectory);

// only for logged in users
$user = Pimcore_Tool_Authentication::authenticateSession();
if(!$user instanceof User) {
    die("Authentication failed!");
}

@ini_set("display_errors", "Off");

// we have to load all classes since the autoloader doesn't work in pimcore environment
$files = scandir(LOCAL_PATH . "lib");
foreach ($files as $file) {
    if(is_file(LOCAL_PATH . "lib/" . $file) && strpos($file, ".php") !== false) {
        include_once(LOCAL_PATH . "lib/" . $file);
    }
}
/*######### /PIMCORE MODIFICATION #########*/

// Timer
define('TIME_START', microtime(true));

// Are we running from the CLI?
if (isset($argc) && is_array($argv))
	define('LINFO_CLI', true);

// Version
define('AppName', 'Linfo');
define('VERSION', '1.8.1');



// Configure absolute path to stored info cache, for things that take a while
// to find and don't change, like hardware devcies
define('CACHE_PATH', PIMCORE_SYSTEM_TEMP_DIRECTORY . "/");

// Configure absolute path to web directory
$web_path = dirname($_SERVER['SCRIPT_NAME']);
define('WEB_PATH', substr($web_path, -1) == '/' ? $web_path : $web_path.'/');

// If configuration file does not exist but the sample does, say so
if (!is_file(LOCAL_PATH . 'config.inc.php') && is_file(LOCAL_PATH . 'sample.config.inc.php'))
	exit('Make changes to sample.config.inc.php then rename as config.inc.php');

// If the config file is just gone, also say so
elseif(!is_file(LOCAL_PATH . 'config.inc.php'))
	exit('Config file not found.');

// It exists; just include it
require_once LOCAL_PATH . 'config.inc.php';

// This is essentially the only extension we need, so make sure we have it
if (!extension_loaded('pcre') && !function_exists('preg_match') && !function_exists('preg_match_all')) {
	echo AppName.' needs the `pcre\' extension to be loaded. http://us2.php.net/manual/en/book.pcre.php';
	exit(1);
}

// Make sure these are arrays
$settings['hide']['filesystems'] = is_array($settings['hide']['filesystems']) ? $settings['hide']['filesystems'] : array();
$settings['hide']['storage_devices'] = is_array($settings['hide']['storage_devices']) ? $settings['hide']['storage_devices'] : array();

// Make sure these are always hidden
$settings['hide']['filesystems'][] = 'rootfs';
$settings['hide']['filesystems'][] = 'binfmt_misc';

// Load libs
require_once LOCAL_PATH . 'lib/functions.init.php';
require_once LOCAL_PATH . 'lib/functions.misc.php';
require_once LOCAL_PATH . 'lib/functions.display.php';
require_once LOCAL_PATH . 'lib/class.LinfoTimer.php';
require_once LOCAL_PATH . 'lib/interface.LinfoExtension.php';

// Default to english translation if garbage is passed
if (empty($settings['language']) || !preg_match('/^[a-z]{2}$/', $settings['language']))
	$settings['language'] = 'en';

// If it can't be found default to english
if (!is_file(LOCAL_PATH . 'lang/'.$settings['language'].'.php'))
	$settings['language'] = 'en';
	
// Load translation
require_once LOCAL_PATH . 'lang/'.$settings['language'].'.php';

// Determine our OS
$os = determineOS();

// Cannot?
if ($os == false)
	exit("Unknown/unsupported operating system\n");

// Get info
$getter = parseSystem($os, $settings);
$info = $getter->getAll();

// Store current timestamp for alternative output formats
$info['timestamp'] = date("c");

// Extensions
runExtensions($info, $settings);

// Make sure we have an array of what not to show
$info['contains'] = array_key_exists('contains', $info) ? (array) $info['contains'] : array();


// From the command prompt? Ncurses motha fucka!
if (defined('LINFO_CLI')) {
	$out = new out_ncurses();
	$out->work($info, $settings, $getter);
}

// Coming from a web server
else {
	// Decide what web format to output in
	switch (array_key_exists('out', $_GET) ? $_GET['out'] : 'html') {

		// Just regular html 
		case 'html':
		default:
			showInfoHTML($info, $settings);
		break;

		// JSON
		case 'json':
			showInfoJSON($info, $settings);
		break;

		// Serialized php array
		case 'php_array':
			echo serialize($info);
		break;

		// XML
		case 'xml':

			// Try using SimpleXML
			if (extension_loaded('SimpleXML')) 
				showInfoSimpleXML($info, $settings);
			

			// If not that, then try XMLWriter
			elseif (extension_loaded('XMLWriter')) 
				showInfoXMLWriter($info, $settings);	

			// Can't generate XML anywhere :-/
			else 
				exit('Cannot generate XML. Install either php\'s SimpleXML or XMLWriter extension');
		break;
	}
}
// "This is where it ends, Commander"






/*
	Roses are red; violets are blue; vaginas are nice and
	sometimes you are too! 

	Once upton a time, there was a sex machine named Billie. Her
	counterpart was named Linus. Her cunt was lined with sweet
	oil to ease his passing, Linus so loved her cunt. At one
	point, their liquid metal condom broke and they conceived a
	child. They named it "pinpho" after the broken condom.

	Out it came, not much more than a sandisk flash drive. They
	inserted it where the sun shouldn't shine; it had a lone
	ext2 partition consisting of a single file:
	linfo_0.1.tar.gz.

	Thus Linfo was born.

						--
						The book of Metal
						cocks, 93:69
*/
