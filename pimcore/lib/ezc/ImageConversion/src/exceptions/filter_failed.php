<?php
/**
 * File containing the ezcImageFilterFailedException.
 * 
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Thrown if the given filter failed.
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageFilterFailedException extends ezcImageException
{
    /**
     * Creates a new ezcImageFilterFailedException.
     * 
     * @param string $filterName The failed filter.
     * @param string $reason     The reason why the filter failed.
     * @return void
     */
    function __construct( $filterName, $reason = null )
    {
        $reasonPart = "";
        if ( $reason )
        {
            $reasonPart = " $reason";
        }
        parent::__construct( "The filter '{$filterName}' failed.{$reasonPart}" );
    }
}

?>
