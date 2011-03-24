<?php
/**
 * Csv_Dialect
 * 
 * Tells readers and writers the format of a csv file. No properties of this class should be specific
 * to a certain csv file. It is tempting to add things like column-to-header mapping inside of this
 * class, but this ties a dialect too closely to a specific csv file. I'd like to avoid that. This
 * class should be as generic as possible. All it does is describe formatting.
 * 
 * Please read the LICENSE file
 * 
 * @package 	PHP CSV Utilities
 * @subpackage  Dialects
 * @copyright 	(c) 2010 Luke Visinoni <luke.visinoni@gmail.com>
 * @author 		Luke Visinoni <luke.visinoni@gmail.com>
 * @license 	GNU Lesser General Public License
 * @version 	$Id: Dialect.php 81 2010-04-22 02:24:16Z luke.visinoni $
 */
class Csv_Dialect {

    /**
     * Instructs Csv_Writer to quote only columns with special characters such as the
     * delimiter character, quote character or any of the characters in line terminator
     */
    const QUOTE_MINIMAL = 0;
    
    /**
     * Instructs Csv_Writer to quote all columns
     */
    const QUOTE_ALL = 1;
    
    /**
     * Instructs Csv_Writer to quote all columns that aren't numeric
     */
    const QUOTE_NONNUMERIC = 2;
    
    /**
     * Instructs Csv_Writer to quote no columns
     */
    const QUOTE_NONE = 3;
    
    /**
     * @var string The character used to seperate fields in a csv file
     */
    public $delimiter = ",";
    
    /**
     * @var string The character used to quote columns
     */
    public $quotechar = '"';
    
    /**
     * @var string The character used to escape the quotechar if it appears in a column
     */
    public $escapechar = "\\";
    
    /**
     * @var string This is a remnant of me copying functionality from python's csv module
     * @todo Implement this
     */
    public $skipinitialspace;
    
    /**
     * @var boolean Set to true to ignore blank lines when reading
     */
    public $skipblanklines = true;
    
    /**
     * @var string The character(s) used to terminate a line in the csv file
     */
    public $lineterminator = "\r\n";
    
    /**
     * @var integer Set to any of the self::QUOTE_* constants above
     */
    public $quoting = self::QUOTE_NONE;
    
    public function __construct($options = null) {
    
        if (is_array($options)) {
            //pr($options);
            $properties = array();
            foreach ($this as $property => $value) $properties[$property] = $value;
            foreach (array_intersect_key($options, $properties) as $property => $value) {
                $this->{$property} = $value;
            }
        }
    
    }
    
    public function __toString() {
    
        ob_start();
        var_dump($this);
        return ob_get_clean();
    
    }

}
