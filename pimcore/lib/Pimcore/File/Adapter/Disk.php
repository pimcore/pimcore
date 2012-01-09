<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    File
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_File_Adapter_Disk extends Pimcore_File_Adapter {

	/**
	 * @access private
	 */
	private $chmod = 0766;

	
	/**
     * Loads the file contents from the filesystem.
     * 
     * @access public
     * @return string The contents of the file, or FALSE on failure
     */
	public function load() {
		if(!is_file($this->getPath()) or !is_readable($this->getPath())) {
    		return FALSE;
    	} else {
    		$contents = file_get_contents($this->getPath());
	    	$this->setContents($contents);
	    	return $contents;
    	}
	}


	/**
     * Writes the file's contents to the filesystem.
     * 
     * @access public
     * @return bool|int The number of bytes that were written to the file, or FALSE on failure.
     */
    public function save() {
    	$exists = $this->exists();
    	$destinationPath = $this->getPath();
    	
    	// create the parent folder if it doesn't exist
    	if (!is_dir(dirname($destinationPath))) {
            mkdir(dirname($destinationPath), $this->getChmod(), true);
        }
        
        // check if file and directory are writeable, if so save the file
        if (!is_writable(dirname($destinationPath)) || (is_file($destinationPath) && !is_writable($destinationPath))) {
        	$result = FALSE;
        } elseif ($this->isFolder()) {
        	if ($this->folderExists()) {
        		$result = TRUE;
        	} elseif ($this->fileExists()) {
        		$result = FALSE;
        	} else {
        		$result = mkdir($destinationPath, $this->getChmod(), true);
        	}
        } else {
        	if ($this->folderExists()) {
        		$result = FALSE;
        	} else {
        		$result = file_put_contents($destinationPath, $this->getContents());
        	}
        }
        
        if ($result !== FALSE) {
        	chmod($destinationPath, $this->getChmod());
        }
        
        return $result;
    }
    
    
    /**
     * Deletes the file from the filesystem.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function delete() {
		$path = $this->getPath();	
		
		if(!$this->exists()) return false;
        
        if(!$this->isFolder()) {
        	if(is_file($path) && is_writable($path)) {
	            $result = @unlink($path);
	        }
        } else {
            if(is_dir($path) && is_writable($path)) {
                $result = recursiveDelete($path, true);
            }
        }
        
        return $result;
	}
		
	
	/**
     * Tells whether a file exists at the object's path.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function fileExists() {
		return is_file($this->getPath());
	}
	
	
	/**
     * Tells whether a folder exists at the object's path.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function folderExists() {
		return is_dir($this->getPath());
	}
	
	
	/**
	 * Detects and returns the file's mime type.
	 * 
	 * @access public
	 * @return string The mime type of the file.
	 */
	public function getMimeType() {
		return MIME_Type::autoDetect($this->getPath());
	}
	
	
	/**
	 * Sets the filesystem mode for saving new files or folders.
	 * 
	 * @access public
	 * @param int $chmod
	 * @return void
	 */
	public function setChmod($chmod) {
		$this->chmod = $chmod;
	}
	
	
	/**
	 * Returns the filesystem mode for saving new files or folders.
	 * 
	 * @access public
	 * @return int
	 */
	public function getChmod() {
		if(isset($this->chmod)) {
			return $this->chmod;
		} else {
			return 0766;
		}
	}
}