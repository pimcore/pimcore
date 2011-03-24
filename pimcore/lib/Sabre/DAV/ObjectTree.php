<?php

/**
 * ObjectTree class
 *
 * This implementation of the Tree class makes use of the INode, IFile and ICollection API's 
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_ObjectTree extends Sabre_DAV_Tree {

    /**
     * The root node 
     * 
     * @var Sabre_DAV_ICollection
     */
    protected $rootNode;


    /**
     * Creates the object
     *
     * This method expects the rootObject to be passed as a parameter
     * 
     * @param Sabre_DAV_ICollection $rootNode 
     * @return void
     */
    public function __construct(Sabre_DAV_ICollection $rootNode) {

        $this->rootNode = $rootNode;

    }

    /**
     * Returns the INode object for the requested path  
     * 
     * @param string $path 
     * @return Sabre_DAV_INode 
     */
    public function getNodeForPath($path) {

        $path = trim($path,'/');

        //if (!$path || $path=='.') return $this->rootNode;
        $currentNode = $this->rootNode;
        $i=0;
        // We're splitting up the path variable into folder/subfolder components and traverse to the correct node.. 
        foreach(explode('/',$path) as $pathPart) {

            // If this part of the path is just a dot, it actually means we can skip it
            if ($pathPart=='.' || $pathPart=='') continue;

            if (!($currentNode instanceof Sabre_DAV_ICollection))
                throw new Sabre_DAV_Exception_FileNotFound('Could not find node at path: ' . $path);

            $currentNode = $currentNode->getChild($pathPart); 

        }

        return $currentNode;

    }

}

