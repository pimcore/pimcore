<?php
/**
 * Excel Dialect
 * 
 * This is the dialect that is most similar to the way Microsoft Excel outputs
 * CSV data (actually I haven't checked, so maybe it isn't).
 * 
 * Please read the LICENSE file
 * 
 * @package 	PHP CSV Utilities
 * @subpackage  Dialects
 * @copyright 	(c) 2010 Luke Visinoni <luke.visinoni@gmail.com>
 * @author 		Luke Visinoni <luke.visinoni@gmail.com>
 * @license 	GNU Lesser General Public License
 * @version 	$Id: Excel.php 81 2010-04-22 02:24:16Z luke.visinoni $
 */
class Csv_Dialect_Excel extends Csv_Dialect {

    public $delimiter = ',';
    public $quotechar = '"';
    public $escapechar = "\\";
    // public $doublequote = true;
    // public $skipinitialspace = false;
    public $lineterminator = "\r\n";
    public $quoting = Csv_Dialect::QUOTE_MINIMAL;

}
