<?php
/**
 * File containing the ezcImageTransformationAlreadyExistsException.
 * 
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Thrown if a transformation with the given name already exists.
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageTransformationAlreadyExistsException extends ezcImageException
{
    /**
     * Creates a new ezcImageTransformationAlreadyExistsException.
     * 
     * @param string $name Name of the collision transformation.
     * @return void
     */
    function __construct( $name )
    {
        parent::__construct( "Transformation '{$name}' already exists." );
    }
}

?>
