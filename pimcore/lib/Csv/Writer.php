<?php
/**
 * CSV Reader
 * 
 * Provides an easy-to-use interface for writing csv-formatted text files. It
 * does not make use of the PHP5 function fputcsv. It provides quite a bit of
 * flexibility. You can specify just about everything about how it writes csv
 *
 * Please read the LICENSE file
 * 
 * @copyright 	(c) 2010, Luke Visinoni <luke.visinoni@gmail.com>
 * @author 		Luke Visinoni <luke.visinoni@gmail.com>
 * @package 	PHP CSV Utilities
 * @subpackage 	Writers
 * @license 	GNU Lesser General Public License
 * @version 	$Id: Writer.php 81 2010-04-22 02:24:16Z luke.visinoni $
 */
class Csv_Writer
{
    /**
     * The filename of the file we're working on
     * @var string
     * @access protected
     */
    protected $filename;
    /**
     * Holds an instance of Csv_Dialect - tells writer how to write
     * @var Csv_Dialect 
     * @access protected
     */
    protected $dialect;
    /**
     * Holds the file resource
     * @var resource
     * @access protected
     */
    protected $handle;
    /**
     * Contains the in-menory data waiting to be written to disk
     * @var array
     * @access protected
     */
    protected $data = array();
    /**
     * Class constructor
     *
     * @param resource|string Either a valid filename or a valid file resource
     * @param Csv_Dialect A Csv_Dialect object
     * @todo: Allow the user to pass in a file handle (this way they can specify
     *        to append rather than overwrite or visa versa)
     */
    public function __construct($file, $dialect = null) {
    
        if (is_null($dialect)) $dialect = new Csv_Dialect();
        if (is_resource($file)) {
            $this->handle = $file;
		} else {
            $this->filename = $file;
		}
        $this->dialect = $dialect;
    
    }
    /**
     * Get the current Csv_Dialect object
     *
     * @returns Csv_Dialect object
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
     * Get the filename attached to this writer (unless none was specified)
     *
     * @return string|null The filename this writer is attached to or null if it
     *         was passed a resource and no filename
     * @todo Add a functions file so that you can use convenience functions like
     *       get('variable', 'default')
     */
    public function getPath() {
    
        return $this->filename;
    
    }
    /**
     * Write a single row to the file
     *
     * @param array An array representing a row of data to be written
     * @access public
     */
    public function writeRow(Array $row) {
    
        $this->data[] = $row;
        $this->writeData();
    
    }
    /**
     * Write multiple rows to file
     *
     * @param array An two-dimensional array representing rows of data to be written
     * @access public
     */
    public function writeRows($rows) {
    
        //if ($rows instanceof Csv_Writer) $rows->reset();
        foreach ($rows as $row) {
            $this->data[] = $row;
        }
        $this->writeData();
    
    }
    /**
     * Writes the data to the csv file according to the dialect specified
     *
     * @access protected
	 * @todo Maybe this should attempt to chmod the file/directory it is trying to create?
     */
    protected function writeData() {

        if (!is_resource($this->handle)) {
            if (!$this->handle = @fopen($this->filename, 'wb')) {
                // if parser reaches this, the file couldnt be created
                throw new Csv_Exception_FileNotFound(sprintf('Unable to create/access file: "%s".', $this->filename));
            }
        }
        $rows = array();
        foreach ($this->data as $row) {
            $rows[] = implode($this->formatRow($row), $this->dialect->delimiter);
        }
        $output = implode($rows, $this->dialect->lineterminator) . $this->dialect->lineterminator; // ensures that there is a line terminator at the end of the file, which is necessary
        fwrite($this->handle, $output);
        $this->data = array(); // data has been written, so empty it
    
    }
    /**
     * Accepts a row of data and returns it formatted according to $this->dialect
     * This method is called by writeData()
     * 
     * @param array An array of data to be formatted for output to the file
     * @access protected
     * @return array The formatted array (formatting determined by dialect)
     */
    protected function formatRow(Array $row) {
    
        foreach ($row as &$column) {
            switch($this->dialect->quoting) {
                case Csv_Dialect::QUOTE_NONE:
                    // do nothing... no quoting is happening here
                    break;                
                case Csv_Dialect::QUOTE_ALL:
                    $column = $this->quote($this->escape($column));
                    break;                
                case Csv_Dialect::QUOTE_NONNUMERIC:
                    if (preg_match("/[^0-9]/", $column))
                        $column = $this->quote($this->escape($column));
                    break;
                case Csv_Dialect::QUOTE_MINIMAL:
                default:
                    if ($this->containsSpecialChars($column)) 
                        $column = $this->quote($this->escape($column));
                    break;            
            }
        }
        return $row;
    
    }
    /**
     * Escapes a column (escapes quotechar with escapechar)
     *
     * @param string A single value to be escaped for output
     * @return string Escaped input value
     * @access protected
     */
    protected function escape($input) {
    
        return str_replace(
            $this->dialect->quotechar,
            $this->dialect->escapechar . $this->dialect->quotechar,
            $input
        );
    
    }
    /**
     * Quotes a column with quotechar
     *
     * @param string A single value to be quoted for output
     * @return string Quoted input value
     * @access protected
     */
    protected function quote($input) {
    
        return $this->dialect->quotechar . $input . $this->dialect->quotechar;
    
    }
    /**
     * Returns true if input contains quotechar, delimiter or any of the characters in lineterminator
     *
     * @param string A single value to be checked for special characters
     * @return boolean True if contains any special characters
     * @access protected
     */
    protected function containsSpecialChars($input) {
    
        $special_chars = str_split($this->dialect->lineterminator, 1);
        $special_chars[] = $this->dialect->quotechar;
        $special_chars[] = $this->dialect->delimiter;
        foreach ($special_chars as $char) {
            if (strpos($input, $char)) return true;
        }
    
    }
    /**
     * When the object is destroyed, if there is still data waiting to be written to disk, write it
     *
     * @access public
     */
    public function __destruct() {
    
        if (is_resource($this->handle)) fclose($this->handle);
    
    }
}
