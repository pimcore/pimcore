<?php
/**
 * File containing the ezcImageFileNotProcessableException.
 * 
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Thrown if a file could not be processed by a handler.
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageFileNotProcessableException extends ezcImageException
{
    /**
     * Creates a new ezcImageFileNotProcessableException.
     * 
     * @param string $file   The not processable file.
     * @param string $reason The reason why the file could not be processed.
     * @return void
     */
    function __construct( $file, $reason = null )
    {
        $reasonPart = "";
        if ( $reason )
        {
            $reasonPart = " $reason";
        }
        parent::__construct( "File '{$file}' could not be processed.{$reasonPart}" );
    }
}

?>
