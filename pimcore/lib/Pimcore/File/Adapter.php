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
     * Creates a new instance with the provided filepath.
     * 
     * @access public
     */
    public function __construct() {}
    
    
    /**
     * Tells whether a file or folder exists at the object's path.
     * 
     * @access public
     * @param Pimcore_File
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function exists($file) {
		return ($file->isFile() || $file->isDir());
	}
	
	
	/**
     * Tells whether a file exists and is readable.
     * 
     * @access public
     * @param Pimcore_File
     * @return bool Returns TRUE if the filename exists and is readable.
     */
    abstract public function canRead($file);
    
    
    /**
     * Tells whether a file exists and is writeable.
     * 
     * @access public
     * @param Pimcore_File
     * @return bool Returns TRUE if the filename exists and is writable.
     */
    abstract public function canWrite($file);
    
    
    /**
     * Copies a file.
     * 
     * @access public
     * @param Pimcore_File The source file.
     * @param string The destination of the copied file.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	abstract public function copy($file, $destination);
    
    
    /**
     * Deletes the file.
     * 
     * @access public
     * @param Pimcore_File
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	abstract public function delete($file);
	
	
	/**
     * Tells whether a folder exists at the object's path.
     * 
     * @access public
     * @param Pimcore_File
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	abstract public function isDir($file);
	
	
	/**
     * Tells whether a file exists at the object's path.
     * 
     * @access public
     * @param Pimcore_File
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	abstract public function isFile($file);
	
	
	/**
     * Detects and returns the file's mime type.
     * 
     * @access public
     * @param Pimcore_File
     * @return string The mime type of the file.
     */
	abstract public function getMimeType($file);
    
    
    /**
     * Loads the file or folder contents.
     * 
     * @access public
     * @param Pimcore_File
     * @return string|array|bool The contents of the file or directory or FALSE on failure
     */
    abstract public function load($file);
    
    
    /**
     * Creates a directory at $file's path.
     * 
     * @access public
     * @param Pimcore_File
     * @return bool TRUE on success or FALSE on failure.
     */
    abstract public function mkdir($file);
    
    
    /**
     * Moves a file to a new location.
     * 
     * @access public
     * @param Pimcore_File The source file.
     * @param string The destination of the moved file.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	abstract public function move($file, $destination);
    
    
    /**
     * Writes the contents to the file, overwriting existing files.
     * 
     * @access public
     * @param Pimcore_File
     * @param string|array The data to write. Can be either a string, an array, or a stream resource.
     * @return bool|int The number of bytes that were written to the file or FALSE on failure.
     */
    abstract public function save($file, $data = '');

	
}
