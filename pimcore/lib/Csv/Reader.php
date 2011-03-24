<?php
/**
 * CSV Reader
 * 
 * Provides an easy-to-use interface for reading csv-formatted text files. It
 * makes use of the function fgetcsv. It provides quite a bit of flexibility.
 * You can specify just about everything about how it should read a csv file
 *
 * Please read the LICENSE file
 * 
 * @copyright 	(c) 2010, Luke Visinoni <luke.visinoni@gmail.com>
 * @author 		Luke Visinoni <luke.visinoni@gmail.com>
 * @package 	PHP CSV Utilities
 * @subpackage 	Readers
 * @license 	GNU Lesser General Public License
 * @version 	$Id: Reader.php 81 2010-04-22 02:24:16Z luke.visinoni $
 */
class Csv_Reader extends Csv_Reader_Abstract {
	
    /**
     * Path to csv file
     * @var string
     * @access protected
     */
    protected $path;
	
    /**
     * A handle that points to the file we are reading
     * @var resource
     * @access protected
     */
    protected $handle;
    
	/**
     * Class constructor
     *
     * @param string Path to csv file we want to open
     * @param Csv_Dialect If a dialect is not provided, Csv_Reader will attempt to guess the file's dialect
     * @throws Csv_Exception_FileNotFound
     */
    public function __construct($path, Csv_Dialect $dialect = null) {
    
        // open the file
        $this->setPath($path);
		$this->initStream();
        if (is_null($dialect)) {
            // if dialect isn't specified in the constructor, the reader will attempt to figure out the format
			$dialect = $this->detectDialect(file_get_contents($this->path));
        }
        $this->dialect = $dialect;
        $this->rewind();
    
    }
	
    /**
     * Set the path to the csv file
     *
     * @param string The full path to the file we want to read
     * @access protected
     */
    protected function setPath($path) {
    
        $this->path = $path;
		if (!file_exists($this->path)) {
			throw new Csv_Exception_FileNotFound('File does not exist or is not readable: "' . $path . '".');
		}
		return $this;
    
    }
	
    /**
     * Get the path to the csv file we're reading
     *
     * @return string The path to the file we are reading
     * @access public
     */
    public function getPath() {
    
        return $this->path;
    
    }
	
	protected function initStream() {
	
		$this->handle = fopen($this->path, 'rb');
		if ($this->handle === false) {
			throw new Csv_Exception_FileNotFound('File does not exist or is not readable: "' . $path . '".');
		}
	
	}
	
    /**
     * Loads the current row into memory
     * 
     * @access protected
     * @todo Don't use fgetcsv - parse the file manually. I think this would allow much more control
     */
    protected function loadRow() {
    
        if (!$this->current = fgetcsv($this->handle, $this->maxRowSize, $this->dialect->delimiter, $this->dialect->quotechar)) {
            // we actually don't want to throw an exception... that's a little dramatic. maybe log it?
            // throw new Csv_Exception('Invalid format for row ' . $this->position);
        }
        if (
            $this->dialect->escapechar !== ''
            && $this->dialect->escapechar !== $this->dialect->quotechar
            && is_array($this->current)
        ) array_walk($this->current, array($this, 'unescape'));
        // if this row is blank and dialect says to skip blank lines, load in the next one and pretend this never happened
        if ($this->dialect->skipblanklines && is_array($this->current) && count($this->current) == 1 && $this->current[0] == '') {
            $this->skippedlines++;
            $this->next();
        }
    
    }
	
    /**
     * Get total rows
     *
     * @return integer The number of rows in the file (not includeing line-breaks in the data)
     * @todo Make sure that this is aware of line-breaks in data as opposed to end of row
     * @access public
     */
    public function close() {
    
        if (is_resource($this->handle)) fclose($this->handle);
    
    }
	
    /**
     * Destructor method - Closes the file handle
     * 
     * @access public
     */
    public function __destruct() {

        $this->close();

    }
    
    /**
     * The following are the methods required by php's Standard PHP Library - Iterator, Countable Interfaces
     */
    
    /**
     * Advances the internal pointer to the next row and returns it if valid, otherwise it returns false
     * 
     * @access public
     * @return boolean|array An array of data if valid, or false if not
     */
    public function next() {
    
        $this->position++;
        $this->loadRow(); // loads the current row into memory
        return ($this->valid()) ? $this->current() : false;
    
    }
	
    /**
     * Tells whether or not the current row is valid - called after next and rewind
     * 
     * @access public
     * @return boolean True if the current row is valid
     */
    public function valid() {
    
        if (is_resource($this->handle))
            return (boolean) !feof($this->handle);
        
        return false;
    
    }
	
    /**
     * Returns the current row 
     * 
     * @access public
     * @return array An array of the current row's data
     */
    public function current() {
    
        if (empty($this->header) || !$this->current) return $this->current;
        else return array_combine($this->header, $this->current);
    
    }
	
    /**
     * Moves the internal pointer to the beginning
     * 
     * @access public
     */
    public function rewind() {
    
        rewind($this->handle);
        $this->position = 0;
        $this->loadRow(); // loads the current (first) row into memory 
    
    }
	
    /**
     * Returns the key of the current row (position of pointer)
     * 
     * @access public
     * @return integer
     */
    public function key() {
    
        return (integer) $this->position;
    
    }
	
    /**
     * Returns the number of rows in the csv file
     * 
     * @access public
     * @return integer
     * @todo Should this remember the position the file was in or something?
     */
    public function count() {
    
        $lines = 0;
        foreach ($this as $row) $lines++;
        $this->rewind();
        return (integer) $lines;
    
    }
}
