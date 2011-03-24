<?php
/**
 * Abstract CSV Reader
 * 
 * All CSV readers should extend this class. It provides the basic structure
 * and functionality of a CSV reader object.
 *
 * @package     PHP CSV Utilities
 * @subpackage  Csv_Reader
 * @author      Luke Visinoni <luke.visinoni@gmail.com>
 * @copyright   (c) 2010, Luke Visinoni <luke.visinoni@gmail.com>
 * @license 	GNU Lesser General Public License
 * @version     $Id: Abstract.php 81 2010-04-22 02:24:16Z luke.visinoni $
 */
abstract class Csv_Reader_Abstract implements Iterator, Countable {

    /**
     * Tells reader how to read the file
     * @var Csv_Dialect
     * @access protected
     */
    protected $dialect;
    
    /**
     * Maximum row size
     */
    protected $maxRowSize = 4096;
    
    /**
     * The currently loaded row
     * @var array
     * @access public
     * @todo: Should this be public? I think it might have been required for ArrayIterator to work properly
     */
    protected $currentRow = array();
    
    /**
     * This is the current line position in the file we're reading 
     * @var integer
     */
    protected $position = 0;
    
    /**
     * Number of lines skipped due to malformed data
     * @var integer
     * @todo This may be flawed - be sure to test it thoroughly
     */
    protected $skippedLines = 0;
    
	/**
     * An array of values to use as the header row - allows to reference by key
     */
    protected $header = array();
    
    /**
     * Constructor
     */
    abstract public function __construct($source, Csv_Dialect $dialect);
    
    /**
     * Get the current Csv_Dialect object
     *
     * @return The current Csv_Dialect object
     * @access public
     */
    public function getDialect() {
    
        return $this->dialect;
    
    }
	
    /**
     * Change the dialect this csv reader is using
     *
     * @param Csv_Dialect the current Csv_Dialect object
     * @access public
     */
    public function setDialect(Csv_Dialect $dialect) {
    
        $this->dialect = $dialect;
    
    }
    
    /**
     * 
     * 
     * @param boolean 
     * @access public
     */
    public function hasHeader($flag = null) {
    
        return $this->dialect->hasHeader();
    
    }
	
    /**
     * Use this method if your csv file doesn't have a header row and you want the reader to pretend that it does,
     * pass an array of column names in the order they appear in the csv file and it will return associative arrays
     * with this array as keys
     *
     * @param array An array of column names you would like to use as the "header row"
     * @access public
     */
    public function setHeader($header) {
    
        $row = $this->current();
        if (count($row) != count($header)) throw new Csv_Exception_InvalidHeaderRow('A header row should contain the same amount of columns as the data');
        $this->header = $header;
    
    }
    
    protected function detectDialect($data) {
    
        // if dialect isn't specified in the constructor, the reader will attempt to figure out the format
        $autodetect = new Csv_AutoDetect();
        return $autodetect->detect($data);
    
    }
    
    /**
     * Removes the escape character in front of our quote character
     *
     * @param string The input we are unescaping
     * @param string The key of the item
     * @todo Is the second param necssary? I think it is because array_walk
     */
    protected function unescape(&$item, $key) {
    
        $item = str_replace($this->dialect->escapechar.$this->dialect->quotechar, $this->dialect->quotechar, $item);
    
    }
    
    /**
     * Returns the current row and calls advances internal pointer
     * 
     * @access public
     */
    public function getRow() {
    
        $return = $this->current();
        $this->next();
        return $return;
    
    }
    
    /**
     * Get number of lines that were skipped
     * @todo probably should return an array with actual data instead of just the amount
     */
    public function getSkippedLines() {
    
        return $this->skippedlines;
    
    }
	
    /**
     * Returns csv data as an array
     * @todo if reader has been given a header row it is used as keys
     */
    public function toArray() {
    
        $return = array();
        $this->rewind();
        while ($row = $this->getRow()) {
            $return[] = $row;
        }
        
        // be kinds, please rewind
        $this->rewind();
        return $return;
    
    }
	
    abstract protected function loadRow();
    
    /*
    abstract public function next();
    
    abstract public function rewind();
    
    abstract public function valid();
    
    abstract public function current();
    
    abstract public function key();
    
    abstract public function count();
    */

}
