<?php
/**
 * File containing the ezcImageConverterSettings struct.
 *
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * Struct to store the settings for objects of ezcImageConverter.
 *
 * This class is used as a struct for the settings of ezcImageConverter.
 *
 * @see ezcImageConverter
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageConverterSettings extends ezcBaseStruct
{
    /**
     * Array with {@link ezcImageHandlerSettings handler settings} objects.
     * Each settings objects is consulted by the converter to figure out which
     * {@link ezcImageHandler image handlers} to use.
     *
     * @see ezcImageHandler
     * @see ezcImageGdHandler
     * @see ezcImageImagemagickHandler
     *
     * @var array(ezcImageHandlerSettings)
     */
    public $handlers = array();

    /**
     * Map of automatic MIME type conversions. The converter will automatically
     * perform the defined conversions when a transformation is applied through
     * it and the specific MIME type is recognized.
     *
     * The conversion map has the following structure:
     * <code>
     * array(
     *     'image/gif' => 'image/png',  // Note: lower case!
     *     'image/bmp' => 'image/jpeg',
     * )
     * </code>
     *
     * @var array
     */
    public $conversions = array();

    /**
     * Create a new instance of ezcImageConverterSettings.
     * Create a new instance of ezcImageConverterSettings to be used with
     * {@link ezcImageConverter} objects..
     *
     * @see ezcImageConverterSettings::$handlers
     * @see ezcImageConverterSettings::$conversions
     *
     * @param array $handlers    Array of {@link ezcImageHandlerSettings handler objects}.
     * @param array $conversions Map of standard MIME type conversions.
     */
    public function __construct( array $handlers = array(), array $conversions = array() )
    {
        $this->handlers    = $handlers;
        $this->conversions = $conversions;
    }
}
?>
