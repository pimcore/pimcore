<?php

/**
 * This is the base class for any authentication object.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Auth_Backend_Abstract {

    /**
     * Authenticates the user based on the current request.
     *
     * If authentication is succesful, true must be returned.
     * If authentication fails, an exception must be thrown.
     *
     * @return bool 
     */
    abstract public function authenticate(Sabre_DAV_Server $server,$realm); 

    /**
     * Returns information about the currently logged in user.
     *
     * If nobody is currently logged in, this method should return null.
     * 
     * @return array|null
     */
    abstract public function getCurrentUser();

    /**
     * Returns the full list of users.
     *
     * This method must at least return a uri for each user.
     *
     * It is optional to implement this.
     * 
     * @return array 
     */
    public function getUsers() {

        return array();

    }

}

