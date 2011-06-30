<?php
/**
 * File containing the ezcImageAnalyzerFileNotProcessableException.
 * 
 * @package ImageAnalysis
 * @version 1.1.3
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * The option name you tried to register is already in use.
 *
 * @package ImageAnalysis
 * @version 1.1.3
 */
class ezcImageAnalyzerFileNotProcessableException extends ezcImageAnalyzerException
{
    /**
     * Creates a new ezcImageAnalyzerFileNotProcessableException.
     * 
     * @param string $file   Not processable file.
     * @param string $reason Reason that the file is not processable.
     * @return void
     */
    function __construct( $file, $reason = null )
    {
        $reasonPart = '';
        if ( $reason )
        {
            $reasonPart = " Reason: $reason.";
        }
        parent::__construct( "Could not process file '{$file}'.{$reasonPart}" );
    }
}

?>
