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

abstract class Pimcore_File_Adapter {

    /**
     * @var string
     */
    private $filepath;
    
    /**
     * @var string
     */
    private $contents;
    
    /**
     * @var bool
     */
    private $isFolder;
    
    
    /**
     * Creates a new instance with the provided filepath.
     * 
     * @access public
     * @param mixed $path The full system filepath for the file. (default: null)
     * @param mixed $isFolder Whether this file represents a folder. (default: false)
     */
    public function __construct($path = null, $isFolder = false) {    
    	if(isset($path)) {
    		$this->setPath($path);
    	}
    }
    
    
    /**
     * Returns the filepath.
     * 
     * @access public
     * @return string The full system filepath
     */
    public function getPath() {
    	return $this->filepath;
    }


    /**
     * Sets the filepath.
     * 
     * @access public
     * @param string $path The full system filepath.
     * @return void
     */
    public function setPath($path) {
    	$this->filepath = $path;
    }
    
    
    /**
     * Returns this file's contents previously set by setContents or loaded by
     * loadContents. This function does not load the file's contents directly.
     * 
     * @access public
     * @return void
     */
    public function getContents() {
    	return $this->contents;
    }


    /**
     * Sets the file contents prior to saving the file. If the $write parameter
     * is true, the contents will be written to file.
     * 
     * @access public
     * @param string $contents The contents of the file.
     * @param string $write If TRUE, the new contents will also be written to file.
     * @return bool If $write is TRUE, returns the result of the save operation. Returns TRUE otherwise.
     */
    public function setContents($contents, $write = FALSE) {
    	$this->contents = $contents;
    	
    	if($write) {
    		return $this->save();
    	} else {
    		return true;
    	}
    }
    
    
    /**
	 * Tells whether this Pimcore_File object represents a folder. If a boolean is passed,
	 * it sets whether this object represents a folder.
	 * 
	 * @access public
	 * @param bool $isFolder If passed, sets whether this file is a directory. (default: NULL)
	 * @return bool TRUE if the current file is a directory, FALSE otherwise
	 */
	public function isFolder($value = NULL) {
    	if(is_bool($value)) {
    		$this->isFolder = $value;
    	}
    
    	return $this->isFolder;
    }
    
    
    /**
     * Tells whether a file or folder exists at the object's path.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function exists() {
		return ($this->fileExists() || $this->folderExists());
	}
    
    
    /**
     * Loads the file contents from the filesystem.
     * 
     * @access public
     * @return string The contents of the file, or FALSE on failure
     */
    abstract public function load();
    
    
    /**
     * Writes the current contents to the file, overwriting existing files.
     * 
     * @access public
     * @return bool|int The number of bytes that were written to the file, or FALSE on failure.
     */
    abstract public function save();


	/**
     * Deletes the file.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	abstract public function delete();
	
	
	/**
     * Tells whether a file exists at the object's path.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	abstract public function fileExists();
	
	
	/**
     * Tells whether a folder exists at the object's path.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	abstract public function folderExists();
	
	
	/**
     * Detects and returns the file's mime type.
     * 
     * @access public
     * @return string The mime type of the file.
     */
	abstract public function getMimeType();
	
}
