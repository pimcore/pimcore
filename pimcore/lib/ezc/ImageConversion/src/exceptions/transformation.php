<?php
/**
 * File containing the abstract class ezcImageTransformationException.
 *
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * Exception to be thrown be ezcImageTransformation classes.
 *
 * This is a special exception which is used in ezcImageTransformation to 
 * catch all transformation exceptions. Purpose is to provide a catch
 * all for all transformation inherited excptions, that leaves the source
 * exception in tact for logging or analysis purposes.
 *
 * @see ezcImageTransformation
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageTransformationException extends ezcImageException
{

    /**
     * Stores the parent exception.
     * Each transformation exception is based on a parent, which can be any 
     * ezcImage* exception. The transformation exception deals as a collection 
     * container to catch all these exception at once.
     * 
     * @var ezcImageException
     */
    public $parent;
    
    /**
     * Creates a new ezcImageTransformationException using a parent exception. 
     * Creates a new ezcImageTransformationException and appends an existing
     * exception to it. The ezcImageTransformationException is just the catch-
     * all container. The parent is stored for logging/debugging purpose.
     * 
     * @param ezcBaseException $e Any exception that may occur during
     *                            transformation.
     */
    public function __construct( ezcBaseException $e )
    {
        $this->parent = $e;
        $message = $e->getMessage();
        parent::__construct( "Transformation failed. '{$message}'." );
    }

}
?>
