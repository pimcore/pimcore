<?php

/**
 * CalDAV server
 *
 * This script is a convenience script. It quickly sets up a WebDAV server
 * with caldav and ACL support, and it creates the root 'principals' and
 * 'calendars' collections.
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Server extends Sabre_DAV_Server {

    /**
     * Sets up the object. A PDO object must be passed to setup all the backends.
     * 
     * @param PDO $pdo 
     */
    public function __construct(PDO $pdo) {

        /* Backends */
        $authBackend = new Sabre_DAV_Auth_Backend_PDO($pdo);
        $calendarBackend = new Sabre_CalDAV_Backend_PDO($pdo);

        /* Directory structure */
        $root = new Sabre_DAV_SimpleDirectory('root');
        $principals = new Sabre_DAV_Auth_PrincipalCollection($authBackend);
        $root->addChild($principals);
        $calendars = new Sabre_CalDAV_CalendarRootNode($authBackend, $calendarBackend);
        $root->addChild($calendars);

        $objectTree = new Sabre_DAV_ObjectTree($root);
        
        /* Initializing server */
        parent::__construct($objectTree);

        /* Server Plugins */
        $authPlugin = new Sabre_DAV_Auth_Plugin($authBackend,'SabreDAV');
        $this->addPlugin($authPlugin);

        $caldavPlugin = new Sabre_CalDAV_Plugin();
        $this->addPlugin($caldavPlugin);

    }

}
