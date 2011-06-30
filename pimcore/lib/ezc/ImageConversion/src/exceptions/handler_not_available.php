<?php
/**
 * File containing the ezcImageHandlerNotAvailableException.
 * 
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Thrown if a specified handler class is not available.
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageHandlerNotAvailableException extends ezcImageException
{
    /**
     * Creates a new ezcImageHandlerNotAvailableException.
     * 
     * @param string $handlerClass Name of the affected class.
     * @param string $reason       Reason why it is not available.
     * @return void
     */
    function __construct( $handlerClass, $reason = null )
    {
        $reasonPart = "";
        if ( $reason )
        {
            $reasonPart = " $reason";
        }
        parent::__construct( "Handler class '{$handlerClass}' not found.{$reasonPart}" );
    }
}

?>
