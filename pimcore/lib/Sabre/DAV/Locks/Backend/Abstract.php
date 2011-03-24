<?php

/**
 * The Lock manager allows you to handle all file-locks centrally.
 *
 * This is an alternative approach to doing this on a per-node basis
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Locks_Backend_Abstract {

    /**
     * Returns a list of Sabre_DAV_Locks_LockInfo objects  
     * 
     * This method should return all the locks for a particular uri, including
     * locks that might be set on a parent uri.
     *
     * @param string $uri 
     * @return array 
     */
    abstract function getLocks($uri);

    /**
     * Locks a uri 
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return bool 
     */
    abstract function lock($uri,Sabre_DAV_Locks_LockInfo $lockInfo);

    /**
     * Removes a lock from a uri 
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return bool 
     */
    abstract function unlock($uri,Sabre_DAV_Locks_LockInfo $lockInfo);

}

