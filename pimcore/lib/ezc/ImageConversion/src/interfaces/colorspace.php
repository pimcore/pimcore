<?php
/**
 * File containing the ezcImageColorspaceFilters interface.
 *
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * This interface has to implemented by ezcImageFilters classes to
 * support colorspace filters.
 *
 * @see ezcImageHandler
 * @see ezcImageTransformation
 * @see ezcImageFiltersInterface
 *
 * @package ImageConversion
 * @version 1.3.8
 */
interface ezcImageColorspaceFilters
{
    /**
     * Grey color space.
     * 
     * @var int
     */
    const COLORSPACE_GREY = 1;

    /**
     * Sepia color space.
     * 
     * @var int
     */
    const COLORSPACE_SEPIA = 2;

    /**
     * Monochrome color space.
     * 
     * @var int
     */
    const COLORSPACE_MONOCHROME = 3;

    /**
     * Colorspace filter.
     * Transform the colorspace of the picture. The following colorspaces are 
     * supported:
     *
     * - {@link self::COLORSPACE_GREY} - 255 grey colors
     * - {@link self::COLORSPACE_SEPIA} - Sepia colors
     * - {@link self::COLORSPACE_MONOCHROME} - 2 colors black and white
     * 
     * @param int $space Colorspace, one of self::COLORSPACE_* constants.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageFilterFailedException
     *         If the parameter submitted as the colorspace was not within the 
     *         self::COLORSPACE_* constants
     *         If the operation performed by the the filter failed.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    function colorspace( $space );
}
?>
