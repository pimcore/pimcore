<?php
/**
 * File containing the ezcImageFilter struct.
 *
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * Struct to store information about a filter operation.
 *
 * The struct contains the {@link self::name name} of the filter to use and
 * which {@link self::options options} to use for it.
 *
 * Possible filter names are determined by the methods defined in the following
 * filter interfaces:
 *
 * <ul>
 *  <li>{@link ezcImageGeometryFilters}</li>
 *  <li>{@link ezcImageColorspaceFilters}</li>
 *  <li>{@link ezcImageEffectFilters}</li>
 *  <li>{@link ezcImageWatermarkFilters}</li>
 *  <li>{@link ezcImageThumbnailFilters}</li>
 * </ul>
 *
 * The options for each filter are represented by the parameters received by
 * their corresponding method. You can determine if a certain {@link
 * ezcImageHandler} implementation supports a filter by checking the interfaces
 * this handler implements.
 *
 * @see ezcImageTransformation
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageFilter extends ezcBaseStruct
{
    /**
     * Name of filter operation to use.
     *
     * @see ezcImageEffectFilters
     * @see ezcImageGeometryFilters
     * @see ezcImageColorspaceFilters
     *
     * @var string
     */
    public $name;

    /**
     * Associative array of options for the filter operation.
     * The array key is the option name and the array entry is the value for
     * the option.
     * Consult each filter operation to see which names and values to use.
     *
     * @see ezcImageEffectFilters
     * @see ezcImageGeometryFilters
     * @see ezcImageColorspaceFilters
     *
     * @var array(string=>mixed)
     */
    public $options;

    /**
     * Initialize with the filter name and options.
     *
     * @see ezcImageFilter::$name
     * @see ezcImageFilter::$options
     *
     * @param array $name    Name of filter operation.
     * @param array $options Associative array of options for filter operation.
     */
    public function __construct( $name, array $options = array() )
    {
        $this->name    = $name;
        $this->options = $options;
    }
}
?>
