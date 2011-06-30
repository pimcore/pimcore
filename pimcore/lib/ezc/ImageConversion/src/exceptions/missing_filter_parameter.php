<?php
/**
 * File containing the ezcImageMissingFilterParameter.
 * 
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Thrown if an expected parameter for a filter was not submitted.
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageMissingFilterParameterException extends ezcImageException
{
    /**
     * Creates a new ezcImageMissingFilterParameterException.
     * 
     * @param string $filterName    Affected filter.
     * @param string $parameterName Affected parameter.
     * @return void
     */
    function __construct( $filterName, $parameterName )
    {
        parent::__construct( "The filter '{$filterName}' expects a parameter called '{$parameterName}' which was not submitted." );
    }
}

?>
