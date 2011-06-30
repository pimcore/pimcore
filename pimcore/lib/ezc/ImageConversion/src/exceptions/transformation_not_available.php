<?php
/**
 * File containing the ezcImageTransformationNotAvailableException.
 * 
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Thrown if a transformation with the given name does not exists.
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageTransformationNotAvailableException extends ezcImageException
{
    /**
     * Creates a new ezcImageTransformationNotAvailableException.
     * 
     * @param string $name Name of the missing transformation.
     * @return void
     */
    function __construct( $name )
    {
        parent::__construct( "Transformation '{$name}' does not exists." );
    }
}

?>
