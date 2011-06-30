<?php
/**
 * File containing the ezcImageAnalyzerInvalidHandlerException.
 * 
 * @package ImageAnalysis
 * @version 1.1.3
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * A registered handler class does not exist or does not inherit from ezcImageAnalyzerHandler.
 *
 * @package ImageAnalysis
 * @version 1.1.3
 */
class ezcImageAnalyzerInvalidHandlerException extends ezcImageAnalyzerException
{
    /**
     * Creates a new ezcImageAnalyzerInvalidHandlerException.
     * 
     * @param string $handlerClass Invalid class name.
     * @return void
     */
    function __construct( $handlerClass )
    {
        parent::__construct( "The registered handler class '{$handlerClass}' does not exist or does not inherit from ezcImageAnalyzerHandler." );
    }
}

?>
