<?php

/**
 * The Lock manager allows you to handle all file-locks centrally.
 *
 * This Lock Manager stores all its data in the filesystem. By default it will do this in PHP's standard temporary session directory,
 * but this can be overriden by specifiying an alternative path in the contructor
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Locks_Backend_FS extends Sabre_DAV_Locks_Backend_Abstract {

    /**
     * The default data directory 
     * 
     * @var string 
     */
    private $dataDir;

    public function __construct($dataDir) {

        $this->dataDir = $dataDir;

    }

    protected function getFileNameForUri($uri) {

        return $this->dataDir . '/sabredav_' . md5($uri) . '.locks';

    }


    /**
     * Returns a list of Sabre_DAV_Locks_LockInfo objects  
     * 
     * This method should return all the locks for a particular uri, including
     * locks that might be set on a parent uri.
     *
     * @param string $uri 
     * @return array 
     */
    public function getLocks($uri) {

        $lockList = array();
        $currentPath = '';

        foreach(explode('/',$uri) as $uriPart) {

            // weird algorithm that can probably be improved, but we're traversing the path top down 
            if ($currentPath) $currentPath.='/'; 
            $currentPath.=$uriPart;

            $uriLocks = $this->getData($currentPath);

            foreach($uriLocks as $uriLock) {

                // Unless we're on the leaf of the uri-tree we should ingore locks with depth 0
                if($uri==$currentPath || $uriLock->depth!=0) {
                    $uriLock->uri = $currentPath;
                    $lockList[] = $uriLock;
                }

            }

        }

        // Checking if we can remove any of these locks
        foreach($lockList as $k=>$lock) {
            if (time() > $lock->timeout + $lock->created) unset($lockList[$k]); 
        }
        return $lockList;

    }

    /**
     * Locks a uri 
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return bool 
     */
    public function lock($uri,Sabre_DAV_Locks_LockInfo $lockInfo) {

        // We're making the lock timeout 30 minutes
        $lockInfo->timeout = 1800;
        $lockInfo->created = time();

        $locks = $this->getLocks($uri);
        foreach($locks as $k=>$lock) {
            if ($lock->token == $lockInfo->token) unset($locks[$k]);
        }
        $locks[] = $lockInfo;
        $this->putData($uri,$locks);
        return true;

    }

    /**
     * Removes a lock from a uri 
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return bool 
     */
    public function unlock($uri,Sabre_DAV_Locks_LockInfo $lockInfo) {

        $locks = $this->getLocks($uri);
        foreach($locks as $k=>$lock) {

            if ($lock->token == $lockInfo->token) {

                unset($locks[$k]);
                $this->putData($uri,$locks);
                return true;

            }
        }
        return false;

    }

    /**
     * Returns the stored data for a uri
     *
     * @param string $uri
     * @return array 
     */
    protected function getData($uri) {

        $path = $this->getFilenameForUri($uri);
        if (!file_exists($path)) return array();

        // opening up the file, and creating a shared lock
        $handle = fopen($path,'r');
        flock($handle,LOCK_SH);
        $data = '';

        // Reading data until the eof
        while(!feof($handle)) {
            $data.=fread($handle,8192);
        }

        // We're all good
        fclose($handle);

        // Unserializing and checking if the resource file contains data for this file
        $data = unserialize($data);
        if (!$data) return array();
        return $data;

    }

    /**
     * Updates the lock information
     *
     * @param string $uri
     * @param array $newData 
     * @return void
     */
    protected function putData($uri,array $newData) {

        $path = $this->getFileNameForUri($uri);

        // opening up the file, and creating a shared lock
        $handle = fopen($path,'a+');
        flock($handle,LOCK_EX);
        ftruncate($handle,0);
        rewind($handle);

        fwrite($handle,serialize($newData));
        fclose($handle);

    }

}

