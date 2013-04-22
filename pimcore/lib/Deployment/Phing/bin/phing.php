<?php

/**
 * This is the Phing command line launcher. It starts up the system evironment
 * tests for all important paths and properties and kicks of the main command-
 * line entry point of phing located in phing.Phing
 * @version $Id: a8860602d62d2fc7ebebcc4ad89e73b1acedcd94 $
 */

// Use composers autoload.php if available
if (file_exists(dirname(__FILE__) . '/../vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/../vendor/autoload.php';
} else if (file_exists(dirname(__FILE__) . '/../../../autoload.php')) {
    require_once dirname(__FILE__) . '/../../../autoload.php';
}

// Set any INI options for PHP
// ---------------------------

/* set include paths */
set_include_path(
    	    dirname(__FILE__) . '/../classes' .
    	    PATH_SEPARATOR .
    	    get_include_path() 
    	);


require_once 'phing/Phing.php';

try {
    
    /* Setup Phing environment */
    Phing::startup();

    // Set phing.home property to the value from environment
    // (this may be NULL, but that's not a big problem.) 
    Phing::setProperty('phing.home', getenv('PHING_HOME'));
    // Grab and clean up the CLI arguments
    $args = isset($argv) ? $argv : $_SERVER['argv']; // $_SERVER['argv'] seems to not work (sometimes?) when argv is registered
    array_shift($args); // 1st arg is script name, so drop it

    // Invoke the commandline entry point
    Phing::fire($args);
    
    // Invoke any shutdown routines.
    Phing::shutdown();
    
} catch (ConfigurationException $x) {
    
    Phing::printMessage($x);
    exit(-1); // This was convention previously for configuration errors.
    
} catch (Exception $x) {
    
    // Assume the message was already printed as part of the build and
    // exit with non-0 error code.
    
    exit(1);
    
}

?>
