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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_File {

    /**
     * @var string
     */
    private $filepath;
    
    /**
     * @var string
     */
    private $filename;
    
    /**
     * @var string
     */
    private $contents;
    
    /**
     * @var bool
     */
    private $isFolder;
    
    /**
     * @var bool
     */
    private static $noEvents = FALSE;
    
    /**
     * @var array
     */
    private static $isIncludeableCache = array();
    
    
    /**
     * Creates a new Pimcore_File instance with the provided filepath.
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
     * is true, the contents will be written to filesystem. Otherwise, the save function
     * still must be called for the contents to be written.
     * 
     * @access public
     * @param string $contents The contents of the file.
     * @param string $write If TRUE, the new contents will also be written to the filesystem.
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
     * Loads the file contents from the filesystem.
     * 
     * @access public
     * @return string The contents of the file, or FALSE on failure
     */
    public function loadContents() {
    	if(!is_file($this->getPath()) or !is_readable($this->getPath())) {
    		return FALSE;
    	} else {
    		$contents = file_get_contents($this->getPath());
	    	$this->setContents($contents);
	    	return $contents;
    	}
    }


	/**
	 * Returns TRUE if this Pimcore_File object represents a folder. If a boolean is passed,
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
     * Writes the file's contents to the filesystem. If a file already exists in its
     * location, it will be overwritten. If the destination directory does not exist, 
     * it will be created. Files will not overwrite existing folders, and folders
     * will not overwrite existing files.
     * 
     * @access public
     * @return bool|int The number of bytes that were written to the file, or FALSE on failure.
     */
    public function save() {
    	$exists = $this->exists();
    	$destinationPath = $this->getPath();
        $eventType = $exists ? Pimcore_Event::EVENT_TYPE_FILE_MODIFIED : Pimcore_Event::EVENT_TYPE_FILE_CREATED;
        
    	$this->dispatchPreEvent($eventType);
    	
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
        
        // don't dispatch event if save failed
        if ($result !== FALSE) {
        	chmod($destinationPath, $this->getChmod());
        	$this->dispatchPostEvent($eventType);
        }
        
        return $result;
    }
    
    
    /**
     * Moves the file to a new location. If a file exists at the destination,
     * it will be overwritten. If the destination directory does not exist, 
     * it will be created.
     * 
     * @access public
     * @param mixed $destination The destination path of the moved file.
     * @return void Returns TRUE on success or FALSE on failure.
     */
    public function move($destination) {
    	if(!$this->fileExists()) return FALSE;
    	
    	$destFile = new Pimcore_File($destination);
    	$destFile->setContents($this->loadContents());
    	$result = $destFile->save();
    	
    	if($result !== FALSE) {
    		$this->delete();
    		$this->setPath($destination);
    	}
    	
    	return $result;
    }
    
    
    /**
     * Makes a copy of the file. If a file exists at the destination,
     * it will be overwritten. If the destination directory does not exist, 
     * it will be created.
     * 
     * @access public
     * @param string $destination The destination path
     * @return bool Returns the result of the save operation.
     */
    public function copy($destination) {
    	if(empty($this->contents)) {
    		$this->loadContents();
    	}
    	
    	$fileCopy = new Pimcore_File($destination);
    	$fileCopy->setContents($this->getContents());
    	return $fileCopy->save();
    }


	/**
     * Deletes the file from the filesystem.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function delete() {
		$path = $this->getPath();	
		$this->dispatchPreEvent(Pimcore_Event::EVENT_TYPE_FILE_DELETED);
		
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
                
        $this->dispatchPostEvent(Pimcore_Event::EVENT_TYPE_FILE_DELETED);
	}
	
	
	/**
     * Tells whether a file or folder exists at the object's path.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function exists() {
		return file_exists($this->getPath());
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
     * Disables dispatching hooks for the current process.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public static function disableEvents() {
		self::$noEvents = TRUE;
	}
	
	
	/**
     * Re-enables dispatching hooks for the current process.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public static function enableEvents() {
		self::$noEvents = FALSE;
	}
	
	
	/**
	 * Dispatches a plugin "pre" event for this file.
	 * 
	 * @access public
	 * @param int $type The constant representing the type of file event to be dispatched. (default: NULL)
	 * @return void
	 */
	public function dispatchPreEvent($type = NULL) {
		if(self::$noEvents) return;
	
		switch($type) {
			case Pimcore_Event::EVENT_TYPE_FILE_CREATED:
				Pimcore_API_Plugin_Broker::getInstance()->preFileChange(new Pimcore_Event_File_Created($this));
			break;
			case Pimcore_Event::EVENT_TYPE_FILE_MODIFIED:
				Pimcore_API_Plugin_Broker::getInstance()->preFileChange(new Pimcore_Event_File_Modified($this));
			break;
			case Pimcore_Event::EVENT_TYPE_FILE_DELETED:
				Pimcore_API_Plugin_Broker::getInstance()->preFileChange(new Pimcore_Event_File_Deleted($this));
			break;
			default:
				Pimcore_API_Plugin_Broker::getInstance()->preFileChange(new Pimcore_Event_File($this));
			break;
		}
		
	}
	
	
	/**
	 * Dispatches a plugin "post" event for this file.
	 * 
	 * @access public
	 * @param int The constant representing the type of file event to be dispatched. (default: NULL)
	 * @return void
	 */
	public function dispatchPostEvent($type = NULL) {
		if(self::$noEvents) return;
	
		switch($type) {
			case Pimcore_Event::EVENT_TYPE_FILE_CREATED:
				Pimcore_API_Plugin_Broker::getInstance()->postFileChange(new Pimcore_Event_File_Created($this));
			break;
			case Pimcore_Event::EVENT_TYPE_FILE_MODIFIED:
				Pimcore_API_Plugin_Broker::getInstance()->postFileChange(new Pimcore_Event_File_Modified($this));
			break;
			case Pimcore_Event::EVENT_TYPE_FILE_DELETED:
				Pimcore_API_Plugin_Broker::getInstance()->postFileChange(new Pimcore_Event_File_Deleted($this));
			break;
			default:
				Pimcore_API_Plugin_Broker::getInstance()->postFileChange(new Pimcore_Event_File($this));
			break;
		}
		
	}
	
	
	public function getChmod() {
		return 0766;
	}


    /**
     * @static
     * @param  $name
     * @return string
     */
    public static function getFileExtension($name) {
        
        $name = strtolower($name);
        $parts = explode(".", $name);

        if(count($parts) > 1) {
            return strtolower($parts[count($parts) - 1]);
        }
        return "";
    }

    /**
     * @static
     * @param  $tmpFilename
     * @return string
     */
    public static function getValidFilename($tmpFilename) {
        
        $tmpFilename = Pimcore_Tool_Transliteration::toASCII($tmpFilename);
        $validChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-_.~";
        $filenameParts = array();

        for ($i = 0; $i < strlen($tmpFilename); $i++) {
            if (strpos($validChars, $tmpFilename[$i]) !== false) {
                $filenameParts[] = $tmpFilename[$i];
            }
            else {
                $filenameParts[] = "_";
            }
        }

        return strtolower(implode("", $filenameParts));
    }

    /**
     * @static
     * @param  $filename
     * @return bool
     */
    public static function isIncludeable($filename) {

        if(array_key_exists($filename,self::$isIncludeableCache)) {
            return self::$isIncludeableCache[$filename];
        }

        $include_paths = explode(PATH_SEPARATOR, get_include_path());
        $isIncludeAble = false;

        foreach ($include_paths as $path) {
            $include = $path.DIRECTORY_SEPARATOR.$filename;
            if (@is_file($include) && @is_readable($include)) {
                $isIncludeAble = true;
                break;
            }
        }
        
        // add to store
        self::$isIncludeableCache[$filename] = $isIncludeAble;

        return $isIncludeAble;
    }
}
