<?php

/**
 * Users collection 
 *
 * This object is responsible for generating a collection of users.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_CalendarRootNode extends Sabre_DAV_Directory {

    /**
     * Authentication Backend 
     * 
     * @var Sabre_DAV_Auth_Backend_Abstract 
     */
    protected $authBackend;

    /**
     * CalDAV backend 
     * 
     * @var Sabre_CalDAV_Backend_Abstract 
     */
    protected $caldavBackend;

    /**
     * Constructor 
     *
     * This constructor needs both an authentication and a caldav backend.
     *
     * @param Sabre_DAV_Auth_Backend_Abstract $authBackend 
     * @param Sabre_CalDAV_Backend_Abstract $caldavBackend 
     */
    public function __construct(Sabre_DAV_Auth_Backend_Abstract $authBackend,Sabre_CalDAV_Backend_Abstract $caldavBackend) {

        $this->authBackend = $authBackend;
        $this->caldavBackend = $caldavBackend;

    }

    /**
     * Returns the name of the node 
     * 
     * @return string 
     */
    public function getName() {

        return Sabre_CalDAV_Plugin::CALENDAR_ROOT;

    }

    /**
     * Returns the list of users as Sabre_CalDAV_User objects. 
     * 
     * @return array 
     */
    public function getChildren() {

        $users = $this->authBackend->getUsers();
        $children = array();
        foreach($users as $user) {

            $children[] = new Sabre_CalDAV_UserCalendars($this->authBackend, $this->caldavBackend, $user['uri']);

        }
        return $children;

    }

}
