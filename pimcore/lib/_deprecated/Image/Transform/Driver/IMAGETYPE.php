<?php
/**
 * Backward compatibility for PHP < 4.3.0
 *
 * @author Philippe.Jausions @at@ 11abacus.com
 **/

if (!defined('IMAGETYPE_GIF')) {
    define('IMAGETYPE_GIF',     1);
    define('IMAGETYPE_JPEG',    2);
    define('IMAGETYPE_PNG',     3);
    define('IMAGETYPE_SWF',     4);
    define('IMAGETYPE_PSD',     5);
    define('IMAGETYPE_BMP',     6);
    define('IMAGETYPE_TIFF_II', 7);
    define('IMAGETYPE_TIFF_MM', 8);
    define('IMAGETYPE_JPC',     9);
    define('IMAGETYPE_JP2',    10);
    define('IMAGETYPE_JPX',    11);
    define('IMAGETYPE_JB2',    12);
    define('IMAGETYPE_SWC',    13);
    define('IMAGETYPE_IFF',    14);
    define('IMAGETYPE_WBMP',   15);
    define('IMAGETYPE_XBM',    16);
}

?>