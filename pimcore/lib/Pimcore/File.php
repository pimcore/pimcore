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

class Pimcore_File {

	/**
     * @var Pimcore_File_Adapter
     */
    private $adapter;
    
	/**
     * @var string
     */
    private static $defaultAdapterName = 'Pimcore_File_Adapter_Disk';
    
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
     * @param mixed $path The full filepath for the file. (default: null)
     * @param mixed $isFolder Whether this file represents a folder. (default: false)
     */
    public function __construct($path = null, $isFolder = false) {
    	$this->adapter = $this->getAdapter();
    	$this->adapter->setPath($path);
    	$this->adapter->isFolder($isFolder);
    }
    
    
    /**
     * Internal function to retrieve the appropriate file adapter. Pimcore's disk 
     * file adapter is used by default unless a different one is specified 
     * in the system config.
     * 
     * @access private
     * @param mixed $adapterName. (default: null)
     * @return Pimcore_File_Adapter
     */
    private function getAdapter($adapterName = null) {
    	if(isset($adapterName)) {
    		$adapterName = new $adapterName();
    	} else {
    		$config = Pimcore_Config::getSystemConfig()->toArray();
	        
	        if(!empty($config["files"]["adapter"])) {
        		$adapterName = $config["files"]["adapter"];
	        } else {
	        	$adapterName = self::$defaultAdapterName;
	        }
    	}
    	
		$adapter = new $adapterName();
    	return $adapter;
    }
    
    
    /**
     * The class name of the adapter this file is currently using.
     * 
     * @access public
     * @return string The class name of the adapter.
     */
    public function getAdapterType() {
    	return get_class($this->adapter);
    }
    
    
    /**
     * Sets the adapter type for this file to use.
     * 
     * @access public
     * @param mixed $adapterName The full classname of the adapter to use.
     * @return void
     */
    public function setAdapter($adapterName) {
    	$adapter = $this->getAdapter($adapterName);
    	$adapter->setPath($this->adapter->getPath());
    	$adapter->isFolder($this->adapter->isFolder());
    	$this->adapter = $adapter;
    }
    
    
    /**
     * Returns the filepath.
     * 
     * @access public
     * @return string The full system filepath
     */
    public function getPath() {
    	return $this->adapter->getPath();
    }


    /**
     * Sets the filepath.
     * 
     * @access public
     * @param string $path The full system filepath.
     * @return void
     */
    public function setPath($path) {
    	$this->adapter->setPath($path);
    }
    
    
    /**
     * Returns this file's contents previously set by setContents or loaded by
     * loadContents. This function does not load the file's contents directly.
     * 
     * @access public
     * @return void
     */
    public function getContents() {
    	return $this->adapter->getContents();
    }


    /**
     * Sets the file contents prior to saving the file. If the $write parameter
     * is true, the contents will be written. Otherwise, the save function
     * still must be called to write the file.
     * 
     * @access public
     * @param string $contents The contents of the file.
     * @param string $write If TRUE, the new contents will also be saved to file.
     * @return bool If $write is TRUE, returns the result of the save operation. Returns TRUE otherwise.
     */
    public function setContents($contents, $write = FALSE) {
    	return $this->adapter->setContents($contents, $write = FALSE);
    }
    
    
    /**
     * Loads the file contents.
     * 
     * @access public
     * @return string The contents of the file, or FALSE on failure
     */
    public function loadContents() {
    	return $this->adapter->load();
    }


	/**
	 * Returns TRUE if this Pimcore_File object represents a folder. If a boolean is passed,
	 * it sets whether this object represents a folder.
	 * 
	 * @access public
	 * @param bool $isFolder If passed, sets whether this file is a directory. (default: NULL)
	 * @return bool TRUE if the current file is a directory, FALSE otherwise
	 */
	public function isFolder($isFolder = NULL) {
    	return $this->adapter->isFolder($isFolder);
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
    	$eventType = $this->exists() ? Pimcore_Event::EVENT_TYPE_FILE_MODIFIED : Pimcore_Event::EVENT_TYPE_FILE_CREATED;
    	$this->dispatchPreEvent($eventType);
    	
    	$result = $this->adapter->save();
    	if($result !== FALSE) {
    		$this->dispatchPostEvent($eventType);
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
     * Deletes the file from the filesystem.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function delete() {
		$this->dispatchPreEvent(Pimcore_Event::EVENT_TYPE_FILE_DELETED);
		
		$result = $this->adapter->delete();
		if($result !== FALSE) {
			$this->dispatchPostEvent(Pimcore_Event::EVENT_TYPE_FILE_DELETED);
		}
		
		return $result;
	}
	
	
	/**
     * Tells whether a file or folder exists at the object's path.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function exists() {
		return $this->adapter->exists();
	}
	
	
	/**
     * Tells whether a file exists at the object's path.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function fileExists() {
		return $this->adapter->fileExists();
	}
	
	
	/**
     * Tells whether a folder exists at the object's path.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function folderExists() {
		return $this->adapter->folderExists();
	}
	
	
	/**
     * Detects and returns the file's mime type.
     * 
     * @access public
     * @return string The mime type of the file.
     */
	public function getMimeType() {
		return $this->adapter->getMimeType();
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
