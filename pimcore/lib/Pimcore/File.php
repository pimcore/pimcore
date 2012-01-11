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
	 * @var string
	 */
	private $filepath;
	
	/**
	 * @var string
	 */
	private $oldpath;
	
	/**
	 * @var int
	 */
	private $chmod = 0766;

	/**
     * @var Pimcore_File_Adapter
     */
    private $adapter;
    
	/**
     * @var string
     */
    private static $defaultAdapterName = 'Pimcore_File_Adapter_Disk';
    
    /**
     * @var array
     */
    private static $isIncludeableCache = array();
    
    
    /**
     * Creates a new Pimcore_File instance with the provided filepath.
     * 
     * @access public
     * @param mixed $path The full filepath for the file. (default: null)
     * @param mixed $isDir Whether this file represents a folder. (default: false)
     */
    public function __construct($path = null, $adapterName = null) {
    	$this->adapter = $this->getAdapter($adapterName);
    	$this->setPath($path);
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
    	return $this->adapter->copy($this, $destination);
    }
    
    
    /**
     * Deletes the file from the filesystem.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function delete() {
		$this->dispatchPreEvent(Pimcore_Event::EVENT_TYPE_FILE_DELETED);
		
		$result = $this->adapter->delete($this);
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
		return $this->adapter->exists($this);
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
    	if(!isset($adapterName)) {
    		$config = Pimcore_Config::getSystemConfig()->toArray();
	        
	        if(!empty($config["files"]["adapter"])) {
        		$adapterName = $config["files"]["adapter"];
	        } else {
	        	$adapterName = self::$defaultAdapterName;
	        }
    	}
    	
    	if(Zend_Registry::isRegistered($adapterName)) {
            $adapter = Zend_Registry::get($adapterName);
        } else {
        	$adapter = new $adapterName();
        	Zend_Registry::set($adapterName, $adapter);
        }
		
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
     * Detects and returns the file's mime type.
     * 
     * @access public
     * @return string The mime type of the file.
     */
	public function getMimeType() {
		return $this->adapter->getMimeType($this);
	}
	
	
	/**
	 * Returns the filesystem mode for saving new files or folders.
	 * 
	 * @access public
	 * @return int
	 */
	public function getChmod() {
		return $this->chmod;
	}
	
	
	/**
     * Returns the filepath.
     * 
     * @access public
     * @return string
     */
    public function getPath() {
    	return $this->filepath;
    }
    
    
    /**
     * Returns the old filepath if a file has been moved.
     * 
     * @access public
     * @return string
     */
    public function getOldPath() {
    	return $this->oldpath;
    }
	
	
	/**
     * Tells whether a folder exists at the object's path.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function isDir() {
		return $this->adapter->isDir($this);
	}
	
	
	/**
     * Tells whether a file exists at the object's path.
     * 
     * @access public
     * @return bool Returns TRUE on success or FALSE on failure.
     */
	public function isFile() {
		return $this->adapter->isFile($this);
	}
	
	
	/**
	 * Returns TRUE if this Pimcore_File object represents a folder. If a boolean is passed,
	 * it sets whether this object represents a folder.
	 * 
	 * @access public
	 * @param bool $isDir If passed, sets whether this file is a directory. (default: NULL)
	 * @return bool TRUE if the current file is a directory, FALSE otherwise
	 */
	public function isDirectoryType() {
    	return $this instanceof Pimcore_File_Directory;
    }
    
    
    /**
     * Loads the file contents.
     * 
     * @access public
     * @return string The contents of the file, or FALSE on failure
     */
    public function loadContents() {
    	return $this->adapter->load($this);
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
    	$this->dispatchPreEvent(Pimcore_Event::EVENT_TYPE_FILE_MOVED);
    	$result = $this->adapter->move($this, $destination);
    	
    	if($result !== FALSE) {
    		$this->dispatchPostEvent(Pimcore_Event::EVENT_TYPE_FILE_MOVED);
    	}
    }
    
    
    /**
     * Writes the file's contents to the filesystem. If a file already exists in its
     * location, it will be overwritten. If the destination directory does not exist, 
     * it will be created. Files will not overwrite existing folders, and folders
     * will not overwrite existing files.
     * 
     * @access public
     * @param $contents The contents to save to the file.
     * @return bool|int The number of bytes that were written to the file, or FALSE on failure.
     */
    public function save($contents = NULL) {
    	$eventType = $this->exists() ? Pimcore_Event::EVENT_TYPE_FILE_MODIFIED : Pimcore_Event::EVENT_TYPE_FILE_CREATED;
    	$this->dispatchPreEvent($eventType);
    	
    	$result = $this->adapter->save($this, $contents);
    	if($result !== FALSE) {
    		$this->dispatchPostEvent($eventType);
    	}
    	
    	return $result;
    }
    
    
    /**
     * Sets the adapter type for this file to use.
     * 
     * @access public
     * @param mixed $adapterName The full classname of the adapter to use.
     * @return void
     */
    public function setAdapter($adapterName) {
    	$this->adapter = $this->getAdapter($adapterName);
    }
    
    
    /**
	 * Sets the filesystem mode for saving files or folders. This 
	 * only takes effect if the save method is called.
	 * 
	 * @access public
	 * @param int $chmod
	 * @return void
	 */
	public function setChmod($chmod) {
		$this->chmod = $chmod;
	}
    
    
    /**
     * Sets the filepath.
     * 
     * @access public
     * @param string $path
     * @return void
     */
    public function setPath($path) {
    	$this->filepath = $path;
    }
    
    
    /**
     * Sets the old filepath for moved files.
     * 
     * @access public
     * @param string $path
     * @return void
     */
    public function setOldPath($path) {
    	$this->oldpath = $path;
    }
	
	
	/**
	 * Dispatches a plugin "pre" event for this file.
	 * 
	 * @access public
	 * @param int $type The constant representing the type of file event to be dispatched. (default: NULL)
	 * @return void
	 */
	public function dispatchPreEvent($type = NULL) {
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
			case Pimcore_Event::EVENT_TYPE_FILE_MOVED:
				Pimcore_API_Plugin_Broker::getInstance()->preFileChange(new Pimcore_Event_File_Moved($this));
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
			case Pimcore_Event::EVENT_TYPE_FILE_MOVED:
				Pimcore_API_Plugin_Broker::getInstance()->postFileChange(new Pimcore_Event_File_Moved($this));
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
