<?php

/**
 * Principals Collection
 *
 * This collection represents a list of users. It uses
 * Sabre_DAV_Auth_Backend to determine which users are available on the list.
 *
 * The users are instances of Sabre_DAV_Auth_Principal
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Auth_PrincipalCollection extends Sabre_DAV_Directory {

    /**
     * The name of this object. It is not adviced to change this.
     * The plugins that depend on the principals collection to exist need to 
     * be have a common name to find it.
     */
    const NODENAME = 'principals';

    /**
     * Authentication backend 
     * 
     * @var Sabre_DAV_Auth_Backend 
     */
    protected $authBackend;

    /**
     * Creates the object 
     * 
     * @param Sabre_DAV_Auth_Backend_Abstract $authBackend 
     */
    public function __construct(Sabre_DAV_Auth_Backend_Abstract $authBackend) {

        $this->authBackend = $authBackend;

    }

    /**
     * Returns the name of this collection. 
     * 
     * @return string 
     */
    public function getName() {

        return self::NODENAME; 

    }

    /**
     * Retursn the list of users 
     * 
     * @return void
     */
    public function getChildren() {

        $children = array();
        foreach($this->authBackend->getUsers() as $principalInfo) {

            $principalUri = $principalInfo['uri'] . '/';
            $children[] = new Sabre_DAV_Auth_Principal($principalUri,$principalInfo);


        }
        return $children; 

    }

}
