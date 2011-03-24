<?php
/**
 * CSV Raw String Reader
 * Reads raw CSV data as if it were a file.
 *
 * @package     PHP CSV Utilities
 * @subpackage  Readers
 * @author      Luke Visinoni <luke.visinoni@gmail.com>
 * @copyright   (c) 2010, Luke Visinoni <luke.visinoni@gmail.com>
 * @license 	GNU Lesser General Public License
 * @version     $Id: String.php 81 2010-04-22 02:24:16Z luke.visinoni $
 */
class Csv_Reader_String extends Csv_Reader {

    /**
     * Build a Csv Reader from a string. 
	 * @todo I am beginning to think that the default should be that it accepts a string. 
     */
    public function __construct($string, Csv_Dialect $dialect = null) {
    
        if (is_null($dialect)) {
            $dialect = $this->detectDialect($string);
        }
        $this->dialect = $dialect;
        // if last character isn't a line-break add one
        $lastchar = substr($string, strlen($string) - 1, 1);
        if ($lastchar !== $dialect->lineterminator) $string = $string . $dialect->lineterminator;
		$this->initStream($string);
        $this->rewind();
    
    }
	
	protected function initStream($string) {
	
        $this->handle = fopen("php://memory", 'w+'); // not sure if I should use php://memory or php://temp here
        fwrite($this->handle, $string);
        if ($this->handle === false) {
			// throw new Csv_Reader_Exception_CannotReadMemoryStream('PHP cannot access the php://memory stream.');
		}
	
	}

}
