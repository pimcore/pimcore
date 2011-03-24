<?php

/**
 * This class represents the {DAV:}resourcetype property
 *
 * Normally for files this is empty, and for collection {DAV:}collection.
 * However, other specs define different values for this. 
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Property_ResourceType extends Sabre_DAV_Property {

    /**
     * resourceType 
     * 
     * @var string 
     */
    public $resourceType = null;

    /**
     * __construct 
     * 
     * @param mixed $resourceType 
     * @return void
     */
    public function __construct($resourceType = null) {

        if ($resourceType === Sabre_DAV_Server::NODE_FILE)
            $this->resourceType = null;
        elseif ($resourceType === Sabre_DAV_Server::NODE_DIRECTORY)
            $this->resourceType = '{DAV:}collection';
        else 
            $this->resourceType = $resourceType;

    }

    /**
     * serialize 
     * 
     * @param DOMElement $prop 
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $prop) {

        $propName = null;
        $rt = $this->resourceType;
        if (!is_array($rt)) $rt = array($rt);
        
        foreach($rt as $resourceType) {
            if (preg_match('/^{([^}]*)}(.*)$/',$resourceType,$propName)) { 
       
                if (isset($server->xmlNamespaces[$propName[1]])) {
                    $prop->appendChild($prop->ownerDocument->createElement($server->xmlNamespaces[$propName[1]] . ':' . $propName[2]));
                } else {
                    $prop->appendChild($prop->ownerDocument->createElementNS($propName[1],'custom:' . $propName[2]));
                }
            
            }
        }

    }

    /**
     * Returns the value in clark-notation
     *
     * For example '{DAV:}collection'
     * 
     * @return string 
     */
    public function getValue() {

        return $this->resourceType;

    }

}
