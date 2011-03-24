<?php

/**
 * Abstract tree object 
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Tree {
    
    /**
     * This function must return an INode object for a path
     * If a Path doesn't exist, thrown an Exception_FileNotFound
     * 
     * @param string $path 
     * @throws Exception_FileNotFound
     * @return Sabre_DAV_INode 
     */
    abstract function getNodeForPath($path);

    /**
     * Copies a file from path to another
     *
     * @param string $sourcePath The source location
     * @param string $destinationPath The full destination path
     * @return void 
     */
    public function copy($sourcePath, $destinationPath) {

        $sourceNode = $this->getNodeForPath($sourcePath);
       
        // grab the dirname and basename components
        list($destinationDir, $destinationName) = Sabre_DAV_URLUtil::splitPath($destinationPath);

        $destinationParent = $this->getNodeForPath($destinationDir);
        $this->copyNode($sourceNode,$destinationParent,$destinationName);

    }

    /**
     * Moves a file from one location to another 
     * 
     * @param string $sourcePath The path to the file which should be moved 
     * @param string $destinationPath The full destination path, so not just the destination parent node
     * @return int
     */
    public function move($sourcePath, $destinationPath) {

        list($sourceDir, $sourceName) = Sabre_DAV_URLUtil::splitPath($sourcePath);
        list($destinationDir, $destinationName) = Sabre_DAV_URLUtil::splitPath($destinationPath);

        if ($sourceDir===$destinationDir) {
            $renameable = $this->getNodeForPath($sourcePath);
            $renameable->setName($destinationName);
        } else {
            $this->copy($sourcePath,$destinationPath);
            $this->getNodeForPath($sourcePath)->delete();
        }

    }

    /**
     * copyNode 
     * 
     * @param Sabre_DAV_INode $source 
     * @param Sabre_DAV_ICollection $destination 
     * @return void
     */
    protected function copyNode(Sabre_DAV_INode $source,Sabre_DAV_ICollection $destinationParent,$destinationName = null) {

        if (!$destinationName) $destinationName = $source->getName();

        if ($source instanceof Sabre_DAV_IFile) {

            $data = $source->get();

            // If the body was a string, we need to convert it to a stream
            if (is_string($data)) {
                $stream = fopen('php://temp','r+');
                fwrite($stream,$data);
                rewind($stream);
                $data = $stream;
            } 
            $destinationParent->createFile($destinationName,$data);
            $destination = $destinationParent->getChild($destinationName);

        } elseif ($source instanceof Sabre_DAV_ICollection) {

            $destinationParent->createDirectory($destinationName);
            
            $destination = $destinationParent->getChild($destinationName);
            foreach($source->getChildren() as $child) {

                $this->copyNode($child,$destination);

            }

        }
        if ($source instanceof Sabre_DAV_IProperties && $destination instanceof Sabre_DAV_IProperties) {

            $props = $source->getProperties(array());
            $destination->updateProperties($props);

        }

    }

}

